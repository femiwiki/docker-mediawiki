<?php

use Flow\Model\UUID;

class SanctionsPager extends IndexPager {
	protected $UserHasVoteRight = null;

	function __construct( $context, $targetName ) {
		parent::__construct( $context );
		$this->targetName = $targetName;
	}

	function getIndexField () {
		if ( $this->getUserHasVoteRight() )
			return 'not_expired';
		return 'st_expiry';
	}

	function getExtraSortFields () {
		if ( $this->getUserHasVoteRight() )
			return ['voted_from', 'st_expiry'];
		return null;
	}

	function getNavigationBar () {
		return '';
	}

	function getQueryInfo () {
		Sanction::checkAllSanctionNewVotes();
		$subquery = $this->mDb->selectSQLText(
				 		'sanctions_vote',
				 		[ 'stv_id', 'stv_topic' ],
				 		[ 'stv_user' => $this->getUser()->getId() ]
				 );
		$query = [
			'tables' => [
				 'sanctions'
			 ],
			'fields' => [
				'st_id',
				'st_author',
				'st_expiry',
				'not_expired' => 'st_expiry > '.wfTimestamp(TS_MW),
			],
			'conds' => [ 'st_handled' => 0 ]
		];

		if ( $this->targetName )
			$query['conds'][] = 'st_target = '.User::newFromName( $this->targetName )->getId();

		if ( $this->getUserHasVoteRight() ) {
			$query['tables']['sub'] = '('.$subquery.') AS'; // AS를 따로 써야 작동하길래 이렇게 썼는데 당최 이게 맞는지??
			$query['fields']['voted_from'] = 'stv_id';
			$query['join_conds'] = ['sub' => ['LEFT JOIN', 'st_topic = sub.stv_topic']];
		} else {
			// 제재 절차 참가 권한이 없을 때는 만료된 제재안은 보지 않습니다.		
			$query['conds'][] = 'st_expiry > '.wfTimestamp(TS_MW);
		}
		
		return $query;
	}

	function formatRow( $row ) {
		 //foreach($row as $key => $value) echo $key.'-'.$value.'<br/>';
		 //echo '<div style="clear:both;">------------------------------------------------</div>';
		$sanction = Sanction::newFromId( $row->st_id );

		if ( $this->getUserHasVoteRight() )
			$isVoted = $row->voted_from != null;
		
		$author = $sanction->getAuthor();
		$isMySanction = $author->equals( $this->getUser() );

		$expiry = $sanction->getExpiry();
		$expired = $sanction->isExpired();

		$process = $sanction->isEmergency() ? '긴급' : '일반';
		$passed = $sanction->isPassed() ? '가결' : '부결';

		if ( !$expired ) {
			$diff = MWTimestamp::getInstance( $expiry )->diff( MWTimestamp::getInstance() );
			if( $diff->d )
				$timeLeftText = $diff->d.'일 '.$diff->h.'시간 남음';
			elseif ( $diff->h )
				$timeLeftText = $diff->h.'시간 남음';
			elseif ( $diff->i )
				$timeLeftText = $diff->i.'분 남음';
			else
				$timeLeftText = $diff->s.'초 남음';
		}

		$target = $sanction->getTarget();

		$isForInsultingName = $sanction->isForInsultingName();
		$targetName = $target->getName();

		if ( $isForInsultingName ) {
			$originalName = $sanction->getTargetOriginalName();
			$length = mb_strlen($originalName, 'utf-8');
			$targetNameForDiplay = 
				mb_substr($originalName, 0, 1, 'utf-8')
				.str_pad('', $length-2, '*');

			if ( $length > 1 )
				$targetNameForDiplay .= iconv_substr($originalName, $length-1, $length, 'utf-8');
		} else 
			$targetNameForDiplay = $targetName;

		$topicTitle = $sanction->getTopic();

		$userLinkTitle = Title::newFromText(
			 strtok( $this->getTitle(), '/' )
			.'/'.$target->getName() 
		); // @todo 다른 방법 찾기

		$rowTitle = linker::link( $userLinkTitle, $targetNameForDiplay, ['class'=>'sanction-target']).' 님에 대한 ';
		$rowTitle .= linker::link( $topicTitle, $isForInsultingName ? '부적절한 사용자명 변경 건의' : '편집 차단 건의', [ 'class'=>'sanction-type' ] );

		$class = 'sanction';
		$class .=  ( $isMySanction ? ' my-sanction': '' )
			.( $isForInsultingName ? ' insulting-name' : ' block' )
			.( $sanction->isEmergency() ? ' emergency' : '' )
			.( $expired ? ' expired' : '' );
		if ( $this->getUserHasVoteRight() && !$isMySanction )
			$class .= $isVoted ? ' voted' : ' not-voted';

		$out = Html::openElement(
            'div',
            array('class' => $class)
        );
		if( $expired ) {
			$out .= Html::rawelement(
                'div',
                [ 'class' => 'sanction-expired' ],
                '처리 대기중'
            );
			$out .= Html::rawelement(
                'div',
                [ 'class' => 'sanction-pass-status' ],
                $passed
            );
        }
		if ( $this->getUserHasVoteRight() )
			$out .= Html::rawelement(
                'div',
                [ 'class' => 'sanction-vote-status' ],
                $isMySanction ? '내 제재안' : ( $isVoted ? '참여함' : '참여 전' )
            );
		if( !$expired )
			$out .= Html::rawelement(
                'div',
                [ 'class' => 'sanction-timeLeft' ],
                $timeLeftText
            );
		if( $expired && $this->getUserHasVoteRight() )
			$out .= $this->executeButton( $sanction->getId() );
		$out .= Html::rawelement(
            'div',
            ['class' => 'sanction-process'],
            $process
        );
		if( !$expired && $this->getUser()->isAllowed( 'block' ) )
			$out .= $this->processToggleButton( $sanction->getId() );

		$out .= Html::rawelement(
                        'div',
                        ['class' => 'sanction-title'],
                        $rowTitle
                    );

		return $out.Html::closeElement('div');
	}

	function getEmptyBody () {
		$text = '제재안이 없습니다.';

		if ( $this->targetName == null )
			$text = '현재 의결 중인 제재안이 없습니다.';
		else 
			$text = '현재 의결 중인 '.$this->targetName.'님에 대한 제재안이 없습니다.';
		return Html::rawelement(
            'div',
            ['class' => 'sanction-empty'],
            $text
        );
	}

	protected function processToggleButton( $sanctionId ) {
		$out = '';

		$out .= Xml::tags(
			'form',
			[
				'method' => 'post',
				'action' => $this->getContext()->getTitle()->getFullURL(),
				'class'=>'sanction-process-toggle'
			],
			Html::submitButton(
				'전환',
				[ 'class'=>'sanction-process-toggle-button'],
				[ 'mw-ui-progressive' ]
			) .
			Html::hidden(
				'token',
				$this->getUser()->getEditToken( 'sanctions' )
			) .
			Html::hidden(
				'sanctionId',
				$sanctionId
			).
			Html::hidden(
				'sanction-action',
				'toggle-emergency'
			)
		);

		return $out;
	}

	protected function executeButton($sanctionId) {
		$out = '';

		$out .= Xml::tags(
			'form',
			[
				'method' => 'post',
				'action' => $this->getContext()->getTitle()->getFullURL(),
				'class'=>'sanction-exectute-form'
			],
			Html::submitButton(
				'처리',
				[ 'class'=>'sanction-exectute-button' ],
				[ 'mw-ui-progressive' ]
			) .
			Html::hidden(
				'token',
				$this->getUser()->getEditToken( 'sanctions' )
			) .
			Html::hidden(
				'sanctionId',
				$sanctionId
			).
			Html::hidden(
				'sanction-action',
				'execute'
			)
		); 

		return $out;
	}

	protected function getUserHasVoteRight() {
		if ( $this->UserHasVoteRight === null )
			$this->UserHasVoteRight = SanctionsUtils::hasVoteRight( $this->getUser() );
		return $this->UserHasVoteRight;
	}
}