<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/* Admin Classes */
/*
	Works in the background: yes
	Pro version scans the entire website: yes
	Sends email reminders: yes
	Finds place holder text: yes
	Custom Dictionary for unusual words: yes
	Scans Password Protected membership Sites: yes
	Unlimited scans on my website: Yes


	Scans Categories: Yes WP Spell Check Pro
	Scans SEO Titles: Yes WP Spell Check Pro
	Scans SEO Descriptions: Yes WP Spell Check Pro
	Scans WordPress Menus: Yes WP Spell Check Pro
	Scans Page Titles: Yes WP Spell Check Pro
	Scans Post Titles: Yes WP Spell Check Pro
	Scans Page slugs: Yes WP Spell Check Pro
	Scans Post Slugs: Yes WP Spell Check Pro
	Scans Post categories: Yes WP Spell Check Pro

	Privacy URI: https://www.wpspellcheck.com/privacy-policy/
	Pro Add-on / Home Page: https://www.wpspellcheck.com/
	Pro Add-on / Prices: https://www.wpspellcheck.com/product-tour/
*/
class Wpgcx_Table extends WP_List_Table {

	function __construct() {
		global $status, $page;

		parent::__construct(
			array(
				'singular' => 'result',
				'plural'   => 'results',
				'ajax'     => true,
			)
		);
	}

	function column_default( $item, $column_name ) {
		return print_r( $item, true );
	}


	/*function column_page($item) {
		set_time_limit(600);
		global $wpdb;
		global $wpgc_options;


		$actions = array (
			'Ignore'      			=> sprintf('<input type="checkbox" class="wpgc-ignore-checkbox" name="ignore-word[]" value="' . $item['id'] . '" />Ignore'),
			'Edit'					=> sprintf('<a href="#" class="wpsc-edit-button-grammar" page_type="' . $item['page_type'] . '" id="wpsc-word-' . $item['word'] . '">Edit</a>'),
		);


		return sprintf('%1$s<span style="background-color:#0096ff; float: left; margin: 3px 5px 0 -30px; display: block; width: 12px; height: 12px; border-radius: 16px; opacity: 1.0;"></span>%3$s',
			stripslashes(stripslashes($item['word'])),
			$item['ID'],
			$this->row_actions($actions)
		);
	}*/

	function column_page( $item ) {

		$actions = array();

		$page_name = get_the_title( $item['page_id'] );
                if ( !isset($item['ID']) ) $item['ID'] = '';

		$actions = array(
			'Edit' => sprintf( '<a href="/wp-admin/post.php?post=' . $item['page_id'] . '&action=edit" class="wpsc-edit-button-grammar" target="_blank">Edit</a>' ),
		);

		return sprintf(
			'%1$s <span style="color:silver"></span>%3$s',
			$page_name,
			$item['ID'],
			$this->row_actions( $actions )
		);
	}

	function column_grammar( $item ) {

		$actions = array();
                if ( !isset($item['ID']) ) $item['ID'] = '';

		return sprintf(
			'%1$s <span style="color:silver"></span>%3$s',
			$item['grammar'],
			$item['ID'],
			$this->row_actions( $actions )
		);
	}


	function get_columns() {
		global $wpdb;
		global $wpscx_ent_included;
		wpscx_set_global_vars();
		global $wpgc_settings;

		$options_list = $wpgc_settings;
		$grammar      = '<div style="position: relative; height: 100%;"># of Grammar Errors</div>';

		$columns = array(
			'cb'      => '<input type="checkbox" />',
			'page'    => 'Page',
			'grammar' => $grammar,
		);
		return $columns;
	}


	function get_sortable_columns() {
		$sortable_columns = array(
			'page' => array( 'page', false ),
		);
		return $sortable_columns;
	}


	function single_row( $item ) {
		static $row_class = 'wpsc-row';
		$row_class        = ( '' === $row_class ? ' class="alternate"' : '' );

		echo '<tr class="wpsc-row" id="wpsc-row-' . esc_html( $item['id'] ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}


	function prepare_items() {
		global $wpdb;

		$per_page = 20;

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$table_name = $wpdb->prefix . 'spellcheck_grammar';
		if ( isset( $_GET['s'] ) && '' !== $_GET['s'] ) {
                    $search = stripcslashes( $_GET['s'] );
                    $results = $wpdb->get_results( $wpdb->prepare( 'SELECT a.*, b.post_title FROM `wp_spellcheck_grammar` a JOIN wp_posts b ON a.page_id = b.id WHERE b.post_title LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' ) , OBJECT );
		} elseif ( isset( $_GET['s-top'] ) && '' !== $_GET['s-top'] ) {
                    $search = stripcslashes( $_GET['s-top'] );
                    $results = $wpdb->get_results( $wpdb->prepare( 'SELECT a.*, b.post_title FROM `wp_spellcheck_grammar` a JOIN wp_posts b ON a.page_id = b.id WHERE b.post_title LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' ) , OBJECT );
		} else {
			$results = $wpdb->get_results( 'SELECT * FROM ' . $table_name . ' ORDER BY grammar DESC', OBJECT );
		}

		$data = array();

		foreach ( $results as $word ) {
				array_push(
					$data,
					array(
						'id'               => $word->id,
						'page_id'          => $word->page_id,
						'grammar'          => $word->grammar
					)
				);
		}

		function usort_reorder( $a, $b ) {
			$orderby = ( ! empty( sanitize_text_field( $_REQUEST['orderby'] ) ) ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'word';
			$order   = ( ! empty( sanitize_text_field( $_REQUEST['order'] ) ) ) ? sanitize_text_field( $_REQUEST['order'] ) : 'asc';

			$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
			return ( 'asc' === $order ) ? $result : -$result;
		}
		//usort($data, 'usort_reorder');

		$current_page = $this->get_pagenum();
		$total_items  = count( $data );
		$data         = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
		$this->items  = $data;

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}
}

function wpgcx_render_results() {

		//wp_enqueue_style( 'wpsc-admin-styles', plugin_dir_url( __DIR__ ) . 'css/admin-styles.css' );
		//wp_enqueue_style( 'wpsc-sidebar', plugin_dir_url( __DIR__ ) . 'css/wpsc-sidebar.css' );
		//wp_enqueue_style( 'wpsc-jquery-ui', plugin_dir_url( __DIR__ ) . 'css/jquery-ui.css' );

	$start = round( microtime( true ), 5 );
	ini_set( 'memory_limit', '8192M' );
	set_time_limit( 600 );
	global $wpdb;
	global $wpscx_ent_included;
	global $wpscx_base_page_max;
        global $wpsc_version;
        $classic_active = is_plugin_active( 'classic-editor/classic-editor.php' );
	$table_name       = $wpdb->prefix . 'spellcheck_grammar';
	$options_table    = $wpdb->prefix . 'spellcheck_grammar_options';
	$sc_options_table = $wpdb->prefix . 'spellcheck_options';
	$post_table       = $wpdb->prefix . 'posts';
	$time_estimate    = 0;

		wp_enqueue_script( 'jquery-ui-dialog' );
	wp_enqueue_script( 'admin-js', plugin_dir_url( __FILE__ ) . '../../js/feature-request.js' );
	wp_enqueue_script( 'feature-request', plugin_dir_url( __FILE__ ) . '../../js/admin-js.js' );
		wp_enqueue_script( 'jquery.contextMenu', plugin_dir_url( __FILE__ ) . '../../js/jquery.contextMenu.js' );
	wp_enqueue_script( 'jquery.ui.position', plugin_dir_url( __FILE__ ) . '../../js/jquery.ui.position.js' );

	if ( ! isset( $_GET['action'] ) ) {
		$_GET['action'] = '';
	}
	if ( ! isset( $_GET['submit'] ) ) {
		$_GET['submit'] = '';
	}

	wpscx_set_global_vars();
	$wpgc_settings = $wpdb->get_results( "SELECT option_value FROM $options_table;" );

	$message = '';

	$options_list = $wpgc_settings;
	$total_posts  = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'post'" );

	$pro_word_count = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='pro_error_count';" );
	$pro_words      = $pro_word_count[0]->option_value;

	$scan_message = 'No scan currently running';

	$scan_progress = $wpdb->get_results( "SELECT * FROM $options_table WHERE option_name='scan_running'" );

	if ( 'true' === $scan_progress[0]->option_value && isset( $_GET['wpsc-script'] ) && 'noscript' !== $_GET['wpsc-script'] ) {
		$scan_message = '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> A scan is currently in progress for <span class="sc-message" style="color: rgb(0, 150, 255); font-weight: bold;">' . $options_list[7]->option_value . '</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck-grammar.php">Click here</a> to see scan results.';
	}

	$check_scan = wpgcx_check_scan_progress();

	$post_types     = get_post_types();
	$post_type_list = array();
	foreach ( $post_types as $type ) {
		if ( 'revision' !== $type && 'page' !== $type && 'slider' !== $type && 'attachment' !== $type && 'optionsframework' !== $type && 'product' !== $type && 'wpsc-product' !== $type && 'wpcf7_contact_form' !== $type && 'nav_menu_item' !== $type && 'gal_display_source' !== $type && 'lightbox_library' !== $type && 'wpcf7s' !== $type ) {
			array_push( $post_type_list, $type );
		}
	}

	$post_status = array( 'publish', 'draft' );

	$post_count  = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type='post' AND (post_status='draft' OR post_status='publish')" );
	$page_count  = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type='page' AND (post_status='draft' OR post_status='publish')" );
	$total_pages = $page_count;

	$post_scan_count = $options_list[5]->option_value;
	if ( $post_scan_count > $post_count ) {
		$post_scan_count = $post_count;
	}
	$total_posts = $post_count;

	$max_pages = $wpdb->get_results( "SELECT option_value FROM $sc_options_table WHERE option_name = 'pro_max_pages'" );
	$max_pages = intval( $max_pages[0]->option_value );

	if ( ! $wpscx_ent_included ) {
		$max_pages = $wpscx_base_page_max;
	}

	if ( 'noscript' !== $check_scan && isset( $_GET['wpsc-script'] ) ) {
		wp_enqueue_script( 'wpgc-results-ajax', plugin_dir_url( __FILE__ ) . '/wpgc-ajax.js', array( 'jquery' ) );
		wp_localize_script( 'wpgc-results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( WPSC_ADMIN_AJAX ) ) );
	}

	/*if ($_GET['action'] == 'check' && $_GET['submit'] == 'Posts') {
		wpgcx_clear_results(); //Clear out results table in preparation for a new scan
		$rng_seed = rand(0,999999999);
		$time_estimate = intval($total_posts / 8);
		$time_estimate= wpscx_time_elapsed($time_estimate);
		$wpdb->update($options_table, array('option_value' => 0), array('option_name' => 'pro_error_count'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_running'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'post_running'));
		$wpdb->update($options_table, array('option_value' => 'Posts'), array('option_name' => 'last_scan_type'));
		$wpdb->update($options_table, array("option_value" => '0'), array("option_name" => "last_scan_errors"));
		$scan_message = '<img src="'. esc_url(plugin_dir_url( __FILE__ )) . 'images/loading.gif" alt="Scan in Progress" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Posts</span>. Estimated time for completion is ' . $time_estimate. ' seconds. The page will automatically refresh when the scan has finished.';

		wp_enqueue_script( 'wpgc-results-ajax', plugin_dir_url( __FILE__ ) . '/wpgc-ajax.js', array('jquery') );
		wp_localize_script( 'wpgc-results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( WPSC_ADMIN_AJAX ) ) );

		wp_schedule_single_event(time(), 'wpgcx_check_posts', array ($rng_seed, true));
	} elseif ($_GET['action'] == 'check' && $_GET['submit'] == 'Pages') {
		wpgcx_clear_results(); //Clear out results table in preparation for a new scan
		$rng_seed = rand(0,999999999);
		$time_estimate = intval($total_posts / 8);
		$time_estimate= wpscx_time_elapsed($time_estimate);
		$wpdb->update($options_table, array('option_value' => 0), array('option_name' => 'pro_error_count'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_running'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'page_running'));
		$wpdb->update($options_table, array('option_value' => 'Pages'), array('option_name' => 'last_scan_type'));
		$wpdb->update($options_table, array("option_value" => '0'), array("option_name" => "last_scan_errors"));
		$scan_message = '<img src="'. esc_url(plugin_dir_url( __FILE__ )) . 'images/loading.gif" alt="Scan in Progress" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Pages</span>. Estimated time for completion is ' . $time_estimate. ' seconds. The page will automatically refresh when the scan has finished.';

		wp_enqueue_script( 'wpgc-results-ajax', plugin_dir_url( __FILE__ ) . '/wpgc-ajax.js', array('jquery') );
		wp_localize_script( 'wpgc-results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( WPSC_ADMIN_AJAX ) ) );

		wp_schedule_single_event(time(), 'wpgcx_check_pages', array ($rng_seed, true));
	} elseif ($_GET['action'] == 'check' && $_GET['submit'] == 'Entire Site') {
		$time_estimate = intval(($total_posts + $total_pages) / 8);
		$time_estimate= wpscx_time_elapsed($time_estimate);

		$wpdb->update($options_table, array('option_value' => 0), array('option_name' => 'pro_error_count'));
		$scan_message = '<img src="'. esc_url(plugin_dir_url( __FILE__ )) . 'images/loading.gif" alt="Scan in Progress" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Entire Site</span>. Estimated time for completion is ' . $time_estimate. ' seconds. The page will automatically refresh when the scan has finished.';

		wp_enqueue_script( 'wpgc-results-ajax', plugin_dir_url( __FILE__ ) . '/wpgc-ajax.js', array('jquery') );
		wp_localize_script( 'wpgc-results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( WPSC_ADMIN_AJAX ) ) );

		$wpdb->update($options_table, array("option_value" => '0'), array("option_name" => "last_scan_errors"));
		$wpdb->update($options_table, array('option_value' => 'Entire Site'), array('option_name' => 'last_scan_type'));
		sleep(1);;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_running'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'post_running'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'page_running'));

		wp_schedule_single_event(time(), 'wpgcx_scan_site', array ($rng_seed, true));
	}*/
	if ( ( isset( $_GET['action'] ) && isset( $_GET['submit'] ) ) && 'check' === $_GET['action'] && 'Entire Site' === $_GET['submit'] && $classic_active ) {
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
	if ( ( isset( $_GET['action'] ) && isset( $_GET['submit'] ) ) && 'check' === $_GET['action'] && 'Clear Results' === $_GET['submit'] ) {
		$scan_message = 'All spell check results have been cleared';
		wpgcx_clear_results();
	}
	if ( isset( $_GET['submit'] ) && 'Stop Scans' === $_GET['submit'] ) {
		$scan_message = 'All current spell check scans have been stopped.';
		wpgcx_clear_scan();
	}

	if ( isset( $_GET['submit'] ) && 'Create Pages' === $_GET['submit'] ) {

		for ( $x = 5001;$x <= 10000;$x++ ) {
			$post_args = array(
				'post_title'   => 'Post-' . $x,
				'post_content' => 'Grammark helps improve writing style & grammar and teaches students to self-edit. Basically, it finds things that grammarians consider bad, highlights them, and suggests improvements. So writers can measure progress, it gives a "score" based on problems per document length, updated whenever the writer fixes a problem.',
				'post_status'  => 'publish',
				'post_type'    => 'post',
				'post_author'  => get_current_user_id(),
			);
		}
	}

	$list_table = new Wpgcx_Table();
	$list_table->prepare_items();
	?>
		<?php wpscx_show_feature_window(); ?>
		<?php //wpscx_check_install_notice(); ?>
		<script>
			<?php if ( $check_scan ) { ?>
					var scan_in_progress = true;
				<?php } else { ?>
					var scan_in_progress = false;
				<?php } ?>
					var scanStartTime;

				function wpgcx_recheck_scan_temp() {
					jQuery.ajax({
							url: ajax_object.ajax_url,
							type: "POST",
							data: {
									action: 'results_gc',
							},
							dataType: 'html',
							success: function(response) {
									if (response == 'true') { window.setInterval(wpgcx_finish_scan_temp(), 2000 ); console.log(response); }
									else { wpgcx_finish_scan_temp();console.log(response); }
							}
					});
				}
			
			function wpgcx_finish_scan_temp() {
					var scanTime2 = new Date();
					var scanEndTime2 = scanTime2.getTime();
					var scanFinal2 = ( scanEndTime2 - scanStartTime) / 1000;
					//console.log("Scan Time(pre finish scan):" + scanFinal2);
						jQuery.ajax({
								url: ajax_object,
								type: "POST",
								data: {
										action: 'wpscx_display_results_grammar',
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

										wpgcx_show_stats(scanFinal);
								}
						});
				}
				
				function wpgcx_show_stats(x) {
					 //console.log("X = " + x);
					 jQuery.ajax({
						url: ajax_object,
						type: "POST",
						data: {
								action: 'wpscx_get_stats_grammar',
								'scantime': x,
						},
						dataType: 'json',
						success: function(response) {
							//console.log(response);
							jQuery('.sc-type').html("Errors found on <span style='color: rgb(0, 150, 255); font-weight: bold;'>" + response.scanType + ": " + response.totalErrors);
							if (Number(response.pageCount) >= Number(response.totalPages)) { jQuery('.sc-post').html("Posts scanned: " + response.totalPosts + "/" + response.totalPosts); } else { jQuery('.sc-post').html("Posts scanned: " + response.postCount + "/" + response.totalPosts); }
							if (Number(response.pageCount) >= Number(response.totalPages)) { jQuery('.sc-page').html("Pages scanned: " + response.totalPages + "/" + response.totalPages); } else { jQuery('.sc-page').html("Pages scanned: " + response.pageCount + "/" + response.totalPages); }
							jQuery('.sc-time').html("Last scan took " + response.scanTime);
							jQuery('.next-page').click(function(e) {
								e.preventDefault();
								window.location.href = "?page=wp-spellcheck-grammar.php&paged=2";
							});
                                                        var last_page = ( ( parseInt( response.totalPosts ) + parseInt(response.totalPages) ) / 20 ) + 1
                                                        jQuery('.last-page').click(function(e) {
								e.preventDefault();
								window.location.href = "?page=wp-spellcheck-grammar.php&paged=" + last_page;
							});
						},
						error: function(xhr, status, thrownError) {
							//console.log(thrownError);
						}
					});
				}
						
						
		</script>
		
	<style>input[type=submit]{border-radius:32px!important; box-shadow: none!important; text-shadow: none!important; border: none!important;}.search-box input[type=submit] { color: white; background-color: #00A0D2; border-color: #0073AA; } #cb-select-all-1,#cb-select-all-2 { display: none; } td.word { font-size: 15px; } p.submit { display: inline-block; margin-left: 8px; } h3.sc-message { width: 49%; display: inline-block; padding-left: 8px; font-weight: normal; } .wpsc-mouseover-text-page,.wpsc-mouseover-text-post,.wpsc-mouseover-text-refresh { color: black; font-size: 12px; width: 225px; display: inline-block; position: absolute; margin: -13px 0 0 -270px; padding: 3px; border: 1px solid black; border-radius: 10px; opacity: 0; background: white; z-index: -100; } .wpsc-row .row-actions, .wpsc-row .row-actions *{ visibility: visible!important; left: 0!important; } #current-page-selector { width: 12%; } .hidden { display: none; } .wpsc-scan-nav-bar { border-bottom: 1px solid #BBB; } .wpsc-scan-nav-bar a { text-decoration: none; margin: 5px 5px -1px 5px; padding: 8px; border: 1px solid #BBB; display: inline-block; font-weight: bold; color: black; font-size: 14px; } .wpsc-scan-nav-bar a.selected { border-bottom: 1px solid white; background: white; } #wpsc-empty-fields-tab .button-primary { background: #73019a; border-color: #51006E; text-shadow: 1px 1px #51006d; box-shadow: 0 1px 0 #51006d; } #wpsc-empty-fields-tab .button-primary:hover { background: #9100c3 } #wpsc-empty-fields-tab .button-primary:active { background: #51006d; }.wpsc-scan-buttons input#submit:active { margin-top: -7px; } #wpgc-scan-results-tab .wp-list-table th { text-align: center; } .wpgc-desc .wpgc-desc-content { display: none; } .wpgc-desc-hover { display: block!important; position: relative; top: -125px; left: -55px; width: 125px; padding: 0px; margin: 0px; z-index: 100; height: 0px!important; } .wpgc-desc { position: absolute; bottom: 0px; margin-right: -4px; width: 125px; } #wpgc-scan-results-tab .wp-list-table td:not(.column-page) { text-align: center; }
		#wpsc-table-results table { border-radius: 5px; box-shadow: 0px 0px 10px 0px rgb(0 0 0 / 50%); }
		.wpsc-button-greyout, .wpsc-button-greyout:hover { background: darkgrey!important }
                .wpscScanSite { background-color: #ffb01f; border-color: #ffb01f; box-shadow: 0px 1px 0px #ffb01f; text-shadow: 1px 1px 1px #ffb01f; font-weight: bold; }</style>
<div id="wpsc-dialog-confirm" title="Are you sure?" style="display: none;">
  <p>Would you like to Proceed with the changes?</p>
</div>
		<div class="wrap wpsc-table">
			<h2><a href="admin.php?page=wp-spellcheck-grammar.php"><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'images/logo.png'; ?>" alt="WP Spell Check" /></a> <span style="position: relative; top: -8px;"> - Grammar Scan Results</span></h2>
                        <?php if( !$classic_active ) { ?><h3 style="color: red;">WP Spell Check Grammar scan requires WordPress Classic Editor to be installed and active.</h3><?php } ?>
			<div class="wpsc-scan-nav-bar">
				<a href="<?php echo esc_url( admin_url() ); ?>admin.php?page=wp-spellcheck.php" id="wpsc-scan-results" name="wpsc-scan-results">Spelling Errors</a>
				<a href="#" class="selected" id="wpsc-grammar" name="wpsc-grammar">Grammar</a>
				<a href="<?php echo esc_url( admin_url() ); ?>admin.php?page=wp-spellcheck-seo.php" id="wpsc-empty-fields" name="wpsc-empty-fields">SEO</a>
				<a href="<?php echo esc_url( admin_url() ); ?>admin.php?page=wp-spellcheck-html.php" id="wpsc-grammar" name="wpsc-grammar">Broken Code</a>
			</div>
			<div id="wpgc-scan-results-tab" style="margin-top: -17px;" 
			<?php
			if ( isset( $_GET['wpsc-scan-tab'] ) && 'empty' === $_GET['wpsc-scan-tab'] ) {
				echo 'class="hidden"';}
			?>
			>
			<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method='GET'>
							<div style="border-radius: 5px; box-shadow: 0px 0px 10px 0px rgb(0 0 0 / 50%); background: white;">
				<div class="wpsc-scan-buttons" style="padding-left: 8px;">
									<h3 style="margin-bottom: 0px; padding-top: 10px;">Click on the buttons below to grammar check your pages and/or pots.</h3>
				<h3 style="display: inline-block;">Scan:</h3>
				<p class="submit"><input style="background-color: #ffb01f; border-color: #ffb01f; box-shadow: 0px 1px 0px #ffb01f; text-shadow: 1px 1px 1px #ffb01f; font-weight: bold;" type="submit" name="submit" id="submit" class="button button-primary wpscScan wpscScanSite" value="Entire Site" 
				<?php
				if ( !$classic_active ) {
					echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
				></p>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Pages" 
				<?php
				if ( 'false' === $options_list[0]->option_value || !$classic_active ) {
					echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
				></p>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Posts" 
				<?php
				if ( 'false' === $options_list[1]->option_value || !$classic_active ) {
					echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
				></p>
				<p class="submit" style="margin-left: -11px;"><span style="position: relative; left: 15px;"> - </span><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../../images/clear-results.png'; ?>" alt="Clear Error Results" style="width: 20px; position: relative; top: 5px; left: 27px;" /><input type="submit" name="submit" id="submit" class="button button-primary" style="padding-left: 30px; background-color: red;" value="Clear Results"></p>
				<p class="submit" style="margin-left: -11px;"><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../../images/see-results.png'; ?>" alt="See Error Results" style="width: 20px; position: relative; top: 5px; left: 26px;" /><input type="submit" name="submit" id="submit" class="button button-primary" style="padding-left: 30px; background-color: red;" value="See Scan Results"></p>
				<p class="submit" style="margin-left: -11px;"><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../../images/stop-scans.png'; ?>" alt="Stop Current Scans" style="width: 20px; position: relative; top: 5px; left: 25px;" /><input type="submit" name="submit" id="submit" class="button button-primary" style="padding-left: 30px; background-color: red;" value="Stop Scans"></p>
								<p class="submit" style="margin-left: -11px;"><a href="/wp-admin/admin.php?page=wp-spellcheck-options.php" target="_blank"><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../../images/options.png'; ?>" alt="WP Spell Check Options" title="Options" style="width: 30px; position: relative; top: 11px; left: 20px; padding: 0px; border-radius: 25px;" /></a></p>
				<!--<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" style="background-color: red;" value="Create Pages"></p>-->
				</div>
				<div style="padding: 5px; font-size: 12px;">
					<input type="hidden" name="page" value="wp-spellcheck-grammar.php">
					<input type="hidden" name="action" value="check">
										<?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);' id='wpscScanMessage'>" . esc_html( $scan_message ) . '</h3><br />'; ?>
					<?php echo "<h3 class='sc-message sc-time' style='color: rgb(0, 115, 0);'>Last scan took " . esc_html( $options_list[3]->option_value ) . '</h3><br>'; ?>
					<?php
					if ( ! $wpscx_ent_included ) {
						if ( $options_list[6]->option_value > 0 && ! $wpscx_ent_included ) {
							echo "<h3 class='sc-message' style='color: rgb(225, 0, 0);'><strong>Pro Version: </strong>Grammar and styling errors were found on other parts of your website. <a href='https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgradegram&utm_medium=grammar_scan&utm_content=" . esc_attr( $wpsc_version ) . "' target='_blank'>Click here</a> to upgrade to find and fix them all.</h3>";
						} else {
							//echo "<h3 class='sc-message' style='color: rgb(225, 0, 0);'><a href='https://www.wpspellcheck.com/product-tour/' target='_blank'>Upgrade</a> to scan all parts of your website.</h3>";
						}
					}
					?>
				</div>
							</div>
			</form>
			<?php include( dirname( __FILE__ ) . '/../sidebar.php' ); ?>
			<form id="words-list" method="get" style="width: 75%; float: left; margin-top: 10px;">
				<p class="search-box" style="position: relative; margin-top: 0.5em;">
					<label class="screen-reader-text" for="search_id-search-input">search:</label>
					<input type="search" id="search_id-search-input-top" name="s-top" value="" placeholder="Search for Page Names">
					<input type="submit" id="search-submit-top" class="button" value="search">
				</p>
				<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
												<div id="wpsc-table-results">
									<?php $list_table->display(); ?>
								</div>
								<p class="search-box" style="margin-top: 0.7em;">
					<label class="screen-reader-text" for="search_id-search-input">search:</label>
					<input type="search" id="search_id-search-input" name="s" value="" placeholder="Search for Page Names">
					<input type="submit" id="search-submit" class="button" value="search">
				</p>
			</form>
			
			<div style="padding: 15px; background: white; clear: both; width: 72%; font-family: helvetica, sans-serif; border-radius: 5px; box-shadow: 0px 0px 10px 0px rgb(0 0 0 / 50%);">
				<?php echo "<h3 class='sc-message sc-type' style='color: rgb(0, 115, 0);'>Errors found on <span style='color: rgb(0, 150, 255); font-weight: bold;'>" . esc_html( $options_list[7]->option_value ) . '</span>: ' . esc_html( $options_list[6]->option_value ) . '</h3>'; ?>
				<?php echo "<h3 class='sc-message sc-page' style='color: rgb(0, 115, 0);'>Pages scanned: " . esc_html( $options_list[4]->option_value ) . '/' . esc_html( $total_pages ) . '</h3>'; ?>
				<?php echo "<h3 class='sc-message sc-post' style='color: rgb(0, 115, 0);'>Posts scanned: " . esc_html( $options_list[5]->option_value ) . '/' . esc_html( $total_posts ) . '</h3>'; ?>
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
							<!--<input type="checkbox" name="global-edit" value="global-edit"> Apply changes to entire website-->
							<div style="clear: both;"></div>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<!-- Suggested Spellings Clone Field -->
		<table style="display: none;" role="presentation">
			<tbody>
				<tr id="wpsc-suggestion-row" class="wpsc-editor">
					<td colspan="4">
						<div class="wpsc-suggestion-content">
							<label><span>Suggested Spellings</span>
							<select class="wpsc-suggested-spelling-list" name="suggested_word[]">
								<option id="wpsc-suggested-spelling-1" value></option>
								<option id="wpsc-suggested-spelling-2" value></option>
								<option id="wpsc-suggested-spelling-3" value></option>
								<option id="wpsc-suggested-spelling-4" value></option>
							</select>
							<input type="hidden" name="suggest_page_name[]" value>
							<input type="hidden" name="suggest_page_type[]" value>
							<input type="hidden" name="suggest_old_word[]" value>
							<input type="hidden" name="suggest_old_word_id[]" value>
						</div>
						<div class="wpsc-buttons">
							<input type="button" class="button-secondary cancel alignleft wpsc-cancel-suggest-button" value="Cancel">
							<!--<input type="checkbox" name="global-suggest" value="global-suggest"> Apply changes to entire website-->
							<div style="clear: both;"></div>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery(".wpgc-desc").click(function() {
					//console.log(jQuery(this).find(".wpgc-desc-content").html());
					jQuery(this).find(".wpgc-desc-content").toggleClass( "wpgc-desc-hover" );
				});
			});
						
						jQuery('.wpscScan').click(function(event) {
					event.preventDefault();
					if (scan_in_progress) return;
					scan_in_progress = true;
					ajax_object = '<?php echo esc_url( admin_url( WPSC_ADMIN_AJAX ) ); ?>';
					var scanType = jQuery(this).attr('value');
					
					var scanTime = new Date();
					scanStartTime = scanTime.getTime();
					
					jQuery('#wpscScanMessage').html('<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ); ?>images/loading.gif" alt="Scan in Progress" /> Starting New Scan');
					jQuery('.wpscScan').addClass('wpsc-button-greyout'); //Greyout buttons

					jQuery.ajax({
							url: ajax_object,
							type: "POST",
							data: {
									type: scanType,
									action: 'wpscx_start_scan_grammar',
							},
							dataType: 'html',
							success: function(response) {
								jQuery('#wpscScanMessage').html(response); //update the scan message to display the scan started message
								window.setInterval(wpgcx_finish_scan_temp(), 500 );
								jQuery('tr.wpsc-row').animate({opacity: 0}, 500, function() { jQuery('tr.wpsc-row').hide(); })
								
								var scanTime = new Date();
								var scanEndTime = scanTime.getTime();
								var scanFinal = ( scanEndTime - scanStartTime) / 1000;
								//console.log("Scan Time(Starting Scan):" + scanFinal);
								
								jQuery(document).ready(function() {
									var mouseover_visible = false;
									jQuery('.wpsc-mouseover-button-refresh').mouseenter(function() {
									jQuery('.wpsc-mouseover-text-refresh').css('z-index','100');
									jQuery('.wpsc-mouseover-text-refresh').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
									}).mouseleave(function() {
									jQuery('.wpsc-mouseover-text-refresh').css('z-index','-100');
									jQuery('.wpsc-mouseover-text-refresh').animate({opacity: 0}, 400);
									mouseover_visible = false;
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
								});
							},
									error: function(xhr, status, thrownError) {
										//console.log(thrownError);
										//console.log(xhr.responseText);
										wpgcx_recheck_scan_temp();
									}
					});
				});
		</script>
	<?php
}




?>
