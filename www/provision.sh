# WARNING!
#
# 제대로 관리되지 않아 작동하지 않는 스크립트이다. 사용하지 말것. 문서 용도로만
# 참고하라. MySQL과 Caddy 세팅은 언급되어있지 않다.

if [ ! -f /opt/femiwiki-provisioned ]; then
    sudo timedatectl set-timezone Asia/Seoul

    # Install PHP
    LC_ALL=C.UTF-8 sudo add-apt-repository -y ppa:ondrej/php
    sudo apt-get update
    sudo apt-get install -y --force-yes \
        build-essential \
        software-properties-common \
        git \
        unzip \
        memcached \
        php7.2-fpm php7.2-mysql php7.2-curl php7.2-intl php7.2-apcu \
        librsvg2-bin
    sudo apt-get --purge autoremove -y

    # Download Mediawiki source
    wget -nv https://releases.wikimedia.org/mediawiki/1.31/mediawiki-1.31.1.tar.gz
    sudo mkdir /var/www/femiwiki.com
    sudo tar -xzf mediawiki-1.31.1.tar.gz --strip-components 1 -C /var/www/femiwiki.com
    rm mediawiki-1.31.1.tar.gz

    sudo chown -R www-data:www-data /var/www/femiwiki.com

    # Plugins

    ## VisualEditor
    wget -nv https://extdist.wmflabs.org/dist/extensions/VisualEditor-REL1_31-13a585a.tar.gz
    sudo tar -xzf VisualEditor-REL1_31-13a585a.tar.gz -C /var/www/femiwiki.com/extensions
    rm VisualEditor-REL1_31-13a585a.tar.gz

    ## TemplateData
    wget -nv https://extdist.wmflabs.org/dist/extensions/TemplateData-REL1_31-61adb16.tar.gz
    sudo tar -xzf TemplateData-REL1_31-61adb16.tar.gz -C /var/www/femiwiki.com/extensions
    rm TemplateData-REL1_31-61adb16.tar.gz

    ## TwoColConflict
    wget -nv https://extdist.wmflabs.org/dist/extensions/TwoColConflict-REL1_31-5c02080.tar.gz
    sudo tar -xzf TwoColConflict-REL1_31-5c02080.tar.gz -C /var/www/femiwiki.com/extensions
    rm TwoColConflict-REL1_31-5c02080.tar.gz

    ## RevisionSlider
    wget -nv https://extdist.wmflabs.org/dist/extensions/RevisionSlider-REL1_31-0ba3b58.tar.gz
    sudo tar -xzf RevisionSlider-REL1_31-0ba3b58.tar.gz -C /var/www/femiwiki.com/extensions
    rm RevisionSlider-REL1_31-0ba3b58.tar.gz

    ## Echo
    wget -nv https://extdist.wmflabs.org/dist/extensions/Echo-REL1_31-249ed89.tar.gz
    sudo tar -xzf Echo-REL1_31-249ed89.tar.gz -C /var/www/femiwiki.com/extensions
    rm Echo-REL1_31-249ed89.tar.gz

    ## Thanks
    wget -nv https://extdist.wmflabs.org/dist/extensions/Thanks-REL1_31-a845a46.tar.gz
    sudo tar -xzf Thanks-REL1_31-a845a46.tar.gz -C /var/www/femiwiki.com/extensions
    rm Thanks-REL1_31-a845a46.tar.gz

    ## Flow
    wget -nv https://extdist.wmflabs.org/dist/extensions/Flow-REL1_31-5a58ab8.tar.gz
    sudo tar -xzf Flow-REL1_31-5a58ab8.tar.gz -C /var/www/femiwiki.com/extensions
    rm Flow-REL1_31-5a58ab8.tar.gz

    ## Scribunto
    wget -nv https://extdist.wmflabs.org/dist/extensions/Scribunto-REL1_31-106fbf4.tar.gz
    sudo tar -xzf Scribunto-REL1_31-106fbf4.tar.gz -C /var/www/femiwiki.com/extensions
    rm Scribunto-REL1_31-106fbf4.tar.gz

    ## TemplateStyles
    wget -nv https://extdist.wmflabs.org/dist/extensions/TemplateStyles-REL1_31-e5da5c0.tar.gz
    sudo tar -xzf TemplateStyles-REL1_31-e5da5c0.tar.gz -C /var/www/femiwiki.com/extensions
    rm TemplateStyles-REL1_31-e5da5c0.tar.gz

    ## CategoryTree
    wget -nv https://extdist.wmflabs.org/dist/extensions/CategoryTree-REL1_31-c24e5ed.tar.gz
    sudo tar -xzf CategoryTree-REL1_31-c24e5ed.tar.gz -C /var/www/femiwiki.com/extensions
    rm CategoryTree-REL1_31-c24e5ed.tar.gz

    ## Disambiguator
    wget -nv https://extdist.wmflabs.org/dist/extensions/Disambiguator-REL1_31-c8645b6.tar.gz
    sudo tar -xzf Disambiguator-REL1_31-c8645b6.tar.gz -C /var/www/femiwiki.com/extensions
    rm Disambiguator-REL1_31-c8645b6.tar.gz

    ## AbuseFilter
    wget -nv https://extdist.wmflabs.org/dist/extensions/AbuseFilter-REL1_31-9fc6235.tar.gz
    sudo tar -xzf AbuseFilter-REL1_31-9fc6235.tar.gz -C /var/www/femiwiki.com/extensions
    rm AbuseFilter-REL1_31-9fc6235.tar.gz

    ## CheckUser
    wget -nv https://extdist.wmflabs.org/dist/extensions/CheckUser-REL1_31-b98e6a4.tar.gz
    sudo tar -xzf CheckUser-REL1_31-b98e6a4.tar.gz -C /var/www/femiwiki.com/extensions
    rm CheckUser-REL1_31-b98e6a4.tar.gz

    ## UserMerge
    wget -nv https://extdist.wmflabs.org/dist/extensions/UserMerge-REL1_31-a641f0c.tar.gz
    sudo tar -xzf UserMerge-REL1_31-a641f0c.tar.gz -C /var/www/femiwiki.com/extensions
    rm UserMerge-REL1_31-a641f0c.tar.gz

    ## EmbedVideo
    wget -nv https://github.com/HydraWiki/mediawiki-embedvideo/archive/v2.7.4.zip
    unzip v2.7.4.zip
    sudo mv mediawiki-embedvideo-2.7.4 /var/www/femiwiki.com/extensions/EmbedVideo
    rm v2.7.4.zip

    ## Description2
    wget -nv https://extdist.wmflabs.org/dist/extensions/Description2-REL1_31-06229d2.tar.gz
    sudo tar -xzf Description2-REL1_31-06229d2.tar.gz -C /var/www/femiwiki.com/extensions
    rm Description2-REL1_31-06229d2.tar.gz

    ## OpenGraphMeta
    wget -nv https://extdist.wmflabs.org/dist/extensions/OpenGraphMeta-REL1_31-54270b0.tar.gz
    sudo tar -xzf OpenGraphMeta-REL1_31-54270b0.tar.gz -C /var/www/femiwiki.com/extensions
    rm OpenGraphMeta-REL1_31-54270b0.tar.gz

    ## PageImages
    wget -nv https://extdist.wmflabs.org/dist/extensions/PageImages-REL1_31-34b0dd5.tar.gz
    sudo tar -xzf PageImages-REL1_31-34b0dd5.tar.gz -C /var/www/femiwiki.com/extensions
    rm PageImages-REL1_31-34b0dd5.tar.gz

    ## SimpleMathJax
    wget -nv https://github.com/jmnote/SimpleMathJax/archive/v0.7.3.zip
    sudo unzip v0.7.3.zip
    sudo mv SimpleMathJax-0.7.3 /var/www/femiwiki.com/extensions/SimpleMathJax
    rm v0.7.3.zip

    ## HTMLTags
    wget -nv https://extdist.wmflabs.org/dist/extensions/HTMLTags-REL1_31-b7377b0.tar.gz
    sudo tar -xzf HTMLTags-REL1_31-b7377b0.tar.gz -C /var/www/femiwiki.com/extensions
    rm HTMLTags-REL1_31-b7377b0.tar.gz

    ## BetaFeatures
    wget -nv https://extdist.wmflabs.org/dist/extensions/BetaFeatures-REL1_31-ec757a5.tar.gz
    sudo tar -xzf BetaFeatures-REL1_31-ec757a5.tar.gz -C /var/www/femiwiki.com/extensions
    rm BetaFeatures-REL1_31-ec757a5.tar.gz

    # Initialize and generate LocalSettings.php
    # TODO: DB 설정 틀렸음
    php /var/www/femiwiki.com/maintenance/install.php \
        --scriptpath "/w" \
        --dbtype mysql --dbname femiwiki --dbserver localhost --dbuser root \
        --dbpass root --installdbuser root --installdbpass root \
        --server https://femiwiki.com --lang ko --pass "$4" "페미위키" Admin

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
sudo sed -i s/PARSOID/$3/ /var/www/femiwiki.com/LocalSettings.php

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
