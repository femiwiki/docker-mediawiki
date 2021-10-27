<?php
// This file was automatically generated by the MediaWiki 1.27.0
// installer at first.
//
// See includes/DefaultSettings.php for all configurable settings
// and their default values, but don't forget to make changes in _this_
// file, not there.
//
// Further documentation for configuration settings may be found at:
// https://www.mediawiki.org/wiki/Manual:Configuration_settings

// Protect against web entry
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

$wgSitename = '페미위키';

// The URL base path to the directory containing the wiki;
// defaults for all runtime URL paths are based off of this.
// For more information on customizing the URLs
// (like /w/index.php/Page_title to /wiki/Page_title) please see:
// https://www.mediawiki.org/wiki/Manual:Short_URL
$wgScriptPath = '';
$wgArticlePath = "/w/$1";

// The protocol and server name to use in fully-qualified URLs
$wgServer = 'https://femiwiki.com';
$wgCanonicalServer = 'https://femiwiki.com';
// Used to purge CDN cache (https://github.com/femiwiki/femiwiki/issues/239)
$wgInternalServer = 'http://' . ( getenv( 'NOMAD_UPSTREAM_ADDR_http' ) ?: 'http:8080' );
$wgEnableCanonicalServerLink = true;

// Determines how section IDs should be encoded
// Must be either [ 'html5', 'legacy' ] or [ 'html5' ] for DiscussionTools
$wgFragmentMode = [ 'html5', 'legacy' ];

// Make the HTTP to HTTPS redirect be unconditional
$wgForceHTTPS = true;

// The URL path to static resources (images, scripts, etc.)
$wgResourceBasePath = $wgScriptPath;

$wgStyleVersion = '20191101_0';

// The URL path to the logo.
$wgLogos = [
	// 'icon' is used by modern Skin:Vector (maximally 50x50)
	'icon' => "$wgResourceBasePath/fw-resources/symbol-transparent-white.svg",
	// 'svg' and 'wordmark' are used by Skin:Femiwiki
	'svg' => "$wgResourceBasePath/fw-resources/symbol-transparent-white.svg",
	'wordmark' => [
		'src' => "$wgResourceBasePath/fw-resources/logo-transparent-white.svg",
		// The unit of below sizes is considered pixel by Skin:Vector
		// Skin:Femiwiki uses only the ratio of them.
		'width' => 142.93,
		'height' => 44.61,
	]
];
$wgAppleTouchIcon = "$wgResourceBasePath/fw-resources/favicons/favicon-180.png";

$wgSMTP = [
	'host' => 'email-smtp.us-east-1.amazonaws.com',
	'IDHost' => 'femiwiki.com',
	'port' => 587,
	'auth' => true,
	'username' => 'AKIAJ472HG7XALTXZ5QA',
];

// UPO means: this is also a user preference option
//
// Reference:
// - https://www.mediawiki.org/wiki/Help:User_preference_option
$wgEnableEmail = true;
// UPO
$wgEnableUserEmail = true;
$wgAllowHTMLEmail = true;
$wgUserEmailUseReplyTo = true;

$wgEmergencyContact = 'admin@femiwiki.com';
$wgPasswordSender = 'admin@femiwiki.com';

// UPO
$wgEnotifUserTalk = false;
// UPO
$wgEnotifWatchlist = false;
$wgEmailAuthentication = true;
$wgEmailConfirmToEdit = true;
$wgEnableUserEmailBlacklist = true;
$wgEnableSpecialMute = true;
$wgUnwatchedPageThreshold = 0;
$wgWatchlistExpiry = true;

// Database settings
$wgDBtype = 'mysql';
$wgDBname = 'femiwiki';

// MySQL specific settings
$wgDBprefix = '';

// MySQL table options to use during installation or update
$wgDBTableOptions = 'ENGINE=InnoDB, DEFAULT CHARSET=binary';

// Change the default password type to use when hashing user passwords.
$wgPasswordDefault = 'argon2';

// Change settings related to password strength and security.
$wgPasswordPolicy['policies']['default']['MinimalPasswordLength'] = [
	'value' => 8, 'suggestChangeOnLogin' => true
];

// Enable database-intensive features
$wgMiserMode = true;

// Make no jobs will be performed during ordinary requests
$wgJobRunRate = 0;

// Shared memory settings
$wgMainCacheType = CACHE_MEMCACHED;
$wgSessionCacheType = CACHE_MEMCACHED;
$wgParserCacheType = CACHE_MEMCACHED;
$wgMessageCacheType = CACHE_MEMCACHED;
$wgMemCachedServers = [ getenv( 'NOMAD_UPSTREAM_ADDR_memcached' ) ?: 'memcached:11211' ];

// HTTP Cache setting
$wgUseCdn = true;
$wgCdnServers = [ getenv( 'NOMAD_UPSTREAM_ADDR_http' ) ?: 'http:8080' ];

// To enable image uploads, make sure the 'images' directory
// is writable, then set this to true:
$wgEnableUploads = true;
$wgFileExtensions = array_merge(
	$wgFileExtensions, [
		'pdf',
		'svg',
	]
);
$wgUseImageMagick = true;
$wgImageMagickConvertCommand = '/usr/bin/convert';
$wgSVGConverter = 'rsvg';
$wgNativeImageLazyLoading = true;

// InstantCommons allows wiki to use images from https://commons.wikimedia.org
$wgUseInstantCommons = true;

// If you use ImageMagick (or any other shell command) on a
// Linux server, this will need to be set to the name of an
// available UTF-8 locale
$wgShellLocale = 'C.UTF-8';

// Set $wgCacheDirectory to a writable directory on the web server
// to make your wiki go slightly faster. The directory should not
// be publically accessible from the web.
$wgCacheDirectory = '/tmp/cache';

// Site language code, should be one of the list in ./languages/data/Names.php
$wgLanguageCode = 'ko';
$wgLocaltimezone = 'Asia/Seoul';
date_default_timezone_set( $wgLocaltimezone );
$wgDefaultUserOptions['timecorrection'] = 9;
$wgPageLanguageUseDB = true;

// Changing this will log out all existing sessions.
$wgAuthenticationTokenVersion = '1';

// For attaching licensing metadata to pages, and displaying an
// appropriate copyright notice / icon. GNU Free Documentation
// License and Creative Commons licenses are supported so far.
$wgRightsPage = '페미위키:저작권';
$wgRightsUrl = 'https://creativecommons.org/licenses/by-sa/4.0/deed.ko';
$wgRightsText = '크리에이티브 커먼즈 저작자표시-동일조건변경허락 4.0 국제 라이선스';
$wgRightsIcon = "$wgResourceBasePath/resources/assets/licenses/cc-by-sa.png";

// Open graph tags which should be added by all skins
$wgSkinMetaTags = [
	'og:title',
	'og:type',
	'twitter:card',
];

// Path to the GNU diff3 utility. Used for conflict resolution.
$wgDiff3 = '/usr/bin/diff3';

// Default skin: you can change the default skin. Use the internal symbolic
// names, ie 'vector', 'monobook':
$wgDefaultSkin = 'femiwiki';

// Enabled skins.
// The following skins were automatically enabled:
wfLoadSkin( 'Vector' );
wfLoadSkin( 'Femiwiki' );
$wgFemiwikiHeadItems = [
	'fav1' => '<link rel="icon" type="image/svg+xml" sizes="any" href="/fw-resources/favicons/favicon.svg">',
	'fav2' => '<link rel="icon" type="image/png" sizes="96x96" href="/fw-resources/favicons/favicon-96.png">',
	'fav3' => '<link rel="icon" type="image/png" sizes="32x32" href="/fw-resources/favicons/favicon-32.png">',
	'fav4' => '<link rel="icon" type="image/png" sizes="16x16" href="/fw-resources/favicons/favicon-16.png">',
	'fav6' => '<link rel="manifest" href="/fw-resources/favicons/manifest.json">',
	'fav7' => '<meta name="theme-color" content="#aca6e4">',
];
$wgFemiwikiTwitterAccount = 'femiwikidotcome';
$wgFemiwikiAddThisId = [
	'pub' => 'ra-5ffbebf1fd382d20',
	'tool' => 'ucmm',
];
// https://github.com/femiwiki/FemiwikiSkin/issues/14
$wgFemiwikiLegacySmallElementsForAnonymousUser = false;

//
// Namespace settings
//

// Define Namespaces
foreach ( [
	// Defined by extensions
	// phpcs:disable Squiz.WhiteSpace.OperatorSpacing.SpacingAfter
	'NS_ITEM'                   =>  120,
	'NS_ITEM_TALK'              =>  121,
	'NS_PROPERTY'               =>  122,
	'NS_PROPERTY_TALK'          =>  123,
	'NS_WIDGET'                 =>  274,
	'NS_WIDGET_TALK'            =>  275,
	'NS_MODULE'                 =>  828,
	'NS_MODULE_TALK'            =>  829,
	'NS_TRANSLATIONS'           => 1198,
	'NS_TRANSLATIONS_TALK'      => 1199,
	'NS_GADGET'                 => 2300,
	'NS_GADGET_TALK'            => 2301,
	'NS_GADGET_DEFINITION'      => 2302,
	'NS_GADGET_DEFINITION_TALK' => 2303,
	'NS_TOPIC'                  => 2600,
	'NS_NEWSLETTER'             => 5500,
	'NS_NEWSLETTER_TALK'        => 5501,
	// Others
	'NS_BBS'      => 3906,
	'NS_BBS_TALK' => 3907,
	// phpcs:enable
] as $k => $v ) {
	if ( !defined( $k ) ) {
		define( $k, $v );
	}
}

// BBS
$wgExtraNamespaces[NS_BBS] = '게시판';
$wgExtraNamespaces[NS_BBS_TALK] = '게시판토론';

// Permission
$wgGroupPermissions['*']['createaccount'] = true;
$wgGroupPermissions['sysop']['usermerge'] = true;
$wgGroupPermissions['sysop']['renameuser'] = true;
$wgGroupPermissions['sysop']['interwiki'] = true;
$wgGroupPermissions['sysop']['import'] = false;
$wgGroupPermissions['sysop']['importupload'] = false;
$wgGroupPermissions['oversight']['deletelogentry'] = true;
$wgGroupPermissions['oversight']['deleterevision'] = true;

// Prevent anonymous users from edit pages
$wgGroupPermissions['*']['edit'] = false;

// Set when users become autoconfirmed users
$wgAutoConfirmCount = 0;
$wgAutoConfirmAge = 3600;

$wgAutopromote = [
	'autoconfirmed' => [ '&',
		[ APCOND_EDITCOUNT, &$wgAutoConfirmCount ],
		[ APCOND_AGE, &$wgAutoConfirmAge ],
	],
];

// Allow autoconfirmed users to edit pages
$wgGroupPermissions['user']['edit'] = false;
$wgGroupPermissions['autoconfirmed']['edit'] = true;

// Importer
$wgGroupPermissions['importer']['import'] = true;

// FemiwikiTeam is just a list of all Femiwiki team member
$wgGroupPermissions['femiwiki-team']['editprotected'] = true;
$wgGroupPermissions['femiwiki-team']['editsemiprotected'] = true;
$wgGroupPermissions['femiwiki-team']['protect'] = true;

// Remain commemorative Seeder group
$wgGroupPermissions['seeder']['edit'] = true;

// Show numbers on headings
$wgDefaultUserOptions['numberheadings'] = 1;

// Do not show page content below diffs
$wgDefaultUserOptions['diffonly'] = '1';

// Enable Enhanced recent changes to opt-out
$wgDefaultUserOptions['rcenhancedfilters-disable'] = 0;

// Hide logs in Special:Recentchanges
$wgDefaultUserOptions['rcfilters-saved-queries'] = FormatJson::encode( [
	'queries' => [
		// random ID is generated by javascript code '( new Date() ).getTime()' originally
		'1000000000000' => [
			'data' => [
				'params' => [
					'hidelog' => '1',
					'hidebots' => '1',
					'translations' => 'filter',
				],
				'highlights' => []
			],
			'label' => '기본 필터',
		]
	],
	'version' => '2',
	'default' => '1000000000000',
] );

// Hide some Preferences
$wgHiddenPrefs[] = 'gender';
$wgHiddenPrefs[] = 'realname';
// See https://github.com/femiwiki/mediawiki/issues/211
$wgHiddenPrefs[] = 'numberheadings';

// Allow display titles not only to titles that normalize to the same canonical
// DB key as the real page title.
$wgRestrictDisplayTitle = false;

// Open external links in new tab
$wgExternalLinkTarget = '_blank';

// The number of authors that credited below an article text.
// https://github.com/femiwiki/FemiwikiSkin/issues/137
$wgMaxCredits = 5;

// User CSS and JS
$wgAllowUserCss = true;
$wgAllowUserJs = true;

// Allow external image link
$wgAllowExternalImages = true;

// all pages (that are not redirects) are considered as valid articles
$wgArticleCountMethod = 'any';

// Enable subpages in the main namespace
// See https://github.com/femiwiki/docker-mediawiki/issues/267
$wgNamespacesWithSubpages[NS_MAIN] = true;

// Prevent Search for some namespaces
$wgNamespaceRobotPolicies = [
	NS_USER => 'noindex,nofollow',
	NS_USER_TALK => 'noindex,nofollow',
];
$wgSitemapNamespaces = [
	NS_MAIN,
	NS_TALK,
	// Exclude noindex namespaces
	// See https://github.com/femiwiki/femiwiki/issues/211
	// NS_USER,
	// NS_USER_TALK,
	NS_PROJECT,
	NS_PROJECT_TALK,
	NS_FILE,
	NS_FILE_TALK,
	NS_MEDIAWIKI,
	NS_MEDIAWIKI_TALK,
	NS_TEMPLATE,
	NS_TEMPLATE_TALK,
	NS_HELP,
	NS_HELP_TALK,
	NS_CATEGORY,
	NS_CATEGORY_TALK,
	NS_ITEM,
	NS_ITEM_TALK,
	NS_PROPERTY,
	NS_PROPERTY_TALK,
	NS_WIDGET,
	NS_WIDGET_TALK,
	NS_MODULE,
	NS_MODULE_TALK,
	NS_TRANSLATIONS,
	NS_TRANSLATIONS_TALK,
	NS_GADGET,
	NS_GADGET_TALK,
	NS_GADGET_DEFINITION,
	NS_GADGET_DEFINITION_TALK,
	NS_TOPIC,
	NS_NEWSLETTER,
	NS_NEWSLETTER_TALK,
];

// Provide Namespace Aliases
$wgNamespaceAliases = [
	'도' => NS_HELP,
	'페' => NS_PROJECT
];

// Parsoid Setting
$wgParsoidSettings = [
	'linting' => true
];

# Disable "zero configuration" VisualEditor
# zero-conf VisualEditor assumes that all the services are served as the same host. ('/' for
# MediaWiki, '/rest.php/<domain>/v3/' for Parsoid and '/restbase/<domain>/v1/' for RESTBase)
# It is not our use case, we are serving those services behind the orchestration tool, Docker or
# Nomad and a variety of addresses are used.
$wgVisualEditorParsoidAutoConfig = false;

$wgVirtualRestConfig = [
	'modules' => [
		'parsoid' => [
			'url' => 'http://' . ( getenv( 'NOMAD_UPSTREAM_ADDR_http' ) ?: 'http:8080' ) . '/rest.php',
		],
		'restbase' => [
			'url' => 'http://' . ( getenv( 'NOMAD_UPSTREAM_ADDR_restbase' ) ?: 'restbase:7231' ),
			# https://github.com/femiwiki/femiwiki/issues/266
			'domain' => 'femiwiki.com',
		],
	],
	'global' => [
		'domain' => 'femiwiki.com',
		'restbaseCompat' => true,
		'forwardCookies' => false,
	],
];

$wgVisualEditorRestbaseURL = 'https://femiwiki.com/femiwiki.com/v1/page/html/';
$wgVisualEditorFullRestbaseURL = 'https://femiwiki.com/femiwiki.com/';
$wgMathFullRestbaseURL = 'https://femiwiki.com/femiwiki.com/';

wfLoadExtension( 'Parsoid', 'vendor/wikimedia/parsoid/extension.json' );

//
// Extensions
//

// AbuseFilter
wfLoadExtension( 'AbuseFilter' );
$wgGroupPermissions['sysop']['abusefilter-modify'] = false;
$wgGroupPermissions['abusefilter']['abusefilter-modify'] = true;
$wgGroupPermissions['abusefilter']['changetags'] = true;
$wgGroupPermissions['abusefilter']['managechangetags'] = true;
$wgGroupPermissions['sysop']['abusefilter-modify-restricted'] = false;
$wgGroupPermissions['abusefilter']['abusefilter-modify-restricted'] = true;
$wgGroupPermissions['*']['abusefilter-log-detail'] = true;
$wgGroupPermissions['*']['abusefilter-view'] = true;
$wgGroupPermissions['*']['abusefilter-log'] = true;
$wgGroupPermissions['sysop']['abusefilter-privatedetails'] = false;
$wgGroupPermissions['checkuser']['abusefilter-privatedetails'] = true;
$wgGroupPermissions['sysop']['abusefilter-revert'] = true;

// AchievementBadges
wfLoadExtension( 'AchievementBadges' );
$wgAchievementBadgesEnableBetaFeature = true;
$wgAchievementBadgesFacebookAppId = '1937597133150935';
$wgAchievementBadgesAddThisId = [
	'pub' => 'ra-5ffbebf1fd382d20',
	'tool' => 'kas4',
];

// AntiSpoof
wfLoadExtension( 'AntiSpoof' );

// AWS
wfLoadExtension( 'AWS' );
$wgAWSRegion = 'ap-northeast-1';
$wgAWSBucketPrefix = 'femiwiki-uploaded-files';
$wgAWSRepoHashLevels = 2;
$wgAWSRepoDeletedHashLevels = 2;

// BetaFeatures
wfLoadExtension( 'BetaFeatures' );

// BounceHandler
wfLoadExtension( 'BounceHandler' );

// CategoryTree
wfLoadExtension( 'CategoryTree' );

// CharInsert
wfLoadExtension( 'CharInsert' );

// CheckUser
wfLoadExtension( 'CheckUser' );

// Cite
wfLoadExtension( 'Cite' );

// CiteThisPage
wfLoadExtension( 'CiteThisPage' );

// Citoid
wfLoadExtension( 'Citoid' );
$wgCitoidFullRestbaseURL = 'https://ko.wikipedia.org/api/rest_';

// CodeEditor
wfLoadExtension( 'CodeEditor' );

// CodeMirror
wfLoadExtension( 'CodeMirror' );

// ConfirmEdit
wfLoadExtensions( [ 'ConfirmEdit', 'ConfirmEdit/ReCaptchaNoCaptcha' ] );
$wgCaptchaClass = 'ReCaptchaNoCaptcha';
$wgCaptchaTriggers['createaccount'] = true;
// If you plan to use VisualEditor forget about this new and better No Captcha solution from Google.
$wgCaptchaTriggers['edit'] = false;
$wgCaptchaTriggers['create'] = false;
$wgCaptchaTriggers['addurl'] = false;
$wgCaptchaTriggers['badlogin'] = false;

// Description2
wfLoadExtension( 'Description2' );

// DisableAccount
wfLoadExtension( 'DisableAccount' );
$wgGroupPermissions['sysop']['disableaccount'] = true;

// Disambiguator
wfLoadExtension( 'Disambiguator' );

// DiscordRCFeed
wfLoadExtension( 'DiscordRCFeed' );
$wgRCFeeds['discord'] = [
	'omit_bots' => true,
	'omit_namespaces' => [
		NS_TRANSLATIONS,
	],
	'omit_log_types' => [
		'patrol',
		// To prevent abusing with names.
		'newusers',
	],
	'user_tools' => [
		[
			'target' => 'user_page',
			'msg' => 'nstab-user'
		],
		[
			'target' => 'special',
			'special' => 'Sanctions',
			'msg' => 'sanctions-link-on-user-page'
		],
		[
			'target' => 'talk',
			'msg' => 'talkpagelinktext'
		],
		[
			'target' => 'special',
			'special' => 'Contributions',
			'msg' => 'contribslink'
		]
	],
];

// DiscussionTools
wfLoadExtension( 'DiscussionTools' );
$wgDiscussionToolsEnableVisual = true;

// Echo
wfLoadExtension( 'Echo' );
$wgEchoMaxMentionsInEditSummary = 5;
$wgEchoPollForUpdates = 60;

// EmbedVideo
wfLoadExtension( 'EmbedVideo' );

// EventLogging
wfLoadExtension( 'EventLogging' );

// FacetedCategory
wfLoadExtension( 'FacetedCategory' );

// FlaggedRevs
wfLoadExtension( 'FlaggedRevs' );
$wgFlaggedRevsNamespaces = [
	NS_MAIN,
	NS_PROJECT,
	NS_TEMPLATE,
	// Module
	828,
];
// Use FlaggedRevs only as a protection-like mechanism
$wgFlaggedRevsProtection = true;
// Disable Special:ValidationStatistics updates
$wgFlaggedRevsStatsAge = false;
// Changes the settings of stable revisions of any page
// FR_SHOW_STABLE_ALWAYS is 1.
$wgDefaultUserOptions[ 'flaggedrevsstable' ] = 1;
// Group permissions for femiwiki-team
$wgGroupPermissions['femiwiki-team']['review'] = true;
$wgGroupPermissions['femiwiki-team']['validate'] = true;
$wgGroupPermissions['femiwiki-team']['autoreview'] = true;
$wgGroupPermissions['femiwiki-team']['autoreviewrestore'] = true;
$wgGroupPermissions['femiwiki-team']['movestable'] = true;
$wgGroupPermissions['femiwiki-team']['stablesettings'] = true;
// Everyone can view Special:UnreviewedPages
$wgGroupPermissions['*']['unreviewedpages'] = true;

// Flow
wfLoadExtension( 'Flow' );
$wgFlowEditorList = [ 'visualeditor', 'none' ];
foreach ( [
	NS_TALK,
	NS_USER_TALK,
	NS_PROJECT_TALK,
	NS_FILE_TALK,
	NS_MEDIAWIKI_TALK,
	NS_TEMPLATE_TALK,
	NS_HELP_TALK,
	NS_CATEGORY_TALK,
	NS_ITEM_TALK,
	NS_PROPERTY_TALK,
	NS_WIDGET_TALK,
	NS_MODULE_TALK,
	NS_TRANSLATIONS_TALK,
	NS_GADGET_TALK,
	NS_GADGET_DEFINITION_TALK,
	NS_NEWSLETTER_TALK,
	NS_BBS,
	NS_BBS_TALK,
] as $space ) {
	$wgNamespaceContentModels[$space] = 'flow-board';
}
$wgFlowDefaultLimit = 2;

// Gadgets
wfLoadExtension( 'Gadgets' );
$wgGadgetsRepoClass = 'GadgetDefinitionNamespaceRepo';
$wgGroupPermissions['interface-admin']['gadgets-edit'] = true;
$wgGroupPermissions['interface-admin']['gadgets-definition-edit'] = true;
$wgGrantPermissions['editinterface']['gadgets-edit'] = true;
$wgGrantPermissions['editinterface']['gadgets-definition-edit'] = true;

// Graph
wfLoadExtension( 'Graph' );

// GrowthExperiments
wfLoadExtension( 'GrowthExperiments' );

// HelpPanel
$wgGEHelpPanelReadingModeNamespaces = [
	NS_MAIN,
	NS_PROJECT,
	NS_USER,
	NS_TEMPLATE,
	NS_HELP,
	NS_CATEGORY,
	NS_MODULE,
];

// Cannot be configured via on-wiki configuration in MW 1.36
// https://phabricator.wikimedia.org/T215911
$wgGEHelpPanelLinks = [
	[
		"title" => "도움말:초보자 도움말",
		"text" => "초보자 도움말",
		"id" => "newcomer",
	],
	[
		"title" => "도움말:문서 이름 바꾸기",
		"text" => "문서 이름 바꾸기",
		"id" => "move",
	],
];
$wgGEHelpPanelHelpDeskTitle = '게시판:질문게시판';
$wgGEHelpPanelViewMoreTitle = '도움말:색인';

// Disable Mentorship, we have few experienced users...
$wgGEMentorshipEnabled = false;

// Disable SuggestedEdits which requires either CirrusSearch or ORES.
$wgGEHomepageSuggestedEditsEnabled = false;

// Disable Welcome Survey
// (Visit https://en.wikipedia.org/wiki/Special:WelcomeSurvey to see an example)
$wgWelcomeSurveyEnabled = false;

// Do not override messages of ConfirmEdit and confirm mail
$wgGEConfirmEmailEnabled = false;

// We don't collect data via Extension:EventStream
$wgGEHelpPanelLoggingEnabled = false;
$wgGEHomepageLoggingEnabled = false;

// GuidedTour
wfLoadExtension( 'GuidedTour' );

// HTMLTags
require_once "$IP/extensions/HTMLTags/HTMLTags.php";
$wgHTMLTagsAttributes['a'] = [ 'href', 'class', 'itemprop' ];
$wgHTMLTagsAttributes['link'] = [ 'href', 'itemprop' ];
$wgHTMLTagsAttributes['meta'] = [ 'content', 'itemprop' ];

// InputBox
wfLoadExtension( 'InputBox' );

// Interwiki
wfLoadExtension( 'Interwiki' );

// Josa
wfLoadExtension( 'Josa' );

// Linter
wfLoadExtension( 'Linter' );

// LocalisationUpdate
wfLoadExtension( 'LocalisationUpdate' );
$wgLocalisationUpdateRepositories = [
	'github' => [
		'mediawiki' => 'https://raw.github.com/wikimedia/mediawiki/master/%PATH%',
		'extension' => 'https://raw.github.com/wikimedia/mediawiki-extensions-%NAME%/master/%PATH%',
		'skin' => 'https://raw.github.com/wikimedia/mediawiki-skins-%NAME%/master/%PATH%'
	],
	'femiwiki' => [
		'extension' => 'https://raw.github.com/femiwiki/%NAME%/main/%PATH%',
		'skin' => 'https://raw.github.com/femiwiki/FemiwikiSkin/main/%PATH%',
	],
];
$wgLocalisationUpdateHttpRequestOptions = [
	'followRedirects' => true,
];

// LoginNotify
wfLoadExtension( 'LoginNotify' );

// Math
wfLoadExtension( 'Math' );
$wgDefaultUserOptions['math'] = 'mathml';
// IP of Mathoid server
$wgMathMathMLUrl = 'http://' . ( getenv( 'NOMAD_UPSTREAM_ADDR_mathoid' ) ?: 'mathoid:10044' );

// MobileFrontend
wfLoadExtension( 'MobileFrontend' );
$wgMFMwApiContentProviderBaseUri = $wgCanonicalServer . '/api.php';
$wgMFMcsContentProviderBaseUri = $wgCanonicalServer . '/femiwiki.com/v1';
// Disable automatically showing mobile view, as FemiwikiSkin is little responsive and
// MobileFrontend is not tested enough.
$wgMFAutodetectMobileView = false;
// Enable mobile preferences in Special:Preferences
$wgMFEnableMobilePreferences = true;
// Disable CollapsibleSections in anywhere.
$wgMFNamespacesWithoutCollapsibleSections = [
	NS_MAIN,
	NS_TALK,
	NS_USER,
	NS_USER_TALK,
	NS_PROJECT,
	NS_PROJECT_TALK,
	NS_FILE,
	NS_FILE_TALK,
	NS_MEDIAWIKI,
	NS_MEDIAWIKI_TALK,
	NS_TEMPLATE,
	NS_TEMPLATE_TALK,
	NS_HELP,
	NS_HELP_TALK,
	NS_CATEGORY,
	NS_CATEGORY_TALK,
	// Don't collapse various forms
	NS_SPECIAL,
	// Just don't
	NS_MEDIA,
];
// Disable mobile transformations to page content.
$wgMFMobileFormatterNamespaceBlacklist = [
	NS_MAIN,
	NS_TALK,
	NS_USER,
	NS_USER_TALK,
	NS_PROJECT,
	NS_PROJECT_TALK,
	NS_FILE,
	NS_FILE_TALK,
	NS_MEDIAWIKI,
	NS_MEDIAWIKI_TALK,
	NS_TEMPLATE,
	NS_TEMPLATE_TALK,
	NS_HELP,
	NS_HELP_TALK,
	NS_CATEGORY,
	NS_CATEGORY_TALK,
];
$wgDefaultUserOptions['mobile-specialpages'] = false;
// Use the user's preferred editor (i.e. visual editor or source editor)
$wgMFUsePreferredEditor = true;
// Advanced mode is available for users
$wgMFAdvancedMobileContributions = true;
// Enable the use Wikibase and associated features
$wgMFUseWikibase = true;
$wgMFBetaFeedbackLink = true;
$wgMFEnableWikidataDescriptions = [
	'base' => false,
	'beta' => false,
	'amc' => false,
];

// Newsletter
wfLoadExtension( 'Newsletter' );

// Nuke
wfLoadExtension( 'Nuke' );

// OATHAuth
wfLoadExtension( 'OATHAuth' );

// PageImages
wfLoadExtension( 'PageImages' );
$wgPageImagesLeadSectionOnly = false;
$wgPageImagesOpenGraphFallbackImage = "/fw-resources/favicons/favicon-512.png";

// PageViewInfo
wfLoadExtension( 'PageViewInfo' );

// PageViewInfoGA
wfLoadExtension( 'PageViewInfoGA' );
$wgPageViewInfoGACredentialsFile = '/a/analytics-credentials-file.json';
$wgPageViewInfoGATrackingID = 'UA-82072330-1';
$wgPageViewInfoGAProfileId = '127138848';
$wgPageViewInfoGAReadCustomDimensions = true;

// ParserFunctions
wfLoadExtension( 'ParserFunctions' );
$wgPFEnableStringFunctions = true;

// Poem
wfLoadExtension( 'Poem' );

// Popups
wfLoadExtension( 'Popups' );
$wgPopupsOptInDefaultState = '1';
$wgDefaultUserOptions[ 'popupsreferencepreviews' ] = '1';

// RelatedArticles
wfLoadExtension( 'RelatedArticles' );
$wgRelatedArticlesFooterWhitelistedSkins = [
	'femiwiki',
	'vector'
];
$wgRelatedArticlesCardLimit = 6;
$wgRelatedArticlesDescriptionSource = 'textextracts';

// Renameuser
wfLoadExtension( 'Renameuser' );

// ReplaceText
wfLoadExtension( 'ReplaceText' );

// RevisionSlider
wfLoadExtension( 'RevisionSlider' );

// Sanctions
wfLoadExtension( 'Sanctions' );

// Scribunto
wfLoadExtension( 'Scribunto' );
$wgScribuntoDefaultEngine = 'luastandalone';
if ( php_uname( 'm' ) == 'aarch64' ) {
	$wgScribuntoEngineConf['luastandalone']['luaPath'] = '/usr/bin/lua';
}

// SecureLinkFixer
wfLoadExtension( 'SecureLinkFixer' );

// SpamBlacklist
wfLoadExtension( 'SpamBlacklist' );
// Empty Meta-Wiki blacklist
$wgBlacklistSettings = [
	'spam' => [
		'files' => []
	]
];

// SyntaxHighlight
wfLoadExtension( 'SyntaxHighlight_GeSHi' );

// TemplateData
wfLoadExtension( 'TemplateData' );

// TemplateSandbox
wfLoadExtension( 'TemplateSandbox' );

// TemplateStyles
wfLoadExtension( 'TemplateStyles' );
$wgTemplateStylesAllowedUrls = [
	'font' => [
		'<^https://fonts\\.googleapis\\.com/css(?:[?#]|$)>',
	],
];

// TemplateWizard
wfLoadExtension( 'TemplateWizard' );
$wgDefaultUserOptions['templatewizard-betafeature'] = 1;

// TextExtracts
wfLoadExtension( 'TextExtracts' );

// Thanks
wfLoadExtension( 'Thanks' );

// TitleBlacklist
wfLoadExtension( 'TitleBlacklist' );

// Translate
wfLoadExtension( 'Translate' );
$wgGroupPermissions['autoconfirmed']['translate'] = true;
$wgGroupPermissions['translationadmin']['pagetranslation'] = true;
$wgGroupPermissions['translationadmin']['translate-manage'] = true;
$wgGroupPermissions['translationadmin']['translate-messagereview'] = true;
$wgGroupPermissions['translationadmin']['pagelang'] = true;
$wgTranslatePageTranslationULS = true;
$wgPageTranslationLanguageList = 'sidebar-always';
$wgTranslatePermissionUrl = 'Project:번역';
$wgTranslateSecondaryPermissionUrl = 'Project:번역';

// TwoColConflict
wfLoadExtension( 'TwoColConflict' );
// Enable twocolconflict to opt-out
$wgDefaultUserOptions['twocolconflict'] = '1';

// UnifiedExtensionForFemiwiki
wfLoadExtension( 'UnifiedExtensionForFemiwiki' );
$wgUnifiedExtensionForFemiwikiPreAuth = true;
$wgSpecialPages['Whatlinkshere'] = [
	'class' => 'MediaWiki\Extension\UnifiedExtensionForFemiwiki\Specials\SpecialOrderedWhatLinksHere',
	'services' => [
		'DBLoadBalancer',
		'LinkBatchFactory',
		'ContentHandlerFactory',
		'SearchEngineFactory',
		'NamespaceInfo',
	]
];
$wgUnifiedExtensionForFemiwikiRelatedArticlesTargetNamespaces = [
	NS_MAIN,
	NS_PROJECT,
	NS_CATEGORY,
];
$wgUnifiedExtensionForFemiwikiSoftDefaultOptions = [
	'visualeditor-newwikitext' => 1,
	'visualeditor-tabs' => 'prefer-ve',
	// https://github.com/femiwiki/FemiwikiSkin/issues/14
	'FemiwikiUseLargerElements' => 1,
];

// UniversalLanguageSelector
wfLoadExtension( 'UniversalLanguageSelector' );
$wgULSPosition = 'interlanguage';
$wgULSIMEEnabled = false;
$wgULSCompactLinksEnableAnon = true;
// Enable ULS compact links beta feature to opt-out
$wgDefaultUserOptions['uls-compact-links'] = 1;

// UploadWizard
wfLoadExtension( 'UploadWizard' );
// Needed to make UploadWizard work in IE, see https://phabricator.wikimedia.org/T41877
$wgApiFrameOptions = 'SAMEORIGIN';
$wgExtensionFunctions[] = static function () {
	$GLOBALS['wgUploadNavigationUrl'] = SpecialPage::getTitleFor( 'UploadWizard' )->getLocalURL();
	return true;
};
$wgUploadWizardConfig = [];
$wgUploadWizardConfig['alternativeUploadToolsPage'] = '특수:파일올리기';
$wgUploadWizardConfig['licensing']['thirdParty']['licenseGroups'] = [
	[
		'head' => 'mwe-upwiz-license-cc-head',
		'subhead' => 'mwe-upwiz-license-cc-subhead',
		'licenses' => [
			'cc-by-sa-4.0',
			'cc-by-sa-3.0',
			'cc-by-sa-2.5',
			'cc-by-4.0',
			'cc-by-3.0',
			'cc-by-2.5',
			'cc-zero'
		]
	],
	[
		'head' => 'mwe-upwiz-license-custom-head',
		'special' => 'custom',
		'licenses' => [ 'custom' ],
	],
	[
		'head' => 'mwe-upwiz-license-none-head',
		'licenses' => [ 'none' ]
	],
];
// Skip the tutorial
$wgUploadWizardConfig['tutorial'] = [ 'skip' => true ];
$wgUploadWizardConfig['uwLanguages'] = [
	'en' => 'English',
	'ko' => '한국어',
];
$wgDefaultUserOptions['upwiz_skiptutorial'] = 1;
$wgHiddenPrefs[] = 'upwiz_skiptutorial';
// Tweaks for permissions
$wgGroupPermissions['sysop']['upwizcampaigns'] = false;
$wgGroupPermissions['sysop']['mass-upload'] = false;
$wgAddGroups['sysop']['upwizcampeditors'] = false;
$wgRemoveGroups['sysop']['upwizcampeditors'] = false;
$wgAddGroups['bureaucrat']['upwizcampeditors'] = true;
$wgRemoveGroups['bureaucrat']['upwizcampeditors'] = true;

// UserMerge
wfLoadExtension( 'UserMerge' );

// VisualEditor
wfLoadExtension( 'VisualEditor' );
$wgVisualEditorAvailableNamespaces = [
	NS_SPECIAL => true,
	NS_MAIN => true,
	NS_TALK => true,
	NS_USER => true,
	NS_USER_TALK => true,
	NS_PROJECT => true,
	NS_PROJECT_TALK => true,
	NS_FILE => true,
	NS_FILE_TALK => true,
	NS_MEDIAWIKI => true,
	NS_MEDIAWIKI_TALK => true,
	NS_TEMPLATE => true,
	NS_TEMPLATE_TALK => true,
	NS_HELP => true,
	NS_HELP_TALK => true,
	NS_CATEGORY => true,
	NS_CATEGORY_TALK => true,
	'_merge_strategy' => 'array_plus',
];
$wgVisualEditorEnableTocWidget = true;
// Enable 2017 Wikitext Editor to opt-out
$wgVisualEditorEnableWikitext = true;
$wgDefaultUserOptions['visualeditor-newwikitext'] = 1;
// Enable Visual diffs on history pages
$wgVisualEditorEnableDiffPage = true;
// Enable Single Edit Tab to opt-in
$wgVisualEditorUseSingleEditTab = true;
$wgDefaultUserOptions['visualeditor-tabs'] = 'multi-tab';
// Enable the section editing
$wgVisualEditorEnableVisualSectionEditing = true;
// Disallow switching from wikitext to visual editor if doing so may cause dirty diffs
$wgVisualEditorAllowLossySwitching = false;

// Widgets
require_once "$IP/extensions/Widgets/Widgets.php";
$wgNamespaceContentModels[274] = CONTENT_MODEL_TEXT;

// WikiBase - repo
wfLoadExtension( 'WikibaseRepository', "$IP/extensions/Wikibase/extension-repo.json" );
require_once "$IP/extensions/Wikibase/repo/ExampleSettings.php";
$wgWBRepoSettings['enableEntitySearchUI'] = false;
$wgWBRepoSettings['siteLinkGroups'] = [ 'femiwiki' ];
$wgWBRepoSettings['dataBridgeEnabled'] = true;
$wgWBRepoSettings['conceptBaseUri'] = $wgCanonicalServer . str_replace( '$1', 'Item:', $wgArticlePath );

// WikiBase - client
wfLoadExtension( 'WikibaseClient', "$IP/extensions/Wikibase/extension-client.json" );
require_once "$IP/extensions/Wikibase/client/ExampleSettings.php";
// See https://github.com/femiwiki/docker-mediawiki/issues/324
$wgWBClientSettings['dataBridgeEnabled'] = true;
$wgWBClientSettings['dataBridgeHrefRegExp'] = '^' . $wgCanonicalServer .
	str_replace( '$1', '(Item:(Q[1-9][0-9]*)).*#(P[1-9][0-9]*)', $wgArticlePath ) . '$';
$wgWBClientSettings['repoSiteName'] = 'wikibase-repo-site-name';

// WikiEditor
wfLoadExtension( 'WikiEditor' );
$wgDefaultUserOptions['usebetatoolbar'] = 1;
$wgDefaultUserOptions['usebetatoolbar-cgd'] = 1;
$wgDefaultUserOptions['wikieditor-preview'] = 1;
$wgDefaultUserOptions['wikieditor-publish'] = 1;
$wgHiddenPrefs[] = 'usebetatoolbar';
$wgDefaultUserOptions['visualeditor-enable-experimental'] = 1;

//
// Load secret.php
//
require_once '/a/secret.php';

//
// Overwrite server url
//
if ( getenv( 'MEDIAWIKI_SERVER' ) ) {
	$wgServer = getenv( 'MEDIAWIKI_SERVER' );
	$wgForceHTTPS = substr( $wgServer, 0, 5 ) === 'https';
	$wgCanonicalServer = $wgServer;
	$wgWBRepoSettings['conceptBaseUri'] = $wgServer . '/w/Item:';
	$wgWBClientSettings['dataBridgeHrefRegExp'] = '^' . $wgCanonicalServer .
		str_replace( '$1', '(Item:(Q[1-9][0-9]*)).*#(P[1-9][0-9]*)', $wgArticlePath ) . '$';

	$domain = getenv( 'MEDIAWIKI_DOMAIN_FOR_NODE_SERVICE' ) ?: 'femiwiki.com';
	$wgVisualEditorRestbaseURL = "$wgServer/$domain/v1/page/html/";
	$wgVisualEditorFullRestbaseURL = "$wgServer/$domain/";
	$wgMathFullRestbaseURL = "$wgServer/$domain/";
}

// Domain is an arbitrary keyword for communicate with MediaWiki node services
if ( getenv( 'MEDIAWIKI_DOMAIN_FOR_NODE_SERVICE' ) ) {
	$domain = getenv( 'MEDIAWIKI_DOMAIN_FOR_NODE_SERVICE' );
	$wgVirtualRestConfig['global']['domain'] = $domain;
	# https://github.com/femiwiki/femiwiki/issues/266
	$wgVirtualRestConfig['modules']['restbase']['domain'] = $domain;
	$wgVisualEditorRestbaseURL = "$wgServer/$domain/v1/page/html/";
	$wgVisualEditorFullRestbaseURL = "$wgServer/$domain/";
	$wgMathFullRestbaseURL = "$wgServer/$domain/";
}

//
// Debug Mode
//
if ( getenv( 'MEDIAWIKI_DEBUG_MODE' ) ) {
	$wgBounceHandlerInternalIPs = [ '0.0.0.0/0' ];

	// 디버그 툴 활성화
	require_once "includes/DevelopmentSettings.php";
	// Overwrite DevelopmentSettings
	$wgCacheDirectory = '/tmp/cache';

	$wgDebugToolbar = true;
	$wgShowDBErrorBacktrace = true;

	// 다음이 비활성화되어있어야 디버그 툴을 쓸 수 있음
	$wgUseFileCache = false;
	$wgUseCdn = false;

	// 이메일 인증 요구 비활성화
	$wgEmailConfirmToEdit = false;

	// AWS 플러그인 비활성화
	$wgAWSBucketName = null;
	$wgAWSBucketPrefix = null;

	// 구글 리캡차 비활성화
	$wgCaptchaTriggers['edit'] = false;
	$wgCaptchaTriggers['create'] = false;
	$wgCaptchaTriggers['createtalk'] = false;
	$wgCaptchaTriggers['addurl'] = false;
	$wgCaptchaTriggers['createaccount'] = false;
	$wgCaptchaTriggers['badlogin'] = false;

	// Google Analytics 기록 비활성화
	$wgPageViewInfoGATrackingID = false;

	// Google Analytics 읽어오기 비활성화
	$wgPageViewInfoGAProfileId = false;
}

require_once '/a/Hotfix.php';
