<?php
/**
 * Audit (#18, #19) fixes for markup a release can't reach: stored page/post content. Dry run unless passed `apply`.
 *
 * @package IK2
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$ik2_apply = in_array( 'apply', $args ?? [], true );

/**
 * Hide decorative // prefixes, "more" arrows and the terminal path from screen readers.
 *
 * @param string $content Post content.
 * @return array{0: string, 1: array<string,int>} New content and per-rule counts.
 */
function ik2_a11y_fix_decorative( string $content ): array {
	$hide   = '<span aria-hidden="true">//</span> ';
	$rules  = [
		'eyebrow //'  => [ '#(<p class="ik-section__eyebrow[^"]*">\s*)// #', '$1' . $hide ],
		'caption //'  => [ '#(<figcaption class="wp-element-caption">)// #', '$1' . $hide ],
		'span //'     => [ '#(<span>)// #', '$1' . $hide ],
		'note //'     => [ '#(<p>)// #', '$1' . $hide ],
		'more arrow'  => [ '# →</a>#u', ' <span aria-hidden="true">→</span></a>' ],
		'prompt path' => [ '#<span class="ik-hero__portrait-path">#', '<span class="ik-hero__portrait-path" aria-hidden="true">' ],
	];
	$counts = [];
	foreach ( $rules as $name => [ $pattern, $replacement ] ) {
		$content = (string) preg_replace( $pattern, $replacement, $content, -1, $count );
		if ( $count ) {
			$counts[ $name ] = $count;
		}
	}
	return [ $content, $counts ];
}

/**
 * Promote top-level H3 sections to H2 so the outline doesn't skip a level.
 *
 * @param string $content Post content.
 * @return array{0: string, 1: array<string,int>}
 */
function ik2_a11y_fix_headings( string $content ): array {
	$content = (string) preg_replace(
		'#<!-- wp:heading \{"level":3\} -->(\s*)<h3([^>]*)>(.*?)</h3>#s',
		'<!-- wp:heading -->$1<h2$2>$3</h2>',
		$content,
		-1,
		$count
	);
	return [ $content, $count ? [ 'H3 to H2' => $count ] : [] ];
}

/**
 * Add a named direct link to the recording after the first YouTube embed.
 *
 * @param string $content Post content.
 * @return array{0: string, 1: array<string,int>}
 */
function ik2_a11y_fix_recording_link( string $content ): array {
	if ( str_contains( $content, 'Watch the recording on YouTube' )
		|| ! preg_match( '#"url":"(https://www\.youtube\.com/watch\?v=[^"]+)"#', $content, $match ) ) {
		return [ $content, [] ];
	}
	$link    = "\n\n<!-- wp:paragraph -->\n<p><a href=\"" . esc_url( $match[1] ) . "\">Watch the recording on YouTube</a></p>\n<!-- /wp:paragraph -->";
	$content = (string) preg_replace( '#<!-- /wp:embed -->#', '$0' . $link, $content, 1, $count );
	return [ $content, $count ? [ 'recording link' => $count ] : [] ];
}

$ik2_jobs = [
	[ 'page', (int) get_option( 'page_on_front' ), 'ik2_a11y_fix_decorative' ],
	[ 'page', 'about', 'ik2_a11y_fix_decorative' ],
	[ 'page', 'contact', 'ik2_a11y_fix_decorative' ],
	[ 'page', 'speaking', 'ik2_a11y_fix_decorative' ],
	[ 'post', 'cloudflare-api-cli-tool', 'ik2_a11y_fix_headings' ],
	[ 'post', 'secure-your-wordpress-site', 'ik2_a11y_fix_headings' ],
	[ 'post', 'mcp-talk-kelas-tanya-domainesia', 'ik2_a11y_fix_recording_link' ],
];

foreach ( $ik2_jobs as [ $ik2_type, $ik2_ref, $ik2_fix ] ) {
	$ik2_post = is_int( $ik2_ref ) ? get_post( $ik2_ref ) : get_page_by_path( $ik2_ref, OBJECT, $ik2_type );
	if ( ! $ik2_post instanceof WP_Post ) {
		WP_CLI::warning( sprintf( 'No %s found for %s, skipped.', $ik2_type, (string) $ik2_ref ) );
		continue;
	}

	[ $ik2_content, $ik2_counts ] = $ik2_fix( $ik2_post->post_content );
	$ik2_label                    = sprintf( '/%s/ (ID %d)', $ik2_post->post_name, $ik2_post->ID );

	if ( $ik2_content === $ik2_post->post_content ) {
		WP_CLI::log( "{$ik2_label}: nothing to change" );
		continue;
	}

	$ik2_parts = [];
	foreach ( $ik2_counts as $ik2_rule => $ik2_count ) {
		$ik2_parts[] = "{$ik2_rule} x{$ik2_count}";
	}
	$ik2_summary = implode( ', ', $ik2_parts );

	if ( ! $ik2_apply ) {
		WP_CLI::log( "{$ik2_label}: would change {$ik2_summary}" );
		continue;
	}

	$ik2_result = wp_update_post(
		[
			'ID'           => $ik2_post->ID,
			'post_content' => wp_slash( $ik2_content ),
		],
		true
	);
	if ( is_wp_error( $ik2_result ) ) {
		WP_CLI::error( "{$ik2_label}: " . $ik2_result->get_error_message() );
	}
	WP_CLI::success( "{$ik2_label}: changed {$ik2_summary}" );
}
