#!/bin/bash
#
export DEBIAN_FRONTEND=noninteractive

DBHOST="localhost"
DBNAME="roadiz"
DBUSER="roadiz"
DBPASSWD="roadiz"

echo -e "\n--- Okay, installing now... ---\n"
sudo apt-get -qq update;

echo -e "\n--- Install base packages ---\n"
sudo locale-gen fr_FR.utf8;

echo -e "\n--- Add some repos to update our distro ---\n"
sudo add-apt-repository ppa:ondrej/php-7.0 > /dev/null 2>&1;

# Use latest nginx for HTTP/2
sudo touch /etc/apt/sources.list.d/nginx.list;
sudo cat >> /etc/apt/sources.list.d/nginx.list <<'EOF'
deb http://nginx.org/packages/mainline/ubuntu/ trusty nginx
deb-src http://nginx.org/packages/mainline/ubuntu/ trusty nginx
EOF
wget -q -O- http://nginx.org/keys/nginx_signing.key | sudo apt-key add - > /dev/null 2>&1;

echo -e "\n--- Updating packages list ---\n"
sudo apt-get -qq update;

echo -e "\n--- Install MySQL specific packages and settings ---\n"
sudo debconf-set-selections <<< "mariadb-server-10.0 mysql-server/root_password password $DBPASSWD"
sudo debconf-set-selections <<< "mariadb-server-10.0 mysql-server/root_password_again password $DBPASSWD"
sudo debconf-set-selections <<< "phpmyadmin phpmyadmin/dbconfig-install boolean true"
sudo debconf-set-selections <<< "phpmyadmin phpmyadmin/app-password-confirm password $DBPASSWD"
sudo debconf-set-selections <<< "phpmyadmin phpmyadmin/mysql/admin-pass password $DBPASSWD"
sudo debconf-set-selections <<< "phpmyadmin phpmyadmin/mysql/app-pass password $DBPASSWD"
sudo debconf-set-selections <<< "phpmyadmin phpmyadmin/reconfigure-webserver multiselect none"

echo -e "\n--- Install base servers and packages ---\n"
sudo apt-get -qq -f -y install git nginx mariadb-server mariadb-client php7.0-fpm curl > /dev/null 2>&1;

echo -e "\n--- Install all php7.0 extensions ---\n"
sudo apt-get -qq -f -y install php7.0-opcache php7.0-cli php7.0-mysql php7.0-curl \
                                php7.0-gd php7.0-intl php7.0-imap php7.0-mcrypt php7.0-pspell \
                                php7.0-recode php7.0-sqlite3 php7.0-tidy php7.0-xmlrpc \
                                php7.0-xsl php-apcu php-gd php-apcu-bc php-xdebug > /dev/null 2>&1;

echo -e "\n--- Install phpmyadmin manually (not done) ---\n"

echo -e "\n--- Setting up our MySQL user and db ---\n"
sudo mysql -uroot -p$DBPASSWD -e "CREATE DATABASE $DBNAME"
mysql -uroot -p$DBPASSWD -e "grant all privileges on $DBNAME.* to '$DBUSER'@'localhost' identified by '$DBPASSWD'"

echo -e "\n--- We definitly need to see the PHP errors, turning them on ---\n"
sed -i "s/error_reporting = .*/error_reporting = E_ALL/" /etc/php/7.0/fpm/php.ini
sed -i "s/display_errors = .*/display_errors = On/" /etc/php/7.0/fpm/php.ini

echo -e "\n--- We definitly need to upload large files ---\n"
sed -i "s/server_tokens off;/server_tokens off;\\n\\tclient_max_body_size 256M;/" /etc/nginx/nginx.conf

echo -e "\n--- Configure Nginx virtual host for Roadiz and phpmyadmin ---\n"
sudo mkdir /etc/nginx/snippets;
sudo mkdir /etc/nginx/certs;
sudo mkdir /etc/nginx/sites-available;
sudo rm /etc/nginx/conf.d/default.conf;
sudo cp /var/www/samples/vagrant/nginx-conf.conf /etc/nginx/nginx.conf;
sudo cp /var/www/samples/vagrant/nginx-vhost.conf /etc/nginx/sites-available/default;
sudo cp /var/www/samples/vagrant/roadiz-nginx-include.conf /etc/nginx/snippets/roadiz.conf;

echo -e "\n--- Generating a unique Diffie-Hellman Group ---\n"
sudo openssl dhparam -out /etc/nginx/certs/default.dhparam.pem 2048 > /dev/null 2>&1;

echo -e "\n--- Generating a self-signed SSL certificate ---\n"
sudo openssl req -new -newkey rsa:4096 -days 365 -nodes \
            -x509 -subj "/C=FR/ST=Rhonealpes/L=Lyon/O=ACME/CN=localhost" \
            -keyout /etc/nginx/certs/default.key \
            -out /etc/nginx/certs/default.crt > /dev/null 2>&1;

echo -e "\n--- Configure PHP-FPM default pool ---\n"
sudo rm /etc/php/7.0/fpm/pool.d/www.conf;
sudo cp /var/www/samples/vagrant/php-pool.conf /etc/php/7.0/fpm/pool.d/www.conf;
sudo cp /var/www/samples/vagrant/opcache-recommended.ini /etc/php/7.0/fpm/conf.d/20-opcache-recommended.ini;

echo -e "\n--- Restarting Nginx and PHP servers ---\n"
sudo service nginx restart > /dev/null 2>&1;
sudo service php7.0-fpm restart > /dev/null 2>&1;

##### CLEAN UP #####
sudo dpkg --configure -a  > /dev/null 2>&1; # when upgrade or install doesnt run well (e.g. loss of connection) this may resolve quite a few issues
sudo apt-get autoremove -y  > /dev/null 2>&1; # remove obsolete packages

# Set envvars
export DB_HOST=$DBHOST
export DB_NAME=$DBNAME
export DB_USER=$DBUSER
export DB_PASS=$DBPASSWD

echo -e "\n-----------------------------------------------------------------"
echo -e "\n----------- Your Roadiz Vagrant is ready in /var/www ------------"
echo -e "\n-----------------------------------------------------------------"
echo -e "\nDo not forget to \"composer install\" and to add "
echo -e "\nyour host IP into install.php and dev.php (generally 10.0.2.2)"
echo -e "\nto get allowed in install and dev entrypoints."
echo -e "\n* Type http://localhost:8080/install.php to proceed to install."
echo -e "\n* Type https://localhost:4430/install.php to proceed using SSL (cert is not authentified)."
echo -e "\n* MySQL User: $DBUSER"
echo -e "\n* MySQL Password: $DBPASSWD"
echo -e "\n* MySQL Database: $DBNAME"
echo -e "\n-----------------------------------------------------------------"
