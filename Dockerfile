# 파일 목록
#
# /usr/local/etc/php     : PHP 설정
# /srv/femiwiki.com      : 미디어위키 소스코드 및 확장들
# /usr/local/bin         : 임의로 설치한 실행파일들
# /tmp/cache             : 캐시 디렉토리
# /tmp/LocalSettings.php : LocalSettings.php가 임시로 이 위치에 저장됨
# /tmp/secret.php        : 각종 크레덴셜이 저장되어있는 파일
# /tini                  : tini

FROM php:7.2-fpm

ENV MEDIAWIKI_MAJOR_VERSION=1.31
ENV MEDIAWIKI_BRANCH=REL1_31
ENV MEDIAWIKI_VERSION=1.31.1
ENV MEDIAWIKI_SHA512=ee49649cc37d0a7d45a7c6d90c822c2a595df290be2b5bf085affbec3318768700a458a6e5b5b7e437651400b9641424429d6d304f870c22ec63fae86ffc5152

# Set timezone
ENV TZ=Asia/Seoul
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Install dependencies and utilities
RUN apt-get update && apt-get install -y \
      build-essential \
      libicu-dev \
      git \
      memcached \
      librsvg2-bin \
      wget \
      cron \
      sudo

# Install the PHP extensions we need
RUN docker-php-ext-install -j8 mysqli opcache intl

RUN apt-get autoremove -y --purge \
      build-essential \
      libicu-dev

# Install the default object cache.
RUN pecl channel-update pecl.php.net &&\
    pecl install apcu &&\
    docker-php-ext-enable apcu

# Configure PHP
RUN mv /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini &&\
    rm /usr/local/etc/php/php.ini-development &&\
    sed -ri '/^\s*(post_max_size|upload_max_filesize)\s*=\s*.+?\s*$/s/=.*$/= 10M/' /usr/local/etc/php/php.ini

# Configure PHP opcache
# Reference: https://secure.php.net/manual/en/opcache.installation.php
RUN { \
      echo 'opcache.memory_consumption=128'; \
      echo 'opcache.interned_strings_buffer=8'; \
      echo 'opcache.max_accelerated_files=4000'; \
      echo 'opcache.revalidate_freq=60'; \
      echo 'opcache.fast_shutdown=1'; \
      echo 'opcache.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# MediaWiki setup
RUN curl -fSL "https://releases.wikimedia.org/mediawiki/${MEDIAWIKI_MAJOR_VERSION}/mediawiki-${MEDIAWIKI_VERSION}.tar.gz" -o mediawiki.tar.gz &&\
    echo "${MEDIAWIKI_SHA512} *mediawiki.tar.gz" | sha512sum -c - &&\
    mkdir -p /srv/femiwiki.com/ &&\
    tar -xzf mediawiki.tar.gz --strip-components=1 --directory /srv/femiwiki.com/ &&\
    rm mediawiki.tar.gz

# Install Composer
RUN EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)" &&\
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" &&\
    ACTUAL_SIGNATURE="$(php -r "echo hash_file('SHA384', 'composer-setup.php');")" &&\
    if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then \
      >&2 echo 'ERROR: Invalid installer signature' &&\
      rm composer-setup.php &&\
      exit 1; \
    fi &&\
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer --quiet &&\
    rm composer-setup.php

# Install official mediawiki extensions
RUN \
    # VisualEditor
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/VisualEditor \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/VisualEditor &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/VisualEditor &&\
    # TemplateData
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/TemplateData \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/TemplateData &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/TemplateData &&\
    # TwoColConflict
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/TwoColConflict \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/TwoColConflict &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/TwoColConflict &&\
    # RevisionSlider
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/RevisionSlider \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/RevisionSlider &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/RevisionSlider &&\
    # Echo
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Echo \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/Echo &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/Echo &&\
    # Thanks
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Thanks \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/Thanks &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/Thanks &&\
    # Flow
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Flow \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/Flow &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/Flow &&\
    # Scribunto
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Scribunto \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/Scribunto &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/Scribunto &&\
    # TemplateStyles
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/TemplateStyles \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/TemplateStyles &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/TemplateStyles &&\
    # Disambiguator
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Disambiguator \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/Disambiguator &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/Disambiguator &&\
    # CreateUserPage
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/CreateUserPage \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/CreateUserPage &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/CreateUserPage &&\
    # AbuseFilter
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/AbuseFilter \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/AbuseFilter &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/AbuseFilter &&\
    # CheckUser
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/CheckUser \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/CheckUser &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/CheckUser &&\
    # UserMerge
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/UserMerge \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/UserMerge &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/UserMerge &&\
    # Widgets
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Widgets \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/Widgets &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/Widgets &&\
    # CodeMirror
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/CodeMirror \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/CodeMirror &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/CodeMirror &&\
    # CharInsert
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/CharInsert \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/CharInsert &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/CharInsert &&\
    # Description2
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Description2 \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/Description2 &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/Description2 &&\
    # OpenGraphMeta
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/OpenGraphMeta \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/OpenGraphMeta &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/OpenGraphMeta &&\
    # PageImages
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/PageImages \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/PageImages &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/PageImages &&\
    # Josa
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Josa \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/Josa &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/Josa &&\
    # BetaFeatures
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/BetaFeatures \
      -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/BetaFeatures &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/BetaFeatures &&\
    echo 'Installed all official extensions'

# Install third-party mediawiki extensions
RUN \
    # AWS (v0.9.0)
    git clone --recurse-submodules --depth 1 https://github.com/edwardspec/mediawiki-aws-s3.git \
      -b v0.9.0 /srv/femiwiki.com/extensions/AWS &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/AWS &&\
    # EmbedVideo
    wget -nv https://github.com/HydraWiki/mediawiki-embedvideo/archive/v2.7.4.tar.gz &&\
    mkdir -p /srv/femiwiki.com/extensions/EmbedVideo &&\
    tar -xzf v2.7.4.tar.gz --strip-components=1 --directory /srv/femiwiki.com/extensions/EmbedVideo &&\
    rm v2.7.4.tar.gz &&\
    # SimpleMathJax
    wget -nv https://github.com/jmnote/SimpleMathJax/archive/v0.7.3.tar.gz &&\
    mkdir -p /srv/femiwiki.com/extensions/SimpleMathJax &&\
    tar -xzf v0.7.3.tar.gz --strip-components=1 --directory /srv/femiwiki.com/extensions/SimpleMathJax &&\
    rm v0.7.3.tar.gz &&\
    # Sanctions
    wget -nv https://github.com/femiwiki/sanctions/archive/master.tar.gz &&\
    mkdir -p /srv/femiwiki.com/extensions/Sanctions &&\
    tar -xzf master.tar.gz --strip-components=1 --directory /srv/femiwiki.com/extensions/Sanctions &&\
    rm master.tar.gz &&\
    # CategoryIntersectionSearch
    wget -nv https://github.com/femiwiki/categoryIntersectionSearch/archive/master.tar.gz &&\
    mkdir -p /srv/femiwiki.com/extensions/CategoryIntersectionSearch &&\
    tar -xzf master.tar.gz --strip-components=1 --directory /srv/femiwiki.com/extensions/CategoryIntersectionSearch &&\
    rm master.tar.gz &&\
    # FacetedCategory
    wget -nv https://github.com/femiwiki/facetedCategory/archive/master.tar.gz &&\
    mkdir -p /srv/femiwiki.com/extensions/FacetedCategory &&\
    tar -xzf master.tar.gz --strip-components=1 --directory /srv/femiwiki.com/extensions/FacetedCategory &&\
    rm master.tar.gz &&\
    # UnifiedExtensionForFemiwiki
    wget -nv https://github.com/femiwiki/unifiedExtensionForFemiwiki/archive/master.tar.gz &&\
    mkdir -p /srv/femiwiki.com/extensions/UnifiedExtensionForFemiwiki &&\
    tar -xzf master.tar.gz --strip-components=1 --directory /srv/femiwiki.com/extensions/UnifiedExtensionForFemiwiki &&\
    rm master.tar.gz &&\
    # HTMLTags
    wget -nv https://extdist.wmflabs.org/dist/extensions/HTMLTags-REL1_31-b7377b0.tar.gz &&\
    tar -xzf HTMLTags-REL1_31-b7377b0.tar.gz -C /srv/femiwiki.com/extensions &&\
    rm HTMLTags-REL1_31-b7377b0.tar.gz &&\
    echo 'Installed all third-party extensions'

# Install femiwiki skin
RUN mkdir -p /srv/femiwiki.com/skins/Femiwiki /srv/femiwiki.com/extensions &&\
    wget -nv https://github.com/femiwiki/skin/archive/master.tar.gz &&\
    tar -xzf master.tar.gz --strip-components=1 --directory /srv/femiwiki.com/skins/Femiwiki &&\
    rm master.tar.gz

# Remove composer
RUN rm /usr/local/bin/composer

# Create a cache directory
RUN sudo -u www-data mkdir -p /tmp/cache
# PHP process should be able to access source code of femiwiki
RUN chown -R www-data:www-data /srv/femiwiki.com
# PHP process should be able to write 'extensions/Widgets/compiled_templates' directory
# Required by 'Widgets' extension
#
# Reference: https://www.mediawiki.org/wiki/Extension:Widgets
RUN chmod o+w /srv/femiwiki.com/extensions/Widgets/compiled_templates


#
# Install and register cron
#
COPY cron/crontab /tmp/crontab
RUN crontab /tmp/crontab && rm /tmp/crontab

# Install 'generate-sitemap' script
RUN sudo -u www-data mkdir -p /srv/femiwiki.com/sitemap
COPY cron/generate-sitemap /usr/local/bin/generate-sitemap


#
# Tini
#
# See https://github.com/krallin/tini for the further details
ENV TINI_VERSION v0.18.0
ADD https://github.com/krallin/tini/releases/download/${TINI_VERSION}/tini /tini
RUN chmod +x /tini
ENTRYPOINT ["/tini", "--"]


# Store femiwiki resources
COPY resources /srv/femiwiki.com/
# Copy LocalSettings.php file
COPY configs/LocalSettings.php configs/secret.php /tmp/

WORKDIR /srv/femiwiki.com
EXPOSE 9000
CMD php /srv/femiwiki.com/maintenance/install.php \
      --scriptpath "/w" \
      --dbtype mysql --dbserver "${DB:-localhost}" --dbname femiwiki --dbuser root \
      --dbpass "${DB_PW:-root}" --installdbuser root --installdbpass "${DB_PW:-root}" \
      --server "${PROTOCOL:-https}://${HOST:-femiwiki.com}" --lang ko --pass root "페미위키" Admin &&\
    # Overwrite LocalSettings.php generated by install script
    mv /tmp/LocalSettings.php /srv/femiwiki.com/LocalSettings.php &&\
    sed -i 's/PROTOCOL/'"${PROTOCOL:-https}"'/' /srv/femiwiki.com/LocalSettings.php &&\
    sed -i 's/HOST/'"${HOST:-femiwiki.com}"'/' /srv/femiwiki.com/LocalSettings.php &&\
    sed -i 's/PARSOID/'"${PARSOID:-parsoid.femiwiki.com}"'/' /srv/femiwiki.com/LocalSettings.php &&\
    # Run update script
    /srv/femiwiki.com/maintenance/update.php --quick &&\
    # Run cron
    cron &&\
    # Run php-fpm
    php-fpm
