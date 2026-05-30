<?php
/**
 * Shared helpers for reading Project CPT meta in a normalized shape.
 *
 * Centralised so both the plugin's `ik2/project-card` block and the theme's
 * preview/archive blocks render identical card data.
 *
 * @package IK2\Plugin
 */

declare(strict_types=1);

namespace IK2\Plugin\PostTypes\Project;

defined( 'ABSPATH' ) || exit;

/**
 * Valid status values. Status normalisation is case-insensitive.
 *
 * @var array<int,string>
 */
const STATUS_VALUES = array( 'Active', 'Experiment', 'Archived' );

/**
 * Return a normalised array describing a project for card rendering.
 *
 * @param int $post_id Project post ID.
 * @return array{
 *     id:int,
 *     title:string,
 *     permalink:string,
 *     excerpt:string,
 *     status:string,
 *     tech:array<int,string>,
 *     links:array<int,array{label:string,href:string}>,
 *     learned:string,
 * }|null
 */
function get_card_data( int $post_id ): ?array {
	$post = get_post( $post_id );

	if ( ! $post || POST_TYPE !== $post->post_type ) {
		return null;
	}

	return array(
		'id'        => $post_id,
		'title'     => get_the_title( $post ),
		'permalink' => (string) get_permalink( $post ),
		'excerpt'   => wp_strip_all_tags( (string) get_the_excerpt( $post ) ),
		'status'    => normalize_status( (string) get_post_meta( $post_id, 'status', true ) ),
		'tech'      => parse_tech( (string) get_post_meta( $post_id, 'tech', true ) ),
		'links'     => parse_links( (string) get_post_meta( $post_id, 'links', true ) ),
		'learned'   => trim( (string) get_post_meta( $post_id, 'learned', true ) ),
	);
}

/**
 * Coerce a stored status value to one of STATUS_VALUES (case-insensitive).
 * Falls back to "Active" if blank, or returns the raw value untouched if unknown
 * so the editor surfaces user typos rather than silently rewriting them.
 *
 * @param string $value Raw status meta value.
 */
function normalize_status( string $value ): string {
	$trimmed = trim( $value );

	if ( '' === $trimmed ) {
		return 'Active';
	}

	foreach ( STATUS_VALUES as $allowed ) {
		if ( 0 === strcasecmp( $trimmed, $allowed ) ) {
			return $allowed;
		}
	}

	return $trimmed;
}

/**
 * Split a pipe-separated tech string into a clean list.
 *
 * @param string $value Raw tech meta value.
 * @return array<int,string>
 */
function parse_tech( string $value ): array {
	if ( '' === $value ) {
		return array();
	}

	$parts = array_map( 'trim', explode( '|', $value ) );

	return array_values( array_filter( $parts, __NAMESPACE__ . '\\is_non_empty_string' ) );
}

/**
 * Predicate for array_filter — true when the string is non-empty.
 *
 * @param string $value Candidate string.
 */
function is_non_empty_string( string $value ): bool {
	return '' !== $value;
}

/**
 * Parse a pipe-separated "Label::URL" links string.
 *
 * @param string $value Raw links meta value.
 * @return array<int,array{label:string,href:string}>
 */
function parse_links( string $value ): array {
	if ( '' === $value ) {
		return array();
	}

	$out = array();
	foreach ( explode( '|', $value ) as $pair ) {
		$pair = trim( $pair );
		if ( '' === $pair ) {
			continue;
		}

		$parts = array_map( 'trim', explode( '::', $pair, 2 ) );
		if ( count( $parts ) !== 2 || '' === $parts[0] || '' === $parts[1] ) {
			continue;
		}

		$out[] = array(
			'label' => $parts[0],
			'href'  => $parts[1],
		);
	}

	return $out;
}
