<?php

class FemiwikiHooks {

	/**
	 * 푸터에 몇 링크들을 추가합니다.
	 */
	public static function onSkinTemplateOutputPageBeforeExec( &$skin, &$template ) {
		// 이용 약관을 추가합니다. 이용 약관은 앞쪽에 추가합니다.
		$template->set( 'femiwiki-terms-label', $template->getSkin()->footerLink( 'femiwiki-terms-label', 'femiwiki-terms-page' ) );
		array_unshift( $template->data['footerlinks']['places'], 'femiwiki-terms-label' );

		// 권리 침해 신고를 추가합니다.
		$template->set( 'femiwiki-report-infringement-label', $template->getSkin()->footerLink( 'femiwiki-report-infringement-label', 'femiwiki-report-infringement-page' ) );
		$template->data['footerlinks']['places'][] = 'femiwiki-report-infringement-label';
	}
}

?>
