sh -c "if [ '$DB' = 'pgsql' ]; then psql -c 'DROP DATABASE IF EXISTS roadiz_tests;' -U postgres; fi";
sh -c "if [ '$DB' = 'pgsql' ]; then psql -c 'DROP DATABASE IF EXISTS roadiz_tests_tmp;' -U postgres; fi";
sh -c "if [ '$DB' = 'pgsql' ]; then psql -c 'create database roadiz_tests;' -U postgres; fi";
sh -c "if [ '$DB' = 'pgsql' ]; then psql -c 'create database roadiz_tests_tmp;' -U postgres; fi";
sh -c "if [ '$DB' = 'pgsql' ]; then cp conf/config.pgsql.travis.yml conf/config.yml; fi";
sh -c "if [ '$DB' = 'mysql' ] || [ '$DB' = 'mariadb' ]; then mysql -e 'create database IF NOT EXISTS roadiz_tests_tmp;create database IF NOT EXISTS roadiz_tests;'; fi";
sh -c "if [ '$DB' = 'mysql' ] || [ '$DB' = 'mariadb' ]; then cp conf/config.mysql.travis.yml conf/config.yml; fi";
curl -s http://getcomposer.org/installer | php;
php composer.phar install --dev --no-interaction;
