#!/usr/bin/env php
<?php
// Asks the www pool for a few pages so php-fpm's opcache holds the hot path
// before Caddy routes anything here. The CLI keeps an opcache of its own, so
// this has to go through FastCGI; see femiwiki/femiwiki#567.
require '/srv/fcgi-check/AdoyFastCgiClient.php';

// What Caddy's fastcgi transport sends. Without HTTPS the wiki answers 301 to
// its canonical address, compiling nothing past the entry point, and without
// REMOTE_ADDR it throws while working out who is asking.
const REQUEST = [
	'REQUEST_METHOD' => 'GET',
	'SERVER_NAME' => 'femiwiki.com',
	'HTTP_HOST' => 'femiwiki.com',
	'HTTPS' => 'on',
	'REQUEST_SCHEME' => 'https',
	'SERVER_PORT' => '443',
	'SERVER_PROTOCOL' => 'HTTP/1.1',
	'GATEWAY_INTERFACE' => 'CGI/1.1',
	'REMOTE_ADDR' => '127.0.0.1',
	'REMOTE_PORT' => '0',
	'HTTP_X_FORWARDED_FOR' => '127.0.0.1',
	'HTTP_ACCEPT_LANGUAGE' => 'ko',
	'HTTP_USER_AGENT' => 'femiwiki-warm-up',
];

// The pages Caddy's preflight checks, so none of them reaches it cold
// (femiwiki/infra#1588)
$pages = require __DIR__ . '/preflight-pages.php';

// A page that never answers 200 must not spend the whole budget and leave the
// others cold.
const ATTEMPTS = 6;

// The first page compiles the whole hot path into an empty opcache, so it is
// given room.
const FIRST_TIMEOUT_MS = 90000;
const TIMEOUT_MS = 30000;

$ask = static function (
	string $host, int $port, string $script, string $uri, string $query, int $timeoutMs, string $pathInfo = ''
): ?string {
	$client = new Adoy\FastCGI\Client( $host, $port );
	$client->setReadWriteTimeout( $timeoutMs );
	try {
		return $client->request( REQUEST + [
			'SCRIPT_FILENAME' => "/srv/docroot/$script",
			'SCRIPT_NAME' => "/$script",
			'REQUEST_URI' => $uri . ( $query === '' ? '' : "?$query" ),
			'QUERY_STRING' => $query,
			'PATH_INFO' => $pathInfo,
		], '' );
	} catch ( Throwable $e ) {
		// php-fpm may not be listening yet, or the page may be slow while the
		// cache is cold; either way the deadline is the only limit.
		return null;
	}
};

// php-fpm sends a Status header only when the answer is not a plain 200, and
// only in the header block: a page whose text begins a line with Status: is
// not an error.
$status = static function ( ?string $response ): int {
	if ( $response === null ) {
		return 0;
	}
	$headers = explode( "\r\n\r\n", $response, 2 )[0];
	return preg_match( '/^Status:\s*(\d{3})/mi', $headers, $m ) ? (int)$m[1] : 200;
};
$served = static function ( ?string $response ) use ( $status ): bool {
	return $status( $response ) === 200;
};

$report = static function ( string $uri, ?string $response, float $seconds ) use ( $status ): void {
	$got = $status( $response ) ?: 'nothing';
	fwrite( STDERR, sprintf( "warm-up: %s answered %s in %.1fs\n", $uri, $got, $seconds ) );
};

[ $host, $port ] = explode( ':', getenv( 'FCGI_WARMUP_URL' ) ?: '127.0.0.1:9000' );
$budget = (int)( getenv( 'FCGI_WARMUP_SECONDS' ) ?: 120 );
$sibling = getenv( 'FCGI_SIBLING_URL' );

// Refusing to serve only helps while the generation before this one still
// answers. A listening socket is not enough: a container on its way out holds
// one while /healthz.php already reports draining.
if ( $sibling ) {
	[ $siblingHost, $siblingPort ] = explode( ':', $sibling );
	if ( !$served( $ask( $siblingHost, (int)$siblingPort, 'healthz.php', '/healthz.php', '', 5000 ) ) ) {
		fwrite( STDERR, "warm-up: no other generation is serving, so answering while warming\n" );
		if ( file_exists( '/tmp/warming' ) ) {
			unlink( '/tmp/warming' );
		}
	}
}

// A 5xx is the page failing rather than the cache being cold. Nothing answering
// in time is left alone, since a slow first compile looks the same.
$failed = static function ( ?string $response ) use ( $status ): bool {
	return $status( $response ) >= 500;
};

$first = true;
$main = null;
$broken = [];
foreach ( $pages as [ $uri, $want ] ) {
	if ( $uri === '{main}' ) {
		if ( $main === null ) {
			fwrite( STDERR, "warm-up: siteinfo gave no main page, so skipping it\n" );
			continue;
		}
		$uri = $main;
	}
	// Split the way Caddy's php_fastcgi does: a .php path names its script, and
	// anything else is rewritten to index.php with the original URI kept
	[ $path, $query ] = explode( '?', $uri, 2 ) + [ 1 => '' ];
	$script = 'index.php';
	$pathInfo = '';
	if ( preg_match( '#^/([^/]+\.php)(/.*)?$#', $path, $m ) ) {
		$script = $m[1];
		$pathInfo = $m[2] ?? '';
	}
	$deadline = time() + intdiv( $budget, count( $pages ) );
	$response = null;
	for ( $attempt = 0; $attempt < ATTEMPTS && time() < $deadline; $attempt++ ) {
		$started = microtime( true );
		$timeoutMs = $first ? FIRST_TIMEOUT_MS : TIMEOUT_MS;
		$response = $ask( $host, (int)$port, $script, $path, $query, $timeoutMs, $pathInfo );
		$report( $uri, $response, microtime( true ) - $started );
		$first = false;
		if ( $status( $response ) === $want ) {
			break;
		}
		usleep( 500000 );
	}
	if ( $main === null && str_starts_with( $uri, '/api.php?action=query&meta=siteinfo' ) && $response !== null ) {
		$base = json_decode( explode( "\r\n\r\n", $response, 2 )[1] ?? '', true )['query']['general']['base'] ?? null;
		$main = is_string( $base ) ? parse_url( $base, PHP_URL_PATH ) : null;
	}
	if ( $failed( $response ) ) {
		$broken[] = $uri;
	}
}

// The warming file is left only while another generation serves; exit 3 tells
// run to keep this one out of service and readers on that one (femiwiki/infra#1281)
if ( $broken && file_exists( '/tmp/warming' ) ) {
	fwrite( STDERR, 'warm-up: ' . implode( ', ', $broken ) . " never answered without a server error\n" );
	exit( 3 );
}
