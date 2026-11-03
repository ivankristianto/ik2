<?php
/**
 * Data for the automated "lately" card: daily GitHub push times for linked
 * projects, the newest Ngobrolin WEB episode, and the latest talk. Nothing here is edited by hand.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\Activity;

use IK2\Plugin\PostTypes\Project;

defined( 'ABSPATH' ) || exit;

const CRON_HOOK  = 'ik2_refresh_github_activity';
const OPTION_KEY = 'ik2_github_activity';

// The data file ngobrol.in is built from, read straight from the public repo.
const PODCAST_DATA   = 'https://raw.githubusercontent.com/ngobrolin/landing/main/src/data/episodes.json';
const PODCAST_BASE   = 'https://ngobrol.in/episodes/';
const PODCAST_SUFFIX = ' - Ngobrolin WEB';

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_action( 'init', __NAMESPACE__ . '\\schedule_refresh' );
	add_action( CRON_HOOK, __NAMESPACE__ . '\\refresh' );
}

/**
 * Schedule the daily refresh if it is not queued yet.
 */
function schedule_refresh(): void {
	if ( ! wp_next_scheduled( CRON_HOOK ) ) {
		wp_schedule_event( time(), 'daily', CRON_HOOK );
	}
}

/**
 * Remove the daily refresh, on plugin deactivation.
 */
function unschedule_refresh(): void {
	wp_clear_scheduled_hook( CRON_HOOK );
}

/**
 * Published, non-archived projects that link a GitHub repo.
 *
 * @return array<string,array{name:string,url:string}> Keyed by "owner/repo".
 */
function linked_repos(): array {
	$ids = get_posts(
		[
			'post_type'      => Project\POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		]
	);

	$repos = [];
	foreach ( $ids as $id ) {
		$card = Project\get_card_data( (int) $id );
		if ( null === $card || 'Archived' === $card['status'] ) {
			continue;
		}

		foreach ( $card['links'] as $link ) {
			$slug = Rules\github_repo_from_url( $link['href'] );
			if ( null !== $slug ) {
				$repos[ $slug ] = [
					'name' => $card['title'],
					'url'  => $link['href'],
				];
				break;
			}
		}
	}

	return $repos;
}

/**
 * Cron callback: fetch each repo's last push from the public GitHub API and the newest podcast episode.
 */
function refresh(): void {
	$repos   = linked_repos();
	$fetched = [];

	foreach ( array_keys( $repos ) as $slug ) {
		$pushed = fetch_pushed_at( $slug );
		if ( null !== $pushed ) {
			$fetched[ $slug ] = $pushed;
		}
	}

	$stored  = get_option( OPTION_KEY, [] );
	$stored  = is_array( $stored ) ? $stored : [];
	$episode = fetch_latest_episode();

	update_option(
		OPTION_KEY,
		[
			'fetched' => time(),
			'repos'   => Rules\merge_fetched( is_array( $stored['repos'] ?? null ) ? $stored['repos'] : [], $fetched, array_keys( $repos ) ),
			'episode' => $episode ?? ( is_array( $stored['episode'] ?? null ) ? $stored['episode'] : null ),
		],
		false
	);
}

/**
 * Newest Ngobrolin WEB episode from the ngobrolin/landing repo, or null when it did not load.
 *
 * @return array{title:string,url:string,date:int}|null
 */
function fetch_latest_episode(): ?array {
	$response = wp_remote_get(
		PODCAST_DATA,
		[
			'timeout' => 10,
			'headers' => [ 'User-Agent' => 'ivankristianto.com' ],
		]
	);

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return null;
	}

	return Rules\latest_episode_from_json( wp_remote_retrieve_body( $response ), PODCAST_BASE, PODCAST_SUFFIX );
}

/**
 * Last push time for one repo, or null when GitHub did not answer cleanly.
 *
 * @param string $slug Repo as "owner/repo".
 */
function fetch_pushed_at( string $slug ): ?int {
	$response = wp_remote_get(
		'https://api.github.com/repos/' . $slug,
		[
			'timeout' => 5,
			'headers' => [
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'ivankristianto.com',
			],
		]
	);

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return null;
	}

	$body   = json_decode( wp_remote_retrieve_body( $response ), true );
	$pushed = is_array( $body ) && is_string( $body['pushed_at'] ?? null ) ? strtotime( $body['pushed_at'] ) : false;

	return false === $pushed ? null : $pushed;
}

/**
 * Projects pushed to most recently, for the card's "building" row.
 *
 * @param int $limit        Most projects to return.
 * @param int $max_age_days Oldest push to show.
 * @return array<int,array{name:string,url:string,pushed:int}>
 */
function recent_projects( int $limit = 3, int $max_age_days = 90 ): array {
	$stored = get_option( OPTION_KEY, [] );
	$pushes = is_array( $stored ) && is_array( $stored['repos'] ?? null ) ? $stored['repos'] : [];

	$rows = [];
	foreach ( linked_repos() as $slug => $repo ) {
		if ( isset( $pushes[ $slug ] ) ) {
			$rows[] = $repo + [ 'pushed' => (int) $pushes[ $slug ] ];
		}
	}

	return Rules\recent_repos( $rows, time(), $limit, $max_age_days );
}

/**
 * The newest post in the `talk` category, the same set /speaking lists, if it is recent.
 *
 * @param int $max_age_days Oldest talk to show.
 * @return array{title:string,url:string,date:int}|null
 */
function latest_talk( int $max_age_days = 365 ): ?array {
	$posts = get_posts(
		[
			'category_name'  => 'talk',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		]
	);

	if ( [] === $posts ) {
		return null;
	}

	$date = (int) get_post_timestamp( $posts[0] );

	if ( ! Rules\is_within_days( $date, time(), $max_age_days ) ) {
		return null;
	}

	return [
		'title' => get_the_title( $posts[0] ),
		'url'   => (string) get_permalink( $posts[0] ),
		'date'  => $date,
	];
}

/**
 * The newest Ngobrolin WEB episode, if it aired recently.
 *
 * @param int $max_age_days Oldest episode to show.
 * @return array{title:string,url:string,date:int}|null
 */
function latest_episode( int $max_age_days = 30 ): ?array {
	$stored  = get_option( OPTION_KEY, [] );
	$episode = is_array( $stored ) && is_array( $stored['episode'] ?? null ) ? $stored['episode'] : null;

	if ( null === $episode || ! Rules\is_within_days( (int) ( $episode['date'] ?? 0 ), time(), $max_age_days ) ) {
		return null;
	}

	return [
		'title' => (string) $episode['title'],
		'url'   => (string) $episode['url'],
		'date'  => (int) $episode['date'],
	];
}
