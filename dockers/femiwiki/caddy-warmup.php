<?php
/**
 * Cuts the warm-up config out of the adapted Caddyfile: storage and the tls
 * app only, told to manage the site's names, with its admin endpoint on a
 * socket of its own so the reload that follows cannot land on another Caddy
 * on this host. Starting with this loads the certificates from storage
 * before any listener opens; see caddy-run.
 *
 * Usage: php caddy-warmup.php <adapted config json> > warmup.json
 */

$config = json_decode( file_get_contents( $argv[1] ), true );

$names = [];
foreach ( $config['apps']['tls']['automation']['policies'] ?? [] as $policy ) {
	foreach ( $policy['subjects'] ?? [] as $subject ) {
		$names[$subject] = true;
	}
}
if ( !$names ) {
	fwrite( STDERR, "no TLS subjects in the config\n" );
	exit( 1 );
}

$warmup = [
	'admin' => [ 'listen' => 'unix//run/caddy-warmup.sock' ],
	'apps' => [ 'tls' => $config['apps']['tls'] ],
];
$warmup['apps']['tls']['certificates']['automate'] = array_keys( $names );
foreach ( [ 'storage', 'logging' ] as $key ) {
	if ( isset( $config[$key] ) ) {
		$warmup[$key] = $config[$key];
	}
}
echo json_encode( $warmup, JSON_UNESCAPED_SLASHES );
