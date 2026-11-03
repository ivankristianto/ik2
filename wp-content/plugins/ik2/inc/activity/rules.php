<?php
/**
 * Pure helpers for the automated "lately" card. No WordPress, unit-tested.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\Activity\Rules;

const DAY_IN_SECONDS = 86400;

/**
 * The "owner/repo" slug of a GitHub repository URL, or null for anything else.
 *
 * @param string $url A project link such as https://github.com/ivankristianto/ik2.
 */
function github_repo_from_url( string $url ): ?string {
	if ( 1 !== preg_match( '#^https?://(?:www\.)?github\.com/([^/\s?\#]+)/([^/\s?\#]+)#i', trim( $url ), $m ) ) {
		return null;
	}

	return $m[1] . '/' . preg_replace( '/\.git$/', '', $m[2] );
}

/**
 * True when $timestamp is set, not in the future, and at most $days old.
 *
 * @param int $timestamp Unix time to check.
 * @param int $now       Current Unix time.
 * @param int $days      Maximum age in days.
 */
function is_within_days( int $timestamp, int $now, int $days ): bool {
	return $timestamp > 0 && $timestamp <= $now && ( $now - $timestamp ) <= $days * DAY_IN_SECONDS;
}

/**
 * The most recently pushed repos, newest first, dropping any older than $max_age_days.
 *
 * @param array<int,array{name:string,url:string,pushed:int}> $repos        Candidate repos.
 * @param int                                                 $now          Current Unix time.
 * @param int                                                 $limit        Most rows to return.
 * @param int                                                 $max_age_days Oldest push to keep.
 * @return array<int,array{name:string,url:string,pushed:int}>
 */
function recent_repos( array $repos, int $now, int $limit, int $max_age_days ): array {
	$fresh = [];

	foreach ( $repos as $repo ) {
		if ( is_within_days( (int) $repo['pushed'], $now, $max_age_days ) ) {
			$fresh[] = $repo;
		}
	}

	usort( $fresh, __NAMESPACE__ . '\\compare_newest_first' );

	return array_slice( $fresh, 0, max( 0, $limit ) );
}

/**
 * Sort callback: later push first, then name so ties stay stable.
 *
 * @param array{name:string,pushed:int} $a First repo.
 * @param array{name:string,pushed:int} $b Second repo.
 */
function compare_newest_first( array $a, array $b ): int {
	return [ $b['pushed'], $a['name'] ] <=> [ $a['pushed'], $b['name'] ];
}

/**
 * Push times to store: fresh where the fetch worked, the last good value where
 * it failed (so one bad API call never empties the card), and nothing for repos
 * no project links to any more.
 *
 * @param array<string,int> $previous "owner/repo" => pushed timestamp.
 * @param array<string,int> $fetched  Same shape; failed repos are absent.
 * @param array<int,string> $wanted   Repos that still belong to a live project.
 * @return array<string,int>
 */
function merge_fetched( array $previous, array $fetched, array $wanted ): array {
	$out = [];

	foreach ( $wanted as $slug ) {
		if ( isset( $fetched[ $slug ] ) ) {
			$out[ $slug ] = $fetched[ $slug ];
		} elseif ( isset( $previous[ $slug ] ) ) {
			$out[ $slug ] = $previous[ $slug ];
		}
	}

	return $out;
}

/**
 * The newest episode in ngobrolin/landing's episodes.json, with the show name trimmed from its title.
 *
 * @param string $json   Raw episodes.json: a list of { title, publishedAt, slug, ... }.
 * @param string $base   Episode URL prefix, such as https://ngobrol.in/episodes/.
 * @param string $suffix Trailing text to drop from titles, such as " - Ngobrolin WEB".
 * @return array{title:string,url:string,date:int}|null
 */
function latest_episode_from_json( string $json, string $base, string $suffix = '' ): ?array {
	$episodes = json_decode( $json, true );

	if ( ! is_array( $episodes ) ) {
		return null;
	}

	$newest = null;
	foreach ( $episodes as $episode ) {
		if ( ! is_array( $episode ) ) {
			continue;
		}

		$date  = strtotime( (string) ( $episode['publishedAt'] ?? '' ) );
		$title = trim( (string) ( $episode['title'] ?? '' ) );
		$slug  = trim( (string) ( $episode['slug'] ?? '' ) );

		if ( false === $date || '' === $title || '' === $slug ) {
			continue;
		}

		if ( null === $newest || $date > $newest['date'] ) {
			$newest = [
				'title' => strip_suffix( $title, $suffix ),
				'url'   => $base . rawurlencode( $slug ) . '/',
				'date'  => $date,
			];
		}
	}

	return $newest;
}

/**
 * Drop $suffix from the end of $text, ignoring case, if it is there.
 *
 * @param string $text   Text to trim.
 * @param string $suffix Ending to remove.
 */
function strip_suffix( string $text, string $suffix ): string {
	if ( '' === $suffix || 0 !== strcasecmp( substr( $text, -strlen( $suffix ) ), $suffix ) ) {
		return $text;
	}

	return rtrim( substr( $text, 0, -strlen( $suffix ) ) );
}
