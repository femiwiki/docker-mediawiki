<?php
/**
 * SkinTemplate class for the Femiwiki skin
 *
 * @ingroup Skins
 */
class SkinFemiwiki extends SkinTemplate {
	public $skinname = 'femiwiki', $stylename = 'Femiwiki',
		$template = 'FemiwikiTemplate', $useHeadElement = true;

	/**
	 * Add CSS via ResourceLoader
	 *
	 * @param $out OutputPage
	 */
	public function initPage( OutputPage $out ) {

		$out->addMeta( 'viewport', 'width=device-width, initial-scale=1.0' );

		$out->addModuleStyles( array(
			'mediawiki.skinning.interface',
			'mediawiki.skinning.content.externallinks',
			'skins.femiwiki'
		) );
		$out->addModules( array(
			'skins.femiwiki.js'
		) );
	}

	/**
	 * @param $out OutputPage
	 */
	function setupSkinUserCss( OutputPage $out ) {
		parent::setupSkinUserCss( $out );
	}
}
