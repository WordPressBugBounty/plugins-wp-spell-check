<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

	const WPSCX_POST = 'Post Content';
	const WPSCX_SITE = 'Site Tagline';

class Wpscx_Spellcheck_Scanner extends wpscx_scanner {

	function check_pages( $log_errors = false, $wpsc_haystack = null, $is_running = false ) {
		$start         = round( microtime( true ), 5 );
		$wpscx_debug_q = wpscx_debug_queries_at_start();
		global $wpscx_scan_delay;
		$sql_count = 0;
		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit
		set_time_limit( 6000 );
		global $wpdb;
		global $wpscx_ignore_list;
		global $wpsc_settings;
		global $wpscx_base_page_max;
				global $wpscx_title;

		$start_time = time();
		wpscx_set_global_vars();

		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$page_table    = $wpdb->prefix . 'posts';
		$max_pages     = $wpscx_base_page_max;

		if ( ! $is_running ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
		}

		if ( null === $wpsc_haystack ) {
			$loc = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );

			$contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

			$contents  = str_replace( "\r\n", "\n", $contents );
			$dict_file = explode( "\n", $contents );

			$wpsc_haystack = wpscx_dictionary_init( $dict_file );
		}

		$total_pages             = $max_pages;
		$total_words             = 0;
		$page_count              = 0;
		$word_count              = 0;
		$error_count             = 0;
				$pro_error_count = 0;

		if ( 'true' === $wpsc_settings[136]->option_value ) {
			$post_status = " AND (post_status='publish' OR post_status='draft')"; } else {
			$post_status = " AND post_status='publish'"; }

			$page_list = SplFixedArray::fromArray( $wpdb->get_results( "SELECT post_content, post_title, post_name, post_excerpt, ID FROM $page_table WHERE post_type='page'$post_status" ) );
			++$sql_count;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: $wpdb->prefix . 'spellcheck_ignore', query contains only hardcoded value "page"
			$ignore_pages = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );
			++$sql_count;

			$error_list = new SplFixedArray( 1 );
			for ( $x = 0; $x < $page_list->getSize(); $x++ ) {
				if ( ! $log_errors && $x >= 25 ) {
					break;
				}

				$ignore_flag = 'false';
				foreach ( $ignore_pages as $ignore_check ) {
					if ( strtoupper( trim( $page_list[ $x ]->post_title ) ) === strtoupper( trim( $ignore_check->keyword ) ) ) {
						$ignore_flag = 'true';
					}
				}
				if ( 'true' === $ignore_flag ) {
					continue; }
				++$page_count;
								$wpscx_title = $page_list[ $x ]->post_title;

				$words_content = $page_list[ $x ]->post_content;
				try {
					$words_content = do_shortcode( $words_content ); } catch ( Exception $e ) {
					}
					$words_content = wpscx_content_filter( $words_content );

					$words_content = wpscx_clean_all( $words_content, $wpsc_settings );

					$words = explode( ' ', $words_content );

					foreach ( $words as $word ) {

						++$total_words;
						$word = trim( $word, "'`”“$" );

						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							if ( $page_count <= $total_pages ) {
								// Add the error to a new fixed holding array
								$hold    = new SplFixedArray( 4 );
								$hold[0] = $word;
								$hold[1] = $page_list[ $x ]->post_title;
								$hold[2] = $page_list[ $x ]->ID;
								$hold[3] = 'Page Content';

								$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
								$error_list[ $error_count ] = $hold;
								++$error_count;
							} else {
								++$pro_error_count;
							}
						}
					}

					$word_list = html_entity_decode( wp_strip_all_tags( $page_list[ $x ]->post_excerpt ), ENT_QUOTES, 'utf-8' );
					$word_list = wpscx_clean_all( $word_list, $wpsc_settings );
					$words     = explode( ' ', $word_list );

					foreach ( $words as $word ) {

						++$total_words;
						$word = trim( $word, "'`”“$" );

						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							if ( $page_count <= $total_pages ) {
								$hold    = new SplFixedArray( 4 );
								$hold[0] = $word;
								$hold[1] = $page_list[ $x ]->post_title;
								$hold[2] = $page_list[ $x ]->ID;
								$hold[3] = 'Page Excerpt';

								$error_list->setSize( $error_list->getSize() + 1 );
								$error_list[ $error_count ] = $hold;
								++$error_count;
							} else {
								++$pro_error_count;
							}
						}
					}
					unset( $page_list[ $x ] );
			}

			if ( $log_errors ) {
				$end = round( microtime( true ), 5 );
				wpscx_print_debug( 'Page Content EPS', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $pro_error_count, $wpscx_debug_q );
				return $pro_error_count;
			}

			if ( $page_count > $max_pages ) {
					$counter    = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name ='pro_word_count';" );
					$word_count = $word_count + intval( $counter[0]->option_value );
			}

						$counter     = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name ='total_word_count';" );
						$total_words = $total_words + intval( $counter[0]->option_value );
						$wpdb->update( $options_table, array( 'option_value' => $total_words ), array( 'option_name' => 'total_word_count' ) );
			if ( $page_count > $total_pages ) {
					$page_count = $total_pages;
			}
						$wpdb->update( $options_table, array( 'option_value' => $page_count ), array( 'option_name' => 'page_count' ) );
						$sql_count += 4;

						wpscx_sql_insert( $error_list, 'Multi' );

			if ( ! $is_running ) {
					wpscx_finalize( $start_time );
			}
			$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'page_sip' ) );
			++$sql_count;

			$end = round( microtime( true ), 5 );
			wpscx_print_debug( 'Page Content', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $error_count, $wpscx_debug_q );
	}

	function check_posts( $log_errors = false, $wpsc_haystack = null, $is_running = false ) {
		$start         = round( microtime( true ), 5 );
		$wpscx_debug_q = wpscx_debug_queries_at_start();
		$start_debug = round( microtime( true ), 5 );
		global $wpscx_scan_delay;
				global $wpscx_title;
		$sql_count = 0;

		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit
		set_time_limit( 6000 );
		global $wpdb;
		// global $wpsc_haystack;
		global $wpscx_ignore_list;
		global $wpsc_settings;
		global $wpscx_base_page_max;
		$timer_init       = 0; // Initialization
		$timer_ignore     = 0; // Ignore Page
		$timer_email      = 0; // Ignore Emails if needed
		$timer_website    = 0; // Ignore websites if needed
		$timer_upper      = 0; // Ignore uppercase words if needed
		$timer_spellcheck = 0; // Spellcheck the word
		$timer_cleanup    = 0; // Cleanup words before checking them
		$timer_errors     = 0; // Add errors to database
		$timer_final      = 0; // Finalization

		$start_time = time();
		wpscx_set_global_vars();

		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$page_table    = $wpdb->prefix . 'posts';

		$max_pages         = $wpscx_base_page_max;
		$wpscx_dict_list   = $wpdb->get_results( "SELECT * FROM $dict_table;" );
		$wpscx_ignore_list = $wpdb->get_results( 'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE ignore_word=true;' );

		if ( null === $wpsc_haystack ) {
					$loc      = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );
					$contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

					$contents  = str_replace( "\r\n", "\n", $contents );
					$dict_file = explode( "\n", $contents );

					$wpsc_haystack = wpscx_dictionary_init( $dict_file );
		}

		$divi_check = wp_get_theme();

		// $total_pages = $max_pages;
		$total_words             = 0;
		$page_count              = 0;
		$word_count              = 0;
				$total_pages     = $max_pages;
		$pro_word_count          = 0;
		$error_count             = 0;
				$pro_error_count = 0;

		$post_types         = get_post_types( array( 'publicly_queryable' => true ) );
			$post_type_list = '(';
		foreach ( $post_types as $type ) {
			if ( 'revision' !== $type && 'page' !== $type && 'slider' !== $type && 'attachment' !== $type && 'optionsframework' !== $type && 'product' !== $type && 'wpcf7_contact_form' !== $type && 'nav_menu_item' !== $type && 'gal_display_source' !== $type && 'lightbox_library' !== $type && 'wpcf7s' !== $type ) {
				$post_type_list .= "post_type='$type' OR ";
			}
		}
			$post_type_list  = trim( $post_type_list, ' OR ' );
			$post_type_list .= ')';

		if ( 'true' === $wpsc_settings[137]->option_value ) {
			$post_status = " AND (post_status='publish' OR post_status='draft')"; } else {
			$post_status = " AND post_status='publish'"; }

			$page_list = SplFixedArray::fromArray( $wpdb->get_results( "SELECT post_content, post_title, post_name, post_excerpt, ID FROM $page_table WHERE $post_type_list$post_status" ) );
			++$sql_count;

			if ( ! $is_running ) {
				$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			}
			$ind_start_time = time();

			$max_time = ini_get( 'max_execution_time' );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: $wpdb->prefix . 'spellcheck_ignore', query contains only hardcoded value "page"
			$ignore_pages = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );
			++$sql_count;

			global $wpscx_ignore_list;
			global $wpsc_settings;
			$error_list = new SplFixedArray( 1 );

			$timer_init = round( microtime( true ), 5 ) - $start;

			// wpscx_print_debug("Post Content - Init: ", 0, 0, round(memory_get_usage() / 1000,5), 0);

			for ( $x = 0; $x < $page_list->getSize(); $x++ ) {
				if ( ! $log_errors && $x >= 25 ) {
					break;
				}

				$start_timer = round( microtime( true ), 5 );
				$ignore_flag = 'false';
				foreach ( $ignore_pages as $ignore_check ) {
					if ( strtoupper( trim( $page_list[ $x ]->post_title ) ) === strtoupper( trim( $ignore_check->keyword ) ) ) {
						$ignore_flag = 'true';
					}
				}
				if ( 'true' === $ignore_flag ) {
					continue; }
				++$page_count;
						$wpscx_title = $page_list[ $x ]->post_title;
						// wpscx_print_debug("Page Content - ID: " . $page_list[$x]->ID, 0, 0, round(memory_get_usage() / 1000,5), 0);

				$timer_ignore += round( microtime( true ), 5 ) - $start_timer;

				$words_content = $page_list[ $x ]->post_content;
				if ( strpos( $words_content, '[fep_submission_form]' ) ) {
					continue;
				}
				try {
					$words_content = do_shortcode( $words_content ); } catch ( Exception $e ) {
					}
					$words_content = wpscx_content_filter( $words_content );

					$words_content = wpscx_clean_all( $words_content, $wpsc_settings );

					$words = explode( ' ', $words_content );

					foreach ( $words as $word ) {
						$start_timer = round( microtime( true ), 5 );

						++$total_words;
						$word = trim( $word, "'`”“$" );

						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
									$timer_upper += round( microtime( true ), 5 ) - $start_timer;
							if ( $page_count <= $total_pages ) {
								// $word = addslashes($word);

								// Add the error to a new fixed holding array
								$hold    = new SplFixedArray( 4 );
								$hold[0] = $word;
								$hold[1] = $page_list[ $x ]->post_title;
								$hold[2] = $page_list[ $x ]->ID;
								$hold[3] = WPSCX_POST;

								$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
								$error_list[ $error_count ] = $hold;
								++$error_count;
							} else {
								++$pro_error_count;
							}
						}
					}

					$word_list = html_entity_decode( wp_strip_all_tags( $page_list[ $x ]->post_excerpt ), ENT_QUOTES, 'utf-8' );
					$word_list = wpscx_clean_all( $word_list, $wpsc_settings );
					$words     = explode( ' ', $word_list );

					foreach ( $words as $word ) {
						$start_timer = round( microtime( true ), 5 );

						++$total_words;
						$word = trim( $word, "'`”“$" );

						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
									$timer_upper += round( microtime( true ), 5 ) - $start_timer;
							if ( $page_count <= $total_pages ) {
								$hold    = new SplFixedArray( 4 );
								$hold[0] = $word;
								$hold[1] = $page_list[ $x ]->post_title;
								$hold[2] = $page_list[ $x ]->ID;
								$hold[3] = 'Post Excerpt';

								$error_list->setSize( $error_list->getSize() + 1 );
								$error_list[ $error_count ] = $hold;
								++$error_count;
							} else {
								++$pro_error_count;
							}
						}
					}
					unset( $page_list[ $x ] );
			}

			if ( $log_errors ) {
				$end = round( microtime( true ), 5 );
				wpscx_print_debug( 'Post Content EPS', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $pro_error_count, $wpscx_debug_q );
				return $pro_error_count;
			}

			if ( $page_count > $max_pages ) {
				$counter = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name ='pro_word_count';" );
				++$sql_count;
				$word_count = $word_count + intval( $counter[0]->option_value ); }

				$counter = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name ='total_word_count';" );
				++$sql_count;
				$total_words = $total_words + intval( $counter[0]->option_value );
				$wpdb->update( $options_table, array( 'option_value' => $total_words ), array( 'option_name' => 'total_word_count' ) );
				++$sql_count;
			if ( $page_count > $total_pages ) {
				$page_count = $total_pages;
			}
				$wpdb->update( $options_table, array( 'option_value' => $page_count ), array( 'option_name' => 'post_count' ) );
				++$sql_count;

				$start_timer = round( microtime( true ), 5 );

				wpscx_sql_insert( $error_list, 'Multi' );

				$timer_errors += round( microtime( true ), 5 ) - $start_timer;
				$start_timer   = round( microtime( true ), 5 );

			if ( ! $is_running ) {
				wpscx_finalize( $start_time );
			}
			$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'post_sip' ) );
			++$sql_count;

			$timer_final += round( microtime( true ), 5 ) - $start_timer;

			$end = round( microtime( true ), 5 );
			wpscx_print_debug( WPSCX_POST, round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $error_count, $wpscx_debug_q );

			unset( $sql );
			unset( $error_list );
	}

	function check_author_spelling( $wpsc_haystack ) {
		$start         = round( microtime( true ), 5 );
		$wpscx_debug_q = wpscx_debug_queries_at_start();

		global $wpdb;
		global $wpsc_haystack;
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		global $wpscx_ent_included;

		$table_name     = $wpdb->prefix . 'spellcheck_words';
		$options_table  = $wpdb->prefix . 'spellcheck_options';
		$ignore_table   = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table     = $wpdb->prefix . 'spellcheck_dictionary';
		$post_table     = $wpdb->prefix . 'posts';
		$user_table     = $wpdb->prefix . 'usermeta';
		$username_table = $wpdb->prefix . 'users';
		$sql_count      = 0;
		$total_words    = 0;
		$word_count     = 0;
		$error_count    = 0;

		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray( 1 );

		$options_settings = SplFixedArray::fromArray( $wpdb->get_results( "SELECT option_value FROM $options_table;" ) );
		++$sql_count;

		wpscx_set_global_vars();
		global $wpsc_settings;

		if ( null === $wpsc_haystack ) {
			$loc      = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );
			$contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

			$contents  = str_replace( "\r\n", "\n", $contents );
			$dict_file = explode( "\n", $contents );

			$wpsc_haystack = wpscx_dictionary_init( $dict_file );
		}

		$wpscx_dict_list   = $wpdb->get_results( "SELECT * FROM $dict_table;" );
		$wpscx_ignore_list = $wpdb->get_results( 'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE ignore_word=true;' );

		$author_meta_where = "(a.meta_key = 'first_name' OR a.meta_key = 'last_name' OR a.meta_key = 'description' OR a.meta_key = 'wpseo_metadesc' OR a.meta_key='wpseo_title' OR a.meta_key='wpseo_pronouns'";
		if ( wpscx_rank_math_is_active() ) {
			$author_meta_where .= " OR a.meta_key='rank_math_title' OR a.meta_key='rank_math_description'";
		}
		$author_meta_where .= ')';
		$rm_author_types = wpscx_rank_math_is_active() ? wpscx_rank_math_usermeta_keys() : array();

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table names from $wpdb->prefix; meta_key OR list is hardcoded.
		$posts_list = SplFixedArray::fromArray( $wpdb->get_results( "SELECT a.meta_key, a.user_id, a.meta_value, b.user_login, b.post_author FROM $user_table a LEFT JOIN (SELECT a.post_author, b.user_login FROM $post_table a, $username_table b WHERE a.post_author = b.ID GROUP BY post_author) AS b ON b.post_author = a.user_id WHERE $author_meta_where;" ) );
		++$sql_count;

		for ( $x = 0; $x < $posts_list->getSize(); $x++ ) {

			if ( '' === $posts_list[ $x ]->user_login || null === $posts_list[ $x ]->user_login ) {
				continue;
			}
			$words_list = $posts_list[ $x ]->meta_value;
			$words_list = wpscx_clean_all( $words_list, $wpsc_settings );
			$words      = explode( ' ', $words_list );

			foreach ( $words as $word ) {
				++$total_words;
				$word = trim( $word, "'`”“" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							// $word = addslashes($word);
							$to_add = true;
					if ( 'first_name' === $posts_list[ $x ]->meta_key ) {
						$post_type = 'Author First Name';
					} elseif ( 'last_name' === $posts_list[ $x ]->meta_key ) {
						$post_type = 'Author Last Name';
					} elseif ( 'description' === $posts_list[ $x ]->meta_key ) {
						$post_type = 'Author Biography';
					} elseif ( 'wpseo_metadesc' === $posts_list[ $x ]->meta_key ) {
						$post_type = 'Author SEO Description';
						if ( ! $wpscx_ent_included ) {
							$to_add = false;
						}
					} elseif ( 'wpseo_title' === $posts_list[ $x ]->meta_key ) {
						$post_type = 'Author SEO Title';
						if ( ! $wpscx_ent_included ) {
							$to_add = false;
						}
					} elseif ( 'wpseo_pronouns' === $posts_list[ $x ]->meta_key ) {
						$post_type = 'Yoast Author Pronouns';
						if ( ! wpscx_yoast_is_active() || ! $wpscx_ent_included ) {
							$to_add = false;
						}
					} elseif ( isset( $rm_author_types[ $posts_list[ $x ]->meta_key ] ) ) {
						$post_type = $rm_author_types[ $posts_list[ $x ]->meta_key ];
						if ( ! $wpscx_ent_included ) {
							$to_add = false;
						}
					} else {
						$post_type = $posts_list[ $x ]->meta_key; }

							// Add the error to a new fixed holding array
							$hold    = new SplFixedArray( 4 );
							$hold[0] = $word;
							$hold[1] = $posts_list[ $x ]->user_login;
							$hold[2] = $posts_list[ $x ]->user_id;
							$hold[3] = $post_type;

							$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
							$error_list[ $error_count ] = $hold;
							++$error_count;
				}
			}
		}

		wpscx_sql_insert( $error_list, 'Multi' );

		$end = round( microtime( true ), 5 );
		wpscx_print_debug( 'Author', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $error_count, $wpscx_debug_q );
	}

	function check_site_name( $wpsc_haystack, $is_running = false ) {
		$start         = round( microtime( true ), 5 );
		$wpscx_debug_q = wpscx_debug_queries_at_start();
		global $wpdb;
		global $end_included;
		global $wpsc_haystack;
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$post_table    = $wpdb->prefix . 'posts';
		$opt_table     = $wpdb->prefix . 'options';
		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit
		set_time_limit( 600 );
		$sql_count = 0;

		wpscx_set_global_vars();

		$max_pages = intval( $wpsc_settings[138]->option_value );

		if ( null === $wpsc_haystack ) {
			$loc      = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );
			$contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

			$contents  = str_replace( "\r\n", "\n", $contents );
			$dict_file = explode( "\n", $contents );

			$wpsc_haystack = wpscx_dictionary_init( $dict_file );
		}

		global $wpsc_settings;

		$total_words = 0;
		$post_count  = 0;
		$word_count  = 0;
		$error_count = 0;
		$word_count  = 0;
		$max_time    = ini_get( 'max_execution_time' );
		if ( ! $is_running ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			++$sql_count;
			$start_time = time();
		}
		$ind_start_time = time();

		// $posts_list = SplFixedArray::fromArray($wpdb->get_results("SELECT * FROM $opt_table WHERE option_name='blogname'"));$sql_count++;
				$words_list = get_bloginfo( 'name' );

		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray( 1 );

			$words_list = wpscx_clean_all( $words_list, $wpsc_settings );
			$words      = explode( ' ', $words_list );

		foreach ( $words as $word ) {
			++$total_words;
			$word = trim( $word, "'`”“" );
			if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
						// $word = addslashes($word);
						// Add the error to a new fixed holding array
						$hold    = new SplFixedArray( 3 );
						$hold[0] = $word;
						$hold[1] = 'Site Name';
						$hold[2] = 0;

						$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
						$error_list[ $error_count ] = $hold;
						++$error_count;
			}
		}

		wpscx_sql_insert( $error_list, 'Sitename' );

		if ( ! $is_running ) {
			wpscx_finalize( $start_time );
		}

		$end = round( microtime( true ), 5 );
		wpscx_print_debug( 'Sitename', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $error_count, $wpscx_debug_q );
	}

	function check_site_tagline( $wpsc_haystack, $is_running = false ) {
		$start         = round( microtime( true ), 5 );
		$wpscx_debug_q = wpscx_debug_queries_at_start();
		global $wpdb;
		global $wpscx_ent_included;
		global $wpsc_haystack;
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$post_table    = $wpdb->prefix . 'posts';
		$opt_table     = $wpdb->prefix . 'options';
		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit
		set_time_limit( 600 );
		$sql_count = 0;

		$max_pages = intval( $wpsc_settings[138]->option_value );
		if ( ! $wpscx_ent_included ) {
			$max_pages = 500;
		}

		wpscx_set_global_vars();
		global $wpsc_settings;

		if ( null === $wpsc_haystack ) {
			$loc      = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );
			$contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

			$contents  = str_replace( "\r\n", "\n", $contents );
			$dict_file = explode( "\n", $contents );

			$wpsc_haystack = wpscx_dictionary_init( $dict_file );
		}

		$total_words = 0;
		$post_count  = 0;
		$word_count  = 0;
		$error_count = 0;
		$word_count  = 0;
		$max_time    = ini_get( 'max_execution_time' );
		if ( ! $is_running ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			++$sql_count;
			$start_time = time();
		}

		// $posts_list = SplFixedArray::fromArray($wpdb->get_results("SELECT * FROM $opt_table WHERE option_name='blogdescription'"));$sql_count++;
				$words_list = get_bloginfo( 'description' );

		$error_list = new SplFixedArray( 1 );

			$words_list = wpscx_clean_all( $words_list, $wpsc_settings );
			$words      = explode( ' ', $words_list );

		foreach ( $words as $word ) {
			++$total_words;
			$word = trim( $word, "'`”“" );
			if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
						// $word = addslashes($word);

						// Add the error to a new fixed holding array
						$hold    = new SplFixedArray( 3 );
						$hold[0] = $word;
						$hold[1] = WPSCX_SITE;
						$hold[2] = 0;

						$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
						$error_list[ $error_count ] = $hold;
						++$error_count;
			}
		}

		wpscx_sql_insert( $error_list, WPSCX_SITE );

		if ( ! $is_running ) {
				$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'scan_in_progress' ) );
			++$sql_count;
			$end_time   = time();
			$total_time = wpscx_time_elapsed( $end_time - $start_time + 6 );
			$wpdb->update( $options_table, array( 'option_value' => $total_time ), array( 'option_name' => 'last_scan_finished' ) );
			++$sql_count;
		}

		$end = round( microtime( true ), 5 );
		wpscx_print_debug( WPSCX_SITE, round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $error_count, $wpscx_debug_q );
	}

	function check_authors( $wpsc_haystack = null, $is_running = false ) {
		global $wpscx_scan_delay;
		global $wpsc_settings;

		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit
		set_time_limit( 6000 );

		global $wpdb;
		global $wpscx_ent_included;
		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$authors_start = round( microtime( true ), 5 );
		$authors_setup_q = wpscx_debug_queries_at_start();
		$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
		$start_time = time();

		$post_table = $wpdb->prefix . 'posts';
		$posts_list = $wpdb->get_results( "SELECT * FROM $post_table GROUP BY post_author" );

		wpscx_print_debug( 'Authors Scan Setup', round( microtime( true ) - $authors_start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), 'N/A', $authors_setup_q );

		if ( null === $wpsc_haystack ) {
			$loc      = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );
			$contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

			$contents  = str_replace( "\r\n", "\n", $contents );
			$dict_file = explode( "\n", $contents );

			$wpsc_haystack = wpscx_dictionary_init( $dict_file );
		}

		$this->check_author_spelling( $wpsc_haystack );
		$this->check_site_tagline( true, $wpsc_haystack );
		$this->check_site_name( true, $wpsc_haystack );
		if ( $wpscx_ent_included ) {
			// check_author_seotitle_ent(true);
			// check_author_seodesc_ent(true);
		}

		$authors_finalize_q = wpscx_debug_queries_at_start();
		$authors_finalize_start = round( microtime( true ), 5 );
		$end_time   = time();
		$total_time = wpscx_time_elapsed( $end_time - $start_time + 6 );
		$wpdb->update( $options_table, array( 'option_value' => $total_time ), array( 'option_name' => 'last_scan_finished' ) );
		$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'author_sip' ) );
		wpscx_print_debug( 'Authors Scan Finalize', round( microtime( true ) - $authors_finalize_start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), 'N/A', $authors_finalize_q );
	}

	function check_cf7( $wpsc_haystack = null, $is_running = false ) {
		$start         = round( microtime( true ), 5 );
		$wpscx_debug_q = wpscx_debug_queries_at_start();
		if ( ! is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
			return;
		}

		global $wpscx_scan_delay;
		global $wpdb;
		global $wpscx_ent_included;
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit
		set_time_limit( 6000 );
		$sql_count = 0;

		$max_pages = intval( $wpsc_settings[138]->option_value );
		if ( ! $wpscx_ent_included ) {
			$max_pages = 500;
		}

		wpscx_set_global_vars();
		global $wpsc_settings;

		if ( null === $wpsc_haystack ) {
			// $loc = plugins_url("/dict/" . $wpsc_settings[11]->option_value . ".pws", __FILE__ );
			$loc = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );

			$contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

			$contents  = str_replace( "\r\n", "\n", $contents );
			$dict_file = explode( "\n", $contents );

			$wpsc_haystack = wpscx_dictionary_init( $dict_file );
		}

		$total_posts = 100;
		if ( $wpscx_ent_included ) {
			$total_posts = PHP_INT_MAX;
		}
		$total_words = 0;
		$post_count  = 0;
		$error_count = 0;
		$word_count  = 0;
		$word_count  = 0;
		if ( ! $is_running ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			$start_time = time();
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: $wpdb->prefix . 'spellcheck_ignore', query contains only hardcoded value "page"
		$ignore_posts = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );
		++$sql_count;

		if ( 'true' === $wpsc_settings[136]->option_value ) {
			$post_status = array( 'publish', 'draft' ); } else {
			$post_status = array( 'publish' ); }

			$posts_list = SplFixedArray::fromArray(
				get_posts(
					array(
						'posts_per_page' => $total_posts,
						'post_type'      => 'wpcf7_contact_form',
						'post_status'    => $post_status,
					)
				)
			);
		++$sql_count;

		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray( 1 );

		for ( $x = 0; $x < $posts_list->getSize(); $x++ ) {
			$ignore_flag = 'false';
			foreach ( $ignore_posts as $ignore_check ) {
				if ( strtoupper( trim( $posts_list[ $x ]->post_title ) ) == strtoupper( trim( $ignore_check->keyword ) ) ) {
					$ignore_flag = 'true';
				}
			}
			if ( 'true' === $ignore_flag ) {
				continue; }
			++$post_count;
			$words_list             = $posts_list[ $x ]->post_content;
						$words_list = explode( PHP_EOL . '1' . PHP_EOL, $words_list );
						$words_list = $words_list[0];
						$words_list = wpscx_clean_all( $words_list, $wpsc_settings );
			$words                  = explode( ' ', $words_list );

			foreach ( $words as $word ) {
				++$total_words;
				$word = trim( $word, "'`”“#" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							// $word = addslashes($word);

							// Add the error to a new fixed holding array
							$hold                                = new SplFixedArray( 4 );
							$hold[0]                             = $word;
							$hold[1]                             = $posts_list[ $x ]->post_title;
							$hold[2]                             = $posts_list[ $x ]->ID;
														$hold[3] = 'Contact Form 7 Form';

							$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
							$error_list[ $error_count ] = $hold;

							++$error_count;
				}
			}

					// Email Notification
					$words_list = $posts_list[ $x ]->post_content;
					$words_list = explode( PHP_EOL . '1' . PHP_EOL, $words_list );
			if ( isset( $words_list[1] ) ) {
				$words_list = $words_list[1];

				$words_list = preg_replace( '/(.*\n){1}/m', '', $words_list, 3 );
				$words_list = wpscx_clean_all( $words_list, $wpsc_settings );
				$words      = explode( ' ', $words_list );

				foreach ( $words as $word ) {
						++$total_words;
						$word = trim( $word, "'`”“#" );
					if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
						// $word = addslashes($word);

						// Add the error to a new fixed holding array
						$hold                            = new SplFixedArray( 4 );
						$hold[0]                         = $word;
						$hold[1]                         = $posts_list[ $x ]->post_title;
						$hold[2]                         = $posts_list[ $x ]->ID;
												$hold[3] = 'Contact Form 7 Email Notification';

						$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
						$error_list[ $error_count ] = $hold;

						++$error_count;
					}
				}
			}

					// Email Auto Response
						$words_list = $posts_list[ $x ]->post_content;
						$words_list = explode( PHP_EOL . '1' . PHP_EOL, $words_list );
			if ( isset( $words_list[2] ) ) {
				$words_list = $words_list[2];

				$words_list = preg_replace( '/(.*\n){1}/m', '', $words_list, 2 );
				$words_list = wpscx_clean_all( $words_list, $wpsc_settings );
				$words      = explode( ' ', $words_list );

				foreach ( $words as $word ) {
					++$total_words;
					$word = trim( $word, "'`”“#" );
					if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							// $word = addslashes($word);

							// Add the error to a new fixed holding array
							$hold                                = new SplFixedArray( 4 );
							$hold[0]                             = $word;
							$hold[1]                             = $posts_list[ $x ]->post_title;
							$hold[2]                             = $posts_list[ $x ]->ID;
														$hold[3] = 'Contact Form 7 Auto Response';

							$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
							$error_list[ $error_count ] = $hold;

							++$error_count;
					}
				}
			}
		}

		$counter = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name ='total_word_count';" );
		++$sql_count;
		$total_words = $total_words + intval( $counter[0]->option_value );
		$wpdb->update( $options_table, array( 'option_value' => $total_words ), array( 'option_name' => 'total_word_count' ) );
		++$sql_count;

		$word_count = $word_count + intval( $counter[0]->option_value );

		wpscx_sql_insert( $error_list, 'Multi' );

		if ( ! $is_running ) {
			$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'scan_in_progress' ) );
			++$sql_count;
			$end_time   = time();
			$total_time = wpscx_time_elapsed( $end_time - $start_time + 6 );
			$wpdb->update( $options_table, array( 'option_value' => $total_time ), array( 'option_name' => 'last_scan_finished' ) );
			++$sql_count;
		}
		$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'cf7_sip' ) );
		++$sql_count;

		$end = round( microtime( true ), 5 );
		wpscx_print_debug( 'Contact Form 7', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $error_count, $wpscx_debug_q );
	}

	function check_author_seotitle_free( $is_running = false, $wpsc_haystack = null, $log_debug = true ) {
		$start         = round( microtime( true ), 5 );
		$wpscx_debug_q = wpscx_debug_queries_at_start();
		if ( ! is_plugin_active( 'wordpress-seo/wp-seo.php' ) ) {
			return 1;
		}

		global $wpscx_scan_delay;
			global $wpdb;
		global $wpsc_haystack;
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$table_name     = $wpdb->prefix . 'spellcheck_words';
		$options_table  = $wpdb->prefix . 'spellcheck_options';
		$ignore_table   = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table     = $wpdb->prefix . 'spellcheck_dictionary';
		$post_table     = $wpdb->prefix . 'posts';
		$user_table     = $wpdb->prefix . 'usermeta';
		$username_table = $wpdb->prefix . 'users';
		set_time_limit( 600 );
		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit
		$sql_count = 0;

		$max_pages = PHP_INT_MAX;

		wpscx_set_global_vars();
		global $wpsc_settings;

		if ( null === $wpsc_haystack ) {
			$loc      = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );
			$contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

			$contents  = str_replace( "\r\n", "\n", $contents );
			$dict_file = explode( "\n", $contents );

			foreach ( $dict_file as $value ) {
				$wpsc_haystack[ strtoupper( $value ) ] = 1;
			}
			unset( $contents );
			unset( $dict_file );

			foreach ( $wpscx_dict_list as $value ) {
				$wpsc_haystack[ strtoupper( $value->word ) ] = 1;
			}
		}
		$word_count  = 0;
		$error_count = 0;
		$total_words = 0;
		$post_count  = 0;
		$word_count  = 0;
		$max_time    = ini_get( 'max_execution_time' );
		if ( ! $is_running ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			++$sql_count;
			$start_time = time();
		}
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$error_list = array();

		$ind_start_time = time();

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: $wpdb->prefix . 'spellcheck_ignore', query contains only hardcoded value "page"
		$ignore_posts = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );
		++$sql_count;

		$posts_list = $wpdb->get_results( "SELECT a.meta_key, a.user_id, a.meta_value, b.user_login, b.post_author FROM $user_table a LEFT JOIN (SELECT a.post_author, b.user_login FROM $post_table a, $username_table b WHERE a.post_author = b.ID GROUP BY post_author) AS b ON b.post_author = a.user_id WHERE a.meta_key='wpseo_title';" );
		++$sql_count;

		foreach ( $posts_list as $post ) {
			array_shift( $posts_list );

			$words_list = $post->meta_value;

			$words_list = wpscx_clean_text( $words_list );
			$words      = explode( ' ', $words_list );

			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {

								// $word = addslashes($word);
								++$error_count;
								array_push(
									$error_list,
									array(
										'word'      => $word,
										'page_name' => $post->user_login,
										'page_id'   => $post->user_id,
										'page_type' => 'Author SEO Title',
									)
								);
				}
			}
		}

		$end = round( microtime( true ), 5 );
		wpscx_print_debug( 'Author SEO Title EPS', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $error_count, $wpscx_debug_q );

		return $error_count;
	}

	function check_author_seodesc_free( $is_running = false, $wpsc_haystack = null, $log_debug = true ) {
		$start         = round( microtime( true ), 5 );
		$wpscx_debug_q = wpscx_debug_queries_at_start();
		if ( ! is_plugin_active( 'wordpress-seo/wp-seo.php' ) ) {
			return 1;
		}

		global $wpscx_scan_delay;
			global $wpdb;
		global $wpsc_haystack;
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$table_name     = $wpdb->prefix . 'spellcheck_words';
		$options_table  = $wpdb->prefix . 'spellcheck_options';
		$ignore_table   = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table     = $wpdb->prefix . 'spellcheck_dictionary';
		$post_table     = $wpdb->prefix . 'posts';
		$user_table     = $wpdb->prefix . 'usermeta';
		$username_table = $wpdb->prefix . 'users';
		set_time_limit( 600 );
		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit
		$sql_count = 0;

		$max_pages           = PHP_INT_MAX;
				$total_posts = $max_pages;

		wpscx_set_global_vars();
		global $wpsc_settings;

		if ( null === $wpsc_haystack ) {
			$loc      = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );
			$contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

			$contents  = str_replace( "\r\n", "\n", $contents );
			$dict_file = explode( "\n", $contents );

			foreach ( $dict_file as $value ) {
				$wpsc_haystack[ strtoupper( $value ) ] = 1;
			}
			unset( $contents );
			unset( $dict_file );

			foreach ( $wpscx_dict_list as $value ) {
				$wpsc_haystack[ strtoupper( $value->word ) ] = 1;
			}
		}

		$word_count  = 0;
		$error_count = 0;
		$total_words = 0;
		$post_count  = 0;
		$word_count  = 0;
		$max_time    = ini_get( 'max_execution_time' );
		if ( ! $is_running ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			++$sql_count;
			$start_time = time();
		}
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$error_list = array();

		$ind_start_time = time();

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: $wpdb->prefix . 'spellcheck_ignore', query contains only hardcoded value "page"
		$ignore_posts = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );
		++$sql_count;

		$posts_list = $wpdb->get_results( "SELECT a.meta_key, a.user_id, a.meta_value, b.user_login, b.post_author FROM $user_table a LEFT JOIN (SELECT a.post_author, b.user_login FROM $post_table a, $username_table b WHERE a.post_author = b.ID GROUP BY post_author) AS b ON b.post_author = a.user_id WHERE a.meta_key = 'wpseo_metadesc';" );
		++$sql_count;

		foreach ( $posts_list as $post ) {
			array_shift( $posts_list );

			$words_list = $post->meta_value;

			$words_list = wpscx_clean_text( $words_list );
			$words      = explode( ' ', $words_list );

			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;
				$word = trim( $word, "'`”“" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
					if ( $post_count <= $total_posts ) {
						// $word = addslashes($word);
						++$error_count;
						array_push(
							$error_list,
							array(
								'word'      => $word,
								'page_name' => $post->user_login,
								'page_id'   => $post->user_id,
								'page_type' => 'Author SEO Description',
							)
						);
					}
				}
			}
		}

		$end = round( microtime( true ), 5 );
		wpscx_print_debug( 'Author SEO Desc EPS ', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $error_count, $wpscx_debug_q );

		return $error_count;
	}

	function check_widgets_free( $is_running = false, $wpsc_haystack = null, $log_debug = true ) {
		$start         = round( microtime( true ), 5 );
		$wpscx_debug_q = wpscx_debug_queries_at_start();
		$sql_count   = 0;
		$total_words = 0;
		$error_count = 0;

		// Set memory/timeout
		set_time_limit( 6000 );
		ini_set( 'memory_limit', '512M' );

		// Set global variables
		global $wpdb;
		global $wpscx_ignore_list;
		global $wpsc_settings;
		global $wpscx_ent_included;
		wpscx_set_global_vars();

		// Set database tablenames
		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';

		$error_list = new SplFixedArray( 1 );

		$widget_instances = get_option( 'widget_text' );
		foreach ( $widget_instances as $widget ) {
			if ( ! isset( $widget['text'] ) ) {
				continue;
			}
				$text = $widget['text'];

				$text  = do_shortcode( $text );
				$text  = wpscx_clean_all( $text, $wpsc_settings, false );
				$words = explode( ' ', $text );

			foreach ( $words as $word ) {
					++$total_words;
					$word = trim( $word, "'`”“" );

				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
					// Add the error to a new fixed holding array
					$hold    = new SplFixedArray( 4 );
					$hold[0] = $word;
					$hold[1] = $widget['title'];
					$hold[2] = 0;
					$hold[3] = 'Widget Content';

					$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
					$error_list[ $error_count ] = $hold;
					++$error_count;
				}
			}
		}

		$end = round( microtime( true ), 5 );
		wpscx_print_debug( 'Widgets EPS ', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $error_count, $wpscx_debug_q );

		return $error_list->getSize();
	}

	function check_menus_free( $is_running = false, $wpsc_haystack = null, $log_debug = true ) {
		$start         = round( microtime( true ), 5 );
		$wpscx_debug_q = wpscx_debug_queries_at_start();
		global $wpscx_scan_delay;
		$sql_count = 0;

		global $wpdb;
		global $wpsc_haystack;
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name    = $wpdb->prefix . 'posts';
		$words_table   = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$word_count    = 0;
		$error_count   = 0;
		$total_words   = 0;
		set_time_limit( 6000 );
		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit

		global $wpsc_settings;
		wpscx_set_global_vars();

		$max_pages         = intval( $wpsc_settings[138]->option_value );
		$wpscx_dict_list   = $wpdb->get_results( "SELECT * FROM $dict_table;" );
		$wpscx_ignore_list = $wpdb->get_results( "SELECT * FROM $words_table WHERE ignore_word=true;" );

		global $wpscx_ignore_list;
		global $wpscx_dict_list;

		$error_list = new SplFixedArray( 1 );

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: $wpdb->prefix . 'posts', $max_pages is sanitized with intval(), query contains only hardcoded values
		$menus = SplFixedArray::fromArray( $wpdb->get_results( 'SELECT post_title, ID FROM ' . $table_name . ' WHERE post_type ="nav_menu_item" LIMIT ' . $max_pages . ';' ) );
		++$sql_count;

		for ( $x = 0; $x < $menus->getSize(); $x++ ) {
			$word_list = html_entity_decode( wp_strip_all_tags( $menus[ $x ]->post_title ), ENT_QUOTES, 'utf-8' );
			$word_list = wpscx_clean_all( $word_list, $wpsc_settings );
			$words     = explode( ' ', $word_list );
			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;
				$word = trim( $word, "'`”“" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {

							// Add the error to a new fixed holding array
							$hold    = new SplFixedArray( 3 );
							$hold[0] = $word;
							$hold[1] = $menus[ $x ]->post_title;
							$hold[2] = $menus[ $x ]->ID;

							$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
							$error_list[ $error_count ] = $hold;
							++$error_count;
				}
			}
			unset( $menus[ $x ] );
		}

		$end = round( microtime( true ), 5 );
		wpscx_print_debug( 'Menus EPS', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $error_count, $wpscx_debug_q );

		return $error_list->getSize();
	}


	function check_page_title_free( $is_running = false, $haystack = null, $log_debug = true ) {
		$end = round( microtime( true ), 5 );

		$start         = round( microtime( true ), 5 );
		$wpscx_debug_q = wpscx_debug_queries_at_start();
		$start_debug = round( microtime( true ), 5 );
		global $wpscx_scan_delay;
		$sql_count = 0;

		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit
		set_time_limit( 6000 );
		global $wpdb;
		// global $wpsc_haystack;
		global $wpscx_ignore_list;
		global $wpsc_settings;
		$timer_init       = 0; // Initialization
		$timer_ignore     = 0; // Ignore Page
		$timer_email      = 0; // Ignore Emails if needed
		$timer_website    = 0; // Ignore websites if needed
		$timer_upper      = 0; // Ignore uppercase words if needed
		$timer_spellcheck = 0; // Spellcheck the word
		$timer_cleanup    = 0; // Cleanup words before checking them
		$timer_errors     = 0; // Add errors to database
		$timer_final      = 0; // Finalization

		$start_time = time();
		wpscx_set_global_vars();

		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$page_table    = $wpdb->prefix . 'posts';

		// $language_setting = $wpdb->get_results('SELECT option_value from ' . $options_table . ' WHERE option_name="language_setting";');

		// $max_pages = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name = 'pro_max_pages'");
		$max_pages = intval( $wpsc_settings[138]->option_value );

		$loc          = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );
			$contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

			$contents  = str_replace( "\r\n", "\n", $contents );
			$dict_file = explode( "\n", $contents );

		$wpsc_haystack = wpscx_dictionary_init( $dict_file );

		$total_pages = $max_pages;
		$total_words = 0;
		$page_count  = 0;
		$word_count  = 0;
		$error_count = 0;

		if ( 'true' === $wpsc_settings[136]->option_value ) {
			$post_status = " AND (post_status='publish' OR post_status='draft')"; } else {
			$post_status = " AND post_status='publish'"; }

			$page_list = SplFixedArray::fromArray( $wpdb->get_results( "SELECT post_content, post_title, post_name, ID FROM $page_table WHERE post_type='page'$post_status" ) );
			++$sql_count;

			if ( ! $is_running ) {
				$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			}
			$ind_start_time = time();

			$max_time = ini_get( 'max_execution_time' );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: $wpdb->prefix . 'spellcheck_ignore', query contains only hardcoded value "page"
			$ignore_pages = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );
			++$sql_count;

			global $wpscx_ignore_list;
			global $wpsc_settings;
			$error_list = new SplFixedArray( 1 );

			$timer_init = round( microtime( true ), 5 ) - $start;

			for ( $x = 0; $x < $page_list->getSize(); $x++ ) {
				$ignore_flag = 'false';
				foreach ( $ignore_pages as $ignore_check ) {
					if ( strtoupper( trim( $page_list[ $x ]->post_title ) ) === strtoupper( trim( $ignore_check->keyword ) ) ) {
						$ignore_flag = 'true';
					}
				}
				if ( 'true' === $ignore_flag ) {
					continue; }
				++$page_count;

				// Page Title
				$word_list = html_entity_decode( wp_strip_all_tags( $page_list[ $x ]->post_title ), ENT_QUOTES, 'utf-8' );

				$word_list = wpscx_clean_all( $word_list, $wpsc_settings );

				$words = explode( ' ', $word_list );

				foreach ( $words as $word ) {
					++$word_count;
					++$total_words;
					$word = trim( $word, "'`”“" );
					if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {

								// Add the error to a new fixed holding array
								$hold    = new SplFixedArray( 4 );
								$hold[0] = $word;
								$hold[1] = $page_list[ $x ]->post_title;
								$hold[2] = $page_list[ $x ]->ID;
								$hold[3] = 'Page Title';

								$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
								$error_list[ $error_count ] = $hold;
								++$error_count;
					}
				}

				// Page Slug
				$desc_title = $page_list[ $x ]->post_title;
				$desc_id    = $page_list[ $x ]->ID;
				$desc       = $page_list[ $x ]->post_name;

				$desc = wpscx_clean_slug( $desc );

				$words = explode( ' ', $desc );

				foreach ( $words as $word ) {
					++$word_count;
					++$total_words;
					$word = str_replace( ' ', '', $word );
					$word = str_replace( '=', '', $word );
					$word = str_replace( ',', '', $word );
					$word = trim( $word, "?!.,'()`”:“@$#-%\=/" );
					$word = trim( $word, '"' );
					$word = trim( $word );
					$word = preg_replace( '/[0-9]/', '', $word );
					$word = preg_replace( "/[^a-zA-z'’`éèùâêîôûçëïü]/i", '', $word );
					if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {

								// Add the error to a new fixed holding array
								$hold    = new SplFixedArray( 4 );
								$hold[0] = $word;
								$hold[1] = $desc_title;
								$hold[2] = $desc_id;
								$hold[3] = 'Page Slug';

								$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
								$error_list[ $error_count ] = $hold;
								++$error_count;
					}
				}

				unset( $page_list[ $x ] );
			}

			// Widgets
			$widget_instances = get_option( 'widget_text' );
			foreach ( $widget_instances as $widget ) {
				if ( ! isset( $widget['text'] ) ) {
					continue;
				}
				$text = $widget['text'];

				$text  = do_shortcode( $text );
				$text  = wpscx_clean_all( $text, $wpsc_settings, false );
				$words = explode( ' ', $text );

				foreach ( $words as $word ) {
					++$total_words;
					$word = trim( $word, "'`”“" );

					if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
						// Add the error to a new fixed holding array
						$hold    = new SplFixedArray( 4 );
						$hold[0] = $word;
						$hold[1] = $widget['title'];
						$hold[2] = 0;
						$hold[3] = 'Widget Content';

						$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
						$error_list[ $error_count ] = $hold;
						++$error_count;
					}
				}
			}

			$end = round( microtime( true ), 5 );
			wpscx_print_debug( 'Page Title/Slug EPS ', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $error_count, $wpscx_debug_q );

			return $error_list->getSize();
	}

	function check_custom_fields_free( $post_id, $wpsc_haystack ) {
		$post_meta = get_post_meta( $post_id );
		global $wpsc_settings;
		$error_list  = new SplFixedArray( 1 );
		$error_count = 0;

		foreach ( $post_meta as $key => $value ) {
			$meta = $value[0];
			if ( substr( $key, 0, 1 ) == '_' || 'pageSlogan' === $key ) {
				continue;
			}
			if ( substr_count( $meta, ':' ) > 5 ) {
				continue;
			}

				$words_content = wpscx_clean_all( $meta, $wpsc_settings, false );

			$words = explode( ' ', $words_content );

			foreach ( $words as $word ) {

					$start_timer = round( microtime( true ), 5 );

					$word = trim( $word, "'`”“" );

				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							$hold    = new SplFixedArray( 4 );
							$hold[0] = $word;
							$hold[1] = '';
							$hold[2] = '';
							$hold[3] = ' Custom Field';

							$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
							$error_list[ $error_count ] = $hold;
							++$error_count;
				}
			}
		}
		return $error_list;
	}

	function check_custom_fields( $type, $wpsc_haystack ) {
		global $wpsc_settings;
		global $wpdb;
		$meta_table  = $wpdb->prefix . 'postmeta';
		$post_table  = $wpdb->prefix . 'posts';
		$error_list  = new SplFixedArray( 1 );
		$error_count = 0;

		$results = $wpdb->get_results( "SELECT a.meta_id, a.meta_key, a.meta_value, b.post_title FROM $meta_table a JOIN $post_table b ON a.post_id = b.ID WHERE b.post_type='$type';" );
		foreach ( $results as $row ) {
			if ( '_' === substr( $row->meta_key, 0, 1 ) || 'pageSlogan' === $row->meta_key ) {
				continue;
			}
			if ( substr_count( $row->meta_value, ':' ) >= 3 ) {
				continue;
			}

			$words_content = wpscx_clean_all( $row->meta_value, $wpsc_settings, false );
			$words         = explode( ' ', $words_content );

			foreach ( $words as $word ) {
				$word = trim( $word, "'`”“" );

				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							$hold    = new SplFixedArray( 4 );
							$hold[0] = $word;
							$hold[1] = $row->post_title;
							$hold[2] = $row->meta_id;
							$hold[3] = $type . ' Custom Field';

							$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
							$error_list[ $error_count ] = $hold;
							++$error_count;
				}
			}
		}

		return $error_list;
	}


	function check_post_title_free( $is_running = false, $wpscx_haystack = null, $log_debug = true ) {
		$end = round( microtime( true ), 5 );

		$start         = round( microtime( true ), 5 );
		$wpscx_debug_q = wpscx_debug_queries_at_start();
		$start_debug = round( microtime( true ), 5 );
		global $wpscx_scan_delay;
		$sql_count = 0;

		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit
		set_time_limit( 6000 );
		global $wpdb;
		// global $wpsc_haystack;
		global $wpscx_ignore_list;
		global $wpsc_settings;
		$timer_init       = 0; // Initialization
		$timer_ignore     = 0; // Ignore Page
		$timer_email      = 0; // Ignore Emails if needed
		$timer_website    = 0; // Ignore websites if needed
		$timer_upper      = 0; // Ignore uppercase words if needed
		$timer_spellcheck = 0; // Spellcheck the word
		$timer_cleanup    = 0; // Cleanup words before checking them
		$timer_errors     = 0; // Add errors to database
		$timer_final      = 0; // Finalization

		$start_time = time();
		wpscx_set_global_vars();

		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$page_table    = $wpdb->prefix . 'posts';

		// $language_setting = $wpdb->get_results('SELECT option_value from ' . $options_table . ' WHERE option_name="language_setting";');

		// $max_pages = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name = 'pro_max_pages'");
		$max_pages         = intval( $wpsc_settings[138]->option_value );
		$wpscx_dict_list   = $wpdb->get_results( "SELECT * FROM $dict_table;" );
		$wpscx_ignore_list = $wpdb->get_results( 'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE ignore_word=true;' );
		$loc      = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );
		$contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

		$contents  = str_replace( "\r\n", "\n", $contents );
		$dict_file = explode( "\n", $contents );

		$wpsc_haystack = wpscx_dictionary_init( $dict_file );

		$divi_check = wp_get_theme();

		$total_pages = $max_pages;
		$total_words = 0;
		$page_count  = 0;
		$word_count  = 0;
		$error_count = 0;

		$post_types         = get_post_types( array( 'publicly_queryable' => true ) );
			$post_type_list = '(';
		foreach ( $post_types as $type ) {
			if ( 'revision' !== $type && 'page' !== $type && 'slider' !== $type && 'attachment' !== $type && 'optionsframework' !== $type && 'product' !== $type && 'wpcf7_contact_form' !== $type && 'nav_menu_item' !== $type && 'gal_display_source' !== $type && 'lightbox_library' !== $type && 'wpcf7s' !== $type ) {
				$post_type_list .= "post_type='$type' OR ";
			}
		}
			$post_type_list  = trim( $post_type_list, ' OR ' );
			$post_type_list .= ')';

		if ( 'true' === $wpsc_settings[137]->option_value ) {
			$post_status = " AND (post_status='publish' OR post_status='draft')"; } else {
			$post_status = " AND post_status='publish'"; }

			$page_list = SplFixedArray::fromArray( $wpdb->get_results( "SELECT post_content, post_title, post_name, ID, post_type FROM $page_table WHERE $post_type_list$post_status" ) );
			++$sql_count;

			if ( ! $is_running ) {
				$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			}
			$ind_start_time = time();

			$max_time = ini_get( 'max_execution_time' );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: $wpdb->prefix . 'spellcheck_ignore', query contains only hardcoded value "page"
			$ignore_pages = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );
			++$sql_count;

			global $wpscx_ignore_list;
			global $wpsc_settings;
			$error_list = new SplFixedArray( 1 );

			$timer_init = round( microtime( true ), 5 ) - $start;

						// custom fields
						$custom = $this->check_custom_fields( 'Post', $wpsc_haystack );
			for ( $y = 0; $y < $custom->getSize(); $y++ ) {
				$error_list->setSize( $error_list->getSize() + 1 );
				$error_list[ $error_count ] = $custom[ $y ];
				++$error_count;
			}

			for ( $x = 0; $x < $page_list->getSize(); $x++ ) {

				$start_timer = round( microtime( true ), 5 );
				$ignore_flag = 'false';
				foreach ( $ignore_pages as $ignore_check ) {
					if ( strtoupper( trim( $page_list[ $x ]->post_title ) ) == strtoupper( trim( $ignore_check->keyword ) ) ) {
						$ignore_flag = 'true';
					}
				}
				if ( 'true' === $ignore_flag ) {
					continue; }
				++$page_count;

				$timer_ignore += round( microtime( true ), 5 ) - $start_timer;

				// Post Titles
				if ( 'true' === $wpsc_settings[13]->option_value ) {
					$word_list = html_entity_decode( wp_strip_all_tags( $page_list[ $x ]->post_title ), ENT_QUOTES, 'utf-8' );
					$word_list = wpscx_clean_all( $word_list, $wpsc_settings );
					$words     = explode( ' ', $word_list );

					foreach ( $words as $word ) {
						++$word_count;
						++$total_words;
						$word = trim( $word, "'`”“" );
						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {

									// Add the error to a new fixed holding array
									$hold    = new SplFixedArray( 4 );
									$hold[0] = $word;
									$hold[1] = $page_list[ $x ]->post_title;
									$hold[2] = $page_list[ $x ]->ID;
									$hold[3] = 'Post Title';

									$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
									$error_list[ $error_count ] = $hold;
									++$error_count;
						}
					}
				}

				// Post Slugs
				if ( 'true' === $wpsc_settings[19]->option_value ) {
					$desc_title = $page_list[ $x ]->post_title;
					$desc_id    = $page_list[ $x ]->ID;
					$desc       = $page_list[ $x ]->post_name;
					// $desc = wpscx_clean_slug($desc);
					$words = explode( '-', $desc );

					foreach ( $words as $word ) {
						++$word_count;
						++$total_words;
						$word = str_replace( ' ', '', $word );
						$word = str_replace( '=', '', $word );
						$word = str_replace( ',', '', $word );
						$word = trim( $word, "?!.,'()`”:“@$#-%\=/" );
						$word = trim( $word, '"' );
						$word = trim( $word );
						$word = preg_replace( '/[0-9]/', '', $word );
						$word = preg_replace( "/[^a-zA-z'’`éèùâêîôûçëïü]/i", '', $word );
						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {

									// Add the error to a new fixed holding array
									$hold    = new SplFixedArray( 4 );
									$hold[0] = $word;
									$hold[1] = $desc_title;
									$hold[2] = $desc_id;
									$hold[3] = 'Post Slug';

									$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
									$error_list[ $error_count ] = $hold;
									++$error_count;
						}
					}
				}
				unset( $page_list[ $x ] );
			}

			$end = round( microtime( true ), 5 );
			wpscx_print_debug( 'Post Title/Slug EPS ', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $error_count, $wpscx_debug_q );

			return $error_list->getSize();
	}


	function check_post_tags_free( $is_running = false, $wpscx_haystack = null, $log_debug = true ) {
		$start         = round( microtime( true ), 5 );
		$wpscx_debug_q = wpscx_debug_queries_at_start();
		global $wpscx_scan_delay;
		$sql_count = 0;

		global $wpdb;
		global $wpsc_haystack;
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$word_count    = 0;
		$error_count   = 0;
		$total_words   = 0;
		set_time_limit( 6000 );
		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit

		wpscx_set_global_vars();
		global $wpsc_settings;

		$max_pages         = intval( $wpsc_settings[138]->option_value );
		$wpscx_dict_list   = $wpdb->get_results( "SELECT * FROM $dict_table;" );
		$wpscx_ignore_list = $wpdb->get_results( 'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE ignore_word=true;' );
		$loc      = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );
		$contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

		$contents  = str_replace( "\r\n", "\n", $contents );
		$dict_file = explode( "\n", $contents );

		$wpsc_haystack = wpscx_dictionary_init( $dict_file );
		if ( ! $is_running ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			++$sql_count;
			$start_time = time();
		}
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray( 1 );

		$tags_list = SplFixedArray::fromArray( get_tags() );
		++$sql_count;
		$tax_meta  = wpscx_yoast_is_active() ? get_option( 'wpseo_taxonomy_meta', array() ) : array();
		$tax_map   = wpscx_yoast_is_active() ? wpscx_yoast_taxonomy_field_map( 'post_tag' ) : array();
		$rm_term_meta = array();
		if ( wpscx_rank_math_is_active() ) {
			$termmeta_table      = $wpdb->prefix . 'termmeta';
			$term_taxonomy_table = $wpdb->prefix . 'term_taxonomy';
			$terms_table         = $wpdb->prefix . 'terms';
			$rm_keys             = array_keys( wpscx_rank_math_term_keys( 'post_tag' ) );
			$key_placeholders    = implode( ', ', array_fill( 0, count( $rm_keys ), '%s' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPlaceholder, WordPress.DB.PreparedSQL.NotPrepared -- Table names from $wpdb->prefix; IN placeholders built from fixed key count.
			$rm_rows = $wpdb->get_results( $wpdb->prepare( 'SELECT tm.term_id, tm.meta_key, tm.meta_value FROM ' . $termmeta_table . ' tm INNER JOIN ' . $term_taxonomy_table . ' tt ON tm.term_id = tt.term_id WHERE tt.taxonomy = %s AND tm.meta_key IN (' . $key_placeholders . ')', array_merge( array( 'post_tag' ), $rm_keys ) ) );
			foreach ( $rm_rows as $rm_row ) {
				$rm_term_meta[ $rm_row->term_id ][ $rm_row->meta_key ] = $rm_row->meta_value;
			}
		}
		$aioseo_term_meta = wpscx_aioseo_term_rows_for_taxonomy( 'post_tag' );
		$aioseo_tag_types = wpscx_aioseo_term_page_types( 'post_tag' );

		for ( $x = 0; $x < $tags_list->getSize(); $x++ ) {
			$words = array();

			if ( 'true' === $wpsc_settings[14]->option_value ) {
				$words = wpscx_clean_text( wp_strip_all_tags( html_entity_decode( $tags_list[ $x ]->name ) ) );
				$words = wpscx_clean_all( $words, $wpsc_settings );

				$words = explode( ' ', $words );

				// Tag Titles
				foreach ( $words as $word ) {
					++$word_count;
					++$total_words;
					$word = trim( $word, '"' );
					$word = trim( $word );
					if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {

								// Add the error to a new fixed holding array
								$hold    = new SplFixedArray( 4 );
								$hold[0] = $word;
								$hold[1] = $tags_list[ $x ]->post_title;
								$hold[2] = $tags_list[ $x ]->term_id;
								$hold[3] = 'Tag Title';

								$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
								$error_list[ $error_count ] = $hold;
								++$error_count;
					}
				}
			}

			if ( 'true' === $wpsc_settings[38]->option_value ) {
				// Tag Descriptions
				$words = wpscx_clean_text( wp_strip_all_tags( html_entity_decode( $tags_list[ $x ]->description ) ) );
				$words = wpscx_clean_all( $words, $wpsc_settings );
				$words = explode( ' ', $words );

				foreach ( $words as $word ) {
					++$word_count;
					++$total_words;
					$word = trim( $word, "?!.,'()`”:“@$#-%\=/" );
					$word = trim( $word, '"' );
					$word = trim( $word );
					if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {

								// Add the error to a new fixed holding array
								$hold    = new SplFixedArray( 4 );
								$hold[0] = $word;
								$hold[1] = $tags_list[ $x ]->post_title;
								$hold[2] = $tags_list[ $x ]->term_id;
								$hold[3] = 'Tag Description';

								$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
								$error_list[ $error_count ] = $hold;
								++$error_count;
					}
				}
			}

			if ( 'true' === $wpsc_settings[39]->option_value ) {
				// Tag Slugs
				$words = wpscx_clean_slug( $tags_list[ $x ]->slug );

				$words = explode( ' ', $words );

				foreach ( $words as $word ) {
					++$word_count;
					++$total_words;
					$word = str_replace( ' ', '', $word );
					$word = str_replace( '=', '', $word );
					$word = str_replace( ',', '', $word );
					$word = trim( $word, "?!.,'()`”:“@$#-%\=/" );
					$word = trim( $word, '"' );
					$word = trim( $word );
					$word = preg_replace( '/[0-9]/', '', $word );
					$word = preg_replace( "/[^a-zA-z'’`éèùâêîôûçëïü]/i", '', $word );
					if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {

								// Add the error to a new fixed holding array
								$hold    = new SplFixedArray( 4 );
								$hold[0] = $word;
								$hold[1] = $tags_list[ $x ]->post_title;
								$hold[2] = $tags_list[ $x ]->term_id;
								$hold[3] = 'Tag Slug';

								$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
								$error_list[ $error_count ] = $hold;
								++$error_count;
					}
				}
			}

			if ( wpscx_yoast_is_active() && isset( $tags_list[ $x ]->term_id ) && ! empty( $tax_meta['post_tag'][ $tags_list[ $x ]->term_id ] ) ) {
				$term_id   = $tags_list[ $x ]->term_id;
				$term_name = isset( $tags_list[ $x ]->name ) ? $tags_list[ $x ]->name : '';
				foreach ( $tax_map as $field_key => $page_type ) {
					if ( empty( $tax_meta['post_tag'][ $term_id ][ $field_key ] ) ) {
						continue;
					}
					$field_value = $tax_meta['post_tag'][ $term_id ][ $field_key ];
					if ( false !== strpos( $field_key, 'focuskeywords' ) || false !== strpos( $field_key, 'keywordsynonyms' ) ) {
						$field_value = wpscx_yoast_flatten_json_text( $field_key, $field_value );
					}
					$field_value = wpscx_clean_all( $field_value, $wpsc_settings );
					$words       = explode( ' ', $field_value );
					foreach ( $words as $word ) {
						++$word_count;
						++$total_words;
						$word = trim( $word, "'`”“" );
						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							$hold    = new SplFixedArray( 4 );
							$hold[0] = $word;
							$hold[1] = $term_name;
							$hold[2] = $term_id;
							$hold[3] = $page_type;
							$error_list->setSize( $error_list->getSize() + 1 );
							$error_list[ $error_count ] = $hold;
							++$error_count;
						}
					}
				}
			}

			if ( wpscx_rank_math_is_active() && isset( $tags_list[ $x ]->term_id ) && ! empty( $rm_term_meta[ $tags_list[ $x ]->term_id ] ) ) {
				$term_id   = $tags_list[ $x ]->term_id;
				$term_name = isset( $tags_list[ $x ]->name ) ? $tags_list[ $x ]->name : '';
				$rm_map    = wpscx_rank_math_term_keys( 'post_tag' );
				foreach ( $rm_map as $meta_key => $page_type ) {
					if ( empty( $rm_term_meta[ $term_id ][ $meta_key ] ) ) {
						continue;
					}
					$field_value = wpscx_clean_all( $rm_term_meta[ $term_id ][ $meta_key ], $wpsc_settings );
					$words       = explode( ' ', $field_value );
					foreach ( $words as $word ) {
						++$word_count;
						++$total_words;
						$word = trim( $word, "'`”“" );
						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							$hold    = new SplFixedArray( 4 );
							$hold[0] = $word;
							$hold[1] = $term_name;
							$hold[2] = $term_id;
							$hold[3] = $page_type;
							$error_list->setSize( $error_list->getSize() + 1 );
							$error_list[ $error_count ] = $hold;
							++$error_count;
						}
					}
				}
			}

			if ( wpscx_aioseo_is_active() && isset( $tags_list[ $x ]->term_id ) && ! empty( $aioseo_term_meta[ $tags_list[ $x ]->term_id ] ) ) {
				$term_id    = $tags_list[ $x ]->term_id;
				$term_name  = isset( $tags_list[ $x ]->name ) ? $tags_list[ $x ]->name : '';
				$aioseo_row = $aioseo_term_meta[ $term_id ];
				foreach ( array(
					'title'       => $aioseo_tag_types['title'],
					'description' => $aioseo_tag_types['description'],
				) as $column => $page_type ) {
					if ( empty( $aioseo_row->$column ) ) {
						continue;
					}
					$field_value = wpscx_clean_all( $aioseo_row->$column, $wpsc_settings );
					$words       = explode( ' ', $field_value );
					foreach ( $words as $word ) {
						++$word_count;
						++$total_words;
						$word = trim( $word, "'`”“" );
						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							$hold    = new SplFixedArray( 4 );
							$hold[0] = $word;
							$hold[1] = $term_name;
							$hold[2] = $term_id;
							$hold[3] = $page_type;
							$error_list->setSize( $error_list->getSize() + 1 );
							$error_list[ $error_count ] = $hold;
							++$error_count;
						}
					}
				}
			}

			unset( $tags_list[ $x ] );
		}

		$end = round( microtime( true ), 5 );
		wpscx_print_debug( 'Tag EPS ', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $error_count, $wpscx_debug_q );

		return $error_list->getSize();
	}

	function check_post_categories_free( $is_running = false, $wpscx_haystack = null, $log_debug = true ) {
		$start         = round( microtime( true ), 5 );
		$wpscx_debug_q = wpscx_debug_queries_at_start();
		global $wpscx_scan_delay;
		$sql_count = 0;

		global $wpdb;
		global $wpsc_haystack;
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$word_count    = 0;
		$error_count   = 0;
		$total_words   = 0;
		set_time_limit( 6000 );
		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit

		wpscx_set_global_vars();
		global $wpsc_settings;

		$max_pages         = intval( $wpsc_settings[138]->option_value );
		$wpscx_dict_list   = $wpdb->get_results( "SELECT * FROM $dict_table;" );
		$wpscx_ignore_list = $wpdb->get_results( 'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE ignore_word=true;' );
		$loc      = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );
		$contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

		$contents  = str_replace( "\r\n", "\n", $contents );
		$dict_file = explode( "\n", $contents );

		$wpsc_haystack = wpscx_dictionary_init( $dict_file );
		if ( ! $is_running ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			++$sql_count;
			$start_time = time();
		}
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray( 1 );

		$cats_list = SplFixedArray::fromArray( get_categories() );
		++$sql_count;
		$tax_meta  = wpscx_yoast_is_active() ? get_option( 'wpseo_taxonomy_meta', array() ) : array();
		$tax_map   = wpscx_yoast_is_active() ? wpscx_yoast_taxonomy_field_map( 'category' ) : array();
		$rm_term_meta = array();
		if ( wpscx_rank_math_is_active() ) {
			$termmeta_table      = $wpdb->prefix . 'termmeta';
			$term_taxonomy_table = $wpdb->prefix . 'term_taxonomy';
			$rm_keys             = array_keys( wpscx_rank_math_term_keys( 'category' ) );
			$key_placeholders    = implode( ', ', array_fill( 0, count( $rm_keys ), '%s' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPlaceholder, WordPress.DB.PreparedSQL.NotPrepared -- Table names from $wpdb->prefix; IN placeholders built from fixed key count.
			$rm_rows = $wpdb->get_results( $wpdb->prepare( 'SELECT tm.term_id, tm.meta_key, tm.meta_value FROM ' . $termmeta_table . ' tm INNER JOIN ' . $term_taxonomy_table . ' tt ON tm.term_id = tt.term_id WHERE tt.taxonomy = %s AND tm.meta_key IN (' . $key_placeholders . ')', array_merge( array( 'category' ), $rm_keys ) ) );
			foreach ( $rm_rows as $rm_row ) {
				$rm_term_meta[ $rm_row->term_id ][ $rm_row->meta_key ] = $rm_row->meta_value;
			}
		}
		$aioseo_term_meta = wpscx_aioseo_term_rows_for_taxonomy( 'category' );
		$aioseo_cat_types = wpscx_aioseo_term_page_types( 'category' );

		for ( $x = 0; $x < $cats_list->getSize(); $x++ ) {
			$words = array();

			if ( 'true' === $wpsc_settings[15]->option_value && isset( $cats_list[ $x ]->name ) ) {
				// Cat Titles
				$words = wp_strip_all_tags( html_entity_decode( $cats_list[ $x ]->name ) );
				$words = wpscx_clean_all( $words, $wpsc_settings );
				$words = explode( ' ', $words );

				foreach ( $words as $word ) {
					++$word_count;
					++$total_words;
					$word = trim( $word, "'`”“" );
					if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {

								// Add the error to a new fixed holding array
								$hold    = new SplFixedArray( 4 );
								$hold[0] = $word;
								$hold[1] = $cats_list[ $x ]->post_title;
								$hold[2] = $cats_list[ $x ]->term_id;
								$hold[3] = 'Category Title';

								$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
								$error_list[ $error_count ] = $hold;
								++$error_count;
					}
				}
			}

			if ( 'true' === $wpsc_settings[40]->option_value && isset( $cats_list[ $x ]->description ) ) {
				// Cat Descriptions
				$words = array();
				$words = $cats_list[ $x ]->description;

				$words = wpscx_clean_all( $words, $wpsc_settings );

				$words = explode( ' ', $words );

				foreach ( $words as $word ) {
					++$word_count;
					++$total_words;
					$word = trim( $word, "'`”“" );
					if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {

								// Add the error to a new fixed holding array
								$hold    = new SplFixedArray( 4 );
								$hold[0] = $word;
								$hold[1] = $cats_list[ $x ]->post_title;
								$hold[2] = $cats_list[ $x ]->term_id;
								$hold[3] = 'Category Description';

								$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
								$error_list[ $error_count ] = $hold;
								++$error_count;
					}
				}
			}

			if ( 'true' === $wpsc_settings[41]->option_value && isset( $cats_list[ $x ]->slug ) ) {
				// Cat Slugs
				$words = wpscx_clean_slug( $cats_list[ $x ]->slug );

				$words = explode( ' ', $words );

				foreach ( $words as $word ) {
					++$word_count;
					++$total_words;
					$word = str_replace( ' ', '', $word );
					$word = str_replace( '=', '', $word );
					$word = str_replace( ',', '', $word );
					$word = trim( $word, "?!.,'()`”:“@$#-%\=/" );
					$word = trim( $word, '"' );
					$word = trim( $word );
					$word = preg_replace( '/[0-9]/', '', $word );
					$word = preg_replace( "/[^a-zA-z'’`éèùâêîôûçëïü]/i", '', $word );
					if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {

								// Add the error to a new fixed holding array
								$hold    = new SplFixedArray( 4 );
								$hold[0] = $word;
								$hold[1] = $cats_list[ $x ]->post_title;
								$hold[2] = $cats_list[ $x ]->term_id;
								$hold[3] = 'Category Slug';

								$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
								$error_list[ $error_count ] = $hold;
								++$error_count;
					}
				}
			}

			if ( wpscx_yoast_is_active() && isset( $cats_list[ $x ]->term_id ) && ! empty( $tax_meta['category'][ $cats_list[ $x ]->term_id ] ) ) {
				$term_id   = $cats_list[ $x ]->term_id;
				$term_name = isset( $cats_list[ $x ]->name ) ? $cats_list[ $x ]->name : '';
				foreach ( $tax_map as $field_key => $page_type ) {
					if ( empty( $tax_meta['category'][ $term_id ][ $field_key ] ) ) {
						continue;
					}
					$field_value = $tax_meta['category'][ $term_id ][ $field_key ];
					if ( false !== strpos( $field_key, 'focuskeywords' ) || false !== strpos( $field_key, 'keywordsynonyms' ) ) {
						$field_value = wpscx_yoast_flatten_json_text( $field_key, $field_value );
					}
					$field_value = wpscx_clean_all( $field_value, $wpsc_settings );
					$words       = explode( ' ', $field_value );
					foreach ( $words as $word ) {
						++$word_count;
						++$total_words;
						$word = trim( $word, "'`”“" );
						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							$hold    = new SplFixedArray( 4 );
							$hold[0] = $word;
							$hold[1] = $term_name;
							$hold[2] = $term_id;
							$hold[3] = $page_type;
							$error_list->setSize( $error_list->getSize() + 1 );
							$error_list[ $error_count ] = $hold;
							++$error_count;
						}
					}
				}
			}

			if ( wpscx_rank_math_is_active() && isset( $cats_list[ $x ]->term_id ) && ! empty( $rm_term_meta[ $cats_list[ $x ]->term_id ] ) ) {
				$term_id   = $cats_list[ $x ]->term_id;
				$term_name = isset( $cats_list[ $x ]->name ) ? $cats_list[ $x ]->name : '';
				$rm_map    = wpscx_rank_math_term_keys( 'category' );
				foreach ( $rm_map as $meta_key => $page_type ) {
					if ( empty( $rm_term_meta[ $term_id ][ $meta_key ] ) ) {
						continue;
					}
					$field_value = wpscx_clean_all( $rm_term_meta[ $term_id ][ $meta_key ], $wpsc_settings );
					$words       = explode( ' ', $field_value );
					foreach ( $words as $word ) {
						++$word_count;
						++$total_words;
						$word = trim( $word, "'`”“" );
						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							$hold    = new SplFixedArray( 4 );
							$hold[0] = $word;
							$hold[1] = $term_name;
							$hold[2] = $term_id;
							$hold[3] = $page_type;
							$error_list->setSize( $error_list->getSize() + 1 );
							$error_list[ $error_count ] = $hold;
							++$error_count;
						}
					}
				}
			}

			if ( wpscx_aioseo_is_active() && isset( $cats_list[ $x ]->term_id ) && ! empty( $aioseo_term_meta[ $cats_list[ $x ]->term_id ] ) ) {
				$term_id    = $cats_list[ $x ]->term_id;
				$term_name  = isset( $cats_list[ $x ]->name ) ? $cats_list[ $x ]->name : '';
				$aioseo_row = $aioseo_term_meta[ $term_id ];
				foreach ( array(
					'title'       => $aioseo_cat_types['title'],
					'description' => $aioseo_cat_types['description'],
				) as $column => $page_type ) {
					if ( empty( $aioseo_row->$column ) ) {
						continue;
					}
					$field_value = wpscx_clean_all( $aioseo_row->$column, $wpsc_settings );
					$words       = explode( ' ', $field_value );
					foreach ( $words as $word ) {
						++$word_count;
						++$total_words;
						$word = trim( $word, "'`”“" );
						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							$hold    = new SplFixedArray( 4 );
							$hold[0] = $word;
							$hold[1] = $term_name;
							$hold[2] = $term_id;
							$hold[3] = $page_type;
							$error_list->setSize( $error_list->getSize() + 1 );
							$error_list[ $error_count ] = $hold;
							++$error_count;
						}
					}
				}
			}

			unset( $cats_list[ $x ] );
		}

		$end = round( microtime( true ), 5 );
		wpscx_print_debug( 'Category EPS', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $error_count, $wpscx_debug_q );

		return $error_list->getSize();
	}

	function check_yoast_free( $is_running = false, $wpsc_haystack = null, $log_debug = true ) {
		$start         = round( microtime( true ), 5 );
		$wpscx_debug_q = wpscx_debug_queries_at_start();
		if ( ! is_plugin_active( 'wordpress-seo/wp-seo.php' ) && ! is_plugin_active( 'all-in-one-seo-pack/all_in_one_seo_pack.php' ) && ! is_plugin_active( 'seo-by-rank-math/rank-math.php' ) ) {
			return 1;
		}

		global $wpscx_scan_delay;
		$sql_count = 0;

		global $wpdb;
		global $wpsc_haystack;
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name    = $wpdb->prefix . 'postmeta';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$words_table   = $wpdb->prefix . 'spellcheck_words';
		$word_count    = 0;
		$error_count   = 0;
		$total_words   = 0;
		set_time_limit( 6000 );
		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit
		wpscx_set_global_vars();
		global $wpsc_settings;

		$max_pages         = intval( $wpsc_settings[138]->option_value );
		$wpscx_dict_list   = $wpdb->get_results( "SELECT * FROM $dict_table;" );
		$wpscx_ignore_list = $wpdb->get_results( "SELECT * FROM $words_table WHERE ignore_word=true;" );

		$posts_table = $wpdb->prefix . 'posts';
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray( 1 );

		if ( 'true' === $wpsc_settings[136]->option_value ) {
			$page_status = true; } else {
			$page_status = false; }

			if ( 'true' === $wpsc_settings[137]->option_value ) {
				$post_status = true; } else {
						$post_status = false; }

				$ain_active   = is_plugin_active( 'all-in-one-seo-pack/all_in_one_seo_pack.php' );
				$yoast_active = is_plugin_active( 'wordpress-seo/wp-seo.php' );
				$rm_active    = is_plugin_active( 'seo-by-rank-math/rank-math.php' );
				$yoast_types  = $yoast_active ? wpscx_yoast_postmeta_desc_keys() : array();
				$ain_types    = $ain_active ? wpscx_aioseo_postmeta_desc_keys() : array();
				$rm_types     = $rm_active ? wpscx_rank_math_postmeta_desc_keys() : array();
				$where_parts  = array();
				if ( $yoast_active ) {
					$where_parts[] = wpscx_yoast_postmeta_sql_or_clause( array_keys( $yoast_types ) );
				}
				if ( $ain_active ) {
					$where_parts[] = wpscx_yoast_postmeta_sql_or_clause( array_keys( $ain_types ) );
				}
				if ( $rm_active ) {
					$where_parts[] = wpscx_yoast_postmeta_sql_or_clause( array_keys( $rm_types ) );
				}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: $wpdb->prefix . 'postmeta', $max_pages is sanitized with intval(), query contains only hardcoded meta_key values
				$results = SplFixedArray::fromArray( $wpdb->get_results( 'SELECT post_id, meta_value, meta_key FROM ' . $table_name . ' WHERE ' . implode( ' OR ', $where_parts ) . ' LIMIT ' . $max_pages ) );
				++$sql_count;

				for ( $x = 0;$x < $results->getSize();$x++ ) {
					$desc       = $results[ $x ];
					$post_store = $desc;
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'posts', WHERE value from prior database query
					$page_results = $wpdb->get_results( 'SELECT * FROM ' . $posts_table . ' WHERE ID=' . $desc->post_id );

					if ( ! isset( $page_results[0]->post_title ) ) {
						continue;
					}
					if ( 'draft' === $page_results[0]->post_status && 'page' === $page_results[0]->post_type && ! $page_status ) {
						continue;
					}
					if ( 'draft' === $page_results[0]->post_status && 'page' !== $page_results[0]->post_type && ! $post_status ) {
						continue;
					}

					$desc_type  = $desc->meta_key;
					$desc_value = $desc->meta_value;
					if ( $yoast_active && isset( $yoast_types[ $desc_type ] ) && ( '_yoast_wpseo_focuskeywords' === $desc_type || '_yoast_wpseo_keywordsynonyms' === $desc_type ) ) {
						$desc_value = wpscx_yoast_flatten_json_text( $desc_type, $desc_value );
					}
					$desc  = html_entity_decode( wp_strip_all_tags( $desc_value ), ENT_QUOTES, 'utf-8' );
					$desc  = wpscx_clean_all( $desc, $wpsc_settings );
					$words = explode( ' ', $desc );

					foreach ( $words as $word ) {
						++$word_count;
						++$total_words;
						$word = trim( $word, "'`”“" );
						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
									$page_type = null;
							if ( $yoast_active && isset( $yoast_types[ $desc_type ] ) ) {
								$page_type = $yoast_types[ $desc_type ];
							} elseif ( $ain_active && isset( $ain_types[ $desc_type ] ) ) {
								$page_type = $ain_types[ $desc_type ];
							} elseif ( $rm_active && isset( $rm_types[ $desc_type ] ) ) {
								$page_type = $rm_types[ $desc_type ];
							}
							if ( null === $page_type ) {
								break;
							}
									$hold    = new SplFixedArray( 4 );
									$hold[0] = $word;
									$hold[1] = $page_results[0]->post_title;
									$hold[2] = $page_results[0]->ID;
									$hold[3] = $page_type;

									$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
									$error_list[ $error_count ] = $hold;
									++$error_count;
						}
					}
					unset( $results[ $x ] );
				}

				if ( $ain_active ) {
					$aioseo_posts_table = $wpdb->prefix . 'aioseo_posts';
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table names from $wpdb->prefix; LIMIT uses intval().
					$kp_sql = 'SELECT ap.post_id, ap.keyphrases, p.post_title, p.post_status, p.post_type FROM ' . $aioseo_posts_table . ' ap INNER JOIN ' . $posts_table . ' p ON ap.post_id = p.ID WHERE ap.keyphrases IS NOT NULL AND ap.keyphrases != "" LIMIT ' . intval( $max_pages );
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL built above from safe table names and intval LIMIT.
					$kp_rows = $wpdb->get_results( $kp_sql );
					foreach ( (array) $kp_rows as $kp_row ) {
						if ( 'draft' === $kp_row->post_status && 'page' === $kp_row->post_type && ! $page_status ) {
							continue;
						}
						if ( 'draft' === $kp_row->post_status && 'page' !== $kp_row->post_type && ! $post_status ) {
							continue;
						}
						$kp_text = wpscx_aioseo_flatten_keyphrases( $kp_row->keyphrases );
						if ( '' === $kp_text ) {
							continue;
						}
						$desc  = wpscx_clean_all( html_entity_decode( wp_strip_all_tags( $kp_text ), ENT_QUOTES, 'utf-8' ), $wpsc_settings );
						$words = explode( ' ', $desc );
						foreach ( $words as $word ) {
							++$word_count;
							++$total_words;
							$word = trim( $word, "'`”“" );
							if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
								$hold    = new SplFixedArray( 4 );
								$hold[0] = $word;
								$hold[1] = $kp_row->post_title;
								$hold[2] = $kp_row->post_id;
								$hold[3] = 'All in One SEO Focus Keyphrase';
								$error_list->setSize( $error_list->getSize() + 1 );
								$error_list[ $error_count ] = $hold;
								++$error_count;
							}
						}
					}
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table names from $wpdb->prefix; LIMIT uses intval().
					$schema_sql = 'SELECT ap.post_id, ap.schema, p.post_title, p.post_status, p.post_type FROM ' . $aioseo_posts_table . ' ap INNER JOIN ' . $posts_table . ' p ON ap.post_id = p.ID WHERE p.post_type="product" AND ap.schema IS NOT NULL AND ap.schema != "" LIMIT ' . intval( $max_pages );
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL built above from safe table names and intval LIMIT.
					$schema_rows = $wpdb->get_results( $schema_sql );
					foreach ( (array) $schema_rows as $schema_row ) {
						if ( 'draft' === $schema_row->post_status && ! $page_status ) {
							continue;
						}
						if ( 'draft' === $schema_row->post_status && ! $post_status ) {
							continue;
						}
						$texts = wpscx_aioseo_product_schema_texts( $schema_row->schema );
						foreach ( array(
							'description' => 'All in One SEO Product Schema Description',
							'brand'       => 'All in One SEO Product Brand',
						) as $prop => $schema_page_type ) {
							if ( '' === $texts[ $prop ] ) {
								continue;
							}
							$desc  = wpscx_clean_all( html_entity_decode( wp_strip_all_tags( $texts[ $prop ] ), ENT_QUOTES, 'utf-8' ), $wpsc_settings );
							$words = explode( ' ', $desc );
							foreach ( $words as $word ) {
								++$word_count;
								++$total_words;
								$word = trim( $word, "'`”“" );
								if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
									$hold    = new SplFixedArray( 4 );
									$hold[0] = $word;
									$hold[1] = $schema_row->post_title;
									$hold[2] = $schema_row->post_id;
									$hold[3] = $schema_page_type;
									$error_list->setSize( $error_list->getSize() + 1 );
									$error_list[ $error_count ] = $hold;
									++$error_count;
								}
							}
						}
					}
				}

				$end = round( microtime( true ), 5 );
				wpscx_print_debug( 'Seo Desc EPS', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $error_count, $wpscx_debug_q );

				return $error_list->getSize();
	}


	function check_seo_titles_free( $is_running = false, $wpsc_haystack = null, $log_debug = true ) {
		$start         = round( microtime( true ), 5 );
		$wpscx_debug_q = wpscx_debug_queries_at_start();
		if ( ! is_plugin_active( 'wordpress-seo/wp-seo.php' ) && ! is_plugin_active( 'all-in-one-seo-pack/all_in_one_seo_pack.php' ) && ! is_plugin_active( 'seo-by-rank-math/rank-math.php' ) ) {
			return 1;
		}

		global $wpscx_scan_delay;
		$sql_count = 0;

		global $wpdb;
		global $wpsc_haystack;
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name    = $wpdb->prefix . 'postmeta';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$words_table   = $wpdb->prefix . 'spellcheck_words';
		$word_count    = 0;
		$error_count   = 0;
		$total_words   = 0;
		set_time_limit( 6000 );
		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit
		wpscx_set_global_vars();

		$max_pages = intval( $wpsc_settings[138]->option_value );
		if ( 0 === $max_pages ) {
			$max_pages = PHP_INT_MAX;
		}
				$wpscx_dict_list = $wpdb->get_results( "SELECT * FROM $dict_table;" );
		$wpscx_ignore_list       = $wpdb->get_results( "SELECT * FROM $words_table WHERE ignore_word=true;" );
		if ( null === $wpsc_haystack ) {
			$loc      = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );
			$contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

			$contents  = str_replace( "\r\n", "\n", $contents );
			$dict_file = explode( "\n", $contents );

			$wpsc_haystack = wpscx_dictionary_init( $dict_file );
		}

		$words_table = $wpdb->prefix . 'spellcheck_words';
		$posts_table = $wpdb->prefix . 'posts';
		if ( ! $is_running ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			++$sql_count;
			$start_time = time();
		}
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray( 1 );

		if ( 'true' === $wpsc_settings[136]->option_value ) {
			$page_status = true; } else {
			$page_status = false; }

			if ( 'true' === $wpsc_settings[137]->option_value ) {
				$post_status = true; } else {
						$post_status = false; }

				$ain_active   = is_plugin_active( 'all-in-one-seo-pack/all_in_one_seo_pack.php' );
				$yoast_active = is_plugin_active( 'wordpress-seo/wp-seo.php' );
				$rm_active    = is_plugin_active( 'seo-by-rank-math/rank-math.php' );
				$yoast_types  = $yoast_active ? wpscx_yoast_postmeta_title_keys() : array();
				$ain_types    = $ain_active ? wpscx_aioseo_postmeta_title_keys() : array();
				$rm_types     = $rm_active ? wpscx_rank_math_postmeta_title_keys() : array();
				$where_parts  = array();
				if ( $yoast_active ) {
					$where_parts[] = wpscx_yoast_postmeta_sql_or_clause( array_keys( $yoast_types ) );
				}
				if ( $ain_active ) {
					$where_parts[] = wpscx_yoast_postmeta_sql_or_clause( array_keys( $ain_types ) );
				}
				if ( $rm_active ) {
					$where_parts[] = wpscx_yoast_postmeta_sql_or_clause( array_keys( $rm_types ) );
				}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: $wpdb->prefix . 'postmeta', $max_pages is sanitized with intval(), query contains only hardcoded meta_key values
				$results = SplFixedArray::fromArray( $wpdb->get_results( 'SELECT post_id, meta_value, meta_key FROM ' . $table_name . ' WHERE ' . implode( ' OR ', $where_parts ) . ' LIMIT ' . $max_pages ) );
				++$sql_count;

				for ( $x = 0;$x < $results->getSize();$x++ ) {
					$desc       = $results[ $x ];
					$post_store = $desc;
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'posts', WHERE value from prior database query
					$page_results = $wpdb->get_results( 'SELECT ID, post_title, post_status FROM ' . $posts_table . ' WHERE ID=' . $desc->post_id );

					if ( ! isset( $page_results[0]->post_title ) ) {
						continue;
					}
					if ( 'draft' === $page_results[0]->post_status && ! $page_status ) {
						continue;
					}
					if ( 'draft' === $page_results[0]->post_status && ! $post_status ) {
						continue;
					}

					$desc_type  = $desc->meta_key;
					$desc_value = $desc->meta_value;
					if ( $yoast_active && isset( $yoast_types[ $desc_type ] ) && ( '_yoast_wpseo_focuskeywords' === $desc_type || '_yoast_wpseo_keywordsynonyms' === $desc_type ) ) {
						$desc_value = wpscx_yoast_flatten_json_text( $desc_type, $desc_value );
					}
					$desc  = wpscx_clean_all( $desc_value, $wpsc_settings );
					$words = explode( ' ', $desc );

					foreach ( $words as $word ) {
						++$word_count;
						++$total_words;
						$word = trim( $word, "'`”“" );
						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
									$page_type = null;
							if ( $yoast_active && isset( $yoast_types[ $desc_type ] ) ) {
								$page_type = $yoast_types[ $desc_type ];
							} elseif ( $ain_active && isset( $ain_types[ $desc_type ] ) ) {
								$page_type = $ain_types[ $desc_type ];
							} elseif ( $rm_active && isset( $rm_types[ $desc_type ] ) ) {
								$page_type = $rm_types[ $desc_type ];
							}
							if ( null === $page_type ) {
								break;
							}
									$hold    = new SplFixedArray( 4 );
									$hold[0] = $word;
									$hold[1] = $page_results[0]->post_title;
									$hold[2] = $page_results[0]->ID;
									$hold[3] = $page_type;

									$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
									$error_list[ $error_count ] = $hold;
									++$error_count;
						}
					}
					unset( $results[ $x ] );
				}

				if ( $ain_active ) {
					$aioseo_posts_table = $wpdb->prefix . 'aioseo_posts';
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table names from $wpdb->prefix; LIMIT uses intval().
					$schema_sql = 'SELECT ap.post_id, ap.schema, p.post_title, p.post_status, p.post_type FROM ' . $aioseo_posts_table . ' ap INNER JOIN ' . $posts_table . ' p ON ap.post_id = p.ID WHERE p.post_type="product" AND ap.schema IS NOT NULL AND ap.schema != "" LIMIT ' . intval( $max_pages );
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL built above from safe table names and intval LIMIT.
					$schema_rows = $wpdb->get_results( $schema_sql );
					foreach ( (array) $schema_rows as $schema_row ) {
						if ( 'draft' === $schema_row->post_status && ! $page_status ) {
							continue;
						}
						if ( 'draft' === $schema_row->post_status && ! $post_status ) {
							continue;
						}
						$texts = wpscx_aioseo_product_schema_texts( $schema_row->schema );
						if ( '' === $texts['name'] ) {
							continue;
						}
						$desc  = wpscx_clean_all( $texts['name'], $wpsc_settings );
						$words = explode( ' ', $desc );
						foreach ( $words as $word ) {
							++$word_count;
							++$total_words;
							$word = trim( $word, "'`”“" );
							if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
								$hold    = new SplFixedArray( 4 );
								$hold[0] = $word;
								$hold[1] = $schema_row->post_title;
								$hold[2] = $schema_row->post_id;
								$hold[3] = 'All in One SEO Product Schema Name';
								$error_list->setSize( $error_list->getSize() + 1 );
								$error_list[ $error_count ] = $hold;
								++$error_count;
							}
						}
					}
				}

				if ( $yoast_active ) {
					$titles        = get_option( 'wpseo_titles', array() );
					$archive_map   = wpscx_yoast_archive_field_map();
					$post_types    = get_post_types( array( 'public' => true ), 'objects' );
					foreach ( $post_types as $post_type ) {
						foreach ( $archive_map as $prefix => $page_type ) {
							$option_key = $prefix . $post_type->name;
							if ( empty( $titles[ $option_key ] ) ) {
								continue;
							}
							$archive_value = wpscx_clean_all( $titles[ $option_key ], $wpsc_settings );
							$words         = explode( ' ', $archive_value );
							foreach ( $words as $word ) {
								++$word_count;
								++$total_words;
								$word = trim( $word, "'`”“" );
								if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
									$hold    = new SplFixedArray( 4 );
									$hold[0] = $word;
									$hold[1] = $post_type->labels->name;
									$hold[2] = 0;
									$hold[3] = $page_type;
									$error_list->setSize( $error_list->getSize() + 1 );
									$error_list[ $error_count ] = $hold;
									++$error_count;
								}
							}
						}
					}
				}

				$end = round( microtime( true ), 5 );
				wpscx_print_debug( 'SEO Title EPS', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $error_count, $wpscx_debug_q );

				return $error_list->getSize();
	}


	function check_smart_slider3_eps_free( $is_running = false, $wpsc_haystack = null, $log_debug = true ) {
		$start         = round( microtime( true ), 5 );
		$wpscx_debug_q = wpscx_debug_queries_at_start();
		if ( ! is_plugin_active( 'smart-slider-3/smart-slider-3.php' ) ) {
			return 1;
		}

		$sql_count = 0;

		global $wpdb;
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;

		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$word_count    = 0;
		$error_count   = 0;
		$total_words   = 0;
		set_time_limit( 6000 );
		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit
		wpscx_set_global_vars();

		if ( null === $wpsc_haystack ) {
			$wpscx_dict_list   = $wpdb->get_results( "SELECT * FROM $dict_table;" );
			$wpscx_ignore_list = $wpdb->get_results( 'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE ignore_word=true;' );
			$loc               = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );
			$contents          = wp_remote_retrieve_body( wp_remote_get( $loc ) );
			$contents          = str_replace( "\r\n", "\n", $contents );
			$dict_file         = explode( "\n", $contents );
			$wpsc_haystack     = wpscx_dictionary_init( $dict_file );
		}

		if ( ! $is_running ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			++$sql_count;
		}

		$error_list = new SplFixedArray( 1 );

		$sliders_table = $wpdb->prefix . 'nextend2_smartslider3_sliders';
		$slides_table  = $wpdb->prefix . 'nextend2_smartslider3_slides';
		$sql = "SELECT sl1.id AS slider_id, sl1.title AS slider_title, sl2.id AS slide_id, sl2.title AS slide_title, sl2.description AS slide_description, sl2.slide AS slide_json FROM " . $sliders_table . " sl1 LEFT JOIN " . $slides_table . " sl2 ON sl2.slider = sl1.id WHERE sl1.slider_status = 'published' AND (sl2.published = 1 OR sl2.id IS NULL)";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names built from $wpdb->prefix + fixed suffixes; no user input.
		$rows = $wpdb->get_results( $sql );
		++$sql_count;

		$seen_slider_ids = array();

		foreach ( $rows as $row ) {
			$slider_id = (int) $row->slider_id;

			if ( ! isset( $seen_slider_ids[ $slider_id ] ) ) {
				$seen_slider_ids[ $slider_id ] = true;
				$group_title                   = isset( $row->slider_title ) ? (string) $row->slider_title : '';
				$word_list                     = wpscx_clean_all( $group_title, $wpsc_settings );
				$words                         = explode( ' ', $word_list );
				foreach ( $words as $word ) {
					++$word_count;
					++$total_words;
					$word = trim( $word, "\"`\xEF\xBF\xBD\xEF\xBF\xBD" );
					if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
						$hold    = new SplFixedArray( 3 );
						$hold[0] = $word;
						$hold[1] = $group_title;
						$hold[2] = $slider_id;
						$error_list->setSize( $error_list->getSize() + 1 );
						$error_list[ $error_count ] = $hold;
						++$error_count;
					}
				}
			}

			if ( null === $row->slide_id || '' === $row->slide_id ) {
				continue;
			}

			$slide_id    = (int) $row->slide_id;
			$slide_label = isset( $row->slide_title ) ? (string) $row->slide_title : '';

			$word_list = wpscx_clean_all( $slide_label, $wpsc_settings );
			$words     = explode( ' ', $word_list );
			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;
				$word = trim( $word, "\"`\xEF\xBF\xBD\xEF\xBF\xBD" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
					$hold    = new SplFixedArray( 3 );
					$hold[0] = $word;
					$hold[1] = $slide_label;
					$hold[2] = $slide_id;
					$error_list->setSize( $error_list->getSize() + 1 );
					$error_list[ $error_count ] = $hold;
					++$error_count;
				}
			}

			$caption_raw = isset( $row->slide_description ) ? (string) $row->slide_description : '';
			$word_list   = wpscx_clean_all( $caption_raw, $wpsc_settings );
			$words       = explode( ' ', $word_list );
			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;
				$word = trim( $word, "\"`\xEF\xBF\xBD\xEF\xBF\xBD" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
					$hold    = new SplFixedArray( 3 );
					$hold[0] = $word;
					$hold[1] = $slide_label;
					$hold[2] = $slide_id;
					$error_list->setSize( $error_list->getSize() + 1 );
					$error_list[ $error_count ] = $hold;
					++$error_count;
				}
			}

			$slide_json = isset( $row->slide_json ) ? (string) $row->slide_json : '';
			$decoded    = json_decode( $slide_json, true );
			$extracted  = '';
			if ( null !== $decoded ) {
				$extracted = wpscx_extract_smartslider3_slide_text( $decoded );
			}
			$word_list = wpscx_clean_all( $extracted, $wpsc_settings );
			$words     = explode( ' ', $word_list );
			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;
				$word = trim( $word, "\"`\xEF\xBF\xBD\xEF\xBF\xBD" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
					$hold    = new SplFixedArray( 3 );
					$hold[0] = $word;
					$hold[1] = $slide_label;
					$hold[2] = $slide_id;
					$error_list->setSize( $error_list->getSize() + 1 );
					$error_list[ $error_count ] = $hold;
					++$error_count;
				}
			}
		}

		$end = round( microtime( true ), 5 );
		if ( $log_debug ) {
			wpscx_print_debug( 'Smart Slider 3 EPS', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $error_count, $wpscx_debug_q );
		}

		return $error_list->getSize();
	}

	function check_metaslider_eps_free( $is_running = false, $wpsc_haystack = null, $log_debug = true ) {
		$start         = round( microtime( true ), 5 );
		$wpscx_debug_q = wpscx_debug_queries_at_start();
		if ( ! is_plugin_active( 'ml-slider/ml-slider.php' ) ) {
			return 1;
		}

		$sql_count = 0;

		global $wpdb;
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;

		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$word_count    = 0;
		$error_count   = 0;
		$total_words   = 0;
		set_time_limit( 6000 );
		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit
		wpscx_set_global_vars();

		if ( null === $wpsc_haystack ) {
			$wpscx_dict_list   = $wpdb->get_results( "SELECT * FROM $dict_table;" );
			$wpscx_ignore_list = $wpdb->get_results( 'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE ignore_word=true;' );
			$loc               = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );
			$contents          = wp_remote_retrieve_body( wp_remote_get( $loc ) );
			$contents          = str_replace( "\r\n", "\n", $contents );
			$dict_file         = explode( "\n", $contents );
			$wpsc_haystack     = wpscx_dictionary_init( $dict_file );
		}

		if ( ! $is_running ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			++$sql_count;
		}

		$error_list = new SplFixedArray( 1 );

		$posts_table    = $wpdb->posts;
		$terms_table    = $wpdb->terms;
		$tt_table       = $wpdb->term_taxonomy;
		$tr_table       = $wpdb->term_relationships;
		$postmeta_table = $wpdb->postmeta;

		$sql = "SELECT p_slider.ID AS slider_id, p_slider.post_title AS slider_title, p_slide.ID AS slide_id, p_slide.post_title AS slide_title, p_slide.post_excerpt AS slide_excerpt, p_slide.post_content AS slide_content, pm_hidden.meta_value AS is_hidden FROM {$posts_table} p_slider INNER JOIN {$terms_table} t ON t.slug = CAST(p_slider.ID AS CHAR) INNER JOIN {$tt_table} tt ON tt.term_id = t.term_id AND tt.taxonomy = 'ml-slider' LEFT JOIN {$tr_table} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id LEFT JOIN {$posts_table} p_slide ON p_slide.ID = tr.object_id AND p_slide.post_type IN ('ml-slide', 'attachment') AND p_slide.post_status IN ('publish', 'inherit') LEFT JOIN {$postmeta_table} pm_hidden ON pm_hidden.post_id = p_slide.ID AND pm_hidden.meta_key = '_meta_slider_slide_is_hidden' WHERE p_slider.post_type = 'ml-slider' AND p_slider.post_status = 'publish' ORDER BY p_slider.ID";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table names from $wpdb->* properties; no user input.
		$rows = $wpdb->get_results( $sql );
		++$sql_count;

		$slide_ids = array();
		foreach ( $rows as $row ) {
			if ( null === $row->slide_id || '' === $row->slide_id ) {
				continue;
			}
			$hidden_raw = isset( $row->is_hidden ) ? (string) $row->is_hidden : '';
			if ( '1' === $hidden_raw || 'true' === $hidden_raw ) {
				continue;
			}
			$slide_ids[ (int) $row->slide_id ] = true;
		}
		$slide_meta = wpscx_metaslider_load_slide_meta( array_keys( $slide_ids ) );
		++$sql_count;

		$seen_slider_ids = array();

		foreach ( $rows as $row ) {
			$slider_id = (int) $row->slider_id;

			if ( ! isset( $seen_slider_ids[ $slider_id ] ) ) {
				$seen_slider_ids[ $slider_id ] = true;
				$group_title                   = isset( $row->slider_title ) ? (string) $row->slider_title : '';
				wpscx_metaslider_add_errors_from_text( $group_title, $group_title, $slider_id, $error_list, $error_count, $word_count, $total_words, $wpsc_haystack, $wpsc_settings );
			}

			if ( null === $row->slide_id || '' === $row->slide_id ) {
				continue;
			}

			$hidden_raw = isset( $row->is_hidden ) ? (string) $row->is_hidden : '';
			if ( '1' === $hidden_raw || 'true' === $hidden_raw ) {
				continue;
			}

			$slide_id    = (int) $row->slide_id;
			$slide_label = isset( $row->slide_title ) ? (string) $row->slide_title : '';

			wpscx_metaslider_add_errors_from_text( $slide_label, $slide_label, $slide_id, $error_list, $error_count, $word_count, $total_words, $wpsc_haystack, $wpsc_settings );

			$caption_raw = isset( $row->slide_excerpt ) ? (string) $row->slide_excerpt : '';
			wpscx_metaslider_add_errors_from_text( $caption_raw, $slide_label, $slide_id, $error_list, $error_count, $word_count, $total_words, $wpsc_haystack, $wpsc_settings );

			$content_raw = isset( $row->slide_content ) ? (string) $row->slide_content : '';
			wpscx_metaslider_add_errors_from_text( $content_raw, $slide_label, $slide_id, $error_list, $error_count, $word_count, $total_words, $wpsc_haystack, $wpsc_settings );

			$slide_meta_row = isset( $slide_meta[ $slide_id ] ) ? $slide_meta[ $slide_id ] : array();

			if ( ! wpscx_metaslider_inherit_enabled( $slide_meta, $slide_id, 'title' ) ) {
				$image_title_raw = isset( $slide_meta_row['ml-slider_title'] ) ? (string) $slide_meta_row['ml-slider_title'] : '';
				wpscx_metaslider_add_errors_from_text( $image_title_raw, $slide_label, $slide_id, $error_list, $error_count, $word_count, $total_words, $wpsc_haystack, $wpsc_settings );
			}

			if ( ! wpscx_metaslider_inherit_enabled( $slide_meta, $slide_id, 'alt' ) ) {
				$image_alt_raw = isset( $slide_meta_row['_wp_attachment_image_alt'] ) ? (string) $slide_meta_row['_wp_attachment_image_alt'] : '';
				wpscx_metaslider_add_errors_from_text( $image_alt_raw, $slide_label, $slide_id, $error_list, $error_count, $word_count, $total_words, $wpsc_haystack, $wpsc_settings );
			}

			$link_alt_raw = isset( $slide_meta_row['ml-slider_link-alt'] ) ? (string) $slide_meta_row['ml-slider_link-alt'] : '';
			wpscx_metaslider_add_errors_from_text( $link_alt_raw, $slide_label, $slide_id, $error_list, $error_count, $word_count, $total_words, $wpsc_haystack, $wpsc_settings );
		}

		$end = round( microtime( true ), 5 );
		if ( $log_debug ) {
			wpscx_print_debug( 'Meta Slider EPS', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $error_count, $wpscx_debug_q );
		}

		return $error_list->getSize();
	}

	function check_media_titles_free( $is_running = false, $wpsc_haystack = null, $log_debug = true ) {
		$start         = round( microtime( true ), 5 );
		$wpscx_debug_q = wpscx_debug_queries_at_start();
		global $wpdb;
		global $wpsc_haystack;
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$sql_count = 0;

		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$post_table    = $wpdb->prefix . 'posts';
		$word_count    = 0;
		$total_words   = 0;
		$media_count   = 0;
		$error_count   = 0;
		set_time_limit( 6000 );
		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit
		wpscx_set_global_vars();
		global $wpsc_settings;

		$max_pages         = intval( $wpsc_settings[138]->option_value );
		$wpscx_dict_list   = $wpdb->get_results( "SELECT * FROM $dict_table;" );
		$wpscx_ignore_list = $wpdb->get_results( 'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE ignore_word=true;' );

		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray( 1 );

		$posts_list = SplFixedArray::fromArray( $wpdb->get_results( "SELECT post_content, post_title, post_excerpt, ID from $post_table WHERE post_type='attachment'" ) );
		++$sql_count;

		for ( $x = 0;$x < $posts_list->getSize();$x++ ) {
			++$media_count;

			// ******CHECK MEDIA TITLES******
			$word_list = html_entity_decode( wp_strip_all_tags( $posts_list[ $x ]->post_title ), ENT_QUOTES, 'utf-8' );
			$word_list = wpscx_clean_all( $word_list, $wpsc_settings );
			$words     = explode( ' ', $word_list );

			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;
				$word = trim( $word, "'`”“" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {

							$hold    = new SplFixedArray( 4 );
							$hold[0] = $word;
							$hold[1] = $posts_list[ $x ]->post_title;
							$hold[2] = $posts_list[ $x ]->ID;
							$hold[3] = 'Media Title';

							$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
							$error_list[ $error_count ] = $hold;
							++$error_count;
				}
			}

			// ******CHECK MEDIA DESCRIPTION******
			$word_list = html_entity_decode( wp_strip_all_tags( $posts_list[ $x ]->post_content ), ENT_QUOTES, 'utf-8' );
			$word_list = wpscx_clean_all( $word_list, $wpsc_settings );
			$words     = explode( ' ', $word_list );

			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;
				$word = trim( $word, "'`”“" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {

							$hold    = new SplFixedArray( 4 );
							$hold[0] = $word;
							$hold[1] = $posts_list[ $x ]->post_title;
							$hold[2] = $posts_list[ $x ]->ID;
							$hold[3] = 'Media Description';

							$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
							$error_list[ $error_count ] = $hold;
							++$error_count;
				}
			}

			// ******CHECK MEDIA CAPTION******
			$word_list = html_entity_decode( wp_strip_all_tags( $posts_list[ $x ]->post_excerpt ), ENT_QUOTES, 'utf-8' );
			$word_list = wpscx_clean_all( $word_list, $wpsc_settings );
			$words     = explode( ' ', $word_list );

			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;
				$word = trim( $word, "'`”“" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {

							$hold    = new SplFixedArray( 4 );
							$hold[0] = $word;
							$hold[1] = $posts_list[ $x ]->post_title;
							$hold[2] = $posts_list[ $x ]->ID;
							$hold[3] = 'Media Caption';

							$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
							$error_list[ $error_count ] = $hold;
							++$error_count;
				}
			}

			// ******CHECK MEDIA ALT TEXT******
			$word_list = html_entity_decode( wp_strip_all_tags( get_post_meta( $posts_list[ $x ]->ID, '_wp_attachment_image_alt', true ) ), ENT_QUOTES, 'utf-8' );
			$word_list = wpscx_clean_all( $word_list, $wpsc_settings );
			$words     = explode( ' ', $word_list );

			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;
				$word = trim( $word, "'`”“" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {

							$hold    = new SplFixedArray( 4 );
							$hold[0] = $word;
							$hold[1] = $posts_list[ $x ]->post_title;
							$hold[2] = $posts_list[ $x ]->ID;
							$hold[3] = 'Media Alternate Text';

							$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
							$error_list[ $error_count ] = $hold;
							++$error_count;
				}
			}
			unset( $posts_list[ $x ] );
		}

		$end = round( microtime( true ), 5 );
		wpscx_print_debug( 'Media EPS', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $error_count, $wpscx_debug_q );

		return $error_list->getSize();
	}

	function check_woocommerce_free( $is_running = false, $wpscx_haystack = null, $log_debug = true ) {
		$start         = round( microtime( true ), 5 );
		$wpscx_debug_q = wpscx_debug_queries_at_start();
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return 1;
		}

		global $wpscx_scan_delay;
		$sql_count = 0;

		global $wpdb;
		global $wpsc_haystack;
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		set_time_limit( 6000 );
		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit
		wpscx_set_global_vars();
		global $wpsc_settings;

		$max_pages         = intval( $wpsc_settings[138]->option_value );
		$wpscx_dict_list   = $wpdb->get_results( "SELECT * FROM $dict_table;" );
		$wpscx_ignore_list = $wpdb->get_results( 'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE ignore_word=true;' );

		$total_posts = $max_pages;
		if ( 0 === $total_posts ) {
			$total_posts = PHP_INT_MAX;
		}
		if ( 0 === $max_pages ) {
			$max_pages = PHP_INT_MAX;
		}

		if ( null === $wpscx_haystack ) {
			$loc      = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );
			$contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

			$contents  = str_replace( "\r\n", "\n", $contents );
			$dict_file = explode( "\n", $contents );

			$wpsc_haystack = wpscx_dictionary_init( $dict_file );
		} else {
			$wpsc_haystack = $wpscx_haystack;
		}

		$word_count  = 0;
		$error_count = 0;
		$total_words = 0;
		$post_count  = 0;
		$word_count  = 0;
		if ( ! $is_running ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			++$sql_count;
			$start_time = time();
		}
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray( 1 );

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: $wpdb->prefix . 'spellcheck_ignore', query contains only hardcoded value "page"
		$ignore_posts = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );
		++$sql_count;

		$scan_post_ids = array();

		$posts_list = get_posts(
			array(
				'posts_per_page' => $max_pages,
				'post_type'      => 'product',
				'post_status'    => array(
					'publish',
					'draft',
				),
			)
		);
		++$sql_count;

		foreach ( $posts_list as $post ) {
			array_shift( $posts_list );
			$ignore_flag = 'false';
			foreach ( $ignore_posts as $ignore_check ) {
				if ( strtoupper( trim( $post->post_title ) ) === strtoupper( trim( $ignore_check->keyword ) ) ) {
					$ignore_flag = 'true';
				}
			}
			if ( 'true' === $ignore_flag ) {
				continue; }
			++$post_count;
			$scan_post_ids[] = $post->ID;
						$words_list = $post->post_content;

						// Product Description
			$words_list = wpscx_clean_all( $words_list, $wpsc_settings );
			$words      = explode( ' ', $words_list );

			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;

				$word = trim( $word, "'`”“" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
					if ( $post_count <= $total_posts ) {
						// $word = addslashes($word);

						// Add the error to a new fixed holding array
						$hold                            = new SplFixedArray( 4 );
						$hold[0]                         = $word;
						$hold[1]                         = $post->post_title;
						$hold[2]                         = $post->ID;
												$hold[3] = 'WooCommerce Product';

						$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
						$error_list[ $error_count ] = $hold;
						++$error_count;
					} else {

					}
				}
			}

						// Product Excerpt
						$words_list = $post->post_excerpt;
			$words_list             = wpscx_clean_all( $words_list, $wpsc_settings );
			$words                  = explode( ' ', $words_list );

			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;

				$word = trim( $word, "'`”“" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
					if ( $post_count <= $total_posts ) {
						// $word = addslashes($word);

						// Add the error to a new fixed holding array
						$hold                            = new SplFixedArray( 4 );
						$hold[0]                         = $word;
						$hold[1]                         = $post->post_title;
						$hold[2]                         = $post->ID;
												$hold[3] = 'WooCommerce Short Description';

						$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
						$error_list[ $error_count ] = $hold;
						++$error_count;
					} else {

					}
				}
			}

						// Product Title
						$words_list = $post->post_title;
			$words_list             = wpscx_clean_all( $words_list, $wpsc_settings );
			$words                  = explode( ' ', $words_list );

			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;

				$word = trim( $word, "'`”“" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
					if ( $post_count <= $total_posts ) {
						// $word = addslashes($word);

						// Add the error to a new fixed holding array
						$hold                            = new SplFixedArray( 4 );
						$hold[0]                         = $word;
						$hold[1]                         = $post->post_title;
						$hold[2]                         = $post->ID;
												$hold[3] = 'WooCommerce Title';

						$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
						$error_list[ $error_count ] = $hold;
						++$error_count;
					} else {

					}
				}
			}

			if ( wpscx_yoast_is_active() ) {
				$yoast_types = array_merge(
					wpscx_yoast_postmeta_desc_keys(),
					wpscx_yoast_postmeta_title_keys()
				);
				foreach ( $yoast_types as $meta_key => $page_type ) {
					$meta_value = get_post_meta( $post->ID, $meta_key, true );
					if ( '' === $meta_value ) {
						continue;
					}
					if ( '_yoast_wpseo_focuskeywords' === $meta_key || '_yoast_wpseo_keywordsynonyms' === $meta_key ) {
						$meta_value = wpscx_yoast_flatten_json_text( $meta_key, $meta_value );
						if ( '' === $meta_value ) {
							continue;
						}
					}
					$words_list = wpscx_clean_all( $meta_value, $wpsc_settings );
					$words      = explode( ' ', $words_list );

					foreach ( $words as $word ) {
						++$word_count;
						++$total_words;

						$word = trim( $word, "'`”“" );
						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							if ( $post_count <= $total_posts ) {
								$hold    = new SplFixedArray( 4 );
								$hold[0] = $word;
								$hold[1] = $post->post_title;
								$hold[2] = $post->ID;
								$hold[3] = $page_type;

								$error_list->setSize( $error_list->getSize() + 1 );
								$error_list[ $error_count ] = $hold;
								++$error_count;
							}
						}
					}
				}
			}

			if ( wpscx_rank_math_is_active() ) {
				$rm_types = array_merge(
					wpscx_rank_math_postmeta_desc_keys(),
					wpscx_rank_math_postmeta_title_keys()
				);
				foreach ( $rm_types as $meta_key => $page_type ) {
					$meta_value = get_post_meta( $post->ID, $meta_key, true );
					if ( '' === $meta_value ) {
						continue;
					}
					$words_list = wpscx_clean_all( $meta_value, $wpsc_settings );
					$words      = explode( ' ', $words_list );

					foreach ( $words as $word ) {
						++$word_count;
						++$total_words;

						$word = trim( $word, "'`”“" );
						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							if ( $post_count <= $total_posts ) {
								$hold    = new SplFixedArray( 4 );
								$hold[0] = $word;
								$hold[1] = $post->post_title;
								$hold[2] = $post->ID;
								$hold[3] = $page_type;

								$error_list->setSize( $error_list->getSize() + 1 );
								$error_list[ $error_count ] = $hold;
								++$error_count;
							}
						}
					}
				}
			}
		}

		// Check Variations
		$variations_list = get_posts(
			array(
				'posts_per_page' => $max_pages,
				'post_type'      => 'product_variation',
				'post_status'    => array(
					'publish',
					'draft',
				),
			)
		);
		++$sql_count;

		foreach ( $variations_list as $post ) {
			array_shift( $variations_list );
			$ignore_flag = 'false';
			foreach ( $ignore_posts as $ignore_check ) {
				if ( strtoupper( trim( $post->post_title ) ) === strtoupper( trim( $ignore_check->keyword ) ) ) {
					$ignore_flag = 'true';
				}
			}
			if ( 'true' === $ignore_flag ) {
				continue;
			}
			++$post_count;
			$scan_post_ids[] = $post->ID;

			// Variation Content
			$words_list = $post->post_content;
			$words_list = wpscx_clean_all( $words_list, $wpsc_settings );
			$words      = explode( ' ', $words_list );

			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;
				$word = trim( $word, "'`\u{201c}\u{201d}" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
					if ( $post_count <= $total_posts ) {
						$hold    = new SplFixedArray( 4 );
						$hold[0] = $word;
						$hold[1] = $post->post_title;
						$hold[2] = $post->ID;
						$hold[3] = 'WooCommerce Variation';

						$error_list->setSize( $error_list->getSize() + 1 );
						$error_list[ $error_count ] = $hold;
						++$error_count;
					}
				}
			}

			// Variation Short Description
			$words_list = $post->post_excerpt;
			$words_list = wpscx_clean_all( $words_list, $wpsc_settings );
			$words      = explode( ' ', $words_list );

			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;
				$word = trim( $word, "'`\u{201c}\u{201d}" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
					if ( $post_count <= $total_posts ) {
						$hold    = new SplFixedArray( 4 );
						$hold[0] = $word;
						$hold[1] = $post->post_title;
						$hold[2] = $post->ID;
						$hold[3] = 'WooCommerce Variation Short Description';

						$error_list->setSize( $error_list->getSize() + 1 );
						$error_list[ $error_count ] = $hold;
						++$error_count;
					}
				}
			}

			// Variation Title
			$words_list = $post->post_title;
			$words_list = wpscx_clean_all( $words_list, $wpsc_settings );
			$words      = explode( ' ', $words_list );

			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;
				$word = trim( $word, "'`\u{201c}\u{201d}" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
					if ( $post_count <= $total_posts ) {
						$hold    = new SplFixedArray( 4 );
						$hold[0] = $word;
						$hold[1] = $post->post_title;
						$hold[2] = $post->ID;
						$hold[3] = 'WooCommerce Variation Title';

						$error_list->setSize( $error_list->getSize() + 1 );
						$error_list[ $error_count ] = $hold;
						++$error_count;
					}
				}
			}
		}

				// Check Categories
				$title_table = $wpdb->prefix . 'terms';
				$desc_table  = $wpdb->prefix . 'term_taxonomy';
				$cats_list   = SplFixedArray::fromArray( $wpdb->get_results( "SELECT a.term_id, a.description, b.name FROM $desc_table a, $title_table b WHERE a.taxonomy='product_cat' AND a.term_id = b.term_id;" ) );
				$rm_wc_cat_map = wpscx_rank_math_is_active() ? wpscx_rank_math_term_keys( 'product_cat' ) : array();
				$aioseo_wc_cat_meta = wpscx_aioseo_term_rows_for_taxonomy( 'product_cat' );
				$aioseo_wc_cat_types = wpscx_aioseo_term_page_types( 'product_cat' );

		for ( $x = 0; $x < $cats_list->getSize(); $x++ ) {
				$words = array();

			if ( isset( $cats_list[ $x ]->name ) ) {
				$words = $cats_list[ $x ]->name;

				$words = wpscx_clean_all( $words, $wpsc_settings );

				$words = explode( ' ', $words );

				foreach ( $words as $word ) {
						++$word_count;
						++$total_words;
						$word = trim( $word, "'`”“" );
					if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {

										// Add the error to a new fixed holding array
										$hold    = new SplFixedArray( 4 );
										$hold[0] = $word;
										$hold[1] = $cats_list[ $x ]->name;
										$hold[2] = $cats_list[ $x ]->term_id;
										$hold[3] = 'WooCommerce Category Title';

										$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
										$error_list[ $error_count ] = $hold;
										++$error_count;
					}
				}
			}

			if ( isset( $cats_list[ $x ]->description ) ) {
				$words = $cats_list[ $x ]->description;

				$words = wpscx_clean_all( $words, $wpsc_settings );

				$words = explode( ' ', $words );

				foreach ( $words as $word ) {
							++$word_count;
							++$total_words;
							$word = trim( $word, "'`”“" );
					if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {

								// Add the error to a new fixed holding array
								$hold    = new SplFixedArray( 4 );
								$hold[0] = $word;
								$hold[1] = $cats_list[ $x ]->name;
								$hold[2] = $cats_list[ $x ]->term_id;
								$hold[3] = 'WooCommerce Category Description';

								$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
								$error_list[ $error_count ] = $hold;
								++$error_count;
					}
				}
			}

			if ( wpscx_rank_math_is_active() && isset( $cats_list[ $x ]->term_id ) ) {
				$term_id   = $cats_list[ $x ]->term_id;
				$term_name = $cats_list[ $x ]->name;
				foreach ( $rm_wc_cat_map as $meta_key => $page_type ) {
					$field_value = get_term_meta( $term_id, $meta_key, true );
					if ( '' === $field_value ) {
						continue;
					}
					$field_value = wpscx_clean_all( $field_value, $wpsc_settings );
					$words       = explode( ' ', $field_value );
					foreach ( $words as $word ) {
						++$word_count;
						++$total_words;
						$word = trim( $word, "'`”“" );
						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							$hold    = new SplFixedArray( 4 );
							$hold[0] = $word;
							$hold[1] = $term_name;
							$hold[2] = $term_id;
							$hold[3] = $page_type;
							$error_list->setSize( $error_list->getSize() + 1 );
							$error_list[ $error_count ] = $hold;
							++$error_count;
						}
					}
				}
			}

			if ( wpscx_aioseo_is_active() && isset( $cats_list[ $x ]->term_id ) && ! empty( $aioseo_wc_cat_meta[ $cats_list[ $x ]->term_id ] ) ) {
				$term_id    = $cats_list[ $x ]->term_id;
				$term_name  = $cats_list[ $x ]->name;
				$aioseo_row = $aioseo_wc_cat_meta[ $term_id ];
				foreach ( array(
					'title'       => $aioseo_wc_cat_types['title'],
					'description' => $aioseo_wc_cat_types['description'],
				) as $column => $page_type ) {
					if ( empty( $aioseo_row->$column ) ) {
						continue;
					}
					$field_value = wpscx_clean_all( $aioseo_row->$column, $wpsc_settings );
					$words       = explode( ' ', $field_value );
					foreach ( $words as $word ) {
						++$word_count;
						++$total_words;
						$word = trim( $word, "'`”“" );
						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							$hold    = new SplFixedArray( 4 );
							$hold[0] = $word;
							$hold[1] = $term_name;
							$hold[2] = $term_id;
							$hold[3] = $page_type;
							$error_list->setSize( $error_list->getSize() + 1 );
							$error_list[ $error_count ] = $hold;
							++$error_count;
						}
					}
				}
			}
		}

				// Check Tags
				$tags_list = SplFixedArray::fromArray( $wpdb->get_results( "SELECT a.term_id, a.description, b.name FROM $desc_table a, $title_table b WHERE a.taxonomy='product_tag' AND a.term_id = b.term_id;" ) );
				$rm_wc_tag_map = wpscx_rank_math_is_active() ? wpscx_rank_math_term_keys( 'product_tag' ) : array();
				$aioseo_wc_tag_meta = wpscx_aioseo_term_rows_for_taxonomy( 'product_tag' );
				$aioseo_wc_tag_types = wpscx_aioseo_term_page_types( 'product_tag' );

		for ( $x = 0; $x < $tags_list->getSize(); $x++ ) {
				$words = array();

			if ( isset( $tags_list[ $x ]->name ) ) {
				$words = $tags_list[ $x ]->name;

				$words = wpscx_clean_all( $words, $wpsc_settings );

				$words = explode( ' ', $words );

				foreach ( $words as $word ) {
						++$word_count;
						++$total_words;
						$word = trim( $word, "'`”“" );
					if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {

										// Add the error to a new fixed holding array
										$hold    = new SplFixedArray( 4 );
										$hold[0] = $word;
										$hold[1] = $tags_list[ $x ]->name;
										$hold[2] = $tags_list[ $x ]->term_id;
										$hold[3] = 'WooCommerce Tag Title';

										$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
										$error_list[ $error_count ] = $hold;
										++$error_count;
					}
				}
			}

			if ( isset( $tags_list[ $x ]->description ) ) {
				$words = $tags_list[ $x ]->description;

				$words = wpscx_clean_all( $words, $wpsc_settings );

				$words = explode( ' ', $words );

				foreach ( $words as $word ) {
							++$word_count;
							++$total_words;
							$word = trim( $word, "'`”“" );
					if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {

								// Add the error to a new fixed holding array
								$hold    = new SplFixedArray( 4 );
								$hold[0] = $word;
								$hold[1] = $tags_list[ $x ]->name;
								$hold[2] = $tags_list[ $x ]->term_id;
								$hold[3] = 'WooCommerce Tag Description';

								$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
								$error_list[ $error_count ] = $hold;
								++$error_count;
					}
				}
			}

			if ( wpscx_rank_math_is_active() && isset( $tags_list[ $x ]->term_id ) ) {
				$term_id   = $tags_list[ $x ]->term_id;
				$term_name = $tags_list[ $x ]->name;
				foreach ( $rm_wc_tag_map as $meta_key => $page_type ) {
					$field_value = get_term_meta( $term_id, $meta_key, true );
					if ( '' === $field_value ) {
						continue;
					}
					$field_value = wpscx_clean_all( $field_value, $wpsc_settings );
					$words       = explode( ' ', $field_value );
					foreach ( $words as $word ) {
						++$word_count;
						++$total_words;
						$word = trim( $word, "'`”“" );
						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							$hold    = new SplFixedArray( 4 );
							$hold[0] = $word;
							$hold[1] = $term_name;
							$hold[2] = $term_id;
							$hold[3] = $page_type;
							$error_list->setSize( $error_list->getSize() + 1 );
							$error_list[ $error_count ] = $hold;
							++$error_count;
						}
					}
				}
			}

			if ( wpscx_aioseo_is_active() && isset( $tags_list[ $x ]->term_id ) && ! empty( $aioseo_wc_tag_meta[ $tags_list[ $x ]->term_id ] ) ) {
				$term_id    = $tags_list[ $x ]->term_id;
				$term_name  = $tags_list[ $x ]->name;
				$aioseo_row = $aioseo_wc_tag_meta[ $term_id ];
				foreach ( array(
					'title'       => $aioseo_wc_tag_types['title'],
					'description' => $aioseo_wc_tag_types['description'],
				) as $column => $page_type ) {
					if ( empty( $aioseo_row->$column ) ) {
						continue;
					}
					$field_value = wpscx_clean_all( $aioseo_row->$column, $wpsc_settings );
					$words       = explode( ' ', $field_value );
					foreach ( $words as $word ) {
						++$word_count;
						++$total_words;
						$word = trim( $word, "'`”“" );
						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							$hold    = new SplFixedArray( 4 );
							$hold[0] = $word;
							$hold[1] = $term_name;
							$hold[2] = $term_id;
							$hold[3] = $page_type;
							$error_list->setSize( $error_list->getSize() + 1 );
							$error_list[ $error_count ] = $hold;
							++$error_count;
						}
					}
				}
			}
		}

		// Check Global Attributes (pa_* taxonomies)
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe, LIKE value is sanitized with $wpdb->esc_like
		$attrs_list = SplFixedArray::fromArray( $wpdb->get_results( $wpdb->prepare( "SELECT a.term_id, a.description, b.name FROM $desc_table a, $title_table b WHERE a.taxonomy LIKE %s AND a.term_id = b.term_id", $wpdb->esc_like( 'pa_' ) . '%' ) ) );
		++$sql_count;

		for ( $x = 0; $x < $attrs_list->getSize(); $x++ ) {
			if ( isset( $attrs_list[ $x ]->name ) ) {
				$words = $attrs_list[ $x ]->name;
				$words = wpscx_clean_all( $words, $wpsc_settings );
				$words = explode( ' ', $words );

				foreach ( $words as $word ) {
					++$word_count;
					++$total_words;
					$word = trim( $word, "'`\u{201c}\u{201d}" );
					if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
						$hold    = new SplFixedArray( 4 );
						$hold[0] = $word;
						$hold[1] = $attrs_list[ $x ]->name;
						$hold[2] = $attrs_list[ $x ]->term_id;
						$hold[3] = 'WooCommerce Attribute Title';

						$error_list->setSize( $error_list->getSize() + 1 );
						$error_list[ $error_count ] = $hold;
						++$error_count;
					}
				}
			}

			if ( isset( $attrs_list[ $x ]->description ) ) {
				$words = $attrs_list[ $x ]->description;
				$words = wpscx_clean_all( $words, $wpsc_settings );
				$words = explode( ' ', $words );

				foreach ( $words as $word ) {
					++$word_count;
					++$total_words;
					$word = trim( $word, "'`\u{201c}\u{201d}" );
					if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
						$hold    = new SplFixedArray( 4 );
						$hold[0] = $word;
						$hold[1] = $attrs_list[ $x ]->name;
						$hold[2] = $attrs_list[ $x ]->term_id;
						$hold[3] = 'WooCommerce Attribute Description';

						$error_list->setSize( $error_list->getSize() + 1 );
						$error_list[ $error_count ] = $hold;
						++$error_count;
					}
				}
			}
		}

		// Check Brands (only if product_brand taxonomy exists)
		if ( taxonomy_exists( 'product_brand' ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are safe: $wpdb->prefix + hardcoded strings
			$brands_list = SplFixedArray::fromArray( $wpdb->get_results( "SELECT a.term_id, a.description, b.name FROM $desc_table a, $title_table b WHERE a.taxonomy='product_brand' AND a.term_id = b.term_id;" ) );
			++$sql_count;

			for ( $x = 0; $x < $brands_list->getSize(); $x++ ) {
				if ( isset( $brands_list[ $x ]->name ) ) {
					$words = $brands_list[ $x ]->name;
					$words = wpscx_clean_all( $words, $wpsc_settings );
					$words = explode( ' ', $words );

					foreach ( $words as $word ) {
						++$word_count;
						++$total_words;
						$word = trim( $word, "'`\u{201c}\u{201d}" );
						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							$hold    = new SplFixedArray( 4 );
							$hold[0] = $word;
							$hold[1] = $brands_list[ $x ]->name;
							$hold[2] = $brands_list[ $x ]->term_id;
							$hold[3] = 'WooCommerce Brand Title';

							$error_list->setSize( $error_list->getSize() + 1 );
							$error_list[ $error_count ] = $hold;
							++$error_count;
						}
					}
				}

				if ( isset( $brands_list[ $x ]->description ) ) {
					$words = $brands_list[ $x ]->description;
					$words = wpscx_clean_all( $words, $wpsc_settings );
					$words = explode( ' ', $words );

					foreach ( $words as $word ) {
						++$word_count;
						++$total_words;
						$word = trim( $word, "'`\u{201c}\u{201d}" );
						if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
							$hold    = new SplFixedArray( 4 );
							$hold[0] = $word;
							$hold[1] = $brands_list[ $x ]->name;
							$hold[2] = $brands_list[ $x ]->term_id;
							$hold[3] = 'WooCommerce Brand Description';

							$error_list->setSize( $error_list->getSize() + 1 );
							$error_list[ $error_count ] = $hold;
							++$error_count;
						}
					}
				}
			}
		}

		// Check Purchase Notes (_purchase_note meta on products + variations)
		if ( ! empty( $scan_post_ids ) ) {
			$id_placeholders = implode( ', ', array_fill( 0, count( $scan_post_ids ), '%d' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholders built from count(); table names are $wpdb->postmeta/$wpdb->posts (safe)
			$notes_query = $wpdb->prepare(
				"SELECT pm.post_id, pm.meta_value, p.post_title FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE pm.meta_key = %s AND pm.post_id IN ($id_placeholders)",
				array_merge( array( '_purchase_note' ), $scan_post_ids )
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- query fully built with prepare() above
			$notes_list = $wpdb->get_results( $notes_query );
			++$sql_count;

			foreach ( $notes_list as $note_row ) {
				$ignore_flag = 'false';
				foreach ( $ignore_posts as $ignore_check ) {
					if ( strtoupper( trim( $note_row->post_title ) ) === strtoupper( trim( $ignore_check->keyword ) ) ) {
						$ignore_flag = 'true';
					}
				}
				if ( 'true' === $ignore_flag ) {
					continue;
				}

				$words_list = $note_row->meta_value;
				$words_list = wpscx_clean_all( $words_list, $wpsc_settings );
				$words      = explode( ' ', $words_list );

				foreach ( $words as $word ) {
					++$word_count;
					++$total_words;
					$word = trim( $word, "'`\u{201c}\u{201d}" );
					if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
						$hold    = new SplFixedArray( 4 );
						$hold[0] = $word;
						$hold[1] = $note_row->post_title;
						$hold[2] = $note_row->post_id;
						$hold[3] = 'WooCommerce Purchase Note';

						$error_list->setSize( $error_list->getSize() + 1 );
						$error_list[ $error_count ] = $hold;
						++$error_count;
					}
				}
			}
		}

				// Check Coupons
				$coupon_list = get_posts(
					array(
						'posts_per_page' => $max_pages,
						'post_type'      => 'shop_coupon',
						'post_status'    => array(
							'publish',
							'draft',
						),
					)
				);
				++$sql_count;

				$coupon_count = 0;

		foreach ( $coupon_list as $post ) {
			$ignore_flag = 'false';
			foreach ( $ignore_posts as $ignore_check ) {
				if ( strtoupper( trim( $post->post_title ) ) === strtoupper( trim( $ignore_check->keyword ) ) ) {
					$ignore_flag = 'true';
				}
			}
			if ( 'true' === $ignore_flag ) {
				continue;
			}
			++$coupon_count;
			$words_list = $post->post_excerpt;
			$words_list = wpscx_clean_all( $words_list, $wpsc_settings );
			$words      = explode( ' ', $words_list );

			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;
				$word = trim( $word, "'`”“" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
					if ( $coupon_count <= $total_posts ) {
						$hold    = new SplFixedArray( 4 );
						$hold[0] = $word;
						$hold[1] = $post->post_title;
						$hold[2] = $post->ID;
						$hold[3] = 'WooCommerce Coupon';

						$error_list->setSize( $error_list->getSize() + 1 );
						$error_list[ $error_count ] = $hold;
						++$error_count;
					}
				}
			}

			// Coupon Content
			$words_list = $post->post_content;
			$words_list = wpscx_clean_all( $words_list, $wpsc_settings );
			$words      = explode( ' ', $words_list );

			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;
				$word = trim( $word, "'`\u{201c}\u{201d}" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
					if ( $coupon_count <= $total_posts ) {
						$hold    = new SplFixedArray( 4 );
						$hold[0] = $word;
						$hold[1] = $post->post_title;
						$hold[2] = $post->ID;
						$hold[3] = 'WooCommerce Coupon Content';

						$error_list->setSize( $error_list->getSize() + 1 );
						$error_list[ $error_count ] = $hold;
						++$error_count;
					}
				}
			}
		}

		$end = round( microtime( true ), 5 );
		wpscx_print_debug( 'WooCommerce', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), $error_count, $wpscx_debug_q );

		return $error_list->getSize();
	}

	function check_woocommerce_coupon_free( $is_running = false, $wpscx_haystack = null ) {
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return 0;
		}

		global $wpscx_scan_delay;
		$sql_count = 0;

		global $wpdb;
		global $wpsc_haystack;
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		set_time_limit( 6000 );
		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit

		wpscx_set_global_vars();
		global $wpsc_settings;

		$max_pages         = intval( $wpsc_settings[138]->option_value );
		$wpscx_dict_list   = $wpdb->get_results( "SELECT * FROM $dict_table;" );
		$wpscx_ignore_list = $wpdb->get_results( 'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE ignore_word=true;' );
		$loc      = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );
		$contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

		$contents  = str_replace( "\r\n", "\n", $contents );
		$dict_file = explode( "\n", $contents );

		$wpsc_haystack = wpscx_dictionary_init( $dict_file );

		$total_posts = $max_pages;
		$word_count  = 0;
		$error_count = 0;
		$total_words = 0;
		$post_count  = 0;
		$word_count  = 0;
		if ( ! $is_running ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			++$sql_count;
			$start_time = time();
		}
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray( 1 );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: $wpdb->prefix . 'spellcheck_ignore', query contains only hardcoded value "page"
		$ignore_posts = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );
		++$sql_count;

		$posts_list = get_posts(
			array(
				'posts_per_page' => $max_pages,
				'post_type'      => 'shop_coupon',
				'post_status'    => array(
					'publish',
					'draft',
				),
			)
		);
		++$sql_count;

		foreach ( $posts_list as $post ) {
			array_shift( $posts_list );
			$ignore_flag = 'false';
			foreach ( $ignore_posts as $ignore_check ) {
				if ( strtoupper( trim( $post->post_title ) ) === strtoupper( trim( $ignore_check->keyword ) ) ) {
					$ignore_flag = 'true';
				}
			}
			if ( 'true' === $ignore_flag ) {
				continue; }
			++$post_count;
			$words_list = $post->post_excerpt;
			$words_list = wpscx_clean_all( $words_list, $wpsc_settings );
			$words      = explode( ' ', $words_list );

			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;
				$word = trim( $word, "'`”“" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
					if ( $post_count <= $total_posts ) {
						// $word = addslashes($word);

						// Add the error to a new fixed holding array
						$hold    = new SplFixedArray( 4 );
						$hold[0] = $word;
						$hold[1] = $post->post_title;
						$hold[2] = $post->ID;
						$hold[3] = 'WooCommerce Coupon';

						$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
						$error_list[ $error_count ] = $hold;
						++$error_count;
					} else {

					}
				}
			}

			// Coupon Content
			$words_list = $post->post_content;
			$words_list = wpscx_clean_all( $words_list, $wpsc_settings );
			$words      = explode( ' ', $words_list );

			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;
				$word = trim( $word, "'`\u{201c}\u{201d}" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
					if ( $post_count <= $total_posts ) {
						$hold    = new SplFixedArray( 4 );
						$hold[0] = $word;
						$hold[1] = $post->post_title;
						$hold[2] = $post->ID;
						$hold[3] = 'WooCommerce Coupon Content';

						$error_list->setSize( $error_list->getSize() + 1 );
						$error_list[ $error_count ] = $hold;
						++$error_count;
					}
				}
			}
		}

		return $error_count;
	}

	function check_woocommerce_excerpt_free( $is_running = false, $wpscx_haystack = null ) {
		global $wpscx_scan_delay;
		$sql_count = 0;

		global $wpdb;
		global $wpsc_haystack;
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		set_time_limit( 6000 );
		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit

		$max_pages         = intval( $wpsc_settings[138]->option_value );
		$wpscx_dict_list   = $wpdb->get_results( "SELECT * FROM $dict_table;" );
		$wpscx_ignore_list = $wpdb->get_results( 'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE ignore_word=true;' );

		wpscx_set_global_vars();
		global $wpsc_settings;

		$loc      = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );
		$contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

		$contents  = str_replace( "\r\n", "\n", $contents );
		$dict_file = explode( "\n", $contents );

		$wpsc_haystack = wpscx_dictionary_init( $dict_file );

		$word_count  = 0;
		$error_count = 0;
		$total_words = 0;
		$post_count  = 0;
		$word_count  = 0;
		if ( ! $is_running ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			$start_time = time();
		}
		global $wpscx_ignore_list;
		global $wpscx_dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray( 1 );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: $wpdb->prefix . 'spellcheck_ignore', query contains only hardcoded value "page"
		$ignore_posts = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );

		$posts_list = get_posts(
			array(
				'posts_per_page' => $max_pages,
				'post_type'      => 'product',
				'post_status'    => array(
					'publish',
					'draft',
				),
			)
		);

		foreach ( $posts_list as $post ) {
			array_shift( $posts_list );
			$ignore_flag = 'false';
			foreach ( $ignore_posts as $ignore_check ) {
				if ( strtoupper( trim( $post->post_title ) ) === strtoupper( trim( $ignore_check->keyword ) ) ) {
					$ignore_flag = 'true';
				}
			}
			if ( 'true' === $ignore_flag ) {
				continue; }
			++$post_count;
			$words_list = $post->post_excerpt;
			$words_list = wpscx_clean_all( $words_list, $wpsc_settings );
			$words      = explode( ' ', $words_list );

			foreach ( $words as $word ) {
				++$word_count;
				++$total_words;
				$word = trim( $word, "'`”“" );
				if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
								// $word = addslashes($word);

								// Add the error to a new fixed holding array
								$hold    = new SplFixedArray( 3 );
								$hold[0] = $word;
								$hold[1] = $post->post_title;
								$hold[2] = $post->ID;

								$error_list->setSize( $error_list->getSize() + 1 ); // Increase the size of the main error array by 1
								$error_list[ $error_count ] = $hold;
								++$error_count;
				}
			}
		}

		return $error_count;
	}


	function check_errors( $wpsc_haystack ) {
		global $wpdb;
		global $wpscx_ent_included;
		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		set_time_limit( 600 );

		$check_errors_setup_start = round( microtime( true ), 5 );
		$check_errors_setup_q     = wpscx_debug_queries_at_start();

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: $wpdb->prefix . 'spellcheck_options', query contains no WHERE clause
		$settings = $wpdb->get_results( 'SELECT option_value FROM ' . $options_table );

		$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'pro_word_count' ) );

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: $wpdb->prefix . 'spellcheck_options', query contains only hardcoded value "language_setting"
		$language_setting = $wpdb->get_results( 'SELECT option_value from ' . $options_table . ' WHERE option_name="language_setting";' );

		wpscx_print_debug( 'Check Errors Setup', round( microtime( true ) - $check_errors_setup_start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), 'N/A', $check_errors_setup_q );

		$error_count = 0;
		$last_count  = 0;

		$error_count += $this->check_posts( true ) - 1;
		$last_count   = $error_count;

		$error_count += $this->check_pages( true ) - 1;
		$last_count   = $error_count;

			$error_count += $this->check_widgets_free( true, $wpsc_haystack ) - 1;
		$last_count       = $error_count;

		$error_count += $this->check_menus_free( true, $wpsc_haystack ) - 1;
		$last_count   = $error_count;

		$error_count += $this->check_page_title_free( true, $wpsc_haystack ) - 1;
		$last_count   = $error_count;

		$error_count += $this->check_post_title_free( true, $wpsc_haystack ) - 1;
		$last_count   = $error_count;

		$error_count += $this->check_post_tags_free( true, $wpsc_haystack ) - 1;
		$last_count   = $error_count;

		$error_count += $this->check_post_categories_free( true, $wpsc_haystack ) - 1;
		$last_count   = $error_count;

		$error_count += $this->check_yoast_free( true, $wpsc_haystack ) - 1;
		$last_count   = $error_count;

		$error_count += $this->check_seo_titles_free( true, $wpsc_haystack ) - 1;
		$last_count   = $error_count;

		$error_count += $this->check_smart_slider3_eps_free( true, $wpsc_haystack ) - 1;
		$last_count   = $error_count;

		$error_count += $this->check_metaslider_eps_free( true, $wpsc_haystack ) - 1;
		$last_count   = $error_count;

		$error_count += $this->check_woocommerce_free( true, $wpsc_haystack ) - 1;
		$last_count   = $error_count;

		$error_count += $this->check_media_titles_free( true, $wpsc_haystack ) - 1;
		$last_count   = $error_count;

		$error_count += $this->check_author_seotitle_free( true, $wpsc_haystack ) - 1;
		$last_count   = $error_count;

		$error_count += $this->check_author_seodesc_free( true, $wpsc_haystack ) - 1;
		$last_count   = $error_count;

		$check_errors_finalize_q     = wpscx_debug_queries_at_start();
		$check_errors_finalize_start = round( microtime( true ), 5 );
		$wpdb->update( $options_table, array( 'option_value' => $error_count ), array( 'option_name' => 'pro_word_count' ) );
		$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'free_sip' ) );
		wpscx_print_debug( 'Check Errors Finalize', round( microtime( true ) - $check_errors_finalize_start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), 'N/A', $check_errors_finalize_q );
	}

	function scan_single( $post_id ) {
		// Initialization
		ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit
		global $wpdb;
				global $wpscx_ent_included;
		wpscx_set_global_vars();
		global $wpsc_settings;
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$error_list    = array();

		// Set up Dictionary haystack based on language settings
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: $wpdb->prefix . 'spellcheck_options', query contains only hardcoded value "language_setting"
		$language_setting = $wpdb->get_results( 'SELECT option_value from ' . $options_table . ' WHERE option_name="language_setting";' );

		if ( $wpscx_ent_included ) {
			global $wpscx_ent_loc;
			// echo "Location: " . plugins_url( '/admin/dict/' . $wpsc_settings[11]->option_value . '.pws', $wpscx_ent_loc) . '<br>';
			// echo "Base Location: " . plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ ) . "<br>";
			$loc = plugins_url( '/admin/dict/' . $wpsc_settings[11]->option_value . '.pws', $wpscx_ent_loc );
			// $loc       = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );
			$contents  = wp_remote_retrieve_body( wp_remote_get( $loc ) );
			$contents  = str_replace( "\r\n", "\n", $contents );
			$dict_file = explode( "\n", $contents );
		} else {
			// echo 'Ent Included: false <br>';
			$loc       = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );
			$contents  = wp_remote_retrieve_body( wp_remote_get( $loc ) );
			$contents  = str_replace( "\r\n", "\n", $contents );
			$dict_file = explode( "\n", $contents );
		}

		$wpsc_haystack = wpscx_dictionary_init( $dict_file );

				$page = get_page( $post_id ); // Get the page/post

		$page_content = $page->post_content; // Get the content from the page/post

		// Cleanup the content for scanning
		$page_content = do_shortcode( $page_content );
		$page_content = wpscx_content_filter( $page_content );
		$page_content = wpscx_clean_all( $page_content, $wpsc_settings );
		$words        = explode( ' ', $page_content );

		foreach ( $words as $word ) {
			$word = trim( $word, "'`”“" );
			if ( '' === $word || preg_match( '/^[^a-zA-ZÀÂÆÈÉÊËÎÏÔŒÙÛÜŸÁÉÍÑÓÚÜ]+$/', $word ) ) {
				continue;
			}

			// Check the word against the dictionary haystack
			if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
					array_push(
						$error_list,
						array(
							'word'      => $word,
							'page_type' => 'Page Content',
						)
					);
			}
		}

		return $error_list; // Return the error list to the on page editor for highlighting
	}
}
