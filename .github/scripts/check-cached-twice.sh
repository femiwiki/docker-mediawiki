#!/usr/bin/env bash
# Requests each kind of page twice through Caddy's cache, plain and gzipped, and
# fails unless every answer is what MediaWiki itself gives: a 301 with the same
# Location, or a 200 with a body. The second request is the cache hit, which is
# what returned an empty 200 for every redirect in femiwiki/caddy-mwcache#165.
# Usage: check-cached-twice.sh [BASE_URL]
set -euo pipefail
base=${1:-http://127.0.0.1:8080}
main="$(curl -sSf "$base/api.php?action=query&meta=siteinfo&format=json" | jq -r .query.general.base)"
body="$(mktemp)"
fail=0

# check PATH STATUS [LOCATION]: an empty LOCATION wants any, but the same each time
check() {
  local path=$1 want=$2 want_loc=${3:-} first_loc='' enc i code loc opts
  for enc in identity gzip; do
    # --compressed both asks for gzip and decodes it, so the body is checked as read
    opts=()
    [ "$enc" = identity ] || opts=(--compressed)
    for i in 1 2; do
      read -r code loc < <(curl -s "${opts[@]}" -o "$body" -w '%{http_code} %{redirect_url}\n' "$base$path")
      first_loc=${first_loc:-$loc}
      if [ "$code" != "$want" ] ||
        { [ "$want" = 301 ] && [ "$loc" != "${want_loc:-$first_loc}" ]; } ||
        { [ "$want" = 301 ] && [ -z "$loc" ]; } ||
        { [ "$want" = 200 ] && [ ! -s "$body" ]; }; then
        echo "::error::$path ($enc, request $i): $code, Location '$loc', $(stat -c %s "$body") bytes; want $want${want_loc:+ to $want_loc}"
        fail=1
      else
        echo "$path ($enc, request $i): $code${loc:+ to $loc}, $(stat -c %s "$body") bytes"
      fi
    done
  done
}

check / 301 "$main"
check /w/Special:Version 301
check '/index.php?title=Special:Version' 301
check "${main#"$base"}" 200
exit "$fail"
