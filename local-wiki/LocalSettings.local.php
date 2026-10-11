<?php
/**
 * Mounted over /etc/mediawiki/Hotfix.php, so it runs after the production
 * LocalSettings.php and its MEDIAWIKI_DEBUG_MODE block.
 *
 * @file
 */

// Pages should look like femiwiki.com, so no debug toolbar under them
$wgDebugToolbar = false;

// Files come from femiwiki.com rather than being uploaded here
$wgForeignFileRepos[] = [
	'class' => ForeignAPIRepo::class,
	'name' => 'femiwiki',
	'apibase' => 'https://femiwiki.com/api.php',
	// FileBackendGroup reads it for every repo, though nothing is written there
	'directory' => '/tmp/cache/femiwiki-files',
	'hashLevels' => 2,
	'fetchDescription' => true,
	'descriptionCacheExpiry' => 86400,
	'apiThumbCacheExpiry' => 0,
];

// Needs production secrets or services
$wgEnableEmail = false;
$wgEnableUserEmail = false;
$wgRCFeeds = [];

// No CDN here; [''] from the unset WG_CDN_SERVERS would purge each URL itself
$wgCdnServers = [];

// DevelopmentSettings.php shrinks these for tests, which long articles and
// VisualEditor on them exceed; back to MediaWiki's defaults as in production
$wgMaxArticleSize = 2048;
unset( $wgParsoidSettings['wt2htmlLimits'], $wgParsoidSettings['html2wtLimits'] );
