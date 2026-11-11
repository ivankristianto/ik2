<?php
/**
 * Server render for ik2/lab-archive: every published lab entry as a row, newest first.
 *
 * A row is the entry's name, blurb, kind and date, beside a specimen of what the tool gives back.
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
	[ 'class' => 'ik-lab-ledger' ]
);
?>
<div <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( empty( $ik2_labs ) ) : ?>
		<p class="ik-lab-ledger__empty"><?php esc_html_e( 'Nothing here yet.', 'ik2' ); ?></p>
	<?php else : ?>
		<p class="ik-lab-ledger__head">
			<span>
			<?php
			/* translators: %d: number of lab entries. */
			echo esc_html( sprintf( _n( '%d entry', '%d entries', count( $ik2_labs ), 'ik2' ), count( $ik2_labs ) ) );
			?>
			</span>
			<span><?php esc_html_e( 'last', 'ik2' ); ?> <time datetime="<?php echo esc_attr( (string) get_the_date( 'c', $ik2_labs[0] ) ); ?>"><?php echo esc_html( (string) get_the_date( 'Y-m-d', $ik2_labs[0] ) ); ?></time></span>
		</p>

		<ol class="ik-lab-list">
			<?php foreach ( $ik2_labs as $ik2_lab ) : ?>
				<?php $ik2_specimen = Lab\get_specimen( $ik2_lab->ID ); ?>
				<li class="ik-lab-row">
					<div class="ik-lab-row__main">
						<h2 class="ik-lab-row__name"><a href="<?php echo esc_url( (string) get_permalink( $ik2_lab ) ); ?>"><?php echo esc_html( get_the_title( $ik2_lab ) ); ?></a></h2>
						<?php if ( has_excerpt( $ik2_lab ) ) : ?>
							<p class="ik-lab-row__blurb"><?php echo esc_html( get_the_excerpt( $ik2_lab ) ); ?></p>
						<?php endif; ?>
						<p class="ik-lab-row__meta">
							<span><?php echo '' !== Lab\get_tool_data( $ik2_lab->ID )['url'] ? esc_html__( 'tool', 'ik2' ) : esc_html__( 'write-up', 'ik2' ); ?></span>
							<time datetime="<?php echo esc_attr( (string) get_the_date( 'c', $ik2_lab ) ); ?>"><?php echo esc_html( (string) get_the_date( 'Y-m-d', $ik2_lab ) ); ?></time>
						</p>
					</div>

					<?php if ( ! empty( $ik2_specimen ) ) : ?>
						<p class="ik-lab-row__specimen">
							<?php foreach ( $ik2_specimen as $ik2_line ) : ?>
								<span><?php echo esc_html( $ik2_line ); ?></span>
							<?php endforeach; ?>
						</p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php endif; ?>
</div>
