<?php
class SpecialOrderedWhatlinkshere extends SpecialWhatLinksHere {

	function showIndirectLinks( $level, $target, $limit, $from = 0, $back = 0 ) {
		$out = $this->getOutput();
		$dbr = wfGetDB( DB_SLAVE );

		$hidelinks = $this->opts->getValue( 'hidelinks' );
		$hideredirs = $this->opts->getValue( 'hideredirs' );
		$hidetrans = $this->opts->getValue( 'hidetrans' );
		$hideimages = $target->getNamespace() != NS_FILE || $this->opts->getValue( 'hideimages' );

		$fetchlinks = ( !$hidelinks || !$hideredirs );

		// Build query conds in concert for all three tables...
		$conds['pagelinks'] = [
			'pl_namespace' => $target->getNamespace(),
			'pl_title' => $target->getDBkey(),
		];
		$conds['templatelinks'] = [
			'tl_namespace' => $target->getNamespace(),
			'tl_title' => $target->getDBkey(),
		];
		$conds['imagelinks'] = [
			'il_to' => $target->getDBkey(),
		];

		$namespace = $this->opts->getValue( 'namespace' );
		$invert = $this->opts->getValue( 'invert' );
		$nsComparison = ( $invert ? '!= ' : '= ' ) . $dbr->addQuotes( $namespace );
		if ( is_int( $namespace ) ) {
			$conds['pagelinks'][] = "pl_from_namespace $nsComparison";
			$conds['templatelinks'][] = "tl_from_namespace $nsComparison";
			$conds['imagelinks'][] = "il_from_namespace $nsComparison";
		}

		/*if ( $from ) {
			$conds['templatelinks'][] = "tl_from >= $from";
			$conds['pagelinks'][] = "pl_from >= $from";
			$conds['imagelinks'][] = "il_from >= $from";
		}*/

		if ( $hideredirs ) {
			$conds['pagelinks']['rd_from'] = null;
		} elseif ( $hidelinks ) {
			$conds['pagelinks'][] = 'rd_from is NOT NULL';
		}

		$queryFunc = function ( IDatabase $dbr, $table, $fromCol ) use (
			$conds, $target, $limit
		) {
			// Read an extra row as an at-end check
			$on = [
				"rd_from = $fromCol",
				'rd_title' => $target->getDBkey(),
				'rd_interwiki = ' . $dbr->addQuotes( '' ) . ' OR rd_interwiki IS NULL'
			];
			$on['rd_namespace'] = $target->getNamespace();
			// Inner LIMIT is 2X in case of stale backlinks with wrong namespaces
			$subQuery = $dbr->selectSQLText(
				[ $table, 'redirect', 'page' ],
				[ $fromCol, 'rd_from' ],
				$conds[$table],
				__CLASS__ . '::showIndirectLinks',
				// Force JOIN order per T106682 to avoid large filesorts
				[ 'ORDER BY' => $fromCol, 'STRAIGHT_JOIN' ],
				[
					'page' => [ 'INNER JOIN', "$fromCol = page_id" ],
					'redirect' => [ 'LEFT JOIN', $on ]
				]
			);
			return $dbr->select(
				[ 'page', 'temp_backlink_range' => "($subQuery)" ],
				[ 'page_id', 'page_namespace', 'page_title', 'rd_from', 'page_is_redirect' ],
				[],
				__CLASS__ . '::showIndirectLinks',
				[ 'ORDER BY' => 'page_title' ],
				[ 'page' => [ 'INNER JOIN', "$fromCol = page_id" ] ]
			);
		};

		if ( $fetchlinks ) {
			$plRes = $queryFunc( $dbr, 'pagelinks', 'pl_from' );
		}

		if ( !$hidetrans ) {
			$tlRes = $queryFunc( $dbr, 'templatelinks', 'tl_from' );
		}

		if ( !$hideimages ) {
			$ilRes = $queryFunc( $dbr, 'imagelinks', 'il_from' );
		}

		if ( ( !$fetchlinks || !$plRes->numRows() )
			&& ( $hidetrans || !$tlRes->numRows() )
			&& ( $hideimages || !$ilRes->numRows() )
		) {
			if ( 0 == $level ) {
				if ( !$this->including() ) {
					$out->addHTML( $this->whatlinkshereForm() );

					// Show filters only if there are links
					if ( $hidelinks || $hidetrans || $hideredirs || $hideimages ) {
						$out->addHTML( $this->getFilterPanel() );
					}
					$errMsg = is_int( $namespace ) ? 'nolinkshere-ns' : 'nolinkshere';
					$out->addWikiMsg( $errMsg, $this->target->getPrefixedText() );
					$out->setStatusCode( 404 );
				}
			}

			return;
		}

		// Read the rows into an array and remove duplicates
		// templatelinks comes second so that the templatelinks row overwrites the
		// pagelinks row, so we get (inclusion) rather than nothing
		if ( $fetchlinks ) {
			foreach ( $plRes as $row ) {
				$row->is_template = 0;
				$row->is_image = 0;
				$rows[$row->page_id] = $row;
			}
		}
		if ( !$hidetrans ) {
			foreach ( $tlRes as $row ) {
				$row->is_template = 1;
				$row->is_image = 0;
				$rows[$row->page_id] = $row;
			}
		}
		if ( !$hideimages ) {
			foreach ( $ilRes as $row ) {
				$row->is_template = 0;
				$row->is_image = 1;
				$rows[$row->page_id] = $row;
			}
		}

		// Sort by key and then change the keys to 0-based indices
		
		
		usort( $rows , function ($a, $b) use ($out) {
			if(isset($a->page_title) && isset($b->page_title)){
				return strcasecmp($a->page_title, $b->page_title);
			}
			else return 0;
		} );
		$rows = array_values( $rows );

		$numRows = count( $rows );

		// Work out the start and end IDs, for prev/next links
		if ( $numRows-$from > $limit ) {
			// More rows available after these ones
			// Get the ID from the last row in the result set
			$nextNumber = $from+$limit;
			// Remove undisplayed rows
			$rows = array_slice( $rows, $from, $limit );
		} else {
			// No more rows after
			$nextNumber = false;
			// Remove undisplayed rows
			$rows = array_slice( $rows, $from );
		}
		$prevNumber = ($from == 0?null:$from-$limit);

		// use LinkBatch to make sure, that all required data (associated with Titles)
		// is loaded in one query
		$lb = new LinkBatch();
		foreach ( $rows as $row ) {
			$lb->add( $row->page_namespace, $row->page_title );
		}
		$lb->execute();

		if ( $level == 0 ) {
			if ( !$this->including() ) {
				$out->addHTML( $this->whatlinkshereForm() );
				$out->addHTML( $this->getFilterPanel() );
				$out->addWikiMsg( 'linkshere', $this->target->getPrefixedText() );

				$prevnext = $this->getPrevNext( $prevNumber, $nextNumber );
				$out->addHTML( $prevnext );
			}
		}
		$out->addHTML( $this->listStart( $level ) );
		foreach ( $rows as $row ) {
			$nt = Title::makeTitle( $row->page_namespace, $row->page_title );

			if ( $row->rd_from && $level < 2 ) {
				$out->addHTML( $this->listItem( $row, $nt, $target, true ) );
				$this->showIndirectLinks(
					$level + 1,
					$nt,
					$this->getConfig()->get( 'MaxRedirectLinksRetrieved' )
				);
				$out->addHTML( Xml::closeElement( 'li' ) );
			} else {
				$out->addHTML( $this->listItem( $row, $nt, $target ) );
			}
		}

		$out->addHTML( $this->listEnd() );

		if ( $level == 0 ) {
			if ( !$this->including() ) {
				$out->addHTML( $prevnext );
			}
		}
	}

	function getPrevNext( $prevNumber, $nextNumber ) {
		$currentLimit = $this->opts->getValue( 'limit' );
		$prev = $this->msg( 'whatlinkshere-prev' )->numParams( $currentLimit )->escaped();
		$next = $this->msg( 'whatlinkshere-next' )->numParams( $currentLimit )->escaped();

		$changed = $this->opts->getChangedValues();
		unset( $changed['target'] ); // Already in the request title

		if ( null !== $prevNumber ) {
			$overrides = [ 'from' => $prevNumber ];
			$prev = $this->makeSelfLink( $prev, array_merge( $changed, $overrides ) );
		}
		if ( 0 != $nextNumber ) {
			$overrides = [ 'from' => $nextNumber ];
			$next = $this->makeSelfLink( $next, array_merge( $changed, $overrides ) );
		}

		$limitLinks = [];
		$lang = $this->getLanguage();
		foreach ( $this->limits as $limit ) {
			$prettyLimit = htmlspecialchars( $lang->formatNum( $limit ) );
			$overrides = [ 'limit' => $limit ];
			$limitLinks[] = $this->makeSelfLink( $prettyLimit, array_merge( $changed, $overrides ) );
		}

		$nums = $lang->pipeList( $limitLinks );

		return $this->msg( 'viewprevnext' )->rawParams( $prev, $next, $nums )->escaped();
	}
}
