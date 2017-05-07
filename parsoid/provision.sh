#!/usr/bin/env bash
curl -sL https://deb.nodesource.com/setup_6.x | sudo -E bash -
sudo apt-key advanced --keyserver pgp.mit.edu --recv-keys 90E9F83F22250DD7
sudo apt-add-repository "deb https://releases.wikimedia.org/debian jessie-mediawiki main"
sudo apt-get update && sudo apt-get install -y --force-yes parsoid
sudo cp /vagrant/parsoid/config.yaml /etc/mediawiki/parsoid/config.yaml
sudo sed -i s/PROTOCOL/$1/ /etc/mediawiki/parsoid/config.yaml
sudo sed -i s/HOST/$2/ /etc/mediawiki/parsoid/config.yaml
sudo service parsoid restart
