<?php
/**
 * Generic coverage gate, bundled with wp-env-macos.
 *
 * Parses clover.xml and reports only the uncovered statement lines. Exits 0 at
 * 100% line coverage, 1 on any gap. Plugin-specific (environment-only) line
 * exclusions may be supplied via --exclude <json>:
 *   { "multisite": { "src/X.php": [315, 339] } }
 * When --multisite is set, only the "multisite" key applies.
 *
 * @package wp-env-macos
 */

$env      = in_array( '--multisite', $argv, true ) ? 'multisite' : 'singlesite';
$exclude  = null;
$clover   = null;

// Parse args: --multisite, --exclude <file>, and a positional clover path.
$args = array_slice( $argv, 1 );
foreach ( $args as $i => $arg ) {
	if ( '--multisite' === $arg ) {
		continue;
	}
	if ( '--exclude' === $arg && isset( $args[ $i + 1 ] ) ) {
		$exclude = $args[ $i + 1 ];
		continue;
	}
	if ( ! str_starts_with( $arg, '--' ) && null === $clover ) {
		$clover = $arg;
	}
}

$clover = $clover ?? 'coverage/clover.xml';

if ( ! is_file( $clover ) ) {
	fwrite( STDERR, "coverage-check: clover.xml not found at '$clover'\n" );
	exit( 2 );
}

$excluded_lines = [];
if ( null !== $exclude && is_file( $exclude ) ) {
	$decoded = json_decode( (string) file_get_contents( $exclude ), true );
	if ( is_array( $decoded ) && isset( $decoded[ $env ] ) && is_array( $decoded[ $env ] ) ) {
		$excluded_lines = $decoded[ $env ];
	}
}

$xml  = simplexml_load_file( $clover );
// clover may carry container paths (/var/www/html/wp-content/plugins/<slug>/...)
// or host paths. Normalize to repo-relative by keeping from /src/ onward, which
// matches the exclusion keys (all covered files live under src/Git_Updater/).
$xpath   = $xml->xpath( '//file' );
$files   = $xpath ? $xpath : [];
$gaps    = [];
$checked = 0;

foreach ( $files as $file ) {
	$path = (string) $file['name'];
	if ( $pos = strpos( $path, '/src/' ) ) {
		$path = substr( $path, $pos + 1 );
	}
	++$checked;

	$lines = [];
	foreach ( $file->line as $line ) {
		if ( (string) $line['type'] === 'stmt' && (int) $line['count'] === 0 ) {
			$num = (int) $line['num'];
			if ( ! ( isset( $excluded_lines[ $path ] ) && in_array( $num, $excluded_lines[ $path ], true ) ) ) {
				$lines[] = $num;
			}
		}
	}

	if ( $lines ) {
		sort( $lines );
		$gaps[ $path ] = $lines;
	}
}

if ( ! $gaps ) {
	echo '100% line coverage — ' . $checked . " files checked\n";
	exit( 0 );
}

foreach ( $gaps as $path => $lines ) {
	echo $path . ': ' . implode( ', ', $lines ) . "\n";
}
$total = array_sum( array_map( 'count', $gaps ) );
fwrite( STDERR, 'COVERAGE GAP: ' . $total . ' uncovered statement line(s) across ' . count( $gaps ) . " file(s)\n" );
exit( 1 );
