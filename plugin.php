<?php
/*
Plugin Name: WP Video SEO
Version: 1.0.4
Plugin URI: http://wp-ecommerce.net/?p=2049
Author: wpecommerce
Author URI: http://wp-ecommerce.net/
Description: Video SEO plugin for WordPress
*/

class WordPress_Video_SEO_Plugin {

    var $plugin_version = '1.0.4';
	function __construct() {

		$this->start();

	}

	function start() {

		$this->hooks();

	}

	function hooks() {

		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );

		add_action( 'save_post', array( $this, 'save_sitemap_on_save_post' ) );

	}


	function add_settings_page() {

		add_submenu_page( 'options-general.php', 'WP Video SEO', 'WP Video SEO', 'manage_options', 'wp-video-seo-settings', array( $this, 'settings_page_view' ) );

	}

	function settings_page_view() {

		$message = false;

		if ( isset( $_POST['submit'] ) ) {
			
			$result = $this->save_sitemap();

			if ( $result )
				$message = __ ( "Sitemap generated successfully", 'wsmp' );
			else
				$message = __( "Unable to generate sitemap, Please check file permissions", 'wsmp' );

		}

		include 'settings_page_view.php';

	}

	function save_sitemap() {

		$view = $this->sitemap_view();

		$file_path = ABSPATH . DIRECTORY_SEPARATOR . 'sitemap-video.xml';

		if ( ! file_put_contents( $file_path , $view ) )
			return FALSE;

		return TRUE;

	}

	function save_sitemap_on_save_post( $post_id ) {

		$post_type = get_post_type( $post_id );

		if ( $post_type == 'page' || $post_type == 'post' )
			$this->save_sitemap();

	}

	function sitemap_view() {

		$videos = $this->get_sitemap_data();

		ob_start();

		include 'sitemap_view.php';

		$sitemap_content = ob_get_contents();
		ob_end_clean();

		return $sitemap_content;

	}

	function get_sitemap_data() {

		global $wpdb;

		$posts = $wpdb->get_results ( "SELECT id, post_title, post_content, post_date_gmt, post_excerpt, post_author
    		FROM $wpdb->posts WHERE post_status = 'publish'
    		AND (post_type = 'post' OR post_type = 'page')
    		AND post_content LIKE '%youtube.com%'
    		ORDER BY post_date DESC" );

		if ( empty( $posts ) ) return array();

		$videos = array();
		$ids = array();
		$matches = array();

		foreach ( $posts as $post ) {

			$excerpt = ($post->post_excerpt != "") ? $post->post_excerpt : $post->post_title ;

			if ( preg_match_all (
				"/youtube.com\/(v\/|watch\?v=|embed\/)([a-zA-Z0-9\-_]*)/"
				, $post->post_content
				, $matches
				, PREG_SET_ORDER) ) {

				foreach( $matches as $match ) {

					$id = $match[2];

					if ( in_array( $id, $ids ) )
						continue;

					array_push( $ids, $id );

					$video['id'] = $id;
					$video['permalink'] = get_permalink( $post->id );
					$video['description'] = htmlspecialchars( $excerpt );
					$video['title'] = htmlspecialchars( $post->post_title );
					$video['publish_date'] = date (DATE_W3C, strtotime ($post->post_date_gmt));
					$video['thumbnail'] = "http://i.ytimg.com/vi/$id/hqdefault.jpg";

					$video['player_loc'] = "http://www.youtube.com/v/$id";

					$post_tags = get_the_tags( $post->id );

					if ( ! is_array( $post_tags ) )
						$post_tags = array();
 					
					$tag_count = count( $post_tags );

					$video['tags'] = $post_tags;

					$video['tag_count'] = $tag_count;

					$post_categories = get_the_category( $post->id );

					if ( ! is_array( $post_categories ) )
						$post_categories = array();

					$video['categories'] = $post_categories;

					$youtube_data = $this->youtube_only_data( $id );

					$video['duration'] = $youtube_data['duration'];

					$video['view_count'] = $youtube_data['view_count'];

					$video['ratings'] = $youtube_data['ratings'];

					if ( isset( $youtube_data['title'] ) )
						$video['title'] = htmlspecialchars($youtube_data['title']);

					if ( isset( $youtube_data['description'] ) )
						$video['description'] = htmlspecialchars($youtube_data['description']);

					$author_id = $post->post_author;

					$author_name = get_the_author_meta( 'first_name', $author_id ) . ' ' . get_the_author_meta( 'last_name', $author_id );

					$video['author'] = $author_name;

					$videos[] = $video;
				}

			}
		}

		return $videos;


	}

	function youtube_only_data( $video_id ) {

		$api_url = "https://gdata.youtube.com/feeds/api/videos/$video_id?v=2&alt=json";

		$request = wp_remote_get( $api_url );

		if ( is_wp_error( $request ) )
			return array( 'duration' => 0, 'ratings' => 0, 'view_count' => 0  );

		if ( $request['response']['code'] !== 200 )
			return array( 'duration' => 0, 'ratings' => 0, 'view_count' => 0  );

		$data = json_decode( $request['body'] );

		$duration = $data->entry->{'media$group'}->{'media$content'}[0]->duration;

		$view_count = $data->{'entry'}->{'yt$statistics'}->{'viewCount'};

		$ratings = $data->{'entry'}->{'gd$rating'}->{'average'};

		$title = $data->{'entry'}->title->{'$t'};

		$description = $data->{'entry'}->{'media$group'}->{'media$description'}->{'$t'};


		return array(
				'duration' => $duration,
				'ratings' => $ratings,
				'view_count' => $view_count,
				'title' => $title,
				'description' => $description
			);
	}
}

new WordPress_Video_SEO_Plugin;