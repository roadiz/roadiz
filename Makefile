# Use a local available port
DEV_DOMAIN="0.0.0.0:8080"
THEME_PREFIX=Default
THEME=${THEME_PREFIX}Theme

# Run PHP inside the "app" container. Override with e.g. `make PHP="docker compose run --rm app php" test`.
PHP=docker compose exec app php

.PHONY : cache dev-server migrate ngrok

cache :
	${PHP} bin/roadiz cache:clear
	${PHP} bin/roadiz cache:clear -e prod
	${PHP} bin/roadiz cache:clear -e prod --preview
	${PHP} bin/roadiz cache:clear-fpm -e prod
	${PHP} bin/roadiz cache:clear-fpm -e prod --preview

# Launch PHP internal server (for dev purpose only)
dev-server:
	@echo "✅\t${GREEN}Launching PHP dev server${NC}" >&2;
	${PHP} -S ${DEV_DOMAIN} -t ./ conf/router.php

# Migrate your configured theme, update DB and empty caches.
migrate:
	@echo "✅\t${GREEN}Update schema node-types${NC}" >&2;
	${PHP} bin/roadiz themes:migrate /Themes/${THEME}/${THEME}App;
	make cache;

ngrok:
	ngrok http ${DEV_DOMAIN}

test:
	${PHP} -d "memory_limit=-1" bin/phpcs --report=full --report-file=./report.txt -p ./
	make phpstan
	${PHP} -d "memory_limit=-1" bin/roadiz lint:twig
	${PHP} -d "memory_limit=-1" bin/roadiz lint:twig themes/Install/Resources/views
	${PHP} -d "memory_limit=-1" bin/roadiz lint:twig vendor/roadiz/rozier/src/Resources/views

phpstan:
	${PHP} -d "memory_limit=-1" bin/phpstan analyse -c phpstan.neon -l 4 src
	${PHP} -d "memory_limit=-1" bin/phpstan analyse -c phpstan.neon -l 3 themes/Install themes/DefaultTheme

test-rozier:
	${PHP} -d "memory_limit=-1" bin/roadiz lint:twig vendor/roadiz/rozier/src/Resources/views

unit:
	${PHP} -d "memory_limit=-1" bin/phpunit -v --bootstrap=tests/bootstrap.php --whitelist ./src tests/
