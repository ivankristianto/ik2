<?php
/**
 * Tests for the lab entry rules: the origin check on tool URLs, the frame height clamp, the bar address and the specimen.
 *
 * @package IK2\Tests
 */

declare(strict_types=1);

namespace IK2\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function IK2\Plugin\PostTypes\Lab\Rules\clamp_height;
use function IK2\Plugin\PostTypes\Lab\Rules\display_address;
use function IK2\Plugin\PostTypes\Lab\Rules\is_lab_url;
use function IK2\Plugin\PostTypes\Lab\Rules\specimen_lines;

final class LabRulesTest extends TestCase {

	private const ORIGIN = 'https://lab.ivankristianto.com';

	/**
	 * @return array<string, array{string, bool}>
	 */
	public static function urls(): array {
		return [
			'tool path'                => [ 'https://lab.ivankristianto.com/font-split/', true ],
			'bare origin'              => [ 'https://lab.ivankristianto.com', true ],
			'query and fragment'       => [ 'https://lab.ivankristianto.com/embed-preview/?url=a#top', true ],
			'explicit default port'    => [ 'https://lab.ivankristianto.com:443/font-split/', true ],
			'host in another case'     => [ 'https://LAB.ivankristianto.com/font-split/', true ],
			'plain http'               => [ 'http://lab.ivankristianto.com/font-split/', false ],
			'another port'             => [ 'https://lab.ivankristianto.com:8443/font-split/', false ],
			'lookalike suffix'         => [ 'https://lab.ivankristianto.com.evil.test/', false ],
			'lookalike prefix'         => [ 'https://evil-lab.ivankristianto.com/', false ],
			'main site'                => [ 'https://www.ivankristianto.com/lab/', false ],
			'userinfo'                 => [ 'https://lab.ivankristianto.com@evil.test/', false ],
			'backslash before host'    => [ 'https://lab.ivankristianto.com\\@evil.test/', false ],
			'protocol relative'        => [ '//lab.ivankristianto.com/font-split/', false ],
			'javascript scheme'        => [ 'javascript:alert(1)//lab.ivankristianto.com/', false ],
			'whitespace in the middle' => [ "https://lab.ivankristianto.com/a\nb", false ],
			'trailing newline'         => [ "https://lab.ivankristianto.com/font-split/\n", false ],
			'empty'                    => [ '', false ],
		];
	}

	#[DataProvider( 'urls' )]
	public function test_only_urls_on_the_lab_origin_pass( string $url, bool $expected ): void {
		self::assertSame( $expected, is_lab_url( $url, self::ORIGIN ) );
	}

	public function test_a_local_http_origin_matches_itself_only(): void {
		self::assertTrue( is_lab_url( 'http://localhost:8787/stub/', 'http://localhost:8787' ) );
		self::assertFalse( is_lab_url( 'http://localhost:8788/stub/', 'http://localhost:8787' ) );
		self::assertFalse( is_lab_url( 'https://lab.ivankristianto.com/', 'http://localhost:8787' ) );
	}

	public function test_a_broken_origin_setting_matches_nothing(): void {
		self::assertFalse( is_lab_url( 'https://lab.ivankristianto.com/', '' ) );
		self::assertFalse( is_lab_url( '', '' ) );
	}

	public function test_height_is_clamped_and_unset_gives_the_default(): void {
		self::assertSame( 480, clamp_height( 0 ) );
		self::assertSame( 480, clamp_height( -20 ) );
		self::assertSame( 160, clamp_height( 40 ) );
		self::assertSame( 720, clamp_height( 720 ) );
		self::assertSame( 4000, clamp_height( 90000 ) );
	}

	public function test_the_bar_address_drops_scheme_query_and_trailing_slash(): void {
		self::assertSame( 'lab.ivankristianto.com/font-split', display_address( 'https://lab.ivankristianto.com/font-split/' ) );
		self::assertSame( 'lab.ivankristianto.com/embed-preview', display_address( 'https://lab.ivankristianto.com/embed-preview/?url=a#top' ) );
		self::assertSame( 'localhost:8787', display_address( 'http://localhost:8787/' ) );
	}

	public function test_specimen_keeps_four_trimmed_lines_and_drops_blanks(): void {
		self::assertSame(
			[ 'Café Łódź 北京', 'inter-latin 13', 'latin-ext 2', 'fallback 2' ],
			specimen_lines( "  Café Łódź 北京 \r\n\ninter-latin 13\nlatin-ext 2\n\nfallback 2\nfifth line" )
		);
		self::assertSame( [], specimen_lines( " \n " ) );
	}

	public function test_a_long_specimen_line_is_cut_by_characters_not_bytes(): void {
		$lines = specimen_lines( str_repeat( '北', 100 ) );

		self::assertSame( 64, mb_strlen( $lines[0] ) );
	}
}
