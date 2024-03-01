FROM --platform=$TARGETPLATFORM php:8.1.22-fpm
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
      # Required for Scribunto when the machine is on aarch64 architecture
      # Only 5.1.x is supported
      #   Reference: https://www.mediawiki.org/wiki/Extension:Scribunto#Additional_binaries
      lua5.1 \
      # CLI utilities
      cron \
      sudo

# See https://caddyserver.com/docs/conventions#file-locations for details
ENV XDG_CONFIG_HOME /config
ENV XDG_DATA_HOME /data

# Install the PHP extensions we need
RUN docker-php-ext-install -j8 \
    mysqli \
    opcache \
    intl \
    sockets \
    calendar

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
