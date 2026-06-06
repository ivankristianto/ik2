<?php
/**
 * Value object describing the outcome of a single setup check.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CLI\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Outcome of one setup check: a label, a success flag, and a short note.
 */
class Check_Result {

	/**
	 * Check label, e.g. a page slug or option name.
	 *
	 * @var string
	 */
	public string $label;

	/**
	 * Whether the check succeeded.
	 *
	 * @var bool
	 */
	public bool $success;

	/**
	 * Short human note, e.g. "created" or "already set".
	 *
	 * @var string
	 */
	public string $note;

	/**
	 * Constructor.
	 *
	 * @param string $label   Check label, e.g. a page slug or option name.
	 * @param bool   $success Whether the check succeeded.
	 * @param string $note    Short human note, e.g. "created" or "already set".
	 */
	public function __construct( string $label, bool $success, string $note ) {
		$this->label   = $label;
		$this->success = $success;
		$this->note    = $note;
	}
}
