<?php
/**
 * Writes each profiled request to a file of its own. Core has none for
 * ProfilerExcimer: ProfilerOutputDump takes ProfilerXhprof only, and
 * ProfilerOutputText prints into the response body.
 *
 * @file
 */

class FemiwikiProfilerOutputFile extends ProfilerOutput {
	/** @inheritDoc */
	public function canUse() {
		return (bool)( $this->params['outputDir'] ?? '' );
	}

	/** @inheritDoc */
	public function log( array $stats ) {
		$elapsed = microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'];
		// By CPU rather than wall time: the surplus credit charge is CPU alone
		usort( $stats, static function ( $a, $b ) {
			return $b['cpu'] <=> $a['cpu'];
		} );

		$out = sprintf( "%s %s\nelapsed %.3fs\n\n%-70s %10s %8s %10s %8s\n",
			$_SERVER['REQUEST_METHOD'] ?? '-',
			$_SERVER['REQUEST_URI'] ?? '-',
			$elapsed,
			'name', 'cpu ms', 'cpu %', 'real ms', 'real %'
		);
		foreach ( $stats as $entry ) {
			$out .= sprintf( "%-70s %10.1f %7.1f%% %10.1f %7.1f%%\n",
				$entry['name'], $entry['cpu'], $entry['%cpu'],
				$entry['real'], $entry['%real']
			);
		}

		// Elapsed milliseconds first so that ls puts the worst request last
		file_put_contents(
			sprintf( '%s/%06.0f-%s.txt', $this->params['outputDir'], $elapsed * 1000, uniqid() ),
			$out
		);
	}
}
