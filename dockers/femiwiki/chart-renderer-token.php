<?php
/**
 * Writes an OIDC token for this instance's IAM role, from AWS STS GetWebIdentityToken, which
 * LocalSettings puts in $wgChartServiceUrl so the chart-renderer Lambda serves only this wiki
 * (femiwiki/femiwiki#671). run calls this at start and cron every 15 minutes; a token lives an hour.
 *
 * Usage: php chart-renderer-token.php [<token file>]
 */

use Aws\Exception\AwsException;
use Aws\Sts\StsClient;

require '/srv/femiwiki.com/vendor/autoload.php';

$tokenFile = $argv[1] ?? '/run/secrets/chart-renderer-token';

// GetWebIdentityToken is not on the global STS endpoint
$sts = new StsClient( [
	'region' => getenv( 'AWS_REGION' ) ?: 'ap-northeast-2',
	'version' => '2011-06-15',
	'sts_regional_endpoints' => 'regional',
	'http' => [ 'connect_timeout' => 2, 'timeout' => 5 ],
] );
try {
	$result = $sts->getWebIdentityToken( [
		'Audience' => [ 'chart-renderer' ],
		'SigningAlgorithm' => 'ES384',
		'DurationSeconds' => 3600,
	] );
} catch ( AwsException $e ) {
	fwrite( STDERR, 'GetWebIdentityToken failed: ' . $e->getAwsErrorCode() . ': ' .
		$e->getAwsErrorMessage() . "\n" );
	exit( 1 );
} catch ( Exception $e ) {
	// Such as no instance role credentials
	fwrite( STDERR, 'GetWebIdentityToken failed: ' . $e->getMessage() . "\n" );
	exit( 1 );
}

// Replace the file whole, so php-fpm never reads half a token
$temp = tempnam( dirname( $tokenFile ), '.chart-renderer-token' );
if ( $temp === false
	|| file_put_contents( $temp, $result['WebIdentityToken'] ) === false
	|| !chmod( $temp, 0600 )
	|| !chown( $temp, 'www-data' )
	|| !rename( $temp, $tokenFile )
) {
	fwrite( STDERR, "Cannot write $tokenFile\n" );
	if ( $temp !== false && is_file( $temp ) ) {
		unlink( $temp );
	}
	exit( 1 );
}
