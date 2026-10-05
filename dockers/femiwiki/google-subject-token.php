<?php
/**
 * Writes an OIDC token for this instance's IAM role, from AWS STS GetWebIdentityToken, where
 * PageViewInfoGA's external_account credentials read it, so no Google key is kept (femiwiki/femiwiki#670).
 * run calls this at start and cron every 15 minutes; a token lives an hour.
 *
 * Usage: php google-subject-token.php [<credential configuration> [<token file>]]
 */

use Aws\Exception\AwsException;
use Aws\Sts\StsClient;

require '/srv/femiwiki.com/vendor/autoload.php';

$configFile = $argv[1] ?? '/etc/mediawiki/google-analytics.json';
$tokenFile = $argv[2] ?? '/run/secrets/google-subject-token';

$config = json_decode( (string)file_get_contents( $configFile ), true );
$audience = is_array( $config ) ? $config['audience'] ?? null : null;
if ( !is_string( $audience ) ) {
	fwrite( STDERR, "No audience in $configFile\n" );
	exit( 1 );
}

// GetWebIdentityToken is not on the global STS endpoint
$sts = new StsClient( [
	'region' => getenv( 'AWS_REGION' ) ?: 'ap-northeast-2',
	'version' => '2011-06-15',
	'sts_regional_endpoints' => 'regional',
	'http' => [ 'connect_timeout' => 2, 'timeout' => 5 ],
] );
try {
	$result = $sts->getWebIdentityToken( [
		// The credential configuration names the provider as //iam.googleapis.com/…, and the
		// provider's default allowed audience is that name with https: in front
		'Audience' => [ "https:$audience" ],
		// Google accepts RS256 and ES256, AWS issues RS256 and ES384
		'SigningAlgorithm' => 'RS256',
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
$temp = tempnam( dirname( $tokenFile ), '.google-subject-token' );
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
