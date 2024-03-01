ARG MEDIAWIKI_VERSION=1.41.0
ARG CADDY_MWCACHE_COMMIT=9e9ac234170400406c6a328bbaaaa4c95e05bd73

FROM --platform=$TARGETPLATFORM ghcr.io/femiwiki/femiwiki-extensions:1.0.0 AS base-extension
#
# Composer 스테이지. Composer 이미지는 PHP 버전을 따로 설정할 수 없어 스테이지를 생성해
# /usr/bin/composer만 복사해 사용하여야 합니다.
# See 'PHP version & extensions' section on https://hub.docker.com/_/composer for more details.
#
FROM --platform=$TARGETPLATFORM composer:2.6.6 AS composer

#
# 미디어위키 다운로드 스테이지. 다운받은 확장기능에 더해 미디어위키를 추가로 받고
# Composer로 디펜던시들을 설치한다.
#
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

COPY --from=base-extension /mediawiki /tmp/mediawiki

# Create a cache directory for composer
RUN mkdir -p /tmp/composer

# MediaWiki setup
RUN MEDIAWIKI_MAJOR_VERSION="$(echo $MEDIAWIKI_VERSION | cut -d. -f-2)" &&\
    curl -fSL "https://releases.wikimedia.org/mediawiki/${MEDIAWIKI_MAJOR_VERSION}/mediawiki-core-${MEDIAWIKI_VERSION}.tar.gz" -o mediawiki.tar.gz &&\
    tar -xzf mediawiki.tar.gz --strip-components=1 --directory /tmp/mediawiki/ &&\
    rm mediawiki.tar.gz
COPY configs/composer.local.json /tmp/mediawiki/

COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN COMPOSER_HOME=/tmp/composer /usr/bin/composer update --no-dev --working-dir '/tmp/mediawiki'

#
# Caddy 스테이지. Route53와 caddy-mwcache 패키지를 설치한 Caddy를 빌드한다.
#
FROM --platform=$TARGETPLATFORM caddy:2-builder AS caddy
ARG CADDY_MWCACHE_COMMIT

RUN XCADDY_DEBUG=1 xcaddy build v2.7.5 \
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
FROM --platform=$TARGETPLATFORM ghcr.io/femiwiki/php:1.0.0
ARG TARGETPLATFORM

# Install Caddy
COPY --from=caddy /usr/bin/caddy /usr/bin/caddy

RUN mkdir -p \
      /config/caddy \
      /data/caddy \
      /etc/caddy \
      /usr/share/caddy

# Set timezone
ENV TZ=Asia/Seoul
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Install Mediawiki and extensions
COPY --from=base-mediawiki --chown=www-data /tmp/mediawiki /srv/femiwiki.com

# Create cache directories for mediawiki
# $wgCacheDirectory should not be accessible from the web and writable by the web server
# See https://www.mediawiki.org/wiki/Manual:$wgCacheDirectory for details
RUN sudo -u www-data mkdir -p /tmp/file-cache /tmp/cache

# Web server should be able to write 'extensions/Widgets/compiled_templates'
# directory Required by 'Widgets' extension
# Reference: https://www.mediawiki.org/wiki/Extension:Widgets
RUN chmod o+w /srv/femiwiki.com/extensions/Widgets/compiled_templates

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
