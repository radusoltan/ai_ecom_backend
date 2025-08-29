.PHONY: init qa test fix cs stan serve reset-db

init:
	bin/console doctrine:database:create --if-not-exists
	bin/console doctrine:migrations:migrate --no-interaction

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
	bin/console doctrine:database:drop --if-exists --force
	bin/console doctrine:database:create --if-not-exists
	bin/console doctrine:migrations:migrate --no-interaction
