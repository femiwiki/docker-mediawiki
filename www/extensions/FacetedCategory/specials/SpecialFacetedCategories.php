<?php

class SpecialFacetedCategories extends IncludableSpecialPage {

	protected $linkRenderer = null;

	public function __construct() {
		parent::__construct( 'FacetedCategories' );
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

		$slash = strpos($par,'/');
		$left = $slash===false?$par:substr($par,0,$slash);
		$right = $slash===false?'':substr($par,$slash+1,strlen($par)-1);
		
		$facetName = $this->getRequest()->getText( 'facetName', $left );
		$facetMember = $this->getRequest()->getText( 'facetMember', $right );
		$includeNotExactlyMatched = $this->getRequest()->getBool( 'includeNotExactlyMatched', false );

		$cap = new FacetedCategoryPager(
			$this->getContext(),
			$facetName,
			$facetMember,
			$includeNotExactlyMatched,
			$this->linkRenderer,
			$this->including()
		);
		$cap->doQuery();

		$this->getOutput()->addHTML(
			Html::openElement( 'div', [ 'class' => 'mw-spcontent' ] ) .
				($this->including()?'':$this->msg( 'categoriespagetext', $cap->getNumRows() )->parseAsBlock()) .
				$cap->getStartForm( $facetName, $facetMember, $includeNotExactlyMatched ) .
				($this->including()?'':$cap->getNavigationBar()) .
				'<ul>' . $cap->getBody() . '</ul>' .
				($this->including()?'':$cap->getNavigationBar()) .
				Html::closeElement( 'div' )
		);
	}

	protected function getGroupName() {
		return 'pages';
	}
}
