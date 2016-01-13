<?php

/**
 * Manage the object cache.
 *
 * ## EXAMPLES
 *
 *     wp piwik-more-stats get 1
 *
 *     wp piwik-more-stats regenerate post
 *
 *     wp piwik-more-stats regenerate --all
 */
class WP_Piwik_More_Stats_CLI extends WP_CLI_Command {

	public function __construct() {
		$this->fetcher = new \WP_CLI\Fetchers\Post;
	}

	/**
	 * Get views from a post
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post to get.
	 *
	 */
	public function get( $args, $assoc_args ) {
		$post = $this->fetcher->get_check( $args[0] );
		return WP_Piwik_More_Stats_API::get_count_from_post( $post );
	}

	/**
	 * Get views from a post
	 *
	 * ## OPTIONS
	 *
	 * [<post_type>]
	 * : The ID of the post to get.
	 *
	 * [--all]
	 * : Loop over all posts
	 */
	public function regenerate( $args, $assoc_args ) {
		$args = array(
			'posts_per_page' => -1,
		);

		if ( ! isset( $assoc_args['all'] ) && empty( $args ) ) {
			\WP_CLI::error( __( "Please specify one or more post types, or use --all.", 'wp-piwik-more-stats' ) );
		}

		if ( isset( $assoc_args['all'] ) ) {
			$args['post_type'] = get_post_types( array( 'public' => true ), 'names' );
		}
		else {
			$args['post_type'] = $args;
		}

		$posts = get_posts( $args );

		foreach( $posts as $post ) {
			WP_Piwik_More_Stats_API::get_count_from_post( $post, true );
		}

		// Let the user know the results.
		$num_to_update = count( $posts );

		\WP_CLI::success( __( "Updated $num_to_update items.", 'wp-piwik-more-stats' ) );
	}

}