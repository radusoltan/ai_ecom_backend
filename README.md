# ECOM API Backend

## Foundation Setup

### Prerequisites

- PHP 8.2+
- Composer
- PostgreSQL (default DSN in `.env`)
- Symfony CLI (for `make serve`)

### Installation

```bash
composer install
make init
```

### Useful Commands

| Command | Description |
| --- | --- |
| `make serve` | Start the Symfony web server |
| `make init` | Create database and run migrations |
| `make qa` | Run CS fixer, PHPStan and PHPUnit |
| `make test` | Run the test suite |
| `make cs` | Run PHP-CS-Fixer in dry-run mode |
| `make fix` | Apply PHP-CS-Fixer changes |
| `make stan` | Run PHPStan static analysis |
| `make reset-db` | Drop, create and migrate the database |

### Verify

Once the server is running, visit `http://localhost:8000/api` to see the API Platform landing page and the `Tenant` resource.
