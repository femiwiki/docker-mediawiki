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
