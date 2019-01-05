<?php
// Below extensions have no submodule (at least in REL1_31)
// So we can download their by tar.gz file simply.
const EXTENSIONS_FROM_GERRIT_WITHOUT_SUBMODULE = [
	'TemplateData',
	'TwoColConflict',
	'RevisionSlider',
	'Echo',
	'Thanks',
	'Flow',
	'Scribunto',
	'TemplateStyles',
	'Disambiguator',
	'CreateUserPage',
	'AbuseFilter',
	'CheckUser',
	'UserMerge',
	'CodeMirror',
	'CharInsert',
	'Description2',
	'OpenGraphMeta',
	'PageImages',
	'Josa',
	'HTMLTags',
	'BetaFeatures',
];

// Below are extensions has no submodules too. But they are all on github/Femiwiki.
const EXTENSIONS_MADE_BY_FEMIWIKI = [
	'Sanctions',
	'CategoryIntersectionSearch',
	'FacetedCategory',
	'UnifiedExtensionForFemiwiki',
];

// Below extensions treated specially because of their complex download URL
const OTHER_EXTENSIONS = [
	'AWS',
	'EmbedVideo',
	'SimpleMathJax',
];
const OTHER_EXTENSIONS_URL = [
	'AWS' => 'https://github.com/edwardspec/mediawiki-aws-s3/archive/v0.10.0.tar.gz',
	'EmbedVideo' => 'https://github.com/HydraWiki/mediawiki-embedvideo/archive/v2.7.4.tar.gz',
	'SimpleMathJax' => 'https://github.com/jmnote/SimpleMathJax/archive/v0.7.3.tar.gz',
];

// Below extensions will be cloned by git command.
const EXTENSIONS_FROM_GERRIT_WITH_SUBMODULE = [
	'VisualEditor',
	'Widgets',
];

$allExtensions = array_merge (
	EXTENSIONS_FROM_GERRIT_WITH_SUBMODULE,
	EXTENSIONS_FROM_GERRIT_WITHOUT_SUBMODULE,
	EXTENSIONS_MADE_BY_FEMIWIKI,
	OTHER_EXTENSIONS
);

$mediawikiBranch = $argv[1];

$inputFile = '';

foreach ( EXTENSIONS_FROM_GERRIT_WITHOUT_SUBMODULE as $extension ) {
	$inputFile .=
		"https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/$extension/+archive/$mediawikiBranch.tar.gz" .
		PHP_EOL .
		" out=$extension.tar.gz" . PHP_EOL;
}

foreach ( EXTENSIONS_MADE_BY_FEMIWIKI as $extension ) {
    $lcfirst = lcfirst( $extension );
	$inputFile .=
		"https://github.com/femiwiki/$lcfirst/archive/master.tar.gz" .
		PHP_EOL .
        " out=$extension.tar.gz" . PHP_EOL;
}

// Extensions with complex download URL
foreach ( OTHER_EXTENSIONS_URL as $extension => $url ) {
	$inputFile .= $url . PHP_EOL . " out=$extension.tar.gz" . PHP_EOL;
}

// Femiwiki skin. Skins should be located in `skins/` directory
$inputFile .= 'https://github.com/femiwiki/skin/archive/master.tar.gz' . PHP_EOL . ' out=Femiwiki.tar.gz' . PHP_EOL;

// Download extensions which can be download using aria2
$tempDirectory = '/tmp';
$aria2 = popen("aria2c --input-file=- --dir=$tempDirectory", 'w');
fputs($aria2, $inputFile);
pclose($aria2);

// Make directories for each extensions.
foreach ( $allExtensions as $extension ) {
	mkdir( "/srv/femiwiki.com/extensions/$extension", 0755, true );
}

// Uncompress tar.gz files.
foreach ( EXTENSIONS_FROM_GERRIT_WITHOUT_SUBMODULE as $extension)  {
	exec( "tar -xzf '/tmp/$extension.tar.gz' --directory '/srv/femiwiki.com/extensions/$extension'" );
	unlink( "/tmp/$extension.tar.gz" );
}

// Uncompress tar.gz files and strip component
foreach ( array_merge ( EXTENSIONS_MADE_BY_FEMIWIKI, OTHER_EXTENSIONS ) as $extension ) {
	exec ( "tar -xzf '/tmp/$extension.tar.gz' --strip-components=1 --directory '/srv/femiwiki.com/extensions/$extension'" );
	unlink( "/tmp/$extension.tar.gz" );
}

// Clone extensions existing in gerrit and having submodule
foreach ( EXTENSIONS_FROM_GERRIT_WITH_SUBMODULE as $extension ) {
	exec (
        "git clone --recurse-submodules --depth 1 'https://gerrit.wikimedia.org/r/p/mediawiki/extensions/$extension' " .
        "-b '$mediawikiBranch' '/srv/femiwiki.com/extensions/$extension'"
    );
}

// Install composer dependencies via 'composer update'
foreach ( $allExtensions as $extension ) {
	if ( !file_exists( "/srv/femiwiki.com/extensions/$extension/composer.json" ) )
		continue;

    echo "Composing $extension..." . PHP_EOL;
	# '/var/www/.composer' is not writable for www-data.
	# Overriding '$COMPOSER_HOME'
	exec( "COMPOSER_HOME=/tmp/composer composer update \
		--no-dev \
		--working-dir '/srv/femiwiki.com/extensions/$extension'" );
}

// Install femiwiki skin
mkdir( '/srv/femiwiki.com/skins/Femiwiki', 0755, true );
exec( 'tar -xzf /tmp/Femiwiki.tar.gz --strip-components=1 --directory /srv/femiwiki.com/skins/Femiwiki');
unlink( '/tmp/Femiwiki.tar.gz' );

echo 'Installed all extensions and skins' . PHP_EOL;
