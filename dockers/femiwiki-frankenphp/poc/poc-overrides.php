<?php
// PoC-only deltas. Disable cloud/anti-abuse integrations that cannot work on a laptop, WITHOUT
// enabling DevelopmentSettings.php (no debug toolbar, no display_errors) so soak latency/RSS stay
// representative of production. Mounted at /a/poc-overrides.php and required from Hotfix.php.

// No S3: ext:AWS off so uploads/thumbs use the local filesystem (LocalSettings.php:439-444).
$wgAWSBucketName   = null;
$wgAWSBucketPrefix = null;

// No reCAPTCHA on a laptop.
$wgCaptchaTriggers['createaccount'] = false;
$wgCaptchaTriggers['edit']          = false;
$wgCaptchaTriggers['create']        = false;
$wgCaptchaTriggers['badlogin']      = false;

// Plain-HTTP PoC: no CDN purging, allow anon edit so the smoke test can write without email-confirm.
$wgUseCdn             = false;
$wgEmailConfirmToEdit = false;

// Plain-HTTP PoC has no edge sending X-Forwarded-Proto: https, so $wgForceHTTPS (LocalSettings.php:35)
// would 301-redirect every request. Disable it for the PoC only; production keeps it (the edge Caddy
// sends X-Forwarded-Proto: https and the backend trusts it).
$wgForceHTTPS = false;

// Keep error visibility production-like (do NOT turn on display_errors).
