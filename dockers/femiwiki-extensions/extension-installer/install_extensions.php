<?php
// Install the extensions and skins listed in extensions.json under /mediawiki.
//
// Usage: php install_extensions.php
//
// The WMF ones are fetched at their pinned commit from the GitHub mirror, or
// from Gerrit when the mirror lacks it, with their submodules, and given what
// extdist would have added: composer's vendor/, gitinfo.json and version. The
// rest are release tarballs. Each is one child process, a few at a time.

const DESTINATION = '/mediawiki';

function fail( string $message ): never {
	fwrite( STDERR, "$message\n" );
	exit( 1 );
}

/** Run a command, returning whether it succeeded; its output goes to this process's */
function run( array $command ): bool {
	$process = proc_open( $command, [ STDIN, STDOUT, STDERR ], $pipes );
	return $process !== false && proc_close( $process ) === 0;
}

function must( array $command ): void {
	run( $command ) || fail( 'failed: ' . implode( ' ', $command ) );
}

function output( array $command ): string {
	$process = proc_open( $command, [ 1 => [ 'pipe', 'w' ], 2 => STDERR ], $pipes );
	$out = stream_get_contents( $pipes[1] );
	fclose( $pipes[1] );
	proc_close( $process ) === 0 || fail( 'failed: ' . implode( ' ', $command ) );
	return trim( $out );
}

$data = json_decode( file_get_contents( __DIR__ . '/extensions.json' ), true, flags: JSON_THROW_ON_ERROR );

/** One WMF extension or skin at its pinned commit */
function installWmf( array $data, string $type, string $name, string $sha ): void {
	$dir = DESTINATION . "/{$type}s/$name";
	$sources = [
		"https://github.com/wikimedia/mediawiki-{$type}s-$name",
		"https://gerrit.wikimedia.org/r/mediawiki/{$type}s/$name",
	];
	must( [ 'git', 'init', '-q', $dir ] );
	$source = null;
	foreach ( $sources as $url ) {
		if ( run( [ 'git', '-C', $dir, 'fetch', '-q', '--depth', '1', $url, $sha ] ) ) {
			$source = $url;
			break;
		}
	}
	$source ?? fail( "{$type}s/$name: $sha is on neither " . implode( ' nor ', $sources ) );
	echo "{$type}s/$name $sha from $source\n";

	// A relative submodule URL resolves against origin
	must( [ 'git', '-C', $dir, 'remote', 'add', 'origin', $source ] );
	must( [ 'git', '-C', $dir, '-c', 'advice.detachedHead=false', 'checkout', '-q', $sha ] );
	// Phabricator refuses to serve a commit by its hash, so its submodules come
	// from the GitHub repositories they mirror; other hosts may still refuse a
	// shallow fetch of a commit no ref points at
	$mirrors = [];
	foreach ( $data['submodule-mirrors'] ?? [] as $from => $to ) {
		array_push( $mirrors, '-c', "url.$to.insteadOf=$from" );
	}
	$submodules = [ 'git', ...$mirrors, '-C', $dir, 'submodule', 'update', '-q', '--init', '--recursive' ];
	run( [ ...$submodules, '--depth', '1' ] ) || must( $submodules );

	// The two files extdist adds, which Special:Version reads in place of .git
	$time = output( [ 'git', '-C', $dir, 'log', '-1', '--format=%ct', $sha ] );
	file_put_contents( "$dir/gitinfo.json", json_encode( [
		'head' => "$sha\n",
		'headSHA1' => "$sha\n",
		'headCommitDate' => $time,
		'branch' => "$sha\n",
		'remoteURL' => "https://gerrit.wikimedia.org/r/mediawiki/{$type}s/$name",
	], JSON_UNESCAPED_SLASHES ) );
	file_put_contents( "$dir/version", sprintf(
		"%s: %s\n%s\n\n%s\n", $name, $data['WMF-branch'], gmdate( 'Y-m-d\TH:i:s', (int)$time ), substr( $sha, 0, 7 )
	) );

	// extdist runs composer for any extension whose composer.json requires
	// something, a PHP extension alone included
	$composer = "$dir/composer.json";
	if ( is_file( $composer ) && ( json_decode( file_get_contents( $composer ), true )['require'] ?? [] ) ) {
		must( [ 'composer', 'install', '--no-dev', '--ignore-platform-reqs', '--no-interaction', '--no-progress',
			'--working-dir', $dir ] );
	}

	// Composer installs a package from source when it has no dist, .git included
	must( [ 'find', $dir, '-name', '.git', '-prune', '-exec', 'rm', '-rf', '{}', '+' ] );
}

/** One release tarball, its top directory stripped */
function installTarball( string $type, string $name, array $entry ): void {
	$dir = DESTINATION . "/{$type}s/$name";
	$url = str_replace( '$1', $entry['version'] ?? '', $entry['template'] );
	$file = sys_get_temp_dir() . "/$name.tar.gz";
	echo "{$type}s/$name from $url\n";
	must( [ 'curl', '-fsSL', '--retry', '3', '-o', $file, $url ] );
	mkdir( $dir, 0755, true );
	must( [ 'tar', '-xzf', $file, '--strip-components=1', '--directory', $dir ] );
	unlink( $file );
}

// A child process installs one item
if ( ( $argv[1] ?? '' ) === 'one' ) {
	[ , , $kind, $type, $name ] = $argv;
	if ( $kind === 'wmf' ) {
		installWmf( $data, $type, $name, $data["WMF-{$type}s"][$name] );
	} else {
		installTarball( $type, $name, $data['non-WMF'][$name] );
	}
	exit( 0 );
}

// A source that lacks a repository must fail, not wait for a password
putenv( 'GIT_TERMINAL_PROMPT=0' );
// Composer 2.9 and later refuses to resolve a package with a security
// advisory, dev requirements included although --no-dev never installs them,
// and MediaWiki's pinned codesniffer has one. extdist's composer predates this.
putenv( 'COMPOSER_NO_SECURITY_BLOCKING=1' );
// Mostly waiting on the network, so two per CPU unless told otherwise. On a
// 4-CPU runner the install took 37 s at 4, 22.5 s at 8 and 20.8 s at 16.
$jobs = (int)( getenv( 'INSTALL_JOBS' ) ?: 2 * (int)output( [ 'nproc' ] ) );

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
		$running[] = [ $item, proc_open( [ PHP_BINARY, __FILE__, 'one', ...$item ], [ STDIN, STDOUT, STDERR ], $pipes ) ];
	}
	foreach ( $running as $i => [ $item, $process ] ) {
		$status = proc_get_status( $process );
		if ( !$status['running'] ) {
			if ( $status['exitcode'] !== 0 ) {
				$failed[] = "$item[1]s/$item[2]";
			}
			proc_close( $process );
			unset( $running[$i] );
		}
	}
	usleep( 100000 );
}
$failed && fail( 'failed to install: ' . implode( ', ', $failed ) );
echo "Finished installing extensions\n";
