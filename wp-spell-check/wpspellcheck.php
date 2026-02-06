<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/*
Plugin Name: WP Spell Check
Description: The Fastest Proofreading plugin that allows you to find & fix Spelling errors, Grammar errors, Broken HTML & Shortcodes and, SEO Opportunities to Create a professional image and take your site to the next level
Version: 9.22
Author: WP Spell Check
Requires at least: 6.3
Tested up to: 6.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Copyright: © 2026 WP Spell Check
Contributors: wpspellcheck
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
const WPSC_FRAMEWORK  = 'wpsc-framework.php';
const WPSC_ADMIN_AJAX = 'admin-ajax.php';

function wpscx_core() {
	if ( ! current_user_can( 'administrator' ) && ! current_user_can( 'editor' ) && ! current_user_can( 'author' ) && ! current_user_can( 'contributor' ) ) {
		return;
	}
	$wpsc_user_id = get_current_user_id();
	$database     = new Wpscx_Database();
	$database->wpsc_update_db_check_main();
	wpscx_load_plugin();

	if ( isset( $_POST['export'] ) && 'Export Plugin Data' === $_POST['export'] ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have permission to export plugin data.', 403 );
		}
		check_admin_referer( 'wpsc_export' );
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
	// phpcs:ignore WordPress.Security.NonceVerification -- Nonce verified in calling function wpscx_core() at line 65
	if ( isset( $_POST['export-dict'] ) && 'true' === $_POST['export-dict'] ) {
		$export_dict = true;
	} else {
		$export_dict = false;
	}
	// phpcs:ignore WordPress.Security.NonceVerification -- Nonce verified in calling function wpscx_core() at line 65
	if ( isset( $_POST['export-ignore'] ) && 'true' === $_POST['export-ignore'] ) {
		$export_ignore = true;
	} else {
		$export_ignore = false;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'spellcheck_options'. Query contains no user input (hardcoded WHERE clause).
	$options_output = $wpdb->get_results( 'SELECT * FROM ' . $wpsc_options_tbl . ' WHERE option_name NOT LIKE "%API%" AND option_name NOT LIKE "%count%" AND option_name NOT LIKE "%scan%" AND option_name NOT LIKE "%checked%" AND option_name NOT LIKE "%type%" AND option_name NOT LIKE "%sip%" AND option_name NOT LIKE "%factor%" AND option_name NOT LIKE "%pro_max%" AND option_name NOT LIKE "%html_%" AND option_name NOT LIKE "%time%";' );
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'spellcheck_grammar_options'. Query contains no user input (hardcoded WHERE clause).
	$grammar_output = $wpdb->get_results( 'SELECT * FROM ' . $wpsc_grammar_tbl . ' WHERE option_name NOT LIKE "%API%" AND option_name NOT LIKE "%count%" AND option_name NOT LIKE "%scan%" AND option_name NOT LIKE "%checked%" AND option_name NOT LIKE "%type%" AND option_name NOT LIKE "%sip%" AND option_name NOT LIKE "%factor%" AND option_name NOT LIKE "%pro_max%" AND option_name NOT LIKE "%html_%" AND option_name NOT LIKE "%time%";' );
	if ( $export_dict ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string. Query contains no user input.
		$dict_output = $wpdb->get_results( 'SELECT * FROM ' . esc_sql( $wpsc_dict_tbl ) );
	}
	if ( $export_ignore ) {
		$ignore_output = $wpdb->get_results( "SELECT * FROM $words_table WHERE ignore_word=true" );
	}

	$output = '';

	$output .= "[wpsc_settings]\r\n";

	foreach ( $options_output as $option ) {
		$output .= $option->option_name . '=' . $option->option_value . "\r\n";
	}
	unset( $options_output );

	$output .= "\r\n[wpsc_grammar]\r\n";

	foreach ( $grammar_output as $option ) {
		$output .= $option->option_name . '=' . $option->option_value . "\r\n";
	}
	unset( $grammar_output );

	$output .= "\r\n[wpsc_dictionary]\r\n";

	if ( isset( $dict_output ) ) {
		foreach ( $dict_output as $dict ) {
			$output .= $dict->word . "\r\n";
		}
		unset( $dict_output );
	}

	$output .= "\r\n[wpsc_ignore]\r\n";

	if ( isset( $ignore_output ) ) {
		foreach ( $ignore_output as $ignore ) {
			$output .= $ignore->word . "\r\n";
		}
		unset( $ignore_output );
	}

	header( 'Content-Type: application/octet-stream' );
	header( 'Content-Disposition: attachment; filename=wpsc-data.ini' );
	header( 'Cache-Control: no-cache, no-store, must-revalidate' );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- File download content, not HTML output
	echo $output;

	echo PHP_EOL;

	exit();
}

function wpscx_enqueue_global_admin_styles() {
	// Enqueue CSS early to prevent FOUC
	global $wpsc_version;
	if ( current_user_can( 'administrator' ) || current_user_can( 'editor' ) || current_user_can( 'author' ) || current_user_can( 'contributor' ) ) {
		wp_enqueue_style( 'global-admin-styles', plugin_dir_url( __FILE__ ) . 'css/global-admin-styles.css', array(), $wpsc_version );
	}
}
add_action( 'admin_enqueue_scripts', 'wpscx_enqueue_global_admin_styles', 1 );

/**
 * Enqueue styles for Network Uninstall page
 *
 * @since 9.22
 */
function wpscx_enqueue_uninstall_page_styles() {
	global $wpsc_version;
	$screen = get_current_screen();
	if ( $screen && ( isset( $_GET['page'] ) && 'wpsc_uninstall_page' === $_GET['page'] ) ) {
		wp_enqueue_style( 'wpsc-uninstall-page', plugin_dir_url( __FILE__ ) . 'admin/css/uninstall-page.css', array(), $wpsc_version );
	}
}
add_action( 'admin_enqueue_scripts', 'wpscx_enqueue_uninstall_page_styles', 1 );

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
			include __DIR__ . '-pro/pro-loader.php';
		}
		return;
	}

	require_once 'admin/class-wpsc-admin.php';
	$wpscx = new Wpscx_Admin();

	if ( is_plugin_active( 'wp-spell-check-pro/wpspellcheckpro.php' ) ) {
		include __DIR__ . '-pro/pro-loader.php';
	}
	// Show activation notice only on Plugins page. Option is unset (false) or '' right after activation; after showing we set it so it does not show again.
	// phpcs:ignore WordPress.Security.NonceVerification -- Not processing form data, only checking absence of $_POST['uninstall']
	$wpsc_acti = get_option( 'wpsc_data_acti' );
	if ( ( false === $wpsc_acti || '' === $wpsc_acti ) && current_user_can( 'administrator' ) && ! isset( $_POST['uninstall'] ) && ! isset( $_GET['action'] ) && isset( $_GET['plugin_status'] ) && 'all' === $_GET['plugin_status'] ) {
		add_action( 'admin_head', array( 'wpscx_banner', 'show_install_notice' ) );
		update_option( 'wpsc_data_acti', array() );
	}

	if ( is_admin() ) {
		new WpscxDeactivation();
	}
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

	$wpsc_version = '9.22';

	$wpscx_ignore_list = array();
	$wpscx_dict_list   = array();
	$wpgc_settings     = array();

	// $test_var = 'Test successful';

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
		$wpscx_base_page_max = 10;
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
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have permission to uninstall the plugin.', 403 );
		}
		check_admin_referer( 'wpsc_network_uninstall' );
		global $wpdb;

		if ( class_exists( 'Wpscx_Options' ) ) {
			$options = new Wpscx_Options();
			$options->prepare_uninstall();
		}
		deactivate_plugins( 'wp-spell-check/wpspellcheck.php' );
		if ( $wpscx_ent_included ) {
			deactivate_plugins( 'wp-spell-check-pro/wpspellcheckpro.php' );
		}
		wp_die( 'WP Spell Check has been deactivated. If you wish to use the plugin again you may activate it on the WordPress plugin page' );
	}

	?>
	<h2><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'images/logo.png'; ?>" alt="WP Spell Check" /> <span
			class="wpsc-uninstall-title">Network Uninstall</span></h2>
	<p>This will deactivate WP Spell Check on all sites on the network and clean up the database of any changes made by WP
		Spell Check. If you wish to use WP Spell Check again after, you may activate it on the WordPress plugins page</p>
	<form action="settings.php?page=wpsc_uninstall_page" method="post" name="uninstall">
		<?php wp_nonce_field( 'wpsc_network_uninstall' ); ?>
		<input type="submit" name="uninstall" value="Clean up Database and Deactivate Plugin" />
	</form>
	<?php
}

function wpscx_cron_add_custom( $schedules ) {
	global $wpdb;
	wpscx_set_global_vars();
	$table_name = $wpdb->prefix . 'spellcheck_options';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string. Query contains no user input.
	$check_db = $wpdb->get_results( "SHOW TABLES LIKE '" . esc_sql( $table_name ) . "'" );
	if ( sizeof( $check_db ) !== 0 ) {
		if ( ! isset( $_POST['scan_frequency_interval'] ) && ! isset( $_POST['scan_frequency'] ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'spellcheck_options'. Query contains no user input (hardcoded WHERE clause).
			$scan_frequency = $wpdb->get_results( 'SELECT option_value FROM ' . $table_name . ' WHERE option_name="scan_frequency";' );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'spellcheck_options'. Query contains no user input (hardcoded WHERE clause).
			$scan_frequency_interval = $wpdb->get_results( 'SELECT option_value FROM ' . $table_name . ' WHERE				option_name="scan_frequency_interval";' );
			$scan_interval           = $scan_frequency_interval[0]->option_value;
			$scan_timer              = intval( $scan_frequency[0]->option_value );
		} else {
			// Verify nonce before processing POST data
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce must be passed raw to wp_verify_nonce(); sanitizing would break verification.
			$nonce_received = isset( $_POST['_wpnonce'] ) ? wp_unslash( $_POST['_wpnonce'] ) : '';
			$nonce_valid    = wp_verify_nonce( $nonce_received, 'wpsc_update_options' );

			if ( $nonce_valid ) {
				// phpcs:ignore WordPress.Security.NonceVerification -- Nonce verified above
				$scan_interval = sanitize_text_field( wp_unslash( $_POST['scan_frequency_interval'] ) );
				// phpcs:ignore WordPress.Security.NonceVerification -- Nonce verified above
				$scan_timer = sanitize_text_field( intval( $_POST['scan_frequency'] ) );
			} else {
				// Nonce invalid or missing - fall back to database values
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'spellcheck_options'. Query contains no user input (hardcoded WHERE clause).
				$scan_frequency = $wpdb->get_results( 'SELECT option_value FROM ' . $table_name . ' WHERE option_name="scan_frequency";' );
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'spellcheck_options'. Query contains no user input (hardcoded WHERE clause).
				$scan_frequency_interval = $wpdb->get_results( 'SELECT option_value FROM ' . $table_name . ' WHERE				option_name="scan_frequency_interval";' );
				$scan_interval           = $scan_frequency_interval[0]->option_value;
				$scan_timer              = intval( $scan_frequency[0]->option_value );
			}
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
			'display'  => __( 'wpsc', 'wp-spell-check' ),
		);
	}
	return $schedules;
}
add_filter( 'cron_schedules', 'wpscx_cron_add_custom' );

function wpscx_add_premium_link( $links ) {
	global $wpsc_version;

	$settings_link = '<a href="https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgradePlugins_Page&utm_medium=plugin_page&utm_content=' . $wpsc_version . '" target="_blank">' . __( 'Premium Features', 'wp-spell-check' ) . '</a>';
	array_push( $links, $settings_link );
	return $links;
}

function wpscx_add_settings_link( $links ) {
	$settings_link = '<a href="admin.php?page=wp-spellcheck-options.php">' . __( 'Settings', 'wp-spell-check' ) . '</a>';
	array_push( $links, $settings_link );
	return $links;
}

$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'wpscx_add_premium_link' );
add_filter( "plugin_action_links_$plugin", 'wpscx_add_settings_link' );
?>