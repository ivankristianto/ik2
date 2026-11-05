<?php
/**
 * Tests for the email address encoder.
 *
 * @package IK2\Tests
 */

declare(strict_types=1);

namespace IK2\Tests;

use PHPUnit\Framework\TestCase;

use function IK2\Plugin\Email\Rules\encode_addresses;

final class EmailRulesTest extends TestCase {

	public function test_mailto_link_is_encoded_and_wrapped_in_email_off(): void {
		$html = '<p>Email me at <a href="mailto:hello@ivankristianto.com">hello@ivankristianto.com</a>.</p>';

		self::assertSame(
			'<p>Email me at <!--email_off--><a href="mailto:[hello@ivankristianto.com]">[hello@ivankristianto.com]</a><!--/email_off-->.</p>',
			encode_addresses( $html, $this->marker() )
		);
	}

	public function test_mailto_link_with_nested_markup_is_wrapped_whole(): void {
		$html = "<li><a class='x' HREF='MAILTO:hello@ivankristianto.com'><span>Email</span><span>hello@ivankristianto.com</span></a></li>";

		self::assertSame(
			"<li><!--email_off--><a class='x' HREF='MAILTO:[hello@ivankristianto.com]'><span>Email</span><span>[hello@ivankristianto.com]</span></a><!--/email_off--></li>",
			encode_addresses( $html, $this->marker() )
		);
	}

	public function test_other_links_are_not_wrapped(): void {
		$html = '<a href="https://github.com/ivankristianto">user@example.com</a>';

		self::assertSame( '<a href="https://github.com/ivankristianto">[user@example.com]</a>', encode_addresses( $html, $this->marker() ) );
	}

	public function test_addresses_in_code_samples_are_encoded(): void {
		$html = '<pre><code>scp file.txt user@example.co.uk:~/</code></pre>';

		self::assertSame( '<pre><code>scp file.txt [user@example.co.uk]:~/</code></pre>', encode_addresses( $html, $this->marker() ) );
	}

	public function test_script_style_and_textarea_contents_are_left_alone(): void {
		$html = '<script>var a = "x@example.com";</script><style>/* y@example.com */</style><textarea>z@example.com</textarea><p>w@example.com</p>';

		self::assertSame(
			'<script>var a = "x@example.com";</script><style>/* y@example.com */</style><textarea>z@example.com</textarea><p>[w@example.com]</p>',
			encode_addresses( $html, $this->marker() )
		);
	}

	public function test_text_without_an_address_is_unchanged(): void {
		$html = '<p>Follow @ivankristianto on GitHub, or ping user@localhost.</p>';

		self::assertSame( $html, encode_addresses( $html, $this->marker() ) );
	}

	public function test_already_encoded_address_is_not_encoded_again(): void {
		$html = '<p>hello&#64;example.com</p>';

		self::assertSame( $html, encode_addresses( $html, $this->marker() ) );
	}

	/**
	 * A visible stand-in for antispambot(), which is random.
	 *
	 * @return callable(string):string
	 */
	private function marker(): callable {
		return static fn( string $address ): string => '[' . $address . ']';
	}
}
