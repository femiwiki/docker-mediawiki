# poolcounter

[PoolCounter](https://www.mediawiki.org/wiki/PoolCounter), the lock daemon that
stops MediaWiki parsing the same page in several processes at once. There is no
source to build: Debian packages it, so this only installs that package.

Listens on 7531. `$wgPoolCounterConf` points at it.

## v1.0.0

- poolcounter 1.2.0-1 from Debian trixie
