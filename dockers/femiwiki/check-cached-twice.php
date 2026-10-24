<?php
/**
 * Requests each kind of page twice through Caddy's cache, plain and gzipped,
 * and fails unless every answer is what MediaWiki itself gives: a 301 with the
 * same Location, or a 200 with a body. The second request is the cache hit,
 * which is what returned an empty 200 for every redirect in
 * femiwiki/caddy-mwcache#165, and served femiwiki.com/ blank for nine hours.
 * Then it asks for the main page in several languages, in turn, and fails if
 * one is served another's, as every reader got the first crawler's language
 * in femiwiki/femiwiki#322.
 *
 * caddy-run runs it against the new Caddy before that opens a public port, and
 * the image test runs it against the test stack.
 *
 * Usage: php check-cached-twice.php <base URL> [address to connect to]
 *   the address replaces the base URL's host in DNS only, as curl --resolve
 */

[ , $base, $connectTo ] = $argv + [ 1 => 'http://127.0.0.1:8080', 2 => null ];
$resolve = [];
if ( $connectTo !== null ) {
	$host = parse_url( $base, PHP_URL_HOST );
	$port = parse_url( $base, PHP_URL_PORT ) ?? ( parse_url( $base, PHP_URL_SCHEME ) === 'https' ? 443 : 80 );
	$resolve = [ "$host:$port:$connectTo" ];
}

/** @return array{int, string, string} status, Location, body */
$get = static function ( string $path, bool $gzip, array $headers = [] ) use ( $base, $resolve ): array {
	$ch = curl_init( $base . $path );
	curl_setopt_array( $ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER => $headers,
		CURLOPT_RESOLVE => $resolve,
		// Loopback to a name the certificate covers; the handshake is not what is checked
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => 0,
		CURLOPT_TIMEOUT => 30,
	] );
	if ( $gzip ) {
		// Both asks for gzip and decodes it, so the body is checked as read
		curl_setopt( $ch, CURLOPT_ENCODING, 'gzip' );
	}
	$body = curl_exec( $ch );
	if ( $body === false ) {
		return [ 0, '', curl_error( $ch ) ];
	}
	return [ curl_getinfo( $ch, CURLINFO_RESPONSE_CODE ), (string)curl_getinfo( $ch, CURLINFO_REDIRECT_URL ), $body ];
};

[ $code, , $siteinfo ] = $get( '/api.php?action=query&meta=siteinfo&format=json', false );
$main = json_decode( $siteinfo, true )['query']['general']['base'] ?? null;
if ( $code !== 200 || !is_string( $main ) ) {
	fwrite( STDERR, "api.php siteinfo: $code, no main page URL\n" );
	exit( 1 );
}
$mainPath = parse_url( $main, PHP_URL_PATH );

$fail = false;
// An empty $wantLocation wants any, but the same each time
$check = static function ( string $path, int $want, string $wantLocation = '' ) use ( $get, &$fail ): void {
	$firstLocation = '';
	foreach ( [ 'identity' => false, 'gzip' => true ] as $enc => $gzip ) {
		foreach ( [ 1, 2 ] as $i ) {
			[ $code, $location, $body ] = $get( $path, $gzip );
			$firstLocation = $firstLocation ?: $location;
			$line = "$path ($enc, request $i): $code" . ( $location ? " to $location" : '' )
				. ', ' . strlen( $body ) . ' bytes';
			$ok = $code === $want && ( $want === 301
				? $location !== '' && $location === ( $wantLocation ?: $firstLocation )
				: $body !== '' );
			if ( $ok ) {
				echo "$line\n";
			} else {
				fwrite( STDERR, "$line; want $want" . ( $wantLocation ? " to $wantLocation" : '' ) . "\n" );
				$fail = true;
			}
		}
	}
};

$check( '/', 301, $main );
$check( '/w/Special:Version', 301 );
$check( '/index.php?title=Special:Version', 301 );
$check( $mainPath, 200 );
$check( '/rest.php/v1/search/title?q=a&limit=1', 200 );
$check( '/load.php?modules=startup&only=scripts', 200 );

// ULS picks the interface language from Accept-Language, and the cache keeps a
// page per header. No header gets the content language.
foreach ( [ 1, 2 ] as $i ) {
	foreach ( [ 'en' => 'en', 'ja' => 'ja', '' => 'ko' ] as $acceptLanguage => $want ) {
		$headers = $acceptLanguage === '' ? [] : [ "Accept-Language: $acceptLanguage" ];
		[ $code, , $body ] = $get( $mainPath, true, $headers );
		$lang = preg_match( '/<html\b[^>]*\slang="([^"]*)"/', $body, $m ) ? $m[1] : '';
		$line = "$mainPath (Accept-Language: " . ( $acceptLanguage ?: 'none' ) . ", request $i): $code, lang=\"$lang\"";
		if ( $code === 200 && $lang === $want ) {
			echo "$line\n";
		} else {
			fwrite( STDERR, "$line; want lang=\"$want\"\n" );
			$fail = true;
		}
	}
}
exit( $fail ? 1 : 0 );
