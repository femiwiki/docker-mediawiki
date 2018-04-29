<?php

use Flow\Model\UUID;
use Flow\Exception\InvalidInputException;

class SanctionsHooks {
	/**
	 * 데이터베이스 테이블을 만듭니다.
	 * @param $updater DatabaseUpdater
	 * @throws MWException
	 * @return bool
	 */
	public static function onLoadExtensionSchemaUpdates( $updater = null ) {
		$dir = dirname( __FILE__ );

		if ( $updater->getDB()->getType() == 'mysql' ) {
			$updater->addExtensionUpdate( array( 'addTable', 'sanctions',
				"$dir/sanctions.tables.sql", true ) );
		} // @todo else
		return true;
	}

	/**
	 * 제재안 관련 workflow 페이지들에 여러 처리를 합니다.
	 * @param $output: OutputPage object
	 */
	public static function onFlowAddModules( OutputPage $out ) {
		$title = $out->getTitle();
		$specialSanctionTitle =  SpecialPage::getTitleFor('Sanctions'); // 특수:제재안목록
		$discussionPageName = wfMessage( 'sanctions-discussion-page-name' )->text(); //페미위키토론:제재안에 대한 의결

		// 제재안 목록 토론 페이지의 처리
		if ( $title->getFullText() == $discussionPageName ) {
			// url에 "redirect=no"가 붙어오지 않았다면 리다이렉트합니다. 근데 왜 이걸 따로 안 하면 무조건 리다이렉트가 되는 건지?
			$request = RequestContext::getMain()->getRequest();
			$redirect = $request->getVal( 'redirect' );
			if ( !$redirect || $redirect == 'no ')
				$out->redirect( $specialSanctionTitle->getLocalURL( $query ) );

			// CSS를 적용합니다. 주로 입력 폼을 막습니다.
			$out->addModuleStyles( 'ext.flow-default-board' );

			return true;
		}

		// 제재안 topic의 처리
		$uuid = null;
		try {
			$uuid = UUID::create( strtolower( $title->getText() ) );
		} catch ( InvalidInputException $e ) {
			return true;
		}

		// UUID가 적절하지 않은 경우에 종료합니다.
		if ( !$uuid )
			return true;

		// 이 topic이 제재안과 관련된 것이 아니라면 종료합니다.
		$sanction = Sanction::newFromUUID( $uuid );
		if ( $sanction === false )
			return true;
		
		// 만료되지 않은 제재안이라면 새 표가 있는지 체크합니다. 이는 주제 요약을 갱신하기 위함이며 원래는 이 hook 말고 ArticleSaveComplete나 RevisionInsertComplete에서 실행하고 싶었지만 flow 게시글을 작성할 때는 작동하지 않아 불가했습니다.
		if ( !$sanction->isExpired() ){
			$sanction->checkNewVotes();
		}
		//else @todo 만료 표시

		return true;
	}

	// (토론|기여)
	public static function onUserToolLinksEdit( $userId, $userText, &$items ) {
		global $wgUser;
		if ( $wgUser == null || !SanctionsUtils::hasVoteRight( $wgUser ) )
			return true;

		$specialSanctionTitle =  SpecialPage::getTitleFor('Sanctions', $userText);
		$items[] = Linker::link( $specialSanctionTitle, '제재안' );
		return true;
	}

	/**
	 * (편집) (편집 취소)
	 * $newRev: Revision object of the "new" revision
	 * &$links: Array of HTML links
	 * $oldRev: Revision object of the "old" revision (may be null)
	 */
	public static function onDiffRevisionTools( Revision $newRev, &$links, $oldRev ) {
		global $wgUser;
		if ( $wgUser == null || !SanctionsUtils::hasVoteRight( $wgUser ) )
			return true;

		$ids = '';
		if ( $oldRev != null )
			$ids .= $oldRev->getId().'/';
		$ids .= $newRev->getId();

		$specialSanctionTitle =  SpecialPage::getTitleFor('Sanctions', $newRev->getUserText().'/'.$ids );
		$links[] = Linker::link( $specialSanctionTitle , '이 편집을 근거로 제재 건의' );

		return true;
	}

	/**
	 * $rev: Revision object
	 * &$links: Array of HTML links
	 */
	public static function onHistoryRevisionTools( $rev, &$links ) {
		global $wgUser;

		if ( $wgUser == null || !SanctionsUtils::hasVoteRight( $wgUser ) )
			return true;

		$specialSanctionTitle =  SpecialPage::getTitleFor('Sanctions', $rev->getUserText().'/'.$rev->getId() );
		$links[] = Linker::link( $specialSanctionTitle, '이 편집을 근거로 제재 건의' );

		return true;
	}
}
