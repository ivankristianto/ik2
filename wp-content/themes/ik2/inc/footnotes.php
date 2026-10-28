<?php
/**
 * Link plain `[n]` source markers in a post to its `[n]` sources list, and back, with hover previews.
 *
 * Hooked on the post-content block of single templates, so feeds, excerpts and REST keep the plain markers.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\Footnotes;

use function IK2\Theme\Assets\section_slugs_for_request;

defined( 'ABSPATH' ) || exit;

const SOURCE_ITEM_PATTERN = '/<li\b[^>]*>\s*\[\d+\]/i';
const MARKER_PATTERN      = '/^(\s*)\[(\d+)\]\s*/';
const MARKER_RUN_PATTERN  = '/(?:\[\d+\])+/';
const SKIPPED_TAGS        = [ 'A', 'CODE', 'PRE', 'KBD', 'SAMP' ];

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_filter( 'render_block_core/post-content', __NAMESPACE__ . '\\link_sources' );
}

/**
 * Rewrite post content only where the single-article styles load.
 *
 * @param string $block_content Rendered post-content HTML.
 */
function link_sources( string $block_content ): string {
	if ( ! in_array( 'single', section_slugs_for_request(), true ) ) {
		return $block_content;
	}

	return rewrite( $block_content );
}

/**
 * Turn `[n]` markers into citation links when the content has a sources list.
 *
 * The sources list is the last list whose items all start with `[n]`, so a body bullet opening with a citation never claims a source.
 *
 * @param string $html Rendered post content.
 */
function rewrite( string $html ): string {
	if ( ! preg_match( SOURCE_ITEM_PATTERN, $html ) ) {
		return $html;
	}

	$document = scan( $html );
	$list     = sources_list( $document['lists'] );

	if ( $list === null ) {
		return $html;
	}

	$sources = [];
	foreach ( $list['items'] as $item ) {
		$item['target']               = $item['id'] ?? 'ik-source-' . $item['number'];
		$sources[ $item['number'] ] ??= $item;
	}

	$targets = array_column( $sources, 'target', 'number' );
	$cites   = [];
	$edits   = [];

	foreach ( $document['texts'] as [ $start, $length, $skipped ] ) {
		if ( $skipped || ( $start >= $list['open'][0] && $start < $list['end'] ) ) {
			continue;
		}

		$text   = substr( $html, $start, $length );
		$linked = link_markers( $text, $targets, $cites );

		if ( $linked !== $text ) {
			$edits[] = [ $start, $length, $linked ];
		}
	}

	$edits[] = [ $list['open'][0], $list['open'][1], tag_with( substr( $html, ...$list['open'] ), 'ik-sources' ) ];

	foreach ( $sources as $number => $item ) {
		$edits[] = [ $item['open'][0], $item['open'][1], tag_with( substr( $html, ...$item['open'] ), 'ik-source', $item['target'] ) ];
		$edits[] = [ $item['marker'][0], $item['marker'][1], '<span class="ik-source__num">' . $number . '</span> ' ];

		$count = $cites[ $number ] ?? 0;

		if ( $count > 0 && $item['close'] !== null ) {
			$body_start = $item['marker'][0] + $item['marker'][1];
			$body       = substr( $html, $body_start, $item['close'] - $body_start );
			$edits[]    = [ $item['close'], 0, backlinks( $number, $count ) . preview( $number, $body ) ];
		}
	}

	return apply_edits( $html, $edits );
}

/**
 * Collect text node spans and every list with its items, in one pass over the tokens.
 *
 * @param string $html Rendered post content.
 * @return array{texts: list<array{0: int, 1: int, 2: bool}>, lists: list<array<string, mixed>>}
 */
function scan( string $html ): array {
	$walker     = new Span_Walker( $html );
	$texts      = [];
	$lists      = [];
	$open_lists = [];
	$skip_depth = 0;
	$new_item   = false;

	while ( $walker->next_token() ) {
		[ $start, $length ] = $walker->span();
		$type               = $walker->get_token_type();

		if ( $type === '#text' ) {
			if ( $new_item ) {
				read_marker( $open_lists[ array_key_last( $open_lists ) ], $html, $start, $length );
			}

			$new_item = false;
			$texts[]  = [ $start, $length, $skip_depth > 0 ];
			continue;
		}

		if ( $type !== '#tag' ) {
			continue;
		}

		$new_item = false;
		$tag      = $walker->get_tag();
		$closer   = $walker->is_tag_closer();

		if ( in_array( $tag, SKIPPED_TAGS, true ) ) {
			$skip_depth = max( 0, $skip_depth + ( $closer ? -1 : 1 ) );
		} elseif ( ( $tag === 'UL' || $tag === 'OL' ) && ! $closer ) {
			$open_lists[] = [
				'open'    => [ $start, $length ],
				'end'     => null,
				'items'   => [],
				'current' => null,
			];
		} elseif ( ( $tag === 'UL' || $tag === 'OL' ) && $open_lists ) {
			$list = array_pop( $open_lists );
			close_item( $list, $start );
			$list['end'] = $start + $length;
			$lists[]     = $list;
		} elseif ( $tag === 'LI' && $open_lists ) {
			$key = array_key_last( $open_lists );
			// An `<li>` also closes an unclosed sibling, as HTML parsing does.
			close_item( $open_lists[ $key ], $start );

			if ( ! $closer ) {
				$id = $walker->get_attribute( 'id' );

				$open_lists[ $key ]['items'][] = [
					'open'   => [ $start, $length ],
					'id'     => is_string( $id ) && $id !== '' ? $id : null,
					'number' => null,
					'marker' => null,
					'close'  => null,
				];
				$open_lists[ $key ]['current'] = array_key_last( $open_lists[ $key ]['items'] );
				$new_item                      = true;
			}
		}
	}

	return [
		'texts' => $texts,
		'lists' => $lists,
	];
}

/**
 * Record the `[n]` marker when an item's first text node starts with one.
 *
 * @param array<string, mixed> $open_list Open list whose current item just started.
 * @param string               $html      Rendered post content.
 * @param int                  $start     Text node offset.
 * @param int                  $length    Text node length.
 */
function read_marker( array &$open_list, string $html, int $start, int $length ): void {
	if ( ! preg_match( MARKER_PATTERN, substr( $html, $start, $length ), $marker ) ) {
		return;
	}

	$item           = &$open_list['items'][ $open_list['current'] ];
	$item['number'] = (int) $marker[2];
	$item['marker'] = [ $start + strlen( $marker[1] ), strlen( $marker[0] ) - strlen( $marker[1] ) ];
}

/**
 * Mark the list's open item as ending at the given offset.
 *
 * @param array<string, mixed> $open_list     List being walked.
 * @param int                  $position Offset of the token that ends the item.
 */
function close_item( array &$open_list, int $position ): void {
	if ( $open_list['current'] !== null ) {
		$open_list['items'][ $open_list['current'] ]['close'] = $position;
		$open_list['current']                                 = null;
	}
}

/**
 * The last list in the document whose items all start with `[n]`.
 *
 * @param list<array<string, mixed>> $lists Lists from scan(), closed order.
 * @return array<string, mixed>|null
 */
function sources_list( array $lists ): ?array {
	$found = null;

	foreach ( $lists as $list ) {
		$numbered = $list['items'] && ! in_array( null, array_column( $list['items'], 'number' ), true );

		if ( $numbered && ( $found === null || $list['end'] > $found['end'] ) ) {
			$found = $list;
		}
	}

	return $found;
}

/**
 * Apply byte-offset replacements from the end of the string back, so earlier offsets stay valid.
 *
 * @param string                                 $html  Source HTML.
 * @param list<array{0: int, 1: int, 2: string}> $edits Offset, length to replace, replacement.
 */
function apply_edits( string $html, array $edits ): string {
	// Same offset: replace the token before inserting in front of it.
	usort( $edits, __NAMESPACE__ . '\\compare_edits' );

	foreach ( $edits as [ $start, $length, $replacement ] ) {
		$html = substr_replace( $html, $replacement, $start, $length );
	}

	return $html;
}

/**
 * Order edits by offset, last first, and replacements before insertions at the same offset.
 *
 * @param array{0: int, 1: int, 2: string} $a First edit.
 * @param array{0: int, 1: int, 2: string} $b Second edit.
 */
function compare_edits( array $a, array $b ): int {
	return [ $b[0], $a[1] === 0 ] <=> [ $a[0], $b[1] === 0 ];
}

/**
 * Wrap each run of known markers in a text node, like `[9][10]`, in one superscript.
 *
 * @param string             $text    Text node HTML.
 * @param array<int, string> $targets Source number to the id of its list item.
 * @param array<int, int>    $cites   Citations so far per number, updated in place.
 */
function link_markers( string $text, array $targets, array &$cites ): string {
	if ( ! str_contains( $text, '[' ) || ! preg_match_all( MARKER_RUN_PATTERN, $text, $runs, PREG_OFFSET_CAPTURE ) ) {
		return $text;
	}

	$output = '';
	$offset = 0;

	foreach ( $runs[0] as [ $run, $position ] ) {
		$ids = array_map( 'intval', explode( '][', trim( $run, '[]' ) ) );

		// Leave the run as typed when any number has no source, so `[0]` in prose never half-links.
		if ( array_diff_key( array_flip( $ids ), $targets ) ) {
			continue;
		}

		$links = [];
		foreach ( $ids as $id ) {
			$cites[ $id ] = ( $cites[ $id ] ?? 0 ) + 1;
			$links[]      = sprintf(
				'<a href="#%4$s" id="ik-cite-%1$d-%2$d" role="doc-noteref" interestfor="ik-source-preview-%1$d"><span class="ik-cite__label">%3$s </span>%1$d</a>',
				$id,
				$cites[ $id ],
				esc_html__( 'Source', 'ik2' ),
				esc_attr( $targets[ $id ] )
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
 * @param int $count Times the text cites it, at least one.
 */
function backlinks( int $id, int $count ): string {
	$links = [];

	for ( $n = 1; $n <= $count; $n++ ) {
		$links[] = sprintf(
			'<a class="ik-source__back" href="#ik-cite-%1$d-%2$d" role="doc-backlink" aria-label="%4$s">%3$s</a>',
			$id,
			$n,
			$count === 1 ? '&uarr;' : backlink_label( $n ),
			$count === 1
				? esc_attr__( 'Back to the citation', 'ik2' )
				/* translators: 1: citation position, 2: total citations of this source. */
				: esc_attr( sprintf( __( 'Back to citation %1$d of %2$d', 'ik2' ), $n, $count ) )
		);
	}

	if ( $count === 1 ) {
		return ' ' . $links[0];
	}

	return ' <span class="ik-source__backs"><span aria-hidden="true">&uarr;</span> ' . implode( ' ', $links ) . '</span>';
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
		without_ids( trim( $body ) )
	);
}

/**
 * Drop id attributes from copied markup so the preview never duplicates an id on the page.
 *
 * @param string $html Markup to copy.
 */
function without_ids( string $html ): string {
	$tags = new \WP_HTML_Tag_Processor( $html );

	while ( $tags->next_tag() ) {
		$tags->remove_attribute( 'id' );
	}

	return $tags->get_updated_html();
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
 * Add a class, and an id when the tag has none, to an opening tag.
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

	if ( $id !== null && $processor->get_attribute( 'id' ) === null ) {
		$processor->set_attribute( 'id', $id );
	}

	return $processor->get_updated_html();
}
