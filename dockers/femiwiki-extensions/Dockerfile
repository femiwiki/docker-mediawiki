# 미디어위키 확장을 병렬로 미리 받아 두는 스테이지. composer 이미지에 설치기용
# PHP, 의존성용 composer, 다운로드용 git과 curl이 있고, npm은 스킨이 쓰는
# 패키지를 설치한다.
FROM composer:2.10.3

RUN apk add --no-cache npm

# git fetch can leave maintenance running in the background, which writes into
# .git while the installer deletes it ("Directory not empty")
RUN git config --global maintenance.auto false &&\
    git config --global gc.auto 0

# How many extensions to fetch at once. Left empty, the installer reads the
# building machine's CPU count with nproc on every build and uses twice that.
ARG INSTALL_JOBS=

WORKDIR /installer
COPY extension-installer/composer.json extension-installer/composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress
# This image's PHP is newer than the one that runs the extensions, so composer
# resolves their dependencies for the PHP that dockers/php-fpm/Dockerfile names.
# Build with --build-context dockers-php-fpm=dockers/php-fpm.
RUN --mount=type=bind,from=dockers-php-fpm,source=Dockerfile,target=/php-fpm.Dockerfile \
    php="$(sed -nE 's/^FROM .* php:([0-9.]+)-.*/\1/p' /php-fpm.Dockerfile)" &&\
    test -n "$php" &&\
    composer config --global platform.php "$php"
COPY extension-installer/install_extensions.php extension-installer/extensions.json ./
RUN INSTALL_JOBS="${INSTALL_JOBS}" php install_extensions.php

# patches/ mirrors /mediawiki: patches/extensions/<name>/*.patch applies to
# that extension. A patch that no longer applies fails the build.
COPY patches/ /patches/
RUN for p in /patches/*/*/*.patch; do \
      [ -e "$p" ] || continue; \
      git -C "/mediawiki/$(dirname "${p#/patches/}")" apply "$p" || exit 1; \
    done
