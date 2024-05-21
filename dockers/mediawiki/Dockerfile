FROM --platform=$TARGETPLATFORM ghcr.io/femiwiki/php-fpm:1.0.0
ARG MEDIAWIKI_VERSION=1.41.0

RUN MEDIAWIKI_MAJOR_VERSION="$(echo $MEDIAWIKI_VERSION | cut -d. -f-2)" &&\
    curl -fSL "https://releases.wikimedia.org/mediawiki/${MEDIAWIKI_MAJOR_VERSION}/mediawiki-core-${MEDIAWIKI_VERSION}.tar.gz" -o mediawiki.tar.gz &&\
    tar -xzf mediawiki.tar.gz --strip-components=1 --directory /mediawiki/ &&\
    rm mediawiki.tar.gz

# Create cache directories for mediawiki
# $wgCacheDirectory should not be accessible from the web and writable by the web server
# See https://www.mediawiki.org/wiki/Manual:$wgCacheDirectory for details
RUN sudo -u www-data mkdir -p /tmp/file-cache /tmp/cache

# Web server should be able to write 'extensions/Widgets/compiled_templates'
# directory Required by 'Widgets' extension
# Reference: https://www.mediawiki.org/wiki/Extension:Widgets
RUN chmod o+w /mediawiki/extensions/Widgets/compiled_templates

# Web server should be able to execute lua binary
# Reference: https://www.mediawiki.org/wiki/Extension:Scribunto#Additional_binaries
RUN chmod o+x /usr/bin/lua

# Install and register cron
COPY cron/crontab /tmp/crontab
RUN crontab /tmp/crontab && rm /tmp/crontab
COPY cron/generate-sitemap \
      cron/update-special-pages \
      cron/run-jobs \
      /usr/local/bin/

# Install scripts
RUN sudo -u www-data mkdir -p /mediawiki/sitemap

# Ship femiwiki-specific mediawiki configurations
COPY --chown=www-data LocalSettings.php /a/

# secret.php should be mounted to '/a/secret.php'
VOLUME /a
