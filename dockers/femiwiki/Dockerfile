# PHP 설정은 /usr/local/etc/php, 미디어위키와 확장은 /srv/femiwiki.com, 미디어위키
# 설정은 /etc/mediawiki, 직접 설치한 실행파일은 /usr/local/{bin,sbin}, 그 밖의
# 스크립트는 /usr/local/lib/femiwiki, 시크릿은 /run/secrets, 캐시는 /tmp/cache,
# 지역화 캐시는 /srv/l10n에 있습니다.
FROM --platform=$TARGETPLATFORM ghcr.io/femiwiki/caddy:1.11.3 AS caddy

FROM --platform=linux/amd64 ghcr.io/femiwiki/femiwiki-extensions:3.12.0 AS extensions

FROM --platform=$TARGETPLATFORM ghcr.io/femiwiki/mediawiki:4.0.2

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

# A class map for every package, and nothing looked up outside it, so loading a
# library class does not stat candidate files. Wikimedia's mediawiki/vendor sets
# the same two options; see femiwiki/infra#1073
RUN COMPOSER_HOME=/composer /usr/bin/composer update --no-dev --optimize-autoloader --classmap-authoritative --working-dir /srv/femiwiki.com

# https://phabricator.wikimedia.org/T363063
RUN COMPOSER_HOME=/composer /usr/bin/composer update --no-dev --optimize-autoloader --classmap-authoritative --working-dir /srv/femiwiki.com/extensions/TemplateStyles

# The extensions and skins that ship their own vendor directory register its
# loader in front of every other one, so each class a request loads first went
# through their PSR-4 lookups; Nuke's alone maps all of Wikimedia\. Authoritative
# maps make each of them an array lookup. They were installed without a platform
# check, and OATHAuth's dependencies ask for a newer PHP than this image has, so
# none is written now either.
RUN for d in /srv/femiwiki.com/extensions/*/vendor/composer /srv/femiwiki.com/skins/*/vendor/composer; do \
      COMPOSER_HOME=/composer /usr/bin/composer dump-autoload --no-dev --classmap-authoritative \
        --ignore-platform-reqs --working-dir "${d%/vendor/composer}" || exit 1; \
    done

# The same for the classes extensions declare through AutoloadNamespaces,
# registered by LocalSettings.php for the extensions it loads
COPY autoload-extensions.php /tmp/
RUN mkdir -p /etc/mediawiki && php /tmp/autoload-extensions.php /srv/femiwiki.com /etc/mediawiki/autoload-extensions.php &&\
    rm /tmp/autoload-extensions.php

# Web server should be able to write 'extensions/Widgets/compiled_templates'
# directory Required by 'Widgets' extension
# Reference: https://www.mediawiki.org/wiki/Extension:Widgets
RUN chmod o+w /srv/femiwiki.com/extensions/Widgets/compiled_templates

# Ship femiwiki resources
COPY --chown=www-data:www-data ["resources", "Caddyfile", "robots.txt", "/srv/femiwiki.com/"]
COPY 429.php /tmp/
RUN php /tmp/429.php /srv/femiwiki.com /srv/femiwiki.com && rm /tmp/429.php
COPY --chown=www-data:www-data ["site-list.xml", "Hotfix.php", "/etc/mediawiki/"]

COPY --chown=www-data LocalSettings.php /etc/mediawiki/

# Every language's localisation cache, built once here rather than by the first
# request for it in each container; see LocalSettings.php. Nothing at build time
# can reach memcached or the database, and run purges the message blobs instead.
RUN cp /etc/mediawiki/LocalSettings.php LocalSettings.php &&\
    FW_L10N_BUILD=1 FW_MAIN_CACHE=none FW_PARSER_CACHE=none FW_MESSAGE_CACHE=none \
      FW_SESSION_CACHE=none \
      php maintenance/run.php rebuildLocalisationCache \
      --no-database --skip-message-purge --force --no-progress --threads "$(nproc)" &&\
    rm LocalSettings.php

# php-fpm liveness probe from Wikimedia's production images, baked in rather
# than fetched by the healthcheck, whose failed fetch restart-looped containers.
ARG WMF_PRODUCTION_IMAGES=ad68c7cb62e4e01436ab3a34fb961fe8034c2cce
RUN mkdir -p /srv/fcgi-check &&\
    BASE="https://github.com/wikimedia/operations-docker-images-production-images/raw/${WMF_PRODUCTION_IMAGES}/images/php/common/fpm/live-test" &&\
    curl -fSL "$BASE/AdoyFastCgiClient.php" -o /srv/fcgi-check/AdoyFastCgiClient.php &&\
    curl -fSL "$BASE/fcgi-probe.php" -o /srv/fcgi-check/fcgi-probe.php

# Beside fcgi-probe.php and so outside /srv/femiwiki.com: Caddy resolves paths
# under the site root, which leaves the probe pool as the only caller.
COPY databasez.php databasez-probe.php /srv/fcgi-check/

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
# php-fpm's status page and an opcache report, read through the metrics site in
# the Caddyfile. /srv/metrics is outside the public site's root.
COPY zzz-status.conf /usr/local/etc/php-fpm.d/
COPY metrics /srv/metrics

# A pool of its own for the container health check, so a www pool with every
# worker busy reads as slow rather than as dead; see femiwiki/femiwiki#561.
COPY zzz-probe.conf /usr/local/etc/php-fpm.d/
ENV PHP_FPM_LISTEN=9000 \
    PHP_FPM_PROBE_LISTEN=9100 \
    PHP_FPM_STATUS_LISTEN=9200 \
    FW_PROBE_PORT=8080 \
    FW_METRICS_PORT=9180

EXPOSE 80
EXPOSE 443
EXPOSE 9000

COPY ["run", "prerun", "postrun", "caddy-run", "/usr/local/bin/"]
COPY caddy-warmup.php warm-up.php create-session-schema.php google-subject-token.php /usr/local/lib/femiwiki/

# The paths femiwiki/infra still names, for one image generation; see #1332
RUN mkdir /a && ln -s /etc/mediawiki/LocalSettings.php /etc/mediawiki/Hotfix.php /srv/fcgi-check/databasez-probe.php /a/
CMD ["/usr/local/bin/run"]
