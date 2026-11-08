# Composer 이미지는 PHP 버전을 따로 설정할 수 없어 스테이지를 생성해 /usr/bin/composer만 복사해
# 사용하여야 합니다.
# See 'PHP version & extensions' section on https://hub.docker.com/_/composer for more details.
FROM composer:2.10.3 AS composer

FROM php:8.3.36-fpm-bookworm

# Install dependencies and utilities
RUN apt-get update && apt-get install -y \
      # Build dependencies
      build-essential \
      libicu-dev \
      libgmp-dev \
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

# Share card fonts for UnifiedExtensionForFemiwiki; Noto CJK's sans faces stay
# only for the Hanja that Pretendard lacks
ADD --checksum=sha256:3ffbacde6ab8411f1d2db54bb9b1f0b3ee2a738932033722cf0388c06aed1c93 \
    https://raw.githubusercontent.com/orioncactus/pretendard/v1.3.9/packages/pretendard/dist/public/static/Pretendard-Regular.otf /usr/local/share/fonts/pretendard/
ADD --checksum=sha256:2e91915fab54df71cc9598ebf608b2bdb54c6fe3c066ac61dff0bc44fca71cc7 \
    https://raw.githubusercontent.com/orioncactus/pretendard/v1.3.9/packages/pretendard/dist/public/static/Pretendard-Bold.otf /usr/local/share/fonts/pretendard/
ADD --checksum=sha256:d31ddd9f2bed32fd7e302a205cf2380ba0de6529152d239ef99cfb6f261bfc04 \
    https://raw.githubusercontent.com/orioncactus/pretendard/v1.3.9/LICENSE /usr/local/share/fonts/pretendard/OFL.txt
RUN apt-get install -y fonts-noto-cjk &&\
    rm /usr/share/fonts/opentype/noto/NotoSerifCJK-* &&\
    chmod 0644 /usr/local/share/fonts/pretendard/* &&\
    fc-cache -f

RUN sh -c 'cd /usr/bin; wget -O- https://getmic.ro | GETMICRO_REGISTER=y sh'

# See https://caddyserver.com/docs/conventions#file-locations for details
ENV XDG_CONFIG_HOME /config
ENV XDG_DATA_HOME /data

# Install the PHP extensions we need
RUN docker-php-ext-install -j8 \
    calendar \
    gmp \
    intl \
    mysqli \
    opcache \
    # rebuildLocalisationCache --threads and runJobs --procs fork with it
    pcntl \
    sockets \
;

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions wikidiff2

# With FreeType, so UnifiedExtensionForFemiwiki can measure text for its share cards
RUN install-php-extensions gd

# pecl.php.net times out under load, so each pecl release falls back to the
# same tag on GitHub. The installer builds a GitHub source from its package.xml.

# Required by Scribunto
RUN install-php-extensions luasandbox-4.1.3 ||\
    install-php-extensions wikimedia/mediawiki-php-luasandbox@4.1.3

# The C memcached client. MediaWiki's pure-PHP client spends about 14% of a
# page's CPU reading and writing its sockets. See femiwiki/infra#1073.
RUN install-php-extensions memcached-3.4.0 ||\
    install-php-extensions php-memcached-dev/php-memcached@v3.4.0

# The default object cache
RUN install-php-extensions apcu-5.1.28 ||\
    install-php-extensions krakjoe/apcu@v5.1.28

# MediaWiki parses LocalSettings.yaml on every request; this parses it in
# 0.13 ms instead of Symfony YAML's 0.7 ms. See #1826.
RUN install-php-extensions yaml-2.3.0 ||\
    install-php-extensions php/pecl-file_formats-yaml@2.3.0

# A sampling profiler, so where CPU goes stops being inferred from Caddy's
# wall-clock durations. It costs nothing until MediaWiki is told to use it, and
# it samples on a timer rather than instrumenting calls, which is why it can be
# left in a production image at all. See femiwiki/femiwiki#532.
RUN install-php-extensions excimer-1.2.6 ||\
    install-php-extensions wikimedia/mediawiki-php-excimer@1.2.6

COPY ./php.ini                 /usr/local/etc/php/php.ini
COPY ./opcache-recommended.ini /usr/local/etc/php/conf.d/opcache-recommended.ini
COPY ./php-fpm.conf            /usr/local/etc/php-fpm.conf
COPY ./www.conf                /usr/local/etc/php-fpm.d/www.conf
# The official image's zz-docker.conf sorts after www.conf and would pin the
# listen address to 9000. Its other line, daemonize = no, is in php-fpm.conf.
RUN rm /usr/local/etc/php-fpm.d/zz-docker.conf

# As of PHP 8.3.0, a fallback value can be specified that will be used when the referenced variable
# is not defined. (https://www.php.net/manual/en/configuration.file.php)
ENV PHP_POST_MAX_SIZE=10M \
    PHP_UPLOAD_MAX_FILESIZE=10M \
    PHP_FPM_EMERGENCY_RESTART_THRESHOLD=5 \
    PHP_FPM_EMERGENCY_RESTART_INTERVAL=1m \
    PHP_FPM_PROCESS_CONTROL_TIMEOUT=10s \
    PHP_FPM_LISTEN=9000 \
    PHP_FPM_PM_MAX_CHILDREN=20 \
    PHP_FPM_PM_START_SERVERS=2 \
    PHP_FPM_PM_MIN_SPARE_SERVERS=1 \
    PHP_FPM_PM_MAX_SPARE_SERVERS=3 \
    PHP_FPM_PM_MAX_REQUESTS=200 \
    PHP_FPM_REQUEST_TERMINATE_TIMEOUT=30

# The containers run under Docker's own tini (`init = true` in femiwiki/infra)
ENTRYPOINT []

# Remove packages which is not needed anymore (build dependencies of PHP extensions)
ONBUILD RUN apt-get autoremove -y --purge \
    build-essential \
    libicu-dev \
    libgmp-dev \
;

# Prepare PHP log
RUN touch /var/log/php-fpm.log &&\
    chown www-data:www-data /var/log/php-fpm.log

# Install Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer
# Cache directory for composer
RUN mkdir -p /composer
