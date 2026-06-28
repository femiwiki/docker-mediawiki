All citations verified against the live files. Here is the final, gap-closed package.

---

# FrankenPHP Classic-Mode Backend Image for Femiwiki, FINAL (no production change)

Every file is copy-pasteable and version-pinned. Each non-obvious choice cites the current image by `file:line`. The set ports the four-layer chain (`php:8.1.32-fpm` -> `php-fpm:1.4.0` -> `mediawiki:3.4.4` -> `femiwiki`) into one FrankenPHP classic-mode backend that serves plain HTTP on `:8080` behind the existing edge Caddy. All 17 gaps from the review are closed; a closure map is at the end.

Build-context layout:

```
femiwiki-frankenphp/
├── Dockerfile
├── Caddyfile                 # -> /etc/frankenphp/Caddyfile
├── entrypoint.sh             # -> /usr/local/bin/entrypoint.sh   (replaces dockers/femiwiki/run)
├── zz-frankenphp.ini         # -> /usr/local/etc/php/conf.d/zz-frankenphp.ini  (opcache deltas)
├── healthz-ready.php         # -> /srv/femiwiki.com/healthz.php
├── backend-overrides.php     # -> /a/backend-overrides.php   (LocalSettings deltas)
├── l10n-build.php            # -> /a/l10n-build.php          (build-time only)
└── poc/
    ├── compose.yaml
    ├── migrate.sh            # one-shot install/update/importSites (closes PoC schema gap)
    ├── poc-overrides.php     # PoC-only: disable AWS/captcha/CDN WITHOUT debug toolbar
    ├── functional.sh
    ├── lua-content.sh        # ships the real Lua module source
    ├── Hotfix-standalone.php
    └── soak.js
```

---

## 1. Dockerfile

Design note on the app tree: MediaWiki core + ~90 extensions/skins + composer `vendor/` are arch-independent PHP that the existing chain already assembles. This consumes that tree from `ghcr.io/femiwiki/femiwiki` (the `app` stage) and rebuilds only the C extensions for ZTS PHP 8.3. The consumed `vendor/` was resolved under PHP 8.1; MediaWiki's `vendor/composer/platform_check.php` asserts a PHP **minimum** (`>= 8.1`), not a ceiling, so running it on 8.3 is compatible. The production-parity alternative (start from `mediawiki:3.4.4` + `femiwiki-extensions:2.4.1`, re-run the two `composer update --no-dev` steps on PHP 8.3, `dockers/femiwiki/Dockerfile:39,42`) is annotated inline and is a drop-in swap.

```dockerfile
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
```

---

## 1b. zz-frankenphp.ini

Path `/usr/local/etc/php/conf.d/zz-frankenphp.ini`. Layered on top of the ported `opcache-recommended.ini` (`zz-` sorts last, so it wins). Closes the opcache-too-small gap: the deployed tree is ~11,656 core files (20,661 with `vendor/`) plus ~90 extensions/skins (Wikibase/Translate/VisualEditor/Flow/GrowthExperiments are large), well over 40k files, so the stock `opcache.max_accelerated_files=4000` (`opcache-recommended.ini:4`) would evict/recompile every request and defeat the whole point of keeping opcache warm in-process.

```ini
; Femiwiki FrankenPHP classic-mode deltas. Loaded AFTER opcache-recommended.ini (zz- wins).
; opcache SHM is allocated ONCE per process (shared across all ZTS threads), not per thread.
opcache.memory_consumption=512
opcache.interned_strings_buffer=32
opcache.max_accelerated_files=50000
opcache.revalidate_freq=60
opcache.validate_timestamps=1
opcache.enable_cli=1
; The deep MediaWiki include tree benefits from a large realpath cache.
realpath_cache_size=4096k
realpath_cache_ttl=600
```

---

## 2. App Caddyfile / FrankenPHP config

Path `/etc/frankenphp/Caddyfile`. Classic mode (no `worker {}` block), plain HTTP `:8080`, `auto_https off`. Ports the app-serving directives of `dockers/femiwiki/Caddyfile`: the two MediaWiki rewrites (`Caddyfile:42-43`) and the health-route concept (`respond /health-check 200`, `Caddyfile:45`). Drops everything edge-only: `storage s3` (`:3`), `tls dns route53` (`:10-12`), `mwcache`+`purge_acl` (`:17-27`), security headers (`:28-37`), `@block_cidr` WAF (`:47-53`), `@filter0/1/2` (`:56-76`). `php_server` replaces `php_fastcgi {$FASTCGI_ADDR}` + `file_server` (`:14-15`). Adds a dedicated handle for path-style entry points so `PATH_INFO` is split correctly (closes the `rest.php` gap).

```caddyfile
{
	# Backend only: the edge Caddy terminates TLS. No ACME, no certs, plain HTTP on :8080.
	auto_https off
	# Immutable ASG node: no runtime admin/reconfig surface.
	admin off

	# CLASSIC mode: a fixed PHP thread pool. num_threads is the 1:1 replacement for the old
	# php-fpm pm.max_children = ${PHP_FPM_PM_MAX_CHILDREN} (www.conf:6, default 20).
	#
	# SIZING MATH (do this before raising num_threads; D1 measures connects/request):
	#   RAM:  num_threads * memory_limit(128M, php.ini:25) + opcache(512M) < container RAM.
	#         e.g. 20 threads => ~3.0GB; pick a value that fits the t4g instance.
	#   DB :  fleet_connections = nodes * num_threads * connects_per_request
	#         must stay under MariaDB max_connections (+ migration job + edge purge headroom).
	frankenphp {
		num_threads {$FRANKENPHP_NUM_THREADS:20}
		# Optional autoscaling (FrankenPHP >= 1.5). Leave OFF until the soak confirms DB headroom.
		# max_threads {$FRANKENPHP_MAX_THREADS:30}

		# Bounded queueing: reject (no free thread within the window) instead of hanging.
		# Partial stand-in for the dropped FPM request_terminate_timeout (www.conf:11).
		max_wait_time 15s
	}

	servers {
		# Trust the edge ONLY so Caddy's access-log client_ip shows the real client. This does
		# NOT change REMOTE_ADDR handed to PHP (stays = edge IP); MediaWiki WebRequest::getIP()
		# does the real XFF walk using $wgCdnServersNoPurge (see section 5).
		trusted_proxies static {$EDGE_CIDR:10.0.0.0/8}
		client_ip_headers X-Forwarded-For

		# The ONLY hard request timeouts in classic mode (no FPM master to hard-kill a thread).
		# Pair with php.ini max_execution_time=30 (php.ini:23). write MUST exceed it.
		timeouts {
			read_header 15s
			read_body   60s   # uploads: post_max_size/upload_max_filesize=10M (php.ini:7-8)
			write       65s   # > max_execution_time(30s) + render slack
			idle        2m
		}
	}
}

:8080 {
	root * /srv/femiwiki.com

	# handle blocks are mutually exclusive; the bare handle is the fallback and MUST stay last.

	# 1) LIVENESS, cheapest possible, answered by Caddy, no PHP, no MW boot. For the container
	#    watchdog. Replaces respond /health-check 200 (Caddyfile:45).
	handle /healthz-live {
		respond "live" 200
	}

	# 2) READINESS, boots MediaWiki + DB SELECT 1 + memcached + CACHE_DB session store.
	#    Edge/ASG-internal ONLY (keep it off the every-10s rotation control plane).
	handle /healthz-ready {
		rewrite * /healthz.php
		php_server
	}

	# 3) Path-style entry points. PATH_INFO population for `script.php/extra/path` is the one place
	#    classic mode can differ (FrankenPHP issue #937); a dedicated handle keeps split_path on .php.
	#    rest.php is load-bearing: Wikibase REST routes ($wgRestAPIAdditionalRouteFiles,
	#    LocalSettings.php:1010) + DiscussionTools/VisualEditor use it. Smoke-tested in functional.sh.
	@pathstyle path /rest.php* /img_auth.php* /thumb_handler.php*
	handle @pathstyle {
		php_server
	}

	# 4) MediaWiki app. Rewrites verbatim from Caddyfile:42-43. $wgScriptPath='' (LocalSettings.php:24),
	#    $wgArticlePath='/w/$1' (LocalSettings.php:25). FrankenPHP keeps the ORIGINAL REQUEST_URI after
	#    a rewrite (issue #1895), so WebRequest::getPathInfo() resolves the title from REQUEST_URI.
	#    api.php / load.php / index.php are all served here by php_server's try_files.
	handle {
		rewrite /w/api.php /api.php
		rewrite /w/* /index.php
		php_server
	}

	# JSON access log to stdout (parity with Caddyfile:38-40).
	log {
		output stdout
		format json
	}
}
```

---

## 3. entrypoint.sh

Path `/usr/local/bin/entrypoint.sh`. Replaces `dockers/femiwiki/run`. Keeps the override hooks (`run:4-5,48-49`) and LocalSettings materialization (`run:27`). Removes the unconditional `cron` (`run:46`) and the FPM master (`run:52`); execs FrankenPHP instead. Forces the DDL-skip flags so N fleet nodes never race `install.php`/`update.php`/`importSites.php` (`run:10-43`). Safety upgrades over `run`: `set -eu` without `-x` (so `WG_DB_PASSWORD` is never traced; `run:2` used `set -euxo`), and the Hotfix snippet written with `printf` (the unquoted heredoc at `run:30-32` shell-interpolates `$`/backticks). No literal FPM-master token appears in any executable line.

```sh
#!/bin/sh
# FrankenPHP (CLASSIC mode) Femiwiki BACKEND entrypoint. Replaces dockers/femiwiki/run.
# execs `frankenphp run` (never the FPM master, never cron); never runs install/update/importSites.
set -eu

log() { printf '%s entrypoint: %s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$*"; }

WEBROOT=/srv/femiwiki.com
STAGE=/a
CADDYFILE=/etc/frankenphp/Caddyfile

# 0. Hard guarantee for fleet nodes: never DDL the shared DB from N nodes. Schema work
#    (install/update/importSites + the in-flight $wgBlockTargetMigrationStage, LocalSettings.php:194)
#    moves to a SEPARATE one-shot migration container (poc/migrate.sh shows the shape).
export MEDIAWIKI_SKIP_INSTALL=1
export MEDIAWIKI_SKIP_UPDATE=1
export MEDIAWIKI_SKIP_IMPORT_SITES=1

# 1. Runtime no-cron guard (the build-time assertion is the primary defense).
if [ -n "${ENABLE_CRON:-}" ] || command -v cron >/dev/null 2>&1 || command -v crond >/dev/null 2>&1; then
  log "FATAL: cron is forbidden in this backend image."
  log "       Jobs/sitemap/specialpages run in a dedicated single runner (\$wgJobRunRate=0)."
  exit 1
fi

# 2. Secrets. LOCAL PoC: plain env (compose) + Docker '*_FILE' convention.
#    PROD: replace fetch_secrets() with `aws ssm get-parameter --with-decryption` / `aws s3 cp`.
fetch_secrets() {
  for _pair in $(env | grep -E '^[A-Za-z_][A-Za-z0-9_]*_FILE=' || true); do
    _name=${_pair%%=*}; _file=${_pair#*=}; _target=${_name%_FILE}
    eval "_cur=\${$_target:-}"
    if [ -z "$_cur" ] && [ -r "$_file" ]; then
      export "$_target=$(tr -d '\n' <"$_file")"
      log "loaded secret $_target from $_file"
    fi
  done
  unset _pair _name _file _target _cur 2>/dev/null || true
}
fetch_secrets

# Fail fast on missing required env (no safe fallback for a real node).
: "${WG_DB_SERVER:?WG_DB_SERVER is required}"
: "${WG_DB_USER:?WG_DB_USER is required}"
: "${WG_SECRET_KEY:?WG_SECRET_KEY is required (must be IDENTICAL across all nodes)}"

# 2b. Soft sizing guard (warn only). num_threads * 128M + 512M opcache should fit container RAM.
mem_max=""
[ -r /sys/fs/cgroup/memory.max ] && mem_max="$(cat /sys/fs/cgroup/memory.max 2>/dev/null || true)"
[ -z "$mem_max" ] || [ "$mem_max" = "max" ] && [ -r /sys/fs/cgroup/memory/memory.limit_in_bytes ] \
  && mem_max="$(cat /sys/fs/cgroup/memory/memory.limit_in_bytes 2>/dev/null || true)"
case "$mem_max" in
  ''|max|*[!0-9]*) : ;;
  *) _need=$(( ${FRANKENPHP_NUM_THREADS:-20} * 128 * 1024 * 1024 + 512 * 1024 * 1024 ));
     [ "$mem_max" -lt "$_need" ] && log "WARN: FRANKENPHP_NUM_THREADS=${FRANKENPHP_NUM_THREADS:-20} may exceed container RAM (need ~$((_need/1024/1024))MB, have $((mem_max/1024/1024))MB). Lower it." ;;
esac

# 3. prerun override hook (preserve the deploy-time customization contract; run:4-5).
if [ -x /usr/local/bin/prerun ]; then log "running prerun hook"; /usr/local/bin/prerun; fi

# 4. Materialize the canonical, env-driven LocalSettings.php (mirrors run:27).
log "materializing LocalSettings.php"
cp -f "$STAGE/LocalSettings.php" "$WEBROOT/LocalSettings.php"

# 5. Compose /a/Hotfix.php (require_once'd last, LocalSettings.php:1090). It always loads the baked
#    backend deltas (sessions=CACHE_DB, proxy trust, baked l10n; see backend-overrides.php), then the
#    optional operator snippet. The snippet MUST be a complete PHP file (open with <?php); it is
#    written verbatim with printf so $/backtick are NOT expanded (the run:30 heredoc footgun).
log "writing Hotfix.php -> backend-overrides + optional operator snippet"
printf '%s\n' "<?php require_once __DIR__ . '/backend-overrides.php';" > "$STAGE/Hotfix.php"
if [ -n "${MEDIAWIKI_HOTFIX_SNIPPET:-}" ]; then
  printf '%s' "$MEDIAWIKI_HOTFIX_SNIPPET" > "$STAGE/user-hotfix.php"   # verbatim; must start with <?php
  printf '%s\n' "require_once __DIR__ . '/user-hotfix.php';" >> "$STAGE/Hotfix.php"
fi

# 6. Ensure ephemeral writable paths (uploads -> S3, LocalSettings.php:442, so no uploads volume).
for d in /tmp/cache /tmp/file-cache "$WEBROOT/extensions/Widgets/compiled_templates"; do
  [ -d "$d" ] || mkdir -p "$d"
done

# 7. postrun override hook (run:48-49).
if [ -x /usr/local/bin/postrun ]; then log "running postrun hook"; /usr/local/bin/postrun; fi

# 8. Hand off. exec => FrankenPHP is PID 1 and receives SIGTERM for graceful stop.
#    Replaces run:52. NO cron line (run:46 removed on purpose).
log "exec frankenphp run --config $CADDYFILE"
exec frankenphp run --config "$CADDYFILE" --adapter caddyfile
```

---

## 4. healthz-ready.php (+ the /healthz-live handler)

Path `/srv/femiwiki.com/healthz.php` (at docroot so `require __DIR__.'/includes/WebStart.php'` resolves LocalSettings exactly like `index.php`). `/healthz-live` is answered by Caddy itself (section 2 block 1); the PHP `str_starts_with` guard is a fallback. `/healthz-ready` proves: (a) LocalSettings loaded, (b) DB `SELECT 1`, (c) memcached main-cache set/get, plus (d) the CACHE_DB session store (`objectcache` table) so a node is never marked ready while sessions are broken (closes that gap). A short APCu cache (3s) protects the shared DB/memcached if the route is ever probed too often (closes the heavy/unprotected gap).

```php
<?php
// /srv/femiwiki.com/healthz.php  (MW 1.43.8, FrankenPHP classic backend)
// Routed by the Caddyfile: /healthz-live -> Caddy respond 200; /healthz-ready -> here.

// LIVENESS fallback: cheap static 200 BEFORE any MediaWiki bootstrap.
if ( str_starts_with( $_SERVER['REQUEST_URI'] ?? '', '/healthz-live' ) ) {
	http_response_code( 200 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'Cache-Control: no-store' );
	echo "live\n";
	exit;
}

$ok = static function (): void {
	http_response_code( 200 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'Cache-Control: no-store' );
	echo "ready\n";
	exit;
};

// Cheap throttle: serve a cached positive verdict for a few seconds so probe floods cannot hammer
// the shared DB/memcached. APCu is per-process (shared across this process's ZTS threads).
$apcuKey = 'healthz-ready-ok';
if ( function_exists( 'apcu_fetch' ) ) {
	$hit = false;
	if ( apcu_fetch( $apcuKey, $hit ) && $hit === true ) {
		$ok();
	}
}

// READINESS. Mirror load.php (a session-independent entry point): no session handler.
define( 'MW_NO_SESSION', 1 );
define( 'MW_ENTRY_POINT', 'healthz' );  // not 'cli', not 'index'; Setup.php defaults unknown safely

$fail = static function ( string $component ): void {
	http_response_code( 503 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'Cache-Control: no-store' );
	echo "not-ready: {$component}\n";
	exit;
};

// (a) Bootstrap loads LocalSettings.php via Setup.php, or core die()s (=> not 200).
try {
	require __DIR__ . '/includes/WebStart.php';
} catch ( \Throwable $e ) {
	$fail( 'bootstrap' );
}

try {
	$services = \MediaWiki\MediaWikiServices::getInstance();

	// (b) DB: getConnectionProvider() (1.42+) -> replica; table-less "SELECT 1" round-trip.
	$db = $services->getConnectionProvider()->getReplicaDatabase();
	if ( (string)$db->selectField( [], '1', '', 'healthz-ready' ) !== '1' ) {
		$fail( 'db' );
	}

	$factory = $services->getObjectCacheFactory();

	// (c) Memcached main cache (CACHE_MEMCACHED, LocalSettings.php:111): set/get round-trip.
	$mainId = $services->getMainConfig()->get( \MediaWiki\MainConfigNames::MainCacheType );
	$main   = $factory->getInstance( $mainId );
	$key    = $main->makeKey( 'healthz', gethostname() ?: '?', (string)getmypid() );
	$token  = (string)hrtime( true );
	if ( !$main->set( $key, $token, 10 ) || $main->get( $key ) !== $token ) {
		$fail( 'cache' );
	}
	$main->delete( $key );

	// (d) CACHE_DB session store (objectcache table; backend-overrides moves sessions here).
	//     Proves the migration job created/works the table, so we never serve broken sessions.
	$sess = $factory->getInstance( CACHE_DB );
	$skey = $sess->makeKey( 'healthz-session', (string)getmypid() );
	if ( !$sess->set( $skey, $token, 10 ) || $sess->get( $skey ) !== $token ) {
		$fail( 'session-store' );
	}
	$sess->delete( $skey );
} catch ( \Throwable $e ) {
	$fail( get_class( $e ) ); // never leak message/stack
}

if ( function_exists( 'apcu_store' ) ) {
	apcu_store( $apcuKey, true, 3 ); // cache only the positive verdict, 3s
}
$ok();
```

Consumer-split caveat (carry into Step 2 wiring): use the cheap Caddy-answered `/healthz-live` as the continuous edge rotation check. Use `/healthz-ready` as the one-shot ASG-lifecycle startup gate, not the every-10s rotation control plane, because it depends on the shared DB+memcached (a single backend blip would fail every node at once, a cascading-failure anti-pattern). MediaWiki tolerates memcached loss by design, so if `/healthz-ready` is ever wired to remove nodes, demote the memcached check to alert-only there. Keep it internal and rate-limited.

---

## 5. LocalSettings deltas (diff-style against `dockers/femiwiki/LocalSettings.php`)

Applied via `/a/backend-overrides.php`, loaded by the existing `require_once '/a/Hotfix.php';` at `LocalSettings.php:1090`. The shared canonical `LocalSettings.php` is NOT forked.

```diff
# Sessions: MySQL instead of memcached. Makes the FrankenPHP file-session deadlock irrelevant and
# gives cross-instance continuity without sticky LB. Requires the objectcache table (created by
# update.php in the one-shot migration job). Main/parser/message stay memcached.
- LocalSettings.php:112  $wgSessionCacheType = CACHE_MEMCACHED;
+ override               $wgSessionCacheType = CACHE_DB;

# Proxy trust so WebRequest::getIP() returns the REAL client (CheckUser/AbuseFilter/blocks).
# $wgCdnServers (LocalSettings.php:145, from WG_CDN_SERVERS) is EXACT-match only AND doubles as the
# PURGE target -> keep feeding it the edge caddy-mwcache purge endpoint. For TRUST of an edge
# fleet/subnet use $wgCdnServersNoPurge (CIDR-capable via IPSet). They may overlap.
  LocalSettings.php:144  $wgUseCdn = true;                                   (unchanged)
  LocalSettings.php:145  $wgCdnServers = explode(',', getenv('WG_CDN_SERVERS'));  (unchanged)
+ override               $wgCdnServersNoPurge = array_merge($wgCdnServersNoPurge ?? [], <WG_CDN_SERVERS_NO_PURGE>);

# HTTPS behind the edge: NO MediaWiki change. $wgForceHTTPS=true (LocalSettings.php:35) does not loop
# because WebRequest::detectProtocol() trusts X-Forwarded-Proto: https. EDGE requirement: the edge
# MUST send X-Forwarded-Proto: https (and X-Forwarded-For).
  LocalSettings.php:35   $wgForceHTTPS = true;                               (unchanged)

# Baked localisation cache: trust the build-time CDBs, never rebuild lazily under threads.
+ override               $wgLocalisationCacheConf['storeDirectory'] = '/var/cache/mw-l10n';
+ override               $wgLocalisationCacheConf['manualRecache']  = true;
  LocalSettings.php:164  $wgCacheDirectory = '/tmp/cache';                   (unchanged)

# PoolCounter placeholder (OFF in Step 1). Enable once a poolcounterd is provisioned.
+ override (commented)   // $wgPoolCounterConf['ArticleView'] = [ ... ];

# Unchanged but load-bearing, verified present and correct for the split:
  LocalSettings.php:108  $wgJobRunRate = 0;        # web nodes never run jobs in-request (keep)
  LocalSettings.php:105  $wgMiserMode = true;      # => updateSpecialPages MUST run in the singleton
  LocalSettings.php:88   $wgSecretKey = getenv('WG_SECRET_KEY');  # MUST be identical across all nodes
```

Concrete `/a/backend-overrides.php` (baked at build, used by both the l10n prebuild and at runtime):

```php
<?php
// /a/backend-overrides.php — backend-only deltas. Loaded last via /a/Hotfix.php
// (LocalSettings.php:1090). Single source of truth for the FrankenPHP backend.

// 1) Sessions -> MySQL (objectcache table; created by update.php in the migration job).
$wgSessionCacheType = CACHE_DB;

// 2) Proxy trust: CIDR-capable list so WebRequest::getIP() sees the real client via XFF.
//    Edge fleet/subnet CIDRs, comma-separated. $wgCdnServers (LocalSettings.php:145) stays the
//    exact-match PURGE list, fed separately from WG_CDN_SERVERS (edge mwcache endpoint).
$__noPurge = getenv( 'WG_CDN_SERVERS_NO_PURGE' );
if ( $__noPurge !== false && $__noPurge !== '' ) {
	$wgCdnServersNoPurge = array_merge(
		$wgCdnServersNoPurge ?? [],
		array_filter( array_map( 'trim', explode( ',', $__noPurge ) ) )
	);
}
unset( $__noPurge );

// 3) Baked localisation cache: trust the build-time CDBs, never rebuild lazily under threads.
$wgLocalisationCacheConf['storeDirectory'] = '/var/cache/mw-l10n';
$wgLocalisationCacheConf['manualRecache']  = true;

// 4) PoolCounter (OFF in Step 1; enable once poolcounterd is provisioned).
// $wgPoolCounterConf['ArticleView'] = [
//   'class'   => 'MediaWiki\\PoolCounter\\PoolCounterClient',
//   'host'    => getenv( 'WG_POOLCOUNTER_HOST' ) ?: 'poolcounter',
//   'port'    => 7531, 'timeout' => 1, 'workers' => 5, 'maxqueue' => 50,
// ];
```

Localisation regression note (kept explicit): the Dockerfile prebuild defaults to **all** languages (`L10N_LANGS` empty), because with `manualRecache=true` a language absent from the store does `initShallowFallback($code,'en')` (verified `includes/language/LocalisationCache.php`), i.e. silently serves English, not the language's real fallback chain, and never rebuilds. With Translate + UniversalLanguageSelector enabled (`LocalSettings.php:868,894`) and arbitrary `uselang`, restricting to `ko,en` would render every other UI language in English. If you set `L10N_LANGS=ko,en` to shrink the image/build, you are accepting that documented regression.

Build-time helper `/a/l10n-build.php` (referenced by the Dockerfile prebuild). Reads the **staged** `/a/LocalSettings.php` (the webroot copy does not exist at build time), then forces caches OFF so the rebuild is hermetic/offline (no attempt to reach a bogus memcached from the empty `WG_MEMCACHED_SERVERS`):

```php
<?php
// Build-time config for rebuildLocalisationCache only. Loads the real, env-driven LocalSettings
// from /a (the staged canonical file; secret/upgrade keys fall back to '' at LocalSettings.php:88,91;
// no DB/memcached is needed for an l10n rebuild), applies the baked-l10n override, then forces all
// caches to CACHE_NONE so the build never touches the network.
require '/a/LocalSettings.php';
require '/a/backend-overrides.php';

// Hermetic/offline: no memcached, no DB cache during the build.
$wgMainCacheType    = CACHE_NONE;
$wgParserCacheType  = CACHE_NONE;
$wgMessageCacheType = CACHE_NONE;
$wgSessionCacheType = CACHE_NONE;
$wgMemCachedServers = [];

// CDBs must land in the baked directory (already set by backend-overrides; assert here).
$wgLocalisationCacheConf['storeDirectory'] = '/var/cache/mw-l10n';
$wgLocalisationCacheConf['manualRecache']  = true;
```

---

## 6. Local PoC (podman/docker) + exact commands

Arch note: the dev box is x86_64; the prod target is arm64/Graviton. Run functional + soak natively on x86_64 (the ZTS threading mechanism reproduces faithfully). Do a separate `--platform linux/arm64` build only to prove it links; do NOT soak under QEMU (signal/timer/segfault timing is not representative). Re-run the soak on a real Graviton host before sign-off.

The schema is created by a one-shot `migrate` service (closes the "schema never created" and "admin password never set" gaps): it runs `install.php` with `--pass "$MEDIAWIKI_ADMIN_PASS"`, then `update.php --quick` (creates the `objectcache` table for CACHE_DB sessions and every extension's schema), then `importSites.php`. The `app` service keeps all three SKIP flags (prod-shaped) and starts only after `migrate` completes successfully.

`poc/compose.yaml`:

```yaml
name: fw-frankenphp-poc
services:
  db:
    image: docker.io/mariadb:10.11
    container_name: poc-db
    environment:
      MARIADB_ROOT_PASSWORD: root
      MARIADB_DATABASE: femiwiki
    command: ['--max-connections=200']
    healthcheck:
      test: ['CMD', 'healthcheck.sh', '--connect', '--innodb_initialized']
      interval: 5s
      timeout: 3s
      retries: 30
    ports: ['13306:3306']

  memcached:
    image: docker.io/memcached:1.6-alpine
    container_name: poc-memcached
    command: ['-m', '256']

  # One-shot schema bootstrap. Same image, entrypoint overridden to migrate.sh.
  migrate:
    build:
      context: ..
      dockerfile: Dockerfile
      args:
        APP_IMAGE: ghcr.io/femiwiki/femiwiki:latest
        # Build the current image locally first if GHCR is not pullable, then pass its tag:
        #   APP_IMAGE: localhost/femiwiki:current
        L10N_LANGS: 'ko,en' # PoC: subset for fast builds. Prod default (empty) builds ALL.
    container_name: poc-migrate
    depends_on:
      db: { condition: service_healthy }
      memcached: { condition: service_started }
    restart: 'no'
    entrypoint: ['/bin/sh', '/migrate.sh']
    volumes:
      - ./migrate.sh:/migrate.sh:ro
    environment: &mwenv
      MEDIAWIKI_SERVER: 'http://localhost:8080'
      WG_DB_SERVER: 'db'
      WG_DB_USER: 'root'
      WG_DB_PASSWORD: 'root'
      WG_MEMCACHED_SERVERS: 'memcached:11211'
      WG_SECRET_KEY: '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef'
      WG_UPGRADE_KEY: '0123456789abcdef'
      WG_INTERNAL_SERVER: 'http://localhost:8080'
      WG_CDN_SERVERS: '127.0.0.1'
      WG_CDN_SERVERS_NO_PURGE: '10.0.0.0/8,127.0.0.1'
      MEDIAWIKI_ADMIN_USER: 'Admin'
      MEDIAWIKI_ADMIN_PASS: 'poc_admin_pw_123'

  app:
    build:
      context: ..
      dockerfile: Dockerfile
      args:
        APP_IMAGE: ghcr.io/femiwiki/femiwiki:latest
        L10N_LANGS: 'ko,en'
    container_name: poc-app
    depends_on:
      db: { condition: service_healthy }
      memcached: { condition: service_started }
      migrate: { condition: service_completed_successfully }
    restart: 'no' # a crash must STAY down and be visible during the soak
    environment:
      <<: *mwenv
      SERVER_NAME: ':8080'
      # SOAK SIZING: 4 threads so a Soak request and a CpuBomb run in sibling threads at once.
      FRANKENPHP_NUM_THREADS: '4'
      # PoC-only: disable AWS(S3)/captcha/CDN via a TARGETED hotfix (NOT MEDIAWIKI_DEBUG_MODE, whose
      # debug toolbar + display_errors would make the soak latency/RSS unrepresentative).
      MEDIAWIKI_HOTFIX_SNIPPET: "<?php require __DIR__ . '/poc-overrides.php';"
    volumes:
      - ./poc-overrides.php:/a/poc-overrides.php:ro
    ports: ['8080:8080']
```

`poc/migrate.sh` (one-shot; closes schema + admin-password gaps):

```sh
#!/bin/sh
set -eu
log() { printf 'migrate: %s\n' "$*"; }

cd /srv/femiwiki.com
ADMIN_USER="${MEDIAWIKI_ADMIN_USER:-Admin}"
ADMIN_PASS="${MEDIAWIKI_ADMIN_PASS:?MEDIAWIKI_ADMIN_PASS is required for the PoC install}"

log "install.php (schema + admin user '$ADMIN_USER')"
php maintenance/install.php \
  --lang ko \
  --scriptpath '/w' \
  --dbtype mysql \
  --dbname femiwiki \
  --dbserver "${WG_DB_SERVER}" \
  --dbuser "${WG_DB_USER}" \
  --dbpass "${WG_DB_PASSWORD}" \
  --installdbuser "${WG_DB_USER}" \
  --installdbpass "${WG_DB_PASSWORD}" \
  --pass "$ADMIN_PASS" \
  '페미위키' "$ADMIN_USER"

# Use the canonical env-driven settings + backend overrides + PoC overrides for update.php, so the
# CACHE_DB objectcache table and every extension's schema are created exactly as the app will use.
log "materialize LocalSettings.php + Hotfix.php"
cp -f /a/LocalSettings.php /srv/femiwiki.com/LocalSettings.php
printf '%s\n' "<?php require_once __DIR__ . '/backend-overrides.php'; require_once __DIR__ . '/poc-overrides.php';" > /a/Hotfix.php

log "update.php --quick (objectcache + extension schema)"
php maintenance/update.php --quick

log "importSites.php"
php maintenance/importSites.php /a/site-list.xml

log "done"
```

`poc/poc-overrides.php` (targeted, replaces `MEDIAWIKI_DEBUG_MODE`; closes the unrepresentative-soak gap):

```php
<?php
// PoC-only deltas. Disable cloud/anti-abuse integrations that cannot work on a laptop, WITHOUT
// enabling DevelopmentSettings.php (no debug toolbar, no display_errors) so soak latency/RSS stay
// representative of production. Mounted at /a/poc-overrides.php and required from Hotfix.php.

// No S3: ext:AWS off so uploads/thumbs use the local filesystem (LocalSettings.php:439-444).
$wgAWSBucketName   = null;
$wgAWSBucketPrefix = null;

// No reCAPTCHA on a laptop.
$wgCaptchaTriggers['createaccount'] = false;
$wgCaptchaTriggers['edit']          = false;
$wgCaptchaTriggers['create']        = false;
$wgCaptchaTriggers['badlogin']      = false;

// Plain-HTTP PoC: no CDN pur520ging, allow anon edit so the smoke test can write without email-confirm.
$wgUseCdn             = false;
$wgEmailConfirmToEdit = false;

// Keep error visibility production-like (do NOT turn on display_errors).
```

Build, run, wait for readiness:

```bash
cd femiwiki-frankenphp/poc

podman compose build migrate app
podman compose up -d                 # db -> memcached -> migrate (runs once) -> app
podman compose logs -f migrate       # watch the one-shot schema bootstrap finish
podman compose logs -f app           # Ctrl-C once you see "exec frankenphp run" / serving :8080

# block until readiness is 200 (no foreground sleep)
until [ "$(curl -fsS -o /dev/null -w '%{http_code}' http://localhost:8080/healthz-ready)" = 200 ]; do
  echo "waiting for readiness..."; command sleep 3 || true
done
echo READY

# extension presence (ZTS) + the MW-1.43 core requirements
podman exec poc-app php -m | grep -Ei 'luasandbox|wikidiff2|apcu|intl|mysqli|sockets|calendar|opcache|mbstring|^dom$|xmlreader|simplexml|gd|exif|pcntl'
podman exec poc-app php -r 'echo "ZTS=", PHP_ZTS, "\n";'

# arm64 PARITY BUILD ONLY (slow, link proof; do NOT benchmark under QEMU)
podman run --rm --privileged docker.io/tonistiigi/binfmt --install arm64
podman build --platform linux/arm64 -f ../Dockerfile -t localhost/fw-frankenphp:arm64 ..
```

Functional checks (now including `/rest.php` PATH_INFO and `/load.php`):

```bash
# serves pages
curl -fsS -o /dev/null -w 'index.php -> %{http_code}\n' "http://localhost:8080/index.php"
curl -fsS "http://localhost:8080/index.php?title=Special:Version" | grep -qi MediaWiki && echo "Special:Version OK"

# api.php siteinfo
curl -fsS "http://localhost:8080/api.php?action=query&meta=siteinfo&siprop=general&format=json" \
  | python3 -m json.tool | grep -E '"generator"|"sitename"'

# load.php (ResourceLoader): startup module must be 200 + non-empty JS
curl -fsS "http://localhost:8080/load.php?modules=startup&only=scripts&raw=1" \
  | grep -qi 'mw.loader' && echo "load.php OK"

# rest.php PATH_INFO: core REST route must resolve (PATH_INFO populated under php_server)
curl -fsS -o /dev/null -w 'rest.php -> %{http_code}\n' \
  "http://localhost:8080/rest.php/v1/page/Main_Page"     # expect 200 (or 404 with JSON body, NOT a PHP routing error)

# health route matrix
curl -s -o /dev/null -w 'ready(up)=%{http_code}\n' http://localhost:8080/healthz-ready    # 200
curl -s -o /dev/null -w 'live(up)=%{http_code}\n'  http://localhost:8080/healthz-live     # 200
podman compose stop db
curl -s -o /dev/null -w 'ready(db-down)=%{http_code}\n' http://localhost:8080/healthz-ready  # 503
curl -s -o /dev/null -w 'live(db-down)=%{http_code}\n'  http://localhost:8080/healthz-live   # 200
podman compose start db
podman compose stop memcached
curl -s -o /dev/null -w 'ready(mc-down)=%{http_code}\n' http://localhost:8080/healthz-ready  # 503 (after the 3s APCu cache expires)
podman compose start memcached

# proxy trust + X-Forwarded-Proto (no redirect loop on the plain-HTTP backend)
curl -s -o /dev/null -w 'xfp-https -> %{http_code} %{redirect_url}\n' \
  -H 'X-Forwarded-Proto: https' -H 'X-Forwarded-For: 203.0.113.7' \
  "http://localhost:8080/index.php?title=Special:Version"   # expect 200, no 301 loop
```

Login + CSRF edit (`poc/functional.sh`; admin password read from the same env the install used):

```bash
#!/bin/bash
set -euo pipefail
BASE="${BASE:-http://localhost:8080}/api.php"; JAR=$(mktemp)
USER="${MEDIAWIKI_ADMIN_USER:-Admin}"; PASS="${MEDIAWIKI_ADMIN_PASS:-poc_admin_pw_123}"
j(){ python3 -c 'import sys,json;d=json.load(sys.stdin);print(eval(sys.argv[1]))' "$1"; }
LT=$(curl -fsS -c "$JAR" -b "$JAR" "$BASE?action=query&meta=tokens&type=login&format=json" | j 'd["query"]["tokens"]["logintoken"]')
curl -fsS -c "$JAR" -b "$JAR" -X POST "$BASE" \
  --data-urlencode action=clientlogin --data-urlencode format=json \
  --data-urlencode loginreturnurl="${BASE%/api.php}/" \
  --data-urlencode "username=$USER" --data-urlencode "password=$PASS" \
  --data-urlencode "logintoken=$LT" | python3 -m json.tool
CSRF=$(curl -fsS -c "$JAR" -b "$JAR" "$BASE?action=query&meta=tokens&format=json" | j 'd["query"]["tokens"]["csrftoken"]')
[ "$CSRF" != '+\' ] || { echo "FAIL: anonymous csrf (login failed)"; exit 1; }
curl -fsS -c "$JAR" -b "$JAR" -X POST "$BASE" \
  --data-urlencode action=edit --data-urlencode format=json \
  --data-urlencode title=PoC_Smoke \
  --data-urlencode "text=PoC edit $(date -u +%FT%TZ)." \
  --data-urlencode summary=poc --data-urlencode "token=$CSRF" | python3 -m json.tool
rm -f "$JAR"   # expect {"edit":{"result":"Success",...}}
```

DB-connections-per-request (connection-budget input, D1):

```bash
before=$(podman exec poc-db mariadb -uroot -proot -N -e "SHOW GLOBAL STATUS LIKE 'Connections'" | awk '{print $2}')
curl -fsS -o /dev/null "http://localhost:8080/api.php?action=parse&format=json&uselang=en&text=<!--$RANDOM-->{{%23invoke:Soak|run|5000000}}"
after=$(podman exec poc-db mariadb -uroot -proot -N -e "SHOW GLOBAL STATUS LIKE 'Connections'" | awk '{print $2}')
echo "connects/render: $((after-before))"
podman exec poc-db mariadb -uroot -proot -e "FLUSH STATUS;"
# ... run section 7 k6 for ~2 min ...
podman exec poc-db mariadb -uroot -proot -N -e \
  "SHOW GLOBAL STATUS WHERE Variable_name IN ('Threads_connected','Max_used_connections','Aborted_connects')"
# Budget: fleet_DB ~= nodes * num_threads * connects_per_request (+ migration job + edge purge).
```

---

## 7. luasandbox ZTS concurrency soak + LuaStandalone fallback

The central risk: luasandbox enforces `cpuLimit=3` / `memoryLimit=50MiB` (`LocalSettings.php:805-807`) with per-thread CPU clocks (`pthread_getcpuclockid` + `SIGEV_THREAD` + a global rwlock-protected timer table). The soak proves that holds under concurrent Lua-running threads in one FrankenPHP process. In classic mode any single thread segfault takes the whole process and all in-flight requests with it (no FPM child isolation), so this is the load-bearing correctness gate.

The soak is sized to make overlap real (closes the "may not actually overlap" gap): `FRANKENPHP_NUM_THREADS=4`, `bomb` VUs=1, `soak` VUs=3, so a CpuBomb occupies exactly one thread for ~3s while three Soak requests run in sibling threads. `Module:Soak` is a bounded loop tuned to ~1-1.5s CPU (well under the 3s limit) so two Soak requests genuinely overlap inside luasandbox. Caddy backpressure 503s (`max_wait_time`) are counted separately and excluded from the crash metric. `uselang=en` forces the timeout message to a stable English string the assertions can match. The real Lua source ships in `poc/lua-content.sh`.

`poc/lua-content.sh` (creates the modules with the same cookie/login flow as `functional.sh`):

```bash
#!/bin/bash
set -euo pipefail
BASE="${BASE:-http://localhost:8080}/api.php"; JAR=$(mktemp)
USER="${MEDIAWIKI_ADMIN_USER:-Admin}"; PASS="${MEDIAWIKI_ADMIN_PASS:-poc_admin_pw_123}"
j(){ python3 -c 'import sys,json;d=json.load(sys.stdin);print(eval(sys.argv[1]))' "$1"; }

LT=$(curl -fsS -c "$JAR" -b "$JAR" "$BASE?action=query&meta=tokens&type=login&format=json" | j 'd["query"]["tokens"]["logintoken"]')
curl -fsS -c "$JAR" -b "$JAR" -X POST "$BASE" --data-urlencode action=clientlogin --data-urlencode format=json \
  --data-urlencode loginreturnurl="${BASE%/api.php}/" \
  --data-urlencode "username=$USER" --data-urlencode "password=$PASS" --data-urlencode "logintoken=$LT" >/dev/null
CSRF=$(curl -fsS -c "$JAR" -b "$JAR" "$BASE?action=query&meta=tokens&format=json" | j 'd["query"]["tokens"]["csrftoken"]')

mkedit(){ # $1=title  $2=text
  curl -fsS -c "$JAR" -b "$JAR" -X POST "$BASE" \
    --data-urlencode action=edit --data-urlencode format=json \
    --data-urlencode "title=$1" --data-urlencode "text=$2" \
    --data-urlencode summary=soak-setup --data-urlencode "token=$CSRF" >/dev/null
  echo "created $1"
}

# Bounded CPU loop, ~1-1.5s on a modern core (TUNE the default n for your CPU so it stays < cpuLimit 3s).
mkedit "Module:Soak" "$(cat <<'LUA'
local p = {}
function p.run(frame)
  local n = tonumber(frame.args[1]) or 5000000
  local x = 0
  for i = 1, n do x = (x + i * 7 + 3) % 2147483647 end
  return tostring(x)
end
return p
LUA
)"

# Infinite loop: MUST trip the 3s cpuLimit -> Scribunto throws scribunto-common-timeout.
mkedit "Module:CpuBomb" "$(cat <<'LUA'
local p = {}
function p.run(frame)
  local x = 0
  while true do x = x + 1 end
  return tostring(x)
end
return p
LUA
)"
rm -f "$JAR"
```

`poc/soak.js` (k6):

```javascript
import http from 'k6/http';
import { check } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const BASE = __ENV.BASE || 'http://localhost:8080';
const SOAK_N = __ENV.SOAK_N || '5000000'; // tune so Module:Soak burns ~1-1.5s CPU
const soakLat = new Trend('soak_latency', true);
const bombLat = new Trend('bomb_latency', true);
const soakBadTimeout = new Counter('soak_spurious_timeout'); // cross-thread timer bleed
const bombNoTimeout = new Counter('bomb_missing_timeout'); // per-thread timer failed to fire
const http5xx = new Counter('http_5xx'); // segfault/500 reaching clients
const backpressure = new Counter('backpressure_503'); // Caddy max_wait_time, NOT a crash

export const options = {
  scenarios: {
    soak: {
      executor: 'constant-vus',
      vus: 3,
      duration: __ENV.DUR || '30m',
      exec: 'soak',
    },
    bomb: {
      executor: 'constant-vus',
      vus: 1,
      duration: __ENV.DUR || '30m',
      exec: 'bomb',
    },
  },
  thresholds: {
    soak_spurious_timeout: ['count==0'],
    bomb_missing_timeout: ['count==0'],
    http_5xx: ['count==0'],
    soak_latency: ['p(99)<3000'], // tune to a recorded single-request baseline
  },
};
// uselang=en makes the timeout message a stable English string we can match.
function parse(t) {
  return http.get(
    `${BASE}/api.php?action=parse&format=json&uselang=en&contentmodel=wikitext&disablelimitreport=1&text=${encodeURIComponent(t)}`,
  );
}
function classify(r) {
  if (r.status === 503) {
    backpressure.add(1);
    return false;
  }
  if (r.status >= 500) {
    http5xx.add(1);
  }
  return true;
}
const MARK =
  /time allocated for running scripts|scribunto-common-timeout|exceeded the time/i;
export function soak() {
  const r = parse(`<!--${Math.random()}-->{{#invoke:Soak|run|${SOAK_N}}}`);
  soakLat.add(r.timings.duration);
  if (classify(r) && MARK.test(r.body)) soakBadTimeout.add(1);
  check(r, { 200: (x) => x.status === 200 || x.status === 503 });
}
export function bomb() {
  const r = parse(`<!--${Math.random()}-->{{#invoke:CpuBomb|run}}`);
  bombLat.add(r.timings.duration);
  if (classify(r) && !MARK.test(r.body)) bombNoTimeout.add(1);
}
```

```bash
podman run --rm -i --network host -e BASE=http://localhost:8080 -e DUR=30m -e SOAK_N=5000000 \
  -v "$PWD/soak.js:/soak.js:ro" docker.io/grafana/k6 run /soak.js
```

Watch during the run (separate terminals):

```bash
podman logs -f poc-app 2>&1 | grep -Ei 'segfault|panic|signal SIG|fatal|out of memory|oom'
dmesg -w | grep -i 'segfault\|general protection\|traps:'
watch -n5 'podman stats --no-stream poc-app'                       # RSS must plateau (no pm.max_requests recycle)
watch -n5 'podman exec poc-app sh -c "ls /proc/1/task | wc -l"'    # thread count stable
watch -n2 'podman inspect -f "{{.State.Status}} exit={{.State.ExitCode}} restarts={{.RestartCount}}" poc-app'
```

Pass/fail criteria:

| #   | Check                         | PASS                                                                                                        | FAIL                                                     |
| --- | ----------------------------- | ----------------------------------------------------------------------------------------------------------- | -------------------------------------------------------- |
| F1  | Build (native + arm64 parity) | both build; all ZTS `.so` present incl. MW core reqs; `PHP_ZTS=1`                                           | any missing / arm64 link error                           |
| F2  | Cron guard                    | build fails if cron installed; `ENABLE_CRON=1` exits non-zero; no `cron`/`crond` in image                   | cron present or starts                                   |
| F3  | Serves pages                  | `index.php`, `Special:Version`, siteinfo, `load.php`, `rest.php/v1/...` all correct                         | any non-200/wrong body or REST routing error             |
| F4  | Login + CSRF edit             | `clientlogin` Pass with the installed admin pass; non-anon csrf; `edit` `"result":"Success"`                | login/edit fails or `badtoken`                           |
| F5  | Health routes                 | `/healthz-live` 200 always (incl. DB down); `/healthz-ready` 200 healthy, 503 DB-down and memcached-down    | wrong code, or live depends on DB                        |
| F6  | Proxy trust                   | `X-Forwarded-Proto: https` -> 200 no loop; real client IP via XFF                                           | redirect loop or edge IP recorded                        |
| S1  | No crashes (the gate)         | 30-min soak (threads=4, soak3/bomb1): no exit/restart; zero segfault/panic/SIG/OOM; `http_5xx==0`           | any crash/restart/5xx (excl. backpressure_503)           |
| S2  | Per-thread CPU timer          | `bomb_missing_timeout==0`; CpuBomb latency ~3s (cpuLimit), not ~30s nor unbounded                           | bomb hangs / 500 / ~30s                                  |
| S3  | No cross-thread timer bleed   | `soak_spurious_timeout==0` while CpuBomb saturates a sibling thread                                         | any Soak request spuriously times out                    |
| S4  | Latency                       | `soak_latency p99` within target (< ~3x single-request baseline)                                            | p99 regresses / climbs over time                         |
| S5  | Memory stability              | RSS plateaus after warmup; thread count stable                                                              | unbounded RSS/thread growth                              |
| S6  | LuaStandalone fallback        | identical soak on `luastandalone` passes S1-S3                                                              | fallback also fails => app-level problem                 |
| D1  | Connection budget             | connects/request ~1-2; peak `Max_used_connections` ~= `num_threads x connects/req`; `Aborted_connects` flat | per-request connects high, or peak >> num_threads (leak) |

Go/No-Go for Step 2: all F\* and S1-S3 + D1 PASS on native x86_64, then S1-S4 re-confirmed on a real arm64/Graviton host. Break-glass mitigations to keep documented: `USE_ZEND_ALLOC=0` (ZTS-on-ARM64 allocator reports) and the LuaStandalone engine.

LuaStandalone fallback toggle (`poc/Hotfix-standalone.php`; mount over `/a/Hotfix.php` after the entrypoint writes it, or pass via `MEDIAWIKI_HOTFIX_SNIPPET`):

```php
<?php
require_once __DIR__ . '/backend-overrides.php';
// Break-glass engine: forks a lua5.1 process per request via proc_open (no in-process ZTS Lua).
// Functionally first-class but materially slower (per-request IPC, higher DB-thread occupancy);
// acceptable as a fallback, NOT a steady state. Valid keys: 'luasandbox' | 'luastandalone'.
$wgScribuntoDefaultEngine = 'luastandalone';
$wgScribuntoEngineConf['luastandalone']['cpuLimit']    = 3;          // matches LocalSettings.php:805
$wgScribuntoEngineConf['luastandalone']['memoryLimit'] = 52428800;   // matches LocalSettings.php:807
```

```bash
podman compose stop app
# add the volume + MEDIAWIKI_HOTFIX_SNIPPET to require Hotfix-standalone.php, then re-run the same k6 soak.
# Expect: no in-process Lua threading concern, higher latency / more processes, same S2/S3 correctness.
```

---

## 8. Phase-0 / Step-1 prep checklist

COMMIT (the drafted files, no production change):

- [ ] `Dockerfile`, `Caddyfile`, `entrypoint.sh`, `zz-frankenphp.ini`, `healthz-ready.php`, `backend-overrides.php`, `l10n-build.php`, plus `poc/` (`compose.yaml`, `migrate.sh`, `poc-overrides.php`, `functional.sh`, `lua-content.sh`, `Hotfix-standalone.php`, `soak.js`).
- [ ] Pin the FrankenPHP base by digest (resolve `dunglas/frankenphp:1.12-php8.3-bookworm`); keep `composer:2.8.6` (matches `php-fpm/Dockerfile:4`).
- [ ] Keep pinned `luasandbox-4.1.3` (floor 4.1.1 per T322748), `wikidiff2-1.14.1`, `apcu-5.1.24` (single consistent value); confirm each tag exists and builds ZTS on arm64; bump only after re-verifying.
- [ ] Confirm `install-php-extensions` is present in the chosen FrankenPHP tag (the build asserts this; `ADD` it pinned if absent).

VALIDATE locally (podman/docker, no AWS):

- [ ] Native x86_64: F1-F6 all PASS; record a single-request latency baseline; confirm `migrate` ran once and created the `objectcache` table.
- [ ] luasandbox ZTS soak: S1-S3 + D1 PASS (30 min, threads=4); capture RSS/thread plots for S4/S5; confirm `backpressure_503` is excluded from the crash metric.
- [ ] arm64 parity build links cleanly and shows all required ZTS `.so` + `PHP_ZTS=1` (build-only; not benchmarked under QEMU).
- [ ] Confirm `$wgJobRunRate=0` (`LocalSettings.php:108`) survives the port and the build-time cron-absence assertion fires if cron is reintroduced.
- [ ] Confirm `X-Forwarded-Proto: https` produces no redirect loop and the real client IP is seen via XFF.
- [ ] Confirm `/rest.php/v1/...` and `/load.php` are correct (PATH_INFO + ResourceLoader).

EXPLICITLY OUT OF SCOPE (deploy-time, Step 2+, not in this image):

- [ ] The edge Caddy (TLS/Let's Encrypt via route53, certmagic-s3, caddy-mwcache + `purge_acl`, security headers, gzip, `@block_cidr`/`@filter` WAF). Stays the edge build (`dockers/caddy`), unchanged. Backend drops all of `Caddyfile:3,10-12,17-37,47-76`.
- [ ] The PROD one-shot migration job (the `poc/migrate.sh` is the PoC shape only): `install.php` / `update.php --quick` / `importSites.php` (`run:10-43`) + the `objectcache` table for CACHE_DB sessions + driving `$wgBlockTargetMigrationStage` (`LocalSettings.php:194`) to completion. Runs exactly once, not per node.
- [ ] The singleton job/sitemap/specialpages runner replacing the removed in-image cron. Must run ALL THREE (`mediawiki/cron`): `run-jobs` (`runJobs.php --maxtime 60`), `generate-sitemap` (`generateSitemap.php --fspath sitemap`, output published to S3 or routed by the edge since web nodes will not see local `sitemap/` files), and `update-special-pages` (`updateSpecialPages.php`, required because `$wgMiserMode=true`, `LocalSettings.php:105`, serves QueryPages from `querycache`). Monitored SPOF: exactly-one, auto-restart, alert on queue depth.
- [ ] Real secret provisioning (SSM/S3) replacing the PoC env/`*_FILE` path in `entrypoint.sh`; `$wgSecretKey` (`LocalSettings.php:88`) identical across all nodes.
- [ ] ASG sizing / DB `max_connections` budget from the D1 number; whether to enable `max_threads` autoscaling and a `frankenphp { max_requests N }` container-recycle threshold (the two no-classic-equivalent watch items: no `pm.max_requests`, no `request_terminate_timeout`); lower the shipped `FRANKENPHP_NUM_THREADS` default to fit the chosen instance.
- [ ] Edge active-health-check wiring: `/healthz-live` as continuous rotation, `/healthz-ready` as the internal one-shot ASG-lifecycle startup gate.
- [ ] PoolCounter daemon provisioning before uncommenting `$wgPoolCounterConf`.
- [ ] If building `L10N_LANGS` as a subset in prod, accept the documented English shallow-fallback; otherwise keep the default (all languages) and budget the larger image/build time.
- [ ] Re-run S1-S4 on a real Graviton host before any production cutover.

---

## Gap-closure map

| Gap (severity)                                          | Fix                                                                                                      | Where                                       |
| ------------------------------------------------------- | -------------------------------------------------------------------------------------------------------- | ------------------------------------------- |
| l10n wrong LocalSettings path (build-breaking)          | read `/a/LocalSettings.php` (staged), not the webroot                                                    | `l10n-build.php`; Dockerfile prebuild RUN   |
| cron/php-fpm assertion self-aborts on comments          | strip comments before grep, match a real token, cron-detection at FS level only                          | Dockerfile build-assertion RUN              |
| PoC schema never created                                | one-shot `migrate` service runs install/update/importSites; app keeps SKIP flags                         | `poc/compose.yaml`, `poc/migrate.sh`        |
| Admin password never set                                | install with `--pass "$MEDIAWIKI_ADMIN_PASS"`; `functional.sh` reads same env                            | `poc/migrate.sh`, `poc/functional.sh`       |
| l10n built ko,en only (English fallback)                | default builds ALL languages (`L10N_LANGS` empty); subset documented as a conscious regression           | Dockerfile `ARG L10N_LANGS`; section 5 note |
| opcache too small (4000 files)                          | `zz-frankenphp.ini`: 50000 files / 512M / interned 32 + realpath cache                                   | `zz-frankenphp.ini`                         |
| l10n build non-hermetic                                 | force CACHE_NONE + clear memcached after loading LocalSettings                                           | `l10n-build.php`                            |
| soak under DEBUG_MODE                                   | targeted `poc-overrides.php` (no debug toolbar) instead of `MEDIAWIKI_DEBUG_MODE`                        | `poc/poc-overrides.php`, `compose.yaml`     |
| soak overlap not guaranteed                             | threads=4, soak3/bomb1, ~1.5s Module:Soak, real Lua source, 503 excluded from crash metric, `uselang=en` | `soak.js`, `lua-content.sh`, `compose.yaml` |
| rest.php PATH_INFO untested                             | dedicated `@pathstyle` handle + `/rest.php/v1/...` functional test                                       | Caddyfile, functional checks                |
| verification omits MW core ext reqs                     | assert mbstring/dom/xml/xmlreader/simplexml/ctype/fileinfo/iconv/libxml/openssl/json/filter              | Dockerfile verify loop                      |
| NUM_THREADS unguarded                                   | sizing math documented; entrypoint soft RAM warning; recommend lowering until D1                         | Caddyfile, Dockerfile, entrypoint step 2b   |
| readiness heavy/unprotected                             | APCu 3s positive-result cache; keep internal/one-shot                                                    | `healthz-ready.php`                         |
| readiness ignores CACHE_DB session store                | add `getInstance(CACHE_DB)` set/get check                                                                | `healthz-ready.php` (d)                     |
| apcu pin inconsistency / install-php-extensions assumed | single `apcu-5.1.24` everywhere; assert installer presence                                               | Dockerfile                                  |
| gd/exif absent, PDF coder blocked                       | install `gd`+`exif`; relax ImageMagick PDF policy                                                        | Dockerfile                                  |
| /load.php + pcntl untested                              | `/load.php` smoke test; install `pcntl` and use it for threads with a `--threads=1` fallback             | functional checks, Dockerfile               |

Relevant absolute paths (current image, read-only, used to verify every citation):
`/home/nemo/git/fw/docker-mediawiki/dockers/femiwiki/{run,Dockerfile,Caddyfile,LocalSettings.php,Hotfix.php,site-list.xml,prerun,postrun}`, `/home/nemo/git/fw/docker-mediawiki/dockers/php-fpm/{Dockerfile,php.ini,opcache-recommended.ini,www.conf}`, `/home/nemo/git/fw/docker-mediawiki/dockers/mediawiki/{Dockerfile,cron/crontab,cron/run-jobs,cron/generate-sitemap,cron/update-special-pages}`.
