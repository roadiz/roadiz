#!/bin/sh -x
phpenv config-rm xdebug.ini;
curl -s http://getcomposer.org/installer | php;
php composer.phar install --dev --no-interaction;
