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

// What an anonymous reader reaches, asked the way Caddy passes it on: the
// address bar shape /w/제목, which is what sends MediaWiki through its path
// router. A page the wiki locks to logged-in users, `history` among them,
// answers a permission page with a 200 and would warm that instead.
const PAGES = [
	[ 'index.php', '/w/%ED%8E%98%EB%AF%B8%EC%9C%84%ED%82%A4:%EB%8C%80%EB%AC%B8', '' ],
	[ 'index.php', '/w/%ED%8A%B9%EC%88%98:%EC%B5%9C%EA%B7%BC%EB%B0%94%EB%80%9C', '' ],
	[ 'api.php', '/api.php', 'action=query&meta=siteinfo&format=json' ],
];

// A page that never answers 200 must not spend the whole budget and leave the
// others cold.
const ATTEMPTS = 6;

// The first page also builds the localisation cache for its language, which
// this image has no chance to build earlier, so it is given room.
const FIRST_TIMEOUT_MS = 90000;
const TIMEOUT_MS = 30000;

$ask = static function (
	string $host, int $port, string $script, string $uri, string $query, int $timeoutMs
): ?string {
	$client = new Adoy\FastCGI\Client( $host, $port );
	$client->setReadWriteTimeout( $timeoutMs );
	try {
		return $client->request( REQUEST + [
			'SCRIPT_FILENAME' => "/srv/femiwiki.com/$script",
			'SCRIPT_NAME' => "/$script",
			'REQUEST_URI' => $uri . ( $query === '' ? '' : "?$query" ),
			'QUERY_STRING' => $query,
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
$served = static function ( ?string $response ): bool {
	if ( $response === null ) {
		return false;
	}
	$headers = explode( "\r\n\r\n", $response, 2 )[0];
	return !preg_match( '/^Status:\s*(?!200)/mi', $headers );
};

$report = static function ( string $uri, ?string $response, float $seconds ) use ( $served ): void {
	$status = $response === null ? 'nothing' : ( $served( $response ) ? '200' : trim( strtok( $response, "\n" ) ) );
	fwrite( STDERR, sprintf( "warm-up: %s answered %s in %.1fs\n", $uri, $status, $seconds ) );
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

$first = true;
foreach ( PAGES as [ $script, $uri, $query ] ) {
	$deadline = time() + intdiv( $budget, count( PAGES ) );
	for ( $attempt = 0; $attempt < ATTEMPTS && time() < $deadline; $attempt++ ) {
		$started = microtime( true );
		$response = $ask( $host, (int)$port, $script, $uri, $query, $first ? FIRST_TIMEOUT_MS : TIMEOUT_MS );
		$report( $uri, $response, microtime( true ) - $started );
		$first = false;
		if ( $served( $response ) ) {
			break;
		}
		usleep( 500000 );
	}
}
