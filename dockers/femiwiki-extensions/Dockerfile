ARG MEDIAWIKI_VERSION=1.41.0

#
# 미디어위키 확장 설치 스테이지. 루비 스크립트를 이용해 수많은 미디어위키
# 확장들을 병렬로 빠르게 미리 다운받아 놓는다.
#
FROM --platform=$TARGETPLATFORM ruby:3.3.0-alpine

# ARG instructions without a value inside of a build stage to use the default
# value of an ARG declared before the first FROM use
ARG MEDIAWIKI_VERSION

# aria2
#
# References:
#   https://aria2.github.io/
RUN apk update && apk add \
      aria2

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
