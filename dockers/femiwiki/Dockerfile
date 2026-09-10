# 파일 목록:
#   /usr/local/etc/php     PHP 설정
#   /srv/femiwiki.com      미디어위키 소스코드 및 확장들
#   /usr/local/{bin,sbin}  임의로 설치한 실행파일들
#   /tmp/cache             캐시 디렉토리
#   /var/log/cron.log      크론 로그
#   /tini                  tini
#
FROM --platform=$TARGETPLATFORM ghcr.io/femiwiki/caddy:1.4.0 AS caddy

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

EXPOSE 80
EXPOSE 443
EXPOSE 9000

COPY ["run", "prerun", "postrun", "/usr/local/bin/"]
CMD ["/usr/local/bin/run"]
