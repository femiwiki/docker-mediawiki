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
