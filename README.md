# ECOM API Backend

## Quickstart

```bash
make bootstrap && make ci
```

See [docs/dev/commands.md](docs/dev/commands.md) for more commands.


## Foundation Setup

### Prerequisites

- PHP 8.2+
- Composer
- PostgreSQL (default DSN in `.env`)
- Symfony CLI (for `make serve`)

### Installation

```bash
make bootstrap
```

### Useful Commands

| Command | Description |
| --- | --- |
| `make help` | List available targets |
| `make db:migrate` | Run database migrations |
| `make quality:cs` | Run code style checks |
| `make test` | Run the test suite |

### Verify

Once the server is running, visit `http://localhost:8000/api` to see the API Platform landing page and the `Tenant` resource.

## Rate Limiting & CORS

The API enforces rate limiting and strict CORS:

- **Per IP**: `API_IP_LIMIT` requests per `API_IP_INTERVAL`.
- **Per API key**: `API_KEY_LIMIT` requests per `API_KEY_RATE_INTERVAL` with bucket size `API_KEY_RATE_AMOUNT`.
- **Allowed origins**: set `FRONTEND_ORIGINS` to a comma separated list of approved domains.

Example `429 Too Many Requests` response:

```json
{
  "status": "error",
  "data": null,
  "meta": { "timestamp": "2024-01-01T00:00:00+00:00", "request_id": "uuid", "tenant_id": null },
  "errors": [ { "code": 429, "message": "Too Many Requests" } ]
}
```

Use Symfony's `#[RateLimiter('api_ip')]` or `#[RateLimiter('api_key')]` attribute on a controller to override limits per route.

## Messaging (AMQP)

This project uses Symfony Messenger with RabbitMQ for asynchronous processing.

### Start RabbitMQ

```
make mq-up
```

Set the transport DSN in `.env.local` if necessary. The default is:

```
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@rabbitmq:5672/%2f/messages
```

### Initialize & Diagnostics

```
php bin/console messenger:setup-transports
php bin/console app:messenger:diagnostics
```

### Run a Worker

```
make worker
```

### Send the sample command

```
php bin/console messenger:dispatch 'App\Application\Command\PingCommand' '{"messageId":"1","occurredAt":"2024-01-01T00:00:00+00:00","payload":{"ping":"pong"}}'
```

Use `make mq-down` to stop the broker and `make mq-reset` to recreate queues.
