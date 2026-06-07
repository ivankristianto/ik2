<?php
/**
 * Setup step: trash the sample content WordPress seeds on install.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

use WP_Comment;

defined( 'ABSPATH' ) || exit;

/**
 * Trashes (never force-deletes) the content a fresh install seeds:
 * the "Hello world!" post, the "Sample Page", the draft "Privacy
 * Policy" page, and the default comment. Matching is by exact slug
 * (plus author name for the comment) so real content is never touched;
 * anything already gone reports "absent".
 */
class Sample_Content_Step implements Setup_Step {

	/**
	 * Section heading shown above this step's checks.
	 */
	public function label(): string {
		return 'Sample content';
	}

	/**
	 * Trash each piece of sample content still present.
	 *
	 * @param bool $force Unused; absent content is simply reported absent.
	 * @return array<int, Check_Result>
	 */
	public function run( bool $force ): array {
		return array(
			$this->trash_post( 'hello-world', 'post' ),
			$this->trash_post( 'sample-page', 'page' ),
			$this->trash_draft_privacy_page(),
			$this->trash_default_comment(),
		);
	}

	/**
	 * Trash a post matched by exact slug.
	 *
	 * @param string $slug Post slug to match.
	 * @param string $type Post type to match.
	 */
	private function trash_post( string $slug, string $type ): Check_Result {
		$post = get_page_by_path( $slug, OBJECT, $type );

		if ( null === $post || 'trash' === $post->post_status ) {
			return new Check_Result( $slug, true, 'absent' );
		}

		if ( ! wp_trash_post( $post->ID ) ) {
			return new Check_Result( $slug, false, 'could not trash' );
		}

		return new Check_Result( $slug, true, 'trashed' );
	}

	/**
	 * Trash the draft "Privacy Policy" page WordPress seeds on install,
	 * unless it is still the designated privacy page (the privacy-page
	 * step repoints that option to /privacy first).
	 */
	private function trash_draft_privacy_page(): Check_Result {
		$page = get_page_by_path( 'privacy-policy', OBJECT, 'page' );

		if ( null === $page || 'draft' !== $page->post_status ) {
			return new Check_Result( 'privacy-policy', true, 'absent' );
		}

		if ( (int) get_option( 'wp_page_for_privacy_policy' ) === $page->ID ) {
			return new Check_Result( 'privacy-policy', true, 'still the designated privacy page, kept' );
		}

		if ( ! wp_trash_post( $page->ID ) ) {
			return new Check_Result( 'privacy-policy', false, 'could not trash' );
		}

		return new Check_Result( 'privacy-policy', true, 'trashed' );
	}

	/**
	 * Trash the default comment, matched by ID and author name.
	 */
	private function trash_default_comment(): Check_Result {
		$comment = get_comment( 1 );

		if ( ! $comment instanceof WP_Comment || 'A WordPress Commenter' !== $comment->comment_author ) {
			return new Check_Result( 'default comment', true, 'absent' );
		}

		if ( 'trash' === $comment->comment_approved ) {
			return new Check_Result( 'default comment', true, 'already trashed' );
		}

		if ( ! wp_trash_comment( $comment ) ) {
			return new Check_Result( 'default comment', false, 'could not trash' );
		}

		return new Check_Result( 'default comment', true, 'trashed' );
	}
}
