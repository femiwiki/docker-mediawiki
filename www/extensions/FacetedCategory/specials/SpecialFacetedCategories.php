<?php

class SpecialFacetedCategories extends SpecialPage {

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
		$facetName = $this->getRequest()->getText( 'facetName', $slash?substr($par,0,$slash):$par );
		$facetMember = $this->getRequest()->getText( 'facetMember', $slash?substr($par,$slash+1,strlen($par)-1):'' );
		$matchExactly = $this->getRequest()->getBool( 'matchExactly', false );

		$cap = new FacetedCategoryPager(
			$this->getContext(),
			$facetName,
			$facetMember,
			$matchExactly,
			$this->linkRenderer
		);
		$cap->doQuery();

		$this->getOutput()->addHTML(
			Html::openElement( 'div', [ 'class' => 'mw-spcontent' ] ) .
				$this->msg( 'categoriespagetext', $cap->getNumRows() )->parseAsBlock() .
				$cap->getStartForm( $facetName, $facetMember, $matchExactly ) .
				$cap->getNavigationBar() .
				'<ul>' . $cap->getBody() . '</ul>' .
				$cap->getNavigationBar() .
				Html::closeElement( 'div' )
		);
	}

	protected function getGroupName() {
		return 'pages';
	}
}
