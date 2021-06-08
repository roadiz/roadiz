#!/bin/sh -x
if [ "$DB" = 'pgsql' ];
then
    psql -c 'DROP DATABASE IF EXISTS roadiz_tests;' -U postgres || exit 1;
    psql -c 'DROP DATABASE IF EXISTS roadiz_tests_tmp;' -U postgres || exit 1;
    psql -c 'create database roadiz_tests;' -U postgres || exit 1;
    psql -c 'create database roadiz_tests_tmp;' -U postgres || exit 1;
    cp conf/config.pgsql.travis.yml conf/config.yml || exit 1;
fi;

if [ "$DB" = 'mysql' ];
then
    mysql -e 'create database IF NOT EXISTS roadiz_tests_tmp; create database IF NOT EXISTS roadiz_tests;' || exit 1;
    cp conf/config.mysql.travis.yml conf/config.yml || exit 1;
fi;

if [ "$DB" = 'mariadb' ];
then
    sudo mysql -e 'create database IF NOT EXISTS roadiz_tests_tmp; create database IF NOT EXISTS roadiz_tests;' || exit 1;
    sudo mysql -e "SET Password=PASSWORD('${MYSQL_PASSWORD}');" || exit 1;
    sudo service mysql restart
    cp conf/config.mariadb.travis.yml conf/config.yml || exit 1;
fi;

phpenv config-rm xdebug.ini;
curl -s http://getcomposer.org/installer | php || exit 1;
php composer.phar install --dev --no-interaction || exit 1;
