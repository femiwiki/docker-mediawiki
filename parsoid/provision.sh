#!/usr/bin/env bash
sudo apt-key advanced --keyserver pgp.mit.edu --recv-keys 90E9F83F22250DD7
sudo apt-add-repository "deb https://releases.wikimedia.org/debian jessie-mediawiki main"
sudo apt-get update && sudo apt-get install -y --force-yes parsoid
sudo cp /vagrant/parsoid/settings.js /etc/mediawiki/parsoid/settings.js
sudo sed -i s/PROTOCOL/$1/ /etc/mediawiki/parsoid/settings.js
sudo sed -i s/HOST/$2/ /etc/mediawiki/parsoid/settings.js

sudo service parsoid restart

