# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure(2) do |config|
  config.vm.box = "ubuntu/trusty64"
  config.vm.network "forwarded_port", guest: 80, host: 8080
  config.vm.network "forwarded_port", guest: 8983, host: 8983
  config.vm.network "private_network", ip: "192.168.33.10"

  # Create a public network, which generally matched to bridged network.
  # Bridged networks make the machine appear as another physical device on
  # your network.
  # config.vm.network "public_network"

  # Share an additional folder to the guest VM. The first argument is
  # the path on the host to the actual folder. The second argument is
  # the path on the guest to mount the folder. And the optional third
  # argument is a set of non-required options.
  config.vm.synced_folder "./", "/var/www", nfs: true, mount_options: ['actimeo=1']

  config.vm.provider "virtualbox" do |vb|
    # Display the VirtualBox GUI when booting the machine
    #vb.gui = true
    # Enable Symlink over shared folder
    # Userful if you are using symlink for your themes.
    vb.customize ["setextradata", :id, "VBoxInternal2/SharedFoldersEnableSymlinksCreate/v-root", "1"]
    # Customize the amount of memory on the VM:
    vb.memory = "1024"
  end

  config.vm.provision "roadiz", path: "samples/vagrant-provisioning.sh"
  config.vm.provision "solr", path: "samples/vagrant-solr-provisioning.sh"
  config.vm.provision "devtools", path: "samples/vagrant-devtools-provisioning.sh"
end
