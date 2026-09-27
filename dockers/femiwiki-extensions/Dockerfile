#
# 미디어위키 확장 설치 스테이지. 루비 스크립트를 이용해 수많은 미디어위키
# 확장들을 병렬로 빠르게 미리 다운받아 놓는다.
#
FROM ruby:3.3.0-alpine

# aria2 for the non-WMF tarballs; git and composer for the WMF extensions,
# which are fetched at a pinned commit and given the dependencies extdist
# would have bundled
#
# References:
#   https://aria2.github.io/
RUN apk update && apk add \
      aria2 \
      composer \
      git

# Install aria2.conf
COPY extension-installer/aria2.conf /root/.config/aria2/aria2.conf

RUN mkdir /mediawiki/

# Extensions and skins setup
COPY extension-installer/* /
RUN bundle config set deployment 'true' &&\
    bundle config set without 'development test' &&\
    bundle install
RUN bundle exec ruby /install_extensions.rb
