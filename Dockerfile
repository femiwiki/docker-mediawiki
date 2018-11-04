FROM php:7.2-fpm-stretch

ENV MEDIAWIKI_MAJOR_VERSION 1.31
ENV MEDIAWIKI_BRANCH REL1_31
ENV MEDIAWIKI_VERSION 1.31.1
ENV MEDIAWIKI_SHA512 ee49649cc37d0a7d45a7c6d90c822c2a595df290be2b5bf085affbec3318768700a458a6e5b5b7e437651400b9641424429d6d304f870c22ec63fae86ffc5152

ENV TZ Asia/Seoul
ENV LC_ALL C.UTF-8

# Install dependencies
RUN apt-get update \
    && apt-get install -y build-essential \
        software-properties-common \
        git \
        unzip \
        memcached \
        librsvg2-bin \
        g++ \
        libicu-dev \
        wget \
    && apt-get --purge autoremove -y
    # Install the PHP extensions we need
RUN docker-php-ext-install mysqli opcache intl \
    # 업로드 용량제한 2MiB에서 10MiB로 늘림
    && { \
        echo 'opcache.post_max_size=10M'; \
        echo 'opcache.upload_max_filesize=10M'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini
    # Install Composer
RUN EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)" \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && ACTUAL_SIGNATURE="$(php -r "echo hash_file('SHA384', 'composer-setup.php');")" \
    && if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then \
        >&2 echo 'ERROR: Invalid installer signature' \
        && rm composer-setup.php \
        && exit 1 \
    ; fi \
    && php composer-setup.php --install-dir=/root --quiet \
    && rm composer-setup.php
    # MediaWiki setup
RUN curl -fSL "https://releases.wikimedia.org/mediawiki/${MEDIAWIKI_MAJOR_VERSION}/mediawiki-${MEDIAWIKI_VERSION}.tar.gz" -o mediawiki.tar.gz \
    && echo "${MEDIAWIKI_SHA512} *mediawiki.tar.gz" | sha512sum -c - \
    && mkdir -p /srv/femiwiki.com/ \
    && tar -xzf mediawiki.tar.gz --strip-components=1 --directory /srv/femiwiki.com/ \
    && rm mediawiki.tar.gz \
    && chown -R www-data:www-data /srv/femiwiki.com/extensions /srv/femiwiki.com/skins /srv/femiwiki.com/cache
    # Download and compose Plugins
    # @Todo Avoid running Composer as root
    # @See https://getcomposer.org/doc/faqs/how-to-install-untrusted-packages-safely.md
RUN mkdir -p /srv/femiwiki.com/skins /srv/femiwiki.com/extensions \
    ## Femiwiki (Skin)
    && wget -nv https://github.com/femiwiki/skin/archive/master.tar.gz && mkdir -p /srv/femiwiki.com/skins/Femiwiki \
    && tar -xzf master.tar.gz --strip-components=1 --directory /srv/femiwiki.com/skins/Femiwiki \
    && rm master.tar.gz \
    ## AWS (v0.9.0)
    && git clone \-\-recurse\-submodules --depth 1 https://github.com/edwardspec/mediawiki-aws-s3.git \
        -b v0.9.0 /srv/femiwiki.com/extensions/AWS \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/AWS \
    ## VisualEditor
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/VisualEditor \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/VisualEditor \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/VisualEditor \
    ## TemplateData
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/TemplateData \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/TemplateData \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/TemplateData \
    ## TwoColConflict
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/TwoColConflict \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/TwoColConflict \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/TwoColConflict \
    ## RevisionSlider
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/RevisionSlider \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/RevisionSlider \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/RevisionSlider \
    ## Echo
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Echo \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/Echo \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/Echo \
    ## Thanks
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Thanks \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/Thanks \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/Thanks \
    ## Flow
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Flow \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/Flow \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/Flow \
    ## Scribunto
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Scribunto \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/Scribunto \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/Scribunto \
    ## TemplateStyles
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/TemplateStyles \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/TemplateStyles \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/TemplateStyles \
    ## Disambiguator
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Disambiguator \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/Disambiguator \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/Disambiguator \
    ## CreateUserPage
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/CreateUserPage \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/CreateUserPage \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/CreateUserPage \
    ## AbuseFilter
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/AbuseFilter \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/AbuseFilter \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/AbuseFilter \
    ## CheckUser
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/CheckUser \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/CheckUser \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/CheckUser \
    ## UserMerge
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/UserMerge \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/UserMerge \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/UserMerge \
    ## Widgets
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Widgets \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/Widgets \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/Widgets \
    && chmod o+w /srv/femiwiki.com/extensions/Widgets/compiled_templates \
    ## CodeMirror
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/CodeMirror \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/CodeMirror \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/CodeMirror \
    ## CharInsert
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/CharInsert \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/CharInsert \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/CharInsert \
    ## EmbedVideo
    && wget -nv https://github.com/HydraWiki/mediawiki-embedvideo/archive/v2.7.4.zip \
    && unzip -q v2.7.4.zip \
    && mv mediawiki-embedvideo-2.7.4 /srv/femiwiki.com/extensions/EmbedVideo \
    && rm v2.7.4.zip \
    ## Description2
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Description2 \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/Description2 \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/Description2 \
    ## OpenGraphMeta
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/OpenGraphMeta \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/OpenGraphMeta \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/OpenGraphMeta \
    ## PageImages
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/PageImages \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/PageImages \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/PageImages \
    ## SimpleMathJax
    && wget -nv https://github.com/jmnote/SimpleMathJax/archive/v0.7.3.zip \
    && unzip -q v0.7.3.zip \
    && mv SimpleMathJax-0.7.3 /srv/femiwiki.com/extensions/SimpleMathJax \
    && rm v0.7.3.zip \
    ## Sanctions
    && wget -nv https://github.com/femiwiki/sanctions/archive/master.tar.gz && mkdir -p /srv/femiwiki.com/extensions/Sanctions \
    && tar -xzf master.tar.gz --strip-components=1 --directory /srv/femiwiki.com/extensions/Sanctions \
    && rm master.tar.gz \
    ## CategoryIntersectionSearch
    && wget -nv https://github.com/femiwiki/categoryIntersectionSearch/archive/master.tar.gz && mkdir -p /srv/femiwiki.com/extensions/CategoryIntersectionSearch \
    && tar -xzf master.tar.gz --strip-components=1 --directory /srv/femiwiki.com/extensions/CategoryIntersectionSearch \
    && rm master.tar.gz \
    ## FacetedCategory
    && wget -nv https://github.com/femiwiki/facetedCategory/archive/master.tar.gz && mkdir -p /srv/femiwiki.com/extensions/FacetedCategory \
    && tar -xzf master.tar.gz --strip-components=1 --directory /srv/femiwiki.com/extensions/FacetedCategory \
    && rm master.tar.gz \
    ## UnifiedExtensionForFemiwiki
    && wget -nv https://github.com/femiwiki/unifiedExtensionForFemiwiki/archive/master.tar.gz && mkdir -p /srv/femiwiki.com/extensions/UnifiedExtensionForFemiwiki \
    && tar -xzf master.tar.gz --strip-components=1 --directory /srv/femiwiki.com/extensions/UnifiedExtensionForFemiwiki \
    && rm master.tar.gz \
    ## HTMLTags
    && wget -nv https://extdist.wmflabs.org/dist/extensions/HTMLTags-REL1_31-b7377b0.tar.gz \
    && tar -xzf HTMLTags-REL1_31-b7377b0.tar.gz -C /srv/femiwiki.com/extensions \
    && rm HTMLTags-REL1_31-b7377b0.tar.gz \
    ## Josa
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Josa \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/Josa \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/Josa \
    ## BetaFeatures
    && git clone \-\-recurse\-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/BetaFeatures \
        -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/BetaFeatures \
    && /root/composer.phar update --no-dev -d /srv/femiwiki.com/extensions/BetaFeatures
    # Remove composer
RUN rm /root/composer.phar

RUN mkdir -p /opt/femiwiki/cache \
    && chown www-data:www-data /opt/femiwiki/cache /srv/femiwiki.com

COPY robots.txt /srv/femiwiki.com/
COPY google6a8c7f190836bc0d.html /srv/femiwiki.com/
COPY naver09b95fd90c3231a5a37f42d39222c217.html /srv/femiwiki.com/
COPY favicon.ico /srv/femiwiki.com/

COPY LocalSettings.php /opt/femiwiki/LocalSettings.php
COPY LocalSettingsSecure.php /opt/femiwiki/LocalSettingsSecure.php

WORKDIR /srv/femiwiki.com
EXPOSE 9000
CMD php /srv/femiwiki.com/maintenance/install.php \
        --scriptpath "/w" \
        --dbtype mysql --dbserver "${DB:-localhost}" --dbname femiwiki --dbuser root \
        --dbpass "${DB_PW:-root}" --installdbuser root --installdbpass "${DB_PW:-root}" \
        --server "${PROTOCOL:-https}://${HOST:-femiwiki.com}" --lang ko --pass root "페미위키" Admin \
    # Overwrite LocalSettings.php generated by install script
    && mv /opt/femiwiki/LocalSettings.php /srv/femiwiki.com/LocalSettings.php \
    && sed -i 's/PROTOCOL/'"${PROTOCOL:-https}"'/' /srv/femiwiki.com/LocalSettings.php \
    && sed -i 's/HOST/'"${HOST:-femiwiki.com}"'/' /srv/femiwiki.com/LocalSettings.php \
    && sed -i 's/PARSOID/'"${PARSOID:-parsoid.femiwiki.com}"'/' /srv/femiwiki.com/LocalSettings.php \
    # Run update script
    && /srv/femiwiki.com/maintenance/update.php --quick \
    && php-fpm

