<?php
if(!defined('ABSPATH')) { exit; }
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
class wphcx_table extends WP_List_Table {

	function __construct() {
		global $status, $page;
		
		
		parent::__construct( array(
			'singular' => 'word',
			'plural' => 'words',
			'ajax' => true
		) );
	}
	
	function column_default($item, $column_name) {
		return print_r($item,true);
	}
	
	
	function column_word($item) {

		$actions = array (
			//'Ignore'      			=> sprintf('<input type="checkbox" class="wpsc-ignore-checkbox" name="ignore-word[]" value="' . $item['id'] . '" />Ignore'),
			'Edit'					=> sprintf('<a href="post.php?post=' . $item['page_id'] . '&action=edit" id="wpsc-page-name" page="' . $item['page_id'] . '" target="_blank">Edit</a>')
		);
		
		
		return sprintf('%1$s%3$s',
            stripslashes(stripslashes($item['word'])),
            $item['ID'],
            $this->row_actions($actions)
        );
	}
	
	
	function column_page_name($item) {
		global $wpdb;
		$link = urldecode ( get_permalink( $item['page_id'] ) );

		$actions = array (
			'View'      			=> sprintf('<a href="' . $link . '" id="wpsc-page-name" page="' . $item['page_id'] . '" target="_blank">View</a>'),
		);

		return sprintf('%1$s <span style="color:silver"></span>%3$s',
            $item['page_name'],
            $item['ID'],
            $this->row_actions($actions)
        );
	}

	
	function column_page_type($item) {
		
		$actions = array ();
		
		
		return sprintf('%1$s <span style="color:silver"></span>%3$s',
            $item['page_type'],
            $item['ID'],
            $this->row_actions($actions)
        );
	}

	
	function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'word' => 'Broken Code',
			'page_name' => 'Page',
			'page_type' => 'Page Type'
		);
		return $columns;
	}
	
	
	function get_sortable_columns() {
		$sortable_columns = array(
			'word' => array('word',false),
			'page_name' => array('page_name',false),
			'page_type' => array('page_type',false)
		);
		return $sortable_columns;
	}

	
	function single_row( $item ) {
		static $row_class = 'wpsc-row';
		$row_class = ( $row_class == '' ? ' class="alternate"' : '' );

		echo '<tr class="wpsc-row" id="wpsc-row-' . esc_html($item['id']) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
	
	
	function prepare_items() {
		
		global $wpdb;
		global $wpscx_ent_included;
		
		$per_page = 20;
		
		
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		
		$table_name = $wpdb->prefix . 'spellcheck_html';
		$dictionary_table = $wpdb->prefix . 'spellcheck_dictionary';
		if (isset($_GET['submit']) && $_GET['submit'] == 'Find Broken Shortcodes' && $wpscx_ent_included) {
			$results = $wpdb->get_results('SELECT id, word, page_name, page_type, page_id FROM ' . $table_name . ' WHERE ignore_word is false AND word LIKE "[%]"', OBJECT);
		} elseif (isset($_GET['s']) && $_GET['s'] != '') {
			$results = $wpdb->get_results('SELECT id, word, page_name, page_type, page_id FROM ' . $table_name . ' WHERE ignore_word is false AND word LIKE "%' . sanitize_text_field($_GET['s']) . '%"', OBJECT); 
		} elseif (isset($_GET['s']) && $_GET['s-top'] != '') {
			$results = $wpdb->get_results('SELECT id, word, page_name, page_type, page_id FROM ' . $table_name . ' WHERE ignore_word is false AND word LIKE "%' . sanitize_text_field($_GET['s-top']) . '%"', OBJECT); 
		} else {
			$results = $wpdb->get_results('SELECT id, word, page_name, page_type, page_id FROM ' . $table_name . ' WHERE ignore_word is false', OBJECT);
		}
		$data = array();
		foreach($results as $word) {
			if ($word->word != '') {
				array_push($data, array('id' => $word->id, 'word' => $word->word, 'page_name' => $word->page_name, 'page_type' => $word->page_type, 'page_url' => $word->page_url, 'page_id' => $word->page_id));
			}
		}
		
		function usort_reorder($a, $b) {
			$orderby = (!empty(sanitize_text_field($_REQUEST['orderby']))) ? sanitize_text_field($_REQUEST['orderby']) : 'word'; 
			$order = (!empty(sanitize_text_field($_REQUEST['order']))) ? sanitize_text_field($_REQUEST['order']) : 'asc'; 
			
			$result = strcmp($a[$orderby], $b[$orderby]); 
			return ($order==='asc') ? $result : -$result;
		}
		usort($data, 'usort_reorder');
		
		
		$current_page = $this->get_pagenum();
		$total_items = count($data);
		$data = array_slice($data,(($current_page-1)*$per_page),$per_page);
		$this->items = $data;
		
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page' => $per_page,
			'total_pages' => ceil($total_items/$per_page)
		) );
	}
}

function wphcx_admin_render() {
    
        wp_enqueue_style('wpsc-admin-styles', plugin_dir_url( __DIR__ ) . "css/admin-styles.css");
        wp_enqueue_style('wpsc-sidebar', plugin_dir_url( __DIR__ ) . "css/wpsc-sidebar.css");
        wp_enqueue_style('wpsc-jquery-ui', plugin_dir_url( __DIR__ ) . "css/jquery-ui.css");
        require_once('wpsc-class-brokencode.php');
        
	$start = round(microtime(true),5);
	ini_set('memory_limit','8192M'); 
	set_time_limit(600); 
	global $wpdb;
	global $wpscx_ent_included;
        global $wpsc_version;
	$table_name = $wpdb->prefix . "spellcheck_grammar";
	$options_table = $wpdb->prefix . "spellcheck_options";
	$error_table = $wpdb->prefix . "spellcheck_html";
	$post_table = $wpdb->prefix . "posts";
	$time_estimate = 0;
	$pro_scan_msg = "";
        
        wp_enqueue_script( 'jquery-ui-dialog' );
	wp_enqueue_script('admin-js', plugin_dir_url( __FILE__ ) . '../js/feature-request.js');
	wp_enqueue_script('feature-request', plugin_dir_url( __FILE__ ) . '../js/admin-js.js');
        wp_enqueue_script('jquery.contextMenu', plugin_dir_url( __FILE__ ) . '../js/jquery.contextMenu.js');
	wp_enqueue_script('jquery.ui.position', plugin_dir_url( __FILE__ ) . '../js/jquery.ui.position.js');
	
	if (!isset($_GET['action'])) $_GET['action'] = '';
	if (!isset($_GET['submit'])) $_GET['submit'] = '';
	if (!isset($_GET['wpsc-script'])) $_GET['wpsc-script'] = '';
	
	wpscx_set_global_vars();
	global $wpsc_settings;
	
	$message = '';
	
	$options_list = $wpsc_settings;
	$total_pages = $wpdb->get_var("SELECT COUNT(*) FROM $post_table WHERE post_type = 'page'");
	$total_posts = $wpdb->get_var("SELECT COUNT(*) FROM $post_table WHERE post_type = 'post'");
	
	$post_scan_count = $options_list[144]->option_value;
	if ($post_scan_count > $total_posts) $post_scan_count = $total_posts;
	
	$scan_message = "No scan currently running";
	
	$scan_progress = $wpdb->get_results("SELECT * FROM $options_table WHERE option_name='html_scan_running'");
	
	if ($scan_progress[0]->option_value == "true") $scan_message = '<img src="'. esc_url(plugin_dir_url( __FILE__ )) . 'images/loading.gif" alt="Scan in Progress" /> A scan is currently in progress for <span class="sc-message" style="color: rgb(0, 150, 255); font-weight: bold;">Entire site</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck-html.php">Click here</a> to see scan results.';
	
	$check_scan = wphcx_check_scan_progress();
	
	$post_status = array("publish", "draft");
	
	$post_count = $wpdb->get_var("SELECT COUNT(*) FROM $post_table WHERE post_type='post' AND (post_status='draft' OR post_status='publish')");
	$page_count = $wpdb->get_var("SELECT COUNT(*) FROM $post_table WHERE post_type='page' AND (post_status='draft' OR post_status='publish')");
	$error_count = $wpdb->get_var("SELECT COUNT(*) FROM $error_table WHERE ignore_word = 0");
	
	$max_pages = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name = 'pro_max_pages'");
	$max_pages = intval($max_pages[0]->option_value);
	
	$estimated_time = intval((($total_pages + $total_posts) / 3.5) + 3);
	
	$estimated_time = wpscx_time_elapsed($estimated_time);

	if (!$wpscx_ent_included) $max_pages = 500;
	
	if ($check_scan && $_GET['wpsc-script'] != 'noscript') {
		wp_enqueue_script( 'wphc-results-ajax', plugin_dir_url( __FILE__ ) . '/wphc-ajax.js', array('jquery') );
		wp_localize_script( 'wphc-results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}
	
	/*if ($_GET['action'] == 'check' && $_GET['submit'] == 'Entire Site') {
		wphcx_clear_results(); //Clear out results table in preparation for a new scan
		$rng_seed = rand(0,999999999);
		$time_estimate = intval($total_posts / 8);
		$time_estimate= wpscx_time_elapsed($time_estimate);
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'html_scan_running'));
		$wpdb->update($options_table, array("option_value" => time()), array("option_name" => "html_scan_start_time"));
		$scan_message = '<img src="'. esc_url(plugin_dir_url( __FILE__ )) . 'images/loading.gif" alt="Scan in Progress" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Entire Site</span>. Estimated time for completion is ' . $time_estimate. ' seconds. The page will automatically refresh when the scan has finished.';
		
		wp_enqueue_script( 'wphc-results-ajax', plugin_dir_url( __FILE__ ) . '/wphc-ajax.js', array('jquery') );
		wp_localize_script( 'wphc-results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		
		wp_schedule_single_event(time(), 'admincheckcode', array ($rng_seed, true));
	} elseif ($_GET['action'] == 'check' && $_GET['submit'] == 'Broken HTML') {
		wphcx_clear_results(); //Clear out results table in preparation for a new scan
		$rng_seed = rand(0,999999999);
		$time_estimate = intval($total_posts / 8);
		$time_estimate= wpscx_time_elapsed($time_estimate);
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'html_scan_running'));
		$wpdb->update($options_table, array("option_value" => time()), array("option_name" => "html_scan_start_time"));
		$scan_message = '<img src="'. esc_url(plugin_dir_url( __FILE__ )) . 'images/loading.gif" alt="Scan in Progress" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Broken HTML</span>. Estimated time for completion is ' . $time_estimate. ' seconds. The page will automatically refresh when the scan has finished.';
		
		wp_enqueue_script( 'wphc-results-ajax', plugin_dir_url( __FILE__ ) . '/wphc-ajax.js', array('jquery') );
		wp_localize_script( 'wphc-results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		
		wp_schedule_single_event(time(), 'admincheckhtml', array ($rng_seed, true));
	} elseif ($_GET['action'] == 'check' && $_GET['submit'] == 'Broken Shortcodes') {
		wphcx_clear_results(); //Clear out results table in preparation for a new scan
		$rng_seed = rand(0,999999999);
		$time_estimate = intval($total_posts / 8);
		$time_estimate= wpscx_time_elapsed($time_estimate);
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'html_scan_running'));
		$wpdb->update($options_table, array("option_value" => time()), array("option_name" => "html_scan_start_time"));
		$scan_message = '<img src="'. esc_url(plugin_dir_url( __FILE__ )) . 'images/loading.gif" alt="Scan in Progress" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Broken Shortcodes</span>. Estimated time for completion is ' . $time_estimate. ' seconds. The page will automatically refresh when the scan has finished.';
		
		wp_enqueue_script( 'wphc-results-ajax', plugin_dir_url( __FILE__ ) . '/wphc-ajax.js', array('jquery') );
		wp_localize_script( 'wphc-results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		
		wp_schedule_single_event(time(), 'admincheckshortcode', array ($rng_seed, true));
	}*/
        if ((isset($_GET['action']) && isset($_GET['submit'])) && $_GET['action'] == 'check' && $_GET['submit'] == 'Scan Site') {
            $code_scanner = new wpscx_broken_code_scanner;
            
            $pro_error_count = $code_scanner->wpscx_scan_all_eps();
            
           // $pro_error_count = wpscx_check_broken_code_free(0, true);
            $pro_error_msg = "<h3 style='color: red;'>" . $pro_error_count . " Broken code errors were found on your website.</h3>";
	}
        if ((isset($_GET['action']) && isset($_GET['submit'])) && $_GET['action'] == 'check' && $_GET['submit'] == 'Entire Site') {
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
	if ((isset($_GET['action']) && isset($_GET['submit'])) && $_GET['action'] == 'check' && $_GET['submit'] == 'Clear Results') {
		$scan_message = 'All spell check results have been cleared';
		wphcx_clear_results();
	}
	if (isset($_GET['submit']) && $_GET['submit'] == "Stop Scans") {
		$scan_message = "All current spell check scans have been stopped.";
		wphcx_clear_scan();
	}
		
	$list_table = new wphcx_table();
	$list_table->prepare_items();
	?>
		<?php wpscx_show_feature_window(); ?>
		<?php //wpscx_check_install_notice(); ?>
            
        <script>
            <?php if ($check_scan) { ?>
                var scan_in_progress = true;
            <?php } else { ?>
                var scan_in_progress = false;
            <?php } ?>
                var scanStartTime;
            
            function wphcx_recheck_scan_temp() {
                jQuery.ajax({
                        url: ajax_object.ajax_url,
                        type: "POST",
                        data: {
                                action: 'results_hc',
                        },
                        dataType: 'html',
                        success: function(response) {
                                if (response == 'true') { window.setInterval(wphcx_recheck_scan_temp(), 1000 ); console.log(response); }
                                else { wphcx_finish_scan_temp(); console.log(response); }
                        }
                });
            }

            function wphcx_finish_scan_temp() {
                jQuery.ajax({
                        url: ajax_object.ajax_url,
                        type: "POST",
                        data: {
                                action: 'finish_scan_hc',
                        },
                        dataType: 'html',
                        success: function(response) {
                             scan_in_progress = false;
                                window.location.href = encodeURI("?page=wp-spellcheck-html.php&wpsc-script=noscript");
                        }
                });
            }
            
            function wphcx_finish_scan_temp() {
                        jQuery.ajax({
                                url: ajax_object,
                                type: "POST",
                                data: {
                                        action: 'wpscx_display_results_html',
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

                                        wphcx_show_stats(scanFinal)
                                }
                        });
                }
                
                function wphcx_show_stats(x) {
                     jQuery.ajax({
                        url: ajax_object,
                        type: "POST",
                        data: {
                                action: 'wpscx_get_stats_code',
                                'scantime': x,
                        },
                        dataType: 'json',
                        success: function(response) {
                            //console.log(response);
                            jQuery('.sc-type').html("Errors found on <span style='color: rgb(0, 150, 255); font-weight: bold;'> Entire Site</span>: " + response.totalErrors);
                            if (Number(response.pageCount) >= Number(response.totalPages)) { jQuery('.sc-post').html("Posts scanned: " + response.totalPosts + "/" + response.totalPosts); } else { jQuery('.sc-post').html("Posts scanned: " + response.postCount + "/" + response.totalPosts); }
                            if (Number(response.pageCount) >= Number(response.totalPages)) { jQuery('.sc-page').html("Pages scanned: " + response.totalPages + "/" + response.totalPages); } else { jQuery('.sc-page').html("Pages scanned: " + response.pageCount + "/" + response.totalPages); }
                            jQuery('.sc-time').html("Last scan took " + response.scanTime);
                            jQuery('.next-page').click(function(e) {
                                e.preventDefault();
                                window.location.href = "?page=wp-spellcheck-html.php&paged=2";
                            });
                        },
                        error: function(xhr, status, thrownError) {
                            //console.log(thrownError);
                        }
                    });
                }           
                        
        </script>
		
	<style>input[type=submit]{border-radius:32px!important; box-shadow: none!important; text-shadow: none!important; border: none!important;}.search-box input[type=submit] { color: white; background-color: #00A0D2; border-color: #0073AA; } #cb-select-all-1,#cb-select-all-2 { display: none; } td.word { font-size: 15px; } p.submit { display: inline-block; margin-left: 8px; } h3.sc-message { width: 49%; display: inline-block; padding-left: 8px; font-weight: normal; } .wpsc-mouseover-text-page,.wpsc-mouseover-text-post,.wpsc-mouseover-text-refresh { color: black; font-size: 12px; width: 225px; display: inline-block; position: absolute; margin: -13px 0 0 -270px; padding: 3px; border: 1px solid black; border-radius: 10px; opacity: 0; background: white; z-index: -100; } .wpsc-row .row-actions, .wpsc-row .row-actions *{ visibility: visible!important; left: 0!important; } #current-page-selector { width: 12%; } .hidden { display: none; } .wpsc-scan-nav-bar { border-bottom: 1px solid #BBB; margin-botton: 15px; } .wpsc-scan-nav-bar a { text-decoration: none; margin: 5px 5px -1px 5px; padding: 8px; border: 1px solid #BBB; display: inline-block; font-weight: bold; color: black; font-size: 14px; } .wpsc-scan-nav-bar a.selected { border-bottom: 1px solid white; background: white; } #wpsc-empty-fields-tab .button-primary { background: #73019a; border-color: #51006E; text-shadow: 1px 1px #51006d; box-shadow: 0 1px 0 #51006d; } #wpsc-empty-fields-tab .button-primary:hover { background: #9100c3 } #wpsc-empty-fields-tab .button-primary:active { background: #51006d; }.wpsc-scan-buttons input#submit:active { margin-top: -7px; } #wphc-scan-results-tab .wp-list-table th { text-align: center; } .wpgc-desc .wpgc-desc-content { display: none; } .wpgc-desc-hover { display: block!important; position: relative; top: -125px; left: -55px; width: 125px; padding: 0px; margin: 0px; z-index: 100; height: 0px!important; } .wpgc-desc { position: absolute; bottom: 0px; margin-right: -4px; width: 125px; }
        #wpsc-table-results table { border-radius: 5px; box-shadow: 0px 0px 10px 0px rgb(0 0 0 / 50%); }
        .wpsc-button-greyout, .wpsc-button-greyout:hover { background: darkgrey!important }</style>
<div id="wpsc-dialog-confirm" title="Are you sure?" style="display: none;">
  <p>Would you like to Proceed with the changes?</p>
</div>
		<div class="wrap wpsc-table">
			<h2><a href="admin.php?page=wp-spellcheck-grammar.php"><img src="<?php echo esc_url(plugin_dir_url( __FILE__ )) . 'images/logo.png'; ?>" alt="WP Spell Check" /></a> <span style="position: relative; top: -8px;"> - Broken Code Scan Results</span></h2>
			<div class="wpsc-scan-nav-bar">
				<a href="/wp-admin/admin.php?page=wp-spellcheck.php" id="wpsc-scan-results" name="wpsc-scan-results">Spelling Errors</a>
				<a href="/wp-admin/admin.php?page=wp-spellcheck-grammar.php" id="wpsc-grammar" name="wpsc-grammar">Grammar</a>
				<a href="/wp-admin/admin.php?page=wp-spellcheck-seo.php" id="wpsc-empty-fields" name="wpsc-empty-fields">SEO Empty Fields</a>
				<a href="#" class="selected" id="wpsc-html" name="wpsc-html">Broken Code</a>
			</div>
			<?php if ($wpscx_ent_included) { ?>
			<div id="wphc-scan-results-tab" <?php if (isset($_GET['wpsc-scan-tab']) && $_GET['wpsc-scan-tab'] == 'empty') echo 'class="hidden"';?>>
			<form action="<?php echo esc_url(admin_url('admin.php')); ?>" method='GET'>
                            <div style="border-radius: 5px; box-shadow: 0px 0px 10px 0px rgb(0 0 0 / 50%); background: white;">
				<div class="wpsc-scan-buttons" style="padding-left: 8px; padding-top: 5px;">
                                    <h3 style="margin-bottom: 0px; padding-top: 10px;">Click on the buttons below to find the broken code errors and broken shortcodes on your site</h3>
				<h3 style="margin-bottom: 0px;">This function shows all the broken shortcodes and HTML code displaying on pages. </h3>
				<h3 style="margin-bottom: 0px;">Make sure you go to your <a href="/wp-admin/admin.php?page=wp-spellcheck-options.php">Options page</a> to set up automatic reports to be notified if broken code is found</h3>
				<h3 style="display: inline-block;">Scan:</h3>
				<p class="submit"><input style="background-color: #ffb01f; border-color: #ffb01f; box-shadow: 0px 1px 0px #ffb01f; text-shadow: 1px 1px 1px #ffb01f; font-weight: bold;" type="submit" name="submit" id="submit" class="button button-primary wpscScan wpscScanSite" value="Entire Site"></p>
				<p class="submit"><input style="background-color: #ffb01f; border-color: #ffb01f; box-shadow: 0px 1px 0px #ffb01f; text-shadow: 1px 1px 1px #ffb01f; font-weight: bold;" type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Broken HTML"></p>
				<p class="submit"><input style="background-color: #ffb01f; border-color: #ffb01f; box-shadow: 0px 1px 0px #ffb01f; text-shadow: 1px 1px 1px #ffb01f; font-weight: bold;" type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Broken Shortcodes"></p>
				<p class="submit" style="margin-left: -11px;"><span style="position: relative; left: 15px;"> - </span><img src="<?php echo esc_url(plugin_dir_url( __FILE__ )) . '../images/clear-results.png'; ?>" style="width: 20px; position: relative; top: 5px; left: 27px;" /><input type="submit" name="submit" id="submit" class="button button-primary" style="padding-left: 30px; background-color: red;" value="Clear Results"></p>
				<p class="submit" style="margin-left: -11px;"><img src="<?php echo esc_url(plugin_dir_url( __FILE__ )) . '../images/see-results.png'; ?>" style="width: 20px; position: relative; top: 5px; left: 26px;" /><input type="submit" name="submit" id="submit" class="button button-primary" style="padding-left: 30px; background-color: red;" value="See Scan Results"></p>
				<p class="submit" style="margin-left: -11px;"><img src="<?php echo esc_url(plugin_dir_url( __FILE__ )) . '../images/stop-scans.png'; ?>" style="width: 20px; position: relative; top: 5px; left: 25px;" /><input type="submit" name="submit" id="submit" class="button button-primary" style="padding-left: 30px; background-color: red;" value="Stop Scans"></p>
                                <p class="submit" style="margin-left: -11px;"><a href="/wp-admin/admin.php?page=wp-spellcheck-options.php" target="_blank"><img src="<?php echo esc_url(plugin_dir_url( __FILE__ )) . '../images/options.png'; ?>" title="Options" style="width: 30px; position: relative; top: 11px; left: 20px; padding: 0px; border-radius: 25px;" /></a></p>
				</div>
				<div style="padding: 5px; font-size: 12px;">
					<input type="hidden" name="page" value="wp-spellcheck-html.php">
					<input type="hidden" name="action" value="check">
					<?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);' id='wpscScanMessage'>" . esc_html($scan_message) . "</h3>"; ?>
                                        <?php echo "<h3 class='sc-message sc-time' style='color: rgb(0, 115, 0);'>Last scan took " . esc_html($options_list[27]->option_value) . "</h3><br />"; ?>
					<?php if ((($post_count + $page_count) > $max_pages) & $wpscx_ent_included) echo "<h3 class='sc-message' style='color: rgb(225, 0, 0);'>You have more than " . esc_attr($max_pages) . " Pages/Posts. <a href='https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=&utm_medium=bc_scan&utm_content=" . esc_attr($wpsc_version) . "' target='_blank'>Upgrade</a> to scan all of your website.</h3>" ?>
					<?php if (!$wpscx_ent_included) echo "<h3 class='sc-message' style='color: rgb(225, 0, 0);'><a href='https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgradeBroken_code&utm_medium=bc_scan&utm_content=" . esc_attr($wpsc_version) . "' target='_blank'>Upgrade</a> to scan all parts of your website.</h3>"; ?>
				</div>
                            </div>
			</form>
			<?php include("sidebar.php"); ?>
			<form id="words-list" method="get" style="width: 75%; float: left; margin-top: 10px;">
				<p class="search-box" style="position: relative; margin-top: 0.5em;">
					<label class="screen-reader-text" for="search_id-search-input">search:</label>
					<input type="search" id="search_id-search-input-top" name="s-top" value="" placeholder="Search for Page Names">
					<input type="submit" id="search-submit-top" class="button" value="search">
				</p>
				<input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']) ?>" />
				<?php 
	
	
	
	 ?>
                                <div id="wpsc-table-results">
                                    <?php $list_table->display() ?>
                                </div>
				<?php 
	
	
	
	 ?>
				<p class="search-box" style="margin-top: 0.7em;">
					<label class="screen-reader-text" for="search_id-search-input">search:</label>
					<input type="search" id="search_id-search-input" name="s" value="" placeholder="Search for Page Names">
					<input type="submit" id="search-submit" class="button" value="search">
				</p>
			</form>
			<div style="padding: 15px; background: white;  clear: both; width: 72%; font-family: helvetica; border-radius: 5px; box-shadow: 0px 0px 10px 0px rgb(0 0 0 / 50%);">
				<?php echo "<h3 class='sc-message sc-type' style='color: rgb(0, 115, 0);'>Errors found on <span style='color: rgb(0, 150, 255); font-weight: bold;'>Entire Site</span>: " . esc_html($error_count) . "</h3>"; ?>
				<?php echo "<h3 class='sc-message sc-page' style='color: rgb(0, 115, 0);'>Pages scanned: " . esc_html($options_list[143]->option_value) . "/" . esc_html($page_count) . "</h3>"; ?>
				<?php echo "<h3 class='sc-message sc-post' style='color: rgb(0, 115, 0);'>Posts scanned: " . esc_html($post_scan_count) . "/" . esc_html($total_posts) . "</h3>"; ?>
			</div>
		</div>
		<?php } else { ?>
			<?php if ($pro_error_msg == "") { ?>
				<?php include("sidebar.php"); ?>
				<form action="<?php echo esc_url(admin_url('admin.php')); ?>" method='GET' style="width: 75%; float: left; margin-top: 10px;">
				<input type="hidden" name="page" value="wp-spellcheck-html.php">
				<input type="hidden" name="action" value="check">
				<h3>Click the button below to find out how many broken code errors are on your site</h3>
				<p class="submit" style="margin: 0px;"><input type="submit" name="submit" id="submit" class="button button-primary" value="Scan Site"></p>
				</form>
			<?php } else {
				echo htmlspecialchars_decode(esc_html($pro_error_msg));
			} ?>
			<h3><a href="https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgradeBroken_code&utm_medium=bc_scan&utm_content=<?php echo esc_attr($wpsc_version); ?>" target="_blank">Upgrade to pro</a> to find broken HTML and broken Shortcodes on your website.</h3>
			<h3 style="color: red;">Examples</h3>
			<h4>Broken Shortcode</h4>
                        <div>Shortcodes may show up on your pages if a plugin was deactivated. It will increase the bounce rate on your website and hurt your SEO.<br>Example:</div>
			<div>[broken_shortcode setting=1]</div>
			<h4>Broken HTML</h4>
                        <div>When HTML tags are not closed properly, HTML code could be displayed on the output of your pages. This will also increase the bounce rate on your website and hurt your SEO.<br>Example:</div>
			<div>&lt;h1&gt;Broken Header Title&lt;/h1&gt;
                            <h3><a href="https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgradeBroken_code&utm_medium=bc_scan&utm_content=<?php echo esc_attr($wpsc_version); ?>" target="_blank">Upgrade to pro</a> to find and fix the errors. You will also get notified when errors show up on your website.</h3>
		<?php } ?>
		</div>
                        <script>
                            jQuery('.wpscScan').click(function(event) {
                event.preventDefault();
                if (scan_in_progress) return;
                scan_in_progress = true;
                ajax_object = '<?php echo esc_url(admin_url( 'admin-ajax.php' )) ?>';
                var scanType = jQuery(this).attr('value');
                
                jQuery('#wpscScanMessage').html('<img src="<?php echo esc_url(plugin_dir_url( __FILE__ )); ?>images/loading.gif" alt="Scan in Progress" /> Starting New Scan');
                jQuery('.wpscScan').addClass('wpsc-button-greyout'); //Greyout buttons
                
                var scanTime = new Date();
                scanStartTime = scanTime.getTime();

                jQuery.ajax({
                        url: ajax_object,
                        type: "POST",
                        data: {
                                type: scanType,
                                action: 'wpscx_start_scan_bc',
                        },
                        dataType: 'html',
                        success: function(response) {
                            var scanTime = new Date();
                            var scanEndTime = scanTime.getTime();
                            var scanFinal = ( scanEndTime - scanStartTime) / 1000;
                            //console.log("Scan Time(start):" + scanFinal);
                            
                            
                            jQuery('#wpscScanMessage').html(response); //update the scan message to display the scan started message
                            window.setInterval(wphcx_finish_scan_temp(), 500 );
                            jQuery('tr.wpsc-row').animate({opacity: 0}, 500, function() { jQuery('tr.wpsc-row').hide(); })
                            
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
                        }
                });
            });
                        </script>
	<?php 
	}
	
	
	
	
?>