FROM ghcr.io/femiwiki/php-fpm:2026-11-08T06-37-eeee7021
ARG MEDIAWIKI_VERSION=1.46.2

RUN mkdir /mediawiki/
RUN MEDIAWIKI_MAJOR_VERSION="${MEDIAWIKI_VERSION%.*}" &&\
    curl -fSL "https://releases.wikimedia.org/mediawiki/${MEDIAWIKI_MAJOR_VERSION}/mediawiki-core-${MEDIAWIKI_VERSION}.tar.gz" -o mediawiki.tar.gz &&\
    tar -xzf mediawiki.tar.gz --strip-components=1 --directory /mediawiki/ &&\
    rm mediawiki.tar.gz

# Create cache directories for mediawiki
# $wgCacheDirectory should not be accessible from the web and writable by the web server
# See https://www.mediawiki.org/wiki/Manual:$wgCacheDirectory for details
RUN install -d -o www-data -g www-data /tmp/file-cache /tmp/cache

# The scheduled maintenance; the femiwiki image installs the crontab that runs it
COPY cron/update-special-pages \
      cron/run-jobs \
      cron/publish-dump \
      cron/rebuild-property-pairs \
      /usr/local/bin/
