# shellcheck shell=bash
# The ```wikitext blocks that carry a change to 페미위키:업데이트. Source it.

# Prints each "* ..." or "(분류) ..." line of the blocks in the PR body on stdin as TYPE<tab>*LINE, TYPE being the
# ===heading=== above it or else $1, and a line without a link gets $2. Fails when the body has no block.
wikitext_lines() {
  local type=$1 link=$2 fence=$'\x60\x60\x60' body line
  local prefixed='^\((추가|수정|성능 개선|보안 패치|내부 변화|버그)\) '
  body="$(tr -d '\r')"
  grep -q "^${fence}wikitext" <<< "$body" || return 1
  while IFS= read -r line; do
    if [[ "$line" =~ ^===\ *([^=]+[^=\ ])\ *===$ ]]; then
      type="${BASH_REMATCH[1]}"
    elif [[ "$line" == \** || "$line" =~ $prefixed ]]; then
      [[ "$line" == \** ]] || line="*${line}"
      [[ "$line" =~ \]$ ]] || line+=" [${link}]"
      printf '%s\t%s\n' "$type" "$line"
    fi
  done < <(awk -v f="$fence" 'index($0, f "wikitext") == 1 { on = 1; next } on && index($0, f) == 1 { on = 0 } on' <<< "$body")
}

# Prints one ```wikitext block of the TYPE<tab>LINE lines on stdin, a ===TYPE===
# section each, feat, fix and perf first, or nothing when there are no lines.
wikitext_block() {
  awk -F '\t' -v f=$'\x60\x60\x60' '
    BEGIN { n = split("feat fix perf", order, " "); for (i = 1; i <= n; i++) known[order[i]] = 1 }
    !seen[$0]++ {
      if (!($1 in known)) { order[++n] = $1; known[$1] = 1 }
      lines[$1] = lines[$1] substr($0, length($1) + 2) "\n"
      any = 1
    }
    END {
      if (!any) exit
      print f "wikitext"
      for (i = 1; i <= n; i++) if (lines[order[i]] != "") printf "===%s===\n\n%s\n", order[i], lines[order[i]]
      print f
    }'
}
