# 파일 목록:
#   /usr/local/etc/php     PHP 설정
#   /srv/femiwiki.com      미디어위키 소스코드 및 확장들
#   /usr/local/{bin,sbin}  임의로 설치한 실행파일들
#   /tmp/cache             캐시 디렉토리
#   /var/log/cron.log      크론 로그
#   /tini                  tini
#
FROM --platform=$TARGETPLATFORM ghcr.io/femiwiki/caddy:1.0.0 AS caddy

FROM --platform=$TARGETPLATFORM ghcr.io/femiwiki/mediawiki:1.1.0

# Set timezone
ENV TZ=Asia/Seoul
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Install Caddy
COPY --from=caddy /usr/bin/caddy /usr/bin/caddy

RUN mkdir -p \
      /config/caddy \
      /data/caddy \
      /etc/caddy \
      /usr/share/caddy

# Install MediaWiki
RUN mv /mediawiki /srv/femiwiki.com

WORKDIR /srv/femiwiki.com

# Ship femiwiki resources
COPY --chown=www-data:www-data resources /srv/femiwiki.com/
COPY --chown=www-data:www-data ["site-list.xml", "Hotfix.php", "/a/"]

EXPOSE 80
EXPOSE 443
EXPOSE 9000

COPY run /usr/local/bin/
CMD ["/usr/local/bin/run"]
