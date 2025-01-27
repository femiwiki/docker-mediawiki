FROM --platform=$TARGETPLATFORM ghcr.io/femiwiki/php-fpm:1.2.1
ARG MEDIAWIKI_VERSION=1.42.4

RUN mkdir /mediawiki/
RUN MEDIAWIKI_MAJOR_VERSION="$(echo $MEDIAWIKI_VERSION | cut -d. -f-2)" &&\
    curl -fSL "https://releases.wikimedia.org/mediawiki/${MEDIAWIKI_MAJOR_VERSION}/mediawiki-core-${MEDIAWIKI_VERSION}.tar.gz" -o mediawiki.tar.gz &&\
    tar -xzf mediawiki.tar.gz --strip-components=1 --directory /mediawiki/ &&\
    rm mediawiki.tar.gz

# Create cache directories for mediawiki
# $wgCacheDirectory should not be accessible from the web and writable by the web server
# See https://www.mediawiki.org/wiki/Manual:$wgCacheDirectory for details
RUN sudo -u www-data mkdir -p /tmp/file-cache /tmp/cache

# Install and register cron
COPY cron/crontab /tmp/crontab
RUN crontab /tmp/crontab && rm /tmp/crontab
COPY cron/generate-sitemap \
      cron/update-special-pages \
      cron/run-jobs \
      /usr/local/bin/

# Install scripts
RUN mkdir -p /mediawiki/sitemap &&\
    chown www-data:www-data /mediawiki/sitemap

# secrets.php should be mounted to '/a/secrets.php'
VOLUME /a
