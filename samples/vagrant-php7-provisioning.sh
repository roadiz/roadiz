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
sudo add-apt-repository ppa:ondrej/php-7.0 > /dev/null 2>&1

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
sudo apt-get -qq -f -y install php7.0-opcache php7.0-cli php7.0-mysql php7.0-curl php7.0-gd php7.0-intl php7.0-imap php7.0-mcrypt php7.0-pspell php7.0-recode php7.0-sqlite3 php7.0-tidy php7.0-xmlrpc php7.0-xsl php-apcu php-gd php-apcu-bc > /dev/null 2>&1;

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
sudo rm /etc/nginx/sites-available/default;
sudo touch /etc/nginx/sites-available/default;
sudo cat >> /etc/nginx/sites-available/default <<'EOF'
server {
listen   80;
root /var/www;
index index.php index.html index.htm;
# Make site accessible from http://localhost/
server_name _;

add_header X-Frame-Options "SAMEORIGIN";
add_header X-XSS-Protection "1; mode=block";
add_header X-Content-Type-Options "nosniff";

location ~ /themes/(.+)\.(php|yml|twig|xlf|rzn|rzt|rzg)$ {
  deny all;
}

# Enable Expire on Themes public assets
location ~* ^/themes/*.*\.(?:ico|css|js|woff2?|eot|ttf|otf|svg|gif|jpe?g|png)$ {
  expires 30d;
  access_log off;
  add_header Pragma "public";
  add_header Cache-Control "public";
  add_header Vary "Accept-Encoding";
}
# Enable Expire on native documents files
location ~* ^/files/*.*\.(?:ico|gif|jpe?g|png)$ {
  expires 15d;
  access_log off;
  add_header Pragma "public";
  add_header Cache-Control "public";
  add_header Vary "Accept-Encoding";
}

location / {
  # First attempt to serve request as file, then
  # as directory, then fall back to front-end controller
  # (do not forget to pass GET parameters).
  try_files $uri $uri/ /index.php?$query_string;
}

location ~ /install.php/ {
  try_files $uri @pass_to_roadiz_install;
}
location @pass_to_roadiz_install{
  rewrite ^ /install.php?$request_uri last;
}
location ~ /dev.php/ {
  try_files $uri @pass_to_roadiz_dev;
}
location @pass_to_roadiz_dev{
  rewrite ^ /dev.php?$request_uri last;
}
location ~ /preview.php/ {
  try_files $uri @pass_to_roadiz_preview;
}
location @pass_to_roadiz_preview{
  rewrite ^ /preview.php?$request_uri last;
}

# redirect server error pages to the static page /50x.html
#
error_page 500 502 503 504 /50x.html;
location = /50x.html {
root /var/www;
}

#
# Production entry point.
#
location ~ ^/index\.php(/|$) {
  fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
  fastcgi_split_path_info ^(.+\.php)(/.+)$;
  fastcgi_pass unix:/var/run/php7.0-fpm.sock;
  include fastcgi_params;
  internal;
}

#
# Preview, Dev and Install entry points.
#
# In production server, don't deploy dev.php or install.php
#
location ~ ^/(dev|install|preview)\.php(/|$) {
  fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
  fastcgi_split_path_info ^(.+\.php)(/.+)$;
  fastcgi_pass unix:/var/run/php7.0-fpm.sock;
  include fastcgi_params;
}
location = /favicon.ico { log_not_found off; access_log off; }
location = /robots.txt  { allow all; access_log off; log_not_found off; }

# deny access to .htaccess files, if Apache's document root
# concurs with nginx's one
location ~ /\.ht {
  deny all;
}
location ~ /\.git {
  deny all;
}
location /src {
  deny all;
}
location /gen-src {
  deny all;
}
location /files/fonts {
  deny all;
}
location /files/private {
  deny all;
}
location /cache {
  deny all;
}
location /bin {
  deny all;
}
location /samples {
  deny all;
}
location /tests {
  deny all;
}
location /vendor {
  deny all;
}
location /conf {
  deny all;
}
location /logs {
  deny all;
}
# deny access to .htaccess files, if Apache's document root
# concurs with nginx's one
#
location ~ /\.ht {
deny all;
}
### phpMyAdmin ###
location /phpmyadmin {
root /usr/share/;
index index.php index.html index.htm;
location ~ ^/phpmyadmin/(.+\.php)$ {
  client_max_body_size 4M;
  client_body_buffer_size 128k;
  try_files $uri =404;
  root /usr/share/;
  # Point it to the fpm socket;
  fastcgi_pass unix:/var/run/php7.0-fpm.sock;
  fastcgi_index index.php;
  fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
  include /etc/nginx/fastcgi_params;
}
location ~* ^/phpmyadmin/(.+\.(jpg|jpeg|gif|css|png|js|ico|html|xml|txt)) {
  root /usr/share/;
}
}
location /phpMyAdmin {
rewrite ^/* /phpmyadmin last;
}
### phpMyAdmin ###
}
EOF

echo -e "\n--- Configure PHP-FPM default pool ---\n"
sudo rm /etc/php/7.0/fpm/pool.d/www.conf;
sudo touch /etc/php/7.0/fpm/pool.d/www.conf;
sudo cat >> /etc/php/7.0/fpm/pool.d/www.conf <<'EOF'
[www]
user = www-data
group = www-data
listen = /var/run/php7.0-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = ondemand
pm.max_children = 4
php_value[max_execution_time] = 120
php_value[post_max_size] = 256M
php_value[upload_max_filesize] = 256M
php_value[display_errors] = On
php_value[error_reporting] = E_ALL
EOF

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
echo -e "\n* MySQL User: $DBUSER"
echo -e "\n* MySQL Password: $DBPASSWD"
echo -e "\n* MySQL Database: $DBNAME"
echo -e "\n-----------------------------------------------------------------"
