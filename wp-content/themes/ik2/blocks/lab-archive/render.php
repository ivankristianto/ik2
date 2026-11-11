<?php
/**
 * Server render for ik2/lab-archive: every published lab entry as a ledger row, newest first.
 *
 * The rows borrow the project row classes, so the block lists those two style handles instead of its own CSS.
 *
 * @package IK2
 * @var array<string,mixed> $attributes
 * @var string              $content
 * @var WP_Block            $block
 */

declare(strict_types=1);

use IK2\Plugin\PostTypes\Lab;

defined( 'ABSPATH' ) || exit;

$ik2_labs = function_exists( 'IK2\\Plugin\\PostTypes\\Lab\\get_tool_data' )
	? get_posts(
		[
			'post_type'              => Lab\POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => 100,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'update_post_term_cache' => false,
		]
	)
	: [];

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	[ 'class' => 'ik-project-ledger' ]
);
?>
<div <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( empty( $ik2_labs ) ) : ?>
		<p class="ik-project__blurb"><?php esc_html_e( 'Nothing here yet.', 'ik2' ); ?></p>
	<?php else : ?>
		<div class="ik-project-list">
			<?php foreach ( $ik2_labs as $ik2_lab ) : ?>
				<article class="ik-project">
					<div class="ik-project__main">
						<h2 class="ik-project__name"><a href="<?php echo esc_url( (string) get_permalink( $ik2_lab ) ); ?>"><?php echo esc_html( get_the_title( $ik2_lab ) ); ?></a></h2>
						<?php if ( has_excerpt( $ik2_lab ) ) : ?>
							<p class="ik-project__blurb"><?php echo esc_html( get_the_excerpt( $ik2_lab ) ); ?></p>
						<?php endif; ?>
					</div>

					<div class="ik-project__side">
						<div class="ik-project__tech">
							<span><?php echo '' !== Lab\get_tool_data( $ik2_lab->ID )['url'] ? esc_html__( 'tool', 'ik2' ) : esc_html__( 'write-up', 'ik2' ); ?></span>
							<span><time datetime="<?php echo esc_attr( (string) get_the_date( 'c', $ik2_lab ) ); ?>"><?php echo esc_html( (string) get_the_date( 'Y-m-d', $ik2_lab ) ); ?></time></span>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
