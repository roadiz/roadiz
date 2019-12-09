#!/bin/bash
export DEBIAN_FRONTEND=noninteractive

sudo cp /var/www/samples/vagrant/nginx-vhost.conf /etc/nginx/sites-available/default;
sudo cp /var/www/samples/vagrant/roadiz-nginx-include.conf /etc/nginx/snippets/roadiz.conf;
sudo service nginx reload;
