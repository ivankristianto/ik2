<?php
/**
 * Regenerate the post_content of the pattern-backed pages from their
 * full-page patterns. Run whenever the page patterns change (and once per
 * environment when deploying the post-content template model):
 *
 * Usage: wp eval-file wp-content/themes/ik2/bin/regen-page-content.php
 *
 * Pages are resolved by path so the script works across environments with
 * different page IDs. Overwrites in-editor content edits on those pages —
 * the patterns are the source of truth.
 *
 * @package IK2
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

/**
 * Recursively strip metadata.patternName from resolved blocks — any block
 * carrying it becomes a content-only locked pattern instance in the editor.
 *
 * @param array $blocks Parsed block list.
 * @return array
 */
function ik2_regen_strip_pattern_metadata( array $blocks ): array {
	foreach ( $blocks as &$block ) {
		if ( isset( $block['attrs']['metadata']['patternName'] ) ) {
			unset( $block['attrs']['metadata']['patternName'] );
			if ( empty( $block['attrs']['metadata'] ) ) {
				unset( $block['attrs']['metadata'] );
			}
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$block['innerBlocks'] = ik2_regen_strip_pattern_metadata( $block['innerBlocks'] );
		}
	}
	return $blocks;
}

$ik2_pages = [
	'home'     => 'ik2/home-page',
	'about'    => 'ik2/about-page',
	'contact'  => 'ik2/contact-page',
	'speaking' => 'ik2/speaking-page',
	'resume'   => 'ik2/resume-page',
];

$ik2_registry = WP_Block_Patterns_Registry::get_instance();

foreach ( $ik2_pages as $ik2_path => $ik2_slug ) {
	$ik2_page = get_page_by_path( $ik2_path );
	if ( ! $ik2_page instanceof WP_Post ) {
		WP_CLI::warning( sprintf( 'No page found at /%s/ — skipped.', $ik2_path ) );
		continue;
	}

	if ( ! $ik2_registry->is_registered( $ik2_slug ) ) {
		WP_CLI::error( sprintf( 'Pattern %s is not registered.', $ik2_slug ) );
	}

	$ik2_pattern = $ik2_registry->get_registered( $ik2_slug );
	$ik2_blocks  = parse_blocks( $ik2_pattern['content'] );
	$ik2_blocks  = resolve_pattern_blocks( $ik2_blocks );
	$ik2_blocks  = ik2_regen_strip_pattern_metadata( $ik2_blocks );
	$ik2_content = serialize_blocks( $ik2_blocks );

	if ( str_contains( $ik2_content, 'var(u002d' ) ) {
		WP_CLI::error( sprintf( '%s: serialized content contains corrupted CSS vars (var(u002d…)).', $ik2_slug ) );
	}
	if ( str_contains( $ik2_content, 'patternName' ) ) {
		WP_CLI::error( sprintf( '%s: serialized content still contains patternName.', $ik2_slug ) );
	}
	if ( str_contains( $ik2_content, 'wp:pattern' ) ) {
		WP_CLI::error( sprintf( '%s: serialized content still contains unresolved wp:pattern refs.', $ik2_slug ) );
	}

	$ik2_result = wp_update_post(
		[
			'ID'           => $ik2_page->ID,
			'post_content' => wp_slash( $ik2_content ),
		],
		true
	);

	if ( is_wp_error( $ik2_result ) ) {
		WP_CLI::error( sprintf( '%s: %s', $ik2_slug, $ik2_result->get_error_message() ) );
	}

	WP_CLI::success( sprintf( 'Page /%s/ (ID %d) updated from %s: %d bytes.', $ik2_path, $ik2_page->ID, $ik2_slug, strlen( $ik2_content ) ) );
}
