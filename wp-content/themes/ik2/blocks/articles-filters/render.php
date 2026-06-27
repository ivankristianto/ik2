<?php
/**
 * Server render for ik2/articles-filters.
 *
 * Detects the current archive context (page-articles, category, tag,
 * or other) from the queried object, then renders two pill rows:
 *
 *   - Topic pills: IAPI router links that switch archive context.
 *   - Format pills: same router navigation while preserving topic/tag context.
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

use function IK2\Theme\Blocks\ArticlesFilters\build_url;
use function IK2\Theme\Blocks\ArticlesFilters\detect_context;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/helpers.php';

$ik2_topics = [
	'all'         => __( 'all', 'ik2' ),
	'wordpress'   => __( 'wordpress', 'ik2' ),
	'ai'          => __( 'ai', 'ik2' ),
	'performance' => __( 'performance', 'ik2' ),
	'security'    => __( 'security', 'ik2' ),
	'web-apis'    => __( 'web-apis', 'ik2' ),
	'tooling'     => __( 'tooling', 'ik2' ),
];

$ik2_formats = [
	'all'        => __( 'All', 'ik2' ),
	'guide'      => __( 'Guides', 'ik2' ),
	'note'       => __( 'Notes', 'ik2' ),
	'experiment' => __( 'Experiments', 'ik2' ),
];

$ik2_context = detect_context();

$ik2_active_topic  = 'all';
$ik2_context_topic = $ik2_context['topic'];
if ( $ik2_context['kind'] === 'category' && $ik2_context_topic !== null ) {
	$ik2_active_topic = $ik2_context_topic;
}

$ik2_active_format = $ik2_context['format'] !== '' ? $ik2_context['format'] : 'all';

$ik2_show_count = ! empty( $attributes['showCount'] );

$ik2_total = 0;
$ik2_shown = 0;

if ( $ik2_show_count ) {
	$ik2_total = (int) wp_count_posts( 'post' )->publish;

	$ik2_count_args = [
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'no_found_rows'  => false,
		'fields'         => 'ids',
	];

	$ik2_tax_query = [];

	$ik2_topic_slug = $ik2_context['topic'];
	if ( $ik2_context['kind'] === 'category' && $ik2_topic_slug !== null ) {
		$ik2_tax_query[] = [
			'taxonomy' => 'category',
			'field'    => 'slug',
			'terms'    => [ $ik2_topic_slug ],
		];
	}

	$ik2_tag_slug = $ik2_context['tag'];
	if ( $ik2_context['kind'] === 'tag' && $ik2_tag_slug !== null ) {
		$ik2_tax_query[] = [
			'taxonomy' => 'post_tag',
			'field'    => 'slug',
			'terms'    => [ $ik2_tag_slug ],
		];
	}

	if ( $ik2_active_format !== 'all' ) {
		$ik2_tax_query[] = [
			'taxonomy' => 'category',
			'field'    => 'slug',
			'terms'    => [ $ik2_active_format ],
		];
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
	[ 'class' => 'ik-articles-filters' ]
);
?>
<div <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<span class="ik-articles-filters__label"><?php esc_html_e( 'filter:', 'ik2' ); ?></span>

	<div class="ik-articles-filters__group" role="group" aria-label="<?php esc_attr_e( 'Filter by topic', 'ik2' ); ?>">
		<?php foreach ( $ik2_topics as $slug => $label ) : ?>
			<?php
			$is_current = ( $slug === $ik2_active_topic );
			$href       = build_url( $ik2_context, $slug, $ik2_active_format );
			?>
			<a
				class="ik-articles-filters__pill<?php echo $is_current ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( $href ); ?>"
				data-wp-on--click="actions.navigate"
				<?php echo $is_current ? 'aria-current="true"' : ''; ?>
			><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</div>

	<span class="ik-articles-filters__divider" aria-hidden="true"></span>

	<div class="ik-articles-filters__group" role="group" aria-label="<?php esc_attr_e( 'Filter by format', 'ik2' ); ?>">
		<?php foreach ( $ik2_formats as $slug => $label ) : ?>
			<?php
			$is_current = ( $slug === $ik2_active_format );
			$href       = build_url( $ik2_context, $ik2_active_topic, $slug );
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
		<span class="ik-articles-filters__count" role="status" aria-live="polite" aria-atomic="true">
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
