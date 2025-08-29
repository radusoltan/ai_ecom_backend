.PHONY: init qa test fix cs stan serve reset-db

init:
	php bin/console doctrine:database:create --if-not-exists
	php bin/console doctrine:migrations:migrate --no-interaction

qa: cs stan test

test:
	vendor/bin/phpunit

fix:
	vendor/bin/php-cs-fixer fix --allow-risky=yes

cs:
	vendor/bin/php-cs-fixer fix --allow-risky=yes --dry-run --diff

stan:
	vendor/bin/phpstan analyse -c phpstan.neon.dist

serve:
	symfony server:start -d

reset-db:
	php bin/console doctrine:database:drop --if-exists --force
	php bin/console doctrine:database:create --if-not-exists
	php bin/console doctrine:migrations:migrate --no-interaction
