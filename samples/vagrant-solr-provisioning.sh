#!/bin/bash
#
export DEBIAN_FRONTEND=noninteractive

# Apache Solr
SOLR_VERSION="6.2.0"
SOLR_MIRROR="http://www-eu.apache.org/dist"

echo -e "\n--- Installing Oracle JDK 8 ---\n"
sudo locale-gen fr_FR.UTF-8;
sudo apt-get -qq update;
sudo apt-get install -qq -f -y python-software-properties debconf-utils unzip > /dev/null 2>&1;
sudo add-apt-repository -y ppa:webupd8team/java > /dev/null 2>&1;
sudo apt-get -qq update;
# Accept silently Oracle license
echo "oracle-java8-installer shared/accepted-oracle-license-v1-1 select true" | sudo debconf-set-selections > /dev/null 2>&1;
sudo apt-get install -qq -f -y oracle-java8-installer > /dev/null 2>&1;

echo -e "\n--- Downloading Apache Solr (may take a while, be patient) ---\n"
sudo wget –q --output-document=solr-$SOLR_VERSION.tgz $SOLR_MIRROR/lucene/solr/$SOLR_VERSION/solr-$SOLR_VERSION.tgz > /dev/null 2>&1
echo -e "\n--- Installing Apache Solr (may take a while, be patient) ---\n"
sudo tar xzf solr-$SOLR_VERSION.tgz
sudo mv solr-$SOLR_VERSION /opt/solr
sudo rm -f solr-$SOLR_VERSION.tgz
sudo ln -s /opt/solr/bin/solr.in.sh /etc/default/solr.in.sh
# Run Solr as root for dev environment only, do not do this in prod ;-)
sudo sed -i "s/RUNAS=\"solr\"/RUNAS=\"root\"/" /opt/solr/bin/init.d/solr
sudo ln -s /opt/solr/bin/init.d/solr /etc/init.d/solr
sudo mkdir -p /opt/solr/server/logs
sudo mkdir -p /var/solr
sudo cp /opt/solr/bin/solr.in.sh /var/solr/solr.in.sh
sudo update-rc.d solr defaults > /dev/null 2>&1;
sudo update-rc.d solr enable > /dev/null 2>&1;
sudo service solr start > /dev/null 2>&1;

echo -e "\n--- Create a new Solr core called \"roadiz\"  ---\n"
sudo /opt/solr/bin/solr create_core -c roadiz > /dev/null 2>&1;
echo -e "\n--- Create a new Solr core called \"roadiz_test\"  ---\n"
sudo /opt/solr/bin/solr create_core -c roadiz_test > /dev/null 2>&1;

echo -e "\n--- Restarting Solr server ---\n"
sudo service solr restart > /dev/null 2>&1;

echo -e "\n-----------------------------------------------------------"
echo -e "\n---------------- Your Solr server is ready ----------------"
echo -e "\n* Type http://localhost:8983/solr to use Apache Solr admin."
echo -e "\n-----------------------------------------------------------"
