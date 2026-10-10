#!/usr/bin/env bash
# Commits a bump pull request's change again on top of the checked-out commit,
# for when another bump merged first and took the version it wrote. The README
# entry it adds goes on top under the next version, raised by the same part it
# raised; the rest applies as a three-way merge, and a conflict there fails.
# Usage: replay-bump.sh PR_HEAD
set -euo pipefail
head=$1
base="$(git merge-base HEAD "$head")"
readmes=':(glob)dockers/*/README.md'
rest=':(exclude,glob)dockers/*/README.md'

top() { git show "$1:$2" | grep -m 1 '^## v' | cut -c5-; }

if ! git diff --quiet "$base" "$head" -- . "$rest"; then
  git diff --binary "$base" "$head" -- . "$rest" | git apply --3way --index
fi

for readme in $(git diff --name-only "$base" "$head" -- "$readmes"); do
  old="$(top "$base" "$readme")"
  IFS=. read -r oma omi opa <<< "$old"
  IFS=. read -r nma nmi npa <<< "$(top "$head" "$readme")"
  IFS=. read -r ma mi pa <<< "$(top HEAD "$readme")"
  if [ "$nma" != "$oma" ]; then
    next="$((ma + 1)).0.0"
  elif [ "$nmi" != "$omi" ]; then
    next="${ma}.$((mi + 1)).0"
  elif [ "$npa" != "$opa" ]; then
    next="${ma}.${mi}.$((pa + 1))"
  else
    echo "::error file=${readme}::${head} changes ${readme} but not its version" >&2
    exit 1
  fi
  # The lines the pull request put above the entry it started from
  entry="$(git show "$head:$readme" | awk -v old="## v${old}" '$0 == old { exit } /^## v/ { n++ } n')"
  if [ "$(grep -c '^## v' <<< "$entry")" != 1 ]; then
    echo "::error file=${readme}::${head} adds more than one entry to ${readme}" >&2
    exit 1
  fi
  {
    awk '/^## v/ { exit } 1' "$readme"
    echo "## v${next}"
    tail -n +2 <<< "$entry"
    echo
    awk '/^## v/ { on = 1 } on' "$readme"
  } > "${readme}.tmp"
  mv -f "${readme}.tmp" "$readme"
  git add "$readme"
  echo "${readme}: v$(top "$head" "$readme") is now v${next}"
done

git commit -q -C "$head"
