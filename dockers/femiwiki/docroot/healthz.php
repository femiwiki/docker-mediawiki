<?php
// Readiness for Caddy's active health check. The run script creates the drain
// file on SIGTERM so Caddy stops routing here before php-fpm quits, and holds
// the warming file until the opcache has the hot path; see
// femiwiki/femiwiki#567.
if ( file_exists( '/tmp/draining' ) ) {
	http_response_code( 503 );
	echo "draining\n";
} elseif ( file_exists( '/tmp/warming' ) ) {
	http_response_code( 503 );
	echo "warming\n";
} else {
	echo "ok\n";
}
