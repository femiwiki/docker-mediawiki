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
