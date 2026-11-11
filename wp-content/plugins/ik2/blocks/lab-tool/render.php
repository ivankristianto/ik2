<?php
/**
 * Server render for ik2/lab-tool: the current lab entry's tool in a sandboxed, cross-origin iframe.
 *
 * The tool renders untrusted input, so it stays on the lab origin and gets no top navigation.
 * Links inside it open new tabs (allow-popups), which must not inherit the sandbox.
 *
 * @package IK2\Plugin
 * @var array<string,mixed> $attributes
 * @var string              $content
 * @var WP_Block            $block
 */

declare(strict_types=1);

use IK2\Plugin\PostTypes\Lab;

defined( 'ABSPATH' ) || exit;

$ik2_lab_id = (int) ( $block->context['postId'] ?? get_the_ID() );

if ( $ik2_lab_id <= 0 || Lab\POST_TYPE !== get_post_type( $ik2_lab_id ) ) {
	return;
}

$ik2_tool = Lab\get_tool_data( $ik2_lab_id );

if ( '' === $ik2_tool['url'] ) {
	return;
}

wp_enqueue_script( Lab\VIEW_HANDLE );

$ik2_wrapper = get_block_wrapper_attributes(
	[
		'class'           => 'ik-lab-tool',
		'data-lab-origin' => Lab\lab_origin(),
		'style'           => sprintf( '--ik-lab-tool-height:%dpx', $ik2_tool['min_height'] ),
	]
);
?>
<div <?php echo $ik2_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by get_block_wrapper_attributes(). ?>>
	<iframe
		class="ik-lab-tool__frame"
		src="<?php echo esc_url( $ik2_tool['url'] ); ?>"
		title="<?php echo esc_attr( get_the_title( $ik2_lab_id ) ); ?>"
		loading="lazy"
		sandbox="allow-scripts allow-forms allow-same-origin allow-downloads allow-popups allow-popups-to-escape-sandbox"
		allow="clipboard-write"
		referrerpolicy="strict-origin-when-cross-origin"></iframe>
	<p class="ik-lab-tool__open">
		<a href="<?php echo esc_url( $ik2_tool['url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open the tool in a new tab', 'ik2' ); ?></a>
	</p>
</div>
