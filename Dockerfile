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
# Create a cache directory for composer
RUN mkdir -p /tmp/cache

# Install Mediawiki extensions
COPY install_extensions /tmp/
RUN sudo -u www-data /tmp/install_extensions
RUN rm /tmp/install_extensions

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
