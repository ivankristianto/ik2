<?php
/**
 * Syntax highlighting for core/code. The language lives in the block comment,
 * so saved markup is unchanged; one script paints it on the front end and in
 * the editor canvas.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\CodeHighlight;

use WP_HTML_Tag_Processor;

use const IK2\Plugin\PLUGIN_DIR;
use const IK2\Plugin\PLUGIN_FILE;
use const IK2\Plugin\PLUGIN_VERSION;

defined( 'ABSPATH' ) || exit;

const VIEW_HANDLE   = 'ik2-code-highlight';
const EDITOR_HANDLE = 'ik2-code-highlight-editor';

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_filter( 'register_block_type_args', __NAMESPACE__ . '\\add_language_attribute', 10, 2 );
	add_action( 'init', __NAMESPACE__ . '\\register_scripts' );
	add_filter( 'render_block_core/code', __NAMESPACE__ . '\\render_code_block', 10, 2 );
	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_editor_script' );
	add_action( 'enqueue_block_assets', __NAMESPACE__ . '\\enqueue_canvas_script' );
}

/**
 * Language slugs shared with the editor picker (src/code-highlight/languages.json).
 *
 * @return array<int,string>
 */
function languages(): array {
	static $slugs = null;

	if ( null === $slugs ) {
		$json  = file_get_contents( PLUGIN_DIR . '/src/code-highlight/languages.json' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
		$list  = is_string( $json ) ? json_decode( $json, true ) : null;
		$slugs = is_array( $list ) ? array_column( $list, 'value' ) : [];
	}

	return $slugs;
}

/**
 * Whether a slug is one the painter has a grammar for.
 *
 * @param mixed $slug Candidate language.
 */
function is_language( mixed $slug ): bool {
	return is_string( $slug ) && in_array( $slug, languages(), true );
}

/**
 * Add the `language` attribute to core/code so the server accepts and renders it.
 *
 * @param array<string,mixed> $args Block type arguments.
 * @param string              $name Block name.
 * @return array<string,mixed>
 */
function add_language_attribute( array $args, string $name ): array {
	if ( 'core/code' === $name ) {
		$args['attributes']['language'] = [ 'type' => 'string' ];
	}

	return $args;
}

/**
 * Register the canvas/front-end painter and the editor picker from their build manifests.
 */
function register_scripts(): void {
	register_built_script( VIEW_HANDLE, 'code-highlight', [ 'strategy' => 'defer' ] );
	register_built_script( EDITOR_HANDLE, 'code-highlight-editor', [] );
}

/**
 * Register build/<name>.js with the dependencies wp-scripts extracted for it.
 *
 * @param string              $handle Script handle.
 * @param string              $name   Build entry name.
 * @param array<string,mixed> $args   Extra wp_register_script() args.
 */
function register_built_script( string $handle, string $name, array $args ): void {
	$asset_file = PLUGIN_DIR . "/build/{$name}.asset.php";

	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	wp_register_script(
		$handle,
		plugins_url( "build/{$name}.js", PLUGIN_FILE ),
		$asset['dependencies'] ?? [],
		$asset['version'] ?? PLUGIN_VERSION,
		[ 'in_footer' => true ] + $args
	);
}

/**
 * Expose the language to the painter and load it only on pages that have code.
 *
 * @param string              $block_content Rendered block HTML.
 * @param array<string,mixed> $block         Parsed block.
 */
function render_code_block( string $block_content, array $block ): string {
	$language = $block['attrs']['language'] ?? null;

	if ( ! is_language( $language ) ) {
		return $block_content;
	}

	$tags = new WP_HTML_Tag_Processor( $block_content );

	if ( ! $tags->next_tag( 'pre' ) ) {
		return $block_content;
	}

	$tags->set_attribute( 'data-language', $language );
	wp_enqueue_script( VIEW_HANDLE );

	return $tags->get_updated_html();
}

/**
 * Load the language picker in the block editor.
 */
function enqueue_editor_script(): void {
	wp_enqueue_script( EDITOR_HANDLE );
}

/**
 * Load the painter into the editor canvas. Block assets enqueued in the admin
 * land inside the iframe, so the painter gets the canvas document and its CSS.highlights.
 */
function enqueue_canvas_script(): void {
	if ( is_admin() ) {
		wp_enqueue_script( VIEW_HANDLE );
	}
}
