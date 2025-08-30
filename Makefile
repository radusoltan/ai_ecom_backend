SHELL := /bin/bash
APP_ENV ?= dev
REQUIRED_PHP_EXTENSIONS := pdo_pgsql intl

.PHONY: help bootstrap app\:env app\:cache-clear app\:warmup db\:create db\:migrate db\:diff db\:reset quality\:cs quality\:cs-fix quality\:stan quality\:audit test test\:coverage logs\:tail obs\:check ci

help: ## Show this help
	@echo "Usage: make [target]"
	@echo
	@tr -d '\\' < $(MAKEFILE_LIST) | grep -E '^[a-zA-Z0-9_.:-]+:.*##' | grep -v '^help:' | sed -n -E 's/^([a-zA-Z0-9_.:-]+):.*## (.*)/\1\t\2/p' | sort | awk -F '\t' '{printf "%-30s %s\n", $$1, $$2}'
bootstrap: ## Install PHP deps and verify requirements
	@command -v php >/dev/null || { echo "PHP is required but not installed." >&2; exit 1; }
	@command -v composer >/dev/null || { echo "Composer is required but not installed." >&2; exit 1; }
	@php -r 'foreach(explode(" ", getenv("REQUIRED_PHP_EXTENSIONS")) as $e) if(!extension_loaded($e)){fwrite(STDERR, "Missing PHP extension: $e\n"); exit(1);}';
	composer install --no-interaction --prefer-dist

app\:env: ## Copy .env to .env.local if missing and print APP_ENV
	@[ -f .env.local ] || cp .env .env.local
	@echo "APP_ENV=$${APP_ENV}"

app\:cache-clear: ## Clear the application cache
	APP_ENV=$(APP_ENV) php bin/console cache:clear

app\:warmup: ## Warm up the application cache
	APP_ENV=$(APP_ENV) php bin/console cache:warmup

db\:create: ## Create the database if it does not exist
	APP_ENV=$(APP_ENV) php bin/console doctrine:database:create --if-not-exists

db\:migrate: ## Run database migrations
	APP_ENV=$(APP_ENV) php bin/console doctrine:migrations:migrate --no-interaction

db\:diff: ## Generate a migration diff
	APP_ENV=$(APP_ENV) php bin/console doctrine:migrations:diff --no-interaction

db\:reset: ## Drop, create, and migrate the database (requires CONFIRM=1)
	@if [ "$(CONFIRM)" != "1" ]; then echo "Set CONFIRM=1 to reset the database" >&2; exit 1; fi
	APP_ENV=$(APP_ENV) php bin/console doctrine:database:drop --if-exists --force
	APP_ENV=$(APP_ENV) php bin/console doctrine:database:create --if-not-exists
	APP_ENV=$(APP_ENV) php bin/console doctrine:migrations:migrate --no-interaction

quality\:cs: ## Check coding standards
	php-cs-fixer fix --dry-run --diff --ansi

quality\:cs-fix: ## Fix coding standards issues
	php-cs-fixer fix --allow-risky=yes --ansi

quality\:stan: ## Run PHPStan static analysis
	phpstan analyse --memory-limit=1G

quality\:audit: ## Run Composer audit for security vulnerabilities
	composer audit --no-interaction --ansi

test: ## Run PHPUnit tests
	phpunit --testdox

test\:coverage: ## Run tests with coverage reports
	phpunit --coverage-html var/coverage-html --coverage-xml var/coverage-xml

logs\:tail: ## Tail the application log for current APP_ENV
	@logfile="var/log/$(APP_ENV).log"; \
	if [ ! -f "$${logfile}" ]; then echo "Log file $${logfile} not found" >&2; exit 1; fi; \
	tail -f "$${logfile}"

obs\:check: ## Check observability configuration and API availability
	@echo "APP_ENV=$(APP_ENV)"
	@if [ -n "${OTEL_EXPORTER_OTLP_ENDPOINT}" ]; then echo "OTEL_EXPORTER_OTLP_ENDPOINT=${OTEL_EXPORTER_OTLP_ENDPOINT}"; else echo "OTEL_EXPORTER_OTLP_ENDPOINT not set"; fi
	@status=$$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/api || true); \
	if [ "$$status" = "200" ]; then echo "/api reachable (200)"; else echo "/api not reachable (status $$status)"; fi
	@echo "Prometheus: http://localhost:9090 (if running)"
	@echo "Grafana: http://localhost:3000 (if running)"
	@echo "Jaeger: http://localhost:16686 (if running)"

ci: ## Run code style, static analysis, and tests
	$(MAKE) bootstrap
	$(MAKE) quality:cs
	$(MAKE) quality:stan
	$(MAKE) test
