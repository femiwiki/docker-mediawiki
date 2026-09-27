<?php
// Install the extensions and skins listed in extensions.json under /mediawiki.
//
// Usage: php install_extensions.php
//
// The WMF ones are fetched at their pinned commit from the GitHub mirror, or
// from Gerrit when the mirror lacks it, with their submodules, and given what
// extdist would have added: composer's vendor/, gitinfo.json and version. Our
// own are fetched at their pinned commit and given their runtime npm packages.
// The rest are release tarballs. Each is one child process, a few at a time.

use Symfony\Component\Process\Process;

require __DIR__ . '/vendor/autoload.php';

const DESTINATION = '/mediawiki';

/** Whether a command succeeded */
function wfTries( array $command ): bool {
	return ( new Process( $command, timeout: null ) )->run() === 0;
}

/** A command's output; a failure throws with the command, its exit code and its output */
function wfMust( array $command ): string {
	return trim( ( new Process( $command, timeout: null ) )->mustRun()->getOutput() );
}

/**
 * Fetch a commit into $dir with its submodules; returns the source it came from
 *
 * @param string $dir
 * @param string[] $sources Repository URLs, tried in order
 * @param string $sha
 * @param array<string,string> $mirrors Submodule URLs to replace, by the URL to replace
 */
function wfFetch( string $dir, array $sources, string $sha, array $mirrors ): string {
	wfMust( [ 'git', 'init', '-q', $dir ] );
	$source = null;
	foreach ( $sources as $url ) {
		if ( wfTries( [ 'git', '-C', $dir, 'fetch', '-q', '--depth', '1', $url, $sha ] ) ) {
			$source = $url;
			break;
		}
	}
	if ( $source === null ) {
		throw new RuntimeException( "$dir: $sha is on neither " . implode( ' nor ', $sources ) );
	}

	// A relative submodule URL resolves against origin
	wfMust( [ 'git', '-C', $dir, 'remote', 'add', 'origin', $source ] );
	wfMust( [ 'git', '-C', $dir, '-c', 'advice.detachedHead=false', 'checkout', '-q', $sha ] );
	// Phabricator refuses to serve a commit by its hash, so its submodules come
	// from the GitHub repositories they mirror; other hosts may still refuse a
	// shallow fetch of a commit no ref points at
	$config = [];
	foreach ( $mirrors as $from => $to ) {
		array_push( $config, '-c', "url.$to.insteadOf=$from" );
	}
	$submodules = [ 'git', ...$config, '-C', $dir, 'submodule', 'update', '-q', '--init', '--recursive' ];
	wfTries( [ ...$submodules, '--depth', '1' ] ) || wfMust( $submodules );
	return $source;
}

/**
 * One WMF extension or skin at its pinned commit
 *
 * @param string $type 'extension' or 'skin'
 * @param string $name
 * @param string $sha
 * @param string $branch The WMF branch the commit is on
 * @param array<string,string> $mirrors Submodule URLs to replace, by the URL to replace
 */
function wfInstallWmf( string $type, string $name, string $sha, string $branch, array $mirrors ): void {
	$dir = DESTINATION . "/{$type}s/$name";
	$source = wfFetch( $dir, [
		"https://github.com/wikimedia/mediawiki-{$type}s-$name",
		"https://gerrit.wikimedia.org/r/mediawiki/{$type}s/$name",
	], $sha, $mirrors );
	echo "{$type}s/$name $sha from $source\n";

	// The two files extdist adds, which Special:Version reads in place of .git
	$time = wfMust( [ 'git', '-C', $dir, 'log', '-1', '--format=%ct', $sha ] );
	file_put_contents( "$dir/gitinfo.json", json_encode( [
		'head' => "$sha\n",
		'headSHA1' => "$sha\n",
		'headCommitDate' => $time,
		'branch' => "$sha\n",
		'remoteURL' => "https://gerrit.wikimedia.org/r/mediawiki/{$type}s/$name",
	], JSON_UNESCAPED_SLASHES ) );
	file_put_contents( "$dir/version", sprintf(
		"%s: %s\n%s\n\n%s\n",
		$name, $branch, gmdate( 'Y-m-d\TH:i:s', (int)$time ), substr( $sha, 0, 7 )
	) );

	// extdist runs composer for any extension whose composer.json requires
	// something, a PHP extension alone included
	$composer = "$dir/composer.json";
	if ( is_file( $composer ) && ( json_decode( file_get_contents( $composer ), true )['require'] ?? [] ) ) {
		wfMust( [ 'composer', 'install', '--no-dev', '--ignore-platform-reqs', '--no-interaction',
			'--no-progress', '--working-dir', $dir ] );
	}

	// Composer installs a package from source when it has no dist, .git included
	wfMust( [ 'find', $dir, '-name', '.git', '-prune', '-exec', 'rm', '-rf', '{}', '+' ] );
}

/**
 * One of our own extensions or skins at its pinned commit, with the npm
 * packages it loads at runtime
 *
 * @param string $type 'extension' or 'skin'
 * @param string $name
 * @param string $repository
 * @param string $sha
 */
function wfInstallInHouse( string $type, string $name, string $repository, string $sha ): void {
	$dir = DESTINATION . "/{$type}s/$name";
	wfFetch( $dir, [ $repository ], $sha, [] );
	echo "{$type}s/$name $sha from $repository\n";

	// Scripts are skipped: the only one is the husky hook for development
	$package = "$dir/package.json";
	if ( is_file( $package ) && ( json_decode( file_get_contents( $package ), true )['dependencies'] ?? [] ) ) {
		wfMust( [ 'npm', 'ci', '--omit=dev', '--ignore-scripts', '--no-audit', '--no-fund', '--prefix', $dir ] );
	}

	wfMust( [ 'find', $dir, '-name', '.git', '-prune', '-exec', 'rm', '-rf', '{}', '+' ] );
}

/**
 * One release tarball, its top directory stripped
 *
 * @param string $type 'extension' or 'skin'
 * @param string $name
 * @param string $url
 */
function wfInstallTarball( string $type, string $name, string $url ): void {
	$dir = DESTINATION . "/{$type}s/$name";
	$file = sys_get_temp_dir() . "/$name.tar.gz";
	echo "{$type}s/$name from $url\n";
	wfMust( [ 'curl', '-fsSL', '--retry', '3', '-o', $file, $url ] );
	mkdir( $dir, 0755, true );
	wfMust( [ 'tar', '-xzf', $file, '--strip-components=1', '--directory', $dir ] );
	unlink( $file );
}

/**
 * extensions.json: the WMF branch, commit hashes by name, our own repositories
 * and commits, tarball URL templates, and submodule URL replacements
 *
 * @return array
 * @phan-return array{
 *   WMF-branch: string,
 *   WMF-extensions: array<string, string>,
 *   WMF-skins: array<string, string>,
 *   in-house: array<string, array{repository: string, commit: string, type?: string}>,
 *   non-WMF: array<string, array{template: string, version?: string, type?: string}>,
 *   submodule-mirrors?: array<string, string>,
 * }
 */
function wfReadExtensions(): array {
	return json_decode( file_get_contents( __DIR__ . '/extensions.json' ), true, flags: JSON_THROW_ON_ERROR );
}

$data = wfReadExtensions();

// A child process installs one item
if ( ( $argv[1] ?? '' ) === 'one' ) {
	[ , , $kind, $type, $name ] = $argv;
	if ( $kind === 'wmf' ) {
		wfInstallWmf( $type, $name, $data["WMF-{$type}s"][$name], $data['WMF-branch'],
			$data['submodule-mirrors'] ?? [] );
	} elseif ( $kind === 'in-house' ) {
		$entry = $data['in-house'][$name];
		wfInstallInHouse( $type, $name, $entry['repository'], $entry['commit'] );
	} else {
		$entry = $data['non-WMF'][$name];
		wfInstallTarball( $type, $name, str_replace( '$1', $entry['version'] ?? '', $entry['template'] ) );
	}
	exit( 0 );
}

// A source that lacks a repository must fail, not wait for a password
putenv( 'GIT_TERMINAL_PROMPT=0' );
// npm records a GitHub dependency as git+ssh, and the build has no SSH key
putenv( 'GIT_CONFIG_COUNT=1' );
putenv( 'GIT_CONFIG_KEY_0=url.https://github.com/.insteadOf' );
putenv( 'GIT_CONFIG_VALUE_0=ssh://git@github.com/' );
// Composer 2.9 and later refuses to resolve a package with a security
// advisory, dev requirements included although --no-dev never installs them,
// and MediaWiki's pinned codesniffer has one. extdist's composer predates this.
putenv( 'COMPOSER_NO_SECURITY_BLOCKING=1' );
// Mostly waiting on the network, so twice this machine's CPU count, read on
// every run, unless INSTALL_JOBS says otherwise. On a 4-CPU runner the install
// took 37 s at 4, 22.5 s at 8 and 20.8 s at 16.
$jobs = (int)( getenv( 'INSTALL_JOBS' ) ?: 2 * (int)wfMust( [ 'nproc' ] ) );

$items = [];
foreach ( [ 'extension', 'skin' ] as $type ) {
	foreach ( array_keys( $data["WMF-{$type}s"] ) as $name ) {
		$items[] = [ 'wmf', $type, $name ];
	}
}
foreach ( $data['in-house'] as $name => $entry ) {
	$items[] = [ 'in-house', $entry['type'] ?? 'extension', $name ];
}
foreach ( $data['non-WMF'] as $name => $entry ) {
	$items[] = [ 'tarball', $entry['type'] ?? 'extension', $name ];
}

echo 'Installing ' . count( $items ) . " extensions and skins, $jobs at a time\n";
$running = [];
$failed = [];
while ( $items || $running ) {
	while ( $items && count( $running ) < $jobs ) {
		$item = array_shift( $items );
		$process = new Process( [ PHP_BINARY, __FILE__, 'one', ...$item ], timeout: null );
		$process->start( static function ( string $type, string $out ): void {
			fwrite( $type === Process::ERR ? STDERR : STDOUT, $out );
		} );
		$running[] = [ $item, $process ];
	}
	foreach ( $running as $i => [ $item, $process ] ) {
		if ( !$process->isRunning() ) {
			$process->isSuccessful() || $failed[] = "$item[1]s/$item[2]";
			unset( $running[$i] );
		}
	}
	usleep( 100000 );
}
$failed && throw new RuntimeException( 'failed to install: ' . implode( ', ', $failed ) );
echo "Finished installing extensions\n";
