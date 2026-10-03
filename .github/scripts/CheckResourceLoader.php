<?php
/**
 * Fails when ResourceLoader cannot build a registered module for the femiwiki
 * skin. load.php answers such a module with 200 and the exception in a comment,
 * so only asking ResourceLoader itself tells. A skinStyles file importing a
 * LESS file that core or an extension removed is the case this was written for
 * (femiwiki/FemiwikiSkin#1009).
 *
 * Core's ResourcesTest::testRespond checks the same, but this image is built
 * without PHPUnit, so this follows it: the same modules skipped, the same
 * only=styles for style modules, for the one skin femiwiki.com serves.
 *
 * Usage: php maintenance/run.php CheckResourceLoader.php
 *
 * @file
 */

use MediaWiki\Request\FauxRequest;
use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\Module;

$IP = getenv( 'MW_INSTALL_PATH' ) ?: '/srv/femiwiki.com';
require_once "$IP/maintenance/Maintenance.php";

class CheckResourceLoader extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addOption( 'skin', 'The skin to build the modules for', false, true );
	}

	public function execute() {
		$rl = $this->getServiceContainer()->getResourceLoader();
		$skin = $this->getOption( 'skin', 'femiwiki' );
		$names = $rl->getModuleNames();
		sort( $names );

		$failed = [];
		$built = 0;
		foreach ( $names as $name ) {
			$module = $rl->getModule( $name );
			// Private modules cannot be served from load.php
			if ( $module->shouldSkipStructureTest() ) {
				continue;
			}
			// Each module in a fresh context, so an error names the module that
			// threw it. getErrors() collects what load.php would put in a comment.
			$before = count( $rl->getErrors() );
			$context = new Context( $rl, new FauxRequest( [
				'modules' => $name,
				'only' => $module->getType() === Module::LOAD_STYLES ? 'styles' : null,
				'skin' => $skin,
				'lang' => 'ko',
			] ) );
			$rl->makeModuleResponse( $context, [ $name => $module ] );
			$errors = array_slice( $rl->getErrors(), $before );
			if ( $errors ) {
				$failed[$name] = $errors;
			}
			$built++;
		}

		foreach ( $failed as $name => $errors ) {
			// The first line names the exception; the rest is its backtrace
			$this->error( "$name: " . strtok( $errors[0], "\n" ) );
		}
		if ( $failed ) {
			$this->fatalError( count( $failed ) . " of $built modules failed for skin=$skin" );
		}
		$this->output( "$built modules, all built for skin=$skin\n" );
	}
}

$maintClass = CheckResourceLoader::class;
require_once RUN_MAINTENANCE_IF_MAIN;
