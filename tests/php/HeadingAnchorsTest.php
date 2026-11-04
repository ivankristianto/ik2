<?php
/**
 * Tests for the heading anchor slugs. The Tag Processor walk needs WordPress, so it isn't covered here.
 *
 * @package IK2\Tests
 */

declare(strict_types=1);

namespace IK2\Tests;

use PHPUnit\Framework\TestCase;

use function IK2\Theme\HeadingAnchors\heading_slug;
use function IK2\Theme\HeadingAnchors\unique_id;

final class HeadingAnchorsTest extends TestCase {

	public function test_slug_hyphenates_punctuation_and_spaces(): void {
		self::assertSame( 'use-wp-cli-not-ftp', heading_slug( "Use WP-CLI\u{00A0}& not FTP" ) );
		self::assertSame( 'step-2-run-it', heading_slug( 'Step 2: run it!' ) );
		self::assertSame( 'café-setup', heading_slug( 'Café setup' ) );
	}

	public function test_slug_drops_apostrophes_like_the_stored_ids(): void {
		self::assertSame( 'where-ive-used-it', heading_slug( "Where I\u{2019}ve used it" ) );
		self::assertSame( 'dont-panic', heading_slug( "Don't panic" ) );
	}

	public function test_slug_is_empty_for_text_without_letters(): void {
		self::assertSame( '', heading_slug( " \u{2014} " ) );
	}

	public function test_slug_is_capped_at_a_word_boundary(): void {
		$slug = heading_slug( str_repeat( 'word ', 30 ) );

		self::assertLessThanOrEqual( 60, strlen( $slug ) );
		self::assertStringEndsWith( 'word', $slug );
	}

	public function test_unique_id_skips_taken_ids(): void {
		self::assertSame( 'setup', unique_id( 'setup', [] ) );
		self::assertSame( 'setup-3', unique_id( 'setup', [ 'setup' => true, 'setup-2' => true ] ) );
	}
}
