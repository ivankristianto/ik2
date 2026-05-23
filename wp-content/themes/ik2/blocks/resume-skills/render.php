<?php
/**
 * Server render for ik2/resume-skills.
 *
 * Outputs a grid of skill groups. Each group has a monospace label and a
 * short bullet list of representative items.
 *
 * @package IK2
 * @var array<string,mixed> $attributes
 * @var string              $content
 * @var WP_Block            $block
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ik2_skills = array(
	array(
		'group' => __( 'WordPress', 'ik2' ),
		'items' => array(
			__( 'Core, REST API, Gutenberg blocks', 'ik2' ),
			__( 'Block themes, theme.json, Interactivity API', 'ik2' ),
			__( 'Multisite, VIP-style scale', 'ik2' ),
		),
	),
	array(
		'group' => __( 'Performance', 'ik2' ),
		'items' => array(
			__( 'LiteSpeed / Nginx / Varnish', 'ik2' ),
			__( 'Cloudflare edge rules', 'ik2' ),
			__( 'Core Web Vitals audits', 'ik2' ),
		),
	),
	array(
		'group' => __( 'Stack', 'ik2' ),
		'items' => array(
			__( 'PHP 8.x, MySQL, Redis', 'ik2' ),
			__( 'Node.js, TypeScript', 'ik2' ),
			__( 'Docker, GitHub Actions', 'ik2' ),
		),
	),
	array(
		'group' => __( 'Other', 'ik2' ),
		'items' => array(
			__( 'AI in the dev loop — Claude Code, Copilot', 'ik2' ),
			__( 'Talks: WordCamp, WordPress meetups', 'ik2' ),
			__( 'Mentoring junior engineers', 'ik2' ),
		),
	),
);

$ik2_wrapper_attrs = get_block_wrapper_attributes(
	array( 'class' => 'ik-resume__section' )
);
?>
<section <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<h2 class="ik-resume__section-title"><?php esc_html_e( 'Skills', 'ik2' ); ?></h2>
	<div class="ik-resume__skills">
		<?php foreach ( $ik2_skills as $ik2_skill ) : ?>
			<div class="ik-resume__skill">
				<div class="ik-resume__skill-group"><?php echo esc_html( $ik2_skill['group'] ); ?></div>
				<ul>
					<?php foreach ( $ik2_skill['items'] as $ik2_item ) : ?>
						<li><?php echo esc_html( $ik2_item ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endforeach; ?>
	</div>
</section>
