# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure(2) do |config|
  config.vm.box = "ubuntu/trusty64"
  config.vm.network "forwarded_port", guest: 80, host: 8080
  config.vm.network "private_network", ip: "192.168.33.10"

  # Create a public network, which generally matched to bridged network.
  # Bridged networks make the machine appear as another physical device on
  # your network.
  # config.vm.network "public_network"

  # Share an additional folder to the guest VM. The first argument is
  # the path on the host to the actual folder. The second argument is
  # the path on the guest to mount the folder. And the optional third
  # argument is a set of non-required options.
  config.vm.synced_folder "./", "/var/www", nfs: true

  config.vm.provider "virtualbox" do |vb|
    # Display the VirtualBox GUI when booting the machine
    vb.gui = true
    # Enable Symlink over shared folder
    # Userful if you are using symlink for your themes.
    vb.customize ["setextradata", :id, "VBoxInternal2/SharedFoldersEnableSymlinksCreate/v-root", "1"]
    # Customize the amount of memory on the VM:
    vb.memory = "1024"
  end

  config.vm.provision "shell", inline: <<-SHELL

    export DEBIAN_FRONTEND=noninteractive

    DBHOST=localhost
    DBNAME=roadiz
    DBUSER=roadiz
    DBPASSWD=roadiz

    echo -e "\n--- Okay, installing now... ---\n"
    echo -e "\n--- Updating packages list ---\n"

    sudo apt-get -qq update;

    echo -e "\n--- Install base packages ---\n"
    sudo locale-gen fr_FR.utf8;


    echo -e "\n--- Add some repos to update our distro ---\n"
    sudo add-apt-repository ppa:ondrej/php5 > /dev/null 2>&1
    sudo add-apt-repository ppa:chris-lea/node.js > /dev/null 2>&1

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

    sudo apt-get -qq -f -y install git nginx mariadb-server mariadb-client php5-fpm curl > /dev/null 2>&1;
    sudo apt-get -qq -f -y install php5-cli php5-mysqlnd php5-curl php5-gd php5-intl php5-imagick php5-imap php5-mcrypt php5-memcached php5-ming php5-ps php5-pspell php5-recode php5-sqlite php5-tidy php5-xmlrpc php5-xsl php5-xcache php5-xdebug phpmyadmin > /dev/null 2>&1;

    echo -e "\n--- Setting up our MySQL user and db ---\n"
    sudo mysql -uroot -p$DBPASSWD -e "CREATE DATABASE $DBNAME"
    mysql -uroot -p$DBPASSWD -e "grant all privileges on $DBNAME.* to '$DBUSER'@'localhost' identified by '$DBPASSWD'"

    echo -e "\n--- We definitly need to see the PHP errors, turning them on ---\n"
    sed -i "s/error_reporting = .*/error_reporting = E_ALL/" /etc/php5/fpm/php.ini
    sed -i "s/display_errors = .*/display_errors = On/" /etc/php5/fpm/php.ini

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
      fastcgi_pass unix:/var/run/php5-fpm.sock;
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
      fastcgi_pass unix:/var/run/php5-fpm.sock;
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
      fastcgi_pass unix:/var/run/php5-fpm.sock;
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
    sudo rm /etc/php5/fpm/pool.d/www.conf;
    sudo touch /etc/php5/fpm/pool.d/www.conf;
    sudo cat >> /etc/php5/fpm/pool.d/www.conf <<'EOF'
[www]
user = www-data
group = www-data
listen = /var/run/php5-fpm.sock
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

    echo -e "\n--- Installing Composer for PHP package management ---\n"
    curl --silent https://getcomposer.org/installer | php > /dev/null 2>&1
    sudo mv composer.phar /usr/local/bin/composer

    echo -e "\n--- Installing NodeJS and NPM ---\n"
    sudo apt-get -y install nodejs > /dev/null 2>&1
    curl --silent https://npmjs.org/install.sh | sudo sh > /dev/null 2>&1

    echo -e "\n--- Installing javascript components ---\n"
    sudo npm install -g grunt grunt-cli bower > /dev/null 2>&1

    echo -e "\n--- Restarting servers ---\n"
    sudo service nginx restart
    sudo service php5-fpm restart

    # Set envvars
    export DB_HOST=$DBHOST
    export DB_NAME=$DBNAME
    export DB_USER=$DBUSER
    export DB_PASS=$DBPASSWD

    echo -e "\n--- Your Roadiz Vagrant is ready in /var/www ---\n"
    echo -e "\nDo not forget to \"composer install\" and to copy a default config\n"
    echo -e "\nand a install.php with your host IP address authorized.\n"
    echo -e "\n--- MySQL User: $DBUSER\n"
    echo -e "\n--- MySQL Password: $DBPASSWD\n"
    echo -e "\n--- MySQL Database: $DBNAME\n"

  SHELL
end
