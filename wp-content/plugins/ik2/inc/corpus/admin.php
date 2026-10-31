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
 * The queue-all script and the screen's styles, on the Corpus screen only.
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

	wp_enqueue_style(
		'ik2-corpus-admin',
		plugins_url( 'assets/corpus-admin.css', PLUGIN_FILE ),
		[],
		(string) filemtime( PLUGIN_DIR . '/assets/corpus-admin.css' )
	);
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

	$statuses = column_statuses();

	// Quick Edit redraws one row over admin-ajax, where the list query is empty, so look that row up alone.
	$row = array_key_exists( $post_id, $statuses ) ? $statuses[ $post_id ] : Corpus\document_statuses( [ $post_id ] )[ $post_id ] ?? null;

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
 * @return array<int, array{status:string, attempts:string, indexed_at:?string, last_error:?string}|null>
 */
function column_statuses(): array {
	static $statuses = null;

	if ( null === $statuses ) {
		$ids = array_map( 'intval', wp_list_pluck( $GLOBALS['wp_query']->posts ?? [], 'ID' ) );

		// Null marks a post that was checked and has no Document, so it isn't looked up again.
		$statuses = Corpus\document_statuses( $ids ) + array_fill_keys( $ids, null );
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
	$runs     = Corpus\recent_runs( 10 );
	$statuses = [];

	foreach ( Corpus\status_counts() as $row ) {
		$statuses[ $row['status'] ] = (int) $row['documents'];
	}

	echo '<div class="wrap ik2-corpus">';
	echo '<h1>' . esc_html__( 'Corpus', 'ik2' ) . '</h1>';
	echo '<p class="ik2-corpus__lede">' . esc_html__( 'Published Articles, cleaned, chunked and embedded by the worker. Queueing here only marks Articles; the worker embeds them on its next Run.', 'ik2' ) . '</p>';
	echo '<hr class="wp-header-end">';

	if ( ! Corpus\tables_exist() ) {
		echo '<div class="notice notice-warning inline"><p>' . esc_html__( "The Corpus tables aren't in the database yet. They are created on the next page load; if this stays, the CREATE TABLE error is in the PHP error log.", 'ik2' ) . '</p></div>';
	}

	render_facts( $runs[0] ?? null, $statuses );

	echo '<div class="ik2-corpus__grid">';
	echo '<div class="ik2-corpus__main">';
	render_parked_panel( Corpus\parked_documents() );
	render_runs_panel( $runs );
	render_documents_panel( $statuses );
	echo '</div>';
	echo '<div class="ik2-corpus__side">';
	render_related_panel( Corpus\related_enabled() );
	render_queue_panel();
	echo '</div>';
	echo '</div>';

	echo '</div>';
}

/**
 * One fact in the summary strip.
 *
 * @param string $label Term.
 * @param string $value Main value, already escaped.
 * @param string $note  Secondary line, already escaped.
 */
function render_fact( string $label, string $value, string $note = '' ): void {
	echo '<div class="ik2-corpus__fact">';
	echo '<dt>' . esc_html( $label ) . '</dt>';
	echo '<dd>' . $value . ( '' === $note ? '' : '<span class="ik2-corpus__note">' . $note . '</span>' ) . '</dd>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Both parts are escaped by the callers.
	echo '</div>';
}

/**
 * A status dot and its word, so the state never rests on color alone.
 *
 * @param string $tone  ok, wait, bad or off.
 * @param string $label Visible state.
 */
function state( string $tone, string $label ): string {
	return '<span class="ik2-corpus__state is-' . esc_attr( $tone ) . '">' . esc_html( $label ) . '</span>';
}

/**
 * The summary strip: picks, last and next Run, coverage, parked.
 *
 * @param array<string, string>|null $last     Newest Run.
 * @param array<string, int>         $statuses Document counts by status.
 */
function render_facts( ?array $last, array $statuses ): void {
	$published = (int) wp_count_posts( Corpus\OBJECT_TYPE )->publish;
	$indexed   = $statuses[ Corpus\STATUS_INDEXED ] ?? 0;
	$queued    = $statuses[ Corpus\STATUS_QUEUED ] ?? 0;
	$parked    = $statuses[ Corpus\STATUS_PARKED ] ?? 0;
	$related   = Corpus\related_enabled();

	echo '<dl class="ik2-corpus__facts">';

	render_fact(
		__( 'Related picks', 'ik2' ),
		$related ? state( 'ok', __( 'On', 'ik2' ) ) : state( 'off', __( 'Off', 'ik2' ) ),
		$related ? esc_html__( 'Keep reading uses the Corpus', 'ik2' ) : esc_html__( 'Keep reading uses the category', 'ik2' )
	);

	if ( null === $last ) {
		render_fact( __( 'Last Run', 'ik2' ), esc_html__( 'None yet', 'ik2' ) );
	} else {
		render_fact(
			__( 'Last Run', 'ik2' ),
			/* translators: %s: how long ago */
			esc_html( sprintf( __( '%s ago', 'ik2' ), human_time_diff( (int) strtotime( $last['finished_at'] . ' UTC' ) ) ) ),
			'<time class="ik2-corpus__mono">' . esc_html( local_time( $last['finished_at'] ) ) . '</time>'
		);
	}

	render_fact(
		__( 'Next Run', 'ik2' ),
		'<span class="ik2-corpus__mono">' . esc_html( next_run_label() ) . '</span>',
		esc_html__( 'expected, hourly schedule', 'ik2' )
	);

	render_fact(
		__( 'Indexed', 'ik2' ),
		/* translators: 1: indexed Articles, 2: published Articles */
		esc_html( sprintf( __( '%1$s of %2$s published', 'ik2' ), number_format_i18n( $indexed ), number_format_i18n( $published ) ) ),
		/* translators: %s: queued Articles */
		$queued > 0 ? esc_html( sprintf( _n( '%s queued for the next Run', '%s queued for the next Run', $queued, 'ik2' ), number_format_i18n( $queued ) ) ) : esc_html__( 'nothing waiting', 'ik2' )
	);

	render_fact(
		__( 'Parked', 'ik2' ),
		$parked > 0 ? state( 'bad', number_format_i18n( $parked ) ) : state( 'ok', '0' ),
		$parked > 0 ? esc_html__( 'failed 3 times, see below', 'ik2' ) : esc_html__( 'no failures', 'ik2' )
	);

	echo '</dl>';
}

/**
 * Open a panel with its heading.
 *
 * @param string $title Heading.
 * @param string $modifier Extra class.
 */
function open_panel( string $title, string $modifier = '' ): void {
	echo '<section class="ik2-corpus__panel ' . esc_attr( $modifier ) . '">';
	echo '<h2>' . esc_html( $title ) . '</h2>';
}

/**
 * The last Runs, newest first.
 *
 * @param list<array<string, string>> $runs Newest first.
 */
function render_runs_panel( array $runs ): void {
	open_panel( __( 'Runs', 'ik2' ) );

	if ( [] === $runs ) {
		echo '<p class="ik2-corpus__empty">' . esc_html__( 'No Run yet. Queue all Articles, then run the worker once by hand (step 5 in the plugin README). After that the hourly schedule takes over.', 'ik2' ) . '</p>';
		echo '</section>';
		return;
	}

	echo '<div class="ik2-corpus__scroll"><table class="ik2-corpus__table"><thead><tr>';
	echo '<th scope="col">' . esc_html__( 'Run', 'ik2' ) . '</th>';
	echo '<th scope="col">' . esc_html__( 'Started', 'ik2' ) . '</th>';
	echo '<th scope="col" class="is-num">' . esc_html__( 'Took', 'ik2' ) . '</th>';
	echo '<th scope="col" class="is-num">' . esc_html__( 'Documents', 'ik2' ) . '</th>';
	echo '<th scope="col" class="is-num">' . esc_html__( 'Chunks', 'ik2' ) . '</th>';
	echo '<th scope="col" class="is-num">' . esc_html__( 'Removed', 'ik2' ) . '</th>';
	echo '<th scope="col" class="is-num">' . esc_html__( 'Failed', 'ik2' ) . '</th>';
	echo '</tr></thead><tbody>';

	foreach ( $runs as $run ) {
		$took = max( 0, (int) strtotime( $run['finished_at'] . ' UTC' ) - (int) strtotime( $run['started_at'] . ' UTC' ) );

		echo '<tr>';
		echo '<td class="ik2-corpus__mono">' . esc_html( $run['id'] ) . '</td>';
		echo '<td class="ik2-corpus__mono">' . esc_html( local_time( $run['started_at'] ) ) . '</td>';
		echo '<td class="is-num">' . esc_html( duration( $took ) ) . '</td>';
		echo count_cell( (int) $run['documents_embedded'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- count_cell() escapes.
		echo count_cell( (int) $run['chunks_embedded'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- As above.
		echo count_cell( (int) $run['removed'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- As above.
		echo count_cell( (int) $run['failed'], 'is-bad' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- As above.
		echo '</tr>';
	}

	echo '</tbody></table></div>';
	echo '</section>';
}

/**
 * A numeric cell that fades zeros so the Runs that did work stand out.
 *
 * @param int    $count   Value.
 * @param string $nonzero Class for a non-zero value.
 */
function count_cell( int $count, string $nonzero = '' ): string {
	$class = 0 === $count ? 'is-num is-zero' : trim( 'is-num ' . $nonzero );

	return '<td class="' . esc_attr( $class ) . '">' . esc_html( number_format_i18n( $count ) ) . '</td>';
}

/**
 * Seconds as 42 s or 2 min 51 s.
 *
 * @param int $seconds Duration.
 */
function duration( int $seconds ): string {
	if ( $seconds < 60 ) {
		/* translators: %d: seconds */
		return sprintf( __( '%d s', 'ik2' ), $seconds );
	}

	/* translators: 1: minutes, 2: seconds */
	return sprintf( __( '%1$d min %2$d s', 'ik2' ), intdiv( $seconds, 60 ), $seconds % 60 );
}

/**
 * Parked Documents with a Retry button each.
 *
 * @param list<array{post_id:string, title:string, attempts:string, last_error:?string}> $parked Parked rows.
 */
function render_parked_panel( array $parked ): void {
	if ( [] === $parked ) {
		return;
	}

	open_panel( __( 'Parked', 'ik2' ), 'is-alert' );
	echo '<p class="ik2-corpus__help">' . esc_html__( 'These failed 3 times, so the worker skips them. Fix the cause, then retry.', 'ik2' ) . '</p>';
	echo '<ul class="ik2-corpus__parked">';

	foreach ( $parked as $row ) {
		$post_id = (int) $row['post_id'];

		echo '<li>';
		echo '<div><a href="' . esc_url( (string) get_edit_post_link( $post_id ) ) . '">' . esc_html( $row['title'] ) . '</a>';
		echo '<code>' . esc_html( (string) $row['last_error'] ) . '</code></div>';
		echo '<a class="button" href="' . esc_url( reembed_url( $post_id ) ) . '">' . esc_html__( 'Retry', 'ik2' ) . '</a>';
		echo '</li>';
	}

	echo '</ul>';
	echo '</section>';
}

/**
 * The related switch and its toggle.
 *
 * @param bool $related Whether the picks are on.
 */
function render_related_panel( bool $related ): void {
	open_panel( __( 'Related picks', 'ik2' ) );
	echo '<p class="ik2-corpus__help">';
	echo $related
		? esc_html__( 'Keep reading shows the closest Articles from the Corpus.', 'ik2' )
		: esc_html__( "Keep reading shows the newest posts from the Article's first category.", 'ik2' );
	echo '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="ik2_corpus_related">';
	echo '<input type="hidden" name="state" value="' . esc_attr( $related ? 'off' : 'on' ) . '">';
	wp_nonce_field( 'ik2_corpus_related' );
	submit_button( $related ? __( 'Turn off', 'ik2' ) : __( 'Turn on', 'ik2' ), $related ? 'secondary' : 'primary', 'submit', false );
	echo '</form>';
	echo '</section>';
}

/**
 * Re-queue one Article by ID, or every published Article in batches.
 */
function render_queue_panel(): void {
	open_panel( __( 'Queue', 'ik2' ) );

	echo '<form class="ik2-corpus__action" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<label for="ik2-corpus-post">' . esc_html__( 'Re-embed one Article', 'ik2' ) . '</label>';
	echo '<div class="ik2-corpus__inline">';
	echo '<input type="number" min="1" id="ik2-corpus-post" name="post" class="small-text" placeholder="' . esc_attr__( 'Post ID', 'ik2' ) . '" aria-describedby="ik2-corpus-post-help" required>';
	echo '<input type="hidden" name="action" value="ik2_corpus_reembed">';
	wp_nonce_field( 'ik2_corpus_reembed' );
	submit_button( __( 'Re-embed', 'ik2' ), 'secondary', 'submit', false );
	echo '</div>';
	echo '<p id="ik2-corpus-post-help" class="ik2-corpus__help">' . esc_html__( 'Rebuilds the Document from the post as it is now, which also retries a parked Article. The worker embeds only what changed. The Posts list has the same link under each Article.', 'ik2' ) . '</p>';
	echo '</form>';

	echo '<div class="ik2-corpus__action">';
	echo '<span class="ik2-corpus__label">' . esc_html__( 'Every Article', 'ik2' ) . '</span>';
	echo '<div class="ik2-corpus__inline">';
	echo '<button type="button" class="button" id="ik2-corpus-queue-all" aria-describedby="ik2-corpus-queue-all-status">' . esc_html__( 'Queue all Articles', 'ik2' ) . '</button>';
	echo '<progress id="ik2-corpus-queue-all-progress" value="0" max="1" hidden></progress>';
	echo '</div>';
	echo '<p id="ik2-corpus-queue-all-status" class="ik2-corpus__help" role="status">' . esc_html__( 'Queues the Articles that changed and marks Documents whose Article is gone for removal, 50 at a time.', 'ik2' ) . '</p>';
	echo '</div>';

	echo '</section>';
}

/**
 * Documents by status and the row counts behind them.
 *
 * @param array<string, int> $statuses Document counts by status.
 */
function render_documents_panel( array $statuses ): void {
	$tones = [
		Corpus\STATUS_INDEXED => [ 'ok', __( 'Indexed', 'ik2' ) ],
		Corpus\STATUS_QUEUED  => [ 'wait', __( 'Queued', 'ik2' ) ],
		Corpus\STATUS_PARKED  => [ 'bad', __( 'Parked', 'ik2' ) ],
		Corpus\STATUS_REMOVE  => [ 'off', __( 'Being removed', 'ik2' ) ],
	];

	open_panel( __( 'Documents', 'ik2' ) );
	echo '<div class="ik2-corpus__split">';
	echo '<table class="ik2-corpus__counts"><caption class="screen-reader-text">' . esc_html__( 'Documents by status', 'ik2' ) . '</caption><tbody>';

	foreach ( $tones as $status => [ $tone, $label ] ) {
		$count = $statuses[ $status ] ?? 0;

		echo '<tr' . ( 0 === $count ? ' class="is-zero"' : '' ) . '><th scope="row">' . state( $tone, $label ) . '</th><td class="is-num">' . esc_html( number_format_i18n( $count ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- state() escapes.
	}

	echo '</tbody></table>';
	echo '<table class="ik2-corpus__counts is-store"><caption class="screen-reader-text">' . esc_html__( 'Stored rows', 'ik2' ) . '</caption><tbody>';

	foreach ( Corpus\table_counts() as $label => $count ) {
		echo '<tr><th scope="row">' . esc_html( ucfirst( (string) $label ) ) . '</th><td class="is-num">' . esc_html( number_format_i18n( $count ) ) . '</td></tr>';
	}

	echo '</tbody></table>';
	echo '</div>';
	echo '</section>';
}
