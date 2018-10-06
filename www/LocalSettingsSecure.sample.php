<?php
# Database settings
$wgDBserver = 'DB-HOST-NAME';
$wgDBuser = 'root';
$wgDBpassword = 'DB-PASSWORD';

# Mail
$wgSMTP = [
    'host' => "SMTP-HOST-NAME",
    'IDHost' => "femiwiki.com",
    'port' => 25,
    'auth' => true,
    'username' => "USERNAME",
    'password' => "PASSWORD",
];

# Other
$wgSecretKey = "SECRET-KEY";

# Site upgrade key. Must be set to a string (default provided) to turn on the
# web installer while LocalSettings.php is in place
$wgUpgradeKey = "UPGRADE-KEY";

# 점검이 끝나면 아래 라인 주석처리한 뒤, 아래 문서 내용을 비우면 됨
# https://femiwiki.com/w/%EB%AF%B8%EB%94%94%EC%96%B4%EC%9C%84%ED%82%A4:Sitenotice
# $wgReadOnly = '데이터베이스 업그레이드 작업이 진행 중입니다. 작업이 진행되는 동안 사이트 이용이 제한됩니다.';

# 업로드를 막고싶을때엔 아래 라인 주석 해제하면 됨
# $wgEnableUploads = false;
