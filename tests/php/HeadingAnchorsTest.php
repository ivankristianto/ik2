<?php
/**
 * Tests for the heading anchors module.
 *
 * @package IK2\Tests
 */

declare(strict_types=1);

namespace IK2\Tests;

use PHPUnit\Framework\TestCase;

use function IK2\Theme\HeadingAnchors\anchor_headings;
use function IK2\Theme\HeadingAnchors\heading_slug;

final class HeadingAnchorsTest extends TestCase {

	public function test_adds_ids_to_h2_and_h3(): void {
		$html = '<h2 class="wp-block-heading">Why it broke</h2><p>x</p><h3>The fix</h3>';

		self::assertSame(
			'<h2 id="why-it-broke" class="wp-block-heading">Why it broke</h2><p>x</p><h3 id="the-fix">The fix</h3>',
			anchor_headings( $html )
		);
	}

	public function test_leaves_other_levels_alone(): void {
		$html = '<h1>Title</h1><h4>Small</h4>';

		self::assertSame( $html, anchor_headings( $html ) );
	}

	public function test_keeps_an_existing_id_and_avoids_reusing_it(): void {
		$html = '<h2 id="setup">Setup</h2><h2>Setup</h2><p id="notes">n</p><h3>Notes</h3>';

		self::assertSame(
			'<h2 id="setup">Setup</h2><h2 id="setup-2">Setup</h2><p id="notes">n</p><h3 id="notes-2">Notes</h3>',
			anchor_headings( $html )
		);
	}

	public function test_skips_headings_with_no_text(): void {
		$html = '<h2><img src="a.png" alt=""></h2>';

		self::assertSame( $html, anchor_headings( $html ) );
	}

	public function test_slug_strips_markup_and_entities(): void {
		self::assertSame( 'use-wp-cli-code-not-ftp', heading_slug( 'Use <code>WP-CLI&nbsp;code</code>, &amp; not FTP' ) );
		self::assertSame( 'step-2-run-it', heading_slug( 'Step 2: run it!' ) );
		self::assertSame( 'café-setup', heading_slug( 'Café setup' ) );
	}

	public function test_slug_is_capped_at_a_word_boundary(): void {
		$slug = heading_slug( str_repeat( 'word ', 30 ) );

		self::assertLessThanOrEqual( 60, strlen( $slug ) );
		self::assertStringEndsWith( 'word', $slug );
	}
}
