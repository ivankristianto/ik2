<?php
/**
 * Corpus tables: names and the dbDelta upgrade that creates them.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\Corpus;

defined( 'ABSPATH' ) || exit;

/**
 * Bump when the table definitions below change, so every site re-runs dbDelta.
 */
const CORPUS_DB_VERSION = '1';

const DB_VERSION_OPTION = 'ik2_corpus_db_version';

/**
 * Prefixed table name, e.g. table( 'documents' ) is wp_ik2_corpus_documents.
 *
 * @param string $name One of documents, chunks, vectors, related, runs.
 */
function table( string $name ): string {
	global $wpdb;

	return $wpdb->prefix . 'ik2_corpus_' . $name;
}

/**
 * Create or upgrade the tables when the stored version is behind.
 *
 * Runs on every request rather than on activation: the plugin is already
 * active in production and a redeploy never fires the activation hook.
 */
function maybe_upgrade(): void {
	if ( CORPUS_DB_VERSION === get_option( DB_VERSION_OPTION ) ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	dbDelta( schema() );

	update_option( DB_VERSION_OPTION, CORPUS_DB_VERSION, true );
}

/**
 * CREATE TABLE statements in the layout dbDelta parses: one column per line,
 * two spaces after PRIMARY KEY, no backticks. Column names avoid `rank` and
 * `vector`, which are reserved in MySQL 8 and MariaDB 11.7+.
 *
 * @return list<string>
 */
function schema(): array {
	global $wpdb;

	$collate = $wpdb->get_charset_collate();

	return [
		'CREATE TABLE ' . table( 'documents' ) . " (
id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
object_type varchar(20) NOT NULL DEFAULT 'post',
object_id bigint(20) unsigned NOT NULL,
title text NOT NULL,
url varchar(500) NOT NULL DEFAULT '',
embed_text mediumtext NOT NULL,
embed_hash char(64) NOT NULL DEFAULT '',
content_hash char(64) NOT NULL DEFAULT '',
status varchar(16) NOT NULL DEFAULT 'queued',
attempts tinyint(3) unsigned NOT NULL DEFAULT 0,
last_error text NULL,
queued_at datetime NOT NULL,
indexed_at datetime NULL DEFAULT NULL,
PRIMARY KEY  (id),
UNIQUE KEY object (object_type,object_id),
KEY status (status)
) {$collate};",
		'CREATE TABLE ' . table( 'chunks' ) . " (
id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
document_id bigint(20) unsigned NOT NULL,
chunk_index smallint(5) unsigned NOT NULL,
heading text NOT NULL,
anchor varchar(200) NOT NULL DEFAULT '',
text mediumtext NOT NULL,
token_estimate smallint(5) unsigned NOT NULL DEFAULT 0,
content_hash char(64) NOT NULL DEFAULT '',
PRIMARY KEY  (id),
UNIQUE KEY document_chunk (document_id,chunk_index)
) {$collate};",
		'CREATE TABLE ' . table( 'vectors' ) . " (
id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
level varchar(10) NOT NULL,
owner_id bigint(20) unsigned NOT NULL,
model varchar(100) NOT NULL,
prefix varchar(32) NOT NULL,
dims smallint(5) unsigned NOT NULL,
embedding blob NOT NULL,
content_hash char(64) NOT NULL,
embedded_at datetime NOT NULL,
PRIMARY KEY  (id),
UNIQUE KEY level_owner (level,owner_id)
) {$collate};",
		'CREATE TABLE ' . table( 'related' ) . " (
post_id bigint(20) unsigned NOT NULL,
position tinyint(3) unsigned NOT NULL,
related_id bigint(20) unsigned NOT NULL,
score float NOT NULL,
run_id bigint(20) unsigned NOT NULL,
PRIMARY KEY  (post_id,position)
) {$collate};",
		'CREATE TABLE ' . table( 'runs' ) . " (
id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
started_at datetime NOT NULL,
finished_at datetime NOT NULL,
model varchar(100) NOT NULL,
documents_embedded int(10) unsigned NOT NULL DEFAULT 0,
chunks_embedded int(10) unsigned NOT NULL DEFAULT 0,
removed int(10) unsigned NOT NULL DEFAULT 0,
failed int(10) unsigned NOT NULL DEFAULT 0,
PRIMARY KEY  (id)
) {$collate};",
	];
}
