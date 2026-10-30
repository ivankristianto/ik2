<?php
/**
 * Authorship and entity signals: the byline, the updated date, Person and About schema.
 *
 * @package IK2
 */

declare(strict_types=1);

namespace IK2\Theme\Seo;

defined( 'ABSPATH' ) || exit;

const ABOUT_PAGE_SLUG = 'about';

const WORDMARK_TEXT = 'Ivan';

const EXTRA_PROFILES = [
	'github'        => 'GitHub profile URL',
	'wordpress_org' => 'WordPress.org profile URL',
];

/**
 * Register hooks owned by this module.
 */
function bootstrap(): void {
	add_filter( 'render_block_core/post-author-name', __NAMESPACE__ . '\\link_byline_to_about', 10, 2 );
	add_filter( 'render_block_core/site-title', __NAMESPACE__ . '\\short_wordmark', 10, 2 );
	add_filter( 'render_block_core/post-date', __NAMESPACE__ . '\\label_updated_date', 10, 2 );
	add_filter( 'wpseo_schema_person', __NAMESPACE__ . '\\point_person_at_about', 10, 2 );
	add_filter( 'wpseo_schema_article', __NAMESPACE__ . '\\use_full_name_for_article_author', 10, 2 );
	add_filter( 'user_contactmethods', __NAMESPACE__ . '\\add_profile_fields' );
	add_filter( 'wpseo_schema_person_social_profiles', __NAMESPACE__ . '\\add_schema_profiles' );
	add_filter( 'wpseo_schema_webpage', __NAMESPACE__ . '\\mark_about_as_profile_page', 10, 2 );
	add_action( 'template_redirect', __NAMESPACE__ . '\\redirect_author_archive' );
}

/**
 * The About page URL, the canonical home of the author entity.
 */
function about_url(): string {
	return home_url( '/' . ABOUT_PAGE_SLUG . '/' );
}

/**
 * Show the short name in the header wordmark; the Site Title keeps the full name for search results.
 *
 * @param string               $html  Rendered block.
 * @param array<string, mixed> $block Parsed block.
 */
function short_wordmark( string $html, array $block ): string {
	if ( ! str_contains( (string) ( $block['attrs']['className'] ?? '' ), 'ik-wordmark' ) ) {
		return $html;
	}

	return (string) preg_replace( '#(<a\\b[^>]*>)[^<]*(</a>)#', '${1}' . esc_html( WORDMARK_TEXT ) . '${2}', $html, 1 );
}

/**
 * Point the article byline at /about/ (not the author archive) and prefix it with "by".
 *
 * @param string               $html  Rendered block.
 * @param array<string, mixed> $block Parsed block.
 */
function link_byline_to_about( string $html, array $block ): string {
	$class = (string) ( $block['attrs']['className'] ?? '' );

	if ( ! str_contains( $class, 'ik-article__byline' ) ) {
		return $html;
	}

	$processor = new \WP_HTML_Tag_Processor( $html );

	if ( $processor->next_tag( [ 'tag_name' => 'a' ] ) ) {
		$processor->set_attribute( 'href', about_url() );
		$processor->set_attribute( 'rel', 'author' );
	}

	$html = $processor->get_updated_html();
	$pos  = strpos( $html, '<a ' );

	return false === $pos ? $html : substr_replace( $html, esc_html__( 'by', 'ik2' ) . ' ', $pos, 0 );
}

/**
 * Show the updated date only when the post changed on a later day, labelled "updated".
 *
 * Core already hides it when modified is not after published; a same-day fix is not news.
 *
 * @param string               $html  Rendered block.
 * @param array<string, mixed> $block Parsed block.
 */
function label_updated_date( string $html, array $block ): string {
	if ( 'modified' !== ( $block['attrs']['displayType'] ?? '' ) || '' === $html ) {
		return $html;
	}

	if ( get_the_modified_date( 'Y-m-d' ) <= get_the_date( 'Y-m-d' ) ) {
		return '';
	}

	$pos = strpos( $html, '<time' );

	return false === $pos ? $html : substr_replace( $html, esc_html__( 'updated', 'ik2' ) . ' ', $pos, 0 );
}

/**
 * The user's first and last name, for schema only; the display name stays short on the page.
 *
 * @param int $user_id User id.
 */
function full_name( int $user_id ): string {
	$user = get_userdata( $user_id );

	if ( ! $user instanceof \WP_User ) {
		return '';
	}

	return trim( $user->first_name . ' ' . $user->last_name );
}

/**
 * Use /about/ as the Person's url and the full name as its name.
 *
 * @param array<string, mixed> $data    Person graph piece.
 * @param object               $context Yoast meta tags context.
 * @return array<string, mixed>
 */
function point_person_at_about( $data, $context = null ): array {
	$data        = (array) $data;
	$data['url'] = about_url();
	$name        = full_name( (int) ( $context->site_user_id ?? 0 ) );

	if ( '' !== $name ) {
		$data['name'] = $name;
	}

	// Yoast adds the user's website (this site) to sameAs; sameAs is for other profiles.
	if ( isset( $data['sameAs'] ) && is_array( $data['sameAs'] ) ) {
		$home    = untrailingslashit( home_url() );
		$profile = [];

		foreach ( $data['sameAs'] as $url ) {
			if ( untrailingslashit( (string) $url ) !== $home ) {
				$profile[] = $url;
			}
		}

		$data['sameAs'] = $profile;
	}

	return $data;
}

/**
 * Give the Article author the same full name as the Person node it points to.
 *
 * @param array<string, mixed> $data    Article graph piece.
 * @param object               $context Yoast meta tags context.
 * @return array<string, mixed>
 */
function use_full_name_for_article_author( $data, $context ): array {
	$data = (array) $data;
	$name = full_name( (int) ( $context->post->post_author ?? 0 ) );

	if ( '' !== $name && isset( $data['author'] ) && is_array( $data['author'] ) ) {
		$data['author']['name'] = $name;
	}

	return $data;
}

/**
 * Add GitHub and WordPress.org fields to the user profile screen.
 *
 * @param array<string, string> $methods Contact methods.
 * @return array<string, string>
 */
function add_profile_fields( $methods ): array {
	return array_merge( (array) $methods, EXTRA_PROFILES );
}

/**
 * Include the extra profile fields in the Person's sameAs.
 *
 * @param string[] $profiles User meta keys Yoast reads.
 * @return string[]
 */
function add_schema_profiles( $profiles ): array {
	return array_merge( (array) $profiles, array_keys( EXTRA_PROFILES ) );
}

/**
 * Type the About page as a ProfilePage whose main entity is the site's Person.
 *
 * @param array<string, mixed> $data    WebPage graph piece.
 * @param object               $context Yoast meta tags context.
 * @return array<string, mixed>
 */
function mark_about_as_profile_page( $data, $context ): array {
	$data = (array) $data;

	if ( ! is_page( ABOUT_PAGE_SLUG ) || empty( $context->site_represents_reference ) ) {
		return $data;
	}

	$types = (array) ( $data['@type'] ?? 'WebPage' );

	if ( ! in_array( 'ProfilePage', $types, true ) ) {
		$types[] = 'ProfilePage';
	}

	$data['@type']      = $types;
	$data['mainEntity'] = $context->site_represents_reference;

	return $data;
}

/**
 * Send the single-author archive to /about/; /articles/ already lists every post.
 */
function redirect_author_archive(): void {
	if ( ! is_author() ) {
		return;
	}

	wp_safe_redirect( about_url(), 301 );
	exit;
}
