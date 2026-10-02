# syntax=docker/dockerfile:1

# ── Pins (reproducible) ───────────────────────────────────────────────────────
# Resolve the digest once and freeze it for prod:
#   docker buildx imagetools inspect dunglas/frankenphp:1.12-php8.3-bookworm
# dunglas/frankenphp:1.12-php8.3-bookworm => PHP 8.3.x (cli) ZTS, arm64 published.
ARG FRANKENPHP_VERSION=1.12-php8.3-bookworm
# Source of the assembled app tree (/srv/femiwiki.com + /a + php.ini/opcache.ini).
ARG APP_IMAGE=ghcr.io/femiwiki/femiwiki:latest
# Localisation languages to PREBUILD. EMPTY = ALL languages (Wikimedia-style, correct for a
# multilingual wiki with Translate+ULS). Set e.g. "ko,en" only if you accept the English
# shallow-fallback for every other UI language (see gap note in section 5).
ARG L10N_LANGS=

############################
# composer (unchanged from dockers/php-fpm/Dockerfile:4)
############################
FROM --platform=$TARGETPLATFORM composer:2.8.6 AS composer

############################
# assembled Femiwiki app tree (consumed read-only)
############################
FROM ${APP_IMAGE} AS app
# We copy only its filesystem: /srv/femiwiki.com (MW 1.43.8 + extensions/skins + vendor),
# /a (LocalSettings.php, Hotfix.php template, site-list.xml), and the two PHP ini files.
# NOTE (build fact, verified): in this image LocalSettings.php lives ONLY at /a/LocalSettings.php
# (femiwiki/Dockerfile:53). It is copied to the webroot at RUNTIME (run:27). So the build-time
# l10n step must read /a/LocalSettings.php, not the webroot (see the prebuild RUN below).

############################
# FrankenPHP classic backend (the image under test)
############################
FROM dunglas/frankenphp:${FRANKENPHP_VERSION}
ARG TARGETPLATFORM
ARG L10N_LANGS

# Hard build-time guarantee: FrankenPHP requires ZTS. Fail fast if not.
RUN test "$(php -r 'echo PHP_ZTS;')" = "1" || { echo 'FATAL: PHP is not ZTS'; exit 1; }

# TZ: ports dockers/femiwiki/Dockerfile:16-17 (Asia/Seoul + localtime symlink).
ENV TZ=Asia/Seoul
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Caddy data/config dirs (FrankenPHP embeds Caddy). Ports php-fpm/Dockerfile:32-33.
ENV XDG_CONFIG_HOME=/config \
    XDG_DATA_HOME=/data

# Runtime deps only. Ports php-fpm/Dockerfile:11-27 MINUS cron (:23) and sudo (:24) (both
# forbidden here, locked decision) and MINUS build-essential/libicu-dev (install-php-extensions
# pulls and purges its own build deps). git kept per issue #442 (:18-19); python3 for
# SyntaxHighlight/Pygments (:20-21); imagemagick + librsvg2-bin for thumbnails (:16-17).
RUN apt-get update && apt-get install -y --no-install-recommends \
      imagemagick \
      librsvg2-bin \
      git \
      python3 \
      unzip \
 && rm -rf /var/lib/apt/lists/*

# install-php-extensions: dunglas/frankenphp bundles it, but DO NOT assume. Fail fast if absent
# (then add it the way php-fpm/Dockerfile:44 does). Closes the "assumed bundled" gap explicitly.
RUN command -v install-php-extensions >/dev/null 2>&1 \
 || { echo 'FATAL: install-php-extensions not present in base; ADD it from'; \
      echo '  https://github.com/mlocati/docker-php-extension-installer/releases'; exit 1; }

# PHP extensions, rebuilt ZTS for PHP 8.3, PINNED. Ports php-fpm/Dockerfile:36-54
# (calendar/intl/mysqli/opcache/sockets + luasandbox/wikidiff2 + apcu) PLUS:
#   gd, exif  : enable PDF/large-image + EXIF (composer.json suggests; current image lacked them).
#   pcntl     : lets the build-time rebuildLocalisationCache fork --threads (build speed only;
#               MediaWiki never forks in a web request, so loading pcntl is harmless at runtime).
#   luasandbox >= 4.1.1 required for PHP 8 (T322748); 4.1.3 = latest stable.
#   wikidiff2 1.14.1, apcu 5.1.24 (single pinned value everywhere; reconciles the 5.1.28 note).
RUN install-php-extensions \
      intl mysqli opcache sockets calendar \
      gd exif pcntl \
      apcu-5.1.24 \
      luasandbox-4.1.3 \
      wikidiff2-1.14.1

# Verify EVERY required .so is loaded. Covers our 8 explicit installs AND the MediaWiki 1.43
# hard-required core extensions (composer.json:25-35,94: mbstring/dom/xml/xmlreader/simplexml/
# ctype/fileinfo/iconv/libxml/openssl/json/filter). They ship by default in the php base, but
# this asserts it so a missing core requirement fails the BUILD, not the first request.
RUN php -m | tr 'A-Z' 'a-z' > /tmp/mods \
 && for e in intl mysqli sockets calendar apcu luasandbox wikidiff2 'zend opcache' \
            gd exif pcntl \
            mbstring dom xml xmlreader simplexml ctype fileinfo iconv libxml openssl json filter; do \
      grep -qx "$e" /tmp/mods || grep -q "$e" /tmp/mods \
        || { echo "FATAL: extension '$e' missing"; exit 1; }; \
    done && rm /tmp/mods

# ImageMagick PDF coder: Debian's policy.xml disables it, but $wgFileExtensions allows 'pdf'
# (LocalSettings.php:150-155). Relax it so PDF thumbnailing works. Pre-existing limitation in the
# current image; fixed here on purpose. (No-op if the policy file/line is absent.)
RUN for p in /etc/ImageMagick-6/policy.xml /etc/ImageMagick-7/policy.xml; do \
      [ -f "$p" ] && sed -i 's#\(<policy domain="coder" rights="\)none\("[[:space:]]*pattern="PDF"[[:space:]]*/>\)#\1read|write\2#' "$p" || true; \
    done

# Composer binary (ports php-fpm/Dockerfile:96).
COPY --from=composer /usr/bin/composer /usr/bin/composer

# PHP ini: carry php.ini + opcache verbatim from the current image (port-faithful), THEN layer a
# zz- delta file that wins (conf.d loads alphabetically). php-fpm.conf + www.conf are DROPPED
# (no FPM master, no pool). ${ENV} substitution in php.ini works on PHP 8.3.
COPY --from=app /usr/local/etc/php/php.ini                        /usr/local/etc/php/php.ini
COPY --from=app /usr/local/etc/php/conf.d/opcache-recommended.ini /usr/local/etc/php/conf.d/opcache-recommended.ini
COPY zz-frankenphp.ini                                            /usr/local/etc/php/conf.d/zz-frankenphp.ini
# Ports php-fpm/Dockerfile:64-65. All PHP_FPM_* env (:66-74) are dead under FrankenPHP, dropped.
ENV PHP_POST_MAX_SIZE=10M \
    PHP_UPLOAD_MAX_FILESIZE=10M

# ── Assembled app tree from the current image ────────────────────────────────
# /srv/femiwiki.com = MW 1.43.8 (mediawiki/Dockerfile:2) + femiwiki extensions/skins
# (femiwiki/Dockerfile:23-24) + composer vendor (femiwiki/Dockerfile:39,42).
# /a = LocalSettings.php + Hotfix.php template + site-list.xml (femiwiki/Dockerfile:51,53).
COPY --from=app --chown=www-data:www-data /srv/femiwiki.com /srv/femiwiki.com
COPY --from=app --chown=www-data:www-data /a               /a
WORKDIR /srv/femiwiki.com
#
# ── PRODUCTION-PARITY ALTERNATIVE (swap the two COPYs above for this) ─────────
# FROM mediawiki:3.4.4 + COPY --from=extensions, then re-run composer on PHP 8.3:
#   RUN COMPOSER_HOME=/composer composer update --no-dev --working-dir /srv/femiwiki.com
#   RUN COMPOSER_HOME=/composer composer update --no-dev \
#         --working-dir /srv/femiwiki.com/extensions/TemplateStyles   # T363063, femiwiki/Dockerfile:42
# ─────────────────────────────────────────────────────────────────────────────

# Backend-specific files (the new bits this port adds).
COPY --chown=www-data:www-data healthz-ready.php     /srv/femiwiki.com/healthz.php
COPY --chown=www-data:www-data backend-overrides.php /a/backend-overrides.php
COPY --chown=www-data:www-data l10n-build.php        /a/l10n-build.php
COPY Caddyfile                                       /etc/frankenphp/Caddyfile
COPY --chmod=0755 entrypoint.sh                      /usr/local/bin/entrypoint.sh

# Widgets must write compiled templates at runtime (ports femiwiki/Dockerfile:47).
# Ephemeral writable cache dirs (mediawiki/Dockerfile:13 pre-creates /tmp/cache, /tmp/file-cache).
# Uploads go to S3 via ext:AWS ($wgAWSBucketPrefix, LocalSettings.php:442) => no uploads volume.
RUN chmod o+w /srv/femiwiki.com/extensions/Widgets/compiled_templates \
 && mkdir -p /tmp/cache /tmp/file-cache /var/cache/mw-l10n /config /data \
 && chown -R www-data:www-data /tmp/cache /tmp/file-cache /var/cache/mw-l10n /config /data

# Localisation-cache PREBUILD at build time -> baked, read-only, manualRecache. Without this, each
# container rebuilds lazily into ephemeral /tmp/cache ($wgCacheDirectory, LocalSettings.php:164) on
# first request: a cold-start stampede multiplied per ASG node AND per ZTS thread.
#  * reads /a/LocalSettings.php (the STAGED canonical file; the webroot copy does not exist at build
#    time), via l10n-build.php which forces CACHE_NONE so the build is hermetic/offline.
#  * EMPTY L10N_LANGS builds ALL languages (no English shallow-fallback regression). Threads use
#    pcntl when present, else fall back to 1 so the build never breaks.
RUN THREADS=1; php -m | grep -qi '^pcntl$' && THREADS="$(nproc)"; \
    set -- ; [ -n "$L10N_LANGS" ] && set -- --lang="$L10N_LANGS"; \
    MW_CONFIG_FILE=/a/l10n-build.php \
      php maintenance/run.php rebuildLocalisationCache "$@" --threads="$THREADS" --no-progress \
 && chown -R www-data:www-data /var/cache/mw-l10n

# Strip the Debian base image's stock cron schedule (only /etc/cron.d/e2scrub_all from e2fsprogs
# ships here, an fs-scrub job, inert without a cron daemon). Removing it makes the no-cron guarantee
# literally complete so the assertion below validates a genuinely cron-free image.
RUN rm -rf /etc/cron.d /etc/crontab /etc/cron.daily /etc/cron.hourly /etc/cron.weekly /etc/cron.monthly /var/spool/cron

# ── Build-time assertions ────────────────────────────────────────────────────
# cron MUST be absent (binaries + schedule files); entrypoint must exec FrankenPHP and must NOT
# invoke the FPM master. The php-fpm grep strips comments first (so the entrypoint's own prose can
# never self-abort the build) and matches a real token, not a substring of install-php-extensions.
RUN set -eu; \
    if command -v cron >/dev/null 2>&1 || command -v crond >/dev/null 2>&1; then \
      echo 'FATAL: a cron daemon is installed (forbidden in the backend image)'; exit 1; fi; \
    if [ -s /etc/crontab ] \
       || { [ -d /etc/cron.d ] && [ -n "$(ls -A /etc/cron.d 2>/dev/null)" ]; } \
       || { [ -d /var/spool/cron/crontabs ] && [ -n "$(ls -A /var/spool/cron/crontabs 2>/dev/null)" ]; }; then \
      echo 'FATAL: cron schedule files present'; exit 1; fi; \
    code="$(sed 's/#.*$//' /usr/local/bin/entrypoint.sh)"; \
    if printf '%s' "$code" | grep -Eq '(^|[^A-Za-z0-9_-])php-fpm([^A-Za-z0-9_-]|$)'; then \
      echo 'FATAL: entrypoint invokes the FPM master'; exit 1; fi; \
    if ! grep -q 'exec frankenphp run' /usr/local/bin/entrypoint.sh; then \
      echo 'FATAL: entrypoint does not exec FrankenPHP'; exit 1; fi; \
    echo 'OK: no-cron / no-fpm-master / frankenphp-exec assertions passed'

# Backend serves PLAIN HTTP on :8080 only. Current image EXPOSE 80/443/9000 (femiwiki/Dockerfile:55-57)
# reflected the baked Caddy(80/443)+FPM(9000) dual role; all gone here.
EXPOSE 8080

# Concurrency cap mirrors old pm.max_children=20 (www.conf:6 via PHP_FPM_PM_MAX_CHILDREN, default
# php-fpm/Dockerfile:69). SIZING IS UNVALIDATED until D1: 20 threads * memory_limit 128M (php.ini:25)
# ~= 2.5GB heap + ~512MB opcache SHM ~= 3GB, which can OOM a small t4g. Lower this for the target
# instance and re-confirm with the D1 measurement before raising it. See the math in the Caddyfile.
# DDL-skip flags ON by default: install/update/importSites move to a one-shot migration job.
ENV FRANKENPHP_NUM_THREADS=20 \
    SERVER_NAME=:8080 \
    MEDIAWIKI_SKIP_INSTALL=1 \
    MEDIAWIKI_SKIP_UPDATE=1 \
    MEDIAWIKI_SKIP_IMPORT_SITES=1

USER www-data
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
# No CMD; FrankenPHP is exec'd inside the entrypoint. No tini (FrankenPHP is PID-1-friendly).
