# Composer 이미지는 PHP 버전을 따로 설정할 수 없어 스테이지를 생성해 /usr/bin/composer만 복사해
# 사용하여야 합니다.
# See 'PHP version & extensions' section on https://hub.docker.com/_/composer for more details.
FROM --platform=$TARGETPLATFORM composer:2.8.5 AS composer

FROM --platform=$TARGETPLATFORM php:8.1.31-fpm
ARG TARGETPLATFORM
ARG TINI_VERSION=0.18.0

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
      sudo \
      # https://getcomposer.org/doc/00-intro.md#system-requirements
      unzip \
      ;

# See https://caddyserver.com/docs/conventions#file-locations for details
ENV XDG_CONFIG_HOME /config
ENV XDG_DATA_HOME /data

# Install the PHP extensions we need
RUN docker-php-ext-install \
    calendar \
    intl \
    mysqli \
    opcache \
    sockets \
    ;

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    # Required by Scribunto
    luasandbox \
    wikidiff2 \
    ;

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

# Prepare PHP log
RUN touch /var/log/php-fpm.log &&\
    chown www-data:www-data /var/log/php-fpm.log

# Install Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer
# Cache directory for composer
RUN mkdir -p /composer
