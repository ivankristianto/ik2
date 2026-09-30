<?php
/**
 * Pull production content into the local dev site over the WP REST API.
 * Runs inside the wp-cli container via sync.sh (`wp eval-file -`). Flags are in ../SKILL.md.
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- dev-only tool, rewrites IDs and modified dates WP has no API for.

const IK2_SYNC_PER_PAGE = 100;
const IK2_SYNC_PHASES   = [ 'terms', 'media', 'posts', 'pages', 'projects', 'blocks', 'navigation', 'settings' ];

// REST setting => local option. Title, tagline, URLs, and email stay local so dev is recognisable.
const IK2_SYNC_SETTINGS = [
	'show_on_front'  => 'show_on_front',
	'page_on_front'  => 'page_on_front',
	'page_for_posts' => 'page_for_posts',
	'posts_per_page' => 'posts_per_page',
	'date_format'    => 'date_format',
	'time_format'    => 'time_format',
	'start_of_week'  => 'start_of_week',
	'timezone'       => 'timezone_string',
];

/**
 * Post-type phases: phase name => [ local post type, REST base ].
 */
function ik2_sync_post_phases(): array {
	return [
		'posts'      => [ 'post', 'posts' ],
		'pages'      => [ 'page', 'pages' ],
		'projects'   => [ 'project', 'project' ],
		'blocks'     => [ 'wp_block', 'blocks' ],
		'navigation' => [ 'wp_navigation', 'navigation' ],
	];
}

function ik2_sync_synced_types(): array {
	return array_merge( [ 'attachment' ], array_column( ik2_sync_post_phases(), 0 ) );
}

function ik2_sync_parse_args( array $args ): array {
	$opts = [
		'dry'   => false,
		'prune' => false,
		'force' => false,
		'limit' => 0,
		'only'  => IK2_SYNC_PHASES,
	];

	foreach ( $args as $arg ) {
		if ( 'dry-run' === $arg ) {
			$opts['dry'] = true;
		} elseif ( 'prune' === $arg ) {
			$opts['prune'] = true;
		} elseif ( 'force' === $arg ) {
			$opts['force'] = true;
		} elseif ( str_starts_with( $arg, 'limit=' ) ) {
			$opts['limit'] = max( 0, (int) substr( $arg, 6 ) );
		} elseif ( str_starts_with( $arg, 'only=' ) ) {
			$only    = array_filter( array_map( 'trim', explode( ',', substr( $arg, 5 ) ) ) );
			$unknown = array_diff( $only, IK2_SYNC_PHASES );
			if ( $unknown ) {
				WP_CLI::error( 'Unknown phase(s): ' . implode( ', ', $unknown ) . '. Valid: ' . implode( ', ', IK2_SYNC_PHASES ) );
			}
			$opts['only'] = array_values( $only );
		} else {
			WP_CLI::error( "Unknown argument '$arg'. Valid: dry-run, prune, force, limit=N, only=<phase,...>" );
		}
	}

	// Pruning against a partial list would delete everything past the limit.
	if ( $opts['prune'] && $opts['limit'] ) {
		WP_CLI::error( 'prune cannot be combined with limit=N.' );
	}

	return $opts;
}

function ik2_sync_guard_local( string $prod_home ): void {
	if ( 'development' !== wp_get_environment_type() ) {
		WP_CLI::error( 'Refusing to run: this WordPress is not WP_ENVIRONMENT_TYPE=development.' );
	}

	if ( wp_parse_url( home_url(), PHP_URL_HOST ) === wp_parse_url( $prod_home, PHP_URL_HOST ) ) {
		WP_CLI::error( 'Refusing to run: the local home URL has the same host as production.' );
	}
}

function ik2_sync_remote_config(): array {
	$base = rtrim( (string) ( getenv( 'IK2_PROD_URL' ) ?: 'https://www.ivankristianto.com' ), '/' );
	$user = (string) getenv( 'IK2_PROD_USER' );
	$pass = (string) getenv( 'IK2_PROD_APP_PASSWORD' );

	if ( '' === $user || '' === $pass ) {
		WP_CLI::error( 'IK2_PROD_USER and IK2_PROD_APP_PASSWORD must be set.' );
	}

	if ( ! str_starts_with( $base, 'https://' ) ) {
		WP_CLI::error( "IK2_PROD_URL must be https (got $base). Application passwords are sent as Basic auth." );
	}

	return [
		'api'  => $base . '/wp-json/',
		'auth' => 'Basic ' . base64_encode( $user . ':' . str_replace( ' ', '', $pass ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	];
}

/**
 * GET a production REST path. Returns [ body, total_pages ], or null on failure when $fatal is false.
 */
function ik2_sync_get( array $remote, string $path, array $query = [], bool $fatal = true ): ?array {
	$url      = add_query_arg( $query, $remote['api'] . ltrim( $path, '/' ) );
	$response = wp_remote_get(
		$url,
		[
			'timeout' => 60,
			'headers' => [ 'Authorization' => $remote['auth'] ],
		]
	);

	$error = null;
	if ( is_wp_error( $response ) ) {
		$error = $response->get_error_message();
	} else {
		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 200 !== $code || ! is_array( $body ) ) {
			$error = "HTTP $code" . ( is_array( $body ) && isset( $body['message'] ) ? ': ' . $body['message'] : '' );
		}
	}

	if ( null !== $error ) {
		if ( $fatal ) {
			WP_CLI::error( "GET $path failed ($error)" );
		}
		WP_CLI::warning( "GET $path failed ($error)" );
		return null;
	}

	return [ $body, (int) wp_remote_retrieve_header( $response, 'x-wp-totalpages' ) ];
}

function ik2_sync_fetch_all( array $remote, string $rest_base, array $query, int $limit ): array {
	$items = [];
	$page  = 1;

	do {
		[ $body, $pages ] = ik2_sync_get(
			$remote,
			'wp/v2/' . $rest_base,
			array_merge(
				[
					'context'  => 'edit',
					'per_page' => IK2_SYNC_PER_PAGE,
					'page'     => $page,
					'orderby'  => 'id',
					'order'    => 'asc',
				],
				$query
			)
		);

		$items = array_merge( $items, $body );
		if ( $limit && count( $items ) >= $limit ) {
			return array_slice( $items, 0, $limit );
		}
		++$page;
	} while ( $page <= $pages );

	return $items;
}

function ik2_sync_tally( string $phase = '', string $action = '', string $note = '', int $count = 1 ): array {
	static $tally = [];
	static $notes = [];

	if ( '' !== $phase ) {
		$tally[ $phase ][ $action ] = ( $tally[ $phase ][ $action ] ?? 0 ) + $count;
		if ( '' !== $note ) {
			$notes[] = "[$phase] $note";
		}
	}

	return [ $tally, $notes ];
}

function ik2_sync_progress( string $phase, int $done, int $total ): void {
	if ( 0 === $done % 50 || $done === $total ) {
		WP_CLI::log( "  $phase: $done/$total" );
	}
}

/**
 * Point production URLs at the local site, including the slash-escaped form inside block attribute JSON.
 */
function ik2_sync_rewrite_urls( string $text, array $ctx ): string {
	$from = [];
	$to   = [];
	foreach ( [ $ctx['prod_home'], set_url_scheme( $ctx['prod_home'], 'http' ) ] as $url ) {
		$from[] = $url;
		$to[]   = $ctx['local_home'];
		$from[] = str_replace( '/', '\\/', $url );
		$to[]   = str_replace( '/', '\\/', $ctx['local_home'] );
	}

	return str_replace( $from, $to, $text );
}

function ik2_sync_mysql_date( ?string $rest_date ): string {
	return $rest_date ? str_replace( 'T', ' ', $rest_date ) : '0000-00-00 00:00:00';
}

/**
 * Map production user IDs to local ones by login, falling back to the first local administrator.
 */
function ik2_sync_user_map( array $remote ): array {
	$admins   = get_users(
		[
			'role'    => 'administrator',
			'orderby' => 'ID',
			'order'   => 'ASC',
			'number'  => 1,
			'fields'  => 'ID',
		]
	);
	$fallback = (int) ( $admins[0] ?? 1 );
	$map      = [ 0 => $fallback ];

	$result = ik2_sync_get(
		$remote,
		'wp/v2/users',
		[
			'context'  => 'edit',
			'per_page' => IK2_SYNC_PER_PAGE,
		],
		false
	);

	foreach ( $result[0] ?? [] as $user ) {
		$local                         = get_user_by( 'login', $user['username'] ?? '' ) ?: get_user_by( 'slug', $user['slug'] ?? '' );
		$map[ (int) $user['id'] ] = $local ? (int) $local->ID : $fallback;
	}

	return $map;
}

function ik2_sync_author( int $prod_id, array $ctx ): int {
	return $ctx['users'][ $prod_id ] ?? $ctx['users'][0];
}

/**
 * Upsert one taxonomy by slug. Returns prod term ID => local term ID.
 */
function ik2_sync_terms( string $taxonomy, string $rest_base, array $ctx ): array {
	$phase   = "terms:$taxonomy";
	// Never limited: posts need every term mapped.
	$pending = ik2_sync_fetch_all( $ctx['remote'], $rest_base, [ 'hide_empty' => false ], 0 );
	$total   = count( $pending );
	$map     = [];
	$seen    = [];

	// Parents first; anything whose parent never resolves goes in at the top level.
	while ( $pending ) {
		$progress    = false;
		$pending_ids = array_map( 'intval', array_column( $pending, 'id' ) );
		foreach ( $pending as $key => $term ) {
			$parent = (int) ( $term['parent'] ?? 0 );
			if ( $parent && in_array( $parent, $pending_ids, true ) ) {
				continue;
			}
			$map[ (int) $term['id'] ] = ik2_sync_upsert_term( $term, $taxonomy, $map[ $parent ] ?? 0, $ctx );
			$seen[]                   = $term['slug'];
			unset( $pending[ $key ] );
			$pending_ids = array_diff( $pending_ids, [ (int) $term['id'] ] );
			$progress = true;
		}
		$pending = array_values( $pending );
		if ( ! $progress ) {
			foreach ( $pending as $term ) {
				$map[ (int) $term['id'] ] = ik2_sync_upsert_term( $term, $taxonomy, 0, $ctx );
				$seen[]                   = $term['slug'];
			}
			break;
		}
	}
	WP_CLI::log( "  $phase: $total/$total" );

	if ( $ctx['opts']['prune'] ) {
		$local = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			]
		);
		foreach ( is_array( $local ) ? $local : [] as $term ) {
			if ( in_array( $term->slug, $seen, true ) || ( 'category' === $taxonomy && (int) get_option( 'default_category' ) === $term->term_id ) ) {
				continue;
			}
			if ( ! $ctx['opts']['dry'] ) {
				wp_delete_term( $term->term_id, $taxonomy );
			}
			ik2_sync_tally( $phase, 'pruned' );
		}
	}

	return $map;
}

function ik2_sync_upsert_term( array $term, string $taxonomy, int $parent, array $ctx ): int {
	$phase    = "terms:$taxonomy";
	$existing = get_term_by( 'slug', $term['slug'], $taxonomy );
	$fields   = [
		'name'        => $term['name'],
		'description' => $term['description'] ?? '',
		'parent'      => $parent,
	];

	if ( $existing
		&& $existing->name === $fields['name']
		&& $existing->description === $fields['description']
		&& $existing->parent === $parent ) {
		ik2_sync_tally( $phase, 'skipped' );
		return $existing->term_id;
	}

	if ( $ctx['opts']['dry'] ) {
		ik2_sync_tally( $phase, $existing ? 'updated' : 'created' );
		return $existing ? $existing->term_id : 0;
	}

	$result = $existing
		? wp_update_term( $existing->term_id, $taxonomy, wp_slash( $fields ) )
		: wp_insert_term( $fields['name'], $taxonomy, wp_slash( array_merge( $fields, [ 'slug' => $term['slug'] ] ) ) );

	if ( is_wp_error( $result ) ) {
		$term_id = (int) $result->get_error_data( 'term_exists' );
		ik2_sync_tally( $phase, 'failed', "{$term['slug']}: " . $result->get_error_message() );
		return $term_id;
	}

	ik2_sync_tally( $phase, $existing ? 'updated' : 'created' );
	return (int) $result['term_id'];
}

/**
 * Free up $id for a production item of $type. Returns true when the ID now holds nothing (or a same-type row to update).
 */
function ik2_sync_claim_id( int $id, string $type, string $phase, array $ctx ): bool {
	$local = get_post( $id );
	if ( ! $local || ( $local->post_type === $type && 'auto-draft' !== $local->post_status ) ) {
		return true;
	}

	if ( $ctx['opts']['dry'] ) {
		ik2_sync_tally( $phase, 'displaced' );
		return true;
	}

	// Throwaway rows and other synced types get replaced; anything else (menus, global styles) moves out of the way.
	if ( 'revision' === $local->post_type || 'auto-draft' === $local->post_status || in_array( $local->post_type, ik2_sync_synced_types(), true ) ) {
		$deleted = 'attachment' === $local->post_type ? wp_delete_attachment( $id, true ) : wp_delete_post( $id, true );
		ik2_sync_tally( $phase, 'displaced', "local {$local->post_type} #$id deleted to make room" );
		return (bool) $deleted;
	}

	ik2_sync_relocate_post( $local, $phase );
	return true;
}

function ik2_sync_relocate_post( WP_Post $local, string $phase ): void {
	global $wpdb;

	$old = $local->ID;
	$new = 1 + (int) $wpdb->get_var( "SELECT MAX(ID) FROM {$wpdb->posts}" );

	$wpdb->update( $wpdb->posts, [ 'ID' => $new ], [ 'ID' => $old ] );
	$wpdb->update( $wpdb->posts, [ 'post_parent' => $new ], [ 'post_parent' => $old ] );
	$wpdb->update( $wpdb->postmeta, [ 'post_id' => $new ], [ 'post_id' => $old ] );
	$wpdb->update( $wpdb->term_relationships, [ 'object_id' => $new ], [ 'object_id' => $old ] );
	$wpdb->update( $wpdb->comments, [ 'comment_post_ID' => $new ], [ 'comment_post_ID' => $old ] );
	$wpdb->update(
		$wpdb->postmeta,
		[ 'meta_value' => (string) $new ], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		[
			'meta_key'   => '_menu_item_menu_item_parent', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value' => (string) $old, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		]
	);

	clean_post_cache( $old );
	clean_post_cache( $new );
	ik2_sync_tally( $phase, 'displaced', "local {$local->post_type} #$old moved to #$new" );
}

function ik2_sync_is_unchanged( array $item, string $type, array $ctx ): bool {
	$local = get_post( (int) $item['id'] );

	return ! $ctx['opts']['force']
		&& $local
		&& $local->post_type === $type
		&& $local->post_modified_gmt === ik2_sync_mysql_date( $item['modified_gmt'] ?? null );
}

/**
 * Insert or update a post row at the production ID, then restore the production modified dates.
 */
function ik2_sync_write_post( array $item, array $postarr, string $phase ): int {
	global $wpdb;

	$id = (int) $item['id'];
	if ( get_post( $id ) ) {
		$postarr['ID'] = $id;
	} else {
		$postarr['import_id'] = $id;
	}

	$result = wp_insert_post( wp_slash( $postarr ), true );
	if ( is_wp_error( $result ) ) {
		ik2_sync_tally( $phase, 'failed', "#$id {$item['slug']}: " . $result->get_error_message() );
		return 0;
	}

	if ( $result !== $id ) {
		ik2_sync_tally( $phase, 'failed', "#$id {$item['slug']}: ID was taken, landed at #$result" );
		return 0;
	}

	$fields = [
		'post_modified'     => ik2_sync_mysql_date( $item['modified'] ?? null ),
		'post_modified_gmt' => ik2_sync_mysql_date( $item['modified_gmt'] ?? null ),
	];

	// A local-only post holding the slug makes WordPress suffix ours ("projects-2"); production wins.
	$landed = get_post_field( 'post_name', $id );
	if ( '' !== $item['slug'] && $landed !== $item['slug'] ) {
		ik2_sync_release_slug( $item['slug'], $postarr['post_type'], $id, $phase );
		$fields['post_name'] = $item['slug'];
	}

	$wpdb->update( $wpdb->posts, $fields, [ 'ID' => $id ] );
	clean_post_cache( $id );

	return $id;
}

function ik2_sync_release_slug( string $slug, string $type, int $keep_id, string $phase ): void {
	global $wpdb;

	$holders = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s AND ID != %d",
			$slug,
			$type,
			$keep_id
		)
	);

	foreach ( array_map( 'intval', $holders ) as $holder ) {
		$wpdb->update( $wpdb->posts, [ 'post_name' => "$slug-local-$holder" ], [ 'ID' => $holder ] );
		clean_post_cache( $holder );
		ik2_sync_tally( $phase, 'displaced', "local $type #$holder renamed to $slug-local-$holder so #$keep_id keeps '$slug'" );
	}
}

function ik2_sync_post_meta( int $id, string $type, array $meta ): void {
	$registered = get_registered_meta_keys( 'post', $type ) + get_registered_meta_keys( 'post' );

	foreach ( $meta as $key => $value ) {
		if ( ! empty( $registered[ $key ] ) && empty( $registered[ $key ]['single'] ) && is_array( $value ) ) {
			delete_post_meta( $id, $key );
			foreach ( $value as $single ) {
				add_post_meta( $id, $key, wp_slash( $single ) );
			}
		} elseif ( '' === $value || null === $value || [] === $value ) {
			delete_post_meta( $id, $key );
		} else {
			update_post_meta( $id, $key, wp_slash( $value ) );
		}
	}
}

function ik2_sync_post_terms( int $id, array $item, array $ctx ): void {
	foreach ( [ 'categories' => 'category', 'tags' => 'post_tag' ] as $field => $taxonomy ) {
		if ( ! isset( $item[ $field ] ) || ! isset( $ctx['terms'][ $taxonomy ] ) ) {
			continue;
		}
		$ids = [];
		foreach ( $item[ $field ] as $prod_term ) {
			if ( ! empty( $ctx['terms'][ $taxonomy ][ $prod_term ] ) ) {
				$ids[] = (int) $ctx['terms'][ $taxonomy ][ $prod_term ];
			}
		}
		wp_set_object_terms( $id, $ids, $taxonomy );
	}
}

function ik2_sync_upsert_post( array $item, string $type, string $phase, array $ctx ): void {
	$id = (int) $item['id'];

	if ( ik2_sync_is_unchanged( $item, $type, $ctx ) ) {
		ik2_sync_tally( $phase, 'skipped' );
		return;
	}

	$action = get_post( $id ) && get_post_type( $id ) === $type ? 'updated' : 'created';
	if ( ! ik2_sync_claim_id( $id, $type, $phase, $ctx ) ) {
		ik2_sync_tally( $phase, 'failed', "#$id {$item['slug']}: ID could not be freed" );
		return;
	}
	if ( $ctx['opts']['dry'] ) {
		ik2_sync_tally( $phase, $action );
		return;
	}

	$postarr = [
		'post_type'      => $type,
		'post_status'    => $item['status'],
		'post_title'     => $item['title']['raw'] ?? '',
		'post_content'   => ik2_sync_rewrite_urls( $item['content']['raw'] ?? '', $ctx ),
		'post_excerpt'   => ik2_sync_rewrite_urls( $item['excerpt']['raw'] ?? '', $ctx ),
		'post_name'      => $item['slug'],
		'post_date'      => ik2_sync_mysql_date( $item['date'] ?? null ),
		'post_author'    => ik2_sync_author( (int) ( $item['author'] ?? 0 ), $ctx ),
		'post_parent'    => (int) ( $item['parent'] ?? 0 ),
		'menu_order'     => (int) ( $item['menu_order'] ?? 0 ),
		'comment_status' => $item['comment_status'] ?? 'closed',
		'ping_status'    => $item['ping_status'] ?? 'closed',
		'post_password'  => $item['password'] ?? '',
	];
	if ( ! empty( $item['date_gmt'] ) ) {
		$postarr['post_date_gmt'] = ik2_sync_mysql_date( $item['date_gmt'] );
	}

	if ( ! ik2_sync_write_post( $item, $postarr, $phase ) ) {
		return;
	}

	ik2_sync_post_meta( $id, $type, $item['meta'] ?? [] );
	ik2_sync_post_terms( $id, $item, $ctx );

	if ( ! empty( $item['template'] ) ) {
		update_post_meta( $id, '_wp_page_template', $item['template'] );
	} else {
		delete_post_meta( $id, '_wp_page_template' );
	}

	if ( ! empty( $item['featured_media'] ) ) {
		update_post_meta( $id, '_thumbnail_id', (int) $item['featured_media'] );
	} else {
		delete_post_meta( $id, '_thumbnail_id' );
	}

	if ( isset( $item['format'] ) ) {
		set_post_format( $id, 'standard' === $item['format'] ? '' : $item['format'] );
	}

	if ( isset( $item['sticky'] ) ) {
		$item['sticky'] ? stick_post( $id ) : unstick_post( $id );
	}

	ik2_sync_tally( $phase, $action );
}

/**
 * Files an attachment needs locally: uploads-relative path => production URL.
 */
function ik2_sync_media_files( array $item ): array {
	$marker = '/wp-content/uploads/';
	$source = (string) ( $item['source_url'] ?? '' );
	$pos    = strpos( $source, $marker );
	if ( false === $pos ) {
		return [];
	}

	$rel      = rawurldecode( substr( $source, $pos + strlen( $marker ) ) );
	$dir      = dirname( $rel );
	$base_url = dirname( $source );
	$files    = [ $rel => $source ];
	$details  = is_array( $item['media_details'] ?? null ) ? $item['media_details'] : [];

	foreach ( $details['sizes'] ?? [] as $size ) {
		if ( ! empty( $size['file'] ) ) {
			$files[ "$dir/{$size['file']}" ] = $size['source_url'] ?? "$base_url/" . rawurlencode( $size['file'] );
		}
	}

	// Big images are served as "-scaled"; the untouched upload sits beside them.
	if ( ! empty( $details['original_image'] ) ) {
		$files[ "$dir/{$details['original_image']}" ] = "$base_url/" . rawurlencode( $details['original_image'] );
	}

	return $files;
}

function ik2_sync_download( string $url, string $path ): ?string {
	wp_mkdir_p( dirname( $path ) );
	$tmp      = $path . '.part';
	$response = wp_remote_get(
		$url,
		[
			'timeout'  => 300,
			'stream'   => true,
			'filename' => $tmp,
		]
	);

	$code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
	if ( 200 !== $code ) {
		wp_delete_file( $tmp );
		return is_wp_error( $response ) ? $response->get_error_message() : "HTTP $code";
	}

	rename( $tmp, $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
	return null;
}

function ik2_sync_upsert_media( array $item, array $ctx ): void {
	$phase   = 'media';
	$id      = (int) $item['id'];
	$files   = ik2_sync_media_files( $item );
	$basedir = wp_upload_dir()['basedir'];
	$missing = [];

	foreach ( $files as $rel => $url ) {
		if ( $ctx['opts']['force'] || ! file_exists( "$basedir/$rel" ) ) {
			$missing[ $rel ] = $url;
		}
	}

	if ( ! $missing && ik2_sync_is_unchanged( $item, 'attachment', $ctx ) ) {
		ik2_sync_tally( $phase, 'skipped' );
		return;
	}

	$action = get_post( $id ) && get_post_type( $id ) === 'attachment' ? 'updated' : 'created';
	if ( $ctx['opts']['dry'] ) {
		ik2_sync_claim_id( $id, 'attachment', $phase, $ctx );
		ik2_sync_tally( $phase, $action );
		ik2_sync_tally( 'media files', 'to download', '', count( $missing ) );
		return;
	}

	foreach ( $missing as $rel => $url ) {
		$error = ik2_sync_download( $url, "$basedir/$rel" );
		ik2_sync_tally( 'media files', $error ? 'failed' : 'downloaded', $error ? "$rel: $error" : '' );
	}

	if ( ! $files || ! ik2_sync_claim_id( $id, 'attachment', $phase, $ctx ) ) {
		ik2_sync_tally( $phase, 'failed', "#$id {$item['slug']}: " . ( $files ? 'ID could not be freed' : 'source_url is outside /wp-content/uploads/' ) );
		return;
	}

	$rel     = array_key_first( $files );
	$postarr = [
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'post_mime_type' => $item['mime_type'] ?? '',
		'post_title'     => $item['title']['raw'] ?? '',
		'post_content'   => $item['description']['raw'] ?? '',
		'post_excerpt'   => $item['caption']['raw'] ?? '',
		'post_name'      => $item['slug'],
		'post_date'      => ik2_sync_mysql_date( $item['date'] ?? null ),
		'post_date_gmt'  => ik2_sync_mysql_date( $item['date_gmt'] ?? null ),
		'post_author'    => ik2_sync_author( (int) ( $item['author'] ?? 0 ), $ctx ),
		'post_parent'    => (int) ( $item['post'] ?? 0 ),
		'comment_status' => $item['comment_status'] ?? 'closed',
		'ping_status'    => $item['ping_status'] ?? 'closed',
		'guid'           => wp_upload_dir()['baseurl'] . '/' . $rel,
	];

	if ( ! ik2_sync_write_post( $item, $postarr, $phase ) ) {
		return;
	}

	update_post_meta( $id, '_wp_attached_file', $rel );

	$meta = is_array( $item['media_details'] ?? null ) ? $item['media_details'] : [];
	foreach ( $meta['sizes'] ?? [] as $name => $size ) {
		unset( $meta['sizes'][ $name ]['source_url'] );
	}
	if ( $meta ) {
		wp_update_attachment_metadata( $id, $meta );
	}

	if ( '' !== ( $item['alt_text'] ?? '' ) ) {
		update_post_meta( $id, '_wp_attachment_image_alt', wp_slash( $item['alt_text'] ) );
	} else {
		delete_post_meta( $id, '_wp_attachment_image_alt' );
	}

	ik2_sync_tally( $phase, $action );
}

function ik2_sync_prune_posts( string $type, array $keep_ids, string $phase, array $ctx ): void {
	$local = get_posts(
		[
			'post_type'        => $type,
			'post_status'      => 'any',
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		]
	);
	$trash = get_posts(
		[
			'post_type'        => $type,
			'post_status'      => 'trash',
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		]
	);

	foreach ( array_diff( array_merge( $local, $trash ), $keep_ids ) as $id ) {
		if ( ! $ctx['opts']['dry'] ) {
			'attachment' === $type ? wp_delete_attachment( $id, true ) : wp_delete_post( $id, true );
		}
		ik2_sync_tally( $phase, 'pruned' );
	}
}

function ik2_sync_settings( array $ctx ): void {
	[ $remote_settings ] = ik2_sync_get( $ctx['remote'], 'wp/v2/settings' );

	foreach ( IK2_SYNC_SETTINGS as $field => $option ) {
		if ( ! array_key_exists( $field, $remote_settings ) ) {
			continue;
		}
		$value   = $remote_settings[ $field ];
		$current = get_option( $option );
		if ( (string) $current === (string) $value ) {
			ik2_sync_tally( 'settings', 'skipped' );
			continue;
		}
		if ( ! $ctx['opts']['dry'] ) {
			update_option( $option, $value );
		}
		ik2_sync_tally( 'settings', 'updated', "$option: " . wp_json_encode( $current ) . ' -> ' . wp_json_encode( $value ) );
	}

	// Page IDs match production, but only once pages have been synced.
	foreach ( [ 'page_on_front', 'page_for_posts' ] as $option ) {
		$page_id = (int) ( $remote_settings[ $option ] ?? 0 );
		$pending = $ctx['opts']['dry'] && in_array( 'pages', $ctx['opts']['only'], true );
		if ( $page_id && ! $pending && 'page' !== get_post_type( $page_id ) ) {
			ik2_sync_tally( 'settings', 'failed', "$option points at page #$page_id, which isn't local yet. Run only=pages." );
		}
	}
}

function ik2_sync_print_summary( array $opts ): void {
	[ $tally, $notes ] = ik2_sync_tally();

	foreach ( $notes as $note ) {
		WP_CLI::log( '  ' . $note );
	}

	$columns = [ 'phase', 'created', 'updated', 'skipped', 'displaced', 'pruned', 'downloaded', 'to download', 'failed' ];
	$rows    = [];
	foreach ( $tally as $phase => $counts ) {
		$rows[] = array_merge( array_fill_keys( $columns, 0 ), $counts, [ 'phase' => $phase ] );
	}

	WP_CLI\Utils\format_items( 'table', $rows, $columns );

	$failed = array_sum( array_column( $rows, 'failed' ) );
	$label  = $opts['dry'] ? 'Dry run finished, nothing was written' : 'Sync finished';
	if ( $failed ) {
		WP_CLI::warning( "$label with $failed failure(s). Re-run to retry; unchanged items are skipped." );
	} else {
		WP_CLI::success( "$label." );
	}
}

function ik2_sync_main( array $args ): void {
	$opts   = ik2_sync_parse_args( $args );
	$remote = ik2_sync_remote_config();

	[ $index ] = ik2_sync_get( $remote, '/' );
	$prod_home = untrailingslashit( (string) ( $index['home'] ?? '' ) );
	ik2_sync_guard_local( $prod_home );

	[ $me ] = ik2_sync_get( $remote, 'wp/v2/users/me', [ 'context' => 'edit' ] );
	if ( empty( $me['capabilities']['edit_others_posts'] ) ) {
		WP_CLI::error( "Production user '{$me['username']}' cannot read other authors' drafts. Use an administrator or editor." );
	}

	WP_CLI::log( sprintf( '%s %s -> %s as %s', $opts['dry'] ? 'Dry run:' : 'Syncing', $prod_home, home_url(), $me['username'] ) );

	$admins = get_users(
		[
			'role'   => 'administrator',
			'number' => 1,
			'fields' => 'ID',
		]
	);
	wp_set_current_user( (int) ( $admins[0] ?? 0 ) );
	kses_remove_filters();
	wp_defer_term_counting( true );

	$ctx = [
		'opts'       => $opts,
		'remote'     => $remote,
		'prod_home'  => $prod_home,
		'local_home' => untrailingslashit( home_url() ),
		'users'      => ik2_sync_user_map( $remote ),
		'terms'      => [],
	];

	if ( array_intersect( [ 'terms', 'posts' ], $opts['only'] ) ) {
		$ctx['terms']['category'] = ik2_sync_terms( 'category', 'categories', $ctx );
		$ctx['terms']['post_tag'] = ik2_sync_terms( 'post_tag', 'tags', $ctx );
	}

	if ( in_array( 'media', $opts['only'], true ) ) {
		$items = ik2_sync_fetch_all( $remote, 'media', [], $opts['limit'] );
		foreach ( $items as $i => $item ) {
			ik2_sync_upsert_media( $item, $ctx );
			ik2_sync_progress( 'media', $i + 1, count( $items ) );
		}
		if ( $opts['prune'] ) {
			ik2_sync_prune_posts( 'attachment', array_map( 'intval', array_column( $items, 'id' ) ), 'media', $ctx );
		}
	}

	foreach ( ik2_sync_post_phases() as $phase => [ $type, $rest_base ] ) {
		if ( ! in_array( $phase, $opts['only'], true ) ) {
			continue;
		}
		$items = ik2_sync_fetch_all( $remote, $rest_base, [ 'status' => 'any' ], $opts['limit'] );
		foreach ( $items as $i => $item ) {
			ik2_sync_upsert_post( $item, $type, $phase, $ctx );
			ik2_sync_progress( $phase, $i + 1, count( $items ) );
		}
		if ( $opts['prune'] ) {
			ik2_sync_prune_posts( $type, array_map( 'intval', array_column( $items, 'id' ) ), $phase, $ctx );
		}
	}

	if ( in_array( 'settings', $opts['only'], true ) ) {
		ik2_sync_settings( $ctx );
	}

	wp_defer_term_counting( false );
	wp_cache_flush();

	ik2_sync_print_summary( $opts );
}

ik2_sync_main( $args );
