# 미디어위키 확장을 병렬로 미리 받아 두는 스테이지. composer 이미지에 설치기용
# PHP, 의존성용 composer, 다운로드용 git과 curl이 있고, npm은 스킨이 쓰는
# 패키지를 설치한다.
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
