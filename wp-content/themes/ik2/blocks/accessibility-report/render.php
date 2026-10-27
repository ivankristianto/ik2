<?php
/**
 * Server render for ik2/accessibility-report.
 *
 * @package IK2
 * @var array<string,mixed> $attributes
 * @var string              $content
 * @var WP_Block            $block
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$ik2_report = require __DIR__ . '/report.php';

$ik2_labels = [
	'pass'     => __( 'Pass', 'ik2' ),
	'fail'     => __( 'Fail', 'ik2' ),
	'untested' => __( 'Not tested', 'ik2' ),
	'na'       => __( 'N/A', 'ik2' ),
];

$ik2_legend = [
	'pass'     => __( 'The site meets the criterion.', 'ik2' ),
	'fail'     => __( 'Something on the site does not meet it. It stays Fail until the fix is live.', 'ik2' ),
	'untested' => __( 'Not checked yet, or only partly. It does not count as a pass.', 'ik2' ),
	'na'       => __( 'Nothing on the site is covered by it. WCAG counts this as met.', 'ik2' ),
];

$ik2_counts = array_fill_keys( array_keys( $ik2_labels ), 0 );
$ik2_total  = 0;

foreach ( $ik2_report['principles'] as $ik2_principle ) {
	foreach ( $ik2_principle['criteria'] as $ik2_row ) {
		++$ik2_counts[ $ik2_row[3] ];
		++$ik2_total;
	}
}

$ik2_breakdown = sprintf(
	/* translators: 1: pass count, 2: fail count, 3: not tested count, 4: n/a count, 5: total criteria */
	__( 'Pass %1$d, Fail %2$d, Not tested %3$d, N/A %4$d (of %5$d criteria)', 'ik2' ),
	$ik2_counts['pass'],
	$ik2_counts['fail'],
	$ik2_counts['untested'],
	$ik2_counts['na'],
	$ik2_total
);

$ik2_tested_ts = strtotime( $ik2_report['tested'] );

$ik2_wrapper_attrs = get_block_wrapper_attributes( [ 'class' => 'ik-a11y' ] );
?>
<div <?php echo $ik2_wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<p class="ik-a11y__lede"><?php esc_html_e( 'I test this site against WCAG 2.2 Level AA and keep every result here, including the criteria it does not meet yet.', 'ik2' ); ?></p>

	<nav class="ik-a11y__toc" aria-label="<?php esc_attr_e( 'On this page', 'ik2' ); ?>">
		<ul>
			<li><a href="#results"><?php esc_html_e( 'Results', 'ik2' ); ?></a></li>
			<li><a href="#criteria"><?php esc_html_e( 'Success criteria', 'ik2' ); ?></a></li>
			<li><a href="#next-steps"><?php esc_html_e( 'Next steps', 'ik2' ); ?></a></li>
		</ul>
	</nav>

	<h2 id="results"><?php esc_html_e( 'Results', 'ik2' ); ?></h2>
	<table class="ik-a11y__summary">
		<tbody>
			<?php foreach ( array_slice( $ik2_report['summary'], 0, 4 ) as $ik2_item ) : ?>
				<tr><th scope="row"><?php echo esc_html( $ik2_item[0] ); ?></th><td><?php echo esc_html( $ik2_item[1] ); ?></td></tr>
			<?php endforeach; ?>
			<tr><th scope="row"><?php esc_html_e( 'Breakdown', 'ik2' ); ?></th><td><?php echo esc_html( $ik2_breakdown ); ?></td></tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Test date', 'ik2' ); ?></th>
				<td><time class="ik-a11y__date" datetime="<?php echo esc_attr( $ik2_report['tested'] ); ?>"><?php echo esc_html( $ik2_tested_ts ? wp_date( (string) get_option( 'date_format' ), $ik2_tested_ts, new DateTimeZone( 'UTC' ) ) : $ik2_report['tested'] ); ?></time></td>
			</tr>
			<?php foreach ( array_slice( $ik2_report['summary'], 4 ) as $ik2_item ) : ?>
				<tr><th scope="row"><?php echo esc_html( $ik2_item[0] ); ?></th><td><?php echo esc_html( $ik2_item[1] ); ?></td></tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<h2 id="criteria"><?php esc_html_e( 'Success criteria', 'ik2' ); ?></h2>
	<p>
		<?php
		printf(
			/* translators: %d: number of criteria */
			esc_html__( 'All %d Level A and AA success criteria in WCAG 2.2, grouped by principle. Each result is one of:', 'ik2' ),
			(int) $ik2_total
		);
		?>
	</p>
	<dl class="ik-a11y__legend">
		<?php foreach ( $ik2_legend as $ik2_status => $ik2_meaning ) : ?>
			<div>
				<dt><span class="ik-a11y__status ik-a11y__status--<?php echo esc_attr( $ik2_status ); ?>"><?php echo esc_html( $ik2_labels[ $ik2_status ] ); ?></span></dt>
				<dd><?php echo esc_html( $ik2_meaning ); ?></dd>
			</div>
		<?php endforeach; ?>
	</dl>

	<?php foreach ( $ik2_report['principles'] as $ik2_principle ) : ?>
		<h3 id="<?php echo esc_attr( $ik2_principle['id'] ); ?>"><?php echo esc_html( $ik2_principle['title'] ); ?></h3>
		<table class="ik-a11y__table" aria-labelledby="<?php echo esc_attr( $ik2_principle['id'] ); ?>">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Criterion', 'ik2' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Result and notes', 'ik2' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $ik2_principle['criteria'] as $ik2_row ) : ?>
					<tr>
						<th scope="row"><span class="ik-a11y__sc"><?php echo esc_html( $ik2_row[0] . ' · ' . $ik2_row[2] ); ?></span> <?php echo esc_html( $ik2_row[1] ); ?></th>
						<td>
							<span class="ik-a11y__status ik-a11y__status--<?php echo esc_attr( $ik2_row[3] ); ?>"><?php echo esc_html( $ik2_labels[ $ik2_row[3] ] ); ?></span>
							<?php echo esc_html( $ik2_row[4] ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endforeach; ?>

	<h2 id="next-steps"><?php esc_html_e( 'Next steps', 'ik2' ); ?></h2>
	<ul>
		<?php foreach ( $ik2_report['next_steps'] as $ik2_step ) : ?>
			<li><?php echo esc_html( $ik2_step ); ?></li>
		<?php endforeach; ?>
	</ul>

	<h2 id="report-a-problem"><?php esc_html_e( 'Report a problem', 'ik2' ); ?></h2>
	<p>
		<?php
		printf(
			/* translators: %s: email link */
			esc_html__( 'If something on this site gets in your way, email me at %s. Tell me the page and what happened, and I will fix it or tell you why I can\'t yet.', 'ik2' ),
			'<a href="mailto:hello@ivankristianto.com">hello@ivankristianto.com</a>'
		);
		?>
	</p>
</div>
