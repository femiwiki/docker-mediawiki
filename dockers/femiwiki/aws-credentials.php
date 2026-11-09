<?php
/**
 * Writes AWS credentials for this container's own IAM role, from IAM Roles Anywhere, to the
 * shared credentials file the AWS SDKs and chamber read (femiwiki/infra#1173). The certificate
 * and key come from infra; the instance role is never asked. run and jobrunner call this at
 * start and cron every 15 minutes; a session lives an hour.
 *
 * Does nothing unless FW_ROLES_ANYWHERE_ROLE_ARN is set.
 *
 * Usage: php aws-credentials.php
 */

$roleArn = getenv( 'FW_ROLES_ANYWHERE_ROLE_ARN' );
if ( !$roleArn ) {
	exit( 0 );
}
$trustAnchorArn = getenv( 'FW_ROLES_ANYWHERE_TRUST_ANCHOR_ARN' );
$profileArn = getenv( 'FW_ROLES_ANYWHERE_PROFILE_ARN' );
if ( !$trustAnchorArn || !$profileArn ) {
	fwrite( STDERR, "FW_ROLES_ANYWHERE_TRUST_ANCHOR_ARN and FW_ROLES_ANYWHERE_PROFILE_ARN must be set\n" );
	exit( 1 );
}
$dir = getenv( 'FW_ROLES_ANYWHERE_DIR' ) ?: '/etc/aws-roles-anywhere';
$credentialsFile = getenv( 'AWS_SHARED_CREDENTIALS_FILE' ) ?: '/run/aws/credentials';

$command = [
	'/usr/local/bin/aws_signing_helper', 'credential-process',
	'--certificate', "$dir/certificate.pem",
	'--private-key', "$dir/private-key.pem",
	'--trust-anchor-arn', $trustAnchorArn,
	'--profile-arn', $profileArn,
	'--role-arn', $roleArn,
];
$process = proc_open( $command, [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes );
if ( $process === false ) {
	fwrite( STDERR, "Cannot run aws_signing_helper\n" );
	exit( 1 );
}
$out = stream_get_contents( $pipes[1] );
$err = stream_get_contents( $pipes[2] );
$status = proc_close( $process );

$credentials = json_decode( (string)$out, true );
if ( $status !== 0 || !is_array( $credentials )
	|| !isset( $credentials['AccessKeyId'], $credentials['SecretAccessKey'], $credentials['SessionToken'] )
) {
	// The helper's error names the certificate or the role, never the credentials
	fwrite( STDERR, 'aws_signing_helper failed: ' . trim( (string)$err ) . "\n" );
	exit( 1 );
}

$ini = "[default]\n" .
	"aws_access_key_id = {$credentials['AccessKeyId']}\n" .
	"aws_secret_access_key = {$credentials['SecretAccessKey']}\n" .
	"aws_session_token = {$credentials['SessionToken']}\n";

// Replace the file whole, so php-fpm never reads half of it
$credentialsDir = dirname( $credentialsFile );
if ( !is_dir( $credentialsDir ) && !mkdir( $credentialsDir, 0700, true ) ) {
	fwrite( STDERR, "Cannot make $credentialsDir\n" );
	exit( 1 );
}
chown( $credentialsDir, 'www-data' );
$temp = tempnam( $credentialsDir, '.credentials' );
if ( $temp === false
	|| file_put_contents( $temp, $ini ) === false
	|| !chmod( $temp, 0600 )
	|| !chown( $temp, 'www-data' )
	|| !rename( $temp, $credentialsFile )
) {
	fwrite( STDERR, "Cannot write $credentialsFile\n" );
	if ( $temp !== false && is_file( $temp ) ) {
		unlink( $temp );
	}
	exit( 1 );
}
