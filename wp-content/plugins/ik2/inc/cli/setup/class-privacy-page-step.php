<?php
/**
 * Setup step: designate /privacy as the privacy policy page.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Points wp_page_for_privacy_policy at the published /privacy page that
 * the Pages step creates. The draft "Privacy Policy" page WordPress
 * seeds on a fresh install is overridden; a different published page is
 * treated as a deliberate choice and only overridden with --force.
 */
class Privacy_Page_Step implements Setup_Step {

	/**
	 * Slug of the page the theme links to as the privacy policy.
	 */
	private const SLUG = 'privacy';

	/**
	 * Section heading shown above this step's checks.
	 */
	public function label(): string {
		return 'Privacy page';
	}

	/**
	 * Point the privacy policy option at the /privacy page.
	 *
	 * @param bool $force Override a different published page too.
	 * @return array<int, Check_Result>
	 */
	public function run( bool $force ): array {
		$page = get_page_by_path( self::SLUG, OBJECT, 'page' );

		if ( $page === null || $page->post_status !== 'publish' ) {
			return [ new Check_Result( 'wp_page_for_privacy_policy', false, 'no published /privacy page (run the pages step first)' ) ];
		}

		$current_id = (int) get_option( 'wp_page_for_privacy_policy' );

		if ( $current_id === $page->ID ) {
			return [ new Check_Result( 'wp_page_for_privacy_policy', true, 'already set' ) ];
		}

		$current = $current_id > 0 ? get_post( $current_id ) : null;

		if ( ! $force && $current instanceof WP_Post && $current->post_status === 'publish' ) {
			return [ new Check_Result( 'wp_page_for_privacy_policy', true, sprintf( 'set to page %d, skipped', $current_id ) ) ];
		}

		update_option( 'wp_page_for_privacy_policy', $page->ID );

		return [ new Check_Result( 'wp_page_for_privacy_policy', true, 'set to /' . self::SLUG ) ];
	}
}
