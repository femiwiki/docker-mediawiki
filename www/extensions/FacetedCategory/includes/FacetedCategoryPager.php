<?php
class FacetedCategoryPager extends AlphabeticPager {

	protected $linkRenderer;

	private $facetName;
	private $facetMember;
	private $matchExactly;
	private $including;

	public function __construct( IContextSource $context, $facetName, $facetMember, $matchExactly, PageLinkRenderer $linkRenderer, $including
	) {
		parent::__construct( $context );
		$facetName = str_replace( ' ', '_', $facetName );
		$facetMember = str_replace( ' ', '_', $facetMember );

		if($facetName!=='') 
			$this->facetName = $facetName;
		if($facetMember !== '')
			$this->facetMember = $facetMember;
		if($matchExactly !== '')
			$this->matchExactly = $matchExactly;
		if($including !== '')
			$this->including = $including;

		if($including) $this->setLimit(200);

		$this->linkRenderer = $linkRenderer;
	}

	function getQueryInfo() {
		$query = [
			'tables' => [ 'category' ],
			'fields' => [ 'cat_title', 'cat_pages' ],
			'conds' => [ 'cat_pages > 0'],
			'options' => [ 'USE INDEX' => 'cat_title' ],
		];

		if($this->matchExactly) {
			if ($this->facetName!='' && $this->facetMember!='') {
				$query['conds'][] = 'cat_title' . $this->mDb->buildLike($this->facetName.'/'.$this->facetMember);
			} elseif ($this->facetName!='' && $this->facetMember=='') {
				$query['conds'][] = 'cat_title' . $this->mDb->buildLike($this->facetName.'/',$this->mDb->anyString());
			} elseif ($this->facetName=='' && $this->facetMember!='') {
				$query['conds'][] = 'cat_title' . $this->mDb->buildLike($this->mDb->anyString(),'/'.$this->facetMember);
			} else {
				$query['conds'][] = 'cat_title' . $this->mDb->buildLike($this->mDb->anyString(),'/',$this->mDb->anyString());
			}
		} else {
			$query['conds'][] = 'cat_title' . $this->mDb->buildLike($this->mDb->anyString(),$this->facetName,$this->mDb->anyString(),'/',$this->mDb->anyString(),$this->facetMember,$this->mDb->anyString());
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

	public function getStartForm( $facetName, $facetMember, $matchExactly ) {
		return $this->including ? '':Xml::tags(
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
				) .
				' ' .
				Xml::checkLabel(
					$this->msg( 'faceted_category_match_exactly' ), 'matchExactly', 'matchExactly', $matchExactly, [] )
			)
		);
	}
}
