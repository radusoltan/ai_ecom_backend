# Domain Events & Messaging

## Raising a Domain Event

Domain events extend `AbstractDomainEvent` and are dispatched through the `event.bus`:

```php
$event = new OrderCreatedEvent($orderId, $tenantId, $itemsCount, $currency, $totalMinor);
$this->eventBus->dispatch($event);
```

`CorrelationMiddleware` ensures that every dispatched message carries a correlation and causation ID. These identifiers are propagated when new messages are dispatched from within handlers, enabling traceability across asynchronous flows.

`PersistEventMiddleware` stores events in the `event_store` table inside the same database transaction and enriches metadata with the tenant, correlation and causation identifiers.

## Adding a Projector

1. Create a class implementing `ProjectorInterface`.
2. Tag it in `services.yaml` using `app.projector` and a unique `key`.
3. Implement `supports()` to declare handled event names and `project()` to update the read model.
4. Rebuild projections with:

```bash
bin/console events:replay --projector=your_key --from=2025-01-01T00:00:00Z
```

## Tenant & RLS

PostgreSQL row level security relies on the session variable `app.tenant_id`. The `SetTenantRlsSubscriber` sets this for each request and worker using `TenantContextInterface`. Ensure the tenant context is resolved before interacting with the database.

