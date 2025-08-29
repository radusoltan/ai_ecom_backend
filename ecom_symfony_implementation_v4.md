# Caiet de Sarcini – ECOM Platform **Symfony 7.3 Implementation** (Aligned with **PRD v4.0**)

> Scope: this document refines and completes the Symfony implementation so it matches the **ecom‑prd** specification across API shape (REST + GraphQL), multi‑tenancy, security (RBAC + ABAC), eventing, search, analytics, real‑time, performance and operations.

---

## 1) Technical Architecture

### 1.1 Technology Stack (updated)

* **Framework**: Symfony 7.3 (Business Logic Layer)
* **PHP**: 8.3+ (extensions: intl, pdo\_pgsql, redis, bcmath, apcu)
* **Database**: PostgreSQL 16+ with **Row‑Level Security (RLS)**
* **Cache**: Redis 7+ (namespaces per tenant)
* **Queues / Event Bus**: **RabbitMQ (AMQP 0-9-1)** as primary Messenger transport; Redis only for cache/ephemeral
* **Real‑time**: **WebSockets** (Ratchet/Symfony Runtime) with SSE/Mercure fallback
* **Observability**: Prometheus + Grafana; ELK (Filebeat/Logstash/Elastic/Kibana)
* **CI/CD**: GitHub/GitLab pipelines with Env‑specific deploy steps

### 1.2 Project Structure (DDD)

```
src/
├── Domain/                      # Core business logic per bounded context
│   ├── Tenant/ ...
│   ├── Catalog/ ...             # products, bundles, configurable, subscription
│   ├── Order/ ...               # entities, state machines, domain events
│   ├── Inventory/ ...
│   ├── Pricing/ ...             # PricingEngine, PromotionEngine (stacking)
│   ├── Tax/ ...                 # tax rules, jurisdictions
│   ├── Return/ ...              # RMA flows (inspection)
│   ├── Customer/ ...            # segmentation, loyalty
│   └── Payment/ ...
├── Application/                 # Commands/Queries, Sagas, Handlers
├── Infrastructure/
│   ├── Persistence/Doctrine/... # RLS helpers, tenant filters
│   ├── Bus/                     # Messenger transports + routing
│   ├── API/                     # REST Controllers + GraphQL
│   ├── Realtime/                # WS gateway, topic/auth
│   ├── Search/                  # ES clients/mappings
│   ├── Security/                # RBAC + ABAC (voters/policies)
│   └── Monitoring/              # metrics & tracing
└── Shared/                      # DTOs, ValueObjects, common middleware
```

### 1.3 API Modes (NEW)

* **REST** (API Platform): standard resources & custom Controllers for complex actions
* **GraphQL** (API Platform GraphQL): optimized for n+1 avoidance, product configurator trees, analytics drill‑downs
* **Response Envelope** (REST/GraphQL): unified `{status,data,meta,errors}` (see §3.3)

---

## 2) Multi‑Tenancy & Data Isolation

### 2.1 Tenant Context Resolution

Resolution order:

1. `tenant_id` claim in **JWT**
2. **Custom domain** → tenant mapping
3. **Subdomain** prefix → slug lookup
4. `X-API-Key` header → API key lookup
5. Fallback: **reject** (TenantNotFound)

### 2.2 PostgreSQL RLS Policies (enforced in DB)

```sql
-- Example for table: orders
ALTER TABLE orders ENABLE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation_select ON orders
  FOR SELECT USING (tenant_id = current_setting('app.tenant_id')::uuid);

CREATE POLICY tenant_isolation_write ON orders
  FOR INSERT WITH CHECK (tenant_id = current_setting('app.tenant_id')::uuid);
```

`SET app.tenant_id = :tenantId` is executed per request/worker using a Doctrine listener.

### 2.3 Doctrine Tenant Filter

```php
// src/Infrastructure/Persistence/Doctrine/Filter/TenantFilter.php
class TenantFilter extends SQLFilter {
    public function addFilterConstraint(ClassMetadata $metadata, $alias): string {
        if (!$metadata->hasField('tenantId')) return '';
        return sprintf('%s.tenant_id = %s', $alias, $this->getParameter('tenant_id'));
    }
}
```

Enable per request with tenant parameter bound from the resolved context.

---

## 3) API Design (REST + GraphQL)

### 3.1 REST Resources (selection)

* **/products** (GET/POST/PUT/DELETE)
* **/products/{id}/configuration** (GET)
* **/products/{id}/validate** (POST)
* **/products/{id}/price** (POST)
* **/bundles**, **/bundles/{id}/compose**, **/bundles/{id}/validate**
* **/orders** (GET/POST), **/orders/{id}** (GET), **/orders/{id}/status** (PUT), **/orders/{id}/cancel** (POST)
* **/cart**, **/cart/items**, **/cart/checkout**
* **/returns** (POST/GET/PUT), **/returns/{id}/inspection** (POST)

### 3.2 GraphQL (API Platform)

**Enable** GraphQL on core aggregates and expose specialized queries:

* `product(id)` with nested variant/configuration trees
* `searchProducts(query, filters, facets)` → ES backed
* `order(id)` with timeline & event stream

**Resolver skeleton**

```php
#[AsGraphQlResolver(
    entity: Product::class,
    operation: 'item_query'
)]
final class ProductResolver
{
    public function __invoke(Product $product, Context $context): ProductView
    {   // hydrate child options lazily, batch via DataLoader
        return $this->viewFactory->fromEntity($product, $context->tenant());
    }
}
```

### 3.3 Unified Response Envelope (REST)

Middleware wraps controller payloads:

```json
{
  "status": "success",
  "data": { /* controller payload */ },
  "meta": {
    "timestamp": "<ISO8601>",
    "request_id": "<uuid>",
    "tenant_id": "<tenant>",
    "pagination": {"cursor": "...", "has_more": true, "total": 123}
  },
  "errors": []
}
```

### 3.4 Pagination

* Default **cursor** pagination for large collections (id‑based cursor)
* Fallback page/limit for admin grids

---

## 4) Real‑Time (WebSockets)

### 4.1 Gateway

* Runtime worker with **Ratchet** (or Symfony Runtime + ReactPHP)
* Auth via JWT (`sub`, `tenant_id`, scopes)
* Channels: `tenant:{id}:orders`, `tenant:{id}:inventory`, `tenant:{id}:notifications`
* Outbound from domain events → WS gateway (fan‑out)

### 4.2 Mercure Fallback

* For SSE clients (admin dashboards), publish mirrored topics (optional)

---

## 5) Event‑Driven Architecture

### 5.1 Messenger Transports (AMQP)

```yaml
# config/packages/messenger.yaml
framework:
  messenger:
    default_bus: command.bus
    transports:
      amqp_async: '%env(MESSENGER_TRANSPORT_DSN)%' # amqp://user:pass@rabbitmq:5672/%2f/messages
    routing:
      'App\Application\Command\*': amqp_async
      'App\Domain\Event\*': amqp_async
```

### 5.2 Domain Events

* `order.created|paid|shipped|delivered|cancelled`
* `inventory.reserved|released|low_stock|out_of_stock`
* `product.created|updated|price_changed|stock_changed`
* `customer.registered|updated|segmented|churned`
* `return.requested|approved|received|inspected|completed`

### 5.3 Event Store + Projections

* Append‑only `event_store` table (JSON payload/metadata)
* Projectors build read models (orders timeline, inventory reservations, etc.)

---

## 6) Search & Discovery (Elasticsearch)

### 6.1 Indices & Analyzers

* **products**: `name`, `brand`, `category`, `description` with analyzers: `lowercase`, `synonym`, `stemmer`, stop words per language
* **refresh\_interval**: 1s; shards/replicas tuned for throughput

### 6.2 Query Service (high level)

* `multi_match` over boosted fields, tenant‑scoped index naming: `products_{tenant}`
* Facets/aggregations for brand, price ranges, attributes

---

## 7) Security & Authentication

### 7.1 AuthN

* **JWT** (HTTP‑only cookie or Authorization header) with `tenant_id` claim
* Optional **API Keys** per tenant for server‑to‑server integrations

### 7.2 AuthZ (RBAC + ABAC)

* RBAC via roles: `ADMIN`, `MANAGER`, `CUSTOMER`, `GUEST`
* **ABAC** via security **Voters** and **ExpressionLanguage** policies:

```yaml
# config/packages/security_policies.yaml
policies:
  order.view: "is_granted('ROLE_MANAGER') or (user.id == subject.customerId)"
  product.write: "is_granted('ROLE_MANAGER') and tenant(feature('catalog_write'))"
```

### 7.3 Rate Limiting & API Gateway Rules

* Symfony **RateLimiter** per API key and per IP
* Response caching for GET endpoints (gateway level)

---

## 8) Product Configurator & Bundles (completed)

### 8.1 REST Actions

* `GET /products/{id}/configuration` – option groups, compatibility matrix, pricing rules
* `POST /products/{id}/validate` – rule engine validation, error/warning codes
* `POST /products/{id}/price` – price for a given selection + generated SKU

### 8.2 Services

* `ProductConfigurationService` – resolves dependencies & conflicts
* `BundleCompositionService` – dynamic composition with availability checks
* `SKUGeneratorService` – deterministic SKU from selections; collision detection

---

## 9) Orders, Cart & Checkout

* **Cart**: in‑memory (Redis) + persistence snapshot; idempotent add/update/remove
* **Checkout**: validations (inventory, pricing drift, tax), payment intent creation, order creation saga
* **Order State Machine**: `new → paid → shipped → delivered` (+ `cancelled` branch)

---

## 10) Returns & RMA

* Workflow: `requested → approved → received → inspected → completed`
* Quality inspection artifacts stored to S3; events drive refunds/restocking

---

## 11) Pricing, Promotions & Tax

* **PricingEngine** with pipeline steps (base → modifiers → promotions → taxes)
* **PromotionEngine** supports **stacking** & exclusivity rules; coupon & customer segment targeting
* **Tax** engine: jurisdiction matrix; rounding via **Brick\Money**; auditable calculations

---

## 12) Analytics (Real‑Time + Batch)

* Stream events to Kafka topic `analytics_events` (producer adapter), partitioned by tenant
* Batch warehouse (TimescaleDB / PostgreSQL) for reports & cohorts
* Expose **/analytics** GraphQL queries for dashboards

---

## 13) Observability & SLAs

* Prometheus metrics: API latency histograms, order processing, stock gauges
* Tracing: OpenTelemetry exporter → Tempo/Jaeger
* Error budgets & alerting rules for p95/p99 SLOs

---

## 14) Performance Targets (reference)

| Metric             | Target     |
| ------------------ | ---------- |
| API Response (p95) | < 200ms    |
| Product Search     | < 200ms    |
| Checkout           | < 500ms    |
| Order Creation     | < 1s       |
| Concurrency        | 100k users |

---

## 15) Deployment

* **Kubernetes** base charts for `api`, `ws-gateway`, `worker`
* **RabbitMQ**, **Redis**, **Elasticsearch**, **PostgreSQL**, **MinIO** as managed services or cluster add‑ons
* Blue/Green or Canary rollout; DB migrations, message transport bootstrap

---

## 16) Configuration Samples

### 16.1 Environment

```env
APP_ENV=prod
DATABASE_URL=postgresql://user:pass@db:5432/app?serverVersion=16&charset=utf8
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@rabbitmq:5672/%2f/messages
ELASTICSEARCH_URL=http://elasticsearch:9200
REDIS_URL=redis://redis:6379
S3_ENDPOINT=https://s3.local
JWT_PASSPHRASE=change-me
```

### 16.2 WebSocket Worker (bootstrap)

```php
// bin/ws-worker.php
$server = IoServer::factory(new HttpServer(new WsServer(new RealtimeHub($jwtValidator,$tenantGuard,$broadcaster))), 8081);
$server->run();
```

### 16.3 RateLimiter

```yaml
framework:
  rate_limiter:
    api_ip:
      policy: sliding_window
      limit: 300
      interval: '1 minute'
    api_key:
      policy: token_bucket
      limit: 1200
      rate: { interval: '1 minute', amount: 1200 }
```

---

## 17) Testing Strategy (completed)

* **Unit** (domain services, rules)
* **Integration** (Doctrine RLS, ES queries, Messenger with AMQP)
* **Functional** (REST + GraphQL; multi‑tenant flows; product configurator; order saga)
* **Contract** (OpenAPI & GraphQL schema snapshot tests)
* **Load** (k6/Gatling scenarios: search, add‑to‑cart, checkout, order)

---

## 18) Documentation & Developer Experience

* OpenAPI 3.0 at `/api/doc` + ReDoc; GraphQL schema explorer
* C4 + sequence diagrams (PlantUML)
* Domain glossary, business rules catalog, decision tables kept in `/docs`

---

## 19) Compliance & Security

* PCI DSS (payments via providers), SOC 2, GDPR/CCPA
* TLS 1.3 everywhere; AES‑256 at rest; secrets in Vault/SealedSecrets

---

## 20) Mapping to PRD – Coverage Checklist

* ✅ Multi‑tenancy isolation (RLS + filters)
* ✅ REST + GraphQL API & envelope
* ✅ Real‑time via WebSockets (SSE fallback)
* ✅ Event‑driven (Messenger + AMQP) & event store
* ✅ Search & facets on ES with analyzers
* ✅ Promotions stacking, configurable/bundle products
* ✅ Returns & RMA, Tax, Pricing engine
* ✅ Analytics streaming + batch
* ✅ Observability + performance targets

> **Status:** Implementation spec is now fully aligned with PRD v4.0 and ready for backlog decomposition & sprint planning.
