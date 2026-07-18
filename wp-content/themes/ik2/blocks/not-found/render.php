<?php
/**
 * Server render for ik2/not-found.
 *
 * Outputs the 404 page body so the front end and Site Editor share the same
 * render path instead of relying on a large Custom HTML block preview.
 *
 * @package IK2
 * @var array<string,mixed> $attributes
 * @var string              $content
 * @var WP_Block            $block
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ik2_request_uri = '/';

if ( isset( $_SERVER['REQUEST_URI'] ) ) {
	$ik2_uri_raw     = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
	$ik2_uri_path    = (string) wp_parse_url( $ik2_uri_raw, PHP_URL_PATH );
	$ik2_request_uri = $ik2_uri_path === '' ? '/' : $ik2_uri_path;
}

$ik2_now_utc = gmdate( 'Y-m-d H:i:s' ) . ' UTC';
$ik2_ray_id  = sprintf( '%04x-CGK', wp_rand( 0, 0xffff ) );
$ik2_host    = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );

$ik2_routes = [
	[
		'path'  => '/',
		'label' => 'Home',
		'desc'  => 'The notebook front page — latest writing, guides, and notes.',
	],
	[
		'path'  => '/articles',
		'label' => 'Articles',
		'desc'  => 'Posts on WordPress, AI, performance, security, and developer tooling.',
	],
	[
		'path'  => '/projects',
		'label' => 'Projects',
		'desc'  => 'Open-source CLIs, plugins, and side experiments worth shipping.',
	],
	[
		'path'  => '/speaking',
		'label' => 'Speaking',
		'desc'  => 'Talks at WordCamps, meetups, and Google Developer events.',
	],
	[
		'path'  => '/about',
		'label' => 'About',
		'desc'  => 'Who I am, what I work on, and what this site is for.',
	],
	[
		'path'  => '/contact',
		'label' => 'Contact',
		'desc'  => 'Email, social, and how to reach me for work or speaking.',
	],
];

$ik2_wrapper_attrs = get_block_wrapper_attributes();
?>
<div <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<section class="ik-404">
		<div class="ik-404__hero">
			<div>
				<div class="ik-404__eyebrow">
					<span>HTTP/2</span><span class="dot">·</span>
					<span>404 NOT FOUND</span><span class="dot">·</span>
					<span><?php echo esc_html( $ik2_now_utc ); ?></span>
				</div>
				<h1 class="ik-404__title">This page isn&rsquo;t in the repo.</h1>
				<p class="ik-404__blurb">
					I rewrote, renamed, or never wrote whatever you were after.
					No drama &mdash; this happens. Below is a small terminal trace,
					and a few places that <em>do</em> exist.
				</p>

				<div class="ik-404__stats" aria-label="Diagnostic">
					<span class="ik-404__stats-item"><span class="ik-404__stats-k">status</span><code class="ik-404__stats-v err">404</code></span>
					<span class="ik-404__stats-item"><span class="ik-404__stats-k">path</span><code class="ik-404__stats-v"><?php echo esc_html( $ik2_request_uri ); ?></code></span>
					<span class="ik-404__stats-item"><span class="ik-404__stats-k">cache</span><code class="ik-404__stats-v">MISS</code></span>
					<span class="ik-404__stats-item"><span class="ik-404__stats-k">server</span><code class="ik-404__stats-v">cloudflare</code></span>
					<span class="ik-404__stats-item"><span class="ik-404__stats-k">cf-ray</span><code class="ik-404__stats-v"><?php echo esc_html( $ik2_ray_id ); ?></code></span>
				</div>
			</div>

			<div class="ik-404__number" aria-hidden="true">
				<span class="stamp">err</span>
				<span class="digit">4</span>
				<span class="digit zero">0</span>
				<span class="digit">4</span>
			</div>
		</div>

		<div class="ik-term" role="region" aria-label="Path lookup trace">
			<div class="ik-term__chrome">
				<span class="ik-term__dots" aria-hidden="true"><i></i><i></i><i></i></span>
				<span class="ik-term__path"><b>ivan</b>@blog <span class="dim">·</span> ~/site</span>
			</div>

			<div class="ik-term__body">
				<div class="ik-term__history">
					<div class="ik-term__line">
						<span class="prompt">$</span> <span class="cmd">curl</span> <span class="arg">-I</span> <span class="arg">https://<?php echo esc_html( $ik2_host . $ik2_request_uri ); ?></span>
					</div>
					<div class="ik-term__line">
						<span class="dim">HTTP/2</span> <span class="err">404</span> <span class="dim">not found</span>
					</div>
					<div class="ik-term__line"><span class="key">server:</span> <span class="val">cloudflare</span></div>
					<div class="ik-term__line"><span class="key">content-type:</span> <span class="val">text/html; charset=utf-8</span></div>
					<div class="ik-term__line"><span class="key">x-suggestion:</span> <span class="val"><a href="<?php echo esc_url( home_url( '/articles' ) ); ?>">/articles</a> <span class="dim">&larr; did you mean this?</span></span></div>
					<div class="ik-term__line"><span class="dim">&mdash; try one of the cards below, or head <a href="<?php echo esc_url( home_url( '/' ) ); ?>">home</a>.</span></div>
				</div>
			</div>
		</div>

		<div>
			<div class="ik-404__section-head">
				<h2>Places that do exist</h2>
				<span class="meta"><?php echo count( $ik2_routes ); ?> routes</span>
			</div>

			<div class="ik-404-suggest">
				<?php foreach ( $ik2_routes as $ik2_route ) : ?>
					<a class="ik-404-suggest__card" href="<?php echo esc_url( home_url( $ik2_route['path'] ) ); ?>">
						<span class="ik-404-suggest__eyebrow"><span class="arrow">&rarr;</span>&nbsp;<?php echo esc_html( $ik2_route['path'] ); ?></span>
						<span class="ik-404-suggest__title"><?php echo esc_html( $ik2_route['label'] ); ?></span>
						<span class="ik-404-suggest__desc"><?php echo esc_html( $ik2_route['desc'] ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="wp-block-buttons ik-404__cta-row">
			<div class="wp-block-button">
				<a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/' ) ); ?>" style="border-radius:0.375rem">Go home</a>
			</div>
			<div class="wp-block-button is-style-outline">
				<a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/articles' ) ); ?>" style="border-radius:0.375rem">Browse articles</a>
			</div>
		</div>
	</section>
</div>
