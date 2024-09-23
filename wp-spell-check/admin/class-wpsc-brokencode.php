<?php

class Wpscx_Broken_Code_Scanner extends wpscx_scanner {
	function clean_all( $content, $wpsc_settings ) {
                $content = wpscx_script_cleanup( $content );
                
		if ( 'true' === $wpsc_settings[23]->option_value ) {
				$content = wpscx_email_cleanup( $content );
		}

		if ( 'true' === $wpsc_settings[24]->option_value ) {
				$content = wpscx_website_cleanup( $content );
		}

		return $content;
	}

	function wpscx_scan_all_eps() {
		$start     = round( microtime( true ), 5 );
		$sql_count = 0;
		$page_list = null;
		global $wpscx_scan_delay;
		global $wpscx_ent_included;
		global $wpsc_settings;
                $is_running = null;
		if ( sizeof( (array) $wpsc_settings ) < 1 ) {
			wpscx_set_global_vars();
		}

		ini_set( 'memory_limit', '1024M' ); //Sets the PHP memory limit
		set_time_limit( 600 );
		global $wpdb;

		$table_name    = $wpdb->prefix . 'spellcheck_html';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$page_table    = $wpdb->prefix . 'posts';

		$max_pages = intval( $wpsc_settings[138]->option_value );

		$total_words = 0;
		$page_count  = 0;
		$post_count  = 0;
		$word_count  = 0;
		$error_count = 0;

		wpscx_set_global_vars();

		if ( 'true' === $wpsc_settings[136]->option_value ) {
			$post_status = " AND (post_status='publish' OR post_status='draft')"; } else {
			$post_status = " AND post_status='publish'"; }

			$page_list = SplFixedArray::fromArray( $wpdb->get_results( "SELECT post_content, post_title, ID, post_type FROM $page_table WHERE (post_type='page' OR post_type='post')$post_status" ) );
			$sql_count++;

			if ( ! $is_running ) {
				$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
				$sql_count++;
				$start_time = time();
			}
			$ind_start_time = time();

			$max_time = ini_get( 'max_execution_time' );

			$divi_check = wp_get_theme();

				$ignore_pages = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );

			global $wpscx_ignore_list;
			global $wpscx_dict_list;
			global $wpsc_settings;
			$error_list = new SplFixedArray( 1 );

			for ( $x = 0;$x < $page_list->getSize();$x++ ) {
				if ( 'page' === $page_list[ $x ]->post_type ) {
					$page_count++;
				} else {
						$post_count++; }

						$ignore_flag = 'false';
				foreach ( $ignore_pages as $ignore_check ) {
					if ( strtoupper( trim( $page_list[ $x ]->post_title ) ) === strtoupper( trim( $ignore_check->keyword ) ) ) {
						$ignore_flag = 'true';
					}
				}
				if ( 'true' === $ignore_flag ) {
					continue; }

				$words_content = $page_list[ $x ]->post_content;
				if ( strpos( $words_content, '[fep_submission_form]' ) ) {
					continue;
				}

				$words_content = wpscx_content_filter( $words_content );

				$words_content = do_shortcode( $words_content );
				$words_content = $this->clean_all( $words_content, $wpsc_settings );

				$debug_msg = preg_match_all( '/&lt;.+&gt;/', $words_content, $html_errors );

				if ( sizeof( (array) $html_errors ) !== 0 ) {
					foreach ( $html_errors as $html_error ) {
						foreach ( $html_error as $single_error ) {
							if ( '' != $single_error ) {
								$hold    = new SplFixedArray( 4 );
								$hold[0] = $single_error;
								$hold[1] = $page_list[ $x ]->post_title;
								$hold[2] = 0;
								$hold[3] = 'Broken HTML';

								$error_list->setSize( $error_list->getSize() + 1 ); //Increase the size of the main error array by 1
								$error_list[ $error_count ] = $hold;

								$error_count++;
							}
						}
					}
				}

				preg_match_all( '/\[.*?\]/', $words_content, $shortcode_errors );

				for ( $y = 0;$y <= sizeof( $shortcode_errors[0] );$y++ ) {
                                        if ( isset( $shortcode_errors[0][ $y ] ) ) {
                                                $hold = new SplFixedArray( 4 );
                                                $hold[0] = $shortcode_errors[0][ $y ];
                                                $hold[1] = $page_list[ $x ]->post_title;
                                                $hold[2] = $page_list[ $x ]->ID;
                                                $hold[3] = 'Broken Shortcode';

                                                $error_list->setSize( $error_list->getSize() + 1 ); //Increase the size of the main error array by 1
                                                $error_list[ $error_count ] = $hold;
                                                $error_count++;
                                        }
                                }
				unset( $page_list[ $x ] );
			}

			$end = round( microtime( true ), 5 );
			wpscx_print_debug( 'Broken Code EPS', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );

				$errors_num = $error_list->getSize() - 1;
			if ( $errors_num < 0 ) {
				$errors_num = 0;
			}
			return $errors_num;
	}
}
