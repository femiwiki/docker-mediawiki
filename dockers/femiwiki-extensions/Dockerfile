#
# 루비 스크립트를 이용해 많은 미디어위키
# 확장들을 병렬로 빠르게 다운받아 놓는다.
#
FROM --platform=$TARGETPLATFORM ruby:3.3.0-alpine

ARG MEDIAWIKI_VERSION=1.41.0
ARG COMPOSER_VERSION=2.6.6

# aria2
#
# References:
#   https://aria2.github.io/
RUN apk update && apk add \
    aria2 \
    # Required for composer
    php81-cli \
    php81-mbstring \
    php81-phar \
    php81-openssl \
    # Required for aws-sdk-php
    php81-simplexml \
    ;

# Install Composer
RUN wget 'https://getcomposer.org/installer' -O 'composer-setup.php' &&\
    php81 composer-setup.php --version "${COMPOSER_VERSION}" --install-dir=/usr/local/bin --filename=composer --quiet

# Install aria2.conf
COPY extension-installer/aria2.conf /root/.config/aria2/aria2.conf

RUN mkdir /mediawiki/

# Extensions and skins setup
COPY extension-installer/* /
RUN bundle config set deployment 'true' &&\
    bundle config set without 'development test' &&\
    bundle install
RUN MEDIAWIKI_BRANCH="REL$(echo $MEDIAWIKI_VERSION | cut -d. -f-2 | sed 's/\./_/g')" &&\
    bundle exec ruby /install_extensions.rb "${MEDIAWIKI_BRANCH}"
