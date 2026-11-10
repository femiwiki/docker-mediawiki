<?php
/**
 * Replace pages left with the flow-board content model by wikitext redirects,
 * after Flow is uninstalled. Run once by hand after the deploy that removes Flow:
 *
 *   php /srv/femiwiki.com/maintenance/run.php /usr/local/lib/femiwiki/ReplaceFlowPages.php \
 *     --map /usr/local/lib/femiwiki/flow-topics.json --boards [--dry-run]
 *
 * --map: JSON object {"주제:Xxx": "Target page#anchor"}. Topics not in the map
 *        are left alone and listed.
 * --boards: also turn every "<page>/Flow" board into a redirect to "<page>", and every
 *        topic missing from the map into a redirect to its board's "<page>", found in
 *        Flow's own flow_workflow table (left in place when Flow is uninstalled).
 */

use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\Content\WikitextContent;
use MediaWiki\Maintenance\Maintenance;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
use MediaWiki\User\User;

$IP = getenv( 'MW_INSTALL_PATH' ) ?: '/srv/femiwiki.com';
require_once "$IP/maintenance/Maintenance.php";

class ReplaceFlowPages extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Turn flow-board pages into wikitext redirects' );
		$this->addOption( 'map', 'JSON file mapping topic titles to targets', false, true );
		$this->addOption( 'boards', 'Redirect each <page>/Flow board to <page>' );
		$this->addOption( 'user', 'User to edit as', false, true );
		$this->addOption( 'dry-run', 'Only print what would change' );
		$this->setBatchSize( 100 );
	}

	public function execute() {
		$services = $this->getServiceContainer();
		$dbr = $this->getReplicaDB();
		$user = User::newSystemUser( $this->getOption( 'user', 'Flow talk page manager' ), [ 'steal' => true ] );
		$map = [];
		if ( $this->hasOption( 'map' ) ) {
			$map = json_decode( file_get_contents( $this->getOption( 'map' ) ), true, 512, JSON_THROW_ON_ERROR );
		}
		$targets = [];
		foreach ( $map as $from => $to ) {
			$title = Title::newFromText( $from );
			if ( $title ) {
				$targets[$title->getPrefixedDBkey()] = $to;
			}
		}

		$rows = $dbr->newSelectQueryBuilder()
			->select( [ 'page_id', 'page_namespace', 'page_title' ] )
			->from( 'page' )
			->where( [ 'page_content_model' => 'flow-board' ] )
			->orderBy( 'page_id' )
			->caller( __METHOD__ )->fetchResultSet();

		$done = $skipped = 0;
		foreach ( $rows as $row ) {
			$title = Title::makeTitle( $row->page_namespace, $row->page_title );
			$key = $title->getPrefixedDBkey();
			$board = $this->hasOption( 'boards' ) && (int)$row->page_namespace === NS_TOPIC
				? $this->getBoardOfTopic( $row->page_title )
				: null;
			if ( isset( $targets[$key] ) ) {
				$target = $targets[$key];
			} elseif ( $this->hasOption( 'boards' ) && str_ends_with( $row->page_title, '/Flow' ) ) {
				$target = $title->getBaseTitle()->getPrefixedText();
			} elseif ( $board !== null ) {
				$target = $board;
			} else {
				$this->output( "skip\t{$title->getPrefixedText()}\n" );
				$skipped++;
				continue;
			}
			$this->output( "redirect\t{$title->getPrefixedText()}\t$target\n" );
			if ( $this->hasOption( 'dry-run' ) ) {
				continue;
			}
			$page = $services->getWikiPageFactory()->newFromTitle( $title );
			$updater = $page->newPageUpdater( $user );
			$updater->setContent( SlotRecord::MAIN, new WikitextContent( "#넘겨주기 [[$target]]" ) );
			$updater->saveRevision(
				CommentStoreComment::newUnsavedComment( '구조적 토론을 내려서 변환한 위키텍스트 토론으로 넘겨줌' ),
				EDIT_SUPPRESS_RC | EDIT_INTERNAL | EDIT_FORCE_BOT
			);
			if ( !$updater->wasSuccessful() ) {
				$this->error( "failed\t{$title->getPrefixedText()}\t" . $updater->getStatus()->getWikiText() );
				continue;
			}
			$done++;
			if ( $done % $this->getBatchSize() === 0 ) {
				$this->waitForReplication();
			}
		}
		$this->output( "done $done, skipped $skipped\n" );
	}

	/**
	 * @param string $topicKey Topic page DB key, the alphadecimal workflow UUID
	 * @return string|null The page the topic's board was converted to
	 */
	private function getBoardOfTopic( string $topicKey ): ?string {
		$hex = \Wikimedia\base_convert( strtolower( $topicKey ), 36, 16, 22 );
		$row = $this->getReplicaDB()->newSelectQueryBuilder()
			->select( [ 'workflow_namespace', 'workflow_title_text' ] )
			->from( 'flow_workflow' )
			->where( [ 'workflow_id' => hex2bin( $hex ), 'workflow_type' => 'topic' ] )
			->caller( __METHOD__ )->fetchRow();
		if ( !$row || !str_ends_with( $row->workflow_title_text, '/Flow' ) ) {
			return null;
		}
		return Title::makeTitle( (int)$row->workflow_namespace, $row->workflow_title_text )
			->getBaseTitle()->getPrefixedText();
	}
}

$maintClass = ReplaceFlowPages::class;
require_once RUN_MAINTENANCE_IF_MAIN;
