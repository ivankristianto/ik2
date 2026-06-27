<?php
/**
 * Setup step: create the pages the theme templates hardcode links to.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Ensures every page the theme links to exists and is published.
 *
 * The manifest mirrors the hardcoded links in the theme's templates and
 * parts (header nav, footer meta, in-template CTAs). Existing pages are
 * skipped unless --force, which re-applies title/slug/status in place so
 * the page ID stays stable.
 */
class Pages_Step implements Setup_Step {

	/**
	 * Slug => title manifest of pages the theme templates link to.
	 */
	private const PAGES = [
		'articles' => 'Articles',
		'projects' => 'Projects',
		'speaking' => 'Speaking',
		'about'    => 'About',
		'contact'  => 'Contact',
		'resume'   => 'Resume',
		'privacy'  => 'Privacy',
	];

	/**
	 * Section heading shown above this step's checks.
	 */
	public function label(): string {
		return 'Pages';
	}

	/**
	 * Ensure each manifest page exists, one result per page.
	 *
	 * @param bool $force Re-apply title/slug/status on existing pages.
	 * @return array<int, Check_Result>
	 */
	public function run( bool $force ): array {
		$results = [];

		foreach ( self::PAGES as $slug => $title ) {
			$results[] = $this->ensure_page( $slug, $title, $force );
		}

		return $results;
	}

	/**
	 * Create, skip, or (with --force) re-apply a single page.
	 *
	 * @param string $slug  Page slug.
	 * @param string $title Page title.
	 * @param bool   $force Re-apply state on an existing page.
	 */
	private function ensure_page( string $slug, string $title, bool $force ): Check_Result {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );

		if ( $existing === null ) {
			return $this->create_page( $slug, $title );
		}

		if ( ! $force ) {
			$note = $existing->post_status === 'publish'
				? 'exists, skipped'
				: sprintf( 'exists (%s), skipped', $existing->post_status );

			return new Check_Result( $slug, true, $note );
		}

		return $this->reapply_page( $existing->ID, $slug, $title );
	}

	/**
	 * Insert a new published page with empty content (the block template
	 * renders the layout).
	 *
	 * @param string $slug  Page slug.
	 * @param string $title Page title.
	 */
	private function create_page( string $slug, string $title ): Check_Result {
		$created = wp_insert_post(
			[
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '',
			],
			true
		);

		if ( is_wp_error( $created ) ) {
			return new Check_Result( $slug, false, $created->get_error_message() );
		}

		return new Check_Result( $slug, true, 'created' );
	}

	/**
	 * Re-apply title, slug, and published status in place. The page ID
	 * stays stable so internal links and menus keep working; a trashed
	 * page is restored by forcing its status back to publish.
	 *
	 * @param int    $page_id Existing page ID.
	 * @param string $slug    Page slug.
	 * @param string $title   Page title.
	 */
	private function reapply_page( int $page_id, string $slug, string $title ): Check_Result {
		$updated = wp_update_post(
			[
				'ID'          => $page_id,
				'post_title'  => $title,
				'post_name'   => $slug,
				'post_status' => 'publish',
			],
			true
		);

		if ( is_wp_error( $updated ) ) {
			return new Check_Result( $slug, false, $updated->get_error_message() );
		}

		return new Check_Result( $slug, true, 'updated' );
	}
}
