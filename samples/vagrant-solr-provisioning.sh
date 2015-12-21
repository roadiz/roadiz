#!/bin/bash
#
export DEBIAN_FRONTEND=noninteractive

# Apache Solr
SOLR_VERSION="5.3.1"
SOLR_MIRROR="http://apache.mirrors.ovh.net/ftp.apache.org/dist"

echo -e "\n--- Okay, installing now... ---\n"
echo -e "\n--- Updating packages list ---\n"

sudo apt-get -qq update;

# Install Apache Solr - based on article from Tomasz Muras - https://twitter.com/zabuch
# http://jmuras.com/blog/2012/setup-solr-4-tomcat-ubuntu-server-12-04-lts/
echo -e "\n--- Installing Apache Solr ---\n"
sudo apt-get -qq -f -y install openjdk-7-jre-headless unzip > /dev/null 2>&1;
cd /tmp/
sudo wget –q --output-document=solr-$SOLR_VERSION.tgz $SOLR_MIRROR/lucene/solr/$SOLR_VERSION/solr-$SOLR_VERSION.tgz > /dev/null 2>&1
tar xzf solr-$SOLR_VERSION.tgz
sudo cp -fr solr-$SOLR_VERSION /opt/solr
sudo cp /opt/solr/bin/init.d/solr /etc/init.d/solr
sudo sed -i "s/RUNAS=solr/#RUNAS=solr/" /etc/init.d/solr
sudo mkdir -p /var/solr
sudo cp /opt/solr/bin/solr.in.sh /var/solr/solr.in.sh
sudo update-rc.d solr defaults > /dev/null 2>&1;
sudo update-rc.d solr enable > /dev/null 2>&1;
sudo service solr start > /dev/null 2>&1;

echo -e "\n--- Create a new Solr core called \"roadiz\"  ---\n"
sudo /opt/solr/bin/solr create_core -c roadiz > /dev/null 2>&1;

echo -e "\n--- Restarting Solr server ---\n"
sudo service solr restart > /dev/null 2>&1;

##### CLEAN UP #####
echo -e "\n--- Cleaning up ---\n"
sudo dpkg --configure -a  > /dev/null 2>&1; # when upgrade or install doesnt run well (e.g. loss of connection) this may resolve quite a few issues
sudo apt-get autoremove -y  > /dev/null 2>&1; # remove obsolete packages

# Set envvars
export DB_HOST=$DBHOST
export DB_NAME=$DBNAME
export DB_USER=$DBUSER
export DB_PASS=$DBPASSWD

echo -e "\n--- Your Solr server is ready ---\n"
echo -e "\n* Type http://localhost:8983/solr to use Apache Solr admin."
