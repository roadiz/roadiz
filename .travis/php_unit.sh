#!/bin/sh -x
if [ "$DB" = 'mysql' ] || [ "$DB" = 'mariadb' ];
    then php bin/roadiz migration:migrate --allow-no-migration -n;
fi;
if [ "$DB" = 'pgsql' ];
    then php bin/roadiz bin/roadiz orm:schema-tool:create -n;
fi;
php bin/roadiz install -n --env=install;
php bin/roadiz themes:install -n "/Themes/DefaultTheme/DefaultThemeApp";
php bin/roadiz themes:install -n --data "/Themes/DefaultTheme/DefaultThemeApp";
php bin/roadiz cache:clear --env=install;
php bin/roadiz orm:schema-tool:update -n --force;
php bin/roadiz themes:install -n --nodes "/Themes/DefaultTheme/DefaultThemeApp";
php bin/roadiz cache:clear --env=install;
php bin/roadiz cache:clear --env=test;
php bin/roadiz users:create -n --email=test@test.com --password=secret --super-admin test;
php bin/roadiz users:create -n --email=test2@test.com --password=secret2 --back-end test2;
# Need to drop full database with migration table included
php bin/roadiz orm:schema-tool:drop --force --full-database;

php bin/phpunit -v --bootstrap=tests/bootstrap.php --whitelist ./src --coverage-clover ./build/logs/clover.xml tests/;
php bin/phpstan analyse -c phpstan.neon -l 4 src;
php bin/phpstan analyse -c phpstan.neon -l 3 themes/Rozier themes/Install;
