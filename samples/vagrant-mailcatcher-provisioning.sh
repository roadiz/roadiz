#!/bin/bash
#
export DEBIAN_FRONTEND=noninteractive

echo -e "\n--- Install MailCatcher dependencies ---\n"
sudo apt-get -qq -f -y install build-essential software-properties-common > /dev/null 2>&1;
sudo apt-get -qq -f -y install libsqlite3-dev ruby1.9.1-dev > /dev/null 2>&1;

echo -e "\n--- Install MailCatcher (may take a while, be patient) ---\n"
sudo gem install mailcatcher > /dev/null 2>&1;

echo -e "\n--- Setup MailCatcher service at reboot ---\n"
sudo sh -c "echo '@reboot root $(which mailcatcher) --ip=0.0.0.0' >> /etc/crontab"
sudo update-rc.d cron defaults > /dev/null 2>&1;

echo -e "\n--- Setup MailCatcher catchmail service as PHP sendmail_path ---\n"
sudo sh -c "echo 'sendmail_path = /usr/bin/env $(which catchmail)' >> /etc/php5/mods-available/mailcatcher.ini"
sudo php5enmod mailcatcher

echo -e "\n--- Restart PHP service ---\n"
sudo service php5-fpm restart > /dev/null 2>&1;

echo -e "\n--- Run MailCatcher  ---\n"
/usr/bin/env $(which mailcatcher) --ip=0.0.0.0 > /dev/null 2>&1;
