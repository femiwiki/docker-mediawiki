<?php
//
// Install extensions and skins.
//

// Extensions have each submodule should be cloned by git command.
const EXTENSIONS_FROM_GERRIT_WITH_SUBMODULE = [
	'VisualEditor',
	'Widgets',
];

// Extensions have no submodule (at least in REL1_31)
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

// Extensions has no submodules but are all on github/Femiwiki.
const EXTENSIONS_MADE_BY_FEMIWIKI = [
	'Sanctions',
	'CategoryIntersectionSearch',
	'FacetedCategory',
	'UnifiedExtensionForFemiwiki',
];

// Map from extensions should be treated specially to their URL.
// these extensions have complex download URL
const ETC_EXTENSIONS_URL_MAP = [
	'AWS' => 'https://github.com/edwardspec/mediawiki-aws-s3/archive/v0.10.0.tar.gz',
	'EmbedVideo' => 'https://github.com/HydraWiki/mediawiki-embedvideo/archive/v2.7.4.tar.gz',
	'SimpleMathJax' => 'https://github.com/jmnote/SimpleMathJax/archive/v0.7.3.tar.gz',
];

//
// Download, uncompress and move extensions.
//
// @param $mediawikiBranch string Branch name of the MediaWiki we target for. Example: "REL1_31"
//
function install( $mediawikiBranch ) {
	// Temporary directory path for downloading.
	$tempDirectoryPath = '/tmp';

	// Temporary directory path for Composer.
	$composerHomePath = '/tmp/composer';

	// Names of the all extensions.
	$allExtensions = array_merge(
		EXTENSIONS_FROM_GERRIT_WITH_SUBMODULE,
		EXTENSIONS_FROM_GERRIT_WITHOUT_SUBMODULE,
		EXTENSIONS_MADE_BY_FEMIWIKI,
		array_flip( ETC_EXTENSIONS_URL_MAP )
	);

	$githubExtensions = array_merge(
		EXTENSIONS_MADE_BY_FEMIWIKI,
		array_flip( ETC_EXTENSIONS_URL_MAP )
	);

	// Make directories for each extensions.
	foreach ( $allExtensions as $extension ) {
		mkdir( "/srv/femiwiki.com/extensions/$extension", 0700, true );
	}

	// Download extensions and skins which can be downloaded by aria2.
	// Build string that can be used by aria2c with the '--input-file=-' option.
	// For more infomation for 'Input-file' format, See
	// https://aria2.github.io/manual/en/html/aria2c.html#id2
	// Please remember this function does not make a real file, So you must pass
	// the result throuth the standard input.
	$inputFile = '';
	foreach ( EXTENSIONS_FROM_GERRIT_WITHOUT_SUBMODULE as $extension ) {
		$inputFile .= "https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/$extension/+archive/$mediawikiBranch.tar.gz\n out=$extension.tar.gz\n";
	}
	foreach ( EXTENSIONS_MADE_BY_FEMIWIKI as $extension ) {
		$inputFile .= "https://github.com/femiwiki/$extension/archive/master.tar.gz\n out=$extension.tar.gz\n";
	}
	foreach ( ETC_EXTENSIONS_URL_MAP as $extension => $url ) {
		$inputFile .= "$url\n out=$extension.tar.gz\n";
	}
	$inputFile .= "https://github.com/femiwiki/skin/archive/master.tar.gz\n out=Femiwiki.tar.gz\n";

	// Execute aria2
	$aria2 = popen( "aria2c --input-file=- --dir=$tempDirectoryPath", 'w' );
	fputs( $aria2, $inputFile );
	pclose( $aria2 );

	// Uncompress tar.gz files not from github.
	foreach ( EXTENSIONS_FROM_GERRIT_WITHOUT_SUBMODULE as $extension )  {
		exec( "tar -xzf '$tempDirectoryPath/$extension.tar.gz' --directory '/srv/femiwiki.com/extensions/$extension'" );
		unlink( "$tempDirectoryPath/$extension.tar.gz" );
	}

	// Uncompress tar.gz files from github. It have to be striped component.
	foreach ( $githubExtensions as $extension ) {
		exec( "tar -xzf '$tempDirectoryPath/$extension.tar.gz' --strip-components=1 --directory '/srv/femiwiki.com/extensions/$extension'" );
		unlink( "$tempDirectoryPath/$extension.tar.gz" );
	}

	// Clone other extensions.
	foreach ( EXTENSIONS_FROM_GERRIT_WITH_SUBMODULE as $extension ) {
		exec( "git clone -b '$mediawikiBranch' --depth 1 --recurse-submodules 'https://gerrit.wikimedia.org/r/p/mediawiki/extensions/$extension' '/srv/femiwiki.com/extensions/$extension'" );
	}

	// Install composer dependencies via 'composer update'
	foreach ( $allExtensions as $extension ) {
		if ( !file_exists( "/srv/femiwiki.com/extensions/$extension/composer.json" ) ) {
			continue;
		}

		// '/var/www/.composer' is not writable for www-data.
		// Overriding '$COMPOSER_HOME'
		exec( "COMPOSER_HOME=$composerHomePath composer update --no-dev --working-dir '/srv/femiwiki.com/extensions/$extension'" );
	}

	// Install Skin.
	// Skins should be located in `skins/` directory
	mkdir( '/srv/femiwiki.com/skins/Femiwiki', 0700, true );
	exec( "tar -xzf $tempDirectoryPath/Femiwiki.tar.gz --strip-components=1 --directory /srv/femiwiki.com/skins/Femiwiki" );
	unlink( "/tmp/Femiwiki.tar.gz" );
}

install( $argv[1] );
