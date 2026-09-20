# 파일 목록:
#   /usr/local/etc/php     PHP 설정
#   /srv/femiwiki.com      미디어위키 소스코드 및 확장들
#   /usr/local/{bin,sbin}  임의로 설치한 실행파일들
#   /tmp/cache             캐시 디렉토리
#   /var/log/cron.log      크론 로그
#   /tini                  tini
#
FROM --platform=$TARGETPLATFORM ghcr.io/femiwiki/caddy:1.5.0 AS caddy

FROM --platform=linux/amd64 ghcr.io/femiwiki/femiwiki-extensions:2.4.3 AS extensions

FROM --platform=$TARGETPLATFORM ghcr.io/femiwiki/mediawiki:3.4.4

# Set timezone
ENV TZ=Asia/Seoul
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Install Mediawiki and extensions
RUN mv /mediawiki /srv/femiwiki.com/ &&\
    rm -rf /srv/femiwiki.com/extensions &&\
    rm -rf /srv/femiwiki.com/skins
COPY --from=extensions /mediawiki/extensions /srv/femiwiki.com/extensions
COPY --from=extensions /mediawiki/skins /srv/femiwiki.com/skins

# Install Caddy
COPY --from=caddy /usr/bin/caddy /usr/bin/caddy

RUN mkdir -p \
      /config/caddy \
      /data/caddy \
      /etc/caddy \
      /usr/share/caddy

WORKDIR /srv/femiwiki.com

COPY composer.local.json /srv/femiwiki.com

RUN COMPOSER_HOME=/composer /usr/bin/composer update --no-dev --working-dir /srv/femiwiki.com

# https://phabricator.wikimedia.org/T363063
RUN COMPOSER_HOME=/composer /usr/bin/composer update --no-dev --working-dir /srv/femiwiki.com/extensions/TemplateStyles

# Web server should be able to write 'extensions/Widgets/compiled_templates'
# directory Required by 'Widgets' extension
# Reference: https://www.mediawiki.org/wiki/Extension:Widgets
RUN chmod o+w /srv/femiwiki.com/extensions/Widgets/compiled_templates

# Ship femiwiki resources
COPY --chown=www-data:www-data ["resources", "Caddyfile", "robots.txt", "/srv/femiwiki.com/"]
COPY 429.php /a/
RUN php /a/429.php /srv/femiwiki.com /srv/femiwiki.com
COPY --chown=www-data:www-data ["site-list.xml", "Hotfix.php", "/a/"]

COPY --chown=www-data LocalSettings.php /a/

# php-fpm liveness probe, from Wikimedia's production images. Pinned to the
# same commit the deploy already uses, so baking it in changes no behaviour -
# bumping it upstream is a separate, visible change.
#
# It is baked rather than fetched at runtime because the container healthcheck
# currently curls these from raw.githubusercontent.com with retries=0, so a
# fresh container restart-loops whenever that fetch fails.
ARG WMF_PRODUCTION_IMAGES=ad68c7cb62e4e01436ab3a34fb961fe8034c2cce
RUN mkdir -p /srv/fcgi-check &&\
    BASE="https://github.com/wikimedia/operations-docker-images-production-images/raw/${WMF_PRODUCTION_IMAGES}/images/php/common/fpm/live-test" &&\
    curl -fSL "$BASE/AdoyFastCgiClient.php" -o /srv/fcgi-check/AdoyFastCgiClient.php &&\
    curl -fSL "$BASE/fcgi-probe.php" -o /srv/fcgi-check/fcgi-probe.php

# Reads secrets from SSM Parameter Store at start when SSM_SECRETS is set; see run.
ARG TARGETARCH
ARG CHAMBER_VERSION=v3.1.5
RUN BASE="https://github.com/segmentio/chamber/releases/download/${CHAMBER_VERSION}" &&\
    curl -fSL "$BASE/chamber-${CHAMBER_VERSION}-linux-${TARGETARCH}" -o "/tmp/chamber-${CHAMBER_VERSION}-linux-${TARGETARCH}" &&\
    curl -fSL "$BASE/chamber-${CHAMBER_VERSION}.sha256sums" -o /tmp/chamber.sha256sums &&\
    grep "linux-${TARGETARCH}" /tmp/chamber.sha256sums | sed 's|  chamber-|  /tmp/chamber-|' | sha256sum -c - &&\
    install -m 0755 "/tmp/chamber-${CHAMBER_VERSION}-linux-${TARGETARCH}" /usr/local/bin/chamber &&\
    rm "/tmp/chamber-${CHAMBER_VERSION}-linux-${TARGETARCH}" /tmp/chamber.sha256sums

# Overrides the official image's zz-docker.conf so a replacement container can
# listen on the other port while the old one drains; see run.
COPY zzz-listen.conf /usr/local/etc/php-fpm.d/
ENV PHP_FPM_LISTEN=9000

EXPOSE 80
EXPOSE 443
EXPOSE 9000

COPY ["run", "prerun", "postrun", "caddy-run", "/usr/local/bin/"]
COPY caddy-warmup.php /a/
CMD ["/usr/local/bin/run"]
