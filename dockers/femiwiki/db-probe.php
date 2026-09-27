<?php
/**
 * Checks that this container can reach the database, once, and then never again.
 *
 * The fastcgi healthcheck asks php-fpm for /var/www/livez and never touches the
 * database, so a generation whose WG_DB_SERVER points somewhere broken turns
 * healthy, `wait = true` on the container is satisfied, create_before_destroy
 * drains the generation that worked, and the wiki loses its database with every
 * check green. See femiwiki/infra#880.
 *
 * Once, rather than on every check, because `autoheal` restarts an unhealthy
 * container: a database outage would otherwise flap the app tier instead of
 * serving errors. The marker sits in the container's own filesystem, which a
 * restart keeps and only a replacement loses, and a replacement is the one
 * moment WG_DB_SERVER can change.
 */

$marker = '/tmp/db-checked';
if ( file_exists( $marker ) ) {
	exit( 0 );
}

// php-fpm's credentials are PID 1's: run exports them before starting php-fpm
// and stays as PID 1 to reap it. Reading them here keeps the password out of a
// second file, which a healthcheck would otherwise need because it does not
// inherit what the entrypoint exported.
$environFile = '/proc/1/environ';
$environ = is_readable( $environFile ) ? file_get_contents( $environFile ) : false;
if ( $environ === false ) {
	fwrite( STDERR, "db-probe: cannot read $environFile\n" );
	exit( 1 );
}

$env = [];
foreach ( explode( "\0", $environ ) as $pair ) {
	if ( $pair === '' ) {
		continue;
	}
	$parts = explode( '=', $pair, 2 );
	if ( count( $parts ) === 2 ) {
		$env[$parts[0]] = $parts[1];
	}
}

$server = $env['WG_DB_SERVER'] ?? '';
$user = $env['WG_DB_USER'] ?? '';
if ( $server === '' || $user === '' ) {
	fwrite( STDERR, "db-probe: WG_DB_SERVER or WG_DB_USER is not set\n" );
	exit( 1 );
}

// The value is host:port, and an IPv6 literal would carry colons of its own, so
// split on the last one and only when what follows is a port.
$port = 3306;
$colon = strrpos( $server, ':' );
if ( $colon !== false && ctype_digit( substr( $server, $colon + 1 ) ) ) {
	$port = (int)substr( $server, $colon + 1 );
	$server = substr( $server, 0, $colon );
}

// Exceptions rather than return values, so a failure carries mysqli's own
// message into the healthcheck's output instead of a bare false.
mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

try {
	$connection = mysqli_init();
	// Shorter than the healthcheck's own timeout, so a hung connect reports
	// rather than being killed and leaving nothing behind.
	$connection->options( MYSQLI_OPT_CONNECT_TIMEOUT, 5 );
	// The charset the application pins, so this exercises the path it uses.
	$connection->options( MYSQLI_SET_CHARSET_NAME, 'binary' );
	$connection->real_connect(
		$server,
		$user,
		$env['WG_DB_PASSWORD'] ?? '',
		$env['WG_DB_NAME'] ?? 'femiwiki',
		$port
	);
	$connection->query( 'SELECT 1' );
	$connection->close();
} catch ( mysqli_sql_exception $e ) {
	fwrite(
		STDERR,
		"db-probe: $server:$port did not answer the application's credentials: " .
			$e->getMessage() . "\n"
	);
	exit( 1 );
}

// Best effort: a container that cannot write the marker checks every time,
// which is noisier than intended but not wrong.
if ( !touch( $marker ) ) {
	fwrite( STDERR, "db-probe: reached the database but could not write $marker\n" );
}
exit( 0 );
