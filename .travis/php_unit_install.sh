#!/bin/sh -x
if [ "$DB" = 'pgsql' ];
then
    psql -c 'DROP DATABASE IF EXISTS roadiz_tests;' -U postgres || exit 1;
    psql -c 'DROP DATABASE IF EXISTS roadiz_tests_tmp;' -U postgres || exit 1;
    psql -c 'create database roadiz_tests;' -U postgres || exit 1;
    psql -c 'create database roadiz_tests_tmp;' -U postgres || exit 1;
    cp conf/config.pgsql.travis.yml conf/config.yml || exit 1;
fi;

if [ "$DB" = 'mysql' ] || [ "$DB" = 'mariadb' ];
then
    sudo mysql -e 'create database IF NOT EXISTS roadiz_tests_tmp; create database IF NOT EXISTS roadiz_tests;' || exit 1;
    cp conf/config.mysql.travis.yml conf/config.yml || exit 1;
fi;

phpenv config-rm xdebug.ini;
curl -s http://getcomposer.org/installer | php || exit 1;
php composer.phar install --dev --no-interaction || exit 1;
