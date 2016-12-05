#!/bin/bash

RED='\033[0;31m'
NC='\033[0m' # No Color

export DEBIAN_FRONTEND=noninteractive;

echo -e "\n--- Install MailCatcher dependencies ---\n";
sudo apt-get -qq -f -y install build-essential software-properties-common > /dev/null 2>&1;
if [ $? -eq 0 ]; then
   echo -e "\t--- OK\n"
else
   echo -e "${RED}\t!!! FAIL${NC}\n"
   echo -e "${RED}\t!!! Please destroy your vagrant and provision again.${NC}\n"
   exit 1;
fi
sudo apt-get -qq -f -y install libsqlite3-dev ruby1.9.1-dev > /dev/null 2>&1;
if [ $? -eq 0 ]; then
   echo -e "\t--- OK\n"
else
   echo -e "${RED}\t!!! FAIL${NC}\n"
   echo -e "${RED}\t!!! Please destroy your vagrant and provision again.${NC}\n"
   exit 1;
fi

echo -e "\n--- Install MailCatcher (may take a while, be patient) ---\n";
sudo gem install mime-types --version "< 3" > /dev/null 2>&1;
sudo gem install --conservative mailcatcher > /dev/null 2>&1;

echo -e "\n--- Setup MailCatcher service at reboot ---\n";
sudo sh -c "echo '@reboot root $(which mailcatcher) --ip=0.0.0.0' >> /etc/crontab";
sudo update-rc.d cron defaults > /dev/null 2>&1;

echo -e "\n--- Setup MailCatcher catchmail service as PHP sendmail_path ---\n";
sudo sh -c "echo 'sendmail_path = /usr/bin/env $(which catchmail)' >> /etc/php/7.0/mods-available/mailcatcher.ini";
sudo phpenmod -v ALL -s ALL mailcatcher;

echo -e "\n--- Restart PHP service ---\n";
sudo service php7.0-fpm restart > /dev/null 2>&1;

echo -e "\n--- Run MailCatcher  ---\n";
/usr/bin/env $(which mailcatcher) --ip=0.0.0.0 > /dev/null 2>&1;
