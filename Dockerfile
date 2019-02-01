# 파일 목록
#
# /usr/local/etc/php     : PHP 설정
# /srv/femiwiki.com      : 미디어위키 소스코드 및 확장들
# /usr/local/{bin,sbin}  : 임의로 설치한 실행파일들
# /tmp/cache             : 캐시 디렉토리
# /tini                  : tini

ARG MEDIAWIKI_MAJOR_VERSION=1.31
ARG MEDIAWIKI_BRANCH=REL1_31
ARG MEDIAWIKI_VERSION=1.31.1
ARG MEDIAWIKI_SHA512=ee49649cc37d0a7d45a7c6d90c822c2a595df290be2b5bf085affbec3318768700a458a6e5b5b7e437651400b9641424429d6d304f870c22ec63fae86ffc5152

#
# 미디어위키 확장 설치 스테이지. 루비 스크립트를 이용해 수많은 미디어위키
# 확장들을 병렬로 빠르게 미리 다운받아놓는다.
#
FROM femiwiki/base-extensions

# ARG instructions without a value inside of a build stage to use the default
# value of an ARG declared before the first FROM use
ARG MEDIAWIKI_BRANCH

COPY extension-installer/* /tmp/
RUN bundle install --gemfile /tmp/Gemfile --path /var/www/.gem &&\
    sudo -u www-data ruby /tmp/install_extensions.rb "${MEDIAWIKI_BRANCH}"


#
# 미디어위키 도커이미지 생성 스테이지. 미디어위키 실행에 필요한 각종 PHP
# 디펜던시들을 설치한다.
#
FROM php:7.2-fpm

ARG MEDIAWIKI_MAJOR_VERSION
ARG MEDIAWIKI_BRANCH
ARG MEDIAWIKI_VERSION
ARG MEDIAWIKI_SHA512

# Set timezone
ENV TZ=Asia/Seoul
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Install dependencies and utilities
RUN apt-get update && apt-get install -y \
      # Build dependencies
      build-essential \
      libicu-dev \
      # Runtime depenencies
      imagemagick \
      librsvg2-bin \
      # Required for SyntaxHighlighting
      python3 \
      # Required utilities
      cron \
      sudo

# Install the PHP extensions we need
RUN docker-php-ext-install -j8 mysqli opcache intl

# Install the default object cache.
RUN pecl channel-update pecl.php.net &&\
    pecl install apcu &&\
    docker-php-ext-enable apcu

# Configure PHP
RUN mv /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini &&\
    rm /usr/local/etc/php/php.ini-development &&\
    sed -ri '/^\s*(post_max_size|upload_max_filesize)\s*=\s*.+?\s*$/s/=.*$/= 10M/' /usr/local/etc/php/php.ini
# Configure PHP opcache
COPY configs/opcache-recommended.ini /usr/local/etc/php/conf.d/opcache-recommended.ini

# MediaWiki setup
RUN curl -fSL "https://releases.wikimedia.org/mediawiki/${MEDIAWIKI_MAJOR_VERSION}/mediawiki-${MEDIAWIKI_VERSION}.tar.gz" -o mediawiki.tar.gz &&\
    echo "${MEDIAWIKI_SHA512} *mediawiki.tar.gz" | sha512sum -c - &&\
    mkdir -p /srv/femiwiki.com/ &&\
    chown www-data:www-data /srv/femiwiki.com/ &&\
    sudo -u www-data tar -xzf mediawiki.tar.gz --strip-components=1 --directory /srv/femiwiki.com/ &&\
    rm mediawiki.tar.gz

# Install Mediawiki extensions
COPY --from=0 --chown=www-data /tmp/extensions/ /srv/femiwiki.com/

# Remove packages which is not needed anymore
RUN apt-get autoremove -y --purge \
      # Build dependencies of PHP extensions
      build-essential \
      libicu-dev

# Create a cache directory for mediawiki
RUN sudo -u www-data mkdir -p /tmp/cache

# Web server should be able to write 'extensions/Widgets/compiled_templates'
# directory Required by 'Widgets' extension
# Reference: https://www.mediawiki.org/wiki/Extension:Widgets
RUN chmod o+w /srv/femiwiki.com/extensions/Widgets/compiled_templates


#
# Install and register cron
#
COPY cron/crontab /tmp/crontab
RUN crontab /tmp/crontab && rm /tmp/crontab

# Install 'generate-sitemap' script
RUN sudo -u www-data mkdir -p /srv/femiwiki.com/sitemap
COPY cron/generate-sitemap /usr/local/bin/generate-sitemap


#
# Tini
#
# See https://github.com/krallin/tini for the further details
ARG TINI_VERSION=v0.18.0
ADD https://github.com/krallin/tini/releases/download/${TINI_VERSION}/tini /tini
RUN chmod +x /tini
ENTRYPOINT ["/tini", "--"]


# Store femiwiki resources
COPY --chown=www-data:www-data resources /srv/femiwiki.com/
# secret.php should be mounted to '/a/secret.php'
VOLUME /a

WORKDIR /srv/femiwiki.com
EXPOSE 9000

COPY run /usr/local/bin/
CMD ["/usr/local/bin/run"]
