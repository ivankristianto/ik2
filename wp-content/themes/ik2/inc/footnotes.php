<?php
/**
 * Link plain `[n]` source markers in a post to its `[n]` sources list, and back.
 *
 * Hooked on the post-content block, so feeds, excerpts and REST keep the plain markers.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\Footnotes;

defined( 'ABSPATH' ) || exit;

const SOURCE_ITEM_PATTERN = '/<li\b[^>]*>\s*\[(\d+)\]/i';
const SOURCE_TEXT_PATTERN = '/^(\s*)\[(\d+)\]\s*/';
const MARKER_RUN_PATTERN  = '/(?:\[\d+\])+/';
const SKIPPED_TAGS        = [ 'a', 'code', 'pre', 'script', 'style', 'kbd', 'samp' ];

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_filter( 'render_block_core/post-content', __NAMESPACE__ . '\\link_sources' );
}

/**
 * Turn `[n]` markers into links when the post has list items that start with `[n]`.
 *
 * @param string $block_content Rendered post-content HTML.
 */
function link_sources( string $block_content ): string {
	if ( ! preg_match_all( SOURCE_ITEM_PATTERN, $block_content, $matches ) ) {
		return $block_content;
	}

	$source_ids = array_fill_keys( array_map( 'intval', $matches[1] ), true );
	$tokens     = preg_split( '/(<[^>]*>)/', $block_content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

	if ( ! is_array( $tokens ) ) {
		return $block_content;
	}

	$cites      = [];
	$starts     = [];
	$ends       = [];
	$bodies     = [];
	$skip_depth = 0;
	$source     = null;
	$list_open  = null;

	foreach ( $tokens as $i => $token ) {
		if ( $token[0] !== '<' ) {
			if ( $skip_depth === 0 && $source === null ) {
				$tokens[ $i ] = link_markers( $token, $source_ids, $cites );
			}
			continue;
		}

		if ( ! preg_match( '#^<(/?)([a-z][a-z0-9-]*)#i', $token, $tag ) ) {
			continue;
		}

		$is_closing = $tag[1] === '/';
		$tag_name   = strtolower( $tag[2] );

		if ( in_array( $tag_name, SKIPPED_TAGS, true ) ) {
			$skip_depth = max( 0, $skip_depth + ( $is_closing ? -1 : 1 ) );
		} elseif ( $tag_name === 'ul' || $tag_name === 'ol' ) {
			$list_open = $is_closing ? null : $i;
		} elseif ( $tag_name === 'li' && $is_closing && $source !== null ) {
			$ends[ $source ] = $i;
			$source          = null;
		} elseif ( $tag_name === 'li' && ! $is_closing ) {
			$text = $tokens[ $i + 1 ] ?? '<';

			if ( $text[0] === '<' || ! preg_match( SOURCE_TEXT_PATTERN, $text, $marker ) ) {
				continue;
			}

			$id = (int) $marker[2];

			if ( isset( $ends[ $id ] ) ) {
				continue;
			}

			$source           = $id;
			$starts[ $id ]    = $i;
			$bodies[ $id ]    = substr( $text, strlen( $marker[0] ) );
			$tokens[ $i ]     = tag_with( $token, 'ik-source', 'ik-source-' . $id );
			$tokens[ $i + 1 ] = $marker[1] . '<span class="ik-source__num">' . $id . '</span> ' . $bodies[ $id ];

			if ( $list_open !== null ) {
				$tokens[ $list_open ] = tag_with( $tokens[ $list_open ], 'ik-sources' );
			}
		}
	}

	// Sources come after the text, so the citation counts are complete only now.
	foreach ( $ends as $id => $end ) {
		$body           = $bodies[ $id ] . implode( '', array_slice( $tokens, $starts[ $id ] + 2, $end - $starts[ $id ] - 2 ) );
		$tokens[ $end ] = backlinks( $id, $cites[ $id ] ?? 0 ) . preview( $id, $body ) . $tokens[ $end ];
	}

	return implode( '', $tokens );
}

/**
 * Wrap each run of known markers in a text node, like `[9][10]`, in one superscript.
 *
 * @param string           $text       Text node HTML.
 * @param array<int, true> $source_ids Numbers that have a sources item.
 * @param array<int, int>  $cites      Citations so far per number, updated in place.
 */
function link_markers( string $text, array $source_ids, array &$cites ): string {
	if ( ! str_contains( $text, '[' ) || ! preg_match_all( MARKER_RUN_PATTERN, $text, $runs, PREG_OFFSET_CAPTURE ) ) {
		return $text;
	}

	$output = '';
	$offset = 0;

	foreach ( $runs[0] as [ $run, $position ] ) {
		$ids = array_map( 'intval', explode( '][', trim( $run, '[]' ) ) );

		// Leave the run as typed when any number has no source, so `[0]` in prose never half-links.
		if ( array_diff_key( array_flip( $ids ), $source_ids ) ) {
			continue;
		}

		$links = [];
		foreach ( $ids as $id ) {
			$cites[ $id ] = ( $cites[ $id ] ?? 0 ) + 1;
			$links[]      = sprintf(
				'<a href="#ik-source-%1$d" id="ik-cite-%1$d-%2$d" role="doc-noteref" interestfor="ik-source-preview-%1$d"><span class="ik-cite__label">%3$s </span>%1$d</a>',
				$id,
				$cites[ $id ],
				esc_html__( 'Source', 'ik2' )
			);
		}

		$output .= substr( $text, $offset, $position - $offset )
			. '<sup class="ik-cite">' . implode( '<span class="ik-cite__sep">,</span>', $links ) . '</sup>';
		$offset  = $position + strlen( $run );
	}

	return $output . substr( $text, $offset );
}

/**
 * Links from a source back to each citation of it: a single arrow, or lettered when cited more than once.
 *
 * @param int $id    Source number.
 * @param int $count Times the text cites it.
 */
function backlinks( int $id, int $count ): string {
	if ( $count === 1 ) {
		return sprintf(
			' <a class="ik-source__back" href="#ik-cite-%d-1" role="doc-backlink" aria-label="%s">&uarr;</a>',
			$id,
			esc_attr__( 'Back to the citation', 'ik2' )
		);
	}

	$links = [];
	for ( $n = 1; $n <= $count; $n++ ) {
		$links[] = sprintf(
			'<a class="ik-source__back" href="#ik-cite-%1$d-%2$d" role="doc-backlink" aria-label="%4$s">%3$s</a>',
			$id,
			$n,
			backlink_label( $n ),
			/* translators: 1: citation position, 2: total citations of this source. */
			esc_attr( sprintf( __( 'Back to citation %1$d of %2$d', 'ik2' ), $n, $count ) )
		);
	}

	return $links ? ' <span class="ik-source__backs"><span aria-hidden="true">&uarr;</span> ' . implode( ' ', $links ) . '</span>' : '';
}

/**
 * Hover and focus preview of a source, opened by the citations' `interestfor`.
 *
 * @param int    $id   Source number.
 * @param string $body Source item HTML after its `[n]` marker.
 */
function preview( int $id, string $body ): string {
	return sprintf(
		'<div popover="hint" id="ik-source-preview-%1$d" class="ik-source-preview"><span class="ik-source-preview__label">%2$s</span> %3$s</div>',
		$id,
		/* translators: %d: source number. */
		esc_html( sprintf( __( 'Source %d', 'ik2' ), $id ) ),
		trim( $body )
	);
}

/**
 * Letter for the nth back link: a to z, then aa, ab, and so on.
 *
 * @param int $n One-based position.
 */
function backlink_label( int $n ): string {
	$label = '';

	while ( $n > 0 ) {
		--$n;
		$label = chr( ord( 'a' ) + $n % 26 ) . $label;
		$n     = intdiv( $n, 26 );
	}

	return $label;
}

/**
 * Add a class, and an id when given, to an opening tag.
 *
 * @param string      $tag        Opening tag HTML.
 * @param string      $class_name Class to add.
 * @param string|null $id         Id to set.
 */
function tag_with( string $tag, string $class_name, ?string $id = null ): string {
	$processor = new \WP_HTML_Tag_Processor( $tag );

	if ( ! $processor->next_tag() ) {
		return $tag;
	}

	$processor->add_class( $class_name );

	if ( $id !== null ) {
		$processor->set_attribute( 'id', $id );
	}

	return $processor->get_updated_html();
}
