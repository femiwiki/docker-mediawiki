<?php
// Fails while this container cannot reach the database, so a container whose
// WG_DB_SERVER is wrong stops reporting healthy: it used to satisfy the docker
// provider's wait = true, which then destroyed the generation that worked. See
// femiwiki/infra#880.
//
// Not part of /healthz.php, which Caddy serves to the public and no rate-limit
// zone matches, and not under /srv/femiwiki.com at all, so the only caller is
// the probe pool on 127.0.0.1. Run inside php-fpm because WG_DB_PASSWORD is
// exported by run after it starts and so is absent from the healthcheck's own
// environment; the probe pool carries it because of clear_env = no.

// Returns null when the database answered, the reason otherwise.
$reach = static function (): ?string {
	$server = getenv( 'WG_DB_SERVER' ) ?: '';
	$user = getenv( 'WG_DB_USER' ) ?: '';
	if ( $server === '' || $user === '' ) {
		return 'no database configured';
	}
	$password = getenv( 'WG_DB_PASSWORD' ) ?:
		( getenv( 'DB_PASSWORD_FILE' ) ? trim( file_get_contents( getenv( 'DB_PASSWORD_FILE' ) ) ) : '' );

	// host:port, and an IPv6 literal carries colons of its own, so split on the
	// last one and only when what follows is a port.
	$port = 3306;
	$colon = strrpos( $server, ':' );
	if ( $colon !== false && ctype_digit( substr( $server, $colon + 1 ) ) ) {
		$port = (int)substr( $server, $colon + 1 );
		$server = substr( $server, 0, $colon );
	}

	mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
	try {
		$connection = mysqli_init();
		$connection->options( MYSQLI_OPT_CONNECT_TIMEOUT, 3 );
		// The charset the application pins, so this is the path it uses.
		$connection->options( MYSQLI_SET_CHARSET_NAME, 'binary' );
		// The name is fixed in LocalSettings.php, not configurable.
		$connection->real_connect( $server, $user, $password, 'femiwiki', $port );
		$connection->query( 'SELECT 1' );
		$connection->close();
	} catch ( mysqli_sql_exception $e ) {
		return $e->getMessage();
	}
	return null;
};

$reached = '/tmp/databasez-reached';
$failing = '/tmp/databasez-failing';

// Only success is remembered, so an outage does not make every container on
// every host unhealthy at once for as long as it lasts.
if ( file_exists( $reached ) ) {
	echo "ok\n";
	return;
}

$reason = $reach();
if ( $reason === null ) {
	touch( $reached );
	if ( file_exists( $failing ) ) {
		unlink( $failing );
	}
	echo "ok\n";
	return;
}
// The log, not the body: mysqli names the account and the host that was refused.
error_log( 'databasez: database unreachable: ' . $reason );

// Bounded, because a container that first starts while the database is away
// would otherwise never pass and autoheal would restart it every few minutes,
// where with no check at all it would have served the moment the database came
// back. 900s outlasts the docker provider's wait_timeout of 600s, so an apply
// against a wrong address still fails rather than draining the good generation.
if ( file_exists( $failing ) ) {
	$first = filemtime( $failing );
} else {
	touch( $failing );
	$first = time();
}
if ( time() - $first >= 900 ) {
	echo "ok (unreachable for 900s, giving up on the check)\n";
	return;
}

http_response_code( 503 );
echo "database unreachable\n";
