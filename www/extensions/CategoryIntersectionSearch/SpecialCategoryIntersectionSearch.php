<?php

class SpecialCategoryIntersectionSearch extends SpecialPage {
	public function __construct() {
		parent::__construct( 'CategoryIntersectionSearch' );
	}

	public function execute( $par ) {
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();

		if($par=='') {
			$output->addWikitext('검색어를 입력해 주세요.');
			return;
		}
		$titleParam = str_replace( '_', ' ', $par );
		$categories = $this->splitPar( $titleParam );

		if( count($categories) < 2 ) {
			$output->redirect( Title::newFromText('Category:'.$titleParam)->getFullURL() );
			return;
		}

		$output->setPageTitle( '"'.implode('", "',$categories).'" 분류 ' );

		//여기서 아래는 CategoryViewer.php과 동일
		$oldFrom = $request->getVal( 'from' );
		$oldUntil = $request->getVal( 'until' );
 
		$reqArray = $request->getValues();
		$from = $until = [];
		foreach ( [ 'page', 'subcat', 'file' ] as $type ) {
			$from[$type] = $request->getVal( "{$type}from", $oldFrom );
			$until[$type] = $request->getVal( "{$type}until", $oldUntil );
 
			// Do not want old-style from/until propagating in nav links.
			if ( !isset( $reqArray["{$type}from"] ) && isset( $reqArray["from"] ) ) {
				$reqArray["{$type}from"] = $reqArray["from"];
			}
			if ( !isset( $reqArray["{$type}to"] ) && isset( $reqArray["to"] ) ) {
				$reqArray["{$type}to"] = $reqArray["to"];
			}
		}
		unset( $reqArray["from"] );
		unset( $reqArray["to"] );
		//위에서 여기까지는 CategoryViewer.php과 동일

		$viewer = new CategoryIntersectionSearchViewer(
			Title::newFromText('특수:교집합분류검색/'.implode(', ',$categories)), //"$1에 속하는 문서"라고 표시되는 부분을 그럴 듯하게 보여주기 위한 꼼수입니다.
			$this->getContext(),
			$from,
			$until,
			$reqArray,
			$categories
		);
		$output->addHTML( $viewer->getHTML() );
	}

	function splitPar($par) {
		if(strpos($par,",") === false)
			return null;

		$categories = explode(",",$par);
		for($i=0;$i<count($categories);$i++) {
			if(strpos($categories[$i],"/") === false) return null;
			$categories[$i] = trim($categories[$i]);
			$pos = strrchr($categories[$i],":");
			if($pos !== false) $categories[$i] = trim(substr($pos,1));
		}

		return $categories;
	}

	protected function getGroupName() {
		return 'pages';
	}
}

class CategoryIntersectionSearchViewer extends CategoryTreeCategoryViewer {
	function __construct( $title, IContextSource $context, $from = [], $until = [], $query = [], $categories)  {
		$this->categories = $categories;
		parent::__construct( $title, $context, $from, $until, $query );
	}

	function doCategoryQuery() {
		$categoriesStr='';
		foreach($this->categories as $key => $category) {
			if($key!==0) $categoriesStr .= ',';
			$categoriesStr .= "'".Title::newFromText('category:'.$category)->getDBkey()."'";
		}
		//여기서부터 아래는 CategoryViewer.php의 doCategoryQuery()과 동일
		$dbr = wfGetDB( DB_SLAVE, ['page','categorylinks','category'] );

		$this->nextPage = [
			'page' => null,
			'subcat' => null,
			'file' => null,
		];
		$this->prevPage = [
			'page' => null,
			'subcat' => null,
			'file' => null,
		];

		$this->flip = [ 'page' => false, 'subcat' => false, 'file' => false ];

		foreach ( [ 'page', 'subcat', 'file' ] as $type ) {
			$extraConds = [ 'cl_type' => $type ];
			if ( isset( $this->from[$type] ) && $this->from[$type] !== null ) {
				$extraConds[] = 'cl_sortkey >= '
					. $dbr->addQuotes( $this->collation->getSortKey( $this->from[$type] ) );
			} elseif ( isset( $this->until[$type] ) && $this->until[$type] !== null ) {
				$extraConds[] = 'cl_sortkey < '
					. $dbr->addQuotes( $this->collation->getSortKey( $this->until[$type] ) );
				$this->flip[$type] = true;
			}
			//위에서 여기까지는 CategoryViewer.php의 doCategoryQuery()과 동일
			/*
			$res = $dbr->select(
				[ 'page', 'categorylinks', 'category' ],
				[ 'page_id', 'page_title', 'page_namespace', 'page_len',
					'page_is_redirect', 'cl_sortkey', 'cat_id', 'cat_title',
					'cat_subcats', 'cat_pages', 'cat_files',
					'cl_sortkey_prefix', 'cl_collation' ],
				array_merge( [ 'cl_to' => Title::newFromText('category:성격/아주 많은 문서가 들어 있는 분류1')->getDBkey() ], $extraConds ),
				__METHOD__,
				[
					'USE INDEX' => [ 'categorylinks' => 'cl_sortkey' ],
					'LIMIT' => $this->limit + 1,
					'ORDER BY' => $this->flip[$type] ? 'cl_sortkey DESC' : 'cl_sortkey',
				],
				[
					'categorylinks' => [ 'INNER JOIN', 'cl_from = page_id' ],
					'category' => [ 'LEFT JOIN', [
						'cat_title = page_title',
						'page_namespace' => NS_CATEGORY
					] ]
				]
				);*/
			$res = $dbr->query(
				"SELECT DISTINCT page_id, page_title, page_namespace, page_len, page_is_redirect, cl_sortkey, cat_id, cat_title, cat_subcats, cat_pages, cat_files, cl_sortkey_prefix, cl_collation ".
				"FROM page ".
					"INNER JOIN (SELECT cl_from, COUNT(*) AS match_count FROM categorylinks WHERE cl_to IN({$categoriesStr}) GROUP BY cl_from ORDER BY ". ($this->flip[$type] ? 'cl_sortkey DESC' : 'cl_sortkey').") AS matches ON page.page_id = matches.cl_from AND matches.match_count = ".count($this->categories)." ".
					"INNER JOIN categorylinks ON page.page_id = categorylinks.cl_from "." ".
					"LEFT JOIN category ON category.cat_title = page.page_title AND page.page_namespace = ".NS_CATEGORY." ".
				//'USE INDEX categorylinks.cl_sortkey '.
				"WHERE ".$dbr->makeList( $extraConds, LIST_AND )." ".
					'LIMIT '.($this->limit + 1)." ",
					'ORDER BY '. ($this->flip[$type] ? 'cl_sortkey DESC' : 'cl_sortkey')
				, __METHOD__
				);

			//여기서부터 아래는 CategoryViewer.php의 doCategoryQuery()과 동일
			Hooks::run( 'CategoryViewer::doCategoryQuery', [ $type, $res ] );
 
			$count = 0;
			foreach ( $res as $row ) {
				$title = Title::newFromRow( $row );
				if ( $row->cl_collation === '' ) {
					// Hack to make sure that while updating from 1.16 schema
					// and db is inconsistent, that the sky doesn't fall.
					// See r83544. Could perhaps be removed in a couple decades...
					$humanSortkey = $row->cl_sortkey;
				} else {
					$humanSortkey = $title->getCategorySortkey( $row->cl_sortkey_prefix );
				}
 
				if ( ++$count > $this->limit ) {
					# We've reached the one extra which shows that there
					# are additional pages to be had. Stop here...
					$this->nextPage[$type] = $humanSortkey;
					break;
				}
				if ( $count == $this->limit ) {
					$this->prevPage[$type] = $humanSortkey;
				}
 
				if ( $title->getNamespace() == NS_CATEGORY ) {
					$cat = Category::newFromRow( $row, $title );
					$this->addSubcategoryObject( $cat, $humanSortkey, $row->page_len );
				} elseif ( $title->getNamespace() == NS_FILE ) {
					$this->addImage( $title, $humanSortkey, $row->page_len, $row->page_is_redirect );
				} else {
					$this->addPage( $title, $humanSortkey, $row->page_len, $row->page_is_redirect );
				}
			}
			//위에서 여기까지는 CategoryViewer.php의 doCategoryQuery()과 동일
		}
	}
}
?>
