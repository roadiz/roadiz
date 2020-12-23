#!/bin/sh -x
if [ "$DB" = 'pgsql' ];
then
    psql -c 'DROP DATABASE IF EXISTS roadiz_tests;' -U postgres;
    psql -c 'DROP DATABASE IF EXISTS roadiz_tests_tmp;' -U postgres;
    psql -c 'create database roadiz_tests;' -U postgres;
    psql -c 'create database roadiz_tests_tmp;' -U postgres;
    cp conf/config.pgsql.travis.yml conf/config.yml;
fi;

if [ "$DB" = 'mysql' ] || [ "$DB" = 'mariadb' ];
then
    mysql -e 'create database IF NOT EXISTS roadiz_tests_tmp; create database IF NOT EXISTS roadiz_tests;';
    cp conf/config.mysql.travis.yml conf/config.yml;
fi;

phpenv config-rm xdebug.ini;
curl -s http://getcomposer.org/installer | php;
php composer.phar install --dev --no-interaction;
