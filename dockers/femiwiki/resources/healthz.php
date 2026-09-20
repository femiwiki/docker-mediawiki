<?php
// Readiness for Caddy's active health check. The run script creates the
// drain file on SIGTERM so Caddy stops routing here before php-fpm quits.
if ( file_exists( '/tmp/draining' ) ) {
	http_response_code( 503 );
	echo "draining\n";
} else {
	echo "ok\n";
}
