# Use a local available port
DEV_DOMAIN="0.0.0.0:8080"
THEME_PREFIX=Default
THEME=${THEME_PREFIX}Theme

.PHONY : cache dev-server migrate ngrok

cache :
	bin/roadiz cache:clear
	bin/roadiz cache:clear -e prod
	bin/roadiz cache:clear -e prod --preview
	bin/roadiz cache:clear-fpm -e prod
	bin/roadiz cache:clear-fpm -e prod --preview

# Launch PHP internal server (for dev purpose only)
dev-server:
	@echo "✅\t${GREEN}Launching PHP dev server${NC}" >&2;
	php -S ${DEV_DOMAIN} -t ./ conf/router.php

# Migrate your configured theme, update DB and empty caches.
migrate:
	@echo "✅\t${GREEN}Update schema node-types${NC}" >&2;
	bin/roadiz themes:migrate /Themes/${THEME}/${THEME}App;
	make cache;

ngrok:
	ngrok http ${DEV_DOMAIN}

test:
	bin/phpcs --report=full --report-file=./report.txt -p ./
	bin/phpstan analyse -c phpstan.neon -l 1 src themes/Rozier themes/Install

unit:
	bin/phpunit -v --bootstrap=tests/bootstrap.php --whitelist ./src --coverage-clover ./build/logs/clover.xml tests/
