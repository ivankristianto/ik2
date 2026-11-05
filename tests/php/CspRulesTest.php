<?php
/**
 * Tests for the strict CSP policy builder.
 *
 * @package IK2\Tests
 */

declare(strict_types=1);

namespace IK2\Tests;

use PHPUnit\Framework\TestCase;

use function IK2\Plugin\Csp\Rules\build_policy;
use function IK2\Plugin\Csp\Rules\with_nonce;

final class CspRulesTest extends TestCase {

	private const NONCE = 'q5bU0r3x+Zk/7Yt1nA2mGw==';

	public function test_report_only_and_enforce_use_their_own_header_names(): void {
		self::assertSame( 'Content-Security-Policy-Report-Only', build_policy( self::NONCE, false )['name'] );
		self::assertSame( 'Content-Security-Policy', build_policy( self::NONCE, true )['name'] );
	}

	public function test_both_modes_send_the_same_policy(): void {
		self::assertSame( build_policy( self::NONCE, false )['value'], build_policy( self::NONCE, true )['value'] );
	}

	public function test_nonce_appears_once_and_only_in_script_src(): void {
		$value = build_policy( self::NONCE, false )['value'];

		self::assertSame( 1, substr_count( $value, self::NONCE ) );
		self::assertSame(
			"script-src 'nonce-" . self::NONCE . "' 'strict-dynamic' 'wasm-unsafe-eval' https: 'unsafe-inline'",
			$this->directives( $value )['script-src']
		);
	}

	public function test_policy_closes_objects_base_and_framing(): void {
		$directives = $this->directives( build_policy( self::NONCE, true )['value'] );

		self::assertSame( "object-src 'none'", $directives['object-src'] );
		self::assertSame( "base-uri 'none'", $directives['base-uri'] );
		self::assertSame( "frame-ancestors 'self'", $directives['frame-ancestors'] );
		self::assertCount( 4, $directives );
	}

	public function test_nonce_that_could_break_the_header_is_refused(): void {
		$this->expectException( \InvalidArgumentException::class );
		build_policy( "abc'; script-src *", false );
	}

	public function test_empty_nonce_is_refused(): void {
		$this->expectException( \InvalidArgumentException::class );
		build_policy( '', true );
	}

	public function test_nonce_is_added_and_other_attributes_kept(): void {
		self::assertSame(
			[
				'src'   => 'https://example.com/a.js',
				'defer' => true,
				'nonce' => self::NONCE,
			],
			with_nonce(
				[
					'src'   => 'https://example.com/a.js',
					'defer' => true,
				],
				self::NONCE
			)
		);
	}

	/**
	 * Directives keyed by name.
	 *
	 * @return array<string,string>
	 */
	private function directives( string $value ): array {
		$out = [];
		foreach ( explode( '; ', $value ) as $directive ) {
			$out[ strtok( $directive, ' ' ) ] = $directive;
		}
		return $out;
	}
}
