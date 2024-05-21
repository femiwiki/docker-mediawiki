ARG MEDIAWIKI_VERSION=1.41.0

# Composer 이미지는 PHP 버전을 따로 설정할 수 없어 스테이지를 생성해 /usr/bin/composer만 복사해
# 사용하여야 합니다.
# See 'PHP version & extensions' section on https://hub.docker.com/_/composer for more details.
FROM --platform=$TARGETPLATFORM composer:2.6.6 AS composer

FROM --platform=$TARGETPLATFORM ghcr.io/femiwiki/femiwiki-extensions:1.1.1 AS extensions

FROM --platform=$TARGETPLATFORM php:8.1.22-cli AS base-mediawiki
ARG MEDIAWIKI_VERSION

# Install dependencies and utilities
RUN apt-get update && apt-get install -y \
      libicu-dev \
      # https://getcomposer.org/doc/00-intro.md#system-requirements
      git \
      unzip

# Install the PHP extensions we need
RUN IPE_DEBUG=1 docker-php-ext-install -j8 \
    intl \
    calendar

COPY --from=composer /usr/bin/composer /usr/bin/composer

# Create a cache directory for composer
RUN mkdir -p /composer

COPY --from=extensions /mediawiki /mediawiki

# Download MediaWiki
RUN MEDIAWIKI_MAJOR_VERSION="$(echo $MEDIAWIKI_VERSION | cut -d. -f-2)" &&\
    curl -fSL "https://releases.wikimedia.org/mediawiki/${MEDIAWIKI_MAJOR_VERSION}/mediawiki-core-${MEDIAWIKI_VERSION}.tar.gz" -o mediawiki.tar.gz &&\
    tar -xzf mediawiki.tar.gz --strip-components=1 --directory /mediawiki/ &&\
    rm mediawiki.tar.gz

COPY composer.local.json /mediawiki/

RUN COMPOSER_HOME=/composer /usr/bin/composer update --no-dev --working-dir '/mediawiki'


#
# PHP-FPM Stage
#

FROM --platform=$TARGETPLATFORM ghcr.io/femiwiki/php-fpm:1.0.0

# Install Mediawiki and extensions
COPY --from=base-mediawiki --chown=www-data /mediawiki /mediawiki

# Create cache directories for mediawiki
# $wgCacheDirectory should not be accessible from the web and writable by the web server
# See https://www.mediawiki.org/wiki/Manual:$wgCacheDirectory for details
RUN sudo -u www-data mkdir -p /tmp/file-cache /tmp/cache

# Web server should be able to write 'extensions/Widgets/compiled_templates'
# directory Required by 'Widgets' extension
# Reference: https://www.mediawiki.org/wiki/Extension:Widgets
RUN chmod o+w /mediawiki/extensions/Widgets/compiled_templates

# Web server should be able to execute lua binary
# Reference: https://www.mediawiki.org/wiki/Extension:Scribunto#Additional_binaries
RUN chmod o+x /usr/bin/lua

# Install and register cron
COPY cron/crontab /tmp/crontab
RUN crontab /tmp/crontab && rm /tmp/crontab
COPY cron/generate-sitemap \
      cron/update-special-pages \
      cron/run-jobs \
      /usr/local/bin/

# Install scripts
RUN sudo -u www-data mkdir -p /mediawiki/sitemap

# Ship femiwiki-specific mediawiki configurations
COPY --chown=www-data LocalSettings.php /a/

# secret.php should be mounted to '/a/secret.php'
VOLUME /a
