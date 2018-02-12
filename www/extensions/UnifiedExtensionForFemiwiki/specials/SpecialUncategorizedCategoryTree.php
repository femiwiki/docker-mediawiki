<?php
class SpecialUncategorizedCategoryTree extends PageQueryPage {

    function __construct( $name = 'Uncategorizedcategories' ) {
        parent::__construct( $name );
    }

    function execute($par) {
        parent::execute( $par );

        CategoryTree::setHeaders( $this->getOutput() );
    }

    function sortDescending() {
        return false;
    }

    function isExpensive() {
        return true;
    }

    function isSyndicated() {
        return false;
    }

    function getQueryInfo() {
        return [
            'tables' => ['category', 'page', 'categorylinks' ],
            'fields' => [ 'title' => 'cat_title' ],
            'conds' => [
                'page_title IS NULL OR (cl_from IS NULL AND page_namespace = '.NS_CATEGORY.' AND page_is_redirect = 0 )',
                'cat_pages > 0 OR cat_subcats > 0'
            ],
            'options' => [ 'ORDER BY' => 'cat_pages' ],
            'join_conds' => [
                'page' => [ 'LEFT JOIN', ' page_title = cat_title' ],
                'categorylinks' => [ 'LEFT JOIN', 'cl_from = page_id' ]
            ]
        ];
    }

    function formatResult( $skin, $result ) {
        global $wgCategoryTreeDefaultOptions, $wgCategoryTreeSpecialPageOptions;

        $title = Title::makeTitle( NS_CATEGORY, $result->title );

        $options = array();
        # grab all known options from the request. Normalization is done by the CategoryTree class
        foreach ( $wgCategoryTreeDefaultOptions as $option => $default ) {
            if ( isset( $wgCategoryTreeSpecialPageOptions[$option] ) ) {
                $default = $wgCategoryTreeSpecialPageOptions[$option];
            }
            $options[$option] = $default;
        }
        $options['mode'] = 'categories';
        $this->tree = new CategoryTree( $options );

        return $this->tree->renderNode( $title );
    }

    function getOrderFields() {
        return [ 'cat_title' ];
    }

    protected function getGroupName() {
        return 'maintenance';
    }
}