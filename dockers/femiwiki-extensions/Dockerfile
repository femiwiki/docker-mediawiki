#
# 미디어위키 확장 설치 스테이지. 수많은 미디어위키 확장들을 병렬로 빠르게
# 미리 다운받아 놓는다.
#
# The composer image carries PHP for the installer, composer for the
# dependencies extdist would have bundled, and git and curl for the downloads.
# npm installs the packages our skin loads at runtime.
FROM composer:2.10.3

RUN apk add --no-cache npm

# How many extensions to fetch at once. Left empty, the installer reads the
# building machine's CPU count with nproc on every build and uses twice that.
ARG INSTALL_JOBS=

WORKDIR /installer
COPY extension-installer/composer.json extension-installer/composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress
COPY extension-installer/install_extensions.php extension-installer/extensions.json ./
RUN INSTALL_JOBS="${INSTALL_JOBS}" php install_extensions.php
