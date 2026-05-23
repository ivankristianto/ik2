<?php
/**
 * Server render for ik2/articles-filters.
 *
 * Detects the current archive context (page-articles, category, tag,
 * or other) from the queried object, then renders two pill rows:
 *
 *   - Topic pills: plain `<a href>` links that switch archive context.
 *   - Format pills: same links plus the IAPI router action so the
 *     grid swaps in place without a full reload.
 *
 * Pretty URLs only — no query strings. URLs are generated to preserve
 * the other active dimension where possible (e.g. clicking a topic
 * pill keeps the current format segment).
 *
 * @package IK2
 * @var array<string,mixed> $attributes
 * @var string              $content
 * @var WP_Block            $block
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ik2_topics = array(
	'all'         => __( 'all', 'ik2' ),
	'wordpress'   => __( 'wordpress', 'ik2' ),
	'ai'          => __( 'ai', 'ik2' ),
	'performance' => __( 'performance', 'ik2' ),
	'security'    => __( 'security', 'ik2' ),
	'web-apis'    => __( 'web-apis', 'ik2' ),
	'tooling'     => __( 'tooling', 'ik2' ),
);

$ik2_formats = array(
	'all'        => __( 'All', 'ik2' ),
	'guide'      => __( 'Guides', 'ik2' ),
	'note'       => __( 'Notes', 'ik2' ),
	'experiment' => __( 'Experiments', 'ik2' ),
);

/**
 * Detect the current archive context.
 *
 * @return array{kind:string,topic:?string,tag:?string,format:string}
 */
$ik2_detect_context = static function (): array {
	$ctx = array(
		'kind'   => 'page',
		'topic'  => null,
		'tag'    => null,
		'format' => '',
	);

	$queried = get_queried_object();

	if ( $queried instanceof WP_Term && 'category' === $queried->taxonomy ) {
		$ctx['kind']  = 'category';
		$ctx['topic'] = $queried->slug;
	} elseif ( $queried instanceof WP_Term && 'post_tag' === $queried->taxonomy ) {
		$ctx['kind'] = 'tag';
		$ctx['tag']  = $queried->slug;
	}

	$format          = (string) get_query_var( 'format', '' );
	$allowed_formats = array(
		'guide'      => 1,
		'note'       => 1,
		'experiment' => 1,
	);
	if ( '' !== $format && array_key_exists( $format, $allowed_formats ) ) {
		$ctx['format'] = $format;
	}

	return $ctx;
};

$ik2_context = $ik2_detect_context();

/**
 * Build the pretty URL for a given (topic, format) pair from the current context.
 *
 * Rules:
 *  - topic === 'all'  → main archive (or stay on tag if context is tag and tag pill clicked).
 *  - topic === slug   → /category/{slug}/ — overrides tag context.
 *  - format suffix appended when not 'all'.
 *  - From a tag context, the "all" topic preserves the tag (no topic on tag = "all"-equivalent).
 *
 * @param array{kind:string,topic:?string,tag:?string,format:string} $context Current archive context.
 * @param string                                                     $topic   Topic slug or 'all'.
 * @param string                                                     $format  Format slug or 'all'.
 */
$ik2_build_url = static function ( array $context, string $topic, string $format ): string {
	if ( 'all' !== $topic ) {
		$base = home_url( '/category/' . rawurlencode( $topic ) . '/' );
	} elseif ( 'tag' === $context['kind'] && null !== $context['tag'] ) {
		$base = home_url( '/tag/' . rawurlencode( $context['tag'] ) . '/' );
	} else {
		$base = home_url( '/articles/' );
	}

	if ( 'all' !== $format ) {
		$base .= 'format/' . rawurlencode( $format ) . '/';
	}

	return $base;
};

$ik2_active_topic  = 'all';
$ik2_context_topic = $ik2_context['topic'];
if ( 'category' === $ik2_context['kind'] && null !== $ik2_context_topic ) {
	$ik2_active_topic = $ik2_context_topic;
}

$ik2_active_format = '' !== $ik2_context['format'] ? $ik2_context['format'] : 'all';

$ik2_show_count = ! empty( $attributes['showCount'] );

$ik2_total = 0;
$ik2_shown = 0;

if ( $ik2_show_count ) {
	$ik2_total = (int) wp_count_posts( 'post' )->publish;

	$ik2_count_args = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'no_found_rows'  => false,
		'fields'         => 'ids',
	);

	$ik2_tax_query = array();

	$ik2_topic_slug = $ik2_context['topic'];
	if ( 'category' === $ik2_context['kind'] && null !== $ik2_topic_slug ) {
		$ik2_tax_query[] = array(
			'taxonomy' => 'category',
			'field'    => 'slug',
			'terms'    => array( $ik2_topic_slug ),
		);
	}

	$ik2_tag_slug = $ik2_context['tag'];
	if ( 'tag' === $ik2_context['kind'] && null !== $ik2_tag_slug ) {
		$ik2_tax_query[] = array(
			'taxonomy' => 'post_tag',
			'field'    => 'slug',
			'terms'    => array( $ik2_tag_slug ),
		);
	}

	if ( 'all' !== $ik2_active_format ) {
		$ik2_tax_query[] = array(
			'taxonomy' => 'category',
			'field'    => 'slug',
			'terms'    => array( $ik2_active_format ),
		);
	}

	if ( count( $ik2_tax_query ) > 1 ) {
		$ik2_tax_query['relation'] = 'AND';
	}

	if ( $ik2_tax_query ) {
		$ik2_count_args['tax_query'] = $ik2_tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}

	$ik2_count_query = new WP_Query( $ik2_count_args );
	$ik2_shown       = (int) $ik2_count_query->found_posts;
	wp_reset_postdata();
}

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	array( 'class' => 'ik-articles-filters' )
);
?>
<div <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<span class="ik-articles-filters__label"><?php esc_html_e( 'filter:', 'ik2' ); ?></span>

	<div class="ik-articles-filters__group" role="group" aria-label="<?php esc_attr_e( 'Filter by topic', 'ik2' ); ?>">
		<?php foreach ( $ik2_topics as $slug => $label ) : ?>
			<?php
			$is_current = ( $slug === $ik2_active_topic );
			$href       = $ik2_build_url( $ik2_context, $slug, $ik2_active_format );
			?>
			<a
				class="ik-articles-filters__pill<?php echo $is_current ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( $href ); ?>"
				<?php echo $is_current ? 'aria-current="true"' : ''; ?>
			><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</div>

	<span class="ik-articles-filters__divider" aria-hidden="true"></span>

	<div class="ik-articles-filters__group" role="group" aria-label="<?php esc_attr_e( 'Filter by format', 'ik2' ); ?>">
		<?php foreach ( $ik2_formats as $slug => $label ) : ?>
			<?php
			$is_current = ( $slug === $ik2_active_format );
			$href       = $ik2_build_url( $ik2_context, $ik2_active_topic, $slug );
			?>
			<a
				class="ik-articles-filters__pill<?php echo $is_current ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( $href ); ?>"
				data-wp-on--click="actions.navigate"
				<?php echo $is_current ? 'aria-current="true"' : ''; ?>
			><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</div>

	<?php if ( $ik2_show_count ) : ?>
		<span class="ik-articles-filters__count">
			<?php
			printf(
				/* translators: 1: number of posts matching the current filters, 2: total posts */
				esc_html__( '%1$d of %2$d', 'ik2' ),
				(int) $ik2_shown,
				(int) $ik2_total
			);
			?>
		</span>
	<?php endif; ?>
</div>
