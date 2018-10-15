#!/bin/bash

# WARNING!
#
# 제대로 관리되지 않아 작동하지 않는 스크립트이다. 사용하지 말것. 문서 용도로만
# 참고하라. MySQL과 Caddy, PEAR's Mail 세팅은 언급되어있지 않다. 스왑도
# 생성해야함.

if [ ! -f /opt/femiwiki-provisioned ]; then
    sudo timedatectl set-timezone Asia/Seoul

    # Install mariadb-server if db sever is localhost
    if [ "$3" = "localhost" ];
    then
        sudo apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xcbcb082a1bb943db
        sudo add-apt-repository -y 'deb [arch=amd64,i386,ppc64el] http://ftp.kaist.ac.kr/mariadb/repo/10.1/ubuntu trusty main'
        debconf-set-selections <<< "mysql-server mysql-server/root_password password $4"
        debconf-set-selections <<< "mysql-server mysql-server/root_password_again password $4"
        sudo apt-get install -y --force-yes mariadb-server
    fi

    # Install PHP
    LC_ALL=C.UTF-8 sudo add-apt-repository -y ppa:ondrej/php
    sudo apt-get update
    sudo apt-get install -y --force-yes \
        build-essential \
        software-properties-common \
        git \
        unzip \
        memcached \
        php7.2-fpm php7.2-mysql php7.2-xml php7.2-mbstring php7.2-curl php7.2-intl php7.2-apcu \
        librsvg2-bin
    sudo apt-get --purge autoremove -y

    # 업로드 용량제한 2MiB에서 10MiB로 늘림
    sudo sed -ri \
      '/^\s*(post_max_size|upload_max_filesize)\s*=\s*.+?\s*$/s/=.*$/= 10M/' \
      /etc/php/7.2/fpm/php.ini
    sudo service php7.2-fpm reload

    # Install Composer
    EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_SIGNATURE="$(php -r "echo hash_file('SHA384', 'composer-setup.php');")"

    if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
    then
        >&2 echo 'ERROR: Invalid installer signature'
        rm composer-setup.php
        exit 1
    fi

    sudo mkdir /etc/composer
    php composer-setup.php --install-dir=/etc/composer --quiet
    rm composer-setup.php

    # Download Mediawiki source
    sudo git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/mediawiki/core.git -b REL1_31 /var/www/femiwiki.com
    sudo chown -R www-data:www-data /var/www/femiwiki.com
    sudo /etc/composer/composer.phar update --no-dev -d /var/www/femiwiki.com

    # Plugins

    ## Femiwiki (Skin)
    wget -nv https://github.com/femiwiki/skin/archive/master.tar.gz
    sudo tar -xzf master.tar.gz -C /var/www/femiwiki.com/skins
    sudo mv /var/www/femiwiki.com/skins/skin-master /var/www/femiwiki.com/skins/Femiwiki
    rm master.tar.gz

    ## AWS
    # TODO: https://github.com/edwardspec/mediawiki-aws-s3 이거 설치했음

    ## VisualEditor
    sudo git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/VisualEditor \
        -b REL1_31 /var/www/femiwiki.com/extensions/VisualEditor
    sudo /etc/composer/composer.phar update --no-dev -d /var/www/femiwiki.com/extensions/VisualEditor

    ## TemplateData
    sudo git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/TemplateData \
        -b REL1_31 /var/www/femiwiki.com/extensions/TemplateData
    sudo /etc/composer/composer.phar update --no-dev -d /var/www/femiwiki.com/extensions/TemplateData

    ## TwoColConflict
    sudo git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/TwoColConflict \
        -b REL1_31 /var/www/femiwiki.com/extensions/TwoColConflict
    sudo /etc/composer/composer.phar update --no-dev -d /var/www/femiwiki.com/extensions/TwoColConflict

    ## RevisionSlider
    sudo git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/RevisionSlider \
        -b REL1_31 /var/www/femiwiki.com/extensions/RevisionSlider
    sudo /etc/composer/composer.phar update --no-dev -d /var/www/femiwiki.com/extensions/RevisionSlider

    ## Echo
    sudo git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Echo \
        -b REL1_31 /var/www/femiwiki.com/extensions/Echo
    sudo /etc/composer/composer.phar update --no-dev -d /var/www/femiwiki.com/extensions/Echo

    ## Thanks
    sudo git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Thanks \
        -b REL1_31 /var/www/femiwiki.com/extensions/Thanks
    sudo /etc/composer/composer.phar update --no-dev -d /var/www/femiwiki.com/extensions/Thanks

    ## Flow
    sudo git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Flow \
        -b REL1_31 /var/www/femiwiki.com/extensions/Flow
    sudo /etc/composer/composer.phar update --no-dev -d /var/www/femiwiki.com/extensions/Flow

    ## Scribunto
    sudo git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Scribunto \
        -b REL1_31 /var/www/femiwiki.com/extensions/Scribunto
    sudo /etc/composer/composer.phar update --no-dev -d /var/www/femiwiki.com/extensions/Scribunto

    ## TemplateStyles
    sudo git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/TemplateStyles \
        -b REL1_31 /var/www/femiwiki.com/extensions/TemplateStyles
    sudo /etc/composer/composer.phar update --no-dev -d /var/www/femiwiki.com/extensions/TemplateStyles

    ## CategoryTree
    sudo git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/CategoryTree \
        -b REL1_31 /var/www/femiwiki.com/extensions/CategoryTree
    sudo /etc/composer/composer.phar update --no-dev -d /var/www/femiwiki.com/extensions/CategoryTree

    ## Disambiguator
    sudo git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Disambiguator \
        -b REL1_31 /var/www/femiwiki.com/extensions/Disambiguator
    sudo /etc/composer/composer.phar update --no-dev -d /var/www/femiwiki.com/extensions/Disambiguator

    ## AbuseFilter
    sudo git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/AbuseFilter \
        -b REL1_31 /var/www/femiwiki.com/extensions/AbuseFilter
    sudo /etc/composer/composer.phar update --no-dev -d /var/www/femiwiki.com/extensions/AbuseFilter

    ## CheckUser
    sudo git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/CheckUser \
        -b REL1_31 /var/www/femiwiki.com/extensions/CheckUser
    sudo /etc/composer/composer.phar update --no-dev -d /var/www/femiwiki.com/extensions/CheckUser

    ## UserMerge
    sudo git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/UserMerge \
        -b REL1_31 /var/www/femiwiki.com/extensions/UserMerge
    sudo /etc/composer/composer.phar update --no-dev -d /var/www/femiwiki.com/extensions/UserMerge

    ## Widgets
    sudo git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Widgets \
        -b REL1_31 /var/www/femiwiki.com/extensions/Widgets
    sudo /etc/composer/composer.phar update --no-dev -d /var/www/femiwiki.com/extensions/Widgets
    sudo chmod o+w /var/www/femiwiki.com/extensions/Widgets/compiled_templates

    ## EmbedVideo
    wget -nv https://github.com/HydraWiki/mediawiki-embedvideo/archive/v2.7.4.zip
    unzip v2.7.4.zip
    sudo mv mediawiki-embedvideo-2.7.4 /var/www/femiwiki.com/extensions/EmbedVideo
    rm v2.7.4.zip

    ## Description2
    sudo git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Description2 \
        -b REL1_31 /var/www/femiwiki.com/extensions/Description2
    sudo /etc/composer/composer.phar update --no-dev -d /var/www/femiwiki.com/extensions/Description2

    ## OpenGraphMeta
    sudo git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/OpenGraphMeta \
        -b REL1_31 /var/www/femiwiki.com/extensions/OpenGraphMeta
    sudo /etc/composer/composer.phar update --no-dev -d /var/www/femiwiki.com/extensions/OpenGraphMeta

    ## PageImages
    sudo git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/PageImages \
        -b REL1_31 /var/www/femiwiki.com/extensions/PageImages
    sudo /etc/composer/composer.phar update --no-dev -d /var/www/femiwiki.com/extensions/PageImages

    ## SimpleMathJax
    wget -nv https://github.com/jmnote/SimpleMathJax/archive/v0.7.3.zip
    sudo unzip v0.7.3.zip
    sudo mv SimpleMathJax-0.7.3 /var/www/femiwiki.com/extensions/SimpleMathJax
    rm v0.7.3.zip

    ## Sanctions
    wget -nv https://github.com/femiwiki/sanctions/archive/master.tar.gz
    sudo tar -xzf master.tar.gz -C /var/www/femiwiki.com/extensions
    sudo mv /var/www/femiwiki.com/extensions/sanctions-master /var/www/femiwiki.com/extensions/Sanctions
    rm master.tar.gz

    ## HTMLTags
    wget -nv https://extdist.wmflabs.org/dist/extensions/HTMLTags-REL1_31-b7377b0.tar.gz
    sudo tar -xzf HTMLTags-REL1_31-b7377b0.tar.gz -C /var/www/femiwiki.com/extensions
    rm HTMLTags-REL1_31-b7377b0.tar.gz

    ## BetaFeatures
    sudo git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/BetaFeatures \
        -b REL1_31 /var/www/femiwiki.com/extensions/BetaFeatures
    sudo /etc/composer/composer.phar update --no-dev -d /var/www/femiwiki.com/extensions/BetaFeatures

    # Initialize and generate LocalSettings.php
    php /var/www/femiwiki.com/maintenance/install.php \
        --scriptpath "/w" \
        --dbtype mysql --dbname femiwiki --dbserver "$3" --dbuser root \
        --dbpass $4 --installdbuser root --installdbpass root \
        --server $1://$2 --lang ko --pass root "페미위키" Admin

    # Link directories only in development mode
    if [ "$1" = "http" ];
    then
        sudo ln -sf /vagrant/www/fw-resources /var/www/femiwiki.com/fw-resources
        sudo ln -sf /vagrant/www/skins/Femiwiki /var/www/femiwiki.com/skins/Femiwiki
    fi

    sudo mkdir /opt/femiwiki
    sudo touch /opt/femiwiki-provisioned
    sudo mkdir /opt/femiwiki/cache
    sudo chown www-data:www-data /opt/femiwiki/cache
fi

# Copy resources
if [ "$1" = "https" ];
then
    sudo cp /vagrant/www/robots.txt /var/www/femiwiki.com/
    sudo cp /vagrant/www/LocalSettingsSecure.php /opt/femiwiki/
    sudo cp /vagrant/www/google6a8c7f190836bc0d.html /var/www/femiwiki.com/
    sudo cp /vagrant/www/naver09b95fd90c3231a5a37f42d39222c217.html /var/www/femiwiki.com/
    sudo cp /vagrant/www/favicon.ico /var/www/femiwiki.com/
    sudo cp -r /vagrant/www/extensions/FacetedCategory /var/www/femiwiki.com/extensions/
    sudo cp -r /vagrant/www/extensions/UnifiedExtensionForFemiwiki /var/www/femiwiki.com/extensions/
    sudo cp -r /vagrant/www/extensions/CategoryIntersectionSearch /var/www/femiwiki.com/extensions/
    sudo cp -r /vagrant/www/extensions/Sanctions /var/www/femiwiki.com/extensions/
else
    sudo ln -sf /vagrant/www/robots.txt /var/www/femiwiki.com/robots.txt
    sudo ln -sf /vagrant/www/LocalSettingsSecure.php /opt/femiwiki/LocalSettingsSecure.php
    sudo ln -sf /vagrant/www/google6a8c7f190836bc0d.html /var/www/femiwiki.com/google6a8c7f190836bc0d.html
    sudo ln -sf /vagrant/www/naver09b95fd90c3231a5a37f42d39222c217.html /var/www/femiwiki.com/naver09b95fd90c3231a5a37f42d39222c217.html
    sudo ln -sf /vagrant/www/favicon.ico /var/www/femiwiki.com/favicon.ico
    sudo ln -sf /vagrant/www/extensions/FacetedCategory /var/www/femiwiki.com/extensions/FacetedCategory
    sudo ln -sf /vagrant/www/extensions/UnifiedExtensionForFemiwiki /var/www/femiwiki.com/extensions/UnifiedExtensionForFemiwiki
    sudo ln -sf /vagrant/www/extensions/CategoryIntersectionSearch /var/www/femiwiki.com/extensions/CategoryIntersectionSearch
    sudo ln -sf /vagrant/www/extensions/Sanctions /var/www/femiwiki.com/extensions/Sanctions
fi

# Overwrite LocalSettings.php generated by install script
sudo cp /vagrant/www/LocalSettings.php /var/www/femiwiki.com/LocalSettings.php
sudo sed -i s/PROTOCOL/$1/ /var/www/femiwiki.com/LocalSettings.php
sudo sed -i s/HOST/$2/ /var/www/femiwiki.com/LocalSettings.php
sudo sed -i s/PARSOID/$5/ /var/www/femiwiki.com/LocalSettings.php

# Copy directories only in production mode
if [ "$1" = "https" ];
then
    sudo cp -r /vagrant/www/fw-resources /var/www/femiwiki.com/
    sudo mkdir -p /var/www/femiwiki.com/skins/Femiwiki
    sudo cp -r /vagrant/www/skins/Femiwiki /var/www/femiwiki.com/skins/
fi

sudo chown -R www-data:www-data /var/www/femiwiki.com

# Run update script
sudo /var/www/femiwiki.com/maintenance/update.php --quick
