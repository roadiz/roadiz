# Use a local available port
DEV_DOMAIN="0.0.0.0:8080"
THEME_PREFIX=Default
THEME=${THEME_PREFIX}Theme

.PHONY : cache dev-server migrate ngrok

cache :
	php bin/roadiz cache:clear
	php bin/roadiz cache:clear -e prod
	php bin/roadiz cache:clear -e prod --preview
	php bin/roadiz cache:clear-fpm -e prod
	php bin/roadiz cache:clear-fpm -e prod --preview

# Launch PHP internal server (for dev purpose only)
dev-server:
	@echo "✅\t${GREEN}Launching PHP dev server${NC}" >&2;
	php -S ${DEV_DOMAIN} -t ./ conf/router.php

# Migrate your configured theme, update DB and empty caches.
migrate:
	@echo "✅\t${GREEN}Update schema node-types${NC}" >&2;
	php bin/roadiz themes:migrate /Themes/${THEME}/${THEME}App;
	make cache;

ngrok:
	ngrok http ${DEV_DOMAIN}

test:
	php -d "memory_limit=-1" bin/phpcs --report=full --report-file=./report.txt -p ./
	php -d "memory_limit=-1" bin/phpstan analyse -c phpstan.neon -l 4 src
	php -d "memory_limit=-1" bin/phpstan analyse -c phpstan.neon -l 5 themes/Rozier
	php -d "memory_limit=-1" bin/phpstan analyse -c phpstan.neon -l 3 themes/Install themes/DefaultTheme
	php -d "memory_limit=-1" bin/roadiz lint:twig
	php -d "memory_limit=-1" bin/roadiz lint:twig src/Roadiz/Webhook/Resources/views
	php -d "memory_limit=-1" bin/roadiz lint:twig themes/Install/Resources/views
	php -d "memory_limit=-1" bin/roadiz lint:twig themes/Rozier/Resources/views

test-rozier:
	php -d "memory_limit=-1" bin/phpstan analyse -c phpstan.neon -l 5 themes/Rozier
	php -d "memory_limit=-1" bin/roadiz lint:twig themes/Rozier/Resources/views

unit:
	php -d "memory_limit=-1" bin/phpunit -v --bootstrap=tests/bootstrap.php --whitelist ./src tests/
