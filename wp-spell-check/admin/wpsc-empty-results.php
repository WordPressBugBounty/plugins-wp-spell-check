<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue styles via admin_enqueue_scripts hook to prevent FOUC
 * This ensures styles load in <head> before HTML body renders
 */
function wpscx_enqueue_empty_results_styles( $hook ) {
	// Only enqueue on the empty results page
	if ( isset( $_GET['page'] ) && 'wp-spellcheck-seo.php' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
		global $wpsc_version;
		wp_enqueue_style( 'wpsc-admin-styles', plugin_dir_url( __DIR__ ) . 'css/admin-styles.css', array(), $wpsc_version );
		wp_enqueue_style( 'wpsc-sidebar', plugin_dir_url( __DIR__ ) . 'css/wpsc-sidebar.css', array(), $wpsc_version );
		wp_enqueue_style( 'wpsc-sidebar-inline', plugin_dir_url( __DIR__ ) . 'admin/css/sidebar-inline.css', array( 'wpsc-sidebar' ), $wpsc_version );
		wp_enqueue_style( 'wpsc-jquery-ui', plugin_dir_url( __DIR__ ) . 'css/wpscx-jquery-ui.css', array(), $wpsc_version );

		// Enqueue empty results UI JavaScript (extracted from inline scripts)
		wp_enqueue_script(
			'empty-results-ui',
			plugin_dir_url( __DIR__ ) . 'admin/js/empty-results-ui.js',
			array( 'jquery', 'feature-request' ),
			$wpsc_version,
			true
		);
	}
}
add_action( 'admin_enqueue_scripts', 'wpscx_enqueue_empty_results_styles' );

function wpscx_admin_empty_render() {
	// Styles are now enqueued via admin_enqueue_scripts hook (wpscx_enqueue_empty_results_styles)
	// to prevent FOUC - they load in <head> before HTML body renders

	$start = time();
	ini_set( 'memory_limit', '8192M' );
	set_time_limit( 600 );
	global $wpdb;
	global $wpscx_ent_included;
	global $wpscx_base_page_max;
	global $wpsc_version;
	$table_name         = $wpdb->prefix . 'spellcheck_words';
	$empty_table        = $wpdb->prefix . 'spellcheck_empty';
	$options_table      = $wpdb->prefix . 'spellcheck_options';
	$post_table         = $wpdb->prefix . 'posts';
	$total_smartslider  = 0;
	$total_huge_it      = 0;
	$total_seo_title    = 0;
	$total_seo_desc     = 0;
	$sql_count          = 0;
	$empty_scan_message = '';
	$checked_pages      = '';
	$ignore_message     = '';
	$utils              = new Wpscx_Results_Utils();

	// Check for updated OpenAI Key and save if needed
	if ( isset( $_POST['apiKey'] ) ) {
		check_admin_referer( 'wpsc_update_openai' );
		$apiKey = sanitize_text_field( wp_unslash( $_POST['apiKey'] ) );

		$wpdb->update( $options_table, array( 'option_value' => $apiKey ), array( 'option_name' => 'openAIKey' ) );
	}

	wp_enqueue_script( 'jquery-ui-dialog' );
	// wp_enqueue_script( 'admin-js', plugin_dir_url( __FILE__ ) . '../js/feature-request.js' );
	wp_enqueue_script( 'feature-request', plugin_dir_url( __FILE__ ) . '../js/admin-js.js' );
	wp_enqueue_script( 'wpscx-jquery-contextMenu', plugin_dir_url( __FILE__ ) . '../js/wpscx-jquery.contextMenu.js' );
	wp_enqueue_script( 'wpscx-jquery-ui-position', plugin_dir_url( __FILE__ ) . '../js/wpscx-jquery.ui.position.js' );

	if ( ! isset( $_GET['action'] ) ) {
		$_GET['action'] = '';
	}
	if ( ! isset( $_GET['submit'] ) ) {
		$_GET['submit'] = '';
	}
	if ( ! isset( $_GET['submit-empty'] ) ) {
		$_GET['submit-empty'] = '';
	}
	if ( ! isset( $_GET['wpsc-scan-tab'] ) ) {
		$_GET['wpsc-scan-tab'] = '';
	}
	if ( isset( $_GET['ignore-word'] ) ) {
		// Accept wpsc-update-empty-word nonce since ignore-word and word_update are in the same form
		// The form only has one _wpnonce field (wpsc-update-empty-word) to avoid duplicate field overwrite
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce must not be sanitized before wp_verify_nonce(); only wp_unslash() is correct.
		$submitted_nonce = isset( $_GET['_wpnonce'] ) ? wp_unslash( $_GET['_wpnonce'] ) : '';
		$nonce_valid     = $submitted_nonce ? wp_verify_nonce( $submitted_nonce, 'wpsc-update-empty-word' ) : false;

		if ( ! $nonce_valid ) {
			// Nonce failed - check if referer is valid as security fallback
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Referer used only for origin check (strpos against admin_url()), not echoed or in SQL.
			$referer_check = isset( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : '';
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Referer used only for origin check (strpos against admin_url()), not echoed or in SQL.
			$get_referer     = isset( $_GET['_wp_http_referer'] ) ? wp_unslash( $_GET['_wp_http_referer'] ) : '';
			$referer         = $referer_check ? $referer_check : $get_referer;
			$admin_url_lower = strtolower( admin_url() );
			$referer_lower   = $referer ? strtolower( $referer ) : '';
			$referer_valid   = $referer && ( strpos( $referer_lower, $admin_url_lower ) === 0 );

			if ( ! $referer_valid ) {
				// Both nonce and referer failed - this is a security issue
				wp_die( 'The link you followed has expired. Please try again.' );
			}
			// Referer is valid - allow the request (handles stale nonces from cached forms)
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Passed to ignore_word_empty() which sanitizes before SQL/use.
		$ignore_message = $utils->ignore_word_empty( wp_unslash( $_GET['ignore-word'] ) );
	}

	$max_pages = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name = 'pro_max_pages'" );
	$max_pages = intval( $max_pages[0]->option_value );

	if ( ! $wpscx_ent_included ) {
		$max_pages = $wpscx_base_page_max;
	}

	$message = '';

	if ( isset( $_GET['submit'] ) && 'Stop Scans' === $_GET['submit'] ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce must not be sanitized before wp_verify_nonce(); only wp_unslash() is correct.
		if ( ! isset( $_GET['_wpnonce_stop_scans'] ) || ! wp_verify_nonce( wp_unslash( $_GET['_wpnonce_stop_scans'] ), 'wpsc-stop-scans' ) ) {
			wp_die( 'Security check failed' );
		}
		$message = 'All current spell check scans have been stopped.';
		wpscx_clear_scan();
	}
	if ( isset( $_GET['submit-empty'] ) && 'Stop Scans' === $_GET['submit-empty'] ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce must not be sanitized before wp_verify_nonce(); only wp_unslash() is correct.
		if ( ! isset( $_GET['_wpnonce_stop_scans'] ) || ! wp_verify_nonce( wp_unslash( $_GET['_wpnonce_stop_scans'] ), 'wpsc-stop-scans' ) ) {
			wp_die( 'Security check failed' );
		}
		$message = 'All current empty field scans have been stopped.';
		wpscx_clear_empty_scan();
	}

	// Use options already loaded by wpscx_set_global_vars() (called earlier via wpsc_do_ent_api_request) to avoid duplicate SELECT from wp_spellcheck_options.
	global $wpsc_settings;
	if ( isset( $wpsc_settings ) && ( is_array( $wpsc_settings ) || $wpsc_settings instanceof SplFixedArray ) && count( $wpsc_settings ) > 0 ) {
		$settings = $wpsc_settings;
	} else {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe; ORDER BY id ensures stable index-based access.
		$settings = $wpdb->get_results( 'SELECT option_name, option_value FROM ' . $options_table . ' ORDER BY id' );
	}
	$check_pages             = $settings[4]->option_value;
	$check_posts             = $settings[5]->option_value;
	$check_menus             = $settings[7]->option_value;
	$page_titles             = $settings[12]->option_value;
	$post_titles             = $settings[13]->option_value;
	$tags                    = $settings[14]->option_value;
	$categories              = $settings[15]->option_value;
	$seo_desc                = $settings[16]->option_value;
	$seo_titles              = $settings[17]->option_value;
	$page_slugs              = $settings[18]->option_value;
	$post_slugs              = $settings[19]->option_value;
	$check_sliders           = $settings[30]->option_value;
	$check_media             = $settings[31]->option_value;
	$check_ecommerce         = $settings[36]->option_value;
	$check_cf7               = $settings[37]->option_value;
	$check_tag_desc          = $settings[38]->option_value;
	$check_tag_slug          = $settings[39]->option_value;
	$check_cat_desc          = $settings[40]->option_value;
	$check_cat_slug          = $settings[41]->option_value;
	$check_custom            = $settings[42]->option_value;
	$check_authors           = $settings[44]->option_value;
	$check_authors_empty     = $settings[46]->option_value;
	$check_authors_empty     = $settings[47]->option_value;
	$check_menu_empty        = $settings[48]->option_value;
	$check_page_titles_empty = $settings[49]->option_value;
	$check_post_titles_empty = $settings[50]->option_value;
	$check_tag_desc_empty    = $settings[51]->option_value;
	$check_cat_desc_empty    = $settings[52]->option_value;
	$check_page_seo_empty    = $settings[53]->option_value;
	$check_post_seo_empty    = $settings[54]->option_value;
	$check_media_seo_empty   = $settings[55]->option_value;
	$check_media_empty       = $settings[56]->option_value;
	$check_ecommerce_empty   = ( isset( $settings[57] ) && is_object( $settings[57] ) ) ? $settings[57]->option_value : '';
	$openAIKey               = '';
	foreach ( $settings as $row ) {
		if ( isset( $row->option_name ) && 'openAIKey' === $row->option_name ) {
			$openAIKey = $row->option_value;
			break;
		}
	}

	$postmeta_table    = $wpdb->prefix . 'postmeta';
	$post_table        = $wpdb->prefix . 'posts';
	$it_table          = $wpdb->prefix . 'huge_itslider_images';
	$smartslider_table = $wpdb->prefix . 'nextend_smartslider_slides';

	$total_pages = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'page'" );
	$total_posts = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'post'" );
	$total_media = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'attachment'" );

	if ( isset( $_GET['action'] ) ) {
		if ( 'check' === $_GET['action'] ) {

			$total_products = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type='product' AND (post_status='draft' OR post_status='publish')" );
			$total_cf7      = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type='wpcf7_contact_form' AND (post_status='draft' OR post_status='publish')" );
			$total_menu     = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type='nav_menu_item' AND (post_status='draft' OR post_status='publish')" );
			$total_authors  = sizeof( (array) $wpdb->get_results( "SELECT * FROM $post_table GROUP BY post_author" ) );
			++$sql_count;
			$total_tags = sizeof( get_tags() );
			++$sql_count;
			$total_tag_desc = $total_tags;
			$total_tag_slug = $total_tags;
			$total_cat      = sizeof( get_categories() );
			++$sql_count;
			$total_cat_desc  = $total_cat;
			$total_cat_slug  = $total_cat;
			$total_seo_title = sizeof( (array) $wpdb->get_results( "SELECT * FROM $postmeta_table WHERE meta_key='_yoast_wpseo_title' OR meta_key='_aioseop_title' OR meta_key='_su_title'" ) );
			++$sql_count;
			$total_seo_desc = sizeof( (array) $wpdb->get_results( "SELECT * FROM $postmeta_table WHERE meta_key='_yoast_wpseo_metadesc' OR meta_key='_aioseop_description' OR meta_key='_su_description'" ) );
			++$sql_count;

			$total_generic_slider = sizeof(
				(array) get_pages(
					array(
						'number'       => PHP_INT_MAX,
						'hierarchical' => 0,
						'post_type'    => 'slider',
						'post_status'  => array(
							'publish',
							'draft',
						),
					)
				)
			);
			++$sql_count;
			$total_sliders = $total_huge_it + $total_smartslider + $total_generic_slider;

			if ( ! $wpscx_ent_included ) {
				if ( $total_pages > 1000 ) {
					$total_pages = 1000;
				}
				if ( $total_posts > 1000 ) {
					$total_posts = 1000;
				}
				if ( $total_media > 1000 ) {
					$total_posts = 1000;
				}
				if ( $total_seo_title > 1000 ) {
					$total_seo_title = 1000;
				}
				if ( $total_seo_desc > 1000 ) {
					$total_seo_desc = 1000;
				}
			}

			$total_page_slugs = $total_pages;
			$total_post_slugs = $total_posts;
			$total_page_title = $total_pages;
			$total_post_title = $total_posts;

			$estimated_time = intval( ( ( $total_pages + $total_posts ) / 3.5 ) + 3 );
		}
	}

	if ( ! $wpscx_ent_included ) {
		if ( $total_pages > 1000 ) {
			$total_pages = 1000;
		}
		if ( $total_posts > 1000 ) {
			$total_posts = 1000;
		}
		if ( $total_media > 1000 ) {
			$total_posts = 1000;
		}
		if ( $total_seo_title > 1000 ) {
			$total_seo_title = 1000;
		}
		if ( $total_seo_desc > 1000 ) {
			$total_seo_desc = 1000;
		}
	}

	$total_page_slugs = $total_pages;
	$total_post_slugs = $total_posts;
	$total_page_title = $total_pages;
	$total_post_title = $total_posts;

	$estimated_time = intval( ( ( $total_pages + $total_posts ) / 3.5 ) + 3 );
	$scan_message   = '';

	$scan       = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='scan_in_progress';" );
	$empty_scan = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='empty_scan_in_progress';" );

	$check_scan = wpscx_check_scan_progress();
	if ( 'noscript' !== $check_scan && isset( $_GET['wpsc-script'] ) ) {
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array( 'jquery' ) );
		wp_localize_script( 'results-ajax', 'wpscx__spell_ajax_object', array( 'ajax_url' => admin_url( WPSC_ADMIN_AJAX ) ) );
		sleep( 1 );
	}
	$check_empty = wpscx_check_empty_scan_progress();
	if ( 'noscript' !== $check_empty && isset( $_GET['wpsc-script'] ) ) {
		wp_enqueue_script( 'emptyresults-ajax', plugin_dir_url( __FILE__ ) . '/empty-ajax.js', array( 'jquery' ) );
		wp_localize_script(
			'emptyresults-ajax',
			'wpscx_seo_ajax_object',
			array(
				'ajax_url'                         => admin_url( WPSC_ADMIN_AJAX ),
				'wpsc_start_scan_empty_nonce'      => wp_create_nonce( 'wpsc_start_scan_empty' ),
				'wpsc_empty_scan_nonce'            => wp_create_nonce( 'wpsc_empty_scan' ),
				'wpsc_finish_empty_scan_nonce'     => wp_create_nonce( 'wpsc_finish_empty_scan' ),
				'wpsc_display_results_empty_nonce' => wp_create_nonce( 'wpsc_display_results_empty' ),
				'wpsc_get_stats_empty_nonce'       => wp_create_nonce( 'wpsc_get_stats_empty' ),
				'wpsc_openai_nonce'                => wp_create_nonce( 'wpsc_openai' ),
			)
		);
		sleep( 1 );
	}

	$estimated_time = wpscx_time_elapsed( $estimated_time );

	$end = time();
	// Inline script block #1 removed - now handled in empty-results-ui.js

	if ( isset( $_GET['action'] ) && isset( $_GET['submit-empty'] ) && 'check' === $_GET['action'] && 'Clear Results' === $_GET['submit-empty'] ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce must not be sanitized before wp_verify_nonce(); only wp_unslash() is correct.
		if ( ! isset( $_GET['_wpnonce_clear_empty_results'] ) || ! wp_verify_nonce( wp_unslash( $_GET['_wpnonce_clear_empty_results'] ), 'wpsc-clear-empty-results' ) ) {
			wp_die( 'Security check failed' );
		}
		$message = 'All empty field results have been cleared';
		wpscx_clear_empty_results( 'full' );
	}

	if ( isset( $_GET['word_update'] ) ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce must not be sanitized before wp_verify_nonce(); only wp_unslash() is correct.
		$submitted_nonce     = isset( $_GET['_wpnonce'] ) ? wp_unslash( $_GET['_wpnonce'] ) : '';
		$nonce_verify_result = $submitted_nonce ? wp_verify_nonce( $submitted_nonce, 'wpsc-update-empty-word' ) : false;

		// Verify nonce first
		if ( ! $nonce_verify_result ) {
			// Nonce failed - check if referer is valid as security fallback
			// This handles stale nonces from browser cache while maintaining security via referer check
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Referer used only for origin check (strpos against admin_url()), not echoed or in SQL.
			$referer_check = isset( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : '';
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Referer used only for origin check (strpos against admin_url()), not echoed or in SQL.
			$get_referer     = isset( $_GET['_wp_http_referer'] ) ? wp_unslash( $_GET['_wp_http_referer'] ) : '';
			$referer         = $referer_check ? $referer_check : $get_referer;
			$admin_url_lower = strtolower( admin_url() );
			$referer_lower   = $referer ? strtolower( $referer ) : '';
			$referer_valid   = $referer && ( strpos( $referer_lower, $admin_url_lower ) === 0 );

			if ( ! $referer_valid ) {
				// Both nonce and referer failed - this is a security issue
				wp_die( 'The link you followed has expired. Please try again.' );
			}
			// Referer is valid - allow the request (handles stale nonces from cached forms)
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Unslashed via array_map(wp_unslash) on same line; sanitized on next line.
		$word_update_raw = isset( $_GET['word_update'] ) ? array_map( 'wp_unslash', (array) $_GET['word_update'] ) : array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Unslashed via array_map(wp_unslash) on same line; sanitized on next line.
		$edit_page_name_raw = isset( $_GET['edit_page_name'] ) ? array_map( 'wp_unslash', (array) $_GET['edit_page_name'] ) : array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Unslashed via array_map(wp_unslash) on same line; sanitized on next line.
		$edit_page_type_raw = isset( $_GET['edit_page_type'] ) ? array_map( 'wp_unslash', (array) $_GET['edit_page_type'] ) : array();
		$word_update        = array_map( 'sanitize_text_field', $word_update_raw );
		$edit_page_name     = array_map( 'sanitize_text_field', $edit_page_name_raw );
		$edit_page_type     = array_map( 'sanitize_text_field', $edit_page_type_raw );
		$edit_old_word_id   = isset( $_GET['edit_old_word_id'] ) ? array_map( 'absint', (array) $_GET['edit_old_word_id'] ) : array();

		$message = $utils->update_empty_admin( $word_update, $edit_page_name, $edit_page_type, $edit_old_word_id );
	}

	$end = time();
	// echo "debug - Checking For Scan Buttons Pressed Finished: " . ($end - $start) . " Seconds<br />";

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string. Query contains no user input.
	$word_count = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . esc_sql( $table_name ) . " WHERE ignore_word='false'" );
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string. Query contains no user input.
	$empty_count = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . esc_sql( $empty_table ) . " WHERE ignore_word='false'" );

	$empty_table = new Wpscx_Table();
	$empty_table->prepare_empty_items();

	$path = plugin_dir_path( __FILE__ ) . '../premium-functions.php';

	$end = time();
	// echo "debug - Results Tables Prepared: " . ($end - $start) . " Seconds<br />";

	$pro_words   = 0;
	$empty_words = 0;
	if ( ! $wpscx_ent_included ) {
		$pro_word_count   = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='pro_word_count';" );
		$pro_words        = $pro_word_count[0]->option_value;
		$empty_word_count = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='pro_empty_count';" );
		$empty_words      = $empty_word_count[0]->option_value;
	}
	$total_word_count = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='total_word_count';" );
	$literacy_factor  = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='literary_factor';" );
	$literacy_factor  = $literacy_factor[0]->option_value;

	$empty_factor = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='empty_factor';" );
	$empty_factor = $empty_factor[0]->option_value;

	$empty_results     = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='empty_checked';" );
	$empty_field_count = $empty_results[0]->option_value;

	$cron_tasks    = _get_cron_array();
	$scan_progress = false;
	$scan_site     = 0;

	foreach ( $cron_tasks as $task ) {
		if ( 'adminscansite' === key( $task ) ) {
			++$scan_site;
		} elseif ( substr( key( $task ), 0, strlen( 'admincheck' ) ) === 'admincheck' ) {
			$scan_progress = true;
		}
	}
	if ( $scan_site >= 2 ) {
		$scan_progress = true;
	}

	$scanning      = $scan;
	$scan_progress = wpscx_check_scan_progress();
	if ( $scan_progress && '' === $scan_message && 'noscript' === $_GET['wpsc-script'] ) {
		$last_type    = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='last_scan_type'" );
		$scan_message = '<img src="' . esc_url( function_exists( 'wpsc_get_loading_spinner_url' ) ? wpsc_get_loading_spinner_url() : plugin_dir_url( __FILE__ ) . 'images/loading.svg' ) . '" alt="Scan in Progress" class="wpsc-loading-spinner" /> A scan is currently in progress for <span class="sc-message">' . esc_html( $last_type[0]->option_value ) . '</span>. Estimated time for completion is ' . esc_html( $estimated_time ) . ' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
	} elseif ( 'error' === $scanning[0]->option_value && '' === $scan_message && ! $scan_progress ) {
		$scan_message = "<span class='error-red'>No scan currently running. The previous scan was unable to finish scanning</span>";
	} elseif ( '' === $scan_message ) {
		$scan_message = 'No scan currently running';
	}

	$empty_scan_progress = wpscx_check_empty_scan_progress();
	if ( '' === $empty_scan_progress && $empty_scan_message && 'noscript' !== $_GET['wpsc-script'] ) {
		$last_type          = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='last_empty_type'" );
		$empty_scan_message = '<img src="' . esc_url( function_exists( 'wpsc_get_loading_spinner_url' ) ? wpsc_get_loading_spinner_url() : plugin_dir_url( __FILE__ ) . 'images/loading.svg' ) . '" alt="Scan in Progress" class="wpsc-loading-spinner" /> A scan is currently in progress for <span class="sc-message">' . esc_html( $last_type[0]->option_value ) . '</span>. Estimated time for completion is ' . esc_html( $estimated_time ) . ' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
	} elseif ( '' === $empty_scan_message ) {
		$empty_scan_message = 'No scan currently running';
	}

	$time_of_scan  = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='last_scan_finished';" );
	$time_of_empty = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='empty_start_time';" );
	if ( '0' === $time_of_scan[0]->option_value ) {
		$time_of_scan = '0 Minutes';
	} else {
		$time_of_scan = $time_of_scan[0]->option_value;
		if ( '' === $time_of_scan ) {
			$time_of_scan = '0 Seconds';
		}
	}

	if ( $time_of_empty[0]->option_value == '0' ) {
		$time_of_empty = '0 Minutes';
	} else {
		$time_of_empty = $time_of_empty[0]->option_value;
		if ( '' === $time_of_empty ) {
			$time_of_empty = '0 Seconds';
		}
	}

	$options_table = $wpdb->prefix . 'spellcheck_options';

	$scan_type  = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='last_scan_type'" );
	$empty_type = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='last_empty_type'" );

	$post_status = array( 'publish', 'draft' );

	$post_count  = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type='post' AND (post_status='draft' OR post_status='publish')" );
	$page_count  = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type='page' AND (post_status='draft' OR post_status='publish')" );
	$media_count = $total_media;

	$page_scan  = $wpdb->Get_results( "SELECT option_value FROM $options_table WHERE option_name='page_count';" );
	$post_scan  = $wpdb->Get_results( "SELECT option_value FROM $options_table WHERE option_name='post_count';" );
	$media_scan = $wpdb->Get_results( "SELECT option_value FROM $options_table WHERE option_name='media_count';" );

	$empty_page_scan  = $wpdb->Get_results( "SELECT option_value FROM $options_table WHERE option_name='empty_page_count';" );
	$empty_post_scan  = $wpdb->Get_results( "SELECT option_value FROM $options_table WHERE option_name='empty_post_count';" );
	$empty_media_scan = $wpdb->Get_results( "SELECT option_value FROM $options_table WHERE option_name='empty_media_count';" );
	$options_list     = $wpdb->Get_results( "SELECT option_value FROM $options_table;" );

	$empty_post_scan_count = $empty_post_scan[0]->option_value;
	if ( $empty_post_scan_count > $post_count ) {
		$empty_post_scan_count = $post_count;
	}

	$total_words = $options_list[22]->option_value;

	wp_enqueue_script( 'results-nav', plugin_dir_url( __FILE__ ) . 'results-nav.js' );

	// Localize empty-results-ui.js with PHP variables
	// Note: $check_scan is already computed above at line 262
	wp_localize_script(
		'empty-results-ui',
		'wpsc_empty_results_ui',
		array(
			'check_scan'         => $check_scan ? true : false,
			'admin_ajax_url'     => admin_url( WPSC_ADMIN_AJAX ),
			'plugin_url'         => plugin_dir_url( __FILE__ ),
			'loading_spinner_url' => function_exists( 'wpsc_get_loading_spinner_url' ) ? wpsc_get_loading_spinner_url() : plugin_dir_url( __FILE__ ) . 'images/loading.svg',
			'auto_click_enabled' => ( isset( $_GET['action'] ) && 'check' === $_GET['action'] && isset( $_GET['submit-empty'] ) && 'Entire Site' === $_GET['submit-empty'] ),
			'nonces'             => array(
				'wpsc_start_scan_empty'      => wp_create_nonce( 'wpsc_start_scan_empty' ),
				'wpsc_empty_scan'            => wp_create_nonce( 'wpsc_empty_scan' ),
				'wpsc_finish_empty_scan'     => wp_create_nonce( 'wpsc_finish_empty_scan' ),
				'wpsc_display_results_empty' => wp_create_nonce( 'wpsc_display_results_empty' ),
				'wpsc_get_stats_empty'       => wp_create_nonce( 'wpsc_get_stats_empty' ),
				'wpsc_openai'                => wp_create_nonce( 'wpsc_openai' ),
			),
			'version'            => $wpsc_version,
		)
	);

	// $empty_factor = ();

	$end = time();
	// echo "debug - Finalization Code Finished(about to render HTML): " . ($end - $start) . " Seconds<br />";

	?>
	<?php // wpscx_show_feature_window(); ?>
	<?php // wpscx_check_install_notice(); ?>
	<!-- Inline script block #2 removed - now handled in empty-results-ui.js -->
	<div id="wpsc-mass-edit-confirm" title="Are you sure?" style="display: none;">
		<p>This will update all areas of your website that you have selected WP Spell Check to scan. Are you sure you wish
			to proceed with the changes?</p>
	</div>
	<div class="wrap wpsc-table wpsc-page-seo">
		<h2><a href="admin.php?page=wp-spellcheck-seo.php"><img
					src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../images/logo.png'; ?>"
					alt="WP Spell Check" /></a> <span class="wpsc-seo-page-title"> - SEO Empty Field
				Results</span></h2>
		<h4>Generate SEO titles and descriptions with OpenAI</h4>
		<div class="wpsc-seo-instructions">To allow the generation of SEO titles and descriptions:<br />
			1. Login to you OpenAI account and go to <a href="https://platform.openai.com/account/billing/overview"
				target="_blank">https://platform.openai.com/account/billing/overview</a><br />
			2. Click on Payment Methods and add your payment info<br />
			3. Go to <a href="https://platform.openai.com/settings/">https://platform.openai.com/settings/</a> and create a
			project<br />
			4. Go to <a href="https://platform.openai.com/account/api-keys"
				target="_blank">https://platform.openai.com/account/api-keys</a> and click on "+Create new Secret key"<br />
			5. Copy/paste your OpenAI API Key below and click on "Save Key"<br /><br />
			<span>For instructions on how to get and set up your OpenAI API Key, <a
					href="https://www.wpspellcheck.com/open-ai-setup/" target="_blank">click here</a></span>
			<form action="admin.php?page=wp-spellcheck-seo.php" method="post" name="openAIKey"
				enctype="multipart/form-data" style="margin: 20px 0;">
				<?php wp_nonce_field( 'wpsc_update_openai' ); ?>
				<div class="wpsc-openai-key-row">
					<strong style="margin-right: 5px;">OpenAI API Key</strong>
					<input type="text" name="apiKey" value="<?php echo esc_attr( $openAIKey ); ?>" style="flex: 1; min-width: 300px; max-width: 500px;" />
					<span class="wpsc-mouseover-text-emfeature-seo"><span class="wpsc-pro-feature-header">
						This is a Pro Feature</span><span class="wpsc-pro-feature-content">To generate
						SEO Titles and Descriptions with OpenAI, <a href="https://www.wpspellcheck.com/pricing/"
							target="_blank">Click Here</a> to upgrade to WP Spell Check Pro.</span></span>
					<span class="
					<?php
					if ( ! $wpscx_ent_included ) {
						echo 'wpsc-mouseover-emfeature-seo';
					}
					?>
					"><input type="submit" class="wpsc-save-key-button button button-primary" value="Save Key" name="submit"
							<?php
							if ( ! $wpscx_ent_included ) {
								echo 'disabled';
							}
							?>
							/></span>
				</div>
			</form>
		</div>
		<div class="wpsc-scan-nav-bar">
			<a href="<?php echo esc_url( admin_url() ); ?>admin.php?page=wp-spellcheck.php" id="wpsc-scan-results"
				name="wpsc-scan-results">Spelling Errors</a>
			<a href="<?php echo esc_url( admin_url() ); ?>admin.php?page=wp-spellcheck-grammar.php" id="wpsc-grammar"
				name="wpsc-grammar">Grammar</a>
			<a href="#empty-fields" id="wpsc-empty-fields" class="selected" name="wpsc-empty-fields">SEO</a>
			<a href="<?php echo esc_url( admin_url() ); ?>admin.php?page=wp-spellcheck-html.php" id="wpsc-grammar"
				name="wpsc-grammar">Broken Code</a>
		</div>
		<div id="wpsc-empty-fields-tab">
			<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method='GET'>
				<div class="wpsc-scan-container">
					<div class="wpsc-scan-buttons">
						<h3>This function finds all the fields that have been left empty so you
							can add content to improve your SEO</h3>
						<h3 class="scan-label">Scan:</h3>
						<p class="submit"><input
								type="submit" name="submit-empty" id="submit"
								class="button button-primary wpscScan wpscScanSite" value="Entire Site" 
								<?php
								if ( 'false' === $checked_pages ) {
									echo 'disabled';
								}
								?>
								></p>
						<span>
							<span class="wpsc-mouseover-text-emfeature"><span class="wpsc-pro-feature-header">
									This is a Pro Feature</span><span class="wpsc-pro-feature-content">To scan all
									parts of your website, <a href="https://www.wpspellcheck.com/pricing/"
										target="_blank">Click Here</a> to upgrade to WP Spell Check Pro.</span></span>
							<p class="submit 
				<?php
				if ( ! $wpscx_ent_included ) {
					echo 'wpsc-mouseover-emfeature';
				}
				?>
				"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Page SEO"
									<?php
									if ( 'false' === $check_page_seo_empty || ! $wpscx_ent_included ) {
										echo 'disabled';
									}
									?>
									></p>
							<p class="submit 
				<?php
				if ( ! $wpscx_ent_included ) {
					echo 'wpsc-mouseover-emfeature';
				}
				?>
				"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Post SEO"
									<?php
									if ( 'false' === $check_post_seo_empty || ! $wpscx_ent_included ) {
										echo 'disabled';
									}
									?>
									></p>
							<p class="submit 
				<?php
				if ( ! $wpscx_ent_included ) {
					echo 'wpsc-mouseover-emfeature';
				}
				?>
				"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan"
									value="Media Files SEO" 
									<?php
									if ( 'false' === $check_media_seo_empty || ! $wpscx_ent_included ) {
										echo 'disabled';
									}
									?>
									></p>
							<p class="submit 
				<?php
				if ( ! $wpscx_ent_included ) {
					echo 'wpsc-mouseover-emfeature';
				}
				?>
				"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Media Files"
									<?php
									if ( 'false' === $check_media_empty || ! $wpscx_ent_included ) {
										echo 'disabled';
									}
									?>
									></p>
						</span>
						<p class="submit"><input type="submit" name="submit-empty" id="submit"
								class="button button-primary wpscScan" value="Authors" 
								<?php
								if ( 'false' === $check_authors_empty ) {
									echo 'disabled';
								}
								?>
								></p>
						<span>
							<span class="wpsc-mouseover-text-emfeature-2"><span class="wpsc-pro-feature-header">
									This is a Pro Feature</span><span class="wpsc-pro-feature-content">To scan all
									parts of your website, <a href="https://www.wpspellcheck.com/pricing/"
										target="_blank">Click Here</a> to upgrade to WP Spell Check Pro.</span></span>
							<p class="submit 
				<?php
				if ( ! $wpscx_ent_included ) {
					echo 'wpsc-mouseover-emfeature-2';
				}
				?>
				"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Menus" 
				<?php
				if ( 'false' === $check_menu_empty || ! $wpscx_ent_included ) {
					echo 'disabled';
				}
				?>
				></p>
						</span>
						<p class="submit"><input type="submit" name="submit-empty" id="submit"
								class="button button-primary wpscScan" value="Page Titles" 
								<?php
								if ( 'false' === $check_page_titles_empty ) {
									echo 'disabled';
								}
								?>
								></p>
						<p class="submit"><input type="submit" name="submit-empty" id="submit"
								class="button button-primary wpscScan" value="Post Titles" 
								<?php
								if ( 'false' === $check_post_titles_empty ) {
									echo 'disabled';
								}
								?>
								></p>
						<span>
							<span class="wpsc-mouseover-text-emfeature-3"><span class="wpsc-pro-feature-header">
									This is a Pro Feature</span><span class="wpsc-pro-feature-content">To scan all
									parts of your website, <a href="https://www.wpspellcheck.com/pricing/"
										target="_blank">Click Here</a> to upgrade to WP Spell Check Pro.</span></span>
							<p class="submit 
				<?php
				if ( ! $wpscx_ent_included ) {
					echo 'wpsc-mouseover-emfeature-3';
				}
				?>
				"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan"
									value="Tag Descriptions" 
									<?php
									if ( 'false' === $check_tag_desc_empty || ! $wpscx_ent_included ) {
										echo 'disabled';
									}
									?>
									></p>
							<p class="submit 
				<?php
				if ( ! $wpscx_ent_included ) {
					echo 'wpsc-mouseover-emfeature-3';
				}
				?>
				"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan"
									value="Category Descriptions" 
									<?php
									if ( 'false' === $check_cat_desc_empty || ! $wpscx_ent_included ) {
										echo 'disabled';
									}
									?>
									></p>
							<?php
							if ( is_plugin_active( 'woocommerce/woocommerce.php' ) || is_plugin_active( 'wp-e-commerce/wp-shopping-cart.php' ) ) {
								?>
								<p class="submit 
									<?php
									if ( ! $wpscx_ent_included ) {
										echo 'wpsc-mouseover-emfeature-3';
									}
									?>
									"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan"
										value="WooCommerce Products" 
										<?php
										if ( 'false' === $check_ecommerce_empty || ! $wpscx_ent_included ) {
											echo 'disabled';
										}
										?>
										></p><?php } ?>
						</span>
						<p class="submit wpsc-action-buttons"><span class="wpsc-action-dash"> -
							</span><img
								src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../images/clear-results.png'; ?>"
								alt="Clear Error Results"
								class="wpsc-action-icon clear-results" /><input type="submit"
								name="submit-empty" id="submit" class="wpsc-clear-results-button button button-primary" value="Clear Results"></p>
						<p class="submit wpsc-action-buttons"><img
								src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../images/see-results.png'; ?>"
								alt="See Error Results"
								class="wpsc-action-icon see-results" /><input type="submit"
								name="submit" id="submit" class="wpsc-see-results-button button button-primary"
								value="See Scan Results"></p>
						<p class="submit wpsc-action-buttons"><img
								src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../images/stop-scans.png'; ?>"
								alt="Stop Current Scans"
								class="wpsc-action-icon stop-scans" /><input type="submit"
								name="submit-empty" id="submit" class="wpsc-stop-scans-button button button-primary"
								value="Stop Scans"></p>
						<p class="submit wpsc-action-buttons"><a
								href="/wp-admin/admin.php?page=wp-spellcheck-options.php" target="_blank"><img
									src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../images/options.png'; ?>"
									alt="WP Spell Check Options" title="Options"
									class="wpsc-options-icon" /></a>
						</p>
					</div>
					<div class="wpsc-stats-container">
						<input type="hidden" name="page" value="wp-spellcheck-seo.php">
						<input type="hidden" name="action" value="check">
						<?php
						// Use unique field names to avoid conflicts when multiple nonces are in the same form
						$nonce_stop  = wp_create_nonce( 'wpsc-stop-scans' );
						$nonce_clear = wp_create_nonce( 'wpsc-clear-empty-results' );
						?>
						<input type="hidden" name="_wpnonce_stop_scans" value="<?php echo esc_attr( $nonce_stop ); ?>">
						<input type="hidden" name="_wpnonce_clear_empty_results"
							value="<?php echo esc_attr( $nonce_clear ); ?>">
						<?php echo "<h3 class='sc-message sc-factor'>Website Empty Fields Factor: " . esc_html( $empty_factor ) . '%'; ?>
						<?php echo "<h3 class='sc-message sc-time'>Last scan took " . esc_html( $time_of_empty ) . '</h3>'; ?>
						<?php echo "<h3 class='sc-message' id='wpscScanMessage'>" . esc_html( $empty_scan_message ) . '</h3><br />'; ?>
						<span class="empty-eps-message
						<?php
						if ( $wpscx_ent_included ) {
							echo ' hidden';
						}
						?>
						">
							<?php
							if ( ! $wpscx_ent_included ) {
								if ( $empty_words > 0 ) {
									echo "<h3 class='sc-message error'><strong>Pro Version: </strong>" . esc_html( $empty_words ) . " SEO Empty Fields were found on your website. <a href='https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgradeSEO&utm_medium=seo_scan&utm_content=" . esc_attr( $wpsc_version ) . "' target='_blank'>Upgrade today</a> to boost your SEO and get <strong>AI suggestions for Page/post SEO</strong></h3>";
								} else {
									// echo "<h3 class='sc-message error'><a href='https://www.wpspellcheck.com/product-tour/' target='_blank'>Upgrade</a> to scan all parts of your website.</h3>";
								}
							}
							?>
						</span>
					</div>
				</div>
			</form>
			<?php include 'sidebar.php'; ?>
			<?php if ( ( '' !== $message || isset( $ignore_message[0] ) || isset( $dict_message[0] ) ) && 'empty' === $_GET['wpsc-scan-tab'] ) { ?>
				<div class="wpsc-mesage-container">
					<?php
					if ( '' !== $message ) {
						echo '<div class="wpsc-notice-success"><span class="wpsc-message">' . esc_html( $message ) . '</span></div>';
					}
					if ( isset( $ignore_message[0] ) && '' !== $ignore_message[0] ) {
						echo '<div class="wpsc-notice-success"><span class="wpsc-message">' . esc_html( $ignore_message[0] ) . '</span></div>';
					}
					if ( isset( $dict_message[0] ) && '' !== $dict_message[0] ) {
						echo '<div class="wpsc-notice-success"><span class="wpsc-message">' . esc_html( $dict_message[0] ) . '</span></div>';
					}
					?>
				</div>
			<?php } ?>
			<form id="words-list" method="get">
				<p class="search-box top">
					<label class="screen-reader-text" for="search_id-search-input">search:</label>
					<input type="search" id="search_id-search-input-top" name="s-top" value=""
						placeholder="Search for Page Names">
					<input type="submit" id="search-submit-top" class="button" value="search">
				</p>
				<?php // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Output escaped with esc_attr() for HTML attribute. ?>
				<input type="hidden" name="page" value="<?php echo esc_attr( wp_unslash( $_REQUEST['page'] ) ); ?>" />
				<input type="hidden" name="wpsc-scan-tab" value="empty" />
				<?php
				// Single nonce field for both ignore-word and word_update actions in this form
				// Using wpsc-update-empty-word as the primary action nonce
				wp_nonce_field( 'wpsc-update-empty-word' );
				?>
				<input name="wpsc-edit-update-button" class="wpsc-edit-update-button top empty-tab" type="submit"
					value="Save all Changes" class="button button-primary" />
				<div id="wpsc-table-results">
					<?php $empty_table->display(); ?>
				</div>
				<?php $end_empty = time(); ?>
				<div class="wpsc-words-list-footer">
					<p class="search-box bottom">
						<label class="screen-reader-text" for="search_id-search-input">search:</label>
						<input type="search" id="search_id-search-input" name="s" value="" placeholder="Search for Page Names">
						<input type="submit" id="search-submit" class="button" value="search">
					</p>
					<input name="wpsc-edit-update-buttom" class="wpsc-edit-update-button bottom empty-tab" type="submit"
						value="Save all Changes" class="button button-primary" />
				</div>
			</form>

			<div class="wpsc-stats-summary">
				<?php echo "<h3 class='sc-message sc-type'>SEO problems found on <span>" . esc_html( $empty_type[0]->option_value ) . '</span>: ' . esc_html( $empty_count ) . '</h3>'; ?>
				<?php
				echo "<h3 class='sc-message sc-page'>Pages scanned: " . esc_html( $empty_page_scan[0]->option_value ) . '/' . esc_html( $page_count );
				if ( ! $wpscx_ent_included && sizeof( (array) $page_count ) >= 500 ) {
					?>
					<span class='wpsc-mouseover-button-page'>?<span
							class="wpsc-mouseover-text-page"><span class="wpsc-pro-feature-header">
								This is a Pro Feature</span><span class="wpsc-pro-feature-content">Our free version
								scans up to 25 pages. To scan your entire website, <a
									href="https://www.wpspellcheck.com/pricing/" target="_blank">Click Here</a> to upgrade to WP
								Spell Check Pro.</span></span></span>
					<?php
				}
				echo '</h3>';
				?>
				<?php
				echo "<h3 class='sc-message sc-post'>Posts scanned: " . esc_html( $empty_post_scan_count ) . '/' . esc_html( $post_count );
				if ( ! $wpscx_ent_included && $post_count >= 500 ) {
					?>
					<span class='wpsc-mouseover-button-post'>?<span
							class="wpsc-mouseover-text-post"><span class="wpsc-pro-feature-header">
								This is a Pro Feature</span><span class="wpsc-pro-feature-content">Our free version
								scans up to 25 posts. To scan your entire website, <a
									href="https://www.wpspellcheck.com/pricing/" target="_blank">Click Here</a> to upgrade to WP
								Spell Check Pro..</span></span></span>
					<?php
				}
				echo '</h3>';
				?>
				<?php
				if ( $wpscx_ent_included ) {
					echo "<h3 class='sc-message sc-media'>Media files scanned: " . esc_html( $empty_media_scan[0]->option_value ) . '/' . esc_html( $media_count ) . '</h3>';
				}
				?>
			</div>
		</div>
	</div>
	<!-- Quick Edit Clone Field -->
	<table style="display: none;" role="presentation">
		<tbody>
			<tr id="wpsc-editor-row" class="wpsc-editor">
				<td colspan="4">
					<div class="wpsc-edit-content">
						<h4>Edit %Word%</h4>
						<input type="text" size="60" name="word_update[]" value
							class="wpsc-edit-field edit-field">
						<input type="hidden" name="edit_page_name[]" value>
						<input type="hidden" name="edit_page_type[]" value>
						<input type="hidden" name="edit_old_word[]" value>
						<input type="hidden" name="edit_old_word_id[]" value>
					</div>
					<div class="wpsc-buttons">
						<input type="button" class="button-secondary cancel alignleft wpsc-cancel-button" value="Cancel">
						<div class="clear"></div>
					</div>
				</td>
			</tr>
		</tbody>
	</table>

	<!-- Quick Edit Clone Field for SEO Title & Description -->
	<table style="display: none;" role="presentation">
		<tbody>
			<tr id="wpsc-editor-row-seo" class="wpsc-editor">
				<td colspan="4">
					<div class="wpsc-edit-content">
						<h4 class="seo">Edit %TYPE% For %TITLE%</h4>
						<p>%SEOTEXT%</p>
						<input type="text" size="60" name="word_update[]" value
							class="wpsc-edit-field seo edit-field">
						<input type="hidden" name="edit_page_name[]" value>
						<input type="hidden" name="edit_page_type[]" value>
						<input type="hidden" name="edit_old_word[]" value>
						<input type="hidden" name="edit_old_word_id[]" value>
					</div>
					<div class="wpsc-buttons">
						<input type="button" class="button-secondary cancel alignleft wpsc-cancel-button" value="Cancel">
						<input type="button" class="button-secondary alignleft wpsc-generate-seo-button"
							value="Generate SEO with AI">
						<div class="seo-progress"><img
								src="<?php echo esc_url( function_exists( 'wpsc_get_loading_spinner_url' ) ? wpsc_get_loading_spinner_url() : plugin_dir_url( __FILE__ ) . 'images/loading.svg' ); ?>"
								alt="Generating SEO" class="wpsc-loading-spinner" /></div>
						<div class="clear"></div>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
	<!-- Inline script block #3 removed - now handled in empty-results-ui.js -->
	<?php
}




?>