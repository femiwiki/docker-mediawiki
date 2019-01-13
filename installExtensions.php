<?php
/**
 * Install extensions and skins.
 *
 * @file
 */

/**
 * Installer for a predefined list for extensions and skins.
 */
class Installer {
	/** Extensions have each submodule should be cloned by git command. */
	const EXTENSIONS_FROM_GERRIT_WITH_SUBMODULE = [
		'VisualEditor',
		'Widgets',
	];

	/**
	 * Extensions have no submodule (at least in REL1_31)
	 * So we can download their by tar.gz file simply.
	 */
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

	/** Extensions has no submodules but are all on github/Femiwiki. */
	const EXTENSIONS_MADE_BY_FEMIWIKI = [
		'Sanctions',
		'CategoryIntersectionSearch',
		'FacetedCategory',
		'UnifiedExtensionForFemiwiki',
	];

	/**
	 * Map from extensions should be treated specially to their URL.
	 * these extensions have complex download URL
	 */
	const ETC_EXTENSIONS_URL_MAP = [
		'AWS' => 'https://github.com/edwardspec/mediawiki-aws-s3/archive/v0.10.0.tar.gz',
		'EmbedVideo' => 'https://github.com/HydraWiki/mediawiki-embedvideo/archive/v2.7.4.tar.gz',
		'SimpleMathJax' => 'https://github.com/jmnote/SimpleMathJax/archive/v0.7.3.tar.gz',
	];

	/**
	 * @var string Branch name of the MediaWiki we target for.
	 * Example: "REL1_31"
	 */
	private $mediawikiBranch = 'REL1_31';

	/** @var string Temporary directory path for downloading. */
	private $tempDirectoryPath = '/tmp';

	/** @var string Temporary directory path for Composer. */
	private $composerHomePath = '/tmp/composer';

	/** @var array Names of etc extensions extracted from ETC_EXTENSIONS_URL_MAP */
	private $etcExtensions = [];

	/** @var array Names of the all extensions. */
	private $allExtensions = [];

	function __construct( array $options = [] ) {
		if ( !$this->checkDependencies() ) {
			exit( 'Dependency failed' );
		}

		// Initialize member variables. @todo Maybe there is a better way...
		if ( isset( $options[ 'mediawiki_branch' ] ) )
			$this->mediawikiBranch = $options['mediawiki_branch'];
		if ( isset( $options[ 'temp-directory-path' ] ) )
			$this->tempDirectoryPath = $options['temp-directory-path'];
		if ( isset( $options[ 'composer-home-path' ] ) )
			$this->composerHomePath = $options['composer-home-path'];

		// @todo Check the given branch name is correct.

		$this->etcExtensions = array_flip( self::ETC_EXTENSIONS_URL_MAP );

		$this->allExtensions = array_merge(
			self::EXTENSIONS_FROM_GERRIT_WITH_SUBMODULE,
			self::EXTENSIONS_FROM_GERRIT_WITHOUT_SUBMODULE,
			self::EXTENSIONS_MADE_BY_FEMIWIKI,
			$this->etcExtensions
		);
	}

	/**
	 * Download, uncompress and move extensions.
	 * @return bool
	 */
	public function install() {
		echo 'Installing extensions and skins...' . PHP_EOL;

		// Make directories for each extensions.
		foreach ( $this->allExtensions as $extension ) {
			mkdir( "/srv/femiwiki.com/extensions/$extension", 0700, true );
		}

		// Download extensions and skins which can be downloaded by aria2.
		$this->downloadExtensionsAndSkinsUsingAria2();
		$this->uncompressExtensions();

		// Clone other extensions.
		$this->cloneExtensions();

		// Install composer dependencies via 'composer update'
		$this->installCoposerDependencies();

		// Install Skin.
		$this->installFemiwikiSkin();

		echo 'Installed all extensions and skins' . PHP_EOL;

		return true;
	}

	/**
	 * Check dependencies, tar, git, aria2, etc ...
	 * @return bool whether all dependancies are available.
	 */
	private function checkDependencies() {
		// @todo

		return true;
	}

	/**
	 * Download extensions and skins.
	 * @return bool
	 */
	private function downloadExtensionsAndSkinsUsingAria2() {
		$inputFile = $this->makeAria2InputFile();
		$this->execAria2WithInputFile( $inputFile );

		return true;
	}

	/**
	 * Return string that can be used by aria2c with the '--input-file=-' option.
	 * For more infomation for 'Input-file' format, See
	 * https://aria2.github.io/manual/en/html/aria2c.html#id2
	 * Please remember this function does not make a real file, So you must pass
	 * the result throuth the standard input.
	 * @return string string can be used for 'input-file'
	 */
	private function makeAria2InputFile() {
		$mediawikiBranch = $this->mediawikiBranch;
		$tempDirectory = $this->tempDirectoryPath;
		$inputFile = '';

		foreach ( self::EXTENSIONS_FROM_GERRIT_WITHOUT_SUBMODULE as $extension ) {
			$inputFile .= implode( [
				"https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/$extension/+archive/$mediawikiBranch.tar.gz",
				PHP_EOL,
				" out=$extension.tar.gz",
				PHP_EOL,
			] );
		}

		foreach ( self::EXTENSIONS_MADE_BY_FEMIWIKI as $extension ) {
			$lcfirst = lcfirst( $extension );
			$inputFile .= implode( [
				"https://github.com/femiwiki/$lcfirst/archive/master.tar.gz",
				PHP_EOL,
				" out=$extension.tar.gz",
				PHP_EOL,
			] );
		}

		// Extensions with complex download URL
		foreach ( self::ETC_EXTENSIONS_URL_MAP as $extension => $url ) {
			$inputFile .= $url . PHP_EOL . " out=$extension.tar.gz" . PHP_EOL;
		}

		// Femiwiki skin.
		$inputFile .= implode( [
			'https://github.com/femiwiki/skin/archive/master.tar.gz',
			PHP_EOL,
			' out=Femiwiki.tar.gz',
			PHP_EOL
		] );

		return $inputFile;
	}

	/**
	 * @param string string can be used for aria2c's '--input-file=-' option
	 * @return bool
	 */
	private function execAria2WithInputFile( $inputFile ) {
		$tempDirectory = $this->tempDirectoryPath;

		$aria2 = popen( "aria2c --input-file=- --dir=$tempDirectory", 'w' );

		fputs( $aria2, $inputFile );
		pclose( $aria2 );

		return true;
	}

	/**
	 * Install composer dependencies via 'composer update'
	 * @return bool
	 */
	private function installCoposerDependencies() {
		$composerHomePath = $this->composerHomePath;

		foreach ( $this->allExtensions as $extension ) {
			if ( !file_exists( "/srv/femiwiki.com/extensions/$extension/composer.json" ) )
				continue;

			echo "Composing $extension..." . PHP_EOL;

			$directory = "/srv/femiwiki.com/extensions/$extension";
			$options = "--no-dev --working-dir '$directory'";

			// '/var/www/.composer' is not writable for www-data.
			// Overriding '$COMPOSER_HOME'
			exec( "COMPOSER_HOME=$composerHomePath composer update $options" );
		}

		return true;
	}

	/**
	 * Uncompress tar.gz files.
	 * @return bool
	 */
	private function uncompressExtensions() {
		$tempDirectoryPath = $this->tempDirectoryPath;

		// Uncompress tar.gz files not from github.
		foreach ( self::EXTENSIONS_FROM_GERRIT_WITHOUT_SUBMODULE as $extension )  {
			$options = "--directory '/srv/femiwiki.com/extensions/$extension'";
			exec( "tar -xzf '$tempDirectoryPath/$extension.tar.gz' $options" );
			unlink( "$tempDirectoryPath/$extension.tar.gz" );
		}

		// Uncompress tar.gz files from github. It have to be striped component.
		$githubExtensions = array_merge(
			self::EXTENSIONS_MADE_BY_FEMIWIKI,
			$this->etcExtensions
		);
		foreach ( $githubExtensions as $extension ) {
			$directory = "/srv/femiwiki.com/extensions/$extension";
			$options = "--strip-components=1 --directory '$directory'";
			exec( "tar -xzf '$tempDirectoryPath/$extension.tar.gz' $options" );
			unlink( "$tempDirectoryPath/$extension.tar.gz" );
		}

		return true;
	}

	/**
	 * Clone extensions existing in gerrit and having submodule
	 * @return bool
	 */
	private function cloneExtensions() {
		$mediawikiBranch = $this->mediawikiBranch;

		foreach ( self::EXTENSIONS_FROM_GERRIT_WITH_SUBMODULE as $extension ) {
			$url = "https://gerrit.wikimedia.org/r/p/mediawiki/extensions/$extension";
			$directory = "/srv/femiwiki.com/extensions/$extension";
			$options = implode( ' ', [
				"-b '$mediawikiBranch'",
				'--depth 1',
				'--recurse-submodules'
			] );
			exec( "git clone $options '$url' '$directory'" );
		}
	}

	/**
	 * Install femiwiki skin
	 * @return bool
	 */
	private function installFemiwikiSkin() {
		$tempDirectoryPath = $this->tempDirectoryPath;

		// Skins should be located in `skins/` directory
		mkdir( '/srv/femiwiki.com/skins/Femiwiki', 0700, true );
		$options = "--strip-components=1 --directory /srv/femiwiki.com/skins/Femiwiki";
		exec( "tar -xzf $tempDirectoryPath/Femiwiki.tar.gz $options" );
		unlink( "/tmp/Femiwiki.tar.gz" );

		return true;
	}
}

$installer = new Installer( [ 'mediawiki_branch' => $argv[1] ] );
$installer->install();
