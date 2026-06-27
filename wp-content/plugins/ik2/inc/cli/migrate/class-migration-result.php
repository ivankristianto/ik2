<?php
/**
 * Value object describing the outcome of importing one legacy post.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Migrate;

defined( 'ABSPATH' ) || exit;

/**
 * Outcome of one post import: status, slug, a short note, and how many
 * media files were sideloaded or failed for it.
 */
class Migration_Result {

	/**
	 * One of: created, skipped, overwritten, failed.
	 *
	 * @var string
	 */
	public string $status;

	/**
	 * Post slug the result refers to.
	 *
	 * @var string
	 */
	public string $slug;

	/**
	 * Short human note, e.g. "already exists" or an error message.
	 *
	 * @var string
	 */
	public string $note;

	/**
	 * Number of media files sideloaded while importing this post.
	 *
	 * @var int
	 */
	public int $media_added;

	/**
	 * Number of media files that could not be sideloaded for this post.
	 *
	 * @var int
	 */
	public int $media_failed;

	/**
	 * Constructor.
	 *
	 * @param string $status       created|skipped|overwritten|failed.
	 * @param string $slug         Post slug the result refers to.
	 * @param string $note         Short human note.
	 * @param int    $media_added  Media files sideloaded for this post.
	 * @param int    $media_failed Media files that failed to sideload for this post.
	 */
	public function __construct( string $status, string $slug, string $note, int $media_added = 0, int $media_failed = 0 ) {
		$this->status       = $status;
		$this->slug         = $slug;
		$this->note         = $note;
		$this->media_added  = $media_added;
		$this->media_failed = $media_failed;
	}
}
