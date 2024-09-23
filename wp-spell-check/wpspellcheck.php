<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; }
	/*
	Plugin Name: WP Spell Check
	Description: The Fastest Proofreading plugin that allows you to find & fix Spelling errors, Grammar errors, Broken HTML & Shortcodes and, SEO Opportunities to Create a professional image and take your site to the next level
	Version: 9.21
	Author: WP Spell Check
	Requires at least: 5.0
	Tested up to: 6.6
	Stable tag: 9.21
	License: GPLv2 or later
	License URI: http://www.gnu.org/licenses/gpl-2.0.html
	Copyright: © 2021 WP Spell Check
	Contributors: wpspellcheck
	Donate Link: www.wpspellcheck.com
	Donate Link: www.wpspellcheck.com
	Tags: spelling, SEO, Spell Check, WordPress spell check, Spell Checker, WordPress spell checker, spelling errors, spelling mistakes, spelling report, fix spelling, WP Spell Check

	Author URI: https://www.wpspellcheck.com

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
	Pro Add-on / Prices: https://www.wpspellcheck.com/pricing/
	*/

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        require_once 'admin/class-wpsc-database.php';
        register_activation_hook( __FILE__, array( 'wpscx_database', 'wpsc_install_spellcheck_main' ) );
        const WPSC_FRAMEWORK = 'wpsc-framework.php';
        const WPSC_ADMIN_AJAX = 'admin-ajax.php';

function wpscx_core() {
    if ( !current_user_can( 'administrator' ) && !current_user_can( 'editor' ) && !current_user_can( 'author' ) && !current_user_can( 'contributor' ) ) { return; }
	$wpsc_user_id = get_current_user_id();
	$database     = new Wpscx_Database();
	$database->wpsc_update_db_check_main();
	wpscx_load_plugin();

	if ( isset( $_POST['export'] ) && 'Export Plugin Data' === $_POST['export'] ) {
		add_action( 'admin_init', 'wpscx_export_options' );
	}
}
		add_action( 'init', 'wpscx_core' );

function wpscx_export_options( $dictionary = false, $ignore = false ) {
	global $wpdb;
	$wpsc_options_tbl = $wpdb->prefix . 'spellcheck_options';
	$wpsc_grammar_tbl = $wpdb->prefix . 'spellcheck_grammar_options';
	$wpsc_dict_tbl    = $wpdb->prefix . 'spellcheck_dictionary';
	$words_table      = $wpdb->prefix . 'spellcheck_words';
	ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit
	if ( isset( $_POST['export-dict'] ) && 'true' === $_POST['export-dict'] ) {
		$export_dict = true;
	} else {
		$export_dict = false; }
	if ( isset( $_POST['export-ignore'] ) && 'true' === $_POST['export-ignore'] ) {
		$export_ignore = true;
	} else {
		$export_ignore = false; }

	$options_output = $wpdb->get_results( 'SELECT * FROM ' . $wpsc_options_tbl . ' WHERE option_name NOT LIKE "%API%" AND option_name NOT LIKE "%count%" AND option_name NOT LIKE "%scan%" AND option_name NOT LIKE "%checked%" AND option_name NOT LIKE "%type%" AND option_name NOT LIKE "%sip%" AND option_name NOT LIKE "%factor%" AND option_name NOT LIKE "%pro_max%" AND option_name NOT LIKE "%html_%" AND option_name NOT LIKE "%time%";' );
	$grammar_output = $wpdb->get_results( 'SELECT * FROM ' . $wpsc_grammar_tbl . ' WHERE option_name NOT LIKE "%API%" AND option_name NOT LIKE "%count%" AND option_name NOT LIKE "%scan%" AND option_name NOT LIKE "%checked%" AND option_name NOT LIKE "%type%" AND option_name NOT LIKE "%sip%" AND option_name NOT LIKE "%factor%" AND option_name NOT LIKE "%pro_max%" AND option_name NOT LIKE "%html_%" AND option_name NOT LIKE "%time%";' );
	if ( $export_dict ) {
		$dict_output = $wpdb->get_results( "SELECT * FROM $wpsc_dict_tbl" );
	}
	if ( $export_ignore ) {
		$ignore_output = $wpdb->get_results( "SELECT * FROM $words_table WHERE ignore_word=true" );
	}

	$loc    = dirname( __FILE__ ) . '/wpsc-data.ini';
	$output = fopen( $loc, 'w' );

	fwrite( $output, "[wpsc_settings]\r\n" );

	foreach ( $options_output as $option ) {
			fwrite( $output, $option->option_name . '=' . $option->option_value . "\r\n" );
	}
	unset( $options_output );

	fwrite( $output, "\r\n[wpsc_grammar]\r\n" );

	foreach ( $grammar_output as $option ) {
			fwrite( $output, $option->option_name . '=' . $option->option_value . "\r\n" );
	}
	unset( $grammar_output );

	fwrite( $output, "\r\n[wpsc_dictionary]\r\n" );

	if ( isset($dict_output) ) {
		foreach ( $dict_output as $dict ) {
					fwrite( $output, $dict->word . "\r\n" );
		}
			unset( $dict_output );
	}

	fwrite( $output, "\r\n[wpsc_ignore]\r\n" );

	if ( isset( $ignore_output ) ) {
		foreach ( $ignore_output as $ignore ) {
					fwrite( $output, $ignore->word . "\r\n" );
		}
			unset( $ignore_output );
	}

	fclose( $output );

	header( 'Content-Type: application/octet-stream' );
	header( 'Content-Disposition: attachment; filename=wpsc-data.ini' );
	header( 'Cache-Control: no-cache, no-store, must-revalidate' );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	readfile( dirname( __FILE__ ) . '/wpsc-data.ini' );

	echo PHP_EOL;

	exit();
}

function wpsc_safe_mode() {
	global $wp_version;
	$wpsc_user_id = get_current_user_id();
	global $wpscx_ent_included;

	$error = get_user_meta( $wpsc_user_id, 'wpsc_error_msg', true );

	?>
				<style>
					#wpsc-error-report  { display: block; width: 330px; height: 165px; position: fixed; left: calc(50% - 150px); top: calc(50% - 150px); border: 2px solid black; border-radius: 15px; background: white; z-index: 999999; padding: 15px; }
					#wpsc-error-confirm { display: block; width: 330px; height: 200px; position: fixed; left: calc(50% - 150px); top: calc(50% - 150px); border: 2px solid black; border-radius: 15px; background: white; z-index: 999999; padding: 15px; }
					#wpsc-error-report button, #wpsc-error-confirm .wpsc-error-confirm-contact { text-decoration: none; background: #008200; color: white; padding: 5px 20px; border-radius: 15px; position: absolute; bottom: 10px; }
					#wpsc-error-report a, #wpsc-error-confirm .wpsc-error-confirm-dismiss { text-decoration: none; color: grey; padding: 5px 20px; position: absolute; bottom: 10px; }
				</style>
				<script type='text/javascript'>
					jQuery(document).ready(function() {
						var wpsc_popup = jQuery('#wpsc-error-report');
						var wpsc_form = wpsc_popup.find('form');
						wpsc_form.submit(function(event) { 
							event.preventDefault();

							var form_data = {
									error: <?php echo json_encode( utf8_encode( esc_html( $error ) ) ); ?>,
									site: '<?php echo esc_url( home_url() ); ?>',
									wordpress_ver: '<?php echo esc_html( $wp_version ); ?>',
									php_ver: '<?php echo esc_html( phpversion() ); ?>',
									theme_name: '<?php echo esc_html( wp_get_theme()->name ); ?>',
									parent_name: '<?php echo esc_html( wp_get_theme()->parent()->name ); ?>',
									plugin_ver: '<?php echo '9.21'; ?>',
									error_code: '2'
							};

							var submit = jQuery.post('https://www.wpspellcheck.com/api/error-report.php', form_data);
							submit.always(function() {
									location.href = '<?php echo html_entity_decode( esc_url( add_query_arg( array( 'wpsc_dismiss_error' => '1' ) ) ) ); ?>';
							});
						});
					});
				</script>
				<div id="wpsc-error-report">
					<form class="wpsc_error_form">
						<h3 style='text-align: center;'>Ooops!</h3>
						<p style='text-align: center;'>An error has occurred with WP Spell Check. Click on the Green button below to send us the error report so we can fix it.<br>Thank you.</p>
						<button type="submit" class="wpsc-error-report-send" href="<?php echo html_entity_decode( esc_url( add_query_arg( array( 'wpsc_dismiss_error' => '1' ) ) ) ); ?>" style="left: 10px;">Send Report</button>
						<a class="wpsc-error-report-dismiss" href="<?php echo html_entity_decode( esc_url( add_query_arg( array( 'wpsc_dismiss_error' => '2' ) ) ) ); ?>" style="right: 10px;">Dismiss</a>
					</form>
				</div>
			<?php
			delete_user_meta( $wpsc_user_id, 'wpsc_safe_mode' );
			delete_user_meta( $wpsc_user_id, 'wpsc_error_msg' );
			deactivate_plugins( 'wp-spell-check/wpspellcheck.php' );
			deactivate_plugins( 'wp-spell-check-pro/wpspellcheckpro.php' );
}

function wpscx_load_plugin() {
	if ( ! ( current_user_can( 'administrator' ) || current_user_can( 'editor' ) || current_user_can( 'author' ) || current_user_can( 'contributor' ) ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-includes/pluggable.php';
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

		require_once 'admin/wpsc-framework.php';
		require_once 'admin/class-wpsc-scanner.php';
		require_once 'admin/class-wpsc-email.php';
		require_once 'admin/wpsc-empty.php';
		require_once 'admin/grammar/grammar_framework.php';
		require_once 'admin/grammar/class-wpsc-grammar.php';
		require_once 'admin/class-wpsc-spellcheck.php';
		require_once 'admin/class-wpsc-seo.php';
                require_once 'admin/class-wpsc-options.php';

		if ( is_plugin_active( 'wp-spell-check-pro/wpspellcheckpro.php' ) ) {
			$pro_data = get_plugin_data( dirname( __FILE__ ) . '-pro/wpspellcheckpro.php' );
			$pro_ver  = $pro_data['Version'];
			if ( 0 === version_compare( $pro_ver, '9.21' ) ) {
						include dirname( __FILE__ ) . '-pro/pro-loader.php';
			}
		}
		return;
	}

	require_once 'admin/class-wpsc-admin.php';
	$wpscx = new Wpscx_Admin();

	if ( is_plugin_active( 'wp-spell-check-pro/wpspellcheckpro.php' ) ) {
		$pro_data = get_plugin_data( dirname( __FILE__ ) . '-pro/wpspellcheckpro.php' );
		$pro_ver  = $pro_data['Version'];
		if ( 0 === version_compare( $pro_ver, '9.21' ) ) {
					include dirname( __FILE__ ) . '-pro/pro-loader.php';
		}
	}
	if ( current_user_can( 'administrator' ) || current_user_can( 'editor' ) || current_user_can( 'author' ) || current_user_can( 'contributor' ) ) {
		wp_enqueue_style( 'global-admin-styles', plugin_dir_url( __FILE__ ) . 'css/global-admin-styles.css' );
	}
        if ( '' === get_option( 'wpsc_data_acti' ) && current_user_can( 'administrator' ) && !isset( $_POST['uninstall'] ) && !isset( $_GET['action'] ) && 'all' === $_GET['plugin_status'] ) {
		add_action( 'admin_head', array( 'wpscx_banner', 'show_install_notice' ) );
		update_option( 'wpsc_data_acti', array() );
	}

	if ( is_admin() ) {
		new WpscxDeactivation();
	}

	// Sample errors for testing safe mode - Call to Undfined Function and Call to Function on Null
	// test_function_error();
	// $test = null;
	// $test->get_id();

	// Sample errors for testing error catching - Object cannot be converted
	// print wpscx_error_test();
}

function wpscx_set_global_vars() {
	global $wpdb;
	global $wpscx_ignore_list;
	global $wpscx_dict_list;
	global $wpsc_settings;
	global $wpgc_settings;
	global $check_opt;
	global $wpsc_haystack;
	global $wpscx_base_page_max;
	global $wpscx_ent_included;
			global $wpsc_version;

			$wpsc_version = '9.21';

	$wpscx_ignore_list = array();
	$wpscx_dict_list   = array();
	$wpgc_settings     = array();

	$test_var = 'Test successful';

	$words_table           = $wpdb->prefix . 'spellcheck_words';
	$options_table         = $wpdb->prefix . 'spellcheck_options';
	$grammar_options_table = $wpdb->prefix . 'spellcheck_grammar_options';
	$ignore_table          = $wpdb->prefix . 'spellcheck_ignore';
	$dict_table            = $wpdb->prefix . 'spellcheck_dictionary';

	$check_opt  = $wpdb->get_results( "SHOW TABLES LIKE '$options_table'" );
	$check_word = $wpdb->get_results( "SHOW TABLES LIKE '$words_table'" );
	$check_ig   = $wpdb->get_results( "SHOW TABLES LIKE '$ignore_table'" );
	$check_dict = $wpdb->get_results( "SHOW TABLES LIKE '$dict_table'" );
	$check_grm  = $wpdb->get_results( "SHOW TABLES LIKE '$grammar_options_table'" );

	if ( ! isset( $wpsc_settings ) && 0 < sizeof( $check_opt ) ) {
		$wpsc_settings_temp = $wpdb->get_results( "SELECT * FROM $options_table" );
		if ( isset( $wpsc_settings_temp ) && sizeof( $wpsc_settings_temp ) > 0 ) {
			$wpsc_settings = new SplFixedArray( sizeof( $wpsc_settings_temp ) + 1 );
			for ( $x = 0; $x < sizeof( $wpsc_settings_temp ); $x++ ) {
								$wpsc_settings[ $x ] = $wpsc_settings_temp[ $x ];
			}
						unset( $wpsc_settings_temp );
		}
	}

	if ( sizeof( (array) $wpsc_settings ) < 1 ) {

		if ( sizeof( $check_opt ) !== 0 && sizeof( $check_word ) !== 0 && sizeof( $check_ig ) !== 0 && sizeof( $check_dict ) !== 0 ) {
			$wpscx_ignore_list = $wpdb->get_results( "SELECT word FROM $words_table WHERE ignore_word = true" );
			$wpscx_dict_list   = $wpdb->get_results( "SELECT word FROM $dict_table" );
			$wpgc_settings     = $wpdb->get_results( "SELECT * FROM $grammar_options_table" );
		}
	}

	if ( $wpscx_ent_included ) {
		if ( isset( $wpsc_settings[138] ) ) {
			$wpscx_base_page_max = $wpsc_settings[138]->option_value;
		}
	} else {
		$wpscx_base_page_max = 25;
	}

			// Sample error for testing safe mode - Out of Index on Fixed Array
			// $wpsc_settings[999] = "Test Error";
}

		global $scdb_version;
		global $wpscx_scan_delay;
		$wpscx_scan_delay = 0;
		$scdb_version     = '1.0';
		wpscx_set_global_vars();

	/*
	 Initialization Code */
		 /*Create Network Page*/
function wpscx_uninstall_page() {
    global $wpscx_ent_included;
	if ( isset( $_POST['uninstall'] ) && 'Uninstall' === $_POST['uninstall'] ) {
				check_admin_referer( 'wpsc_network_uninstall' );
				global $wpdb;
                                
				prepare_uninstall();
				deactivate_plugins( 'wp-spell-check/wpspellcheck.php' );
		if ( $wpscx_ent_included ) {
			deactivate_plugins( 'wp-spell-check-pro/wpspellcheckpro.php' );
		}
				wp_die( 'WP Spell Check has been deactivated. If you wish to use the plugin again you may activate it on the WordPress plugin page' );
	}

	?>
				<h2><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'images/logo.png'; ?>" alt="WP Spell Check" /> <span style="position: relative; top: -15px;">Network Uninstall</span></h2>
				<p>This will deactivate WP Spell Check on all sites on the network and clean up the database of any changes made by WP Spell Check. If you wish to use WP Spell Check again after, you may activate it on the WordPress plugins page</p>
				<form action="settings.php?page=wpsc_uninstall_page" method="post" name="uninstall">
				<?php wp_nonce_field( 'wpsc_network_uninstall' ); ?>
						<input type="submit" name="uninstall" value="Clean up Database and Deactivate Plugin" />
				</form>
				<?php
}

function wpscx_cron_add_custom() {
	global $wpdb;
	wpscx_set_global_vars();
	$table_name = $wpdb->prefix . 'spellcheck_options';
	
        $check_db = $wpdb->get_results( "SHOW TABLES LIKE '$table_name'" );
	if ( sizeof( $check_db ) !== 0 ) {
            if ( ! isset( $_POST['scan_frequency_interval'] ) && ! isset( $_POST['scan_frequency'] ) ) {
            $scan_frequency          = $wpdb->get_results( 'SELECT option_value FROM ' . $table_name . ' WHERE option_name="scan_frequency";' );
            $scan_frequency_interval = $wpdb->get_results( 'SELECT option_value FROM ' . $table_name . ' WHERE				option_name="scan_frequency_interval";' );
            $scan_interval           = $scan_frequency_interval[0]->option_value;
            $scan_timer              = intval( $scan_frequency[0]->option_value );
            } else {
                    $scan_interval = sanitize_text_field( $_POST['scan_frequency_interval'] );
                    $scan_timer    = sanitize_text_field( intval( $_POST['scan_frequency'] ) );
            }

            switch ( $scan_interval ) {
                    case 'hourly':
                                            $scan_recurrence = $scan_timer * 3600;
                            break;
                    case 'daily':
                                                    $scan_recurrence = $scan_timer * 86400;
                            break;
                    case 'weekly':
                            $scan_recurrence = $scan_timer * 604800;
                            break;
                    case 'monthly':
                            $scan_recurrence = $scan_timer * 2592000;
                            break;
                    default:
                            $scan_recurrence = 604800;
            }

            $schedules['wpsc'] = array(
                    'interval' => $scan_recurrence,
                    'display'  => __( 'wpsc' ),
            );
	}
			return $schedules;
}
	add_filter( 'cron_schedules', 'wpscx_cron_add_custom' );

        function wpscx_add_premium_link( $links ) {
		global $wpsc_version;

		$settings_link = '<a href="https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgradePlugins_Page&utm_medium=plugin_page&utm_content=' . $wpsc_version . '" target="_blank">' . __( 'Premium Features' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	function wpscx_add_settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=wp-spellcheck-options.php">' . __( 'Settings' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}
        
        $plugin = plugin_basename( __FILE__ );
        add_filter( "plugin_action_links_$plugin", 'wpscx_add_premium_link' );
	add_filter( "plugin_action_links_$plugin", 'wpscx_add_settings_link' );
?>
