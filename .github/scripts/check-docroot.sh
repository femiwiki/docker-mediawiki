#!/usr/bin/env bash
# Usage: check-docroot.sh CONTAINER [BASE_URL], for what the web root serves (#1333)
set -euo pipefail
c=$1
base=${2:-http://127.0.0.1:8080}
fail=0

# check STATUS PATH...
check() {
  local want=$1 path code
  shift
  for path in "$@"; do
    code="$(curl -s -o /dev/null -w '%{http_code}' "$base$path")"
    if [ "$code" = "$want" ]; then
      echo "$path: $code"
    else
      echo "::error::$path: $code, want $want"
      fail=1
    fi
  done
}

# One real file of each kind, read off the image so a renamed file cannot pass
pick() {
  local f
  f="$(docker exec "$c" sh -c "cd /srv/docroot && find -L $1 -type f -name '$2' | sort | head -1")"
  [ -n "$f" ] || { echo "::error::No $2 under /srv/docroot/$1" >&2; exit 1; }
  echo "$f"
}
asset_resources="/$(pick resources '*.png')"
asset_skins="/$(pick skins '*.svg')"
asset_extensions="/$(pick extensions '*.svg')"
extension_code="/$(pick extensions '*.php')"

dangling="$(docker exec "$c" find /srv/docroot -xtype l)"
if [ -n "$dangling" ]; then
  echo "::error::Links in /srv/docroot that lead nowhere: $dangling"
  fail=1
fi

check 200 \
  '/api.php?action=query&meta=siteinfo&format=json' \
  '/load.php?modules=startup&only=scripts&raw=1' \
  '/rest.php/v1/search/title?q=a&limit=1' \
  /healthz.php \
  /robots.txt \
  /429.html \
  /fw-resources/favicons/favicon-32.png \
  "$asset_resources" "$asset_skins" "$asset_extensions"

# MediaWiki's own redirect to /rest.php/v1/search, so the entry point was reached
check 308 /opensearch_desc.php

check 404 \
  /vendor/autoload.php \
  /vendor/composer/installed.json \
  /includes/Defines.php \
  /maintenance/update.php \
  /tests/phpunit/phpunit.php \
  /composer.json \
  /composer.lock \
  /composer.local.json \
  /LocalSettings.php \
  /Caddyfile \
  /UPGRADE \
  /mw-config/ \
  /resources/Resources.php \
  "$extension_code" \
  /no-such-file
exit "$fail"
