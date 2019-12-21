#
# 미디어위키 및 확장 설치 스테이지. 루비 스크립트를 이용해 수많은 미디어위키
# 확장들을 병렬로 빠르게 미리 다운받아 놓는다.
#
FROM femiwiki/base-extensions:build-1

ARG MEDIAWIKI_MAJOR_VERSION=1.34
ARG MEDIAWIKI_BRANCH=REL1_34
ARG MEDIAWIKI_VERSION=1.34.0
ARG MEDIAWIKI_SHA512=d133042fbc2918e5a901d732573d98c17ef56712187c4de60af5b6b46d52acd28b79ef0b3539d8922ad95430bfb45a1b4c91efe60f8c45adb785f3e5e3c645f5

RUN mkdir -p /tmp/mediawiki/ &&\
    chown www-data:www-data /tmp/mediawiki/

# Extensions and skins setup
COPY extension-installer/* /tmp/
RUN bundle install --deployment --gemfile /tmp/Gemfile --path /var/www/.gem
RUN sudo -u www-data ruby /tmp/install_extensions.rb "${MEDIAWIKI_BRANCH}"

# MediaWiki setup
COPY --chown=www-data configs/composer.local.json /tmp/mediawiki/
RUN curl -fSL "https://releases.wikimedia.org/mediawiki/${MEDIAWIKI_MAJOR_VERSION}/mediawiki-core-${MEDIAWIKI_VERSION}.tar.gz" -o mediawiki.tar.gz &&\
    echo "${MEDIAWIKI_SHA512} *mediawiki.tar.gz" | sha512sum -c - &&\
    sudo -u www-data tar -xzf mediawiki.tar.gz --strip-components=1 --directory /tmp/mediawiki/ &&\
    rm mediawiki.tar.gz
RUN sudo -u www-data COMPOSER_HOME=/tmp/composer composer update --no-dev --working-dir '/tmp/mediawiki'

#
# 미디어위키 도커이미지 생성 스테이지. 미디어위키 실행에 필요한 각종 PHP
# 디펜던시들을 설치한다.
#
# 파일 목록:
#   /usr/local/etc/php     PHP 설정
#   /srv/femiwiki.com      미디어위키 소스코드 및 확장들
#   /usr/local/{bin,sbin}  임의로 설치한 실행파일들
#   /tmp/cache             캐시 디렉토리
#   /tini                  tini
#
FROM femiwiki/base:build-2

# Set timezone
ENV TZ=Asia/Seoul
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Configure PHP
COPY php/php.ini /usr/local/etc/php/php.ini
COPY php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY php/opcache-recommended.ini /usr/local/etc/php/conf.d/opcache-recommended.ini

# Install Mediawiki extensions
COPY --from=0 --chown=www-data /tmp/mediawiki /srv/femiwiki.com

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

# Install 'localisation-update' script
COPY cron/localisation-update /usr/local/bin/localisation-update

# Store femiwiki resources
COPY --chown=www-data:www-data resources /srv/femiwiki.com/
# secret.php should be mounted to '/a/secret.php'
VOLUME /a

WORKDIR /srv/femiwiki.com
EXPOSE 9000

COPY run /usr/local/bin/
CMD ["/usr/local/bin/run"]
