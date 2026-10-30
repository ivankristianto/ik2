<?php
/**
 * Corpus text: turn parsed blocks into sections, Chunks and the Document's embed text.
 *
 * Pure functions with no WordPress calls, so PHPUnit loads this file directly.
 * That is also why there is no ABSPATH guard here.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\Corpus\Text;

const DROPPED_BLOCKS = [
	'core/image',
	'core/gallery',
	'core/cover',
	'core/embed',
	'core/video',
	'core/audio',
	'core/file',
	'core/buttons',
	'core/button',
	'core/spacer',
	'core/separator',
	'core/html',
];

const TEXT_BLOCKS = [
	'core/paragraph',
	'core/list',
	'core/list-item',
	'core/quote',
	'core/pullquote',
	'core/verse',
	'core/preformatted',
	'core/table',
];

const CONTAINER_BLOCKS = [
	'core/group',
	'core/columns',
	'core/column',
	'core/media-text',
	'core/details',
];

const CODE_TOKEN_CAP   = 200;
const CHUNK_TARGET     = 400;
const CHUNK_CAP        = 512;
const CHUNK_MIN        = 80;
const EMBED_TEXT_WORDS = 800;

/**
 * Walk parse_blocks() output into sections split on h2 and h3.
 *
 * @param array<int,array<string,mixed>> $blocks Output of parse_blocks().
 * @param callable|null                  $render fn(array $block): string for unknown blocks. Null skips them.
 * @return list<array{heading:string, anchor:string, text:string}> The first section has heading '' (text before the first h2/h3).
 */
function clean_blocks( array $blocks, ?callable $render = null ): array {
	$sections = [ new_section( '', '' ) ];

	foreach ( $blocks as $block ) {
		walk_block( $block, $sections, $render );
	}

	$cleaned = [];
	foreach ( $sections as $index => $section ) {
		if ( 0 === $index && [] === $section['paragraphs'] ) {
			continue;
		}

		$cleaned[] = [
			'heading' => $section['heading'],
			'anchor'  => $section['anchor'],
			'text'    => implode( "\n\n", $section['paragraphs'] ),
		];
	}

	return $cleaned;
}

/**
 * Split sections into Chunks of about 300 to 400 estimated tokens, never over 512.
 *
 * @param string                                                  $title    Article title.
 * @param list<array{heading:string, anchor:string, text:string}> $sections Output of clean_blocks().
 * @return list<array{chunk_index:int, heading:string, anchor:string, text:string, token_estimate:int}>
 */
function chunk_sections( string $title, array $sections ): array {
	$pieces = [];

	foreach ( $sections as $index => $section ) {
		$paragraphs = split_paragraphs( $section['text'] );

		if ( '' === $section['heading'] && [] === $paragraphs ) {
			continue;
		}

		$prefix = chunk_prefix( $title, $section['heading'] );

		foreach ( split_long_section( $prefix, $paragraphs ) as $part ) {
			$pieces[] = [
				'heading'    => $section['heading'],
				'anchor'     => $section['anchor'],
				'prefix'     => $prefix,
				'section'    => $index,
				'paragraphs' => $part,
			];
		}
	}

	$chunks = [];
	foreach ( merge_small_pieces( $pieces ) as $chunk_index => $piece ) {
		$text = piece_text( $piece );

		$chunks[] = [
			'chunk_index'    => $chunk_index,
			'heading'        => $piece['heading'],
			'anchor'         => $piece['anchor'],
			'text'           => $text,
			'token_estimate' => estimate_tokens( $text ),
		];
	}

	return $chunks;
}

/**
 * Text for the Document-level vector: title, categories, tags, then the first 800 words of the body.
 *
 * @param string                                                  $title      Article title.
 * @param array<int,string>                                       $categories Category names.
 * @param array<int,string>                                       $tags       Tag names.
 * @param list<array{heading:string, anchor:string, text:string}> $sections   Output of clean_blocks().
 */
function build_embed_text( string $title, array $categories, array $tags, array $sections ): string {
	$header = $title . "\n"
		. 'Category: ' . implode( ', ', $categories ) . "\n"
		. 'Tags: ' . implode( ', ', $tags );

	$remaining = EMBED_TEXT_WORDS;
	$body      = [];

	foreach ( $sections as $section ) {
		$paragraphs = split_paragraphs( $section['text'] );

		if ( '' !== $section['heading'] ) {
			array_unshift( $paragraphs, $section['heading'] );
		}

		foreach ( $paragraphs as $paragraph ) {
			$words = words( $paragraph );

			if ( count( $words ) > $remaining ) {
				$words = array_slice( $words, 0, $remaining );
			}

			if ( [] !== $words ) {
				$body[] = implode( ' ', $words );
			}

			$remaining -= count( $words );

			if ( $remaining <= 0 ) {
				break 2;
			}
		}
	}

	return $header . "\n\n" . implode( "\n\n", $body );
}

/**
 * Estimated token count: ceil( words * 1.3 ).
 *
 * @param string $text Plain text.
 */
function estimate_tokens( string $text ): int {
	// Integer math, because ceil( 10 * 1.3 ) is 14 in floating point.
	return intdiv( count( words( $text ) ) * 13 + 9, 10 );
}

/**
 * Largest word count whose estimate stays within a token budget.
 *
 * @param int $tokens Token budget.
 */
function max_words( int $tokens ): int {
	return intdiv( $tokens * 10, 13 );
}

/**
 * Split text on any whitespace, including the no-break space that &nbsp; decodes to.
 *
 * @param string $text Plain text.
 * @return list<string>
 */
function words( string $text ): array {
	$words = preg_split( '/[\s\x{00A0}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY );

	return false === $words ? [] : $words;
}

/**
 * Section under construction.
 *
 * @param string $heading Heading text.
 * @param string $anchor  Heading id.
 * @return array{heading:string,anchor:string,paragraphs:list<string>}
 */
function new_section( string $heading, string $anchor ): array {
	return [
		'heading'    => $heading,
		'anchor'     => $anchor,
		'paragraphs' => [],
	];
}

/**
 * Add one parsed block's text to the sections.
 *
 * @param array<string,mixed>                                                         $block    Parsed block.
 * @param non-empty-list<array{heading:string,anchor:string,paragraphs:list<string>}> $sections Sections so far, appended to in place.
 * @param callable|null                                                               $render   Renderer for unknown blocks.
 */
function walk_block( array $block, array &$sections, ?callable $render ): void {
	$name = $block['blockName'] ?? null;
	$html = is_string( $block['innerHTML'] ?? null ) ? $block['innerHTML'] : '';

	if ( null === $name ) {
		add_freeform( $html, $sections );
		return;
	}

	if ( in_array( $name, DROPPED_BLOCKS, true ) ) {
		return;
	}

	if ( 'core/heading' === $name ) {
		$level = (int) ( $block['attrs']['level'] ?? 2 );
		$text  = html_to_text( $html );

		if ( 2 === $level || 3 === $level ) {
			$sections[] = new_section( $text, heading_anchor( $html, $block['attrs']['anchor'] ?? '' ) );
		} else {
			add_paragraphs( [ $text ], $sections );
		}
		return;
	}

	if ( 'core/code' === $name ) {
		add_paragraphs( [ cut_code( html_to_text( $html ) ) ], $sections );
		return;
	}

	if ( in_array( $name, TEXT_BLOCKS, true ) || in_array( $name, CONTAINER_BLOCKS, true ) ) {
		walk_inner_content( $block, $sections, $render );
		return;
	}

	if ( null !== $render ) {
		add_paragraphs( html_to_paragraphs( (string) $render( $block ) ), $sections );
	}
}

/**
 * Walk innerContent in order, so wrapper text such as a quote's citation lands between its inner blocks.
 *
 * @param array<string,mixed>                                                         $block    Parsed block.
 * @param non-empty-list<array{heading:string,anchor:string,paragraphs:list<string>}> $sections Sections so far, appended to in place.
 * @param callable|null                                                               $render   Renderer for unknown blocks.
 */
function walk_inner_content( array $block, array &$sections, ?callable $render ): void {
	$inner_blocks = is_array( $block['innerBlocks'] ?? null ) ? array_values( $block['innerBlocks'] ) : [];
	$content      = is_array( $block['innerContent'] ?? null ) ? $block['innerContent'] : [ $block['innerHTML'] ?? '' ];
	$next         = 0;

	foreach ( $content as $part ) {
		if ( is_string( $part ) ) {
			add_paragraphs( html_to_paragraphs( $part ), $sections );
		} elseif ( isset( $inner_blocks[ $next ] ) && is_array( $inner_blocks[ $next ] ) ) {
			walk_block( $inner_blocks[ $next ], $sections, $render );
			++$next;
		}
	}
}

/**
 * Add classic-editor HTML, starting a new section at each h2 and h3.
 *
 * @param string                                                                      $html     Freeform HTML.
 * @param non-empty-list<array{heading:string,anchor:string,paragraphs:list<string>}> $sections Sections so far, appended to in place.
 */
function add_freeform( string $html, array &$sections ): void {
	$parts = preg_split( '#(<h2\b[^>]*>.*?</h2>|<h3\b[^>]*>.*?</h3>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

	if ( false === $parts ) {
		return;
	}

	foreach ( $parts as $part ) {
		if ( preg_match( '#^<h[23]\b[^>]*>(.*)</h[23]>$#is', $part, $match ) ) {
			$sections[] = new_section( html_to_text( $match[1] ), heading_anchor( $part, '' ) );
		} else {
			add_paragraphs( html_to_paragraphs( $part ), $sections );
		}
	}
}

/**
 * Append paragraphs to the current (last) section.
 *
 * @param array<int,string>                                                           $paragraphs Plain-text paragraphs.
 * @param non-empty-list<array{heading:string,anchor:string,paragraphs:list<string>}> $sections   Sections so far, appended to in place.
 */
function add_paragraphs( array $paragraphs, array &$sections ): void {
	$last = array_key_last( $sections );

	foreach ( $paragraphs as $paragraph ) {
		if ( '' !== $paragraph ) {
			$sections[ $last ]['paragraphs'][] = $paragraph;
		}
	}
}

/**
 * The heading's id attribute, falling back to the block's anchor attribute.
 *
 * @param string $html     Heading HTML.
 * @param mixed  $fallback Anchor from the block attributes.
 */
function heading_anchor( string $html, mixed $fallback ): string {
	if ( preg_match( '#^\s*<h[1-6]\b[^>]*?(?<![-\w])id\s*=\s*(["\'])(.*?)\1#is', $html, $match ) ) {
		return html_entity_decode( $match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	return is_string( $fallback ) ? $fallback : '';
}

/**
 * Plain-text paragraphs from HTML: block tags and blank lines break paragraphs, whitespace collapses inside each.
 *
 * @param string $html HTML fragment.
 * @return list<string>
 */
function html_to_paragraphs( string $html ): array {
	$html = (string) preg_replace( '#<(script|style)\b[^>]*>.*?</\1>#is', '', $html );
	$html = (string) preg_replace( '#<br\b[^>]*>|</t[dh]>#i', ' ', $html );
	$html = (string) preg_replace( '#</?(?:p|div|li|ul|ol|h[1-6]|tr|table|thead|tbody|tfoot|blockquote|pre|figure|figcaption|cite|dl|dt|dd|section|article|hr)\b[^>]*>#i', "\n\n", $html );
	$text = html_entity_decode( strip_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

	$paragraphs = [];
	foreach ( split_paragraphs( $text ) as $paragraph ) {
		$paragraph = implode( ' ', words( $paragraph ) );

		if ( '' !== $paragraph ) {
			$paragraphs[] = $paragraph;
		}
	}

	return $paragraphs;
}

/**
 * HTML as one line of plain text.
 *
 * @param string $html HTML fragment.
 */
function html_to_text( string $html ): string {
	return implode( ' ', html_to_paragraphs( $html ) );
}

/**
 * Split text on blank lines, dropping empty paragraphs.
 *
 * @param string $text Plain text.
 * @return list<string>
 */
function split_paragraphs( string $text ): array {
	$parts = preg_split( '/\n[ \t\x{00A0}]*\n\s*/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY );

	if ( false === $parts ) {
		return [];
	}

	$paragraphs = [];
	foreach ( $parts as $part ) {
		$part = trim( $part );

		if ( '' !== $part ) {
			$paragraphs[] = $part;
		}
	}

	return $paragraphs;
}

/**
 * Cut a code block over 200 estimated tokens and mark the cut.
 *
 * @param string $text Code as plain text.
 */
function cut_code( string $text ): string {
	if ( estimate_tokens( $text ) <= CODE_TOKEN_CAP ) {
		return $text;
	}

	// The marker is glued to the last word so it adds no word to the estimate.
	return implode( ' ', array_slice( words( $text ), 0, max_words( CODE_TOKEN_CAP ) ) ) . "\u{2026}";
}

/**
 * First line of every Chunk.
 *
 * @param string $title   Article title.
 * @param string $heading Section heading, '' for the opening section.
 */
function chunk_prefix( string $title, string $heading ): string {
	return '' === $heading ? $title : $title . ' > ' . $heading;
}

/**
 * Split one section's paragraphs into parts that fit the cap, sized evenly around the target.
 *
 * @param string            $prefix     Chunk prefix line.
 * @param array<int,string> $paragraphs Section paragraphs.
 * @return list<list<string>>
 */
function split_long_section( string $prefix, array $paragraphs ): array {
	if ( estimate_tokens( join_text( $prefix, $paragraphs ) ) <= CHUNK_CAP ) {
		return [ array_values( $paragraphs ) ];
	}

	$units = [];
	foreach ( $paragraphs as $paragraph ) {
		array_push( $units, ...split_long_paragraph( $prefix, $paragraph ) );
	}

	$parts  = (int) ceil( estimate_tokens( join_text( $prefix, $units ) ) / CHUNK_TARGET );
	$target = (int) ceil( estimate_tokens( join_text( $prefix, $units ) ) / max( 1, $parts ) );
	$split  = [];
	$part   = [];

	foreach ( $units as $unit ) {
		if ( [] !== $part && estimate_tokens( join_text( $prefix, [ ...$part, $unit ] ) ) > CHUNK_CAP ) {
			$split[] = $part;
			$part    = [];
		}

		$part[] = $unit;

		if ( estimate_tokens( join_text( $prefix, $part ) ) >= $target ) {
			$split[] = $part;
			$part    = [];
		}
	}

	if ( [] !== $part ) {
		$split[] = $part;
	}

	return $split;
}

/**
 * Cut a paragraph that alone breaks the cap into word windows of about the target size.
 *
 * @param string $prefix    Chunk prefix line.
 * @param string $paragraph One paragraph.
 * @return list<string>
 */
function split_long_paragraph( string $prefix, string $paragraph ): array {
	if ( estimate_tokens( join_text( $prefix, [ $paragraph ] ) ) <= CHUNK_CAP ) {
		return [ $paragraph ];
	}

	$size    = max( 1, max_words( CHUNK_TARGET ) - count( words( $prefix ) ) );
	$windows = [];

	foreach ( array_chunk( words( $paragraph ), $size ) as $window ) {
		$windows[] = implode( ' ', $window );
	}

	return $windows;
}

/**
 * Merge pieces under 80 tokens into the next piece, or the previous one when last, without passing the cap.
 *
 * @param list<array{heading:string, anchor:string, prefix:string, section:int, paragraphs:list<string>}> $pieces Split sections in order.
 * @return list<array{heading:string, anchor:string, prefix:string, section:int, paragraphs:list<string>}>
 */
function merge_small_pieces( array $pieces ): array {
	$merged = [];
	$carry  = null;
	$last   = count( $pieces ) - 1;

	foreach ( $pieces as $index => $piece ) {
		if ( null !== $carry ) {
			$joined = join_pieces( $carry, $piece );

			if ( fits( $joined ) ) {
				$piece = $joined;
			} else {
				$merged = append_or_merge_back( $merged, $carry );
			}
			$carry = null;
		}

		if ( ! is_small( $piece ) ) {
			$merged[] = $piece;
		} elseif ( $index < $last ) {
			$carry = $piece;
		} else {
			$merged = append_or_merge_back( $merged, $piece );
		}
	}

	return $merged;
}

/**
 * Merge a piece into the previous one when it fits, else keep it on its own.
 *
 * @param list<array{heading:string, anchor:string, prefix:string, section:int, paragraphs:list<string>}> $merged Pieces so far.
 * @param array{heading:string, anchor:string, prefix:string, section:int, paragraphs:list<string>}       $piece  Piece to place.
 * @return list<array{heading:string, anchor:string, prefix:string, section:int, paragraphs:list<string>}>
 */
function append_or_merge_back( array $merged, array $piece ): array {
	$previous = array_key_last( $merged );

	if ( null !== $previous && is_small( $piece ) ) {
		$joined = join_pieces( $merged[ $previous ], $piece );

		if ( fits( $joined ) ) {
			$merged[ $previous ] = $joined;
			return $merged;
		}
	}

	$merged[] = $piece;
	return $merged;
}

/**
 * Join two pieces under the first one's heading, keeping the second section's heading inline.
 *
 * @param array{heading:string, anchor:string, prefix:string, section:int, paragraphs:list<string>} $first  Earlier piece.
 * @param array{heading:string, anchor:string, prefix:string, section:int, paragraphs:list<string>} $second Later piece.
 * @return array{heading:string, anchor:string, prefix:string, section:int, paragraphs:list<string>}
 */
function join_pieces( array $first, array $second ): array {
	$inline = $second['section'] !== $first['section'] && '' !== $second['heading'] ? [ $second['heading'] ] : [];

	return [
		'heading'    => $first['heading'],
		'anchor'     => $first['anchor'],
		'prefix'     => $first['prefix'],
		'section'    => $second['section'],
		'paragraphs' => [ ...$first['paragraphs'], ...$inline, ...$second['paragraphs'] ],
	];
}

/**
 * Whether a piece's body is under the 80-token floor. The prefix line does not count.
 *
 * @param array{heading:string, anchor:string, prefix:string, section:int, paragraphs:list<string>} $piece Piece.
 */
function is_small( array $piece ): bool {
	return estimate_tokens( implode( "\n\n", $piece['paragraphs'] ) ) < CHUNK_MIN;
}

/**
 * Whether a piece's full Chunk text, prefix included, is within the cap.
 *
 * @param array{heading:string, anchor:string, prefix:string, section:int, paragraphs:list<string>} $piece Piece.
 */
function fits( array $piece ): bool {
	return estimate_tokens( piece_text( $piece ) ) <= CHUNK_CAP;
}

/**
 * Stored Chunk text for a piece.
 *
 * @param array{heading:string, anchor:string, prefix:string, section:int, paragraphs:list<string>} $piece Piece.
 */
function piece_text( array $piece ): string {
	return join_text( $piece['prefix'], $piece['paragraphs'] );
}

/**
 * Prefix line, then paragraphs separated by blank lines.
 *
 * @param string            $prefix     Chunk prefix line.
 * @param array<int,string> $paragraphs Paragraphs.
 */
function join_text( string $prefix, array $paragraphs ): string {
	return implode( "\n\n", [ $prefix, ...$paragraphs ] );
}
