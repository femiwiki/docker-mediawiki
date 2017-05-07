<?php

class SpecialFacets extends SpecialPage {

	protected $linkRenderer = null;
	protected $mDb;

	public function __construct() {
		parent::__construct( 'Facets' );

		$this->mDb = $this->mDb ?: wfGetDB( DB_SLAVE );
	}
    
    public function setPageLinkRenderer(
        PageLinkRenderer $linkRenderer
    ) {
        $this->linkRenderer = $linkRenderer;
    }
    
	private function initServices() {
	    if ( !$this->linkRenderer ) {
	        $lang = $this->getContext()->getLanguage();
	        $titleFormatter = new MediaWikiTitleCodec( $lang, GenderCache::singleton() );
	        $this->linkRenderer = new MediaWikiPageLinkRenderer( $titleFormatter );
	    }
	}


	public function execute( $par ) {
		$this->initServices();

		$this->setHeaders();
		$this->outputHeader();
		$this->getOutput()->allowClickjacking();

		$this->mResult = $this->mDb->select(
			[ 'category', 'page', 'categorylinks' ],
			'cat_title',
			[
                'page_title IS NULL OR (cl_from IS NULL AND page_namespace = '.NS_CATEGORY.' AND page_is_redirect = 0 )',
                'cat_pages > 0 OR cat_subcats > 0'
            ],
            __METHOD__,
            [ 'ORDER BY' => 'cat_pages' ],
            [
                'page' => [ 'LEFT JOIN', ' page_title = cat_title' ],
                'categorylinks' => [ 'LEFT JOIN', 'cl_from = page_id' ]
            ]
		);
		$this->mResult->rewind();

		$listedFacets = [];

		$facet = '';
		$slash = 0;
		foreach ( $this->mResult as $row ) {
			$slash = strpos($row->cat_title,'/');
			if($slash===false) continue;

			$facet = substr($row->cat_title,0,$slash);
			if(array_search($facet,$listedFacets)===false) {
				$listedFacets[] = $facet;
			}
		}

		$this->getOutput()->addHTML(Html::openElement( 'ul'));
		foreach ($listedFacets as $facet ) {
			$this->getOutput()->addHTML(Html::openElement( 'li'));
			$this->getOutput()->addWikitext('[[특수:다면분류/'.$facet.'|'.str_replace('_',' ',$facet).']]' ,false,false);
			$this->getOutput()->addHTML(Html::closeElement( 'li'));
		}
		$this->getOutput()->addHTML(Html::closeElement( 'ul'));
	}

	protected function getGroupName() {
		return 'pages';
	}
}