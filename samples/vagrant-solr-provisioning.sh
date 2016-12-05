#!/bin/bash
#
RED='\033[0;31m'
NC='\033[0m' # No Color
export DEBIAN_FRONTEND=noninteractive

# Apache Solr
SOLR_VERSION="6.3.0"
SOLR_MIRROR="http://www-eu.apache.org/dist"

echo -e "\n--- Installing Oracle JDK 8 ---\n"
sudo locale-gen fr_FR.UTF-8;
sudo apt-get -qq update;
sudo apt-get install -qq -f -y python-software-properties debconf-utils unzip > /dev/null 2>&1;
sudo add-apt-repository -y ppa:webupd8team/java > /dev/null 2>&1;
if [ $? -eq 0 ]; then
   echo -e "\t--- OK\n"
else
   echo -e "${RED}\t!!! FAIL${NC}\n"
   echo -e "${RED}\t!!! Please destroy your vagrant and provision again.${NC}\n"
   exit 1;
fi


sudo apt-get -qq update;
# Accept silently Oracle license
echo "oracle-java8-installer shared/accepted-oracle-license-v1-1 select true" | sudo debconf-set-selections > /dev/null 2>&1;
sudo apt-get install -qq -f -y oracle-java8-installer > /dev/null 2>&1;
if [ $? -eq 0 ]; then
   echo -e "\t--- OK\n"
else
   echo -e "${RED}\t!!! FAIL${NC}\n"
   echo -e "${RED}\t!!! Please destroy your vagrant and provision again.${NC}\n"
   exit 1;
fi


echo -e "\n--- Downloading Apache Solr (may take a while, be patient) ---\n"
sudo wget --output-document=solr-$SOLR_VERSION.tgz $SOLR_MIRROR/lucene/solr/$SOLR_VERSION/solr-$SOLR_VERSION.tgz > /dev/null 2>&1
if [ $? -eq 0 ]; then
   echo -e "\t--- OK\n"
else
   echo -e "${RED}\t!!! FAIL - Downloading ${SOLR_MIRROR}/lucene/solr/${SOLR_VERSION}/solr-${SOLR_VERSION}.tgz ${NC}\n"
   echo -e "${RED}\t!!! Please verify Solr version exists and provision again.${NC}\n"
   exit 1;
fi

echo -e "\n--- Extracting Apache Solr\n"
sudo tar -xzf solr-$SOLR_VERSION.tgz
sudo rm -rf solr-$SOLR_VERSION.tgz

echo -e "\n--- Installing Apache Solr\n"
sudo mv solr-$SOLR_VERSION /opt/solr
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

echo -e "\n--- Create a new Solr core called \"roadiz\"\n"
sudo /opt/solr/bin/solr create_core -c roadiz > /dev/null 2>&1;
if [ $? -eq 0 ]; then
   echo -e "\t--- OK\n"
else
   echo -e "${RED}\t!!! FAIL${NC}\n"
   echo -e "${RED}\t!!! Please destroy your vagrant and provision again.${NC}\n"
   exit 1;
fi


echo -e "\n--- Create a new Solr core called \"roadiz_test\"\n"
sudo /opt/solr/bin/solr create_core -c roadiz_test > /dev/null 2>&1;
if [ $? -eq 0 ]; then
   echo -e "\t--- OK\n"
else
   echo -e "${RED}\t!!! FAIL${NC}\n"
   echo -e "${RED}\t!!! Please destroy your vagrant and provision again.${NC}\n"
   exit 1;
fi


echo -e "\n--- Restarting Solr server ---\n"
sudo service solr restart > /dev/null 2>&1;

export PRIVATE_IP=`/sbin/ifconfig eth1 | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'`

echo -e "\n-----------------------------------------------------------"
echo -e "\n---------------- Your Solr server is ready ----------------"
echo -e "\n* Type http://$PRIVATE_IP:8983/solr to use Apache Solr admin."
echo -e "\n-----------------------------------------------------------"
