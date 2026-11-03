<?php
/**
 * Tests for the "lately" card's repo parsing, freshness and ordering.
 *
 * @package IK2\Tests
 */

declare(strict_types=1);

namespace IK2\Tests;

use PHPUnit\Framework\TestCase;

use function IK2\Plugin\Activity\Rules\github_repo_from_url;
use function IK2\Plugin\Activity\Rules\is_within_days;
use function IK2\Plugin\Activity\Rules\latest_episode_from_json;
use function IK2\Plugin\Activity\Rules\merge_fetched;
use function IK2\Plugin\Activity\Rules\recent_repos;

final class ActivityRulesTest extends TestCase {

	private const NOW = 1_793_664_000; // 2026-11-03 00:00 UTC.
	private const DAY = 86400;

	public function test_repo_slug_comes_from_a_github_url(): void {
		self::assertSame( 'ivankristianto/ik2', github_repo_from_url( 'https://github.com/ivankristianto/ik2' ) );
		self::assertSame( 'ivankristianto/zsh', github_repo_from_url( ' https://www.github.com/ivankristianto/zsh.git/ ' ) );
		self::assertSame( 'ivankristianto/wp-passkey', github_repo_from_url( 'https://github.com/ivankristianto/wp-passkey/tree/main' ) );
	}

	public function test_non_repo_urls_have_no_slug(): void {
		self::assertNull( github_repo_from_url( 'https://github.com/ivankristianto' ) );
		self::assertNull( github_repo_from_url( 'https://gitlab.com/ivankristianto/ik2' ) );
		self::assertNull( github_repo_from_url( 'not a url' ) );
	}

	public function test_within_days_rejects_future_and_missing_timestamps(): void {
		self::assertTrue( is_within_days( self::NOW - 14 * self::DAY, self::NOW, 14 ) );
		self::assertFalse( is_within_days( self::NOW - 15 * self::DAY, self::NOW, 14 ) );
		self::assertFalse( is_within_days( self::NOW + 60, self::NOW, 14 ) );
		self::assertFalse( is_within_days( 0, self::NOW, 14 ) );
	}

	public function test_recent_repos_are_newest_first_capped_and_drop_stale_ones(): void {
		$repos = [
			$this->repo( 'Zsh Dotfiles', 124 ),
			$this->repo( 'Allowealth', 2 ),
			$this->repo( 'Cloudflare API CLI', 868 ),
			$this->repo( 'ivankristianto.com', 0 ),
			$this->repo( 'WP Passkey', 3 ),
		];

		$names = array_column( recent_repos( $repos, self::NOW, 3, 90 ), 'name' );

		self::assertSame( [ 'ivankristianto.com', 'Allowealth', 'WP Passkey' ], $names );
	}

	public function test_recent_repos_breaks_ties_by_name(): void {
		$names = array_column( recent_repos( [ $this->repo( 'b', 1 ), $this->repo( 'a', 1 ) ], self::NOW, 3, 90 ), 'name' );

		self::assertSame( [ 'a', 'b' ], $names );
	}

	public function test_a_failed_fetch_keeps_the_last_good_value_and_unlinked_repos_drop(): void {
		$merged = merge_fetched(
			[ 'o/kept' => 100, 'o/gone' => 200, 'o/updated' => 300 ],
			[ 'o/updated' => 400, 'o/new' => 500 ],
			[ 'o/kept', 'o/updated', 'o/new', 'o/never' ]
		);

		self::assertSame( [ 'o/kept' => 100, 'o/updated' => 400, 'o/new' => 500 ], $merged );
	}

	public function test_latest_episode_is_the_newest_by_date_with_the_show_name_trimmed(): void {
		$json = json_encode(
			[
				[ 'title' => 'Laracon - Ngobrolin WEB', 'publishedAt' => '2026-10-14T03:06:35Z', 'slug' => 'HeA7bGiTMMA-laracon-ngobrolin-web' ],
				[ 'title' => 'State of Devs - Ngobrolin WEB', 'publishedAt' => '2026-10-21T03:57:48Z', 'slug' => 'Nq3uKguMNCA-state-of-devs-ngobrolin-web' ],
				[ 'title' => 'No slug yet', 'publishedAt' => '2026-10-28T03:00:00Z' ],
			]
		);

		self::assertSame(
			[
				'title' => 'State of Devs',
				'url'   => 'https://ngobrol.in/episodes/Nq3uKguMNCA-state-of-devs-ngobrolin-web/',
				'date'  => strtotime( '2026-10-21T03:57:48Z' ),
			],
			latest_episode_from_json( (string) $json, 'https://ngobrol.in/episodes/', ' - Ngobrolin WEB' )
		);
	}

	public function test_broken_or_empty_episode_data_gives_no_episode(): void {
		self::assertNull( latest_episode_from_json( 'not json', 'https://ngobrol.in/episodes/' ) );
		self::assertNull( latest_episode_from_json( '[]', 'https://ngobrol.in/episodes/' ) );
	}

	/**
	 * @return array{name:string,url:string,pushed:int}
	 */
	private function repo( string $name, int $days_ago ): array {
		return [
			'name'   => $name,
			'url'    => 'https://github.com/o/' . $name,
			'pushed' => self::NOW - $days_ago * self::DAY,
		];
	}
}
