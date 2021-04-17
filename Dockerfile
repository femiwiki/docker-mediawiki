#
# 미디어위키 및 확장 설치 스테이지. 루비 스크립트를 이용해 수많은 미디어위키
# 확장들을 병렬로 빠르게 미리 다운받아 놓는다.
#
FROM ruby:3.0.1 AS base-extension

ARG MEDIAWIKI_VERSION=1.35.2
ARG COMPOSER_VERSION=2.0.12

# Install composer, aria2, sudo and preload configuration file of
# aria2
#
# References:
#   https://getcomposer.org/
#   https://aria2.github.io/
RUN apt-get update && apt-get install -y \
      # Required for composer
      php7.3-cli \
      php7.3-mbstring \
      # Required for aws-sdk-php
      php7.3-simplexml \
      # Install other CLI utilities
      aria2 \
      sudo

# Install Composer
RUN EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)" &&\
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" &&\
    ACTUAL_SIGNATURE="$(php -r "echo hash_file('SHA384', 'composer-setup.php');")" &&\
    if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then \
      >&2 echo 'ERROR: Invalid installer signature' &&\
      rm composer-setup.php &&\
      exit 1; \
    fi &&\
    php composer-setup.php --version "${COMPOSER_VERSION}" --install-dir=/usr/local/bin --filename=composer --quiet

# Create a cache directory for composer
RUN sudo -u www-data mkdir -p /tmp/composer

# Install aria2.conf
COPY extension-installer/aria2.conf /root/.config/aria2/aria2.conf

RUN mkdir -p /tmp/mediawiki/ &&\
    chown www-data:www-data /tmp/mediawiki/

# Extensions and skins setup
COPY extension-installer/* /tmp/
RUN bundle install --deployment --gemfile /tmp/Gemfile --path /var/www/.gem
RUN export MEDIAWIKI_BRANCH="REL$(echo $MEDIAWIKI_VERSION | cut -d. -f-2 | sed 's/\./_/g')" &&\
    sudo -u www-data ruby /tmp/install_extensions.rb "${MEDIAWIKI_BRANCH}"

# MediaWiki setup
COPY --chown=www-data configs/composer.local.json /tmp/mediawiki/
RUN export MEDIAWIKI_MAJOR_VERSION="$(echo $MEDIAWIKI_VERSION | cut -d. -f-2)" &&\
    curl -fSL "https://releases.wikimedia.org/mediawiki/${MEDIAWIKI_MAJOR_VERSION}/mediawiki-core-${MEDIAWIKI_VERSION}.tar.gz" -o mediawiki.tar.gz &&\
    sudo -u www-data tar -xzf mediawiki.tar.gz --strip-components=1 --directory /tmp/mediawiki/ &&\
    rm mediawiki.tar.gz
RUN sudo -u www-data COMPOSER_HOME=/tmp/composer composer update --no-dev --working-dir '/tmp/mediawiki'

#
# Caddy에 Route53 패키지를 설치한다.
#
FROM caddy:2.3.0-builder AS caddy

RUN xcaddy build \
      --with github.com/caddy-dns/route53 \
      --with github.com/femiwiki/caddy-mwcache@v0.0.1

#
# 미디어위키 도커이미지 생성 스테이지. 미디어위키 실행에 필요한 각종 PHP
# 디펜던시들을 설치한다.
#
# 파일 목록:
#   /usr/local/etc/php     PHP 설정
#   /srv/femiwiki.com      미디어위키 소스코드 및 확장들
#   /usr/local/{bin,sbin}  임의로 설치한 실행파일들
#   /tmp/cache             캐시 디렉토리
#   /tmp/log/cron          크론 로그
#   /tini                  tini
#
FROM php:7.4.16-fpm

# Install dependencies and utilities
RUN apt-get update && apt-get install -y \
      # Build dependencies
      build-essential \
      libicu-dev \
      # Runtime depenencies
      imagemagick \
      librsvg2-bin \
      # See https://github.com/femiwiki/docker-mediawiki/issues/442
      git \
      # Required for SyntaxHighlighting
      python3 \
      # CLI utilities
      cron \
      sudo

# Install Caddy
COPY --from=caddy /usr/bin/caddy /usr/bin/caddy

RUN mkdir -p \
      /config/caddy \
      /data/caddy \
      /etc/caddy \
      /usr/share/caddy

# See https://caddyserver.com/docs/conventions#file-locations for details
ENV XDG_CONFIG_HOME /config
ENV XDG_DATA_HOME /data

# Install the PHP extensions we need
RUN docker-php-ext-install -j8 mysqli opcache intl

# Install the default object cache
RUN pecl channel-update pecl.php.net
RUN pecl install apcu
RUN docker-php-ext-enable apcu

#
# Tini
#
# See https://github.com/krallin/tini for the further details
ARG TINI_VERSION=v0.18.0
ADD https://github.com/krallin/tini/releases/download/${TINI_VERSION}/tini /tini
RUN chmod +x /tini
ENTRYPOINT ["/tini", "--"]

# Remove packages which is not needed anymore (build dependencies of PHP extensions)
ONBUILD RUN apt-get autoremove -y --purge \
              build-essential \
              libicu-dev

# Set timezone
ENV TZ=Asia/Seoul
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Configure PHP
COPY php/php.ini /usr/local/etc/php/php.ini
COPY php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY php/opcache-recommended.ini /usr/local/etc/php/conf.d/opcache-recommended.ini

# Install Mediawiki and extensions
COPY --from=base-extension --chown=www-data /tmp/mediawiki /srv/femiwiki.com
# TODO Check the next line is valid when bump MediaWiki version
# TODO Remove the next line in MW 1.36
# Fix https://phabricator.wikimedia.org/T264735
RUN sed -i 's/$pipelining ? 3 : 0/CURLPIPE_MULTIPLEX/' /srv/femiwiki.com/includes/libs/http/MultiHttpClient.php

# Create cache directories for mediawiki
# $wgCacheDirectory should not be accessible from the web and writable by the web server
# See https://www.mediawiki.org/wiki/Manual:$wgCacheDirectory for details
RUN sudo -u www-data mkdir -p /tmp/file-cache /tmp/cache

# Web server should be able to write 'extensions/Widgets/compiled_templates'
# directory Required by 'Widgets' extension
# Reference: https://www.mediawiki.org/wiki/Extension:Widgets
RUN chmod o+w /srv/femiwiki.com/extensions/Widgets/compiled_templates

# Web server should be able to READ 'extensions/FlaggedRevs/frontend/modules'
# directory Required by 'FlaggedRevs' extension
# Reference: https://www.mediawiki.org/wiki/Extension:FlaggedRevs
RUN chmod o+r /srv/femiwiki.com/extensions/FlaggedRevs/frontend/modules


#
# Install and register cron
#
COPY cron/crontab /tmp/crontab
RUN crontab /tmp/crontab && rm /tmp/crontab

# Install 'generate-sitemap' script
RUN sudo -u www-data mkdir -p /srv/femiwiki.com/sitemap
COPY cron/generate-sitemap /usr/local/bin/generate-sitemap

# Install 'localisation-update' script
COPY cron/localisation-update /usr/local/bin/localisation-update

# Store femiwiki resources
COPY --chown=www-data:www-data resources /srv/femiwiki.com/

# Store femiwiki-specific mediawiki configurations
COPY --chown=www-data [ "configs/LocalSettings.php", "configs/site-list.xml", "/config/mediawiki/" ]
# secret.php should be mounted to '/a/secret.php'
VOLUME /a

WORKDIR /srv/femiwiki.com

# Copy Caddyfile for web server usage. See README.md for detail.
COPY caddy/Caddyfile.prod /srv/femiwiki.com/Caddyfile

EXPOSE 80
EXPOSE 443
EXPOSE 9000

COPY run /usr/local/bin/
CMD ["/usr/local/bin/run"]
