<?php
/**
 * Title: Resume — Skills
 * Slug: ik2/resume-skills
 * Categories: ik2-page
 * Description: Skill groups — a monospace label beside a short list of representative items, divided by hairlines.
 *
 * @package IK2
 */

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
			__( 'WordPress core contributor — 5 releases', 'ik2' ),
			__( 'Plugin author — wp-passkey, and more', 'ik2' ),
			__( 'id_ID translation editor, plugin reviewer', 'ik2' ),
		],
	],
	[
		'group' => __( 'Other', 'ik2' ),
		'items' => [
			__( 'AI in the dev loop — Claude Code, Copilot', 'ik2' ),
			__( 'Mentoring junior engineers', 'ik2' ),
		],
	],
];

?>
<!-- wp:group {"tagName":"section","className":"ik-resume__section ik-resume__section--skills","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<section class="wp-block-group ik-resume__section ik-resume__section--skills">
	<!-- wp:heading {"level":2,"className":"ik-resume__section-title"} -->
	<h2 class="wp-block-heading ik-resume__section-title">Skills</h2>
	<!-- /wp:heading -->

	<!-- wp:group {"className":"ik-resume__skills","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group ik-resume__skills">
		<?php foreach ( $ik2_skills as $ik2_skill ) : ?>
		<!-- wp:group {"tagName":"section","className":"ik-resume__skill","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
		<section class="wp-block-group ik-resume__skill">
			<!-- wp:heading {"level":3,"className":"ik-resume__skill-group"} -->
			<h3 class="wp-block-heading ik-resume__skill-group"><?php echo esc_html( $ik2_skill['group'] ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:list {"className":"ik-resume__skill-list"} -->
			<ul class="wp-block-list ik-resume__skill-list">
				<?php foreach ( $ik2_skill['items'] as $ik2_item ) : ?>
				<!-- wp:list-item -->
				<li><?php echo esc_html( $ik2_item ); ?></li>
				<!-- /wp:list-item -->
				<?php endforeach; ?>
			</ul>
			<!-- /wp:list -->
		</section>
		<!-- /wp:group -->
		<?php endforeach; ?>
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
