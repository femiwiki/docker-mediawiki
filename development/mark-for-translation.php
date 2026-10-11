<?php
// Marks imported pages that carry <translate> tags, as femiwiki.com has them,
// the way action=markfortranslation does but without a token. Run by wiki.

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\Translate\MessageGroupProcessing\RevTagStore;
use MediaWiki\Extension\Translate\PageTranslation\TranslatablePageSettings;
use MediaWiki\Extension\Translate\Services;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use Wikimedia\Rdbms\IDBAccessObject;

$services = MediaWikiServices::getInstance();
$marker = Services::getInstance()->getTranslatablePageMarker();
$admin = $services->getUserFactory()->newFromName( 'Admin' );
$rows = $services->getConnectionProvider()->getReplicaDatabase()->newSelectQueryBuilder()
	->select( [ 'page_namespace', 'page_title' ] )
	->from( 'page' )
	->where( [ 'page_content_model' => 'wikitext' ] )
	->caller( 'mark-for-translation' )
	->fetchResultSet();
foreach ( $rows as $row ) {
	$title = Title::makeTitle( $row->page_namespace, $row->page_title );
	// Translation pages are <source>/<language code>
	$code = substr( strrchr( $title->getText(), '/' ) ?: '', 1 );
	if ( $title->getNamespace() === NS_TRANSLATIONS ||
		$services->getLanguageNameUtils()->isKnownLanguageTag( $code )
	) {
		continue;
	}
	$content = $services->getWikiPageFactory()->newFromTitle( $title )->getContent();
	if ( !$content || !str_contains( $content->getText(), '<translate>' ) ) {
		continue;
	}
	// A save would have tagged the revision ready; an import does not
	$page = $title->toPageRecord( IDBAccessObject::READ_LATEST );
	Services::getInstance()->getRevTagStore()->replaceTag( $title, RevTagStore::TP_READY_TAG, $page->getLatest() );
	try {
		$operation = $marker->getMarkOperation( $page, null, false );
		$marker->markForTranslation(
			$operation,
			new TranslatablePageSettings( [], false, '', [], false, false,
				$operation->getPage()->supportsTransclusion() ?? $operation->isFirstMark() ),
			RequestContext::getMain(),
			$admin
		);
		echo $title->getPrefixedText() . "\n";
	} catch ( Exception $e ) {
		echo $title->getPrefixedText() . ': ' . $e->getMessage() . "\n";
	}
}
