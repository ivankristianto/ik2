<?php
/**
 * The "lately" card, built only from GitHub push dates and the latest talk; stored /now items are ignored.
 *
 * @package IK2
 * @var array<string,mixed> $attributes
 * @var string              $content
 * @var WP_Block            $block
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// The data lives in the ik2 plugin; without it there is nothing to show.
if ( ! function_exists( 'IK2\Plugin\Activity\recent_projects' ) ) {
	return;
}

$ik2_building = IK2\Plugin\Activity\recent_projects();
$ik2_talk     = IK2\Plugin\Activity\latest_talk();

if ( [] === $ik2_building && null === $ik2_talk ) {
	return;
}

$ik2_dates   = array_column( $ik2_building, 'pushed' );
$ik2_dates[] = null === $ik2_talk ? 0 : $ik2_talk['date'];
$ik2_newest  = (int) max( $ik2_dates );
$ik2_is_live = IK2\Plugin\Activity\Rules\is_within_days( $ik2_newest, time(), 14 );

$ik2_wrapper_attrs = get_block_wrapper_attributes( [ 'class' => 'ik-now' ] );
?>
<section aria-labelledby="ik-now-label" <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<p class="ik-now__head">
		<?php if ( $ik2_is_live ) : ?>
			<span class="ik-now__dot" aria-hidden="true"></span>
		<?php endif; ?>
		<span class="ik-now__label" id="ik-now-label"><?php esc_html_e( 'lately', 'ik2' ); ?></span>
		<time class="ik-now__date" datetime="<?php echo esc_attr( wp_date( 'Y-m-d', $ik2_newest ) ); ?>"><?php echo esc_html( wp_date( 'Y-m-d', $ik2_newest ) ); ?></time>
	</p>

	<?php if ( [] !== $ik2_building ) : ?>
		<div class="ik-now__group">
			<p class="ik-now__group-title"><?php esc_html_e( 'building', 'ik2' ); ?></p>
			<ul class="ik-now__list">
				<?php foreach ( $ik2_building as $ik2_repo ) : ?>
					<li>
						<a href="<?php echo esc_url( $ik2_repo['url'] ); ?>"><?php echo esc_html( $ik2_repo['name'] ); ?></a>
						<time datetime="<?php echo esc_attr( wp_date( 'Y-m-d', $ik2_repo['pushed'] ) ); ?>"><?php echo esc_html( wp_date( 'Y-m-d', $ik2_repo['pushed'] ) ); ?></time>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( null !== $ik2_talk ) : ?>
		<div class="ik-now__group">
			<p class="ik-now__group-title"><?php esc_html_e( 'speaking', 'ik2' ); ?></p>
			<ul class="ik-now__list">
				<li>
					<a href="<?php echo esc_url( $ik2_talk['url'] ); ?>"><?php echo esc_html( $ik2_talk['title'] ); ?></a>
					<time datetime="<?php echo esc_attr( wp_date( 'Y-m-d', $ik2_talk['date'] ) ); ?>"><?php echo esc_html( wp_date( 'Y-m-d', $ik2_talk['date'] ) ); ?></time>
				</li>
			</ul>
		</div>
	<?php endif; ?>

	<p class="ik-now__foot"><?php esc_html_e( 'Last GitHub push per project and my latest talk, refreshed daily.', 'ik2' ); ?></p>
</section>
