<?php
class FacetedCategoryPager extends AlphabeticPager {

	protected $linkRenderer;

	public $facetName;
	public $facetMember;

	public function __construct( IContextSource $context, $facetName, $facetMember, PageLinkRenderer $linkRenderer
	) {
		parent::__construct( $context );
		$facetName = str_replace( ' ', '_', $facetName );
		$facetMember = str_replace( ' ', '_', $facetMember );

		if($facetName!=='') 
			$this->facetName = $facetName;
		if($facetMember !== '')
			$this->facetMember = $facetMember;

		$this->linkRenderer = $linkRenderer;
	}

	function getQueryInfo() {
		$query = [
			'tables' => [ 'category' ],
			'fields' => [ 'cat_title', 'cat_pages' ],
			'conds' => [ 'cat_pages > 0'],
			'options' => [ 'USE INDEX' => 'cat_title' ],
		];

		if ($this->facetName!=='' && $this->facetMember!=='') {
			$query['conds'][] = 'cat_title' . $this->mDb->buildLike($this->mDb->anyString(),$this->facetName,$this->mDb->anyString(),'/',$this->mDb->anyString(),$this->facetMember,$this->mDb->anyString());
			//$query['conds'][] = 'cat_title' . $this->mDb->buildLike('$this->facetMember.'/'.$this->facetMember');
		} elseif ($this->facetName!=='' && $this->facetMember==='') {
			$query['conds'][] = 'cat_title' . $this->mDb->buildLike($this->mDb->anyString(),$this->facetName,$this->mDb->anyString(),'/',$this->mDb->anyString());
		} elseif ($this->facetName==='' && $this->facetMember!=='') {
			$query['conds'][] = 'cat_title' . $this->mDb->buildLike($this->mDb->anyString(),'/',$this->mDb->anyString(),$this->facetMember,$this->mDb->anyString());
		}

		return $query;
	}

	function getIndexField() {
		return 'cat_title';
	}

	function getDefaultQuery() {
		parent::getDefaultQuery();

		return $this->mDefaultQuery;
	}

	/* Override getBody to apply LinksBatch on resultset before actually outputting anything. */
	public function getBody() {
		$batch = new LinkBatch;

		$this->mResult->rewind();

		foreach ( $this->mResult as $row ) {
			$batch->addObj( Title::makeTitleSafe( NS_CATEGORY, $row->cat_title ) );
		}
		$batch->execute();
		$this->mResult->rewind();

		return parent::getBody();
	}

	function formatRow( $result ) {
		$title = new TitleValue( NS_CATEGORY, $result->cat_title );
		$text = $title->getText();
		$link = $this->linkRenderer->renderHtmlLink( $title, $text );

		$count = $this->msg( 'nmembers' )->numParams( $result->cat_pages )->escaped();
		return Html::rawElement( 'li', null, $this->getLanguage()->specialList( $link, $count ) ) . "\n";
	}

	public function getStartForm( $facetName, $facetMember ) {
		return Xml::tags(
			'form',
			[ 'method' => 'get', 'action' => wfScript() ],
			Html::hidden( 'title', $this->getTitle()->getPrefixedText() ) .
			Xml::fieldset(
				$this->msg( 'categories' )->text(),
				$this->msg( 'faceted_category_search_for' ) .
				' ' .
				Xml::input(
					'facetName', 10, $facetName, [ 'class' => 'mw-ui-input-inline' ] ) .
				' / ' .
				Xml::input(
					'facetMember', 10, $facetMember, [ 'class' => 'mw-ui-input-inline' ] ) .
				' ' .
				Html::submitButton(
					$this->msg( 'categories-submit' )->text(),
					[], [ 'mw-ui-progressive' ]
				)
			)
		);
	}
}
