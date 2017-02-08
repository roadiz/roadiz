#!/bin/bash
#
export DEBIAN_FRONTEND=noninteractive

echo -e "\n--- Add some repos to update our distro ---\n"
curl -sL https://deb.nodesource.com/setup_6.x | sudo -E bash -

echo -e "\n--- Installing NodeJS and NPM ---\n"
sudo apt-get -y install nodejs > /dev/null 2>&1

echo -e "\n--- Installing Composer for PHP package management ---\n"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('SHA384', 'composer-setup.php') === '55d6ead61b29c7bdee5cccfb50076874187bd9f21f65d8991d46ec5cc90518f447387fb9f76ebae1fbbacf329e583e30') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"

sudo mv composer.phar /usr/local/bin/composer

echo -e "\n--- Installing Yarn as better alternative for NPM ---\n"
curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
sudo apt-get -y update > /dev/null 2>&1
sudo apt-get -y install yarn > /dev/null 2>&1


##### CLEAN UP #####
sudo dpkg --configure -a  > /dev/null 2>&1; # when upgrade or install doesnt run well (e.g. loss of connection) this may resolve quite a few issues
sudo apt-get autoremove -y  > /dev/null 2>&1; # remove obsolete packages
