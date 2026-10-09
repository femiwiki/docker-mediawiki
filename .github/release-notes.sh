#!/usr/bin/env bash
# Prints one ```wikitext block merging the pull requests' own blocks, or a line
# from each feat, fix or perf title scoped to an image when there is none. Lines
# above any ===type=== go under the type of the pull request's title.
# Usage: GH_TOKEN=... release-notes.sh OWNER/REPO PR_NUMBER...
set -euo pipefail
# shellcheck source=.github/wikitext.sh
source "$(dirname "$0")/wikitext.sh"
repo=$1
shift
images="$(gh api "repos/${repo}/contents/dockers" --jq '.[] | select(.type == "dir") | .name' | paste -sd '|')"
for n in "$@"; do
  pr="$(gh api "repos/${repo}/pulls/${n}")"
  title="$(jq -r .title <<< "$pr")"
  link="https://github.com/${repo}/pull/${n}"
  # An empty block says there is nothing for readers, so no line from the title either
  if jq -r '.body // ""' <<< "$pr" | wikitext_lines "${title%%[(!:]*}" "$link"; then
    :
  elif [[ "$title" =~ ^(feat|fix|perf)\((${images})\)!?:\ (.+)$ ]]; then
    printf '%s\t*%s [%s]\n' "${BASH_REMATCH[1]}" "${BASH_REMATCH[3]}" "$link"
  fi
done | wikitext_block
