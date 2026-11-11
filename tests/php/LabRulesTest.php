<?php
/**
 * Tests for the lab entry rules: the origin check on tool URLs and the frame height clamp.
 *
 * @package IK2\Tests
 */

declare(strict_types=1);

namespace IK2\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function IK2\Plugin\PostTypes\Lab\Rules\clamp_height;
use function IK2\Plugin\PostTypes\Lab\Rules\is_lab_url;

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
}
