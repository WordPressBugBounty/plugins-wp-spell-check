<?php
    const   WPSCX_SITE_STRING = 'Entire Site';

class Wpscx_Ajax {
	

	function wphcx_scan_function() {
		require_once( WPSC_FRAMEWORK );
                //wpscx_print_debug( 'wphcx_scan_function triggered', 0, 0, round( memory_get_usage() / 1000, 5 ), 0 );
                
		global $wpdb;
		global $wpsc_settings;
                wpscx_set_global_vars();

		$scan_in_progress = false;

		if ( 'true' === $wpsc_settings[141]->option_value ) {
			$scan_in_progress = true;
		}
                
                //wpscx_print_debug( 'Broken Code SIP Result: ' . $scan_in_progress . " | Raw Variable: " . $wpsc_settings[141]->option_value, 0, 0, round( memory_get_usage() / 1000, 5 ), 0 );

		if ( ! $scan_in_progress ) {
			echo 'false';
		} else {
			echo 'true';
		}
		die();
	}

	function wpscx_finish_html_scan() {
		global $wpdb;
		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';

		$settings = $wpdb->get_results( 'SELECT option_value FROM ' . $options_table );

		$time       = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='last_scan_finished'" );
                if ( isset( $time[0]->option_value ) ) {
                    $time       = $time[0]->option_value;
                    $end_time   = time();
                }
	}

	function wpgcx_finish_scan() {
			$start = round( microtime( true ), 5 );
			global $wpdb;
			global $wpscx_ent_included;
			$options_table = $wpdb->prefix . 'spellcheck_grammar_options';
			$sql_count     = 0;

			$settings = $wpdb->get_results( 'SELECT option_value FROM ' . $options_table );
		if ( WPSCX_SITE_STRING !== $settings[7]->option_value ) {
			return false;
		}

			$time = $wpdb->get_results( "SELECT * FROM $options_table WHERE option_name='scan_start_time'" );
		$sql_count++;
			$time = $time[0]->option_value;

			$end_time = time();

			$total_time = wpscx_time_elapsed( $end_time - $time );
			$wpdb->update( $options_table, array( 'option_value' => $total_time ), array( 'option_name' => 'last_scan_time' ) );
		$sql_count++;

		if ( $wpscx_ent_included ) {
				$end        = round( microtime( true ), 5 );
				$total_time = round( $end - $start, 5 );
				wpscx_print_debug_end( '9.21 Grammar Check Pro', $total_time );
		} else {
				$end        = round( microtime( true ), 5 );
				$total_time = round( $end - $start, 5 );
				wpscx_print_debug_end( '9.21 Grammar Check Base', $total_time );
		}
	}

	function wpscx_finish_scan() {
		$start     = round( microtime( true ), 5 );
		$sql_count = 0;
		sleep( 1 );
		global $wpdb;
		global $wpscx_ent_included;
				global $wpsc_version;
		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';

		$settings = $wpdb->get_results( 'SELECT option_value FROM ' . $options_table );
		if ( WPSCX_SITE_STRING !== $settings[45]->option_value ) {
			return false;
		}

		if ( 'true' === $settings[0]->option_value ) {
						$emailer = new Wpscx_Email;
						$emailer->email_admin();
		}

			$total_word = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name ='total_word_count'" );
		$sql_count++;
			$total_words = $total_word[0]->option_value;

			$word_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE ignore_word='false'" );
		$sql_count++;

			$literacy_factor = 0;
		if ( $total_words > 0 ) {
			$literacy_factor = ( ( $total_words - $word_count ) / $total_words ) * 100;
		} else {
			$literacy_factor = 100; }
			$literacy_factor = number_format( floor( (float) $literacy_factor * 100 ) / 100, 2, '.', '' );

			$time = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='scan_start_time'" );
		$sql_count++;
			$time = $time[0]->option_value;

			$wpdb->update( $options_table, array( 'option_value' => $literacy_factor ), array( 'option_name' => 'literary_factor' ) );
		$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'entire_scan' ) );
		$sql_count++;

						$end_time   = time();
						$total_time = wpscx_time_elapsed( $end_time - $time );
						$wpdb->update( $options_table, array( 'option_value' => $total_time ), array( 'option_name' => 'last_scan_finished' ) );
		$sql_count++;

		if ( $wpscx_ent_included ) {
			$end        = round( microtime( true ), 5 );
							wpscx_print_debug( 'Finalization', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), 0 );
		} else {
			$end        = round( microtime( true ), 5 );
							wpscx_print_debug( 'Finalization Scan', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), 0 );
		}
	}

	function wpscx_finish_empty_scan() {
		$start     = round( microtime( true ), 5 );
		$sql_count = 0;
		global $wpdb;
		global $wpscx_ent_included;
				global $wpsc_version;
		$table_name    = $wpdb->prefix . 'spellcheck_empty';
		$options_table = $wpdb->prefix . 'spellcheck_options';

		$settings = $wpdb->get_results( 'SELECT option_value FROM ' . $options_table );
		$sql_count++;
		if ( WPSCX_SITE_STRING !== $settings[63]->option_value ) {
			return false;
		}

		if ( 'true' === $settings[100]->option_value ) {
			$total_fields = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name ='empty_checked'" );
			$sql_count++;
			$total_fields = $total_fields[0]->option_value;
			$empty_count  = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE ignore_word='false'" );
			$sql_count++;

			$empty_factor = 0;
			if ( $total_fields > 0 ) {
				$empty_factor = ( ( $total_fields - $empty_count ) / $total_fields ) * 100;
			} else {
				$empty_factor = 100; }
			if ( $empty_factor < 0 ) {
				$empty_factor = 0;
			}
			$empty_factor = number_format( (float) $empty_factor, 2, '.', '' );

			$wpdb->update( $options_table, array( 'option_value' => $empty_factor ), array( 'option_name' => 'empty_factor' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'entire_empty_scan' ) );
			$sql_count++;

			$time = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='scan_start_time'" );
			$sql_count++;
			$time = $time[0]->option_value;

			if ( $wpscx_ent_included ) {
				$end        = round( microtime( true ), 5 );
				$total_time = round( $end - $start, 5 );
				wpscx_print_debug_end( "$wpsc_version SEO Check Pro", $total_time );
			} else {
				$end        = round( microtime( true ), 5 );
				$total_time = round( $end - $start, 5 );
				wpscx_print_debug_end( "$wpsc_version SEO Check Base", $total_time );
			}

						$end_time = time();
			$total_time           = wpscx_time_elapsed( $end_time - $time );
			$wpdb->update( $options_table, array( 'option_value' => $total_time ), array( 'option_name' => 'empty_start_time' ) );
			$sql_count++;
		}
				$end = round( microtime( true ), 5 );
				wpscx_print_debug( 'Empty Finalization', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), 0 );
	}

	function wpscx_scan_function() {
		require_once( WPSC_FRAMEWORK );

		global $wpdb;
		global $wpsc_settings;

		$scan_in_progress = false;

		if ( 'true' === $wpsc_settings[66]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[67]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[68]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[69]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[70]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[71]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[72]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[73]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[74]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[75]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[76]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[77]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[78]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[79]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[80]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[81]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[82]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[83]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[84]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[85]->option_value ) {
			$scan_in_progress = true;
		}

		if ( ! $scan_in_progress ) {
			echo 'false';
		} else {
			echo 'true';
		}
		die();
	}

	function wpscx_empty_scan_function() {
		require_once( WPSC_FRAMEWORK );

		global $wpdb;
		global $wpsc_settings;

		$scan_in_progress = false;

		if ( 'true' === $wpsc_settings[87]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[88]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[89]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[90]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[91]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[92]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[93]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[94]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[95]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[96]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[97]->option_value ) {
			$scan_in_progress = true;
		}
		if ( 'true' === $wpsc_settings[98]->option_value ) {
			$scan_in_progress = true;
		}

		if ( ! $scan_in_progress ) {
			echo 'false';
		} else {
			echo 'true';
		}
		die();
	}

	function wpscx_display_results() {
		global $wpscx_ent_included;
                global $wpsc_version;
		$start = round( microtime( true ), 5 );
		require_once( 'class-wpsc-results.php' );
		$this->wpscx_finish_scan();

		$results_table = new Sc_Table();
		$results_table->prepare_items( true );

		$end = round( microtime( true ), 5 );
		wpscx_print_debug( 'Get Results Table', round( $end - $start, 5 ), 1, round( memory_get_usage() / 1000, 5 ), 0 );
		if ( $wpscx_ent_included ) {
			wpscx_print_debug_end( "$wpsc_version Spell Check Pro" );
		} else {
			wpscx_print_debug_end( "$wpsc_version Spell Check Base" );
		}
		die( json_encode( $results_table->display() ) );
	}

	function wpscx_get_stats() {
		global $wpdb;

		$table_name   = $wpdb->prefix . 'spellcheck_options';
		$errors_table = $wpdb->prefix . 'spellcheck_words';
		$post_table   = $wpdb->prefix . 'posts';
		$settings     = $wpdb->get_results( 'SELECT option_name, option_value FROM ' . $table_name );

		$page_count      = $settings[28]->option_value;
		$post_count      = $settings[29]->option_value;
		$media_count     = $settings[32]->option_value;
		$eps_count       = $settings[21]->option_value;
		$scan_time       = $settings[27]->option_value;
		$scan_type       = $settings[45]->option_value;
		$total_errors    = $wpdb->get_var( "SELECT COUNT(*) FROM $errors_table WHERE ignore_word='false'" );
		$total_pages     = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'page'" );
		$total_posts     = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'post'" );
		$total_media     = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'attachment'" );
		$literacy_factor = $settings[64]->option_value;

		if ( isset( $_POST['scantime'] ) ) {
			$scan_time = wpscx_time_elapsed( (int) sanitize_text_field( $_POST['scantime'] ) );
			$wpdb->update( $table_name, array( 'option_value' => $scan_time ), array( 'option_name' => 'last_scan_finished' ) );
		}

		$stats = array(
			'pageCount'      => $page_count,
			'postCount'      => $post_count,
			'mediaCount'     => $media_count,
			'epsCount'       => $eps_count,
			'scanTime'       => $scan_time,
			'totalErrors'    => $total_errors,
			'literacyFactor' => $literacy_factor,
			'scanType'       => $scan_type,
			'totalPosts'     => $total_posts,
			'totalPages'     => $total_pages,
			'totalMedia'     => $total_media,
		);

		die( print json_encode( $stats ) );
	}

	function wpscx_get_stats_empty() {
		global $wpdb;

		$table_name   = $wpdb->prefix . 'spellcheck_options';
		$errors_table = $wpdb->prefix . 'spellcheck_empty';
		$post_table   = $wpdb->prefix . 'posts';
		$settings     = $wpdb->get_results( 'SELECT option_name, option_value FROM ' . $table_name );

		$page_count      = $settings[59]->option_value;
		$post_count      = $settings[60]->option_value;
		$media_count     = $settings[61]->option_value;
		$scan_time       = $settings[102]->option_value;
		$scan_type       = $settings[63]->option_value;
		$empty_factor    = $settings[65]->option_value;
		$empty_eps       = $settings[62]->option_value;
		$total_errors    = $wpdb->get_var( "SELECT COUNT(*) FROM $errors_table WHERE ignore_word='false'" );
		$total_pages     = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'page'" );
		$total_posts     = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'post'" );
		$total_media     = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'attachment'" );
		$literacy_factor = $settings[64]->option_value;

		if ( isset( $_POST['scantime'] ) ) {
			$scan_time = wpscx_time_elapsed( (int) sanitize_text_field( $_POST['scantime'] ) );
			$wpdb->update( $table_name, array( 'option_value' => $scan_time ), array( 'option_name' => 'empty_start_time' ) );
		}

		$stats = array(
			'pageCount'   => $page_count,
			'postCount'   => $post_count,
			'mediaCount'  => $media_count,
			'scanTime'    => $scan_time,
			'totalErrors' => $total_errors,
			'scanType'    => $scan_type,
			'totalPosts'  => $total_posts,
			'totalPages'  => $total_pages,
			'totalMedia'  => $total_media,
			'emptyFactor' => $empty_factor,
			'emptyEPS'    => $empty_eps,
		);

		die( print json_encode( $stats ) );
	}

	function wpscx_get_stats_grammar() {
		global $wpdb;

		$table_name   = $wpdb->prefix . 'spellcheck_grammar_options';
		$errors_table = $wpdb->prefix . 'spellcheck_words';
		$post_table   = $wpdb->prefix . 'posts';
		$settings     = $wpdb->get_results( 'SELECT option_name, option_value FROM ' . $table_name );

		$page_count      = $settings[4]->option_value;
		$post_count      = $settings[5]->option_value;
		$scan_time       = $settings[3]->option_value;
		$scan_type       = $settings[7]->option_value;
		$total_errors    = $settings[6]->option_value;
		$total_pages     = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'page'" );
		$total_posts     = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'post'" );

		if ( isset( $_POST['scantime'] ) ) {
			$scan_time = wpscx_time_elapsed( (int) sanitize_text_field( $_POST['scantime'] ) );
			$wpdb->update( $table_name, array( 'option_value' => $scan_time ), array( 'option_name' => 'last_scan_time' ) );
		}

		$stats = array(
			'pageCount'   => $page_count,
			'postCount'   => $post_count,
			'scanTime'    => $scan_time,
			'totalErrors' => $total_errors,
			'scanType'    => $scan_type,
			'totalPosts'  => $total_posts,
			'totalPages'  => $total_pages,
		);

		die( print json_encode( $stats ) );
	}

	function wpscx_get_stats_code() {
		global $wpdb;

		$table_name   = $wpdb->prefix . 'spellcheck_options';
		$errors_table = $wpdb->prefix . 'spellcheck_html';
		$post_table   = $wpdb->prefix . 'posts';
		$settings     = $wpdb->get_results( 'SELECT option_name, option_value FROM ' . $table_name );

		$page_count  = $settings[143]->option_value;
		$post_count  = $settings[144]->option_value;
		$media_count = $settings[145]->option_value;
		$scan_time   = $settings[27]->option_value;
		$total_errors    = $wpdb->get_var( "SELECT COUNT(*) FROM $errors_table WHERE ignore_word='false'" );
		$total_pages     = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'page'" );
		$total_posts     = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'post'" );
		$total_media     = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'attachment'" );
		$literacy_factor = $settings[64]->option_value;

		if ( isset( $_POST['scantime'] ) ) {
			$scan_time = wpscx_time_elapsed( (int) sanitize_text_field( $_POST['scantime'] ) );
			$wpdb->update( $table_name, array( 'option_value' => $scan_time ), array( 'option_name' => 'last_scan_finished' ) );
		}

		$stats = array(
			'pageCount'      => $page_count,
			'postCount'      => $post_count,
			'mediaCount'     => $media_count,
			'scanTime'       => $scan_time,
			'totalErrors'    => $total_errors,
			'literacyFactor' => $literacy_factor,
			'totalPosts'     => $total_posts,
			'totalPages'     => $total_pages,
			'totalMedia'     => $total_media,
		);

		die( print json_encode( $stats ) );
	}

	function wpscx_display_results_empty() {
		require_once( 'class-wpsc-results.php' );
		$this->wpscx_finish_empty_scan();

		$start = round( microtime( true ), 5 );

		$results_table = new Sc_Table();
		$results_table->prepare_empty_items();

		$end = round( microtime( true ), 5 );
		wpscx_print_debug( 'Empty Get Table for Results ', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), 0 );

		die( json_encode( $results_table->display() ) );
	}

	function wpscx_display_results_grammar() {
		include 'grammar/class-grammar-results.php';
		$this->wpgcx_finish_scan();

		$results_table = new Wpgcx_Table();
		$results_table->prepare_items();

		die( json_encode( $results_table->display() ) );
	}

	function wpscx_display_results_html() {
		require_once( 'class-html-results.php' );
		$this->wpscx_finish_html_scan();

		$results_table = new Wphcx_Table();
		$results_table->prepare_items( true );

		die( json_encode( $results_table->display() ) );
	}
        
        function wpscx_prep_empty_scan($time, $type) {
            global $wpdb;
            $options_table = $wpdb->prefix . "spellcheck_options";
            echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">' . esc_html( $type ) . '</span>. Estimated time for completion is ' . esc_html( $time ) . ' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
            
            wpscx_clear_empty_results();

            $wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_scan_in_progress' ) );
            $wpdb->update( $options_table, array( 'option_value' => time() ), array( 'option_name' => 'last_scan_date' ) );
        }

	function wpscx_start_scan_empty() {
		require_once( WPSC_FRAMEWORK );
                $estimated_time = '0 seconds';

		global $wpdb;
		$options_table = $wpdb->prefix . 'spellcheck_options';
		global $wpscx_ent_included;

		$settings = $wpdb->get_results( 'SELECT option_value FROM ' . $options_table );

		$type = sanitize_text_field( $_POST['type'] );

		$start_time = time();
		$wpdb->update( $options_table, array( 'option_value' => $start_time ), array( 'option_name' => 'scan_start_time' ) );

		if ( 'Menus' === $type ) {
                        $this->wpscx_prep_empty_scan($estimated_time, 'Menus');
                        $wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_menu_sip' ) );
			$wpdb->update( $options_table, array( 'option_value' => 'Menus' ), array( 'option_name' => 'last_empty_type' ) );

			if ( $wpscx_ent_included ) {
				wpscx_check_menus_empty_ent();
			}
		} elseif ( 'Page Titles' === $type ) {
			$this->wpscx_prep_empty_scan($estimated_time, 'Page Titles');
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_page_title_sip' ) );
			$wpdb->update( $options_table, array( 'option_value' => 'Page Titles' ), array( 'option_name' => 'last_empty_type' ) );

			if ( $wpscx_ent_included ) {
				wpscx_check_page_title_empty_ent();
			} else {
				wpscx_check_page_title_empty();
			}
		} elseif ( 'Post Titles' === $type ) {
			$this->wpscx_prep_empty_scan($estimated_time, 'Post Titles');
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_post_title_sip' ) );
			$wpdb->update( $options_table, array( 'option_value' => 'Post Titles' ), array( 'option_name' => 'last_empty_type' ) );

			if ( $wpscx_ent_included ) {
				wpscx_check_post_title_empty_ent();
			} else {
				wpscx_check_post_title_empty();
			}
		} elseif ( 'Tag Descriptions' === $type ) {
			$this->wpscx_prep_empty_scan($estimated_time, 'Tag Descriptions');
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_tag_desc_sip' ) );
			$wpdb->update( $options_table, array( 'option_value' => 'Tag Descriptions' ), array( 'option_name' => 'last_empty_type' ) );

			if ( $wpscx_ent_included ) {
				wpscx_check_post_tag_descriptions_empty_ent();
			}
		} elseif ( 'Category Descriptions' === $type ) {
			$this->wpscx_prep_empty_scan($estimated_time, 'Category Descriptions');
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_cat_desc_sip' ) );
			$wpdb->update( $options_table, array( 'option_value' => 'Category Descriptions' ), array( 'option_name' => 'last_empty_type' ) );

			if ( $wpscx_ent_included ) {
				wpscx_check_post_categories_description_empty_ent();
			}
		} elseif ( 'Media Files' === $type ) {
			$this->wpscx_prep_empty_scan($estimated_time, 'Media Files');
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_media_sip' ) );
			$wpdb->update( $options_table, array( 'option_value' => 'Media Files' ), array( 'option_name' => 'last_empty_type' ) );

			if ( $wpscx_ent_included ) {
				wpscx_check_media_empty_ent();
			}
		} elseif ( 'WooCommerce and WP-eCommerce Products' === $type ) {
			$this->wpscx_prep_empty_scan($estimated_time, 'eCommerce Products');
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_ecommerce_sip' ) );
			$wpdb->update( $options_table, array( 'option_value' => 'eCommerce Products' ), array( 'option_name' => 'last_empty_type' ) );

			if ( $wpscx_ent_included ) {
				wpscx_check_ecommerce_empty_ent();
			}
		} elseif ( 'Authors' === $type ) {
                        $this->wpscx_prep_empty_scan($estimated_time, 'Authors');
                        $wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_author_sip' ) );
                        $wpdb->update( $options_table, array( 'option_value' => 'Authors' ), array( 'option_name' => 'last_empty_type' ) );

                        wpscx_check_author_empty();
		} elseif ( 'Page SEO' === $type ) {
                        $this->wpscx_prep_empty_scan($estimated_time, 'Page SEO');
                        $wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_page_seo_sip' ) );
                        $wpdb->update( $options_table, array( 'option_value' => 'Page SEO' ), array( 'option_name' => 'last_empty_type' ) );

                        if ( $wpscx_ent_included ) {
                                wpscx_check_page_seo_empty_ent();
                        }
		} elseif ( 'Post SEO' === $type ) {
                        $this->wpscx_prep_empty_scan($estimated_time, 'Post SEO');
                        $wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_post_seo_sip' ) );
                        $wpdb->update( $options_table, array( 'option_value' => 'Post SEO' ), array( 'option_name' => 'last_empty_type' ) );

			if ( $wpscx_ent_included ) {
				wpscx_check_post_seo_empty_ent();
			}
		} elseif ( 'Media Files SEO' === $type ) {
                        $this->wpscx_prep_empty_scan($estimated_time, 'Media Files SEO');
                        $wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_media_seo_sip' ) );
                        $wpdb->update( $options_table, array( 'option_value' => 'Media Files SEO' ), array( 'option_name' => 'last_empty_type' ) );

			if ( $wpscx_ent_included ) {
				wpscx_check_media_seo_empty_ent();
			}
		} elseif ( WPSCX_SITE_STRING === $type ) {
                        $this->wpscx_prep_empty_scan($estimated_time, WPSCX_SITE_STRING);

                        wpscx_clear_results_empty();
                        $rng_seed = rand( 0, 999999999 );
                        wpscx_set_empty_scan_in_progress( $rng_seed );
                        $wpdb->update( $options_table, array( 'option_value' => WPSCX_SITE_STRING ), array( 'option_name' => 'last_empty_type' ) );

                        wpscx_scan_site_empty();
		}
		die();
	}

	function wpscx_start_scan_grammar() {
		require_once( WPSC_FRAMEWORK );

		global $wpdb;
		$options_table = $wpdb->prefix . 'spellcheck_grammar_options';
		global $wpscx_ent_included;

		$settings = $wpdb->get_results( 'SELECT option_value FROM ' . $options_table );

		$type = sanitize_text_field( $_POST['type'] );

		$start_time = time();
		$wpdb->update( $options_table, array( 'option_value' => $start_time ), array( 'option_name' => 'scan_start_time' ) );

		if ( 'Posts' === $type ) {
			wpgcx_clear_results(); //Clear out results table in preparation for a new scan

			$wpdb->update( $options_table, array( 'option_value' => 0 ), array( 'option_name' => 'pro_error_count' ) );
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_running' ) );
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'post_running' ) );
			$wpdb->update( $options_table, array( 'option_value' => 'Posts' ), array( 'option_name' => 'last_scan_type' ) );
			$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'last_scan_errors' ) );
			echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Posts</span>. The page will automatically refresh when the scan has finished.';

			wpgcx_check_posts();
		} elseif ( 'Pages' === $type ) {
			wpgcx_clear_results(); //Clear out results table in preparation for a new scan

			$wpdb->update( $options_table, array( 'option_value' => 0 ), array( 'option_name' => 'pro_error_count' ) );
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_running' ) );
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'page_running' ) );
			$wpdb->update( $options_table, array( 'option_value' => 'Pages' ), array( 'option_name' => 'last_scan_type' ) );
			$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'last_scan_errors' ) );
			echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Pages</span>. The page will automatically refresh when the scan has finished.';

			wpgcx_check_pages();
		} elseif ( WPSCX_SITE_STRING === $type ) {
			wpgcx_clear_results(); //Clear out results table in preparation for a new scan
			echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Entire Site</span>. The page will automatically refresh when the scan has finished.';

			$wpdb->update( $options_table, array( 'option_value' => 0 ), array( 'option_name' => 'pro_error_count' ) );
			$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'last_scan_errors' ) );
			$wpdb->update( $options_table, array( 'option_value' => WPSCX_SITE_STRING ), array( 'option_name' => 'last_scan_type' ) );
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_running' ) );
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'post_running' ) );
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'page_running' ) );

			wpgcx_scan_site();
		}
		die();
	}

	function wpscx_start_scan_bc() {
		$start = round( microtime( true ), 5 );
		require_once( WPSC_FRAMEWORK );
                wpscx_print_debug( 'Broken Code Init - Start', 0, 0, round( memory_get_usage() / 1000, 5 ), 0 );
		global $wpdb;
		$options_table = $wpdb->prefix . 'spellcheck_options';
		global $wpscx_ent_included;
                $sql_count = 0;

		$settings = $wpdb->get_results( 'SELECT option_value FROM ' . $options_table );

		$type = sanitize_text_field( $_POST['type'] );

		$start_time = time();
		$wpdb->update( $options_table, array( 'option_value' => $start_time ), array( 'option_name' => 'html_scan_start_time' ) );

		if ( WPSCX_SITE_STRING === $type ) {
			wphcx_clear_results(); //Clear out results table in preparation for a new scan

			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'html_scan_running' ) );
			echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Entire Site</span>. The page will automatically refresh when the scan has finished.';

			wpscx_check_broken_code();
                        $wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'html_scan_running' ) );
		} elseif ( 'Broken HTML' === $type ) {
			wphcx_clear_results(); //Clear out results table in preparation for a new scan

			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'html_scan_running' ) );
			echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Broken HTML</span>. The page will automatically refresh when the scan has finished.';

			wpscx_check_broken_html();
                        $wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'html_scan_running' ) );
		} elseif ( 'Broken Shortcodes' === $type ) {
			wphcx_clear_results(); //Clear out results table in preparation for a new scan

			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'html_scan_running' ) );
			echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Broken Shortcodes</span>. The page will automatically refresh when the scan has finished.';

			wpscx_check_broken_shortcode();
                        $wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'html_scan_running' ) );
		}

		$end = round( microtime( true ), 5 );
		wpscx_print_debug( 'Broken Shortcodes(Init)', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), 0 );
		die();
	}

	function wpscx_start_scan() {
		$start = round( microtime( true ), 5 );
		require_once( WPSC_FRAMEWORK );
		set_time_limit( 6000 );

		global $wpdb;
		global $wpscx_ent_included;
		$options_table = $wpdb->prefix . 'spellcheck_options';

		$settings = $wpdb->get_results( 'SELECT option_value FROM ' . $options_table );

		$type = sanitize_text_field( $_POST['type'] );

		$start_time = time();
		$wpdb->update( $options_table, array( 'option_value' => $start_time ), array( 'option_name' => 'scan_start_time' ) );
		$sql_count = 0;
		if ( 'Pages' === $type ) {
			wpscx_clear_results();
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'page_sip' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => time() ), array( 'option_name' => 'last_scan_date' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'Page Content' ), array( 'option_name' => 'last_scan_type' ) );
			$sql_count++;
			if ( $wpscx_ent_included ) {
					wpscx_check_pages_ent();
			} else {
					wpscx_check_pages();
			}
			echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Page Content</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		} elseif ( 'Posts' === $type ) {
			wpscx_clear_results();

			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'post_sip' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => time() ), array( 'option_name' => 'last_scan_date' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'Post Content' ), array( 'option_name' => 'last_scan_type' ) );
			$sql_count++;
			if ( $wpscx_ent_included ) {
				wpscx_check_posts_ent();
			} else {
				wpscx_check_posts();
			}

			echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Post Content</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		} elseif ( 'Authors' === $type ) {
			wpscx_clear_results();

			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'author_sip' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => time() ), array( 'option_name' => 'last_scan_date' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'Authors' ), array( 'option_name' => 'last_scan_type' ) );
			$sql_count++;
			wpscx_check_authors();

			echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Authors</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		} elseif ( 'Menus' === $type ) {
			wpscx_clear_results();

			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'menu_sip' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => time() ), array( 'option_name' => 'last_scan_date' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'Menus' ), array( 'option_name' => 'last_scan_type' ) );
			$sql_count++;
			if ( $wpscx_ent_included ) {
				wpscx_check_menus_ent();
			}

			echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Menus</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		} elseif ( 'Tags' === $type ) {
			wpscx_clear_results();

			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'tag_title_sip' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => time() ), array( 'option_name' => 'last_scan_date' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'Tag Titles' ), array( 'option_name' => 'last_scan_type' ) );
			$sql_count++;
			if ( $wpscx_ent_included ) {
					wpscx_check_post_tags_ent();
			}

			echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Tags</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		} elseif ( 'Categories' === $type ) {
			wpscx_clear_results();

			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'cat_title_sip' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => time() ), array( 'option_name' => 'last_scan_date' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'Category Titles' ), array( 'option_name' => 'last_scan_type' ) );
			$sql_count++;
			if ( $wpscx_ent_included ) {
					wpscx_check_post_categories_ent();
			}

			echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Categories</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		} elseif ( 'SEO Descriptions' === $type ) {
			wpscx_clear_results();

			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'seo_desc_sip' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => time() ), array( 'option_name' => 'last_scan_date' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'SEO Descriptions' ), array( 'option_name' => 'last_scan_type' ) );
			$sql_count++;

			if ( $wpscx_ent_included ) {
					wpscx_check_yoast_ent();
			}

			echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">SEO Descriptions</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		} elseif ( 'SEO Titles' === $type ) {
			wpscx_clear_results();

			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'seo_title_sip' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => time() ), array( 'option_name' => 'last_scan_date' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'SEO Titles' ), array( 'option_name' => 'last_scan_type' ) );
			$sql_count++;

			if ( $wpscx_ent_included ) {
					wpscx_check_seo_titles_ent();
			}

			echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">SEO Titles</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		} elseif ( 'Sliders' === $type ) {
			wpscx_clear_results();

			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'slider_sip' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => time() ), array( 'option_name' => 'last_scan_date' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'Sliders' ), array( 'option_name' => 'last_scan_type' ) );
			$sql_count++;

			if ( $wpscx_ent_included ) {
					wpscx_check_sliders_ent();
			}

			echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Sliders</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		} elseif ( 'Media Files' === $type ) {
			wpscx_clear_results();

			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'media_sip' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => time() ), array( 'option_name' => 'last_scan_date' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'Media Files' ), array( 'option_name' => 'last_scan_type' ) );
			$sql_count++;

			if ( $wpscx_ent_included ) {
					wpscx_check_media_ent();
			}

			echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Media Files</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		} elseif ( 'WooCommerce and WP-eCommerce Products' === $type ) {
			wpscx_clear_results();

			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'ecommerce_sip' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => time() ), array( 'option_name' => 'last_scan_date' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'eCommerce Products' ), array( 'option_name' => 'last_scan_type' ) );
			$sql_count++;

			if ( $wpscx_ent_included ) {
					wpscx_check_ecommerce_ent();
			}

			echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">eCommerce Products</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		} elseif ( 'Widgets' === $type ) {
			wpscx_clear_results();

			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'ecommerce_sip' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => time() ), array( 'option_name' => 'last_scan_date' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'Widgets' ), array( 'option_name' => 'last_scan_type' ) );
			$sql_count++;

			if ( $wpscx_ent_included ) {
					wpscx_check_widgets();
			}

			echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Widgets</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		} elseif ( 'Contact Form 7' === $type ) {
			wpscx_clear_results();
			wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array( 'jquery' ) );
			wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( WPSC_ADMIN_AJAX ) ) );
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_in_progress' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'cf7_sip' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => time() ), array( 'option_name' => 'last_scan_date' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => 'Contact Form 7' ), array( 'option_name' => 'last_scan_type' ) );
			$sql_count++;
			wpscx_check_cf7();

			echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Contact Form 7</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		} elseif ( WPSCX_SITE_STRING === $type ) {
			wpscx_clear_results( 'full' );
			$rng_seed = rand( 0, 999999999 );

			wpscx_set_scan_in_progress( $rng_seed );
			$wpdb->update( $options_table, array( 'option_value' => time() ), array( 'option_name' => 'last_scan_date' ) );
			$sql_count++;
			$wpdb->update( $options_table, array( 'option_value' => WPSCX_SITE_STRING ), array( 'option_name' => 'last_scan_type' ) );
			$sql_count++;
			wpscx_scan_site_event( 10, true );

			echo '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for the <span style="color: rgb(0, 150, 255); font-weight: bold;">Entire Site</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		}

		$end = round( microtime( true ), 5 );
		die();
	}

	function _ajax_fetch_wpsc_list_callback() {

		$wp_list_table = new Sc_Table();
		$wp_list_table->ajax_response();
	}
        
        function wpscx_openAI_ajax() {
            $type = sanitize_text_field( $_POST['type'] );
            $post_id = sanitize_text_field( $_POST['id'] );
            
            $openAI = new Wpscx_OpenAI();
            if ($type == "SEO Post Title" || $type == "SEO Page Title") {
                $response = $openAI->getTitle($post_id);
            } else {
                $response = $openAI->getDesc($post_id);
            }
            
            wp_send_json($response);
        }
}
