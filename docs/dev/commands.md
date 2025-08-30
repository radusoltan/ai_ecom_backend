# Developer Commands

Generated from `make help`:

```
Usage: make [target]

app:cache-clear                Clear the application cache
app:env                        Copy .env to .env.local if missing and print APP_ENV
app:warmup                     Warm up the application cache
bootstrap                      Install PHP deps and verify requirements
ci                             Run code style, static analysis, and tests
db:create                      Create the database if it does not exist
db:diff                        Generate a migration diff
db:migrate                     Run database migrations
db:reset                       Drop, create, and migrate the database (requires CONFIRM=1)
logs:tail                      Tail the application log for current APP_ENV
obs:check                      Check observability configuration and API availability
quality:audit                  Run Composer audit for security vulnerabilities
quality:cs                     Check coding standards
quality:cs-fix                 Fix coding standards issues
quality:stan                   Run PHPStan static analysis
test                           Run PHPUnit tests
test:coverage                  Run tests with coverage reports
```

## CI example

```yaml
# Example CI pipeline
steps:
  - run: make bootstrap
  - run: make ci
  # or using Composer directly
  - run: composer lint:stan
  - run: composer test
```
