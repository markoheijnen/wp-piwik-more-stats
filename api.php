<?php

class WP_Piwik_More_Stats_API {

	public static $timeout = 86400; //in seconds -> DAY_IN_SECONDS


	public static function get_top_pages( $range = 7, $amount = 20,  $post_type = 'post' ) {
		$cache_key = $post_type . '-' . $range  . '-' . $amount;

		if ( false === ( $posts = self::get_cache_value( $cache_key, 'piwik_most_viewed' ) ) ) {
			$parameters = array(
				'period'             => 'range',
				'date'               => date( 'Y-m-d', strtotime( '-' . $range . ' day' ) ) . ',' . date( 'Y-m-d', strtotime('+1 day') ),
				'flat'               => 1,
				'filter_limit'       => $amount + 20,
				'filter_sort_order'  => 'DESC',
				'filter_sort_column' => 'nb_hits'
			);

			$data  = self::request( 'Actions.getPageUrls', $parameters );
			$posts = array();

			if ( ! is_wp_error( $data ) && ! is_object( $data ) ) {
				foreach ( $data as $item ) {
					$post_id = url_to_postid( $item['url'] );

					if ( $post_id ) {
						$post = get_post( $post_id );

						if ( $post->post_type == $post_type ) {
							$posts[] = $post_id;
						}
					}

					if ( count( $posts ) >= $amount ) {
						break;
					}
				}
			}

			self::set_cache_value( $cache_key, $posts, 'piwik_most_viewed', self::$timeout );
		}

		return $posts;
	}


	public static function get_count_from_post( $post_or_post_id, $force_update = false, $day_range = 730 ) {
		$post = get_post( $post_or_post_id );

		$count   = get_post_meta( $post->ID, 'piwik_post_count_' . $day_range, true );
		$timeout = get_post_meta( $post->ID, 'piwik_post_count_' . $day_range . '_timeout', true );

		if ( $count === '' || $force_update || date('U') > $timeout ) {
			$count = 0;
			$url   = get_permalink( $post );

			$data = self::get_page_count( $url, $day_range );

			if ( $data && isset( $data[0]['nb_hits'] ) ) {
				$count = $data[0]['nb_hits'];
			}

			update_post_meta( $post->ID, 'piwik_post_count_' . $day_range, $count );
			update_post_meta( $post->ID, 'piwik_post_count_' . $day_range . '_timeout', (int) date('U') + (int) self::$timeout );
		}

		return $count;
	}



	private static function get_page_count( $url, $day_range = 730 ) {
		$day_range = absint( $day_range );

		$parameters = array(
			'period'  => 'range',
			'date'    => 'last' . $day_range,
			'pageUrl' => urlencode( $url ),
		);

		$data = self::request( 'Actions.getPageUrl', $parameters );

		return $data;
	}

	private static function request( $method, $parameters ) {
		$request  = \WP_Piwik\Request::register( $method, $parameters );
		$response = $GLOBALS['wp-piwik']->request( $request );

		return $response;
	}


	private static function get_cache_value( $key, $group ) {
		if ( wp_using_ext_object_cache() ) {
			return wp_cache_get( $key, $group );
		}
		else {
			return get_transient( $group . '_' . $key );
		}
	}

	private static function set_cache_value( $key, $value, $group, $expiration ) {
		if ( wp_using_ext_object_cache() ) {
			return wp_cache_set( $cache_key, $value, $this->cache_group, $expiration );
		}
		else {
			return set_transient( $group . '_' . $key, $value, $expiration );
		}
	}

	private static function delete_cache_value( $group ) {
		if ( ! wp_using_ext_object_cache() ) {
			global $wpdb;

			$sql = "
				delete from {$wpdb->options}
				where option_name like '_transient_{$group}%' or option_name like '_transient_timeout_{$group}%'
			";

			return $wpdb->query( $sql );
		}

		return false;
	}

}