<?php
/**
 * Fails when a settings file assigns a $wg setting that neither MediaWiki core
 * nor any loaded extension or skin declares. Such a setting was removed
 * upstream, belongs to an extension that is not loaded, or is misspelt; either
 * way nothing reads it. update.php only catches settings marked deprecated or
 * obsolete, so a setting dropped without a mark needs this check.
 *
 * Usage: php CheckConfigKeys.php FILE
 *
 * @file
 */

use MediaWiki\Settings\SettingsBuilder;

$IP = getenv( 'MW_INSTALL_PATH' ) ?: '/srv/femiwiki.com';
require_once "$IP/maintenance/Maintenance.php";

class CheckConfigKeys extends Maintenance {
	/** Settings read by their own mechanism rather than declared as extension config */
	private const ALLOWED = [
		// Wikibase reads these arrays itself
		'wgWBClientSettings',
		'wgWBRepoSettings',
	];

	public function __construct() {
		parent::__construct();
		$this->addArg( 'file', 'The settings file to check' );
	}

	public function execute() {
		$known = array_fill_keys( self::ALLOWED, true );
		foreach ( SettingsBuilder::getInstance()->getDefinedConfigKeys() as $key ) {
			$known["wg$key"] = true;
		}
		foreach ( ExtensionRegistry::getInstance()->getAllThings() as $credits ) {
			$info = json_decode( file_get_contents( $credits['path'] ), true );
			$prefix = $info['config_prefix'] ?? 'wg';
			foreach ( array_keys( $info['config'] ?? [] ) as $key ) {
				$known["$prefix$key"] = true;
			}
		}

		$source = file_get_contents( $this->getArg( 0 ) );
		preg_match_all( '/^\s*\$(wg\w+)\s*(?:\[[^\]\n]*\]\s*)*(?:=|\.=|\+=)/m', $source, $matches );
		$unknown = array_diff( array_unique( $matches[1] ), array_keys( $known ) );
		sort( $unknown );

		if ( $unknown ) {
			$this->fatalError( "Nothing declares these settings:\n\$"
				. implode( "\n\$", $unknown ) );
		}
		$this->output( count( array_unique( $matches[1] ) ) . " settings, all declared\n" );
	}
}

$maintClass = CheckConfigKeys::class;
require_once RUN_MAINTENANCE_IF_MAIN;
