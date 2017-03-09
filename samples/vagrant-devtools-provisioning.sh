#!/bin/bash
#
export DEBIAN_FRONTEND=noninteractive

echo -e "\n--- Add some repos to update our distro ---\n"
curl -sL https://deb.nodesource.com/setup_6.x | sudo -E bash -

echo -e "\n--- Installing NodeJS and NPM ---\n"
sudo apt-get -y install nodejs > /dev/null 2>&1

echo -e "\n--- Installing Composer for PHP package management ---\n"
EXPECTED_SIGNATURE=$(wget -q -O - https://composer.github.io/installer.sig)
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_SIGNATURE=$(php -r "echo hash_file('SHA384', 'composer-setup.php');")
if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
then
    >&2 echo 'ERROR: Invalid installer signature'
    rm composer-setup.php
    exit 1
fi
php composer-setup.php --quiet
RESULT=$?
rm composer-setup.php

sudo mv composer.phar /usr/local/bin/composer

echo -e "\n--- Installing Yarn as better alternative for NPM ---\n"
curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
sudo apt-get -y update > /dev/null 2>&1
sudo apt-get -y install yarn > /dev/null 2>&1


##### CLEAN UP #####
sudo dpkg --configure -a  > /dev/null 2>&1; # when upgrade or install doesnt run well (e.g. loss of connection) this may resolve quite a few issues
sudo apt-get autoremove -y  > /dev/null 2>&1; # remove obsolete packages
