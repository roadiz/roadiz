#!/bin/sh -x
if [ "$DB" = 'mysql' ] || [ "$DB" = 'mariadb' ];
    then php bin/roadiz migration:migrate --allow-no-migration -n || exit 1;
fi;
if [ "$DB" = 'pgsql' ];
    then php bin/roadiz bin/roadiz orm:schema-tool:create -n || exit 1;
fi;
php bin/roadiz install -n --env=install || exit 1;
php bin/roadiz themes:install -n "/Themes/DefaultTheme/DefaultThemeApp" || exit 1;
php bin/roadiz themes:install -n --data "/Themes/DefaultTheme/DefaultThemeApp" || exit 1;
php bin/roadiz cache:clear --env=install || exit 1;
php bin/roadiz orm:schema-tool:update -n --force || exit 1;
php bin/roadiz themes:install -n --nodes "/Themes/DefaultTheme/DefaultThemeApp" || exit 1;
php bin/roadiz cache:clear --env=install || exit 1;
php bin/roadiz cache:clear --env=test || exit 1;
php bin/roadiz users:create -n --email=test@test.com --password=secret --super-admin test || exit 1;
php bin/roadiz users:create -n --email=test2@test.com --password=secret2 --back-end test2 || exit 1;
# Need to drop full database with migration table included
php bin/roadiz orm:schema-tool:drop --force --full-database || exit 1;

php bin/phpunit -v --bootstrap=tests/bootstrap.php --whitelist ./src --coverage-clover ./build/logs/clover.xml tests/ || exit 1;
php bin/phpstan analyse -c phpstan.neon -l 4 src || exit 1;
php bin/phpstan analyse -c phpstan.neon -l 3 themes/Rozier themes/Install || exit 1;
