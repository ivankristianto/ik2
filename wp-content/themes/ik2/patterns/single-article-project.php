<?php
/**
 * Title: Single article, project
 * Slug: ik2/single-article-project
 * Categories: ik2-page
 * Inserter: no
 * Description: Links the post to each project it is tagged with in the post sidebar, under the tags. Prints nothing for untagged posts. Depends on the current post, so it is hidden from the manual inserter.
 *
 * @package IK2
 */

use IK2\Plugin\PostTypes\Project;

$ik2_projects = function_exists( 'IK2\\Plugin\\PostTypes\\Project\\projects_for_post' ) ? Project\projects_for_post( get_queried_object_id() ) : [];

if ( empty( $ik2_projects ) ) {
	return;
}
?>
<!-- wp:html -->
<aside class="ik-article-project" aria-label="<?php esc_attr_e( 'Project', 'ik2' ); ?>">
<?php foreach ( $ik2_projects as $ik2_project ) : ?>
	<?php $ik2_card = Project\get_card_data( $ik2_project->ID ); ?>
	<div class="ik-article-project__item">
		<p class="ik-article-project__label"><?php esc_html_e( 'project', 'ik2' ); ?></p>
		<a class="ik-article-project__name" href="<?php echo esc_url( (string) get_permalink( $ik2_project ) ); ?>"><?php echo esc_html( get_the_title( $ik2_project ) ); ?></a>
		<?php if ( null !== $ik2_card && '' !== $ik2_card['excerpt'] ) : ?>
		<p class="ik-article-project__blurb"><?php echo esc_html( $ik2_card['excerpt'] ); ?></p>
		<?php endif; ?>
	</div>
<?php endforeach; ?>
</aside>
<!-- /wp:html -->
