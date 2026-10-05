# femiwiki

## v2.0.27

- Bump femiwiki/femiwiki-extensions to v3.5.3
  - follow the browser's default font size (Femiwiki f6519a5) (femiwiki/FemiwikiSkin#1021)

## v2.0.26

- Bump femiwiki/femiwiki-extensions to v3.5.2
  - Update namespace of hook (UnifiedExtensionForFemiwiki b569e3a) (femiwiki/UnifiedExtensionForFemiwiki#216)

## v2.0.25

- Bump femiwiki/femiwiki-extensions to v3.5.0
  - define Codex CSS custom properties with Femiwiki colors (Femiwiki 7af371b) (femiwiki/FemiwikiSkin#1027)

## v2.0.24

- Bump femiwiki/femiwiki-extensions to v3.4.0
  - replace the search box with Codex TypeaheadSearch (Femiwiki 1f56536) (femiwiki/FemiwikiSkin#1026)

## v2.0.23

- Bump femiwiki/femiwiki-extensions to v3.2.13
  - 문서 정보의 조회수 다시 표시 (femiwiki/docker-mediawiki#1346)

## v2.0.22

- Bump femiwiki/femiwiki-extensions to v3.2.11
  - keep top bar icons beside their labels in RTL (Femiwiki cda3f17) (femiwiki/FemiwikiSkin#1024)

## v2.0.21

- Bump femiwiki/femiwiki-extensions to v3.2.10

## v2.0.20

- Bump femiwiki/femiwiki-extensions to v3.2.9

## v2.0.19

- Bump femiwiki/femiwiki-extensions to v3.2.8
  - 인물 정보 상자 등에서 위키데이터 내용이 가끔 비어 보이던 문제 수정 (femiwiki/docker-mediawiki#1325)

## v2.0.18

- Bump femiwiki/femiwiki-extensions to v3.2.7

## v2.0.17

- Bump femiwiki/caddy to v1.10.1

## v2.0.16

- Bump femiwiki/femiwiki-extensions to v3.2.6

## v2.0.15

- Bump femiwiki/caddy to v1.10.0
  - put the request's host in the cache key (caddy-mwcache 4dffb41) (femiwiki/caddy-mwcache#171)

## v2.0.14

- Bump femiwiki/caddy to v1.9.0
  - serve load.php from the cache to requests with a session cookie (caddy-mwcache 485f578) (femiwiki/caddy-mwcache#167)
  - never store a HEAD response (caddy-mwcache 485f578) (femiwiki/caddy-mwcache#169)

## v2.0.13

- Bump femiwiki/caddy to v1.8.2
  - 컨테이너 교체 직후에도 요청 제한이 이어지도록 함 (femiwiki/docker-mediawiki#1308)

## v2.0.12

- Bump femiwiki/mediawiki to v4.0.2

## v2.0.11

- Bump femiwiki/femiwiki-extensions to v3.2.5
  - 보안 키와 패스키를 등록할 수 없던 문제 수정 (femiwiki/docker-mediawiki#1298)

## v2.0.10

- Bump femiwiki/femiwiki-extensions to v3.2.4
  - Skip the external link hook when Parsoid calls it (UnifiedExtensionForFemiwiki 5e4039c) (femiwiki/UnifiedExtensionForFemiwiki#259)

## v2.0.9

- Bump femiwiki/femiwiki-extensions to v3.2.3

## v2.0.8

- Bump femiwiki/femiwiki-extensions to v3.2.2
  - Fixes deprecation warnings in 1.46 (Femiwiki 53af529) (femiwiki/FemiwikiSkin#925)

## v2.0.7

- Bump femiwiki/caddy to v1.8.1
  - write cached responses that have an empty body (caddy-mwcache 59f5bd6) (femiwiki/caddy-mwcache#166)

## v2.0.6

- Bump femiwiki/femiwiki-extensions to v3.2.1
  - 교집합분류검색 오류 수정 (femiwiki/docker-mediawiki#1279)

## v2.0.5

- Bump femiwiki/femiwiki-extensions to v3.2.0
  - 쓰이지 않게 된 HTMLTags 확장 기능 제거 (femiwiki/docker-mediawiki#1276)

## v2.0.4

- Bump femiwiki/femiwiki-extensions to v3.1.2
  - the Femiwiki skin's mediawiki.ui.button and mobile.init skinStyles compile on 1.46 (femiwiki/FemiwikiSkin#1009)
- Stop loading Wikibase's REST route file, which 1.46 no longer ships (#1260)

## v2.0.3

- Bump femiwiki/mediawiki to v4.0.1 (MediaWiki 1.46.2)

## v2.0.2

- Read old CollaborationKit revisions with core's fallback handler so dumpBackup.php finishes (ported from #1249)

## v2.0.1

- Set OAuth to the local user ID source explicitly, which OAuth 1.46 asks for instead of `$wgMWOAuthSharedUserIDs = false`

## v2.0.0

- Bump femiwiki/mediawiki to v4.0.0 (MediaWiki 1.46.1) and femiwiki/femiwiki-extensions to v3.1.0
- Stop loading Interwiki, which MediaWiki 1.46 ships in core
- Load CommunityConfiguration, which GrowthExperiments requires on 1.46
- Drop settings that MediaWiki 1.46 and its extensions removed; GrowthExperiments now always adds its confirmation email notice to the account creation form

## v1.7.17

- Bump femiwiki/femiwiki-extensions to v2.7.5
  - follow the LESS files 1.46 moved (Femiwiki a468700) (femiwiki/FemiwikiSkin#1009)

## v1.7.16

- Bump femiwiki/caddy to v1.8.0
  - store pages gzipped (caddy-mwcache 88764d1) (femiwiki/caddy-mwcache#164)

## v1.7.15

- Bump femiwiki/mediawiki to v3.6.0

## v1.7.14

- Bump femiwiki/mediawiki to v3.5.4

## v1.7.13

- Bump femiwiki/mediawiki to v3.5.3
  - MediaWiki 1.43.9 → 1.43.10 (femiwiki/docker-mediawiki#1229)

## v1.7.12

- Bump femiwiki/caddy to v1.7.1

## v1.7.11

- Bump femiwiki/caddy to v1.7.0
  - add a static block that caches static files by their version hash (caddy-mwcache c38104e) (femiwiki/caddy-mwcache#160)

## v1.7.10

- Bump femiwiki/mediawiki to v3.5.2

## v1.7.9

- Bump femiwiki/femiwiki-extensions to v2.7.4

## v1.7.8

- Bump femiwiki/femiwiki-extensions to v2.7.3

## v1.7.7

- Bump femiwiki/caddy to v1.6.2

## v1.7.6

- Bump femiwiki/femiwiki-extensions to v2.7.2

## v1.7.5

- Bump femiwiki/caddy to v1.6.1

## v1.7.4

- Bump femiwiki/femiwiki-extensions to v2.7.1
  - install UnifiedExtensionForFemiwiki from a commit (femiwiki/docker-mediawiki#1191)

## v1.7.3

- Bump femiwiki/femiwiki-extensions to v2.7.0
  - install the Femiwiki skin from a commit (femiwiki/docker-mediawiki#1185)

## v1.7.2

- Bump femiwiki/femiwiki-extensions to v2.6.0
  - drop Extension:Lockdown (femiwiki/docker-mediawiki#1171)
  - (내부 작업) 미디어위키 확장 기능을 빌드 시점의 브랜치 최신판 대신 커밋 단위로 고정해 설치하도록 변경 (femiwiki/docker-mediawiki#1179)

## v1.7.1

- Bump femiwiki/mediawiki to v3.5.0
  - say on stdout when the sitemap has been rebuilt (femiwiki/docker-mediawiki#1169)

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
