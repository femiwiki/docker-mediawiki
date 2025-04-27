# 파일 목록:
#   /usr/local/etc/php     PHP 설정
#   /srv/femiwiki.com      미디어위키 소스코드 및 확장들
#   /usr/local/{bin,sbin}  임의로 설치한 실행파일들
#   /tmp/cache             캐시 디렉토리
#   /var/log/cron.log      크론 로그
#   /tini                  tini
#
FROM --platform=$TARGETPLATFORM ghcr.io/femiwiki/caddy:1.3.3 AS caddy

FROM --platform=linux/amd64 ghcr.io/femiwiki/femiwiki-extensions:2.2.1 AS extensions

FROM --platform=$TARGETPLATFORM ghcr.io/femiwiki/mediawiki:3.4.0

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
COPY --chown=www-data:www-data resources /srv/femiwiki.com/
COPY --chown=www-data:www-data ["site-list.xml", "Hotfix.php", "/a/"]

COPY --chown=www-data LocalSettings.php /a/

EXPOSE 80
EXPOSE 443
EXPOSE 9000

COPY ["run", "prerun", "postrun", "/usr/local/bin/"]
CMD ["/usr/local/bin/run"]
