<?php
// Prometheus text for php-fpm's opcache, scraped by Alloy through Caddy's
// metrics site. It has to run inside php-fpm: the CLI keeps a cache of its own.
header( 'Content-Type: text/plain; version=0.0.4' );

$status = opcache_get_status( false );
if ( $status === false ) {
	echo "# TYPE php_opcache_enabled gauge\nphp_opcache_enabled 0\n";
	return;
}
$stats = $status['opcache_statistics'];
$memory = $status['memory_usage'];
$strings = $status['interned_strings_usage'];

$metrics = [
	'php_opcache_enabled' => [ 'gauge', [ '' => (int)$status['opcache_enabled'] ] ],
	'php_opcache_cache_full' => [ 'gauge', [ '' => (int)$status['cache_full'] ] ],
	'php_opcache_memory_bytes' => [ 'gauge', [
		'state="used"' => $memory['used_memory'],
		'state="free"' => $memory['free_memory'],
		'state="wasted"' => $memory['wasted_memory'],
	] ],
	'php_opcache_interned_strings_bytes' => [ 'gauge', [
		'state="used"' => $strings['used_memory'],
		'state="free"' => $strings['free_memory'],
	] ],
	'php_opcache_cached_scripts' => [ 'gauge', [ '' => $stats['num_cached_scripts'] ] ],
	'php_opcache_cached_keys' => [ 'gauge', [ '' => $stats['num_cached_keys'] ] ],
	'php_opcache_max_cached_keys' => [ 'gauge', [ '' => $stats['max_cached_keys'] ] ],
	'php_opcache_hits_total' => [ 'counter', [ '' => $stats['hits'] ] ],
	'php_opcache_misses_total' => [ 'counter', [ '' => $stats['misses'] ] ],
	'php_opcache_restarts_total' => [ 'counter', [
		'reason="oom"' => $stats['oom_restarts'],
		'reason="hash"' => $stats['hash_restarts'],
		'reason="manual"' => $stats['manual_restarts'],
	] ],
	'php_opcache_start_time_seconds' => [ 'gauge', [ '' => $stats['start_time'] ] ],
];

foreach ( $metrics as $name => [ $type, $samples ] ) {
	echo "# TYPE $name $type\n";
	foreach ( $samples as $labels => $value ) {
		echo $labels === '' ? $name : "$name{{$labels}}", " $value\n";
	}
}
