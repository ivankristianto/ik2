<?php
/**
 * Helpers for the ik2/articles-filters block render template.
 *
 * Loaded once via require_once from render.php — keeps these out of per-render
 * closure scope so they show up in stack traces and are reusable from tests.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\Blocks\ArticlesFilters;

defined( 'ABSPATH' ) || exit;

const ALLOWED_FORMATS = [ 'guide', 'note', 'experiment' ];

/**
 * Detect the current archive context from the request stash.
 *
 * Reads the archive context stashed by inc/Blocks.php on parse_request.
 * We can't rely on get_queried_object() / get_query_var('category_name')
 * because pre_get_posts appends a tax_query item for the format filter,
 * which causes WP to rewrite category_name to the format slug.
 *
 * @return array{kind:string,topic:?string,tag:?string,format:string}
 */
function detect_context(): array {
	$ctx = [
		'kind'   => 'page',
		'topic'  => null,
		'tag'    => null,
		'format' => '',
	];

	$stash = \IK2\Theme\Blocks\ik2_get_archive_context();

	if ( $stash['category'] !== '' ) {
		$ctx['kind']  = 'category';
		$ctx['topic'] = $stash['category'];
	} elseif ( $stash['tag'] !== '' ) {
		$ctx['kind'] = 'tag';
		$ctx['tag']  = $stash['tag'];
	}

	if ( $stash['format'] !== '' && in_array( $stash['format'], ALLOWED_FORMATS, true ) ) {
		$ctx['format'] = $stash['format'];
	}

	return $ctx;
}

/**
 * Build the pretty URL for a given (topic, format) pair from the current context.
 *
 * Rules:
 *  - topic === 'all'  → main archive (or stay on tag if context is tag and tag pill clicked).
 *  - topic === slug   → /category/{slug}/ — overrides tag context.
 *  - format suffix appended when not 'all'.
 *  - From a tag context, the "all" topic preserves the tag (no topic on tag = "all"-equivalent).
 *
 * @param array{kind:string,topic:?string,tag:?string,format:string} $context Current archive context.
 * @param string                                                     $topic   Topic slug or 'all'.
 * @param string                                                     $format  Format slug or 'all'.
 */
function build_url( array $context, string $topic, string $format ): string {
	if ( $topic !== 'all' ) {
		$base = home_url( '/category/' . rawurlencode( $topic ) . '/' );
	} elseif ( $context['kind'] === 'tag' && $context['tag'] !== null ) {
		$base = home_url( '/tag/' . rawurlencode( $context['tag'] ) . '/' );
	} else {
		$base = home_url( '/articles/' );
	}

	if ( $format !== 'all' ) {
		$base .= 'format/' . rawurlencode( $format ) . '/';
	}

	return $base;
}
