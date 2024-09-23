<?php
const WPSCX_ERROR_FILE = '/errors.pws';

class Wpscx_Grammar_Scanner extends wpscx_scanner {
    
	function check_grammar( $to_check, $error_list ) {
		global $wpdb;
		global $wpgc_options;
		$score = 0;

		foreach ( $error_list as $error ) {
			$score += preg_match_all( "/\b" . $error . "\b/i", $to_check );
			if ( preg_match_all( "/\b" . $error . "\b/i", $to_check ) >= 1 ) {
			}
		}

		return $score;
	}

	function check_spacing( $content ) {
		$count = 0;

		preg_match_all( '/(\.|\?|\!|\,|\:|\;)([a-z]|[A-Z])/', $content, $matches );
		$count += sizeof( (array) $matches );
		preg_match_all( '/[A-Z].[A-Z]/', $content, $matches );

		return $count;
	}


	function check_pages() {
		$start     = round( microtime( true ), 5 );
		$sql_count = 0;
		wpscx_set_global_vars();
		$page_list = null;
		global $wpgc_scan_delay;
		global $wpsc_settings;
		global $wpgc_settings;
		global $wpdb;
		global $wpscx_ent_included;
		global $wpscx_base_page_max;
		$options_table   = $wpdb->prefix . 'spellcheck_grammar_options';
		$wpsc_options    = $wpdb->prefix . 'spellcheck_options';
		$page_table      = $wpdb->prefix . 'posts';
		$ignore_table    = $wpdb->prefix . 'spellcheck_ignore';
		$page_count      = 0;
		$error_count     = 0;
		$pro_error_count = 0;
		set_time_limit( 6000 );

		$max_pages = $wpsc_settings[138]->option_value;
		//$max_pages = 5000;
		if ( ! $wpscx_ent_included ) {
			$max_pages = $wpscx_base_page_max;
		}

		$results_table = $wpdb->prefix . 'spellcheck_grammar';

		if ( 'true' === $wpsc_settings[136]->option_value ) {
			$post_status = " AND (post_status='publish' OR post_status='draft')"; } else {
			$post_status = " AND post_status='publish'"; }

			$total_pages = $max_pages;
			if ( 0 === $total_pages ) {
				$total_pages = PHP_INT_MAX;
			}
			$page_list = SplFixedArray::fromArray( $wpdb->get_results( "SELECT post_content, post_title, ID FROM $page_table WHERE post_type='page'$post_status LIMIT $max_pages" ) );
			$sql_count++;

			$loc      = plugins_url( WPSCX_ERROR_FILE, __FILE__ );
			$contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

			$contents   = str_replace( "\r\n", "\n", $contents );
			$error_list = explode( "\n", $contents );

			$list_count = 0;
			$error_hold = new SplFixedArray( 1 );

			$ignore_pages = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );

			for ( $x = 0;$x < $page_list->getSize();$x++ ) {
				$words_content = $page_list[ $x ]->post_content;

				$ignore_flag = 'false';
				foreach ( $ignore_pages as $ignore_check ) {
					if ( strtoupper( trim( $page_list[ $x ]->post_title ) ) === strtoupper( trim( $ignore_check->keyword ) ) ) {
						$ignore_flag = 'true';
					}
				}
				if ( 'true' === $ignore_flag ) {
					continue; }

				if ( strpos( $words_content, '[fep_submission_form]' ) ) {
					continue;
				}
				$words_content = do_shortcode( $words_content );
				$words_content = wpscx_content_filter( $words_content );
				$words_content = wpgcx_clean_all( $words_content, $wpsc_settings );

				$score = $this->check_grammar( $words_content, $error_list );
				if ( $page_count < $total_pages ) {
					if ( null !== $page_list[ $x ]->ID ) {
						//wpgcx_sql_insert(array("page_id" => $page_list[$x]->ID, "grammar" => $score));
						$hold    = new SplFixedArray( 2 );
						$hold[0] = $page_list[ $x ]->ID;
						$hold[1] = $score;

						$error_hold->setSize( $error_hold->getSize() + 1 ); //Increase the size of the main error array by 1
						$error_hold[ $list_count ] = $hold;
						$list_count++;
					}
						$error_count += $score;
				} else {
					$pro_error_count += $score;
				}
				if ( $page_count < $total_pages ) {
					$page_count++;
				}
				unset( $page_list[ $x ] );
			}

			if ( $total_pages > $max_pages ) {
				$count = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name ='pro_error_count';" );
				$sql_count++;
				$pro_error_count += intval( $count[0]->option_value );
				$wpdb->update( $options_table, array( 'option_value' => $pro_error_count ), array( 'option_name' => 'pro_error_count' ) );
				$sql_count++;
			}

			$wpdb->update( $options_table, array( 'option_value' => $page_count ), array( 'option_name' => 'pages_scanned' ) );
			$sql_count++;
			$result = $wpdb->get_results( "SELECT * FROM $options_table WHERE option_name='last_scan_errors'" );
			$sql_count++;
			$error_results = $result[0]->option_value;
			$wpdb->update( $options_table, array( 'option_value' => $error_count + $error_results ), array( 'option_name' => 'last_scan_errors' ) );
			$sql_count++;

			//$wpdb->update($options_table, array('option_value' => $total_time), array('option_name' => 'last_scan_time'));
			$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'scan_running' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'page_running' ) );
			$sql_count++;

			wpgcx_sql_insert( $error_hold );

			$end = round( microtime( true ), 5 );
			wpscx_print_debug( 'Grammar Page Content', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), $error_count );
	}

	function check_posts() {
		$start     = round( microtime( true ), 5 );
		$sql_count = 0;
		wpscx_set_global_vars();
		$post_list = null;
		global $wpgc_scan_delay;
		global $wpsc_settings;
		global $wpgc_settings;
		global $wpdb;
		global $wpscx_ent_included;
		global $wpscx_base_page_max;
		$wpsc_options    = $wpdb->prefix . 'spellcheck_options';
		$options_table   = $wpdb->prefix . 'spellcheck_grammar_options';
		$ignore_table    = $wpdb->prefix . 'spellcheck_ignore';
		$post_table      = $wpdb->prefix . 'posts';
		$post_count      = 0;
		$error_count     = 0;
		$pro_error_count = 0;
		$start_time      = time();
		set_time_limit( 6000 );

		$results_table = $wpdb->prefix . 'spellcheck_grammar';

		$max_pages = $wpsc_settings[138]->option_value;
		//$max_pages = 5000;
		if ( ! $wpscx_ent_included ) {
			$max_pages = $wpscx_base_page_max;
		}

		//Get a list of all the custom post types
		$post_types                     = get_post_types();
						$post_type_list = 'AND (';
		foreach ( $post_types as $type ) {
			if ( 'revision' !== $type && 'page' !== $type && 'slider' !== $type && 'attachment' !== $type && 'optionsframework' !== $type && 'product' !== $type && 'wpsc-product' !== $type && 'wpcf7_contact_form' !== $type && 'nav_menu_item' !== $type && 'gal_display_source' !== $type && 'lightbox_library' !== $type && 'wpcf7s' !== $type ) {
							$post_type_list .= "post_type='$type' OR ";
			}
		}
						$post_type_list  = trim( $post_type_list, ' OR ' );
						$post_type_list .= ')';

		if ( 'true' === $wpsc_settings[137]->option_value ) {
			$post_status = " AND (post_status='publish' OR post_status='draft')"; } else {
			$post_status = " AND post_status='publish'"; }

			$total_pages = $max_pages;
			if ( 0 === $total_pages ) {
				$total_pages = PHP_INT_MAX;
			}
			$posts_list = SplFixedArray::fromArray( $wpdb->get_results( "SELECT post_content, post_title, ID FROM $post_table WHERE post_type = 'post'" . $post_status . $post_type_list . ' LIMIT ' . $max_pages ) );
			$sql_count++;

			$loc      = plugins_url( WPSCX_ERROR_FILE, __FILE__ );
			$contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

			$contents   = str_replace( "\r\n", "\n", $contents );
			$error_list = explode( "\n", $contents );

			$list_count = 0;
			$error_hold = new SplFixedArray( 1 );

			$ignore_pages = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );

			for ( $x = 0;$x < $posts_list->getSize();$x++ ) {
					$words_content = $posts_list[ $x ]->post_content;

					$ignore_flag = 'false';
				foreach ( $ignore_pages as $ignore_check ) {
					if ( strtoupper( trim( $posts_list[ $x ]->post_title ) ) === strtoupper( trim( $ignore_check->keyword ) ) ) {
						$ignore_flag = 'true';
					}
				}
				if ( 'true' === $ignore_flag ) {
					continue; }

				if ( strpos( $words_content, '[fep_submission_form]' ) ) {
					continue;
				}
					$words_content = do_shortcode( $words_content );
					$words_content = wpscx_content_filter( $words_content );
					$words_content = wpgcx_clean_all( $words_content, $wpsc_settings );

					$score = $this->check_grammar( $words_content, $error_list );

				if ( $post_count < $total_pages ) {
					if ( null !== $posts_list[ $x ]->ID ) {
						//wpgcx_sql_insert(array("page_id" => $posts_list[$x]->ID, "grammar" => $score));
						$hold    = new SplFixedArray( 2 );
						$hold[0] = $posts_list[ $x ]->ID;
						$hold[1] = $score;

						$error_hold->setSize( $error_hold->getSize() + 1 ); //Increase the size of the main error array by 1
						$error_hold[ $list_count ] = $hold;
						$list_count++;
					}
						$error_count += $score;
				} else {
						$pro_error_count += $score;
				}
				if ( $post_count < $total_pages ) {
					$post_count++;
				}
					unset( $posts_list[ $x ] );
			}

			if ( $total_pages > $max_pages ) {
					$count = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name ='pro_error_count';" );
				$sql_count++;
					$pro_error_count += intval( $count[0]->option_value );
					$wpdb->update( $options_table, array( 'option_value' => $pro_error_count ), array( 'option_name' => 'pro_error_count' ) );
				$sql_count++;
			}

			$wpdb->update( $options_table, array( 'option_value' => $post_count ), array( 'option_name' => 'posts_scanned' ) );
			$sql_count++;
			$result = $wpdb->get_results( "SELECT * FROM $options_table WHERE option_name='last_scan_errors'" );
			$sql_count++;
			$error_results = $result[0]->option_value;
			$wpdb->update( $options_table, array( 'option_value' => $error_count + $error_results ), array( 'option_name' => 'last_scan_errors' ) );
			$sql_count++;

			$end_time   = time();
			$total_time = wpscx_time_elapsed( $end_time - $start_time );
			//$wpdb->update($options_table, array('option_value' => $total_time), array('option_name' => 'last_scan_time'));
			$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'scan_running' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'post_running' ) );
			$sql_count++;

			wpgcx_sql_insert( $error_hold );

			$end = round( microtime( true ), 5 );
			wpscx_print_debug( 'Grammar Post Content', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), $error_count );
	}

	function scan_site() {
		wpgcx_set_global_vars();
		global $wpdb;
		global $wpgc_options;
		$options_table = $wpdb->prefix . 'spellcheck_grammar_options';
		wpgcx_clear_results(); //Clear out results table in preparation for a new scan

		//$start = time();
		//$wpdb->update($options_table, array("option_value" => $start), array("option_name" => "scan_start_time")); $sql_count++;

		$this->check_pages();
		$this->check_posts();
	}

	function scan_individual( $page_id ) {
		wpscx_set_global_vars();
		global $wpgc_options;
		global $wpdb;
		$results_table = $wpdb->prefix . 'spellcheck_grammar';
                
                $loc      = plugins_url( WPSCX_ERROR_FILE, __FILE__ );
                $contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

                $contents   = str_replace( "\r\n", "\n", $contents );
                $error_list = explode( "\n", $contents );

		$post = get_post( $page_id ); //Get the post/page

		$words_content = $post->post_content; //Get the content from the postpage

		//Clean up the content
		$words_content = do_shortcode( $words_content );
		$words_content = wpscx_content_filter( $words_content );
		$words_content = wpgcx_clean_all( $words_content, null );

		$score = $this->check_grammar( $words_content, $error_list ); //Get the grammar scores

		$error_hold    = new SplFixedArray( 2 );
		$hold          = new SplFixedArray( 2 );
		$hold[0]       = $post->ID;
		$hold[1]       = $score;
                $error_hold[0] = $hold;

		wpgcx_sql_insert( $error_hold ); //Insert into database for the on page editor
	}
}
