<?php
function wpscx_admin_empty_render() {

		wp_enqueue_style( 'wpsc-admin-styles', plugin_dir_url( __DIR__ ) . 'css/admin-styles.css' );
		wp_enqueue_style( 'wpsc-sidebar', plugin_dir_url( __DIR__ ) . 'css/wpsc-sidebar.css' );
		wp_enqueue_style( 'wpsc-jquery-ui', plugin_dir_url( __DIR__ ) . 'css/jquery-ui.css' );

	$start = time();
	ini_set( 'memory_limit', '8192M' );
	set_time_limit( 600 );
	global $wpdb;
	global $wpscx_ent_included;
	global $wpscx_base_page_max;
		global $wpsc_version;
	$table_name        = $wpdb->prefix . 'spellcheck_words';
	$empty_table       = $wpdb->prefix . 'spellcheck_empty';
	$options_table     = $wpdb->prefix . 'spellcheck_options';
	$post_table        = $wpdb->prefix . 'posts';
	$total_smartslider = 0;
	$total_huge_it     = 0;
        $total_seo_title   = 0;
        $total_seo_desc    = 0;
        $sql_count         = 0;
        $empty_scan_message = '';
        $checked_pages = '';
        $ignore_message = '';
		$utils         = new Wpscx_Results_Utils;
                
                
                
        //Check for updated OpenAI Key and save if needed
        if (isset($_POST['apiKey'])) {
            check_admin_referer( 'wpsc_update_openai' );
            $apiKey = $_POST['apiKey'];
            
            $wpdb->update( $options_table, array( 'option_value' => $apiKey ), array( 'option_name' => 'openAIKey' ) );
        }

		wp_enqueue_script( 'jquery-ui-dialog' );
	wp_enqueue_script( 'admin-js', plugin_dir_url( __FILE__ ) . '../js/feature-request.js' );
	wp_enqueue_script( 'feature-request', plugin_dir_url( __FILE__ ) . '../js/admin-js.js' );
		wp_enqueue_script( 'jquery.contextMenu', plugin_dir_url( __FILE__ ) . '../js/jquery.contextMenu.js' );
	wp_enqueue_script( 'jquery.ui.position', plugin_dir_url( __FILE__ ) . '../js/jquery.ui.position.js' );

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
            $ignore_message = $utils->ignore_word_empty( $_GET['ignore-word'] );
	}

	$max_pages = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name = 'pro_max_pages'" );
	$max_pages = intval( $max_pages[0]->option_value );

	if ( ! $wpscx_ent_included ) {
		$max_pages = $wpscx_base_page_max;
	}

	$message = '';

	if ( isset( $_GET['submit'] ) && 'Stop Scans' === $_GET['submit'] ) {
		$message = 'All current spell check scans have been stopped.';
		wpscx_clear_scan();
	}
	if ( isset( $_GET['submit-empty'] ) && 'Stop Scans' === $_GET['submit-empty'] ) {
		$message = 'All current empty field scans have been stopped.';
		wpscx_clear_empty_scan();
	}

	$settings                = $wpdb->get_results( 'SELECT option_name, option_value FROM ' . $options_table );
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
	$check_ecommerce_empty   = $settings[57]->option_value;
        $openAIKey               = $settings[151]->option_value;

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
			$sql_count++;
			$total_tags = sizeof( get_tags() );
			$sql_count++;
			$total_tag_desc = $total_tags;
			$total_tag_slug = $total_tags;
			$total_cat      = sizeof( get_categories() );
			$sql_count++;
			$total_cat_desc  = $total_cat;
			$total_cat_slug  = $total_cat;
			$total_seo_title = sizeof( (array) $wpdb->get_results( "SELECT * FROM $postmeta_table WHERE meta_key='_yoast_wpseo_title' OR meta_key='_aioseop_title' OR meta_key='_su_title'" ) );
			$sql_count++;
			$total_seo_desc = sizeof( (array) $wpdb->get_results( "SELECT * FROM $postmeta_table WHERE meta_key='_yoast_wpseo_metadesc' OR meta_key='_aioseop_description' OR meta_key='_su_description'" ) );
			$sql_count++;

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
			$sql_count++;
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
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( WPSC_ADMIN_AJAX ) ) );
		sleep( 1 );
	}
	$check_empty = wpscx_check_empty_scan_progress();
	if ( 'noscript' !== $check_empty && isset( $_GET['wpsc-script'] ) ) {
		wp_enqueue_script( 'emptyresults-ajax', plugin_dir_url( __FILE__ ) . '/empty-ajax.js', array( 'jquery' ) );
		wp_localize_script( 'emptyresults-ajax', 'ajax_object', array( 'ajax_url' => admin_url( WPSC_ADMIN_AJAX ) ) );
		sleep( 1 );
	}

	$estimated_time = wpscx_time_elapsed( $estimated_time );

	$end = time();
	if ( 'check' === $_GET['action'] && 'Entire Site' === $_GET['submit-empty'] ) {
		?>
				<script type="text/javascript">
					jQuery(document).ready(function() {
						window.setTimeout(function() { 
							//console.log("Start Entire Site Scan");
							jQuery('.wpscScanSite').click();
						}, 1000);
					});
				</script>
		<?php
	}

	if ( isset( $_GET['action'] ) && isset( $_GET['submit-empty'] ) && 'check' === $_GET['action'] && 'Clear Results' === $_GET['submit-empty'] ) {
		$message = 'All empty field results have been cleared';
		wpscx_clear_empty_results( 'full' );
	}

	if ( isset( $_GET['word_update'] ) ) {
		$message = $utils->update_empty_admin( $_GET['word_update'], $_GET['edit_page_name'], $_GET['edit_page_type'], $_GET['edit_old_word_id'] );
	}

	$end = time();
	//echo "debug - Checking For Scan Buttons Pressed Finished: " . ($end - $start) . " Seconds<br />";

	$word_count  = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE ignore_word='false'" );
	$empty_count = $wpdb->get_var( "SELECT COUNT(*) FROM $empty_table WHERE ignore_word='false'" );

	$empty_table = new Sc_Table();
	$empty_table->prepare_empty_items();

	$path = plugin_dir_path( __FILE__ ) . '../premium-functions.php';

	$end = time();
	//echo "debug - Results Tables Prepared: " . ($end - $start) . " Seconds<br />";

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
			$scan_site++;
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
		$scan_message = '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> A scan is currently in progress for <span class="sc-message" style="color: rgb(0, 150, 255); font-weight: bold;">' . $last_type[0]->option_value . '</span>. Estimated time for completion is ' . $estimated_time . ' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
	} elseif ( 'error' === $scanning[0]->option_value && '' === $scan_message && ! $scan_progress ) {
		$scan_message = "<span style='color:red;'>No scan currently running. The previous scan was unable to finish scanning</style>";
	} elseif ( '' === $scan_message ) {
		$scan_message = 'No scan currently running';
	}

	$empty_scan_progress = wpscx_check_empty_scan_progress();
	if ( '' === $empty_scan_progress && $empty_scan_message && 'noscript' !== $_GET['wpsc-script'] ) {
		$last_type          = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='last_empty_type'" );
		$empty_scan_message = '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> A scan is currently in progress for <span class="sc-message" style="color: rgb(0, 150, 255); font-weight: bold;">' . $last_type[0]->option_value . '</span>. Estimated time for completion is ' . $estimated_time . ' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
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

	//$empty_factor = ();

	$end = time();
	//echo "debug - Finalization Code Finished(about to render HTML): " . ($end - $start) . " Seconds<br />";

	?>
		<?php wpscx_show_feature_window(); ?>
		<?php //wpscx_check_install_notice(); ?>
		
	<style>input[type=submit]{border-radius:32px!important; box-shadow: none!important; text-shadow: none!important; border: none!important;}.search-box input[type=submit] { color: white; background-color: #00A0D2; border-color: #0073AA; } #cb-select-all-1,#cb-select-all-2 { display: none; } td.word { font-size: 15px; } p.submit { display: inline-block; margin-left: 8px; } h3.sc-message { width: 49%; display: inline-block; padding-left: 8px; font-weight: normal; } .wpsc-mouseover-text-refresh { color: black; font-size: 12px; width: 225px; display: inline-block; position: absolute; margin: -13px 0 0 -290px; padding: 5px 15px 5px 30px; border: 1px solid #008200; border-radius: 50px; box-shadow: #008200 1px 1px 1px; font-weight: bold; opacity: 0; background: white; z-index: -100; } .wpsc-row .row-actions, .wpsc-row .row-actions *{ visibility: visible!important; left: 0!important; } #current-page-selector { width: 12%; } .hidden { display: none; } .wpsc-scan-nav-bar { border-bottom: 1px solid #BBB; } .wpsc-scan-nav-bar a { text-decoration: none; margin: 5px 5px -1px 5px; padding: 8px; border: 1px solid #BBB; display: inline-block; font-weight: bold; color: black; font-size: 14px; } .wpsc-scan-nav-bar a.selected { border-bottom: 1px solid white; background: white; } #wpsc-empty-fields-tab .button-primary { background: #73019a; border-color: #51006E; text-shadow: 1px 1px #51006d; box-shadow: 0 1px 0 #51006d; } #wpsc-empty-fields-tab .button-primary:hover { background: #9100c3 } #wpsc-empty-fields-tab .button-primary:active { background: #51006d; }.wpsc-scan-buttons input#submit:active { margin-top: -7px; } #wpsc-empty-fields-tab span.wpsc-bulk { display: none; } span.wpsc-bulk { color: black; }
	#wpsc-table-results table { border-radius: 5px; box-shadow: 0px 0px 10px 0px rgb(0 0 0 / 50%); }
		.wpsc-mouseover-text-page,.wpsc-mouseover-text-post, .wpsc-mouseover-text-emfeature, .wpsc-mouseover-text-emfeature-seo, .wpsc-mouseover-text-emfeature-2, .wpsc-mouseover-text-emfeature-3 { color: black; font-size: 12px; width: 225px; display: inline-block; position: absolute; margin: 0px; padding: 0px 0px 15px 0px; border: 2px solid #008200; border-radius: 7px; opacity: 0; background: white; z-index: -100; box-shadow: 2px 2px 10px 3px rgb(0 0 0 / 75%); font-weight: bold; max-width: 205px; }
		.wpsc-button-greyout, .wpsc-button-greyout:hover { background: darkgrey!important }
                input.edit-seo-title, input.edit-seo-desc { border-width: 3px!important; border-color: orange; }
	</style>
	<script>
			var scan_in_progress = false;
						<?php if ( $check_scan ) { ?>
							scan_in_progress = true;
						<?php } else { ?>
							scan_in_progress = false;
						<?php } ?>
						var scanStartTime;
							
		jQuery(document).ready(function() {
			var should_submit = false;
			var shown_box = false;
			
			jQuery(".wpsc-edit-update-button").click( function(event) {
				/*if (!should_submit) event.preventDefault();
				jQuery('.wpsc-mass-edit-chk').each(function() {
					if (jQuery(this).is(":checked") && shown_box == false) {
						shown_box = true;
						jQuery( "#wpsc-mass-edit-confirm" ).dialog({
						  resizable: false,
						  height: "auto",
						  width: 400,
						  modal: true,
						  buttons: {
							"Yes": function() {
							  jQuery( this ).dialog( "close" );
							  should_submit = true;
							  jQuery("#wpsc-edit-update-button-hidden").trigger('click');
							},
							Cancel: function() {
							  jQuery( this ).dialog( "close" );
							}
						  }
						});
				}
				});
				if (shown_box == false) {
					should_submit = true;
                                        jQuery("#wpsc-edit-update-button").trigger('click');
					//jQuery("#wpsc-edit-update-button-hidden").trigger('click');
				}*/
			  } );
		});
				
				function wpscex_recheck_scan_temp() {
						jQuery.ajax({
								url: ajax_object,
								type: "POST",
								data: {
										action: 'emptyresults_sc',
								},
								dataType: 'html',
								success: function(response) {
										//console.log(response);
										if (response == 'true') { window.setInterval(wpscex_recheck_scan_temp(), 500 );}
										else { wpscex_finish_scan_temp(); }
								}
						});
				}
				
				function wpscex_finish_scan_temp() {
						var scanTime = new Date();
						var scanEndTime = scanTime.getTime();
						var scanFinal = ( scanEndTime - scanStartTime) / 1000;
						//console.log("Scan Time(Pre Scan finished):" + scanFinal);
						jQuery.ajax({
								url: ajax_object,
								type: "POST",
								data: {
										action: 'wpscx_display_results_empty',
								},
								dataType: 'html',
								success: function(response) {
										var scanTime = new Date();
										var scanEndTime = scanTime.getTime();
										var scanFinal = ( scanEndTime - scanStartTime) / 1000;
										//console.log("Scan Time:" + scanFinal);
										jQuery('.wpscScan').removeClass('wpsc-button-greyout'); //Remove button greyout
										scan_in_progress = false;
										jQuery('#wpsc-table-results').html(response.replace("null",''));
										wpscx_connect_listeners();
										jQuery('#wpscScanMessage').html("The scan has finished");

										wpscex_show_stats(scanFinal);
								}
						});
				}
				
				function wpscex_show_stats(x) {
					 jQuery.ajax({
						url: ajax_object,
						type: "POST",
						data: {
								action: 'wpscx_get_stats_empty',
								'scantime': x,
						},
						dataType: 'json',
						success: function(response) {
							//console.log(response);
							jQuery('.sc-factor').html("Website Empty Fields Factor:" + response.emptyFactor + "%");
							jQuery('.sc-type').html("Errors found on <span style='color: rgb(0, 150, 255); font-weight: bold;'>" + response.scanType + ": " + response.totalErrors);
							if (Number(response.pageCount) >= Number(response.totalPages)) { jQuery('.sc-post').html("Posts scanned: " + response.totalPosts + "/" + response.totalPosts); } else { jQuery('.sc-post').html("Posts scanned: " + response.postCount + "/" + response.totalPosts); }
							if (Number(response.pageCount) >= Number(response.totalPages)) { jQuery('.sc-page').html("Pages scanned: " + response.totalPages + "/" + response.totalPages); } else { jQuery('.sc-page').html("Pages scanned: " + response.pageCount + "/" + response.totalPages); }
							if (Number(response.mediaCount) >= Number(response.totalMedia)) { jQuery('.sc-media').html("Media Files scanned: " + response.totalMedia + "/" + response.totalMedia); } else { jQuery('.sc-media').html("Media Files scanned: " + response.mediaCount + "/" + response.totalMedia); }
							if (response.emptyEPS > 0) { jQuery('.empty-eps-message').html("<h3 class='sc-message' style='color: rgb(225, 0, 0);'><strong>Pro Version: </strong>" + response.emptyEPS + " SEO Empty Fields were found on your website. <a href='https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgradeSEO&utm_medium=seo_scan&utm_content=9.21' target='_blank'>Upgrade today</a> to boost your SEO and get <strong>AI suggestions for Page/post SEO</strong></h3>"); }
							jQuery('.sc-time').html("Last scan took " + response.scanTime);
							jQuery('.next-page').click(function(e) {
								e.preventDefault();
								window.location.href = "?page=wp-spellcheck-seo.php&paged=2";
							});
                                                        var last_page = parseInt(response.totalErrors / 20) + 1
                                                        jQuery('.last-page').click(function(e) {
								e.preventDefault();
								window.location.href = "?page=wp-spellcheck-seo.php&paged=" + last_page;
							});
						},
						error: function(xhr, status, thrownError) {
							//console.log(thrownError);
						}
					});
				}
	</script>
<div id="wpsc-mass-edit-confirm" title="Are you sure?" style="display: none;">
  <p>This will update all areas of your website that you have selected WP Spell Check to scan. Are you sure you wish to proceed with the changes?</p>
</div>
	<div class="wrap wpsc-table">
		<h2><a href="admin.php?page=wp-spellcheck-seo.php"><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../images/logo.png'; ?>" alt="WP Spell Check" /></a> <span style="position: relative; top: -8px;"> - SEO Empty Field Results</span></h2>
                <h4>Generate SEO titles and descriptions with OpenAI</h4>
                <div style="margin-bottom: 15px;">To allow the generation of SEO titles and descriptions:<br/>
                    1. Login to you OpenAI account and go to <a href="https://platform.openai.com/account/billing/overview" target="_blank">https://platform.openai.com/account/billing/overview</a><br/>
                    2. Click on Payment Methods and add your payment info<br/>
                    3. Go to <a href="https://platform.openai.com/settings/">https://platform.openai.com/settings/</a> and create a project<br/>
                    4. Go to <a href="https://platform.openai.com/account/api-keys" target="_blank">https://platform.openai.com/account/api-keys</a> and click on "+Create new Secret key"<br/>
                    5. Copy/paste your OpenAI API Key below and click on "Save Key"<br/><br/>
                    <span style="display: block;">For instructions on how to get and set up your OpenAI API Key, <a href="https://www.wpspellcheck.com/open-ai-setup/" target="_blank">click here</a></span>
                    <form action="admin.php?page=wp-spellcheck-seo.php" method="post" name="openAIKey" enctype="multipart/form-data">
                        <?php wp_nonce_field( 'wpsc_update_openai' ); ?>
                        <table>
                            <tr><td><strong>OpenAI API Key</strong></td><td><input type="text" name="apiKey" value="<?php echo $openAIKey; ?>" /><span class="wpsc-mouseover-text-emfeature-seo"><span style="display: block;text-align: center;font-size: 14px;padding: 10px 0 10px 0;background-color: #2271b1;color: white;margin-bottom: 8px;"> This is a Pro Feature</span><span style="padding: 0 10px; display: block;">To generate SEO Titles and Descriptions with OpenAI,  <a href="https://www.wpspellcheck.com/pricing/" target="_blank">Click Here</a> to upgrade to WP Spell Check Pro.</span></span><p style="display: inline-block;" class="<?php if ( ! $wpscx_ent_included ) echo 'wpsc-mouseover-emfeature-seo'; ?>"><input type="submit" style="margin-left: 10px;" value="Save Key" name="submit" class="button button-primary" <?php if (! $wpscx_ent_included ) { echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";} ?> /</p></td></tr>
                        </table>
                    </form>
                </div>
			<div class="wpsc-scan-nav-bar">
				<a href="<?php echo esc_url( admin_url() ); ?>admin.php?page=wp-spellcheck.php" id="wpsc-scan-results" name="wpsc-scan-results">Spelling Errors</a>
				<a href="<?php echo esc_url( admin_url() ); ?>admin.php?page=wp-spellcheck-grammar.php" id="wpsc-grammar" name="wpsc-grammar">Grammar</a>
				<a href="#empty-fields" id="wpsc-empty-fields" class="selected" name="wpsc-empty-fields">SEO</a>
				<a href="<?php echo esc_url( admin_url() ); ?>admin.php?page=wp-spellcheck-html.php" id="wpsc-grammar" name="wpsc-grammar">Broken Code</a>
			</div>
			<div id="wpsc-empty-fields-tab">
			<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method='GET'>
							<div style="border-radius: 5px; box-shadow: 0px 0px 10px 0px rgb(0 0 0 / 50%); background: white;">
				<div class="wpsc-scan-buttons" style="padding-left: 8px; padding-top: 5px;">
				<h3 style="margin-bottom: 0px;">This function finds all the fields that have been left empty so you can add content to improve your SEO</h3>
				<h3 style="display: inline-block;">Scan:</h3>
				<p class="submit"><input style="background-color: #ffb01f; border-color: #ffb01f; box-shadow: 0px 1px 0px #ffb01f; text-shadow: 1px 1px 1px #ffb01f; font-weight: bold;" type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan wpscScanSite" value="Entire Site" 
				<?php
				if ( 'false' === $checked_pages ) {
					echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
				></p>
								<span>
									<span class="wpsc-mouseover-text-emfeature"><span style="display: block;text-align: center;font-size: 14px;padding: 10px 0 10px 0;background-color: #2271b1;color: white;margin-bottom: 8px;"> This is a Pro Feature</span><span style="padding: 0 10px; display: block;">To scan all parts of your website,  <a href="https://www.wpspellcheck.com/pricing/" target="_blank">Click Here</a> to upgrade to WP Spell Check Pro.</span></span>
				<p class="submit 
				<?php
				if ( ! $wpscx_ent_included ) {
					echo 'wpsc-mouseover-emfeature';}
				?>
				"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Page SEO" 
				<?php
				if ( 'false' === $check_page_seo_empty || ! $wpscx_ent_included ) {
								echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
></p>
				<p class="submit 
				<?php
				if ( ! $wpscx_ent_included ) {
					echo 'wpsc-mouseover-emfeature';}
				?>
				"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Post SEO" 
				<?php
				if ( 'false' === $check_post_seo_empty || ! $wpscx_ent_included ) {
								echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
></p>
				<p class="submit 
				<?php
				if ( ! $wpscx_ent_included ) {
					echo 'wpsc-mouseover-emfeature';}
				?>
				"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Media Files SEO" 
				<?php
				if ( 'false' === $check_media_seo_empty || ! $wpscx_ent_included ) {
								echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
></p>
				<p class="submit 
				<?php
				if ( ! $wpscx_ent_included ) {
					echo 'wpsc-mouseover-emfeature';}
				?>
				"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Media Files" 
				<?php
				if ( 'false' === $check_media_empty || ! $wpscx_ent_included ) {
								echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
></p>
								</span>
				<p class="submit"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Authors" 
				<?php
				if ( 'false' === $check_authors_empty ) {
					echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
				></p>
								<span>
									<span class="wpsc-mouseover-text-emfeature-2"><span style="display: block;text-align: center;font-size: 14px;padding: 10px 0 10px 0;background-color: #2271b1;color: white;margin-bottom: 8px;"> This is a Pro Feature</span><span style="padding: 0 10px; display: block;">To scan all parts of your website,  <a href="https://www.wpspellcheck.com/pricing/" target="_blank">Click Here</a> to upgrade to WP Spell Check Pro.</span></span>
				<p class="submit 
				<?php
				if ( ! $wpscx_ent_included ) {
					echo 'wpsc-mouseover-emfeature-2';}
				?>
				"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Menus" 
				<?php
				if ( 'false' === $check_menu_empty || ! $wpscx_ent_included ) {
								echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
></p>
								</span>
				<p class="submit"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Page Titles" 
				<?php
				if ( 'false' === $check_page_titles_empty ) {
					echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
				></p>
				<p class="submit"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Post Titles" 
				<?php
				if ( 'false' === $check_post_titles_empty ) {
					echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
				></p>
								<span>
									<span class="wpsc-mouseover-text-emfeature-3"><span style="display: block;text-align: center;font-size: 14px;padding: 10px 0 10px 0;background-color: #2271b1;color: white;margin-bottom: 8px;"> This is a Pro Feature</span><span style="padding: 0 10px; display: block;">To scan all parts of your website,  <a href="https://www.wpspellcheck.com/pricing/" target="_blank">Click Here</a> to upgrade to WP Spell Check Pro.</span></span>
				<p class="submit 
				<?php
				if ( ! $wpscx_ent_included ) {
					echo 'wpsc-mouseover-emfeature-3';}
				?>
				"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Tag Descriptions" 
				<?php
				if ( 'false' === $check_tag_desc_empty || ! $wpscx_ent_included ) {
								echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
></p>
				<p class="submit 
				<?php
				if ( ! $wpscx_ent_included ) {
					echo 'wpsc-mouseover-emfeature-3';}
				?>
				"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Category Descriptions" 
				<?php
				if ( 'false' === $check_cat_desc_empty || ! $wpscx_ent_included ) {
								echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
></p>
								<?php
								if ( is_plugin_active( 'woocommerce/woocommerce.php' ) || is_plugin_active( 'wp-e-commerce/wp-shopping-cart.php' ) ) {
									?>
									<p class="submit 
									<?php
									if ( ! $wpscx_ent_included ) {
										echo 'wpsc-mouseover-emfeature-3';}
									?>
									"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="WooCommerce and WP-eCommerce Products" 
									<?php
									if ( 'false' === $check_ecommerce_empty || ! $wpscx_ent_included ) {
										echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";
									}
									?>
									></p><?php } ?>
								</span>
				<p class="submit" style="margin-left: -11px;"><span style="position: relative; left: 15px;"> - </span><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../images/clear-results.png'; ?>" alt="Clear Error Results" style="width: 20px; position: relative; top: 5px; left: 27px;" /><input type="submit" name="submit-empty" id="submit" style="padding-left: 30px; background-color: red;" class="button button-primary" value="Clear Results"></p>
				<p class="submit" style="margin-left: -11px;"><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../images/see-results.png'; ?>" alt="See Error Results" style="width: 20px; position: relative; top: 5px; left: 26px;" /><input type="submit" name="submit" id="submit" class="button button-primary" style="padding-left: 30px; background-color: red;" value="See Scan Results"></p>
				<p class="submit" style="margin-left: -11px;"><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../images/stop-scans.png'; ?>" alt="Stop Current Scans" style="width: 20px; position: relative; top: 5px; left: 25px;" /><input type="submit" name="submit-empty" id="submit" class="button button-primary" style="padding-left: 30px; background-color: red;" value="Stop Scans"></p>
								<p class="submit" style="margin-left: -11px;"><a href="/wp-admin/admin.php?page=wp-spellcheck-options.php" target="_blank"><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../images/options.png'; ?>" alt="WP Spell Check Options" title="Options" style="width: 30px; position: relative; top: 11px; left: 20px; padding: 0px; border-radius: 25px;" /></a></p>
				</div>
				<div style="padding: 5px; font-size: 12px;">
				<input type="hidden" name="page" value="wp-spellcheck-seo.php">
				<input type="hidden" name="action" value="check">
				<?php echo "<h3 class='sc-message sc-factor'style='color: rgb(115, 1, 154); font-size: 1.4em;'>Website Empty Fields Factor: " . esc_html( $empty_factor ) . '%'; ?>
				<?php echo "<h3 class='sc-message sc-time' style='color: rgb(0, 115, 0);'>Last scan took " . esc_html( $time_of_empty ) . '</h3>'; ?>
				<?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);' id='wpscScanMessage'>" . esc_html( $empty_scan_message ) . '</h3><br />'; ?>
								<span class="empty-eps-message" 
								<?php
								if ( $wpscx_ent_included ) {
									echo 'style="display: none;"';}
								?>
								>
				<?php
				if ( ! $wpscx_ent_included ) {
					if ( $empty_words > 0 ) {
						echo "<h3 class='sc-message' style='color: rgb(225, 0, 0);'><strong>Pro Version: </strong>" . esc_html( $empty_words ) . " SEO Empty Fields were found on your website. <a href='https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgradeSEO&utm_medium=seo_scan&utm_content=" . esc_attr( $wpsc_version ) . "' target='_blank'>Upgrade today</a> to boost your SEO and get <strong>AI suggestions for Page/post SEO</strong></h3>";
					} else {
						//echo "<h3 class='sc-message' style='color: rgb(225, 0, 0);'><a href='https://www.wpspellcheck.com/product-tour/' target='_blank'>Upgrade</a> to scan all parts of your website.</h3>";
					}
				}
				?>
								</span>
				</div>
							</div>
			</form>
			<?php include( 'sidebar.php' ); ?>
			<?php if ( ( '' !== $message || isset( $ignore_message[0] ) || isset(  $dict_message[0] ) ) && 'empty' === $_GET['wpsc-scan-tab'] ) { ?>
				<div style="text-align: center; background-color: white; padding: 5px; margin: 15px 0;" class="wpsc-mesage-container">
					<?php
					if ( '' !== $message ) {
						echo "<div class='wpsc-message' style='width: 74%; font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . esc_html( $message ) . '</div>';}
					?>
					<?php
					if ( isset( $ignore_message[0] ) && '' !== $ignore_message[0] ) {
						echo "<div class='wpsc-message' style='width: 74%; font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . esc_html( $ignore_message[0] ) . '</div>';}
					?>
					<?php
					if ( isset(  $dict_message[0] ) && '' !== $dict_message[0] ) {
						echo "<div class='wpsc-message' style='width: 74%; font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . esc_html( $dict_message[0] ) . '</div>';}
					?>
				</div>
				<?php } ?>
			<form id="words-list" method="get" style="width: 75%; float: left; margin-top: 10px; margin-bottom: 10px;">
				<p class="search-box" style="position: relative; margin-top: 8px;">
					<label class="screen-reader-text" for="search_id-search-input">search:</label>
					<input type="search" id="search_id-search-input-top" name="s-top" value="" placeholder="Search for Page Names">
					<input type="submit" id="search-submit-top" class="button" value="search">
				</p>
				<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
				<input type="hidden" name="wpsc-scan-tab" value="empty" />
				<input name="wpsc-edit-update-button" class="wpsc-edit-update-button empty-tab" type="submit" value="Save all Changes" class="button button-primary" style="width: 16%; padding-top: 5px; padding-bottom: 5px; margin-left: 32.5%; display: block; background: #008200; border-color: #005200; color: white; font-weight: bold; position: absolute; margin-top: 7px;"/>
				<div id="wpsc-table-results">
									<?php $empty_table->display(); ?>
								</div>
				<?php $end_empty = time(); ?>
				<p class="search-box" style="margin-top: -3em;">
					<label class="screen-reader-text" for="search_id-search-input">search:</label>
					<input type="search" id="search_id-search-input" name="s" value="" placeholder="Search for Page Names">
					<input type="submit" id="search-submit" class="button" value="search">
				</p>
				<input name="wpsc-edit-update-buttom" class="wpsc-edit-update-button empty-tab" type="submit" value="Save all Changes" class="button button-primary" style="width: 16%; padding-top: 5px; padding-bottom: 5px; margin-left: 31.5%; display: block;  background: #008200; border-color: #005200; color: white; font-weight: bold; position: absolute; margin-top: -31px;"/>
			</form>
			
			<div style="padding: 15px; background: white;  clear: both; width: 72%; font-family: helvetica, sans-serif; border-radius: 5px; box-shadow: 0px 0px 10px 0px rgb(0 0 0 / 50%);">
				<?php echo "<h3 class='sc-message sc-type' style='color: rgb(0, 115, 0);'>SEO problems found on <span style='color: rgb(115, 1, 154); font-weight: bold;'>" . esc_html( $empty_type[0]->option_value ) . '</span>: ' . esc_html( $empty_count ) . '</h3>'; ?>
				<?php
				echo "<h3 class='sc-message sc-page' style='color: rgb(0, 115, 0);'>Pages scanned: " . esc_html( $empty_page_scan[0]->option_value ) . '/' . esc_html( $page_count );
				if ( ! $wpscx_ent_included && sizeof( (array) $page_count ) >= 500 ) {
					?>
											<span class='wpsc-mouseover-button-page' style='border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?<span class="wpsc-mouseover-text-page"><span style="display: block;text-align: center;font-size: 14px;padding: 10px 0 10px 0;background-color: #2271b1;color: white;margin-bottom: 8px;"> This is a Pro Feature</span><span style="padding: 0 10px; display: block;">Our free version scans up to 25 pages. To scan your entire website,  <a href="https://www.wpspellcheck.com/pricing/" target="_blank">Click Here</a> to upgrade to WP Spell Check Pro.</span></span></span>
										<?php
				}
					echo '</h3>';
				?>
				<?php
				echo "<h3 class='sc-message sc-post' style='color: rgb(0, 115, 0);'>Posts scanned: " . esc_html( $empty_post_scan_count ) . '/' . esc_html( $post_count );
				if ( ! $wpscx_ent_included && $post_count >= 500 ) {
					?>
									<span class='wpsc-mouseover-button-post' style='border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?<span class="wpsc-mouseover-text-post"><span style="display: block;text-align: center;font-size: 14px;padding: 10px 0 10px 0;background-color: #2271b1;color: white;margin-bottom: 8px;"> This is a Pro Feature</span><span style="padding: 0 10px; display: block;">Our free version scans up to 25 posts. To scan your entire website,  <a href="https://www.wpspellcheck.com/pricing/" target="_blank">Click Here</a> to upgrade to WP Spell Check Pro..</span></span></span>
								<?php
				}
				echo '</h3>';
				?>
				<?php
				if ( $wpscx_ent_included ) {
					echo "<h3 class='sc-message sc-media' style='color: rgb(0, 115, 0);'>Media files scanned: " . esc_html( $empty_media_scan[0]->option_value ) . '/' . esc_html( $media_count ) . '</h3>'; }
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
							<h4 style="display: inline-block;">Edit %Word%</h4>
							<input type="text" size="60" name="word_update[]" style="margin-left: 3em;" value class="wpsc-edit-field edit-field">
							<input type="hidden" name="edit_page_name[]" value>
							<input type="hidden" name="edit_page_type[]" value>
							<input type="hidden" name="edit_old_word[]" value>
							<input type="hidden" name="edit_old_word_id[]" value>
						</div>
						<div class="wpsc-buttons">
							<input type="button" class="button-secondary cancel alignleft wpsc-cancel-button" value="Cancel">
							<div style="clear: both;"></div>
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
							<h4 style="font-size: 14px; font-weight: bold; margin: 0.5em 0;">Edit %TYPE% For %TITLE%</h4>
                                                        <p>%SEOTEXT%</p>
							<input type="text" size="60" name="word_update[]" style="margin: 0 0 1em 1em; width: 90%;" value class="wpsc-edit-field edit-field">
							<input type="hidden" name="edit_page_name[]" value>
							<input type="hidden" name="edit_page_type[]" value>
							<input type="hidden" name="edit_old_word[]" value>
							<input type="hidden" name="edit_old_word_id[]" value>
						</div>
						<div class="wpsc-buttons">
							<input type="button" class="button-secondary cancel alignleft wpsc-cancel-button" value="Cancel">
                                                        <input type="button" class="button-secondary alignleft wpsc-generate-seo-button" style="background: #008200; margin-left: 10px; color: white; border: none; font-weight: bold;" value="Generate SEO with AI">
                                                        <div class="seo-progress" style="margin-left: 1em; display: none;"><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif' ?>" alt="Generating SEO" /></div>
							<div style="clear: both;"></div>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
				<script>
                                    
                                    
                                function wpscx_seoListener() { 
                                    jQuery('.wpsc-generate-seo-button').click(function(event) {
                                        event.preventDefault();
                                        //console.log("SEO Clicked");

                                        var postID = jQuery(this).closest("tr").find('input[name="edit_page_name[]"]').attr("value");
                                        var postType = jQuery(this).closest("tr").find('input[name="edit_page_type[]"]').attr("value");
                                        var wordID = jQuery(this).closest("tr").find('input[name="edit_old_word_id[]"]').attr("value");
                                        jQuery(this).closest("tr").find('.seo-progress').css("display", "inline-block");
                                        ajax_object = '<?php echo esc_html( admin_url( WPSC_ADMIN_AJAX ) ); ?>';

                                        jQuery.ajax({
                                            url: ajax_object,
                                            type: "POST",
                                            data: {
                                                type: postType,
                                                id: postID,
                                                action: 'wpscx_openAI_ajax',
                                            },
                                            dataType: 'json',
                                            success: function(response) {
                                                jQuery("#wpsc-edit-seo-row-" + wordID).find('.seo-progress').css("display", "none");
                                                jQuery("#wpsc-edit-seo-row-" + wordID).find(".wpsc-edit-field").val(response.replace(/\n/g, ' '));
                                                jQuery("#wpsc-edit-seo-row-" + wordID).find(".wpsc-edit-field").trigger('input');
                                            }
                                        });
                                    });
                                    
                                    jQuery(".edit-seo-title").on('input', function(e) {
                                        var textLen = jQuery(this).val().length;
                                        
                                        if (textLen < 50) jQuery(this).css('border-color', 'orange');
                                        else if (textLen > 70) jQuery(this).css('border-color', 'red');
                                        else jQuery(this).css('border-color', 'green');
                                    });
                                    
                                    jQuery(".edit-seo-desc").on('input', function(e) {
                                        var textLen = jQuery(this).val().length;
                                        
                                        if (textLen < 120) jQuery(this).css('border-color', 'orange');
                                        else if (textLen > 160) jQuery(this).css('border-color', 'red');
                                        else jQuery(this).css('border-color', 'green');
                                    });
                                }
                                                
					jQuery('.wpscScan').click(function(event) {
							event.preventDefault();
							//console.log(scan_in_progress);
							if (scan_in_progress) return;
							scan_in_progress = true;
							ajax_object = '<?php echo esc_html( admin_url( WPSC_ADMIN_AJAX ) ); ?>';
							var scanType = jQuery(this).attr('value');
							
							jQuery('#wpscScanMessage').html('<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ); ?>images/loading.gif" alt="Scan in Progress" /> Starting New Scan');
							jQuery('.wpscScan').addClass('wpsc-button-greyout'); //Greyout buttons
							
							var scanTime = new Date();
							scanStartTime = scanTime.getTime();
									
							jQuery.ajax({
									url: ajax_object,
									type: "POST",
									data: {
											type: scanType,
											action: 'wpscx_start_scan_empty',
									},
									dataType: 'html',
									success: function(response) {
										jQuery('#wpscScanMessage').html(response); //update the scan message to display the scan started message
										window.setInterval(wpscex_finish_scan_temp(), 100 );
										jQuery('tr.wpsc-row').animate({opacity: 0}, 500, function() { jQuery('tr.wpsc-row').hide(); })
                                                                                jQuery('.wpsc-mesage-container').animate({opacity: 0}, 500, function() { jQuery('.wpsc-mesage-container').hide(); });
										
									   jQuery(document).ready(function() {
											var mouseover_visible = false;
											jQuery('.wpsc-mouseover-button-refresh').mouseenter(function() {
												jQuery('.wpsc-mouseover-text-refresh').css('z-index','100');
												jQuery('.wpsc-mouseover-text-refresh').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
											}).mouseleave(function() {
												var isHoveredPopup = jQuery('.wpsc-mouseover-text-refresh').filter(function() {
													return jQuery(this).is(":hover");
												});
												var isHoveredParent = jQuery(this).parent().filter(function() {
													return jQuery(this).is(":hover");
												});
												if (!isHoveredPopup && !isHoveredParent) {
													jQuery('.wpsc-mouseover-text-refresh').css('z-index','-100');
													jQuery('.wpsc-mouseover-text-refresh').animate({opacity: 0}, 400);
													mouseover_visible = false;
												}
											});
											jQuery('.wpsc-mouseover-button-refresh').click(function() {
											if (!mouseover_visible) {
												jQuery('.wpsc-mouseover-text-refresh').stop();
												jQuery('.wpsc-mouseover-text-refresh').css('z-index','100');
												jQuery('.wpsc-mouseover-text-refresh').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
											} else {
												jQuery('.wpsc-mouseover-text-refresh').css('z-index','-100');
												jQuery('.wpsc-mouseover-text-refresh').animate({opacity: 0}, 400);
												mouseover_visible = false;
											}
											});

											jQuery('.wpsc-mouseover-button-refresh').parent().mouseleave(function() {
												//console.log("Parent Container Mouseleave Triggered");
												var isHoveredPopup = jQuery('.wpsc-mouseover-text-refresh:hover').length > 0;
												var isHoveredButton = jQuery('.wpsc-mouseover-button-refresh:hover').length > 0;
												//console.log("Popup: " + isHoveredPopup + " | Button: " + isHoveredButton);

												if (!isHoveredPopup && !isHoveredButton) {
													jQuery('.wpsc-mouseover-text-refresh').css('z-index','-100');
													jQuery('.wpsc-mouseover-text-refresh').animate({opacity: 0}, 400);
													mouseover_visible = false;
												}
											});
										});
									},
									error: function(xhr, status, thrownError) {
										//console.log(thrownError);
										//console.log(xhr.responseText);
										window.setInterval(wpscex_recheck_scan_temp(), 500 );
									}
							});
						});
				</script>
	<?php
	//echo "debug - After Displaying Spellcheck Table: " . ($end_display - $start) . " Seconds<br />";
	//echo "debug - After Displaying Empty Field Table: " . ($end_empty - $start) . " Seconds<br />";
}




?>
