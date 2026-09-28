<?php
// Creates the session store's schema and objectcache table from MediaWiki's
// own DDL, idempotently. Run by hand; see femiwiki/femiwiki#645.
//   php create-session-schema.php <schema> <host[:port]> <user> <password>
[ , $name, $server, $user, $password ] = $argv + [ '', '', '', '', '' ];
if ( $name === '' || $server === '' ) {
	fwrite( STDERR, "usage: create-session-schema.php <schema> <host[:port]> <user> <password>\n" );
	exit( 1 );
}
$port = 3306;
$colon = strrpos( $server, ':' );
if ( $colon !== false && ctype_digit( substr( $server, $colon + 1 ) ) ) {
	$port = (int)substr( $server, $colon + 1 );
	$server = substr( $server, 0, $colon );
}

$sql = file_get_contents( '/srv/femiwiki.com/maintenance/tables-generated.sql' );
if ( !preg_match( '/CREATE TABLE [^\n]*objectcache \(.*?\n\)[^\n]*;/s', $sql, $m ) ) {
	fwrite( STDERR, "objectcache is not in tables-generated.sql\n" );
	exit( 1 );
}
// The table options come from LocalSettings for the same reason the DDL comes
// from tables-generated.sql: a copy here drifts. A wrong ROW_FORMAT is not a
// cosmetic difference, it decides whether every session read decompresses a page.
$settings = is_file( '/a/LocalSettings.php' ) ? '/a/LocalSettings.php' : __DIR__ . '/LocalSettings.php';
if ( !preg_match( '/\$wgDBTableOptions\s*=\s*\'([^\']*)\'/', file_get_contents( $settings ), $o ) ) {
	fwrite( STDERR, "wgDBTableOptions is not in $settings\n" );
	exit( 1 );
}
$ddl = strtr( $m[0], [
	'/*_*/' => '',
	'CREATE TABLE ' => 'CREATE TABLE IF NOT EXISTS ',
	'/*$wgDBTableOptions*/' => $o[1],
] );

mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
$c = mysqli_init();
$c->options( MYSQLI_SET_CHARSET_NAME, 'binary' );
$c->real_connect( $server, $user, $password, '', $port );
$c->query( sprintf( 'CREATE DATABASE IF NOT EXISTS `%s`', str_replace( '`', '``', $name ) ) );
$c->select_db( $name );
$c->query( rtrim( $ddl, ';' ) );
$r = $c->query( 'SHOW TABLES' );
$tables = [];
foreach ( $r as $row ) {
	$tables[] = reset( $row );
}
printf( "%s holds: %s\n", $name, implode( ', ', $tables ) );
