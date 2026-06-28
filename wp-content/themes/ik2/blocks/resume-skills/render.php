<?php
/**
 * Server render for ik2/resume-skills.
 *
 * Outputs a typographic exhibit of skill groups. Each group has a monospace
 * label and a short list of representative items, divided by hairlines.
 *
 * @package IK2
 * @var array<string,mixed> $attributes
 * @var string              $content
 * @var WP_Block            $block
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ik2_skills = [
	[
		'group' => __( 'WordPress', 'ik2' ),
		'items' => [
			__( 'Core, REST API, Gutenberg blocks', 'ik2' ),
			__( 'Block themes, theme.json, Interactivity API', 'ik2' ),
			__( 'Multisite, VIP-style scale', 'ik2' ),
		],
	],
	[
		'group' => __( 'Performance', 'ik2' ),
		'items' => [
			__( 'LiteSpeed / Nginx / Varnish', 'ik2' ),
			__( 'Cloudflare edge rules', 'ik2' ),
			__( 'Core Web Vitals audits', 'ik2' ),
		],
	],
	[
		'group' => __( 'Stack', 'ik2' ),
		'items' => [
			__( 'PHP 8.x, MySQL, Redis', 'ik2' ),
			__( 'Node.js, TypeScript', 'ik2' ),
			__( 'Docker, GitHub Actions', 'ik2' ),
		],
	],
	[
		'group' => __( 'Open Source', 'ik2' ),
		'items' => [
			__( 'WordPress core contributor &mdash; 5 releases', 'ik2' ),
			__( 'Plugin author &mdash; wp-passkey, and more', 'ik2' ),
			__( 'id_ID translation editor, plugin reviewer', 'ik2' ),
		],
	],
	[
		'group' => __( 'Other', 'ik2' ),
		'items' => [
			__( 'AI in the dev loop &mdash; Claude Code, Copilot', 'ik2' ),
			__( 'Talks: WordCamp, WordPress meetups', 'ik2' ),
			__( 'Mentoring junior engineers', 'ik2' ),
		],
	],
];

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	[ 'class' => 'ik-resume__section ik-resume__section--skills' ]
);
?>
<section <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<h2 class="ik-resume__section-title">
		<span class="ik-resume__section-num" aria-hidden="true">02</span>
		<span class="ik-resume__section-name"><?php esc_html_e( 'Skills', 'ik2' ); ?></span>
	</h2>
	<div class="ik-resume__skills">
		<?php foreach ( $ik2_skills as $ik2_skill ) : ?>
			<section class="ik-resume__skill">
				<h3 class="ik-resume__skill-group"><?php echo esc_html( $ik2_skill['group'] ); ?></h3>
				<ul class="ik-resume__skill-list">
					<?php foreach ( $ik2_skill['items'] as $ik2_item ) : ?>
						<li><?php echo wp_kses( $ik2_item, [] ); ?></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endforeach; ?>
	</div>
</section>
