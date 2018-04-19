<?php

class SanctionsUtils {
	/**
	 * 제재 절차에 참가 가능한지 알아봅니다.
	 * @param $user User 알아볼 사용자입니다.
	 * @param $reason array 참가 불가능할 경우 불가능한 이유들을 담은 배열입니다.
	 * @return 참가 여부를 반환합니다.
	 */
	public static function hasVoteRight ( User $user, &$reason = false ) {
		//로그인을 하지 않은 경우 불가
		if($user->isAnon()) {
			if ( $reason !== false ) $reason[] = '로그인을 하지 않음';
			return false;
		}

		$reg = $user->getRegistration();
		if(!$reg) if ( $reason !== false ) $reason[] = '가입일을 불러오는데 실패함'; else return false;

		//현재 편집 권한이 없을 경우 불가
		if( !$user->isAllowed('edit') )
			if ( $reason !== false ) $reason[] = '편집 권한이 없음'; else return false;

		$verificationPeriod = (float)wfMessage( 'sanctions-voting-right-verification-period' )->text();
		$verificationEdits = (int)wfMessage( 'sanctions-voting-right-verification-edits' )->text();
		
		$twentyDaysAgo = time()-( 60*60*24*$verificationPeriod );
		$twentyDaysAgo = wfTimestamp( TS_MW, $twentyDaysAgo );
		
	 	// 계정 생성 후 20일 이상 경과되지 않았을 경우 불가
		if ( $twentyDaysAgo < $reg )
			if ( $reason !== false ) $reason[] = '가입한 지 '.$verificationPeriod.'일이 경과하지 않음('.MWTimestamp::getLocalInstance( $reg )->getTimestamp( TS_ISO_8601 ).'에 가입)'; else return false;

		$db = wfGetDB( DB_MASTER );

		// 최근 20일 이내에 3회 이상의 기여 이력이 있음 (현재도 활동하고 있음)
		$count = $db->selectRowCount(
			'revision',
			'*',
			[
				'rev_user' => $user->getId(),
				'rev_timestamp > '.$twentyDaysAgo
			]
		);
		if ( $count < $verificationEdits ) if ( $reason !== false ) $reason[] = '최근 '.$verificationPeriod .'일 간 편집 수가 '.$count.'번으로 '.$verificationEdits.'보다 작음'; else return false;

		// 현재 제재되어 있는 경우 불가
		if ( $user->isBlocked() )
			if ( $reason !== false ) $reason[] = '차단되어 있음'; else return false;
		else {
			// 최근 20일 이내에 제재 이력이 없음 (최근 부정적 활동이 없었음)
			$blockExpiry = $db->selectField(
				'ipblocks',
				'MAX(ipb_expiry)',
				[
					'ipb_user' => $user->getId()
				],
				__METHOD__,
				['GROUP BY' => 'ipb_id']
			);
			if ( $blockExpiry > $twentyDaysAgo ) if ( $reason !== false ) $reason[] = '차단이 풀린 '.MWTimestamp::getLocalInstance( $blockExpiry )->getTimestamp( TS_ISO_8601 ).'으로부터 '.$verificationPeriod .'일이 경과하지 않음'; else return false;
		}

		if ( $reason !== false ) return count( $reason ) == 0 ;
		return true;
	}
}
