<?php
# Mail
$wgSMTP = array(
    'host' => "SMTP-HOST-NAME",
    'IDHost' => "femiwiki.com",
    'port' => 25,
    'auth' => true,
    'username' => "USERNAME",
    'password' => "PASSWORD"
);

# Other
$wgSecretKey = "SECRET-KEY";

# Site upgrade key. Must be set to a string (default provided) to turn on the
# web installer while LocalSettings.php is in place
$wgUpgradeKey = "UPGRADE-KEY";

