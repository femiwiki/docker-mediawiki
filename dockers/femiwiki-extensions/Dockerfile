#
# 미디어위키 확장 설치 스테이지. 수많은 미디어위키 확장들을 병렬로 빠르게
# 미리 다운받아 놓는다.
#
# The composer image carries PHP for the installer, composer for the
# dependencies extdist would have bundled, and git and curl for the downloads.
FROM composer:2.10.3

# How many extensions to fetch at once; empty means one per CPU
ARG INSTALL_JOBS=16

COPY extension-installer/install_extensions.php extension-installer/extensions.json /
RUN INSTALL_JOBS="${INSTALL_JOBS}" php /install_extensions.php
