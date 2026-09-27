<?php
// Install the extensions and skins listed in extensions.json under /mediawiki.
//
// Usage: php install_extensions.php
//
// The WMF ones are fetched at their pinned commit from the GitHub mirror, or
// from Gerrit when the mirror lacks it, with their submodules, and given what
// extdist would have added: composer's vendor/, gitinfo.json and version. The
// rest are release tarballs. Each is one child process, a few at a time.

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

/** One WMF extension or skin at its pinned commit */
function wfInstallWmf( array $data, string $type, string $name ): void {
	$sha = $data["WMF-{$type}s"][$name];
	$dir = DESTINATION . "/{$type}s/$name";
	$sources = [
		"https://github.com/wikimedia/mediawiki-{$type}s-$name",
		"https://gerrit.wikimedia.org/r/mediawiki/{$type}s/$name",
	];
	wfMust( [ 'git', 'init', '-q', $dir ] );
	$source = null;
	foreach ( $sources as $url ) {
		if ( wfTries( [ 'git', '-C', $dir, 'fetch', '-q', '--depth', '1', $url, $sha ] ) ) {
			$source = $url;
			break;
		}
	}
	$source ?? throw new RuntimeException( "{$type}s/$name: $sha is on neither " . implode( ' nor ', $sources ) );
	echo "{$type}s/$name $sha from $source\n";

	// A relative submodule URL resolves against origin
	wfMust( [ 'git', '-C', $dir, 'remote', 'add', 'origin', $source ] );
	wfMust( [ 'git', '-C', $dir, '-c', 'advice.detachedHead=false', 'checkout', '-q', $sha ] );
	// Phabricator refuses to serve a commit by its hash, so its submodules come
	// from the GitHub repositories they mirror; other hosts may still refuse a
	// shallow fetch of a commit no ref points at
	$mirrors = [];
	foreach ( $data['submodule-mirrors'] ?? [] as $from => $to ) {
		array_push( $mirrors, '-c', "url.$to.insteadOf=$from" );
	}
	$submodules = [ 'git', ...$mirrors, '-C', $dir, 'submodule', 'update', '-q', '--init', '--recursive' ];
	wfTries( [ ...$submodules, '--depth', '1' ] ) || wfMust( $submodules );

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
		$name, $data['WMF-branch'], gmdate( 'Y-m-d\TH:i:s', (int)$time ), substr( $sha, 0, 7 )
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

/** One release tarball, its top directory stripped */
function wfInstallTarball( array $data, string $type, string $name ): void {
	$entry = $data['non-WMF'][$name];
	$dir = DESTINATION . "/{$type}s/$name";
	$url = str_replace( '$1', $entry['version'] ?? '', $entry['template'] );
	$file = sys_get_temp_dir() . "/$name.tar.gz";
	echo "{$type}s/$name from $url\n";
	wfMust( [ 'curl', '-fsSL', '--retry', '3', '-o', $file, $url ] );
	mkdir( $dir, 0755, true );
	wfMust( [ 'tar', '-xzf', $file, '--strip-components=1', '--directory', $dir ] );
	unlink( $file );
}

$data = json_decode( file_get_contents( __DIR__ . '/extensions.json' ), true, flags: JSON_THROW_ON_ERROR );

// A child process installs one item
if ( ( $argv[1] ?? '' ) === 'one' ) {
	[ , , $kind, $type, $name ] = $argv;
	if ( $kind === 'wmf' ) {
		wfInstallWmf( $data, $type, $name );
	} else {
		wfInstallTarball( $data, $type, $name );
	}
	exit( 0 );
}

// A source that lacks a repository must fail, not wait for a password
putenv( 'GIT_TERMINAL_PROMPT=0' );
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
		$process->start( static fn ( $type, $out ) => fwrite( $type === Process::ERR ? STDERR : STDOUT, $out ) );
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
