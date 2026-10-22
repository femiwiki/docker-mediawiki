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

# Prints the ```wikitext block of the PR body in file $1 plus the stdin block's lines whose links it never offered, and
# a comment listing the links offered, so a rebuild keeps lines edited by hand. Nothing when neither has a line.
wikitext_carry() {
  local fence=$'\x60\x60\x60' marker='wikitext links:' old new offered="" line type="" last="" url fresh added=""
  local -A known
  old="$(tr -d '\r' < "$1")"
  new="$(cat)"
  links() { grep -oE '\[https://[^] ]+' | cut -c2-; }
  inside() { awk -v f="$fence" 'index($0, f "wikitext") == 1 { on = 1; next } on && index($0, f) == 1 { on = 0 } on'; }
  for url in $(sed -nE "s/^<!-- ${marker} (.*) -->\$/\1/p" <<< "$old") $(inside <<< "$old" | links); do
    known[$url]=1
  done
  old="$(inside <<< "$old" | sed -e :a -e '/^\n*$/{$d;N;ba' -e '}')"
  while IFS= read -r line; do
    if [[ "$line" =~ ^===\ *([^=]+[^=\ ])\ *===$ ]]; then
      type="${BASH_REMATCH[1]}"
    elif [[ "$line" == \** ]]; then
      fresh=""
      for url in $(links <<< "$line"); do
        [ -n "${known[$url]:-}" ] || fresh=1
      done
      [ -n "$fresh" ] || continue
      if [ "$type" != "$last" ]; then
        added+=$'\n'"===${type}==="$'\n\n'
        last=$type
      fi
      added+="${line}"$'\n'
    fi
  done < <(inside <<< "$new")
  for url in $( (printf '%s\n' "${!known[@]}"; inside <<< "$new" | links) | sort -u); do
    offered+=" $url"
  done
  [ -n "${old}${added}" ] || return 0
  printf '<!-- %s%s -->\n' "$marker" "$offered"
  printf '%swikitext\n' "$fence"
  if [ -n "$old" ]; then
    printf '%s\n%s%s\n' "$old" "$added" "$fence"
  else
    printf '%s%s\n' "${added#$'\n'}" "$fence"
  fi
}
