#!/usr/bin/env bash
# Print one ```wikitext block merging the release notes of the given pull requests.
# A pull request's own ```wikitext lines are copied as they are, so the link to
# where a change was first made survives every bump after it; one without a
# block gives a line made from a feat, fix or perf title scoped to an image.
#
# Usage: release-notes.sh OWNER/REPO PR_NUMBER...
# Environment: GH_TOKEN
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
  block="$(jq -r '.body // ""' <<< "$pr" | tr -d '\r' \
    | awk -v f="$fence" 'index($0, f "wikitext") == 1 { on = 1; next } on && index($0, f) == 1 { on = 0 } on')"
  if [ -n "$block" ]; then
    type=''
    while IFS= read -r line; do
      if [[ "$line" =~ ^===\ *([^=]+[^=\ ])\ *===$ ]]; then
        type="${BASH_REMATCH[1]}"
        [[ " ${order[*]} " == *" ${type} "* ]] || order+=("$type")
      elif [[ "$line" == \** ]] && [ -n "$type" ]; then
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
