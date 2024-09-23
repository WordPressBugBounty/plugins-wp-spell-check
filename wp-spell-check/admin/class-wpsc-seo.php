<?php

    const WPSCX_EMPTY = 'Empty Field';
class Wpscx_Seo_Scanner extends wpscx_scanner {
    
	function check_page_title_empty( $rng_seed = 0, $is_running = false ) {
		$start = round( microtime( true ), 5 );
		ini_set( 'memory_limit', '512M' );
		global $wpdb;
		global $wpscx_base_page_max;
		global $wpsc_settings;
		if ( sizeof( (array) $wpsc_settings ) < 1 ) {
			wpscx_set_global_vars();
		}
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name    = $wpdb->prefix . 'spellcheck_empty';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$page_table    = $wpdb->prefix . 'posts';
		set_time_limit( 6000 );
		$error_count = 0;
		$total_count = 0;
		$sql_count   = 0;
		$error_list  = array();

		if ( ! $is_running && ! empty( $is_running ) ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_scan_in_progress' ) );
			$sql_count++;
			$start_time = time();
		}

		wpscx_set_global_vars();

		if ( 'true' === $wpsc_settings[136]->option_value ) {
			$post_status = " AND (post_status='publish' OR post_status='draft')"; } else {
			$post_status = " AND post_status='publish'"; }

			$page_list = SplFixedArray::fromArray( $wpdb->get_results( "SELECT post_title, ID FROM $page_table WHERE post_type='page'$post_status LIMIT $wpscx_base_page_max" ) );

			$ignore_pages = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );
			$sql_count++;
                        $ignore_list = $wpdb->get_results("SELECT page_name, page_type, page_id FROM $table_name WHERE ignore_word=1");

			for ( $x = 0; $x < $page_list->getSize(); $x++ ) {
				$total_count++;
				$word_list = $page_list[ $x ]->post_title;
                                
                                $ignore_field = false;
                                foreach($ignore_list as $row) {
                                    if ($row->page_type == 'Page Title' && $row->page_id == $page_list[ $x ]->ID) $ignore_field = true;
                                }
                                if ($ignore_field) continue;

                                if ( wpscx_check_empty( $word_list, false ) ) {
					$error_count++;
					array_push(
						$error_list,
						array(
							'word'      => WPSCX_EMPTY,
							'page_name' => 'Empty Page Title',
							'page_type' => 'Page Title',
							'page_id'   => $page_list[ $x ]->ID,
						)
					);
				}
			}

			wpscx_sql_insert( $error_list, WPSCX_EMPTY, $table_name );
			$wpdb->update( $options_table, array( 'option_value' => $total_count ), array( 'option_name' => 'empty_page_count' ) );
			$sql_count++;

			$counter = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name ='empty_checked';" );
			$sql_count++;
			$total_count = $total_count + intval( $counter[0]->option_value );
			$wpdb->update( $options_table, array( 'option_value' => $total_count ), array( 'option_name' => 'empty_checked' ) );
			$sql_count++;
			if ( ! $is_running && ! empty( $is_running ) ) {
				$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'empty_scan_in_progress' ) );
				$sql_count++;
				$end_time   = time();
				$total_time = wpscx_time_elapsed( $end_time - $start_time );
				//$wpdb->update($options_table, array('option_value' => $total_time), array('option_name' => 'empty_start_time')); $sql_count++;
			}
			$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'empty_page_title_sip' ) );
			$sql_count++;

			$end = round( microtime( true ), 5 );
			wpscx_print_debug( 'Empty Page Title', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
	}

	function check_post_title_empty( $rng_seed = 0, $is_running = false ) {
		$start = round( microtime( true ), 5 );
		ini_set( 'memory_limit', '512M' );
		global $wpdb;
		global $wpsc_settings;
		global $wpscx_base_page_max;
		if ( sizeof( (array) $wpsc_settings ) < 1 ) {
			wpscx_set_global_vars();
		}
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name    = $wpdb->prefix . 'spellcheck_empty';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		set_time_limit( 6000 );
		$error_count = 0;
		$total_count = 0;
		$sql_count   = 0;
		$error_list  = array();

		if ( ! $is_running && ! empty( $is_running ) ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_scan_in_progress' ) );
			$sql_count++;
			$start_time = time();
		}

		$ignore_pages = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );
		$sql_count++;
                $ignore_list = $wpdb->get_results("SELECT page_name, page_type, page_id FROM $table_name WHERE ignore_word=1");

		$post_types     = get_post_types();
		$post_type_list = array();
		foreach ( $post_types as $type ) {
			if ( 'revision' !== $type && 'page' !== $type && 'nav_menu_item' !== $type && 'optionsframework' !== $type && 'slider' !== $type && 'attachment' !== $type && 'oembed_cache' !== $type ) {
				array_push( $post_type_list, $type );
			}
		}

		wpscx_set_global_vars();

		if ( 'true' === $wpsc_settings[137]->option_value ) {
			$post_status = array( 'publish', 'draft' ); } else {
			$post_status = array( 'publish' ); }

			$posts_list = SplFixedArray::fromArray(
				get_posts(
					array(
						'posts_per_page' => $wpscx_base_page_max,
						'post_type'      => $post_type_list,
						'post_status'    => $post_status,
					)
				)
			);
		$sql_count++;

		for ( $x = 0;$x < $posts_list->getSize();$x++ ) {
			$total_count++;
			$word_list = $posts_list[ $x ]->post_title;
                        
                        $ignore_field = false;
                        foreach($ignore_list as $row) {
                            if ($row->page_type == 'Post Title' && $row->page_id == $posts_list[ $x ]->ID) $ignore_field = true;
                        }
                        if ($ignore_field) continue;
                        
                        if ( wpscx_check_empty( $word_list, false ) ) {
				$error_count++;

				array_push(
					$error_list,
					array(
						'word'      => WPSCX_EMPTY,
						'page_name' => 'Empty Post Title',
						'page_type' => 'Post Title',
						'page_id'   => $posts_list[ $x ]->ID,
					)
				);
			}
		}

		wpscx_sql_insert( $error_list, WPSCX_EMPTY, $table_name );
		$wpdb->update( $options_table, array( 'option_value' => $total_count ), array( 'option_name' => 'empty_post_count' ) );
		$sql_count++;

		$counter = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name ='empty_checked';" );
		$sql_count++;
		$total_count = $total_count + intval( $counter[0]->option_value );
		$wpdb->update( $options_table, array( 'option_value' => $total_count ), array( 'option_name' => 'empty_checked' ) );
		$sql_count++;
		if ( ! $is_running && ! empty( $is_running ) ) {
			$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'empty_scan_in_progress' ) );
			$sql_count++;
			$end_time   = time();
			$total_time = wpscx_time_elapsed( $end_time - $start_time );
			//$wpdb->update($options_table, array('option_value' => $total_time), array('option_name' => 'empty_start_time'));  $sql_count++;
		}
		$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'empty_post_title_sip' ) );
		$sql_count++;

		$end = round( microtime( true ), 5 );
		wpscx_print_debug( 'Empty Post Title', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
	}

	function check_author_empty_run( $is_running = false, $log_debug = true ) {
		$start = round( microtime( true ), 5 );
		global $wpdb;
		global $wpsc_settings;
		$table_name     = $wpdb->prefix . 'spellcheck_empty';
		$options_table  = $wpdb->prefix . 'spellcheck_options';
		$ignore_table   = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table     = $wpdb->prefix . 'spellcheck_dictionary';
		$post_table     = $wpdb->prefix . 'posts';
		$user_table     = $wpdb->prefix . 'usermeta';
		$username_table = $wpdb->prefix . 'users';

		set_time_limit( 600 );
		$error_count = 0;
		$total_count = 0;
		$sql_count   = 0;
		$error_list  = array();

		$posts_list = SplFixedArray::fromArray( $wpdb->get_results( "SELECT a.meta_key, a.meta_value, b.user_login, b.post_author FROM $user_table a LEFT JOIN (SELECT a.post_author, b.user_login FROM $post_table a, $username_table b WHERE a.post_author = b.ID GROUP BY post_author) AS b ON b.post_author = a.user_id WHERE (a.meta_key = 'first_name' OR a.meta_key = 'last_name' OR a.meta_key = 'description' OR a.meta_key = 'twitter' OR a.meta_key = 'facebook');" ) );
		$sql_count++;
                $ignore_list = $wpdb->get_results("SELECT page_name, page_type FROM $table_name WHERE ignore_word=1");

		for ( $x = 0; $x < $posts_list->getSize(); $x++ ) {
			$total_count++;

			if ( '' !== $posts_list[ $x ]->user_login && null !== $posts_list[ $x ]->user_login ) {
                                if ( wpscx_check_empty( $posts_list[ $x ]->meta_value, false ) ) {
					$error_count++;
					if ( 'first_name' === $posts_list[ $x ]->meta_key ) {
						$post_type = 'First Name';
					} elseif ( 'last_name' === $posts_list[ $x ]->meta_key ) {
						$post_type = 'Last Name';
					} elseif ( 'description' === $posts_list[ $x ]->meta_key ) {
						$post_type = 'Biography';
					} else {
						$post_type = $posts_list[ $x ]->meta_key; }

                                        $ignore_field = false;
                                        foreach($ignore_list as $row) {
                                            if ($row->page_type == 'Author ' . $post_type && $row->page_name == $posts_list[ $x ]->user_login) $ignore_field = true;
                                        }
                                        if ($ignore_field) continue;
					array_push(
						$error_list,
						array(
							'word'      => WPSCX_EMPTY,
							'page_name' => $posts_list[ $x ]->user_login,
							'page_type' => 'Author ' . $post_type,
							'page_id'   => $posts_list[ $x ]->post_author,
						)
					);
				}
			}
		}

		wpscx_sql_insert( $error_list, WPSCX_EMPTY, $table_name );

		$counter = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name ='empty_checked';" );
		$sql_count++;
		$total_count = $total_count + intval( $counter[0]->option_value );
		$wpdb->update( $options_table, array( 'option_value' => $total_count ), array( 'option_name' => 'empty_checked' ) );
		$sql_count++;

		$end = round( microtime( true ), 5 );
		wpscx_print_debug( 'Empty Author', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
	}

	function check_author_empty( $rng_seed = 0, $is_running = false ) {
		if ( ! $is_running ) {
			sleep( 1 );
		}
		global $wpdb;
		global $wpsc_settings;
		global $wpscx_ent_included;
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$start_time    = time();

		$this->check_author_empty_run( true );
		if ( $wpscx_ent_included ) {
						$scanner = new Wpscx_Seo_Scanner_pro;
			$scanner->check_author_seotitle_empty_ent( true );
			$scanner->check_author_seodesc_empty_ent( true );
		}

			$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'empty_author_sip' ) );
		if ( ! $is_running && ! empty( $is_running ) ) {
					$end_time   = time();
					$total_time = wpscx_time_elapsed( $end_time - $start_time + 6 );
					//$wpdb->update($options_table, array('option_value' => $total_time), array('option_name' => 'empty_start_time'));
		}
	}

	function check_empty_wpsc() {
		global $wpdb;
		global $wpscx_ent_included;
		$table_name    = $wpdb->prefix . 'spellcheck_empty';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		set_time_limit( 600 ); //$ Set PHP timeout limit
		$pro_errors = 0;
		$last_count = 0;

		$pro_errors += $this->check_menus_empty_free( true );
		$last_count  = $pro_errors;

		$pro_errors += $this->check_yoast_page_empty_free( true );
		$last_count  = $pro_errors;

		$pro_errors += $this->check_seo_titles_page_empty_free( true );
		$last_count  = $pro_errors;

		$pro_errors += $this->check_yoast_post_empty_free( true );
		$last_count  = $pro_errors;

		$pro_errors += $this->check_seo_titles_post_empty_free( true );
		$last_count  = $pro_errors;

		$pro_errors += $this->check_yoast_media_empty_free( true );
		$last_count  = $pro_errors;

		$pro_errors += $this->check_seo_titles_media_empty_free( true );
		$last_count  = $pro_errors;

		$pro_errors += $this->check_media_descriptions_empty_free( true );
		$last_count  = $pro_errors;

		$pro_errors += $this->check_media_captions_empty_free( true );
		$last_count  = $pro_errors;

		$pro_errors += $this->check_media_alt_empty_free( true );
		$last_count  = $pro_errors;

		$pro_errors += $this->check_woocommerce_name_empty_free( true );
		$last_count  = $pro_errors;

		$pro_errors += $this->check_woocommerce_excerpt_empty_free( true );
		$last_count  = $pro_errors;

		$pro_errors += $this->check_wpecommerce_name_empty_free( true );
		$last_count  = $pro_errors;

		$pro_errors += $this->check_wpecommerce_excerpt_empty_free( true );
		$last_count  = $pro_errors;

		$pro_errors += $this->check_post_tag_descriptions_empty_free( true );
		$last_count  = $pro_errors;

		$pro_errors += $this->check_post_categories_description_empty_free( true );
		$last_count  = $pro_errors;

		$pro_errors += $this->check_author_seotitle_empty_free( true );
		$last_count  = $pro_errors;

		$pro_errors += $this->check_author_seodesc_empty_free( true );
		$last_count  = $pro_errors;

		$wpdb->update( $options_table, array( 'option_value' => $pro_errors ), array( 'option_name' => 'pro_empty_count' ) );
	}

	function check_menus_empty_free( $rng_seed = 0, $is_running = false, $log_debug = true ) {
		$start = round( microtime( true ), 5 );
		global $wpdb;
		global $wpsc_settings;
		$table_name    = $wpdb->prefix . 'posts';
		$empty_table   = $wpdb->prefix . 'spellcheck_empty';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$sql_count     = 1;

		set_time_limit( 6000 );
		$error_count = 0;
		$total_count = 0;
		$error_list  = array();

		if ( ! $is_running && ! empty( $is_running ) ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_scan_in_progress' ) );
			$start_time = time();
		}

		$menus = $wpdb->get_results( 'SELECT ID, post_title FROM ' . $table_name . ' WHERE post_type ="nav_menu_item" LIMIT 10000;' );

		foreach ( $menus as $menu ) {
			$total_count++;
			$word_list = $menu->post_title;
			if ( wpscx_check_empty( $word_list, false )  ) {
				$error_count++;
				array_push(
					$error_list,
					array(
						'word'      => WPSCX_EMPTY,
						'page_name' => $menu->post_title,
						'page_type' => 'Menu Item',
						'page_id'   => $menu->ID,
					)
				);
			}
		}

		$end = round( microtime( true ), 5 );
		if ( $log_debug ) {
			wpscx_print_debug( 'Empty Menu', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
		}

		return sizeof( (array) $error_list );
	}

	function check_post_tag_descriptions_empty_free( $rng_seed = 0, $is_running = false, $log_debug = true ) {
		$start = round( microtime( true ), 5 );

		global $wpdb;
		global $wpsc_settings;
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name    = $wpdb->prefix . 'spellcheck_empty';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$sql_count     = 1;

		set_time_limit( 6000 );
		$error_count = 0;
		$total_count = 0;
		$error_list  = array();

		if ( ! $is_running && ! empty( $is_running ) ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_scan_in_progress' ) );
			$start_time = time();
		}

		$tags_list = get_tags();

		foreach ( $tags_list as $tag ) {
			$total_count++;
			$word = $tag->description;
                        if ( wpscx_check_empty( $word, false ) ) {
				$error_count++;
				array_push(
					$error_list,
					array(
						'word'      => WPSCX_EMPTY,
						'page_name' => $tag->name,
						'page_type' => 'Tag Description',
						'page_id'   => $tag->term_id,
					)
				);
			}
		}

		$end = round( microtime( true ), 5 );
		if ( $log_debug ) {
			wpscx_print_debug( 'Empty Tag Desc', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
		}

		return sizeof( (array) $error_list );
	}

	function check_post_categories_description_empty_free( $rng_seed = 0, $is_running = false, $log_debug = true ) {
		$start       = round( microtime( true ), 5 );
		$timer_start = round( microtime( true ), 5 );
		//
		global $wpdb;
		global $wpsc_settings;
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name    = $wpdb->prefix . 'spellcheck_empty';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$sql_count     = 0;
		set_time_limit( 6000 );
		$error_count = 0;
		$total_count = 0;
		$error_list  = array();

		$cats_list = get_categories();
		$sql_count++;

		foreach ( $cats_list as $cat ) {
			$words = $cat->description;
			if ( wpscx_check_empty( $words, false ) ) {
				$error_count++;
				array_push(
					$error_list,
					array(
						'word'      => WPSCX_EMPTY,
						'page_name' => $cat->name,
						'page_type' => 'Category Description',
						'page_id'   => $cat->term_id,
					)
				);
			}
		}
		$end = round( microtime( true ), 5 );
		if ( $log_debug ) {
			wpscx_print_debug( 'Empty Category Desc ', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
		}

		return sizeof( (array) $error_list );
	}

	function check_media_descriptions_empty_free( $rng_seed = 0, $is_running = false, $log_debug = true ) {
		$start = round( microtime( true ), 5 );

		global $wpdb;
		global $wpsc_settings;
		$table_name    = $wpdb->prefix . 'spellcheck_empty';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$sql_count     = 1;
		set_time_limit( 6000 );
		$error_count = 0;
		$total_count = 0;
		$error_list  = array();

		$posts_list = get_posts(
			array(
				'posts_per_page' => 10000,
				'post_type'      => 'attachment',
			)
		);

		foreach ( $posts_list as $post ) {
			$total_count++;
			$words_list = $post->post_content;
			if ( wpscx_check_empty( $words_list, false ) ) {
				$error_count++;
				array_push(
					$error_list,
					array(
						'word'      => WPSCX_EMPTY,
						'page_name' => $post->post_title,
						'page_type' => 'Media Description',
						'page_id'   => $post->ID,
					)
				);
			}
		}

		$end = round( microtime( true ), 5 );
		if ( $log_debug ) {
			wpscx_print_debug( 'Empty Media Desc', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
		}

		return sizeof( (array) $error_list );
	}

	function check_media_captions_empty_free( $rng_seed = 0, $is_running = false, $log_debug = true ) {
		$start = round( microtime( true ), 5 );

		global $wpdb;
		global $wpsc_settings;
		$table_name    = $wpdb->prefix . 'spellcheck_empty';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$sql_count     = 1;

		set_time_limit( 6000 );
		$error_count = 0;
		$total_count = 0;
		$error_list  = array();

		$posts_list = get_posts(
			array(
				'posts_per_page' => 10000,
				'post_type'      => 'attachment',
			)
		);

		foreach ( $posts_list as $post ) {
			$total_count++;
			$words_list = $post->post_excerpt;
			if ( wpscx_check_empty( $words_list, false ) ) {
				$error_count++;
				array_push(
					$error_list,
					array(
						'word'      => WPSCX_EMPTY,
						'page_name' => $post->post_title,
						'page_type' => 'Media Caption',
						'page_id'   => $post->ID,
					)
				);
			}
		}

		$end = round( microtime( true ), 5 );
		if ( $log_debug ) {
			wpscx_print_debug( 'Empty Media Caption ', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
		}

		return sizeof( (array) $error_list );
	}

	function check_media_alt_empty_free( $rng_seed = 0, $is_running = false, $log_debug = true ) {
		$start = round( microtime( true ), 5 );

		global $wpdb;
		global $wpsc_settings;
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name    = $wpdb->prefix . 'spellcheck_empty';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$sql_count     = 1;

		set_time_limit( 6000 );
		$error_count = 0;
		$total_count = 0;
		$error_list  = array();

		$posts_list = get_posts(
			array(
				'posts_per_page' => 10000,
				'post_type'      => 'attachment',
			)
		);

		foreach ( $posts_list as $post ) {
			$total_count++;
			$word_list = get_post_meta( $post->ID, '_wp_attachment_image_alt', true );
                        
			if ( wpscx_check_empty( $word_list, false ) ) {
				$error_count++;
				array_push(
					$error_list,
					array(
						'word'      => WPSCX_EMPTY,
						'page_name' => $post->post_title,
						'page_type' => 'Media Alternate Text',
						'page_id'   => $post->ID,
					)
				);
			}
		}

		$end = round( microtime( true ), 5 );
		if ( $log_debug ) {
			wpscx_print_debug( 'Empty Media Alt ', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
		}

		return sizeof( (array) $error_list );
	}

	function check_woocommerce_name_empty_free( $rng_seed = 0, $is_running = false, $log_debug = true ) {
		$start = round( microtime( true ), 5 );

		global $wpdb;
		global $wpsc_settings;
		$table_name    = $wpdb->prefix . 'spellcheck_empty';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$sql_count     = 1;

		set_time_limit( 6000 );
		$error_count = 0;
		$total_count = 0;
		$error_list  = array();

		$ignore_posts = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );

		$posts_list = get_posts(
			array(
				'posts_per_page' => 10000,
				'post_type'      => 'product',
				'post_status'    => array(
					'publish',
					'draft',
				),
			)
		);

		foreach ( $posts_list as $post ) {
			$total_count++;
			$ignore_flag = 'false';
			foreach ( $ignore_posts as $ignore_check ) {
				if ( strtoupper( trim( $post->post_title ) ) === strtoupper( trim( $ignore_check->keyword ) ) ) {
					$ignore_flag = 'true';
				}
			}
			if ( 'true' === $ignore_flag ) {
				continue; }
			$words_list = $post->post_title;
                        if ( wpscx_check_empty( $words_list, false ) ) {
				$error_count++;
				array_push(
					$error_list,
					array(
						'word'      => WPSCX_EMPTY,
						'page_name' => $post->post_title,
						'page_type' => 'WooCommerce Product Name',
						'page_id'   => $post->ID,
					)
				);
			}
		}

		$end = round( microtime( true ), 5 );
		if ( $log_debug ) {
			wpscx_print_debug( 'Empty WooCommerce Title ', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
		}

		return sizeof( (array) $error_list );
	}

	function check_woocommerce_excerpt_empty_free( $rng_seed = 0, $is_running = false, $log_debug = true ) {
		$start = round( microtime( true ), 5 );
		global $wpdb;
		global $wpsc_settings;
		$table_name    = $wpdb->prefix . 'spellcheck_empty';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$sql_count     = 1;

		set_time_limit( 6000 );
		$error_count = 0;
		$total_count = 0;
		$error_list  = array();

		$ignore_posts = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );

		$posts_list = get_posts(
			array(
				'posts_per_page' => 10000,
				'post_type'      => 'product',
				'post_status'    => array(
					'publish',
					'draft',
				),
			)
		);

		foreach ( $posts_list as $post ) {
			$total_count++;
			$ignore_flag = 'false';
			foreach ( $ignore_posts as $ignore_check ) {
				if ( strtoupper( trim( $post->post_title ) ) === strtoupper( trim( $ignore_check->keyword ) ) ) {
					$ignore_flag = 'true';
				}
			}
			if ( 'true' === $ignore_flag ) {
				continue; }
			$words_list = $post->post_excerpt;
			if ( wpscx_check_empty( $words_list, false ) ) {
				$error_count++;
				array_push(
					$error_list,
					array(
						'word'      => WPSCX_EMPTY,
						'page_name' => $post->post_title,
						'page_type' => 'WooCommerce Product Excerpt',
						'page_id'   => $post->ID,
					)
				);
			}
		}

		$end = round( microtime( true ), 5 );
		if ( $log_debug ) {
			wpscx_print_debug( 'Empty WooCommerce Excerpt', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
		}

		return sizeof( (array) $error_list );
	}

	function check_wpecommerce_name_empty_free( $rng_seed = 0, $is_running = false, $log_debug = true ) {
		$start = round( microtime( true ), 5 );
		global $wpdb;
		global $wpsc_settings;
		$table_name    = $wpdb->prefix . 'spellcheck_empty';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$sql_count     = 1;

		set_time_limit( 6000 );
		$error_count = 0;
		$total_count = 0;
		$error_list  = array();

		$ignore_posts = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );
		$posts_list   = get_posts(
			array(
				'posts_per_page' => 10000,
				'post_type'      => 'wpsc-product',
				'post_status'    => array(
					'publish',
					'draft',
				),
			)
		);

		foreach ( $posts_list as $post ) {
			$total_count++;
			$ignore_flag = 'false';
			foreach ( $ignore_posts as $ignore_check ) {
				if ( strtoupper( trim( $post->post_title ) ) === strtoupper( trim( $ignore_check->keyword ) ) ) {
					$ignore_flag = 'true';
				}
			}
			if ( 'true' === $ignore_flag ) {
				continue; }
			$words_list = $post->post_title;
			if ( wpscx_check_empty( $words_list, false ) ) {
				$error_count++;
				array_push(
					$error_list,
					array(
						'word'      => WPSCX_EMPTY,
						'page_name' => $post->post_title,
						'page_type' => 'WP eCommerce Product Name',
						'page_id'   => $post->ID,
					)
				);
			}
		}

		$end = round( microtime( true ), 5 );
		if ( $log_debug ) {
			wpscx_print_debug( 'Empty WPeCommerce Title ', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
		}

		return sizeof( (array) $error_list );
	}

	function check_wpecommerce_excerpt_empty_free( $rng_seed = 0, $is_running = false, $log_debug = true ) {
		$start = round( microtime( true ), 5 );
		global $wpdb;
		global $wpsc_settings;
		$table_name    = $wpdb->prefix . 'spellcheck_empty';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$sql_count     = 1;

		set_time_limit( 6000 );
		$error_count = 0;
		$total_count = 0;
		$error_list  = array();

		$ignore_posts = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );

		$posts_list = get_posts(
			array(
				'posts_per_page' => 10000,
				'post_type'      => 'wpsc-product',
				'post_status'    => array(
					'publish',
					'draft',
				),
			)
		);

		foreach ( $posts_list as $post ) {
			$total_count++;
			$ignore_flag = 'false';
			foreach ( $ignore_posts as $ignore_check ) {
				if ( strtoupper( trim( $post->post_title ) ) === strtoupper( trim( $ignore_check->keyword ) ) ) {
					$ignore_flag = 'true';
				}
			}
			if ( 'true' === $ignore_flag ) {
				continue; }
			$words_list = $post->post_excerpt;
			if ( wpscx_check_empty( $words_list, false ) ) {
				$error_count++;
				array_push(
					$error_list,
					array(
						'word'      => WPSCX_EMPTY,
						'page_name' => $post->post_title,
						'page_type' => 'WP eCommerce Product Excerpt',
						'page_id'   => $post->ID,
					)
				);
			}
		}

		$end = round( microtime( true ), 5 );
		if ( $log_debug ) {
			wpscx_print_debug( 'Empty WPeCommerce Excerpt', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
		}

		return sizeof( (array) $error_list );
	}

	function check_author_seotitle_empty_free( $rng_seed = 0, $is_running = false, $log_debug = true ) {
		$start = round( microtime( true ), 5 );
		global $wpdb;
		global $wpsc_settings;
		$table_name     = $wpdb->prefix . 'spellcheck_empty';
		$options_table  = $wpdb->prefix . 'spellcheck_options';
		$ignore_table   = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table     = $wpdb->prefix . 'spellcheck_dictionary';
		$post_table     = $wpdb->prefix . 'posts';
		$user_table     = $wpdb->prefix . 'usermeta';
		$username_table = $wpdb->prefix . 'users';
		set_time_limit( 600 );
		$error_count = 0;
                $sql_count = 0;
		$total_count = 0;
		$error_list  = array();

		$ignore_posts = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );

		$posts_list = $wpdb->get_results( "SELECT * FROM $post_table GROUP By post_author LIMIT 10000" );

		foreach ( $posts_list as $post ) {
			$total_count++;
			$author      = $wpdb->get_results( "SELECT * FROM $user_table WHERE meta_key='wpseo_title' AND user_id='$post->post_author'" );
			$author_name = $wpdb->get_results( "SELECT * FROM $username_table WHERE id='$post->post_author'" );

			if ( ! is_object( $author_name ) || ! is_object( $author ) ) {
				continue;
			}
			$words_list = $author[0]->meta_value;
                        if ( wpscx_check_empty( $words_list, false ) && '' !== $author_name->user_login ) {
				$error_count++;
				array_push(
					$error_list,
					array(
						'word'      => WPSCX_EMPTY,
						'page_name' => $author_name->user_login,
						'page_type' => 'Author SEO Title',
						'page_id'   => $post->post_author,
					)
				);
			}
		}

		$end = round( microtime( true ), 5 );
		if ( $log_debug ) {
			wpscx_print_debug( 'Empty Author SEO Title', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
		}

		return sizeof( (array) $error_list );
	}

	function check_author_seodesc_empty_free( $rng_seed = 0, $is_running = false, $log_debug = true ) {
		$start = round( microtime( true ), 5 );
		global $wpdb;
		global $wpsc_settings;
		$table_name     = $wpdb->prefix . 'spellcheck_empty';
		$options_table  = $wpdb->prefix . 'spellcheck_options';
		$ignore_table   = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table     = $wpdb->prefix . 'spellcheck_dictionary';
		$post_table     = $wpdb->prefix . 'posts';
		$user_table     = $wpdb->prefix . 'usermeta';
		$username_table = $wpdb->prefix . 'users';
		set_time_limit( 600 );
		$total_count = 0;
                $sql_count = 0;
		$error_count = 0;
		$error_list  = array();

		$ignore_posts = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";' );

		$posts_list = $wpdb->get_results( "SELECT * FROM $post_table GROUP By post_author LIMIT 10000" );

		foreach ( $posts_list as $post ) {
			$total_count++;
			$author      = $wpdb->get_results( "SELECT * FROM $user_table WHERE meta_key='wpseo_metadesc' AND user_id='$post->post_author'" );
			$author_name = $wpdb->get_results( "SELECT * FROM $username_table WHERE id='$post->post_author'" );

			if ( ! is_object( $author_name ) || ! is_object( $author ) ) {
				continue;
			}
			$words_list = $author[0]->meta_value;
			if ( wpscx_check_empty( $words_list, false ) && '' !== $author_name->user_login ) {
				array_push(
					$error_list,
					array(
						'word'      => WPSCX_EMPTY,
						'page_name' => $author_name->user_login,
						'page_type' => 'Author SEO Description',
						'page_id'   => $post->post_author,
					)
				);
			}
		}

		$end = round( microtime( true ), 5 );
		if ( $log_debug ) {
			wpscx_print_debug( 'Empty Author SEO Desc', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
		}

		return sizeof( (array) $error_list );
	}

	function check_yoast_page_empty_free( $rng_seed = 0, $is_running = false, $log_debug = true ) {
		$start = round( microtime( true ), 5 );
		ini_set( 'memory_limit', '512M' );

		global $wpdb;
		global $wpsc_settings;
		if ( sizeof( (array) $wpsc_settings ) < 1 ) {
			wpscx_set_global_vars();
		}
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name    = $wpdb->prefix . 'postmeta';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$error_count   = 0;
		$total_count   = 0;
		$sql_count     = 0;
		$error_list    = array();
		$haystack      = array();
		set_time_limit( 6000 );

		$words_table = $wpdb->prefix . 'spellcheck_empty';
		$posts_table = $wpdb->prefix . 'posts';

		$seo_check = $wpdb->get_results( 'SELECT post_id, meta_value, meta_key FROM ' . $table_name . ' WHERE (meta_key="_yoast_wpseo_metadesc" OR meta_key="_aioseop_description" OR meta_key="_su_description" OR meta_key="rank_math_description") GROUP BY post_id' );
		$sql_count++;

		foreach ( $seo_check as $value ) {
				$haystack[ $value->post_id ] = 'true';
		}

		if ( 'true' === $wpsc_settings[136]->option_value ) {
			$post_status = " AND (post_status='publish' OR post_status='draft')"; } else {
			$post_status = " AND post_status='publish'"; }

			$page_list = SplFixedArray::fromArray( $wpdb->get_results( "SELECT post_content, post_title, post_name, ID FROM $posts_table WHERE post_type='page'$post_status" ) );

			for ( $x = 0; $x < $page_list->getSize(); $x++ ) {
                                if ( !isset($haystack[ $page_list[ $x ]->ID ]) ) {
					array_push(
						$error_list,
						array(
							'word'      => WPSCX_EMPTY,
							'page_name' => $page_list[ $x ]->post_title,
							'page_type' => 'SEO Page Description',
							'page_id'   => $page_list[ $x ]->ID,
						)
					);
				}
			}

			$end = round( microtime( true ), 5 );
			if ( $log_debug ) {
				wpscx_print_debug( 'Empty Page SEO Desc ', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
			}

			return sizeof( (array) $error_list );
	}

	function check_seo_titles_page_empty_free( $rng_seed = 0, $is_running = false, $log_debug = true ) {
		$start = round( microtime( true ), 5 );
		ini_set( 'memory_limit', '512M' );

		global $wpdb;
		global $wpsc_settings;
		if ( sizeof( (array) $wpsc_settings ) < 1 ) {
			wpscx_set_global_vars();
		}
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name    = $wpdb->prefix . 'postmeta';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$error_count   = 0;
		$total_count   = 0;
		$sql_count     = 0;
		$error_list    = array();
		$haystack      = array();
		set_time_limit( 6000 );

		$words_table = $wpdb->prefix . 'spellcheck_empty';
		$posts_table = $wpdb->prefix . 'posts';

		$seo_check = $wpdb->get_results( 'SELECT post_id, meta_value, meta_key FROM ' . $table_name . ' WHERE (meta_key="_yoast_wpseo_title" OR meta_key="_aioseop_title" OR meta_key="_su_title" OR meta_key="rank_math_title") GROUP BY post_id' );
		$sql_count++;

		foreach ( $seo_check as $value ) {
				$haystack[ $value->post_id ] = 'true';
		}

		if ( 'true' === $wpsc_settings[136]->option_value ) {
			$post_status = " AND (post_status='publish' OR post_status='draft')"; } else {
			$post_status = " AND post_status='publish'"; }

			$page_list = SplFixedArray::fromArray( $wpdb->get_results( "SELECT post_content, post_title, post_name, ID FROM $posts_table WHERE post_type='page'$post_status" ) );

			for ( $x = 0; $x < $page_list->getSize(); $x++ ) {
				if ( !isset($haystack[ $page_list[ $x ]->ID ]) ) {
					array_push(
						$error_list,
						array(
							'word'      => WPSCX_EMPTY,
							'page_name' => $page_list[ $x ]->post_title,
							'page_type' => 'SEO Page Title',
							'page_id'   => $page_list[ $x ]->ID,
						)
					);
				}
			}

			$end = round( microtime( true ), 5 );
			if ( $log_debug ) {
				wpscx_print_debug( 'Empty Page SEO Title', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
			}

			return sizeof( (array) $error_list );
	}

	function check_yoast_post_empty_free( $rng_seed = 0, $is_running = false, $log_debug = true ) {
		$start = round( microtime( true ), 5 );
		ini_set( 'memory_limit', '512M' );

		global $wpdb;
		global $wpsc_settings;
		if ( sizeof( (array) $wpsc_settings ) < 1 ) {
			wpscx_set_global_vars();
		}
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name    = $wpdb->prefix . 'postmeta';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$error_count   = 0;
		$total_count   = 0;
		$sql_count     = 0;
		$error_list    = array();
		$haystack      = array();
		set_time_limit( 6000 );

		$words_table = $wpdb->prefix . 'spellcheck_empty';
		$posts_table = $wpdb->prefix . 'posts';

		$seo_check = $wpdb->get_results( 'SELECT post_id, meta_value, meta_key FROM ' . $table_name . ' WHERE (meta_key="_yoast_wpseo_metadesc" OR meta_key="_aioseop_description" OR meta_key="_su_description" OR meta_key="rank_math_dscription") GROUP BY post_id' );
		$sql_count++;

		foreach ( $seo_check as $value ) {
				$haystack[ $value->post_id ] = 'true';
		}

		if ( 'true' === $wpsc_settings[136]->option_value ) {
			$post_status = " AND (post_status='publish' OR post_status='draft')"; } else {
			$post_status = " AND post_status='publish'"; }

			$page_list = SplFixedArray::fromArray( $wpdb->get_results( "SELECT post_content, post_title, post_name, ID FROM $posts_table WHERE post_type='post'$post_status" ) );

			for ( $x = 0; $x < $page_list->getSize(); $x++ ) {
				if ( !isset($haystack[ $page_list[ $x ]->ID ]) ) {
					array_push(
						$error_list,
						array(
							'word'      => WPSCX_EMPTY,
							'page_name' => $page_list[ $x ]->post_title,
							'page_type' => 'SEO Post Description',
							'page_id'   => $page_list[ $x ]->ID,
						)
					);
				}
			}

			$end = round( microtime( true ), 5 );
			if ( $log_debug ) {
				wpscx_print_debug( 'Empty Post SEO Desc ', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
			}

			return sizeof( (array) $error_list );
	}

	function check_seo_titles_post_empty_free( $rng_seed = 0, $is_running = false, $log_debug = true ) {
		$start = round( microtime( true ), 5 );
		ini_set( 'memory_limit', '512M' );

		global $wpdb;
		global $wpsc_settings;
		if ( sizeof( (array) $wpsc_settings ) < 1 ) {
			wpscx_set_global_vars();
		}
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name    = $wpdb->prefix . 'postmeta';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$error_count   = 0;
		$total_count   = 0;
		$sql_count     = 0;
		$error_list    = array();
		$haystack      = array();
		set_time_limit( 6000 );

		$words_table = $wpdb->prefix . 'spellcheck_empty';
		$posts_table = $wpdb->prefix . 'posts';

		$seo_check = $wpdb->get_results( 'SELECT post_id, meta_value, meta_key FROM ' . $table_name . ' WHERE (meta_key="_yoast_wpseo_title" OR meta_key="_aioseop_title" OR meta_key="_su_title" OR meta_key="rank_math_title") GROUP BY post_id' );
		$sql_count++;

		foreach ( $seo_check as $value ) {
				$haystack[ $value->post_id ] = 'true';
		}

		if ( 'true' === $wpsc_settings[136]->option_value ) {
			$post_status = " AND (post_status='publish' OR post_status='draft')"; } else {
			$post_status = " AND post_status='publish'"; }

			$page_list = SplFixedArray::fromArray( $wpdb->get_results( "SELECT post_content, post_title, post_name, ID FROM $posts_table WHERE post_type='post'$post_status" ) );

			for ( $x = 0; $x < $page_list->getSize(); $x++ ) {
				if ( !isset($haystack[ $page_list[ $x ]->ID ]) ) {
					array_push(
						$error_list,
						array(
							'word'      => WPSCX_EMPTY,
							'page_name' => $page_list[ $x ]->post_title,
							'page_type' => 'SEO Post Title',
							'page_id'   => $page_list[ $x ]->ID,
						)
					);
				}
			}

			$end = round( microtime( true ), 5 );
			if ( $log_debug ) {
				wpscx_print_debug( 'Empty Post SEO Title', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
			}

			return sizeof( (array) $error_list );
	}

	function check_yoast_media_empty_free( $rng_seed = 0, $is_running = false, $log_debug = true ) {
		$start = round( microtime( true ), 5 );
		ini_set( 'memory_limit', '512M' );

		global $wpdb;
		global $wpsc_settings;
		if ( sizeof( (array) $wpsc_settings ) < 1 ) {
			wpscx_set_global_vars();
		}
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name    = $wpdb->prefix . 'postmeta';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$error_count   = 0;
		$total_count   = 0;
		$sql_count     = 0;
		$error_list    = array();
		$haystack      = array();
		set_time_limit( 6000 );

		$words_table = $wpdb->prefix . 'spellcheck_empty';
		$posts_table = $wpdb->prefix . 'posts';

		$seo_check = $wpdb->get_results( 'SELECT post_id, meta_value, meta_key FROM ' . $table_name . ' WHERE (meta_key="_yoast_wpseo_metadesc" OR meta_key="_aioseop_description" OR meta_key="_su_description" OR meta_key="rank_math_description") GROUP BY post_id' );
		$sql_count++;

		foreach ( $seo_check as $value ) {
				$haystack[ $value->post_id ] = 'true';
		}

		$page_list = SplFixedArray::fromArray( $wpdb->get_results( "SELECT post_content, post_title, post_name, ID FROM $posts_table WHERE post_type='attachment'" ) );

		for ( $x = 0; $x < $page_list->getSize(); $x++ ) {
			if ( !isset($haystack[ $page_list[ $x ]->ID ]) ) {
				array_push(
					$error_list,
					array(
						'word'      => WPSCX_EMPTY,
						'page_name' => $page_list[ $x ]->post_title,
						'page_type' => 'SEO Media Description',
						'page_id'   => $page_list[ $x ]->ID,
					)
				);
			}
		}

		$end = round( microtime( true ), 5 );
		if ( $log_debug ) {
			wpscx_print_debug( 'Empty Media SEO Desc', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
		}

		return sizeof( (array) $error_list );
	}

	function check_seo_titles_media_empty_free( $rng_seed = 0, $is_running = false, $log_debug = true ) {
		$start = round( microtime( true ), 5 );
		ini_set( 'memory_limit', '512M' );

		global $wpdb;
		global $wpsc_settings;
		if ( sizeof( (array) $wpsc_settings ) < 1 ) {
			wpscx_set_global_vars();
		}
		$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name    = $wpdb->prefix . 'postmeta';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
		$error_count   = 0;
		$total_count   = 0;
		$sql_count     = 0;
		$error_list    = array();
		$haystack      = array();
		set_time_limit( 6000 );

		$words_table = $wpdb->prefix . 'spellcheck_empty';
		$posts_table = $wpdb->prefix . 'posts';

		$seo_check = $wpdb->get_results( 'SELECT post_id, meta_value, meta_key FROM ' . $table_name . ' WHERE (meta_key="_yoast_wpseo_title" OR meta_key="_aioseop_title" OR meta_key="_su_title" OR meta_key="rank_math_title") GROUP BY post_id' );
		$sql_count++;

		foreach ( $seo_check as $value ) {
			if ( '' !== $value->meta_value ) {
				$haystack[ $value->post_id ] = 'true';
			}
		}

		$page_list = SplFixedArray::fromArray( $wpdb->get_results( "SELECT post_content, post_title, post_name, ID FROM $posts_table WHERE post_type='attachment'" ) );

		for ( $x = 0; $x < $page_list->getSize(); $x++ ) {
			if ( isset($haystack[ $page_list[ $x ]->ID ]) && wpscx_check_empty( $haystack[ $page_list[ $x ]->ID ], true ) ) {
				array_push(
					$error_list,
					array(
						'word'      => WPSCX_EMPTY,
						'page_name' => $page_list[ $x ]->post_title,
						'page_type' => 'SEO Media Description',
						'page_id'   => $page_list[ $x ]->ID,
					)
				);
			}
		}

		$end = round( microtime( true ), 5 );
		if ( $log_debug ) {
			wpscx_print_debug( 'Empty Media SEO Title', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), sizeof( (array) $error_list ) );
		}

		return sizeof( (array) $error_list );
	}
}
