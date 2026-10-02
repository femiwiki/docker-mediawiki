<?php
// /a/backend-overrides.php — backend-only deltas. Loaded last via /a/Hotfix.php
// (LocalSettings.php:1090). Single source of truth for the FrankenPHP backend.

// 1) Sessions -> MySQL (objectcache table; created by update.php in the migration job).
$wgSessionCacheType = CACHE_DB;

// 2) Proxy trust: CIDR-capable list so WebRequest::getIP() sees the real client via XFF.
//    Edge fleet/subnet CIDRs, comma-separated. $wgCdnServers (LocalSettings.php:145) stays the
//    exact-match PURGE list, fed separately from WG_CDN_SERVERS (edge mwcache endpoint).
$__noPurge = getenv( 'WG_CDN_SERVERS_NO_PURGE' );
if ( $__noPurge !== false && $__noPurge !== '' ) {
	$wgCdnServersNoPurge = array_merge(
		$wgCdnServersNoPurge ?? [],
		array_filter( array_map( 'trim', explode( ',', $__noPurge ) ) )
	);
}
unset( $__noPurge );

// 3) Baked localisation cache: trust the build-time CDBs, never rebuild lazily under threads.
$wgLocalisationCacheConf['storeDirectory'] = '/var/cache/mw-l10n';
$wgLocalisationCacheConf['manualRecache']  = true;

// 4) PoolCounter (OFF in Step 1; enable once poolcounterd is provisioned).
// $wgPoolCounterConf['ArticleView'] = [
//   'class'   => 'MediaWiki\\PoolCounter\\PoolCounterClient',
//   'host'    => getenv( 'WG_POOLCOUNTER_HOST' ) ?: 'poolcounter',
//   'port'    => 7531, 'timeout' => 1, 'workers' => 5, 'maxqueue' => 50,
// ];
