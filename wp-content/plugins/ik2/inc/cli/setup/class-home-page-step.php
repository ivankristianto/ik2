<?php
/**
 * Setup step: provision the static Home front page.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

use WP_Block_Patterns_Registry;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Creates a published "Home" page seeded from the theme's ik2/home-page
 * block pattern and points show_on_front / page_on_front at it, so the
 * homepage sections are editable per page instead of hardcoded in the
 * front-page.html template. An existing page's content is never touched:
 * it is editorial. A page_on_front pointing at a different published
 * page fails the check (override with --force), and show_on_front is
 * only flipped to "page" once page_on_front actually points at the
 * Home page — a bare run must never redirect the homepage to a page
 * this step did not choose.
 */
class Home_Page_Step implements Setup_Step {

	/**
	 * Slug of the static front page.
	 */
	private const SLUG = 'home';

	/**
	 * Pattern whose content seeds a newly created Home page.
	 */
	private const PATTERN = 'ik2/home-page';

	/**
	 * Section heading shown above this step's checks.
	 */
	public function label(): string {
		return 'Home page';
	}

	/**
	 * Ensure the Home page exists and the front-page options point at it.
	 *
	 * @param bool $force Publish a non-published Home page and override a
	 *                    page_on_front pointing at a different published page.
	 * @return array<int, Check_Result>
	 */
	public function run( bool $force ): array {
		$results = array();
		$page    = get_page_by_path( self::SLUG, OBJECT, 'page' );

		if ( $page instanceof WP_Post ) {
			$result = $this->ensure_published( $page, $force );

			if ( ! $result->success ) {
				return array( $result );
			}

			$results[] = $result;
			$home_id   = $page->ID;
		} else {
			$content = $this->pattern_content();

			if ( null === $content ) {
				return array( new Check_Result( self::SLUG, false, sprintf( 'pattern %s not found (is the ik2 theme installed?)', self::PATTERN ) ) );
			}

			$home_id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => 'Home',
					'post_name'    => self::SLUG,
					'post_content' => $content,
				),
				true
			);

			if ( is_wp_error( $home_id ) ) {
				return array( new Check_Result( self::SLUG, false, $home_id->get_error_message() ) );
			}

			$results[] = new Check_Result( self::SLUG, true, 'created' );
		}

		$results[] = $this->converge_page_on_front( (int) $home_id, $force );

		if ( (int) get_option( 'page_on_front' ) === (int) $home_id ) {
			$results[] = $this->converge_show_on_front();
		}

		return $results;
	}

	/**
	 * Report an existing Home page, publishing it when forced.
	 *
	 * @param WP_Post $page  The existing Home page.
	 * @param bool    $force Publish a non-published page instead of failing.
	 */
	private function ensure_published( WP_Post $page, bool $force ): Check_Result {
		if ( 'publish' === $page->post_status ) {
			return new Check_Result( self::SLUG, true, 'exists, content untouched' );
		}

		if ( ! $force ) {
			return new Check_Result( self::SLUG, false, sprintf( 'exists with status "%s" — publish it or re-run with --force', $page->post_status ) );
		}

		$updated = wp_update_post(
			array(
				'ID'          => $page->ID,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			return new Check_Result( self::SLUG, false, $updated->get_error_message() );
		}

		return new Check_Result( self::SLUG, true, 'published' );
	}

	/**
	 * Converge show_on_front to "page".
	 */
	private function converge_show_on_front(): Check_Result {
		if ( 'page' === get_option( 'show_on_front' ) ) {
			return new Check_Result( 'show_on_front', true, 'already set' );
		}

		update_option( 'show_on_front', 'page' );

		return new Check_Result( 'show_on_front', true, 'set to "page"' );
	}

	/**
	 * Converge page_on_front to the Home page ID.
	 *
	 * @param int  $home_id The Home page ID.
	 * @param bool $force   Override a different published page too.
	 */
	private function converge_page_on_front( int $home_id, bool $force ): Check_Result {
		$current_id = (int) get_option( 'page_on_front' );

		if ( $current_id === $home_id ) {
			return new Check_Result( 'page_on_front', true, 'already set' );
		}

		$current = $current_id > 0 ? get_post( $current_id ) : null;

		if ( ! $force && $current instanceof WP_Post && 'publish' === $current->post_status ) {
			return new Check_Result( 'page_on_front', false, sprintf( 'points to page %d — re-run with --force to override', $current_id ) );
		}

		update_option( 'page_on_front', $home_id );

		return new Check_Result( 'page_on_front', true, 'set to /' . self::SLUG );
	}

	/**
	 * Content of the ik2/home-page pattern.
	 *
	 * Prefers the block pattern registry. Falls back to rendering the
	 * theme's pattern file directly: when Theme_Step activates the theme
	 * earlier in this same process, _register_theme_block_patterns() has
	 * already run at init against the previous theme, so the registry
	 * does not know ik2 patterns yet.
	 *
	 * @return string|null Pattern markup, or null when unavailable.
	 */
	private function pattern_content(): ?string {
		$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( self::PATTERN );

		if ( is_array( $pattern ) && isset( $pattern['content'] ) && '' !== trim( (string) $pattern['content'] ) ) {
			return trim( (string) $pattern['content'] );
		}

		$file = get_stylesheet_directory() . '/patterns/home-page.php';

		if ( ! is_readable( $file ) ) {
			return null;
		}

		ob_start();
		include $file;
		$content = trim( (string) ob_get_clean() );

		return '' !== $content ? $content : null;
	}
}
