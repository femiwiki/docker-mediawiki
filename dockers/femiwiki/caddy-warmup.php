<?php
/**
 * Cuts the warm-up config out of the adapted Caddyfile: storage and the tls
 * app, told to manage the site's names, plus one HTTPS server on loopback
 * that caddy-run handshakes with until the certificate is in the cache, and
 * an admin endpoint on a socket of its own so the reload that follows cannot
 * land on another Caddy on this host.
 *
 * Usage: php caddy-warmup.php <adapted config json> <warm-up port>
 *   writes the warm-up config to stdout and a name to handshake with to
 *   /tmp/caddy-warmup-name
 */

$config = json_decode( file_get_contents( $argv[1] ), true );
$port = $argv[2];

$names = [];
foreach ( $config['apps']['tls']['automation']['policies'] ?? [] as $policy ) {
	foreach ( $policy['subjects'] ?? [] as $subject ) {
		$names[$subject] = true;
	}
}
$plain = array_filter( array_keys( $names ), static fn ( $n ) => !str_contains( $n, '*' ) );
if ( !$plain ) {
	fwrite( STDERR, "no TLS subjects in the config\n" );
	exit( 1 );
}
file_put_contents( '/tmp/caddy-warmup-name', reset( $plain ) );

$warmup = [
	'admin' => [ 'listen' => 'unix//run/caddy-warmup.sock' ],
	'apps' => [
		'tls' => $config['apps']['tls'],
		'http' => [ 'servers' => [ 'warmup' => [
			'listen' => [ "127.0.0.1:$port" ],
			'automatic_https' => [ 'disable' => true ],
			'tls_connection_policies' => [ (object)[] ],
			'routes' => [ [ 'handle' => [ [ 'handler' => 'static_response', 'status_code' => 200 ] ] ] ],
		] ] ],
	],
];
$warmup['apps']['tls']['certificates']['automate'] = array_keys( $names );
foreach ( [ 'storage', 'logging' ] as $key ) {
	if ( isset( $config[$key] ) ) {
		$warmup[$key] = $config[$key];
	}
}
echo json_encode( $warmup, JSON_UNESCAPED_SLASHES );
