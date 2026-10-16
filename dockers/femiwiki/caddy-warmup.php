<?php
/**
 * Cuts the warm-up config out of the adapted Caddyfile: storage and the tls
 * app, told to manage the site's names, plus the HTTPS site on a loopback port
 * that caddy-run handshakes with until the certificate is in the cache and
 * then checks the real pages through, and an admin endpoint on a socket of its
 * own so the reload that follows cannot land on another Caddy on this host.
 * The site keeps its routes but not its rate limits: the check is not a
 * visitor, and a budget the crawlers spent would fail it.
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

// Objects this time: a handler's empty {} has to stay an object, or Caddy refuses it
$site = (object)[ 'routes' => [ [ 'handle' => [ [ 'handler' => 'static_response', 'status_code' => 200 ] ] ] ] ];
foreach ( json_decode( file_get_contents( $argv[1] ) )->apps->http->servers ?? [] as $server ) {
	if ( preg_grep( '/:443$/', $server->listen ?? [] ) ) {
		$site = $server;
		break;
	}
}
$dropRateLimits = static function ( $node ) use ( &$dropRateLimits ): void {
	if ( is_object( $node ) && isset( $node->handle ) ) {
		$node->handle = array_values( array_filter(
			$node->handle,
			static fn ( $h ) => ( $h->handler ?? '' ) !== 'rate_limit'
		) );
	}
	if ( is_object( $node ) || is_array( $node ) ) {
		foreach ( $node as $child ) {
			$dropRateLimits( $child );
		}
	}
};
$dropRateLimits( $site );
$site->listen = [ "127.0.0.1:$port" ];
$site->automatic_https = [ 'disable' => true ];
$site->tls_connection_policies ??= [ (object)[] ];

$warmup = [
	'admin' => [ 'listen' => 'unix//run/caddy-warmup.sock' ],
	'apps' => [
		'tls' => $config['apps']['tls'],
		'http' => [ 'servers' => [ 'warmup' => $site ] ],
	],
];
$warmup['apps']['tls']['certificates']['automate'] = array_keys( $names );
foreach ( [ 'storage', 'logging' ] as $key ) {
	if ( isset( $config[$key] ) ) {
		$warmup[$key] = $config[$key];
	}
}
echo json_encode( $warmup, JSON_UNESCAPED_SLASHES );
