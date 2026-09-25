<?php
// /srv/femiwiki.com/healthz.php  (MW 1.43.8, FrankenPHP classic backend)
// Routed by the Caddyfile: /healthz-live -> Caddy respond 200; /healthz-ready -> here.

// LIVENESS fallback: cheap static 200 BEFORE any MediaWiki bootstrap.
if ( str_starts_with( $_SERVER['REQUEST_URI'] ?? '', '/healthz-live' ) ) {
	http_response_code( 200 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'Cache-Control: no-store' );
	echo "live\n";
	exit;
}

$ok = static function (): void {
	http_response_code( 200 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'Cache-Control: no-store' );
	echo "ready\n";
	exit;
};

// Cheap throttle: serve a cached positive verdict for a few seconds so probe floods cannot hammer
// the shared DB/memcached. APCu is per-process (shared across this process's ZTS threads).
$apcuKey = 'healthz-ready-ok';
if ( function_exists( 'apcu_fetch' ) ) {
	$hit = false;
	if ( apcu_fetch( $apcuKey, $hit ) && $hit === true ) {
		$ok();
	}
}

// READINESS. Mirror load.php (a session-independent entry point): no session handler.
define( 'MW_NO_SESSION', 1 );
// not 'cli', not 'index'; Setup.php defaults unknown safely
define( 'MW_ENTRY_POINT', 'healthz' );

$fail = static function ( string $component ): void {
	http_response_code( 503 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'Cache-Control: no-store' );
	echo "not-ready: {$component}\n";
	exit;
};

// (a) Bootstrap loads LocalSettings.php via Setup.php, or core die()s (=> not 200).
try {
	require __DIR__ . '/includes/WebStart.php';
} catch ( \Throwable $e ) {
	$fail( 'bootstrap' );
}

try {
	$services = \MediaWiki\MediaWikiServices::getInstance();

	// (b) DB: getConnectionProvider() (1.42+) -> replica; table-less "SELECT 1" round-trip.
	$db = $services->getConnectionProvider()->getReplicaDatabase();
	if ( (string)$db->selectField( [], '1', '', 'healthz-ready' ) !== '1' ) {
		$fail( 'db' );
	}

	$factory = $services->getObjectCacheFactory();

	// (c) Memcached main cache (CACHE_MEMCACHED, LocalSettings.php:111): set/get round-trip.
	$mainId = $services->getMainConfig()->get( \MediaWiki\MainConfigNames::MainCacheType );
	$main   = $factory->getInstance( $mainId );
	$key    = $main->makeKey( 'healthz', gethostname() ?: '?', (string)getmypid() );
	$token  = (string)hrtime( true );
	if ( !$main->set( $key, $token, 10 ) || $main->get( $key ) !== $token ) {
		$fail( 'cache' );
	}
	$main->delete( $key );

	// (d) CACHE_DB session store (objectcache table; backend-overrides moves sessions here).
	//     Proves the migration job created/works the table, so we never serve broken sessions.
	$sess = $factory->getInstance( CACHE_DB );
	$skey = $sess->makeKey( 'healthz-session', (string)getmypid() );
	if ( !$sess->set( $skey, $token, 10 ) || $sess->get( $skey ) !== $token ) {
		$fail( 'session-store' );
	}
	$sess->delete( $skey );
} catch ( \Throwable $e ) {
	// never leak message/stack
	$fail( get_class( $e ) );
}

if ( function_exists( 'apcu_store' ) ) {
	// cache only the positive verdict, 3s
	apcu_store( $apcuKey, true, 3 );
}
$ok();
