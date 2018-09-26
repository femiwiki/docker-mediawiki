<?php

class FemiwikiHooks {

	/**
	 * 푸터에 몇 링크들을 추가합니다.
	 * @return bool Sends a line to the debug log if false.
	 */
	public static function onSkinTemplateOutputPageBeforeExec( &$skin, &$template ) {
		// 이용 약관을 추가합니다. 이용 약관은 앞쪽에 추가합니다.
		$template->set( 'femiwiki-terms-label', $template->getSkin()->footerLink( 'femiwiki-terms-label', 'femiwiki-terms-page' ) );
		array_unshift( $template->data['footerlinks']['places'], 'femiwiki-terms-label' );

		// 권리 침해 신고를 추가합니다.
		$template->set( 'femiwiki-report-infringement-label', $template->getSkin()->footerLink( 'femiwiki-report-infringement-label', 'femiwiki-report-infringement-page' ) );
		$template->data['footerlinks']['places'][] = 'femiwiki-report-infringement-label';

		return true;
	}

	/**
	 * 페미위키로 통하는 외부 링크는 내부 링크로 취급합니다.
	 */
	public static function onLinkerMakeExternalLink( &$url, &$text, &$link, &$attribs, $linktype ) { 
		global $wgCanonicalServer;

		if ( strpos( $wgCanonicalServer, parse_url( $url, PHP_URL_HOST )) === false ) {
			return true;
		}

		$attribs['class'] = str_replace( 'external', '', $attribs['class'] );
		$attribs['href'] = $url;
		unset( $attribs['target'] );

		$link = Html::rawElement( 'a', $attribs, $text );
		return false;
	}
}
