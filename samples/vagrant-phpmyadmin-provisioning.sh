#!/bin/bash
#
RED='\033[0;31m'
NC='\033[0m' # No Color
export DEBIAN_FRONTEND=noninteractive

DBPASSWD="roadiz"

TEMP_DIR="/home/vagrant"
PHPMYADMIN_DIR="/usr/share/phpmyadmin"
PHPMYADMIN_VERSION="4.6.5.1"
PHPMYADMIN_ARCHIVE="phpMyAdmin-${PHPMYADMIN_VERSION}-all-languages"
PHPMYADMIN_ARCHIVE_URL="https://files.phpmyadmin.net/phpMyAdmin/${PHPMYADMIN_VERSION}/${PHPMYADMIN_ARCHIVE}.tar.gz"

echo -e "\n--- Downloading phpmyadmin...\n"
sudo wget -O ${TEMP_DIR}/${PHPMYADMIN_ARCHIVE}.tar.gz ${PHPMYADMIN_ARCHIVE_URL} > /dev/null 2>&1;
if [ $? -eq 0 ]; then
   echo -e "\t--- OK\n"
else
   echo -e "${RED}\t!!! FAIL - Downloading ${PHPMYADMIN_ARCHIVE_URL} ${NC}\n"
   echo -e "${RED}\t!!! Please verify PhpMyAdmin version exists and provision again.${NC}\n"
   exit 1;
fi

echo -e "\n--- Uncompressing phpmyadmin...\n"
sudo tar -xzvf ${TEMP_DIR}/${PHPMYADMIN_ARCHIVE}.tar.gz > /dev/null 2>&1;
sudo rm -rf ${TEMP_DIR}/${PHPMYADMIN_ARCHIVE}.tar.gz;

echo -e "\n--- Installing phpmyadmin...\n"
sudo mv ${TEMP_DIR}/${PHPMYADMIN_ARCHIVE} ${PHPMYADMIN_DIR} > /dev/null 2>&1;

echo -e "\n--- Configure phpmyadmin to connect automatically for roadiz DB\n"
sudo cp -a /var/www/samples/vagrant/phpmyadmin/config.inc.php ${PHPMYADMIN_DIR}/config.inc.php;


export PRIVATE_IP=`/sbin/ifconfig eth1 | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'`

echo -e "\n-----------------------------------------------------------------"
echo -e "\n------------------- Your phpmyadmin is ready --------------------"
echo -e "\n-----------------------------------------------------------------"
echo -e "\n* Type http://$PRIVATE_IP/phpmyadmin for your MySQL db admin."
echo -e "\n-----------------------------------------------------------------"
