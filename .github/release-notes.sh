#!/usr/bin/env bash
# Prints one ```wikitext block merging the pull requests' own blocks, or a line
# from each feat, fix or perf title scoped to an image when there is none. Lines
# above any ===type=== go under the type of the pull request's title.
# Usage: GH_TOKEN=... release-notes.sh OWNER/REPO PR_NUMBER...
set -euo pipefail
repo=$1
shift
fence=$'\x60\x60\x60'
images="$(gh api "repos/${repo}/contents/dockers" --jq '.[] | select(.type == "dir") | .name' | paste -sd '|')"
declare -A lines=()
order=(feat fix perf)
for n in "$@"; do
  pr="$(gh api "repos/${repo}/pulls/${n}")"
  title="$(jq -r .title <<< "$pr")"
  body="$(jq -r '.body // ""' <<< "$pr" | tr -d '\r')"
  block="$(awk -v f="$fence" 'index($0, f "wikitext") == 1 { on = 1; next } on && index($0, f) == 1 { on = 0 } on' <<< "$body")"
  # An empty block says there is nothing for readers, so no line from the title either
  if grep -q "^${fence}wikitext" <<< "$body"; then
    type="${title%%[(!:]*}"
    [[ " ${order[*]} " == *" ${type} "* ]] || order+=("$type")
    while IFS= read -r line; do
      if [[ "$line" =~ ^===\ *([^=]+[^=\ ])\ *===$ ]]; then
        type="${BASH_REMATCH[1]}"
        [[ " ${order[*]} " == *" ${type} "* ]] || order+=("$type")
      elif [[ "$line" == \** ]]; then
        [[ "$line" =~ \]$ ]] || line+=" [https://github.com/${repo}/pull/${n}]"
        lines[$type]+="${line}"$'\n'
      fi
    done <<< "$block"
  elif [[ "$title" =~ ^(feat|fix|perf)\((${images})\)!?:\ (.+)$ ]]; then
    lines[${BASH_REMATCH[1]}]+="*${BASH_REMATCH[3]} [https://github.com/${repo}/pull/${n}]"$'\n'
  fi
done
[ ${#lines[@]} -gt 0 ] || exit 0
echo "${fence}wikitext"
for type in "${order[@]}"; do
  [ -n "${lines[$type]:-}" ] || continue
  printf '===%s===\n\n%s\n\n' "$type" "$(printf '%s' "${lines[$type]}" | awk '!seen[$0]++')"
done
echo "$fence"
