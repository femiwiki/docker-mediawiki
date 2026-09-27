<?php
// Asks the probe pool to run databasez.php and fails unless it answers 200, so
// the container healthcheck covers the database as well as php-fpm.
//
// fcgi-probe.php cannot: its SCRIPT_FILENAME is fixed at /var/www/livez, which
// answers for php-fpm alone. See femiwiki/infra#880.
require '/srv/fcgi-check/AdoyFastCgiClient.php';

[ $host, $port ] = explode( ':', getenv( 'FCGI_URL' ) ?: '127.0.0.1:9100' );

$client = new Adoy\FastCGI\Client( $host, (int)$port );
// Under the healthcheck's own timeout, so a hung answer reports rather than
// being killed with nothing said.
$client->setReadWriteTimeout( 5000 );

try {
	$response = $client->request( [
		'REQUEST_METHOD' => 'GET',
		'SERVER_PROTOCOL' => 'HTTP/1.1',
		'GATEWAY_INTERFACE' => 'CGI/1.1',
		'REMOTE_ADDR' => '127.0.0.1',
		'REMOTE_PORT' => '0',
		'SCRIPT_FILENAME' => '/srv/fcgi-check/databasez.php',
		'SCRIPT_NAME' => '/databasez.php',
		'REQUEST_URI' => '/databasez.php',
		'QUERY_STRING' => '',
	], '' );
} catch ( Throwable $e ) {
	fwrite( STDERR, "databasez-probe: $host:$port did not answer: " . $e->getMessage() . "\n" );
	exit( 1 );
}

// php-fpm sends a Status header only when the answer is not a plain 200, and
// only in the header block, so a body that begins a line with Status: is not an
// error. Same reading as warm-up.php.
$parts = explode( "\r\n\r\n", $response, 2 );
if ( preg_match( '/^Status:\s*(?!200)/mi', $parts[0] ) ) {
	fwrite( STDERR, 'databasez-probe: ' . trim( $parts[1] ?? $parts[0] ) . "\n" );
	exit( 1 );
}

exit( 0 );
