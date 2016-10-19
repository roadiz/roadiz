#!/bin/bash
#
export DEBIAN_FRONTEND=noninteractive

DBPASSWD="roadiz"

sudo debconf-set-selections <<< "phpmyadmin phpmyadmin/dbconfig-install boolean true"
sudo debconf-set-selections <<< "phpmyadmin phpmyadmin/app-password-confirm password $DBPASSWD"
sudo debconf-set-selections <<< "phpmyadmin phpmyadmin/mysql/admin-pass password $DBPASSWD"
sudo debconf-set-selections <<< "phpmyadmin phpmyadmin/mysql/app-pass password $DBPASSWD"
sudo debconf-set-selections <<< "phpmyadmin phpmyadmin/reconfigure-webserver multiselect none"

TEMP_DIR="/home/vagrant"
PHPMYADMIN_DIR="/usr/share/phpmyadmin"
PHPMYADMIN_ARCHIVE="phpMyAdmin-4.5.3.1-all-languages"
PHPMYADMIN_ARCHIVE_URL="https://files.phpmyadmin.net/phpMyAdmin/4.5.3.1/${PHPMYADMIN_ARCHIVE}.tar.gz"

echo -e "\n--- Downloading phpmyadmin... ---\n"
sudo wget -O ${TEMP_DIR}/${PHPMYADMIN_ARCHIVE}.tar.gz ${PHPMYADMIN_ARCHIVE_URL} > /dev/null 2>&1;

echo -e "\n--- Uncompressing phpmyadmin... ---\n"
sudo tar -xzvf ${TEMP_DIR}/${PHPMYADMIN_ARCHIVE}.tar.gz > /dev/null 2>&1;

echo -e "\n--- Installing phpmyadmin... ---\n"
sudo mv ${TEMP_DIR}/${PHPMYADMIN_ARCHIVE} ${PHPMYADMIN_DIR} > /dev/null 2>&1;

echo -e "\n--- Configure phpmyadmin to connect automatically for roadiz DB ---\n"
sudo touch ${PHPMYADMIN_DIR}/config.inc.php > /dev/null 2>&1;
sudo cat >> ${PHPMYADMIN_DIR}/config.inc.php <<'EOF'
<?php
/**
 * phpMyAdmin sample configuration, you can use it as base for
 * manual configuration. For easier setup you can use setup/
 *
 * All directives are explained in documentation in the doc/ folder
 * or at <http://docs.phpmyadmin.net/>.
 *
 * @package PhpMyAdmin
 */
$cfg['blowfish_secret'] = 'Ultricies Quam Justo Magna Nibh';

/**
 * Servers configuration
 */
$i = 0;
$i++;
/* Authentication type */
$cfg['Servers'][$i]['auth_type'] = 'config';
$cfg['Servers'][$i]['user'] = 'roadiz';
$cfg['Servers'][$i]['password'] = 'roadiz';
/* Server parameters */
$cfg['Servers'][$i]['host'] = 'localhost';
$cfg['Servers'][$i]['connect_type'] = 'tcp';
$cfg['Servers'][$i]['compress'] = false;
$cfg['Servers'][$i]['AllowNoPassword'] = true;
EOF

export PRIVATE_IP=`/sbin/ifconfig eth1 | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'`

echo -e "\n-----------------------------------------------------------------"
echo -e "\n------------------- Your phpmyadmin is ready --------------------"
echo -e "\n-----------------------------------------------------------------"
echo -e "\n* Type http://$PRIVATE_IP/phpmyadmin for your MySQL db admin."
echo -e "\n-----------------------------------------------------------------"
