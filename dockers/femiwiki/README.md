# femiwiki

## v1.7.0

- Give cron's jobs the environment they run under. Cron passes on nothing it
  inherits, so every maintenance script was failing to reach the database and
  the sitemap volume has been empty since 2025-08-31. `run` now writes the
  environment to a root-only file and names it in the crontab's `BASH_ENV`.
- Keep `UnlinkedWikibaseFetch` off the every-minute job runner and give it a
  slower entry of its own, 20 jobs every 5 minutes, so that fetches queued while
  the rest of the backlog drains cannot all leave for Wikidata at once. Meant for
  the transition rather than for good: femiwiki#589 puts it back. Both the types
  and the rate come from the environment: `FW_JOB_TYPES_OFF_DEFAULT_QUEUE`,
  `FW_JOB_SLOW_TYPES`, `FW_JOB_SLOW_MAXJOBS` and `FW_JOB_SLOW_SCHEDULE`.

## v1.6.0

- Refuse the three api.php calls the skin makes on every article view, the talk
  topic list, the watcher count and the related-articles links, from the same
  clients `FW_BOTLIKE` already covers. `FW_API_QUERY` holds the pattern.
  opensearch is not in it, so the search box is untouched.

## v1.5.0

- Refuse searches, diffs, old revisions and page history from a client whose
  headers contradict the browser its User-Agent claims to be. Plain article
  reads, api.php and `action=raw` are not in the set, and a request carrying a
  session cookie is never tested. `FW_BOTLIKE` holds the whole test and
  `FW_EXPENSIVE_QUERY` the paths it covers, so either can change by an apply.

## v1.4.12

- Bump femiwiki/mediawiki to v3.4.5

## v1.4.11

- Bump femiwiki/caddy to v1.6.0

## v1.4.10

- Bump femiwiki/caddy to v1.5.3

## v1.4.9

- Bump femiwiki/caddy to v1.5.2

## v1.4.8

- Bump femiwiki/caddy to v1.5.1

## v1.4.7

- Bump femiwiki/caddy to v1.5.0

## v1.4.6

- Bump femiwiki/femiwiki-extensions to v2.4.3

## v1.4.5

- Bump femiwiki/caddy to v1.4.0

## v1.4.4

- Bump femiwiki/femiwiki-extensions to v2.4.2

## v1.4.3

- Bump femiwiki/femiwiki-extensions to v2.4.1

## v1.4.2

- Bump femiwiki/mediawiki to v3.4.4

## v1.4.1

- Bump femiwiki/femiwiki-extensions to v2.4.0

## v1.4.0

- Read BLOCKED_CIDR from env and block.

## v1.3.19

- Bump femiwiki/femiwiki-extensions to v2.3.1

## v1.3.18

- Bump femiwiki/mediawiki to v3.4.3

## v1.3.17

- Bump femiwiki/femiwiki-extensions to v2.3.0

## v1.3.16

- Bump femiwiki/mediawiki to v3.4.1

## v1.3.15

- Bump femiwiki/femiwiki-extensions to v2.2.7

## v1.3.14

- Bump femiwiki/femiwiki-extensions to v2.2.6

## v1.3.13

- Bump femiwiki/femiwiki-extensions to v2.2.5

## v1.3.12

- Bump femiwiki/femiwiki-extensions to v2.2.4

## v1.3.11

- Bump femiwiki/femiwiki-extensions to v2.2.3

## v1.3.10

- Bump femiwiki/femiwiki-extensions to v2.2.2

## v1.3.9

- Bump femiwiki/femiwiki-extensions to v2.2.1

## v1.3.8

- Bump femiwiki/mediawiki to v3.4.0

## v1.3.7

- Bump femiwiki/femiwiki-extensions to v2.2.0

## v1.3.6

- Bump femiwiki/mediawiki to v3.3.0

## v1.3.5

- Bump femiwiki/mediawiki to v3.2.3

## v1.3.4

- Rollback femiwiki/caddy to v1.2.0

## v1.3.3

- Bump femiwiki/caddy to v1.3.1

## v1.3.2

- Bump femiwiki/mediawiki to v3.2.2

## v1.3.1

- Bump femiwiki/caddy to v1.3.0

## v1.3.0

- Bump femiwiki/mediawiki to v3.1.0
- Use luaSandbox and set luastandalone

## v1.2.3

- Bump femiwiki/femiwiki-extensions to v1.5.2

## v1.2.2

- `chmod` before executing scripts

## v1.2.1

- Add scripts to override

## v1.2.0

- Bump femiwiki/mediawiki to v3.0.0
- Bump femiwiki/femiwiki-extensions to v1.5.0

## v1.1.2

- Composer install for TemplateStyles [https://phabricator.wikimedia.org/T363063]

## v1.1.1

- Bump femiwiki/mediawiki to v2.0.0
- Run Composer
- Download extensions from femiwiki/femiwiki-extensions v1.1.1
- Embed LocalSettings.php in this image
- Load TorBlock, RealMe, GoogleNewsSitemap

## v1.1.0

- Bump femiwiki/mediawiki to v1.1.0
