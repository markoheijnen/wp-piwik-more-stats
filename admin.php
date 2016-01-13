<?php

class WP_Piwik_More_Stats_Admin {

	public function __construct() {
		$post_types = get_post_types( array( 'public' => true ), 'names' );

		foreach( $post_types as $name ) {
			add_filter( 'manage_edit-' . $name . '_columns', array( $this, 'columns' ), 10, 1 );
			add_filter( 'manage_edit-' . $name . '_sortable_columns', array( $this, 'sortable_columns' ) );

			add_action( 'manage_' . $name . '_posts_custom_column', array( $this, 'column_value' ), 10, 2 );
		}

		add_filter( 'request', array( $this, 'filter_table_view' ) );

		add_action( 'admin_print_styles', array( $this, 'admin_print_styles' ) );
	}

	
	public function columns( $columns ) {
		$columns['piwik_count'] = __( 'Count', 'wp-piwik-more-stats' );

		return $columns;
	}

	public function sortable_columns( $columns ) {
		$columns['piwik_count'] = 'piwik_count';

		return $columns;
	}

	public function column_value( $column, $post_id ) {
		if ( 'piwik_count' == $column ) {
			echo WP_Piwik_More_Stats_API::get_count_from_post( $post_id );
		}
	}


	public function filter_table_view( $vars ) {
		if ( array_key_exists( 'orderby', $vars ) ) {
		   if ( 'piwik_count' == $vars['orderby'] ) {
		        $vars['orderby']  = 'meta_value_num';
		        $vars['meta_key'] = 'piwik_post_count_730';
		   }
		}

		return $vars;
	}


	public function admin_print_styles() {
		$screen = get_current_screen();

		if ( 'edit' == $screen->base ) {
			echo '
				<style type="text/css">
				.column-piwik_count {
					width: 8%;
				}
				</style>
			' . PHP_EOL;
		}
	}

}