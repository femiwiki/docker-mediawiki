ARG MEDIAWIKI_VERSION=1.36.2
ARG CADDY_MWCACHE_COMMIT=8322c2622509823908230c93ec3ba092d81e5015

ARG TINI_VERSION=0.18.0

#
# 미디어위키 확장 설치 스테이지. 루비 스크립트를 이용해 수많은 미디어위키
# 확장들을 병렬로 빠르게 미리 다운받아 놓는다.
#
FROM --platform=$TARGETPLATFORM ruby:3.0.2-alpine AS base-extension

# ARG instructions without a value inside of a build stage to use the default
# value of an ARG declared before the first FROM use
ARG MEDIAWIKI_VERSION

# aria2
#
# References:
#   https://aria2.github.io/
RUN apk update && apk add \
      aria2

# Install aria2.conf
COPY extension-installer/aria2.conf /root/.config/aria2/aria2.conf

RUN mkdir -p /tmp/mediawiki/

# Extensions and skins setup
COPY extension-installer/* /tmp/
RUN bundle config set deployment 'true' &&\
    bundle config set path '/var/www/.gem' &&\
    bundle install --gemfile /tmp/Gemfile
RUN MEDIAWIKI_BRANCH="REL$(echo $MEDIAWIKI_VERSION | cut -d. -f-2 | sed 's/\./_/g')" &&\
    GEM_HOME=/var/www/.gem/ruby/3.0.0 ruby /tmp/install_extensions.rb "${MEDIAWIKI_BRANCH}"

#
# 미디어위키 다운로드와 Composer 스테이지. 다운받은 확장기능에 더해 미디어위키를 추가로 받고
# Composer로 디펜던시들을 설치한다.
#
FROM --platform=$TARGETPLATFORM composer:2.1.11 AS base-mediawiki

ARG MEDIAWIKI_VERSION

# Install dependencies and utilities
RUN apk add \
      # Build dependencies
      icu-dev

# Install the PHP extensions we need
RUN docker-php-ext-install -j8 intl

COPY --from=base-extension /tmp/mediawiki /tmp/mediawiki

# Create a cache directory for composer
RUN mkdir -p /tmp/composer

# MediaWiki setup
COPY configs/composer.local.json /tmp/mediawiki/
RUN MEDIAWIKI_MAJOR_VERSION="$(echo $MEDIAWIKI_VERSION | cut -d. -f-2)" &&\
    curl -fSL "https://releases.wikimedia.org/mediawiki/${MEDIAWIKI_MAJOR_VERSION}/mediawiki-core-${MEDIAWIKI_VERSION}.tar.gz" -o mediawiki.tar.gz &&\
    tar -xzf mediawiki.tar.gz --strip-components=1 --directory /tmp/mediawiki/ &&\
    rm mediawiki.tar.gz
RUN COMPOSER_HOME=/tmp/composer composer update --no-dev --working-dir '/tmp/mediawiki'

#
# Caddy 스테이지. Route53와 caddy-mwcache 패키지를 설치한 Caddy를 빌드한다.
#
FROM --platform=$TARGETPLATFORM caddy:2.4.5-builder AS caddy
ARG CADDY_MWCACHE_COMMIT

RUN xcaddy build \
      --with github.com/caddy-dns/route53 \
      --with "github.com/femiwiki/caddy-mwcache@${CADDY_MWCACHE_COMMIT}"

#
# 미디어위키 도커이미지 생성 스테이지. 미디어위키 실행에 필요한 각종 PHP
# 디펜던시들을 설치한다.
#
# 파일 목록:
#   /usr/local/etc/php     PHP 설정
#   /srv/femiwiki.com      미디어위키 소스코드 및 확장들
#   /usr/local/{bin,sbin}  임의로 설치한 실행파일들
#   /tmp/cache             캐시 디렉토리
#   /var/log/cron.log      크론 로그
#   /tini                  tini
#
FROM --platform=$TARGETPLATFORM php:7.4.25-fpm
ARG TARGETPLATFORM
ARG TINI_VERSION

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
      # Required for Scribunto when the machine is on aarch64 architecture
      # Only 5.1.x is supported
      #   Reference: https://www.mediawiki.org/wiki/Extension:Scribunto#Additional_binaries
      lua5.1 \
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
RUN docker-php-ext-install -j8 mysqli opcache intl sockets

# Install the default object cache
RUN pecl channel-update pecl.php.net
RUN pecl install apcu
RUN docker-php-ext-enable apcu

#
# Tini
#
# See https://github.com/krallin/tini for the further details
RUN PLATFORM="$(echo $TARGETPLATFORM | cut -d/ -f2)" &&\
    curl -sLfo /tini "https://github.com/krallin/tini/releases/download/v${TINI_VERSION}/tini-${PLATFORM}"
RUN chmod +x /tini
ENTRYPOINT ["/tini", "--"]

# Remove packages which is not needed anymore (build dependencies of PHP extensions)
ONBUILD RUN apt-get autoremove -y --purge \
              build-essential \
              libicu-dev

# Set timezone
ENV TZ=Asia/Seoul
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Prepare PHP log
RUN touch /var/log/php-fpm.log &&\
    chown www-data:www-data /var/log/php-fpm.log

# Install Mediawiki and extensions
COPY --from=base-mediawiki --chown=www-data /tmp/mediawiki /srv/femiwiki.com
# TODO Check whether the next line is useful whenever bumping MediaWiki versions
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

# Web server should be able to execute lua binary
# Reference: https://www.mediawiki.org/wiki/Extension:Scribunto#Additional_binaries
RUN chmod o+x /usr/bin/lua


#
# Install and register cron
#
COPY cron/crontab /tmp/crontab
RUN crontab /tmp/crontab && rm /tmp/crontab

# Install scripts
RUN sudo -u www-data mkdir -p /srv/femiwiki.com/sitemap
COPY cron/generate-sitemap \
      cron/localisation-update \
      cron/update-special-pages \
      cron/run-jobs \
      /usr/local/bin/

# Ship femiwiki resources
COPY --chown=www-data:www-data resources /srv/femiwiki.com/

# Ship femiwiki-specific mediawiki configurations
COPY --chown=www-data [ "configs/LocalSettings.php", "configs/Hotfix.php", "configs/site-list.xml", "/a/" ]
# secret.php should be mounted to '/a/secret.php'
VOLUME /a

WORKDIR /srv/femiwiki.com

EXPOSE 80
EXPOSE 443
EXPOSE 9000

COPY run /usr/local/bin/
CMD ["/usr/local/bin/run"]
