<?php

class FacetedCategoryHooks {

	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
        $out->addModules( [ 'ext.facetedCategory.js' ] );

		return true;
	}
}
