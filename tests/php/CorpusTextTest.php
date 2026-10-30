<?php
/**
 * Tests for the corpus text module. Fixtures are real parse_blocks() output.
 *
 * @package IK2\Tests
 */

declare(strict_types=1);

namespace IK2\Tests;

use PHPUnit\Framework\TestCase;

use function IK2\Plugin\Corpus\Text\build_embed_text;
use function IK2\Plugin\Corpus\Text\chunk_sections;
use function IK2\Plugin\Corpus\Text\clean_blocks;
use function IK2\Plugin\Corpus\Text\estimate_tokens;

final class CorpusTextTest extends TestCase {

	/**
	 * Parsed blocks from tests/php/fixtures/{name}.json.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function fixture( string $name ): array {
		$blocks = json_decode( (string) file_get_contents( __DIR__ . "/fixtures/{$name}.json" ), true );
		self::assertIsArray( $blocks );

		return $blocks;
	}

	/**
	 * Distinct words, so each paragraph can be found again in a Chunk.
	 */
	private static function words( string $tag, int $count ): string {
		$words = [];
		for ( $i = 0; $i < $count; $i++ ) {
			$words[] = "{$tag}{$i}";
		}

		return implode( ' ', $words );
	}

	/**
	 * A cleaned section.
	 *
	 * @return array{heading:string, anchor:string, text:string}
	 */
	private static function section( string $heading, string $text, string $anchor = '' ): array {
		return [
			'heading' => $heading,
			'anchor'  => $anchor,
			'text'    => $text,
		];
	}

	public function test_h2_and_h3_start_sections(): void {
		$sections = clean_blocks( self::fixture( 'headings' ) );

		self::assertSame( [ '', 'Install the & tools', 'Configure it' ], array_column( $sections, 'heading' ) );
		self::assertSame( [ '', 'install-step', '' ], array_column( $sections, 'anchor' ) );
		self::assertSame( 'Opening line before any heading.', $sections[0]['text'] );
		self::assertSame( 'Run the installer first.', $sections[1]['text'] );
	}

	public function test_h4_stays_inline_in_the_current_section(): void {
		$sections = clean_blocks( self::fixture( 'headings' ) );

		self::assertCount( 3, $sections );
		self::assertSame( "Edit the config file.\n\nA small note\n\nStill part of Configure it.", $sections[2]['text'] );
	}

	public function test_no_empty_opening_section_when_content_starts_with_a_heading(): void {
		$sections = clean_blocks( array_slice( self::fixture( 'headings' ), 2 ) );

		self::assertSame( 'Install the & tools', $sections[0]['heading'] );
	}

	public function test_media_blocks_are_dropped(): void {
		$sections = clean_blocks( self::fixture( 'media' ), [ self::class, 'render_marker' ] );

		self::assertCount( 1, $sections );
		self::assertSame( "Before the media.\n\nAfter the media.", $sections[0]['text'] );
	}

	public function test_nested_groups_are_walked_and_headings_inside_them_split(): void {
		$sections = clean_blocks( self::fixture( 'nested' ) );

		self::assertSame( [ '', 'Heading inside a group' ], array_column( $sections, 'heading' ) );
		self::assertSame( 'inside-group', $sections[1]['anchor'] );
		self::assertSame( 'Intro inside a column.', $sections[0]['text'] );
	}

	public function test_quote_list_table_verse_and_preformatted_text_is_kept(): void {
		$sections = clean_blocks( self::fixture( 'nested' ) );

		self::assertSame(
			implode(
				"\n\n",
				[
					'Column text.',
					'Quoted words.',
					'A Person',
					'First item',
					'Nested item',
					'Second item',
					'Name Value',
					'alpha 1',
					'Pulled line.',
					'Roses line Violets line',
					'Preformatted text',
				]
			),
			$sections[1]['text']
		);
	}

	public function test_unknown_blocks_use_the_renderer(): void {
		$sections = clean_blocks( self::fixture( 'nested' ), [ self::class, 'render_marker' ] );

		self::assertStringEndsWith( "Preformatted text\n\nRendered ik2/unknown-dynamic", $sections[1]['text'] );
	}

	/**
	 * Stand-in for render_block().
	 *
	 * @param array<string,mixed> $block Parsed block.
	 */
	public static function render_marker( array $block ): string {
		return '<div><p>Rendered ' . $block['blockName'] . '</p><script>var x = 1;</script></div>';
	}

	public function test_freeform_html_splits_on_h2_and_h3(): void {
		$sections = clean_blocks( self::fixture( 'freeform' ) );

		self::assertSame( [ '', 'Classic heading', 'Classic sub' ], array_column( $sections, 'heading' ) );
		self::assertSame( [ '', 'classic-heading', '' ], array_column( $sections, 'anchor' ) );
		self::assertSame( 'Classic intro with a non-breaking space.', $sections[0]['text'] );
		self::assertSame( "Classic text & more.\n\nClassic small heading", $sections[1]['text'] );
		self::assertSame( "Bare line one still line one.\n\nSecond paragraph.", $sections[2]['text'] );
	}

	public function test_long_code_block_is_cut_at_200_tokens(): void {
		$sections   = clean_blocks( self::fixture( 'code' ) );
		$paragraphs = explode( "\n\n", $sections[0]['text'] );

		self::assertCount( 3, $paragraphs );
		self::assertStringEndsWith( "tok152\u{2026}", $paragraphs[1] );
		self::assertStringNotContainsString( 'tok153', $paragraphs[1] );
		self::assertLessThanOrEqual( 200, estimate_tokens( $paragraphs[1] ) );
		self::assertSame( 'echo "short" && exit', $paragraphs[2] );
	}

	public function test_estimate_tokens_is_ceil_of_words_times_1_3(): void {
		self::assertSame( 0, estimate_tokens( "  \n " ) );
		self::assertSame( 4, estimate_tokens( 'one two three' ) );
		self::assertSame( 13, estimate_tokens( self::words( 'w', 10 ) ) );
		self::assertSame( 199, estimate_tokens( self::words( 'w', 153 ) ) );
		self::assertSame( 201, estimate_tokens( self::words( 'w', 154 ) ) );
	}

	public function test_chunk_text_starts_with_title_and_heading(): void {
		$chunks = chunk_sections(
			'My Title',
			[
				self::section( '', self::words( 'a', 100 ) ),
				self::section( 'Setup', self::words( 'b', 100 ), 'setup' ),
			]
		);

		self::assertCount( 2, $chunks );
		self::assertSame( [ 0, 1 ], array_column( $chunks, 'chunk_index' ) );
		self::assertSame( "My Title\n\n" . self::words( 'a', 100 ), $chunks[0]['text'] );
		self::assertSame( "My Title > Setup\n\n" . self::words( 'b', 100 ), $chunks[1]['text'] );
		self::assertSame( 'setup', $chunks[1]['anchor'] );

		foreach ( $chunks as $chunk ) {
			self::assertSame( estimate_tokens( $chunk['text'] ), $chunk['token_estimate'] );
		}
	}

	public function test_tiny_section_merges_into_the_next(): void {
		$chunks = chunk_sections(
			'T',
			[
				self::section( 'Short', self::words( 'a', 20 ), 'short' ),
				self::section( 'Long', self::words( 'b', 100 ), 'long' ),
				self::section( 'Other', self::words( 'c', 100 ) ),
			]
		);

		self::assertCount( 2, $chunks );
		self::assertSame( 'Short', $chunks[0]['heading'] );
		self::assertSame( 'short', $chunks[0]['anchor'] );
		self::assertSame( "T > Short\n\n" . self::words( 'a', 20 ) . "\n\nLong\n\n" . self::words( 'b', 100 ), $chunks[0]['text'] );
		self::assertSame( 'Other', $chunks[1]['heading'] );
	}

	public function test_tiny_last_section_merges_into_the_previous(): void {
		$chunks = chunk_sections(
			'T',
			[
				self::section( '', self::words( 'a', 100 ) ),
				self::section( 'Wrap up', self::words( 'b', 10 ) ),
			]
		);

		self::assertCount( 1, $chunks );
		self::assertSame( '', $chunks[0]['heading'] );
		self::assertSame( "T\n\n" . self::words( 'a', 100 ) . "\n\nWrap up\n\n" . self::words( 'b', 10 ), $chunks[0]['text'] );
	}

	public function test_single_tiny_section_is_one_chunk(): void {
		$chunks = chunk_sections( 'T', [ self::section( '', 'Just a short note.' ) ] );

		self::assertCount( 1, $chunks );
		self::assertSame( "T\n\nJust a short note.", $chunks[0]['text'] );
	}

	public function test_tiny_merge_never_pushes_a_chunk_over_the_cap(): void {
		$chunks = chunk_sections(
			'T',
			[
				self::section( 'Short', self::words( 'a', 30 ) ),
				self::section( 'Long', self::words( 'b', 380 ) ),
			]
		);

		self::assertSame( [ 'Short', 'Long' ], array_column( $chunks, 'heading' ) );
		foreach ( $chunks as $chunk ) {
			self::assertLessThanOrEqual( 512, $chunk['token_estimate'] );
		}
	}

	public function test_long_section_splits_on_paragraph_boundaries(): void {
		$paragraphs = [];
		for ( $i = 0; $i < 10; $i++ ) {
			$paragraphs[] = self::words( "p{$i}w", 60 );
		}

		$chunks = chunk_sections( 'T', [ self::section( 'Guide', implode( "\n\n", $paragraphs ), 'guide' ) ] );

		self::assertCount( 2, $chunks );
		self::assertSame( [ 0, 1 ], array_column( $chunks, 'chunk_index' ) );

		$seen = [];
		foreach ( $chunks as $chunk ) {
			self::assertSame( 'Guide', $chunk['heading'] );
			self::assertSame( 'guide', $chunk['anchor'] );
			self::assertStringStartsWith( "T > Guide\n\n", $chunk['text'] );
			self::assertLessThanOrEqual( 512, $chunk['token_estimate'] );
			self::assertGreaterThanOrEqual( 300, $chunk['token_estimate'] );

			array_push( $seen, ...array_slice( explode( "\n\n", $chunk['text'] ), 1 ) );
		}

		self::assertSame( $paragraphs, $seen );
	}

	public function test_single_paragraph_over_the_cap_is_split_by_words(): void {
		$chunks = chunk_sections( 'T', [ self::section( 'Wall', self::words( 'w', 700 ) ) ] );

		self::assertGreaterThan( 1, count( $chunks ) );

		$seen = [];
		foreach ( $chunks as $chunk ) {
			self::assertLessThanOrEqual( 512, $chunk['token_estimate'] );
			array_push( $seen, ...explode( ' ', substr( $chunk['text'], strlen( "T > Wall\n\n" ) ) ) );
		}

		self::assertSame( explode( ' ', self::words( 'w', 700 ) ), $seen );
	}

	public function test_empty_sections_produce_no_chunks(): void {
		self::assertSame( [], chunk_sections( 'T', [] ) );
		self::assertSame( [], chunk_sections( 'T', [ self::section( '', '' ) ] ) );
	}

	public function test_embed_text_format(): void {
		$text = build_embed_text(
			'Secure Your WordPress Site',
			[ 'WordPress', 'Security' ],
			[ 'hardening' ],
			[
				self::section( '', 'Intro words.' ),
				self::section( 'Updates', "Keep core updated.\n\nAnd plugins." ),
			]
		);

		self::assertSame(
			"Secure Your WordPress Site\nCategory: WordPress, Security\nTags: hardening\n\nIntro words.\n\nUpdates\n\nKeep core updated.\n\nAnd plugins.",
			$text
		);
	}

	public function test_embed_text_keeps_empty_tag_line(): void {
		$text = build_embed_text( 'T', [ 'Misc' ], [], [ self::section( '', 'Body.' ) ] );

		self::assertSame( "T\nCategory: Misc\nTags: \n\nBody.", $text );
	}

	public function test_embed_text_keeps_empty_category_and_tag_lines_and_blank_line(): void {
		self::assertSame( "T\nCategory: \nTags: \n\n", build_embed_text( 'T', [], [], [] ) );
	}

	public function test_embed_text_body_is_cut_at_800_words(): void {
		$text = build_embed_text(
			'T',
			[ 'Misc' ],
			[],
			[
				self::section( '', self::words( 'a', 500 ) ),
				self::section( 'Next', self::words( 'b', 500 ) ),
			]
		);

		[ $header, $body ] = explode( "\n\n", $text, 2 );

		self::assertSame( "T\nCategory: Misc\nTags: ", $header );
		$words = preg_split( '/\s+/', trim( $body ) );
		self::assertIsArray( $words );
		self::assertCount( 800, $words );
		self::assertStringEndsWith( 'b298', $body );
		self::assertStringNotContainsString( 'b299', $body );
	}
}
