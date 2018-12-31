# 파일 목록
#
# /usr/local/etc/php     : PHP 설정
# /srv/femiwiki.com      : 미디어위키 소스코드 및 확장들
# /usr/local/bin         : 임의로 설치한 실행파일들
# /tmp/cache             : 캐시 디렉토리
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
      # Build dependencies
      build-essential \
      libicu-dev \
      aria2 \
      # Composer dependencies
      git \
      wget \
      unzip \
      # Runtime depenencies
      imagemagick \
      librsvg2-bin \
      # Required for SyntaxHighlighting
      python3 \
      # Required utilities
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
    chown www-data:www-data /srv/femiwiki.com/ &&\
    sudo -u www-data tar -xzf mediawiki.tar.gz --strip-components=1 --directory /srv/femiwiki.com/ &&\
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

# Change user
USER www-data
# '/var/www/.composer' is not writable for www-data. Change $COMPOSER_HOME
ENV COMPOSER_HOME=/tmp/composer

# Download official mediawiki extensions having no submodule using aria2
RUN { \
      # TemplateData
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/TemplateData/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=TemplateData.tar.gz"; \
      # TwoColConflict
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/TwoColConflict/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=TwoColConflict.tar.gz"; \
      # RevisionSlider
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/RevisionSlider/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=RevisionSlider.tar.gz"; \
      # Echo
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/Echo/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=Echo.tar.gz"; \
      # Thanks
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/Thanks/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=Thanks.tar.gz"; \
      # Flow
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/Flow/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=Flow.tar.gz"; \
      # Scribunto
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/Scribunto/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=Scribunto.tar.gz"; \
      # TemplateStyles
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/TemplateStyles/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=TemplateStyles.tar.gz"; \
      # Disambiguator
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/Disambiguator/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=Disambiguator.tar.gz"; \
      # CreateUserPage
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/CreateUserPage/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=CreateUserPage.tar.gz"; \
      # AbuseFilter
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/AbuseFilter/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=AbuseFilter.tar.gz"; \
      # CheckUser
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/CheckUser/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=CheckUser.tar.gz"; \
      # UserMerge
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/UserMerge/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=UserMerge.tar.gz"; \
      # CodeMirror
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/CodeMirror/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=CodeMirror.tar.gz"; \
      # CharInsert
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/CharInsert/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=CharInsert.tar.gz"; \
      # Description2
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/Description2/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=Description2.tar.gz"; \
      # OpenGraphMeta
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/OpenGraphMeta/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=OpenGraphMeta.tar.gz"; \
      # PageImages
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/PageImages/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=PageImages.tar.gz"; \
      # Josa
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/Josa/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=Josa.tar.gz"; \
      # HTMLTags
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/HTMLTags/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=HTMLTags.tar.gz"; \
      # BetaFeatures
      echo "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/BetaFeatures/+archive/${MEDIAWIKI_BRANCH}.tar.gz"; \
      echo "  out=BetaFeatures.tar.gz"; \
    } | aria2c --input-file=- --dir=/tmp

# Install official mediawiki extensions having no submodule
RUN \
    # TemplateData
    mkdir -p /srv/femiwiki.com/extensions/TemplateData &&\
    tar -xzf /tmp/TemplateData.tar.gz --directory /srv/femiwiki.com/extensions/TemplateData &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/TemplateData &&\
    rm /tmp/TemplateData.tar.gz &&\
    # TwoColConflict
    mkdir -p /srv/femiwiki.com/extensions/TwoColConflict &&\
    tar -xzf /tmp/TwoColConflict.tar.gz --directory /srv/femiwiki.com/extensions/TwoColConflict &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/TwoColConflict &&\
    rm /tmp/TwoColConflict.tar.gz &&\
    # RevisionSlider
    mkdir -p /srv/femiwiki.com/extensions/RevisionSlider &&\
    tar -xzf /tmp/RevisionSlider.tar.gz --directory /srv/femiwiki.com/extensions/RevisionSlider &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/RevisionSlider &&\
    rm /tmp/RevisionSlider.tar.gz &&\
    # Echo
    mkdir -p /srv/femiwiki.com/extensions/Echo &&\
    tar -xzf /tmp/Echo.tar.gz --directory /srv/femiwiki.com/extensions/Echo &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/Echo &&\
    rm /tmp/Echo.tar.gz &&\
    # Thanks
    mkdir -p /srv/femiwiki.com/extensions/Thanks &&\
    tar -xzf /tmp/Thanks.tar.gz --directory /srv/femiwiki.com/extensions/Thanks &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/Thanks &&\
    rm /tmp/Thanks.tar.gz &&\
    # Flow
    mkdir -p /srv/femiwiki.com/extensions/Flow &&\
    tar -xzf /tmp/Flow.tar.gz --directory /srv/femiwiki.com/extensions/Flow &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/Flow &&\
    rm /tmp/Flow.tar.gz &&\
    # Scribunto
    mkdir -p /srv/femiwiki.com/extensions/Scribunto &&\
    tar -xzf /tmp/Scribunto.tar.gz --directory /srv/femiwiki.com/extensions/Scribunto &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/Scribunto &&\
    rm /tmp/Scribunto.tar.gz &&\
    # TemplateStyles
    mkdir -p /srv/femiwiki.com/extensions/TemplateStyles &&\
    tar -xzf /tmp/TemplateStyles.tar.gz --directory /srv/femiwiki.com/extensions/TemplateStyles &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/TemplateStyles &&\
    rm /tmp/TemplateStyles.tar.gz &&\
    # Disambiguator
    mkdir -p /srv/femiwiki.com/extensions/Disambiguator &&\
    tar -xzf /tmp/Disambiguator.tar.gz --directory /srv/femiwiki.com/extensions/Disambiguator &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/Disambiguator &&\
    rm /tmp/Disambiguator.tar.gz &&\
    # CreateUserPage
    mkdir -p /srv/femiwiki.com/extensions/CreateUserPage &&\
    tar -xzf /tmp/CreateUserPage.tar.gz --directory /srv/femiwiki.com/extensions/CreateUserPage &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/CreateUserPage &&\
    rm /tmp/CreateUserPage.tar.gz &&\
    # AbuseFilter
    mkdir -p /srv/femiwiki.com/extensions/AbuseFilter &&\
    tar -xzf /tmp/AbuseFilter.tar.gz --directory /srv/femiwiki.com/extensions/AbuseFilter &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/AbuseFilter &&\
    rm /tmp/AbuseFilter.tar.gz &&\
    # CheckUser
    mkdir -p /srv/femiwiki.com/extensions/CheckUser &&\
    tar -xzf /tmp/CheckUser.tar.gz --directory /srv/femiwiki.com/extensions/CheckUser &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/CheckUser &&\
    rm /tmp/CheckUser.tar.gz &&\
    # UserMerge
    mkdir -p /srv/femiwiki.com/extensions/UserMerge &&\
    tar -xzf /tmp/UserMerge.tar.gz --directory /srv/femiwiki.com/extensions/UserMerge &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/UserMerge &&\
    rm /tmp/UserMerge.tar.gz &&\
    # CodeMirror
    mkdir -p /srv/femiwiki.com/extensions/CodeMirror &&\
    tar -xzf /tmp/CodeMirror.tar.gz --directory /srv/femiwiki.com/extensions/CodeMirror &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/CodeMirror &&\
    rm /tmp/CodeMirror.tar.gz &&\
    # CharInsert
    mkdir -p /srv/femiwiki.com/extensions/CharInsert &&\
    tar -xzf /tmp/CharInsert.tar.gz --directory /srv/femiwiki.com/extensions/CharInsert &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/CharInsert &&\
    rm /tmp/CharInsert.tar.gz &&\
    # Description2
    mkdir -p /srv/femiwiki.com/extensions/Description2 &&\
    tar -xzf /tmp/Description2.tar.gz --directory /srv/femiwiki.com/extensions/Description2 &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/Description2 &&\
    rm /tmp/Description2.tar.gz &&\
    # OpenGraphMeta
    mkdir -p /srv/femiwiki.com/extensions/OpenGraphMeta &&\
    tar -xzf /tmp/OpenGraphMeta.tar.gz --directory /srv/femiwiki.com/extensions/OpenGraphMeta &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/OpenGraphMeta &&\
    rm /tmp/OpenGraphMeta.tar.gz &&\
    # PageImages
    mkdir -p /srv/femiwiki.com/extensions/PageImages &&\
    tar -xzf /tmp/PageImages.tar.gz --directory /srv/femiwiki.com/extensions/PageImages &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/PageImages &&\
    rm /tmp/PageImages.tar.gz &&\
    # Josa
    mkdir -p /srv/femiwiki.com/extensions/Josa &&\
    tar -xzf /tmp/Josa.tar.gz --directory /srv/femiwiki.com/extensions/Josa &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/Josa &&\
    rm /tmp/Josa.tar.gz &&\
    # HTMLTags
    mkdir -p /srv/femiwiki.com/extensions/HTMLTags &&\
    tar -xzf /tmp/HTMLTags.tar.gz --directory /srv/femiwiki.com/extensions/HTMLTags &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/HTMLTags &&\
    rm /tmp/HTMLTags.tar.gz &&\
    # BetaFeatures
    mkdir -p /srv/femiwiki.com/extensions/BetaFeatures &&\
    tar -xzf /tmp/BetaFeatures.tar.gz --directory /srv/femiwiki.com/extensions/BetaFeatures &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/BetaFeatures &&\
    rm /tmp/BetaFeatures.tar.gz &&\
    echo 'Installed all official extensions having no submodule'

# Install official mediawiki extensions having submodule
RUN \
    # VisualEditor
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/VisualEditor \
    -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/VisualEditor &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/VisualEditor &&\
    # Widgets
    git clone --recurse-submodules --depth 1 https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Widgets \
    -b "${MEDIAWIKI_BRANCH}" /srv/femiwiki.com/extensions/Widgets &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/Widgets &&\
    echo 'Installed all official extensions having submodule'

# Install third-party mediawiki extensions
RUN \
    # AWS (v0.10.0)
    git clone --depth 1 https://github.com/edwardspec/mediawiki-aws-s3.git \
      -b v0.10.0 /srv/femiwiki.com/extensions/AWS &&\
    composer update --no-dev -d /srv/femiwiki.com/extensions/AWS &&\
    # EmbedVideo
    wget -nv https://github.com/HydraWiki/mediawiki-embedvideo/archive/v2.7.4.tar.gz -O /tmp/tarball.tgz &&\
    mkdir -p /srv/femiwiki.com/extensions/EmbedVideo &&\
    tar -xzf /tmp/tarball.tgz --strip-components=1 --directory /srv/femiwiki.com/extensions/EmbedVideo &&\
    rm /tmp/tarball.tgz &&\
    # SimpleMathJax
    wget -nv https://github.com/jmnote/SimpleMathJax/archive/v0.7.3.tar.gz -O /tmp/tarball.tgz &&\
    mkdir -p /srv/femiwiki.com/extensions/SimpleMathJax &&\
    tar -xzf /tmp/tarball.tgz --strip-components=1 --directory /srv/femiwiki.com/extensions/SimpleMathJax &&\
    rm /tmp/tarball.tgz &&\
    # Sanctions
    wget -nv https://github.com/femiwiki/sanctions/archive/master.tar.gz -O /tmp/tarball.tgz &&\
    mkdir -p /srv/femiwiki.com/extensions/Sanctions &&\
    tar -xzf /tmp/tarball.tgz --strip-components=1 --directory /srv/femiwiki.com/extensions/Sanctions &&\
    rm /tmp/tarball.tgz &&\
    # CategoryIntersectionSearch
    wget -nv https://github.com/femiwiki/categoryIntersectionSearch/archive/master.tar.gz -O /tmp/tarball.tgz &&\
    mkdir -p /srv/femiwiki.com/extensions/CategoryIntersectionSearch &&\
    tar -xzf /tmp/tarball.tgz --strip-components=1 --directory /srv/femiwiki.com/extensions/CategoryIntersectionSearch &&\
    rm /tmp/tarball.tgz &&\
    # FacetedCategory
    wget -nv https://github.com/femiwiki/facetedCategory/archive/master.tar.gz -O /tmp/tarball.tgz &&\
    mkdir -p /srv/femiwiki.com/extensions/FacetedCategory &&\
    tar -xzf /tmp/tarball.tgz --strip-components=1 --directory /srv/femiwiki.com/extensions/FacetedCategory &&\
    rm /tmp/tarball.tgz &&\
    # UnifiedExtensionForFemiwiki
    wget -nv https://github.com/femiwiki/unifiedExtensionForFemiwiki/archive/master.tar.gz -O /tmp/tarball.tgz &&\
    mkdir -p /srv/femiwiki.com/extensions/UnifiedExtensionForFemiwiki &&\
    tar -xzf /tmp/tarball.tgz --strip-components=1 --directory /srv/femiwiki.com/extensions/UnifiedExtensionForFemiwiki &&\
    rm /tmp/tarball.tgz &&\
    echo 'Installed all third-party extensions'

# Install femiwiki skin
RUN \
    wget -nv https://github.com/femiwiki/skin/archive/master.tar.gz -O /tmp/tarball.tgz &&\
    mkdir -p /srv/femiwiki.com/skins/Femiwiki &&\
    tar -xzf /tmp/tarball.tgz --strip-components=1 --directory /srv/femiwiki.com/skins/Femiwiki &&\
    rm /tmp/tarball.tgz

# Create a cache directory
RUN mkdir -p /tmp/cache

USER root

# Remove composer and its caches
RUN rm -rf /usr/local/bin/composer /tmp/composer

# Remove packages which is not needed anymore
RUN apt-get autoremove -y --purge \
      aria2 \
      git \
      wget \
      unzip

# Web server should be able to write 'extensions/Widgets/compiled_templates'
# directory Required by 'Widgets' extension
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
COPY --chown=www-data:www-data resources /srv/femiwiki.com/
# secret.php should be mounted to '/a/secret.php'
VOLUME /a

WORKDIR /srv/femiwiki.com
EXPOSE 9000

COPY run /usr/local/bin/
CMD ["/usr/local/bin/run"]
