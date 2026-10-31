<?php
/**
 * Tools → Corpus: the Corpus status, the related switch, and re-queueing one Article or all of them.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\Corpus\Admin;

use IK2\Plugin\Corpus;
use IK2\Plugin\Corpus\Batch;

use const IK2\Plugin\PLUGIN_DIR;
use const IK2\Plugin\PLUGIN_FILE;

defined( 'ABSPATH' ) || exit;

const PAGE_SLUG  = 'ik2-corpus';
const CAPABILITY = 'manage_options';
const BATCH_SIZE = 50;
const NOTICE_ARG = 'ik2_corpus';
const POST_ARG   = 'ik2_corpus_post';

// Matches the hourly Dokploy schedule in the plugin README; the cron itself is invisible from here.
const RUN_INTERVAL_MINUTES = 60;

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_action( 'admin_menu', __NAMESPACE__ . '\\register_page' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets' );
	add_action( 'admin_notices', __NAMESPACE__ . '\\render_notice' );
	add_action( 'admin_post_ik2_corpus_reembed', __NAMESPACE__ . '\\handle_reembed' );
	add_action( 'admin_post_ik2_corpus_related', __NAMESPACE__ . '\\handle_related' );
	add_action( 'rest_api_init', __NAMESPACE__ . '\\register_routes' );
	add_filter( 'removable_query_args', __NAMESPACE__ . '\\removable_query_args' );
	add_filter( 'manage_post_posts_columns', __NAMESPACE__ . '\\add_column' );
	add_action( 'manage_post_posts_custom_column', __NAMESPACE__ . '\\render_column', 10, 2 );
	add_filter( 'post_row_actions', __NAMESPACE__ . '\\add_row_action', 10, 2 );
}

/**
 * Add Tools → Corpus.
 */
function register_page(): void {
	add_management_page( __( 'Corpus', 'ik2' ), __( 'Corpus', 'ik2' ), CAPABILITY, PAGE_SLUG, __NAMESPACE__ . '\\render_page' );
}

/**
 * The queue-all script and a few layout rules, on the Corpus screen only.
 *
 * @param string $hook_suffix Current admin page.
 */
function enqueue_assets( string $hook_suffix ): void {
	if ( 'tools_page_' . PAGE_SLUG !== $hook_suffix ) {
		return;
	}

	wp_enqueue_script(
		'ik2-corpus-admin',
		plugins_url( 'assets/corpus-admin.js', PLUGIN_FILE ),
		[ 'wp-api-fetch' ],
		(string) filemtime( PLUGIN_DIR . '/assets/corpus-admin.js' ),
		true
	);

	wp_add_inline_style( 'common', '.ik2-corpus .ik2-corpus__narrow{width:auto;min-width:22rem}.ik2-corpus progress{width:22rem;vertical-align:middle}' );
}

/**
 * Whether the current user may see and act on the Corpus.
 */
function can_manage(): bool {
	return current_user_can( CAPABILITY );
}

/**
 * Tools → Corpus URL.
 */
function page_url(): string {
	return admin_url( 'tools.php?page=' . PAGE_SLUG );
}

/**
 * Nonced link that re-queues one Article.
 *
 * @param int $post_id Article ID.
 */
function reembed_url( int $post_id ): string {
	return wp_nonce_url( admin_url( 'admin-post.php?action=ik2_corpus_reembed&post=' . $post_id ), 'ik2_corpus_reembed' );
}

/**
 * Expected time of the next Run, in the site timezone.
 */
function next_run_label(): string {
	return (string) wp_date( 'H:i', Batch\next_run_at( time(), RUN_INTERVAL_MINUTES ) );
}

/**
 * Show a UTC time from the Corpus tables in the site timezone.
 *
 * @param string|null $utc MySQL datetime in UTC.
 */
function local_time( ?string $utc ): string {
	return null === $utc || '' === $utc ? '' : get_date_from_gmt( $utc, 'Y-m-d H:i' );
}

/**
 * POST ik2/v1/corpus/queue-all: one batch of the Queue all loop.
 */
function register_routes(): void {
	register_rest_route(
		'ik2/v1',
		'/corpus/queue-all',
		[
			'methods'             => 'POST',
			'callback'            => __NAMESPACE__ . '\\rest_queue_all',
			'permission_callback' => __NAMESPACE__ . '\\can_manage',
			'args'                => [
				'after' => [
					'type'    => 'integer',
					'minimum' => 0,
					'default' => 0,
				],
			],
		]
	);
}

/**
 * Sync the next batch after the cursor.
 *
 * @param \WP_REST_Request $request Request with `after`.
 */
function rest_queue_all( \WP_REST_Request $request ): \WP_REST_Response {
	return new \WP_REST_Response( Corpus\queue_batch( (int) $request->get_param( 'after' ), BATCH_SIZE ) );
}

/**
 * Re-queue one Article, as `wp ik2 corpus enqueue <id> --force` does.
 */
function handle_reembed(): void {
	if ( ! can_manage() ) {
		wp_die( esc_html__( 'You are not allowed to manage the Corpus.', 'ik2' ), 403 );
	}

	check_admin_referer( 'ik2_corpus_reembed' );

	$post_id = absint( $_REQUEST['post'] ?? 0 );
	$result  = Corpus\sync_post( $post_id, true );

	if ( 'failed' === $result ) {
		// The error won't fit in a redirect URL, so it waits a minute for the notice.
		set_transient( 'ik2_corpus_error_' . get_current_user_id(), $GLOBALS['wpdb']->last_error, MINUTE_IN_SECONDS );
	}

	redirect_back( $result, $post_id );
}

/**
 * Turn the related picks on or off.
 */
function handle_related(): void {
	if ( ! can_manage() ) {
		wp_die( esc_html__( 'You are not allowed to manage the Corpus.', 'ik2' ), 403 );
	}

	check_admin_referer( 'ik2_corpus_related' );

	$state = 'on' === sanitize_key( wp_unslash( $_POST['state'] ?? '' ) ) ? 'on' : 'off';

	update_option( Corpus\RELATED_OPTION, $state, true );

	redirect_back( 'related-' . $state );
}

/**
 * Go back where the action came from, carrying the notice.
 *
 * @param string $notice  Notice key.
 * @param int    $post_id Article the notice is about, if any.
 * @return never
 */
function redirect_back( string $notice, int $post_id = 0 ): void {
	$back = remove_query_arg( [ NOTICE_ARG, POST_ARG ], (string) ( wp_get_referer() ? wp_get_referer() : page_url() ) );

	wp_safe_redirect(
		add_query_arg(
			[
				NOTICE_ARG => $notice,
				POST_ARG   => $post_id,
			],
			$back
		)
	);
	exit;
}

/**
 * Let WordPress drop the notice args from the address bar after showing them.
 *
 * @param string[] $args Removable query args.
 * @return string[]
 */
function removable_query_args( array $args ): array {
	$args[] = NOTICE_ARG;
	$args[] = POST_ARG;

	return $args;
}

/**
 * The notice for an action that just redirected here.
 */
function render_notice(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only; the action was nonce-checked before the redirect.
	$notice = sanitize_key( wp_unslash( $_GET[ NOTICE_ARG ] ?? '' ) );

	if ( '' === $notice || ! can_manage() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- As above.
	$post_id = absint( $_GET[ POST_ARG ] ?? 0 );
	$message = notice_message( $notice, $post_id );

	if ( null === $message ) {
		return;
	}

	printf(
		'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
		esc_attr( $message[0] ),
		esc_html( $message[1] )
	);
}

/**
 * Type and text for a notice key.
 *
 * @param string $notice  Notice key.
 * @param int    $post_id Article the notice is about.
 * @return array{0:string, 1:string}|null
 */
function notice_message( string $notice, int $post_id ): ?array {
	$title = $post_id > 0 ? get_the_title( $post_id ) : '';

	switch ( $notice ) {
		case 'queued':
			/* translators: 1: Article title, 2: time of the next Run */
			return [ 'success', sprintf( __( '"%1$s" is queued. The next Run, expected around %2$s, embeds whatever changed in it.', 'ik2' ), $title, next_run_label() ) ];
		case 'removed':
			/* translators: %s: Article title */
			return [ 'warning', sprintf( __( '"%s" is outside the Corpus (not published, password protected, noindex or excluded), so the next Run removes it.', 'ik2' ), $title ) ];
		case 'skipped':
			/* translators: %d: post ID */
			return [ 'warning', sprintf( __( 'Post %d is not an Article in the Corpus.', 'ik2' ), $post_id ) ];
		case 'failed':
			$error = (string) get_transient( 'ik2_corpus_error_' . get_current_user_id() );
			/* translators: 1: Article title, 2: database error */
			return [ 'error', sprintf( __( '"%1$s" could not be queued: %2$s', 'ik2' ), $title, $error ) ];
		case 'related-on':
			return [ 'success', __( 'Keep reading now uses the Corpus picks. Cached pages keep their old list for up to an hour.', 'ik2' ) ];
		case 'related-off':
			return [ 'success', __( 'Keep reading now shows the newest posts from the category. Cached pages keep their old list for up to an hour.', 'ik2' ) ];
	}

	return null;
}

/**
 * Add the Corpus column before Date on the Posts list.
 *
 * @param array<string, string> $columns Columns.
 * @return array<string, string>
 */
function add_column( array $columns ): array {
	if ( ! can_manage() ) {
		return $columns;
	}

	$added = [];

	foreach ( $columns as $key => $label ) {
		if ( 'date' === $key ) {
			$added['ik2_corpus'] = __( 'Corpus', 'ik2' );
		}

		$added[ $key ] = $label;
	}

	return $added;
}

/**
 * Print an Article's Corpus status.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function render_column( string $column, int $post_id ): void {
	if ( 'ik2_corpus' !== $column ) {
		return;
	}

	$row = column_statuses()[ $post_id ] ?? null;

	if ( null === $row ) {
		echo '<span aria-hidden="true">&mdash;</span><span class="screen-reader-text">' . esc_html__( 'Not in the Corpus', 'ik2' ) . '</span>';
		return;
	}

	switch ( $row['status'] ) {
		case Corpus\STATUS_INDEXED:
			/* translators: %s: date the Article was embedded */
			$label = sprintf( __( 'Indexed %s', 'ik2' ), substr( local_time( $row['indexed_at'] ), 0, 10 ) );
			break;
		case Corpus\STATUS_QUEUED:
			$label = __( 'Queued', 'ik2' );
			break;
		case Corpus\STATUS_PARKED:
			$label = __( 'Parked', 'ik2' );
			break;
		default:
			$label = __( 'Being removed', 'ik2' );
	}

	printf( '<span title="%1$s">%2$s</span>', esc_attr( (string) $row['last_error'] ), esc_html( $label ) );
}

/**
 * Corpus rows for every post on the current list page, fetched once.
 *
 * @return array<int, array{status:string, attempts:string, indexed_at:?string, last_error:?string}>
 */
function column_statuses(): array {
	static $statuses = null;

	if ( null === $statuses ) {
		$posts    = $GLOBALS['wp_query']->posts ?? [];
		$statuses = Corpus\document_statuses( wp_list_pluck( $posts, 'ID' ) );
	}

	return $statuses;
}

/**
 * Add Re-embed under each published Article on the Posts list.
 *
 * @param array<string, string> $actions Row actions.
 * @param \WP_Post              $post    Row post.
 * @return array<string, string>
 */
function add_row_action( array $actions, \WP_Post $post ): array {
	if ( Corpus\OBJECT_TYPE !== $post->post_type || 'publish' !== $post->post_status || ! can_manage() ) {
		return $actions;
	}

	$actions['ik2_corpus_reembed'] = sprintf(
		'<a href="%1$s" aria-label="%2$s">%3$s</a>',
		esc_url( reembed_url( $post->ID ) ),
		/* translators: %s: Article title */
		esc_attr( sprintf( __( 'Re-embed "%s"', 'ik2' ), get_the_title( $post ) ) ),
		esc_html__( 'Re-embed', 'ik2' )
	);

	return $actions;
}

/**
 * Tools → Corpus.
 */
function render_page(): void {
	$related = Corpus\related_enabled();
	$runs    = Corpus\recent_runs( 10 );
	$parked  = Corpus\parked_documents();

	echo '<div class="wrap ik2-corpus">';
	echo '<h1>' . esc_html__( 'Corpus', 'ik2' ) . '</h1>';
	echo '<p>' . esc_html__( 'Published Articles, cleaned, chunked and embedded by the worker. Queueing here only marks Articles; the worker embeds them on its next Run.', 'ik2' ) . '</p>';

	if ( ! Corpus\tables_exist() ) {
		echo '<div class="notice notice-warning inline"><p>' . esc_html__( "The Corpus tables aren't in the database yet. They are created on the next page load; if this stays, the CREATE TABLE error is in the PHP error log.", 'ik2' ) . '</p></div>';
	}

	render_related_section( $related );
	render_runs_section( $runs );
	render_documents_section();
	render_parked_section( $parked );
	render_queue_section();

	echo '</div>';
}

/**
 * The related switch and its toggle.
 *
 * @param bool $related Whether the picks are on.
 */
function render_related_section( bool $related ): void {
	echo '<h2>' . esc_html__( 'Related picks', 'ik2' ) . '</h2>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<p>';
	echo $related
		? esc_html__( 'On: Keep reading shows the closest Articles from the Corpus.', 'ik2' )
		: esc_html__( 'Off: Keep reading shows the newest posts from the Article\'s first category.', 'ik2' );
	echo '</p>';
	echo '<input type="hidden" name="action" value="ik2_corpus_related">';
	echo '<input type="hidden" name="state" value="' . esc_attr( $related ? 'off' : 'on' ) . '">';
	wp_nonce_field( 'ik2_corpus_related' );
	submit_button( $related ? __( 'Turn off', 'ik2' ) : __( 'Turn on', 'ik2' ), $related ? 'secondary' : 'primary', 'submit', false );
	echo '</form>';
}

/**
 * The last Runs and when the next one is expected.
 *
 * @param list<array<string, string>> $runs Newest first.
 */
function render_runs_section( array $runs ): void {
	echo '<h2>' . esc_html__( 'Runs', 'ik2' ) . '</h2>';

	$last = $runs[0] ?? null;

	if ( null === $last ) {
		echo '<p>' . esc_html__( 'No Run yet.', 'ik2' ) . ' ';
	} else {
		printf(
			/* translators: 1: time of the last Run, 2: how long ago */
			'<p>' . esc_html__( 'Last Run %1$s (%2$s ago).', 'ik2' ) . ' ',
			esc_html( local_time( $last['finished_at'] ) ),
			esc_html( human_time_diff( (int) strtotime( $last['finished_at'] . ' UTC' ) ) )
		);
	}

	printf(
		/* translators: %s: time of the next Run */
		esc_html__( 'Next Run expected around %s, on the hourly schedule in the plugin README.', 'ik2' ) . '</p>',
		esc_html( next_run_label() )
	);

	if ( [] === $runs ) {
		return;
	}

	$headings = [ __( 'Run', 'ik2' ), __( 'Started', 'ik2' ), __( 'Took', 'ik2' ), __( 'Documents', 'ik2' ), __( 'Chunks', 'ik2' ), __( 'Removed', 'ik2' ), __( 'Failed', 'ik2' ) ];

	echo '<table class="widefat striped"><thead><tr>';
	foreach ( $headings as $heading ) {
		echo '<th scope="col">' . esc_html( $heading ) . '</th>';
	}
	echo '</tr></thead><tbody>';

	foreach ( $runs as $run ) {
		$took = max( 0, (int) strtotime( $run['finished_at'] . ' UTC' ) - (int) strtotime( $run['started_at'] . ' UTC' ) );

		echo '<tr>';
		echo '<td>' . esc_html( $run['id'] ) . '</td>';
		echo '<td>' . esc_html( local_time( $run['started_at'] ) ) . '</td>';
		/* translators: %d: seconds */
		echo '<td>' . esc_html( sprintf( _n( '%d second', '%d seconds', $took, 'ik2' ), $took ) ) . '</td>';
		echo '<td>' . esc_html( $run['documents_embedded'] ) . '</td>';
		echo '<td>' . esc_html( $run['chunks_embedded'] ) . '</td>';
		echo '<td>' . esc_html( $run['removed'] ) . '</td>';
		echo '<td>' . esc_html( $run['failed'] ) . '</td>';
		echo '</tr>';
	}

	echo '</tbody></table>';
}

/**
 * Documents by status and the row counts.
 */
function render_documents_section(): void {
	echo '<h2>' . esc_html__( 'Documents', 'ik2' ) . '</h2>';

	$rows = [];

	foreach ( Corpus\status_counts() as $status ) {
		$rows[ $status['status'] ] = (int) $status['documents'];
	}

	if ( [] === $rows ) {
		echo '<p>' . esc_html__( 'None yet. Queue all Articles below.', 'ik2' ) . '</p>';
	}

	echo '<table class="widefat striped ik2-corpus__narrow"><tbody>';

	foreach ( $rows + Corpus\table_counts() as $label => $count ) {
		echo '<tr><th scope="row">' . esc_html( ucfirst( (string) $label ) ) . '</th><td>' . esc_html( number_format_i18n( $count ) ) . '</td></tr>';
	}

	echo '</tbody></table>';
}

/**
 * Parked Documents with a Retry button each.
 *
 * @param list<array{post_id:string, title:string, attempts:string, last_error:?string}> $parked Parked rows.
 */
function render_parked_section( array $parked ): void {
	if ( [] === $parked ) {
		return;
	}

	echo '<h2>' . esc_html__( 'Parked', 'ik2' ) . '</h2>';
	echo '<p>' . esc_html__( 'These failed 3 times and the worker skips them. Fix the cause, then retry.', 'ik2' ) . '</p>';
	echo '<table class="widefat striped"><thead><tr>';
	echo '<th scope="col">' . esc_html__( 'Article', 'ik2' ) . '</th>';
	echo '<th scope="col">' . esc_html__( 'Error', 'ik2' ) . '</th>';
	echo '<th scope="col"><span class="screen-reader-text">' . esc_html__( 'Actions', 'ik2' ) . '</span></th>';
	echo '</tr></thead><tbody>';

	foreach ( $parked as $row ) {
		$post_id = (int) $row['post_id'];

		echo '<tr>';
		echo '<td><a href="' . esc_url( (string) get_edit_post_link( $post_id ) ) . '">' . esc_html( $row['title'] ) . '</a></td>';
		echo '<td><code>' . esc_html( (string) $row['last_error'] ) . '</code></td>';
		echo '<td><a class="button" href="' . esc_url( reembed_url( $post_id ) ) . '">' . esc_html__( 'Retry', 'ik2' ) . '</a></td>';
		echo '</tr>';
	}

	echo '</tbody></table>';
}

/**
 * Re-queue one Article by ID, or every published Article in batches.
 */
function render_queue_section(): void {
	echo '<h2>' . esc_html__( 'Queue', 'ik2' ) . '</h2>';

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<p><label for="ik2-corpus-post">' . esc_html__( 'Article ID', 'ik2' ) . '</label> ';
	echo '<input type="number" min="1" id="ik2-corpus-post" name="post" class="small-text" required> ';
	echo '<input type="hidden" name="action" value="ik2_corpus_reembed">';
	wp_nonce_field( 'ik2_corpus_reembed' );
	submit_button( __( 'Re-embed', 'ik2' ), 'secondary', 'submit', false );
	echo '</p>';
	echo '<p class="description">' . esc_html__( 'Rebuilds the Article\'s Document from the post as it is now and queues it, which also retries a parked Article. The worker skips the embedding when nothing changed, since the vectors would come out the same. The Posts list has the same action under each Article.', 'ik2' ) . '</p>';
	echo '</form>';

	echo '<p><button type="button" class="button" id="ik2-corpus-queue-all">' . esc_html__( 'Queue all Articles', 'ik2' ) . '</button> ';
	echo '<progress id="ik2-corpus-queue-all-progress" value="0" max="1" hidden></progress></p>';
	echo '<p id="ik2-corpus-queue-all-status" class="description" role="status">' . esc_html__( 'Checks every published Article and queues the ones that changed, and marks Documents whose Article is gone for removal.', 'ik2' ) . '</p>';
}
