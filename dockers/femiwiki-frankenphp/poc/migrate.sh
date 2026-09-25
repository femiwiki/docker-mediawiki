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
