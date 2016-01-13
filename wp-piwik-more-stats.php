<?php
/**
 * @package Piwik_More_Stats
 * @version 1.0
 */
/*
	Plugin Name:  WP-Piwik More Stats
	Plugin URI:   http://www.mijnpress.nl
	Description:  A plugin that shows more insights of your Piwik stats. Needs WP-Piwik for collecting the stats. 
	Version:      1.0
	Author:       Ramon Fincken
	Author URI:   http://www.mijnpress.nl
	License:      GPL2
	TextDomain:   wp-piwik-more-stats
	Build by:     Marko Heijnen
*/

class WP_Piwik_More_Stats {
	const version = '1.0';

	public function __construct() {
		register_activation_hook( __FILE__, array( $this , 'activate' ) );

		add_action( 'plugins_loaded', array( $this, 'load_plugin' ) );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$this->load_cli();
		}
	}

	public function activate() {
		// Check if WP-Piwik is installed
		if ( ! isset( $GLOBALS['wp-piwik'] ) ) {
			if ( is_plugin_inactive( 'wp-piwik/wp-piwik.php' ) ) {
				 add_action( 'update_option_active_plugins', array( $this, 'activate_piwik' ) );
			} else {
				deactivate_plugins( plugin_basename( __FILE__ ) );
				wp_die( 'This plugin requires WP-Piwik. Install that one first.', 'Install WP-Piwik', array( 'back_link' => true ) );
			}
		}
	}

	public function activate_piwik() {
		remove_action( 'update_option_active_plugins', array( $this, 'activate_piwik' ) );
		activate_plugin('wp-piwik/wp-piwik.php');
	}


	public function load_plugin() {
		if ( ! isset( $GLOBALS['wp-piwik'] ) ) {
			return false;
		}

		include dirname( __FILE__ ) . '/api.php';
		
		if ( is_admin() ) {
			include dirname( __FILE__ ) . '/admin.php';
			new WP_Piwik_More_Stats_Admin();
		}
		else {
			add_action( 'shutdown', array( $this, 'refresh_post_cache' ) );
		}

		return true;
	}

	public function load_cli() {
		include dirname( __FILE__ ) . '/cli.php';

		WP_CLI::add_command( 'piwik-more-stats', 'WP_Piwik_More_Stats_CLI' );
	}

	public function refresh_post_cache() {
		if ( is_singular() ) {
			WP_Piwik_More_Stats_API::get_count_from_post( get_the_ID() );
		}
	}

}

$GLOBALS['wp_piwik_more_stats'] = new WP_Piwik_More_Stats;
