<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Plugin Name: WP Spell Check
 * Description: The fastest proofreading plugin that allows you to find & fix spelling errors, grammar errors, broken HTML & shortcodes and SEO opportunities to create a professional image and take your site to the next level.
 * Version: 11.3
 * Author: WP Spell Check
 * Author URI: https://www.wpspellcheck.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * 
 * Tested up to: 7.0
 */

const WPSC_FRAMEWORK  = 'wpsc-framework.php';
const WPSC_ADMIN_AJAX = 'admin-ajax.php';

/**
 * Activation callback: load DB/upgrade only when activating so they are not loaded on frontend.
 *
 * @since 10.0
 */
function wpscx_activation_install() {
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/class-wpsc-database.php';
	Wpscx_Database::wpsc_install_spellcheck_main();
}
register_activation_hook( __FILE__, 'wpscx_activation_install' );

/**
 * Ensure requests to wp-cron.php (loopback / Site Health) do not time out on slow servers.
 * Only registered in admin or during cron to avoid frontend work.
 *
 * @since 9.22
 */
add_action(
	'init',
	function () {
		if ( ! is_admin() && ! ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return;
		}
		add_filter(
			'http_request_args',
			function ( $args, $url ) {
				if ( is_string( $url ) && strpos( $url, 'wp-cron.php' ) !== false ) {
					$current = isset( $args['timeout'] ) ? (float) $args['timeout'] : 5;
					if ( $current < 15 ) {
						$args['timeout'] = 15;
					}
				}
				return $args;
			},
			10,
			2
		);
	},
	1
);

function wpscx_core() {
	$can_run = current_user_can( 'administrator' ) || current_user_can( 'editor' ) || current_user_can( 'author' ) || current_user_can( 'contributor' );
	if ( ! $can_run ) {
		// During cron there is no user; load framework + Pro so wpscxscanall callbacks are registered.
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			wpscx_load_plugin();
		}
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/class-wpsc-database.php';
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
	// Matches Wpscx_Banner notices (manage_options); Dashicons for badge icons.
	if ( current_user_can( 'manage_options' ) ) {
		wp_enqueue_style( 'wpsc-promo-notices', plugin_dir_url( __FILE__ ) . 'css/wpsc-promo-notices.css', array( 'dashicons' ), $wpsc_version );
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
	$wpsc_acti = get_option( 'wpsc_data_acti' );
	// phpcs:ignore WordPress.Security.NonceVerification -- Not processing form data, only checking absence of $_POST['uninstall'] to avoid showing notice on uninstall.
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
	global $wpscx_check_opt;
	global $wpsc_haystack;
	global $wpscx_base_page_max;
	global $wpscx_ent_included;
	global $wpsc_version;
	global $wpsc_globals_loaded;

	$wpsc_version = '11.3';

	// Return early if globals are already loaded to prevent duplicate queries
	if ( isset( $wpsc_globals_loaded ) && $wpsc_globals_loaded === true ) {
		return;
	}

	$wpscx_ignore_list = array();
	$wpscx_dict_list   = array();
	$wpgc_settings     = array();

	// $test_var = 'Test successful';

	$words_table           = $wpdb->prefix . 'spellcheck_words';
	$options_table         = $wpdb->prefix . 'spellcheck_options';
	$grammar_options_table = $wpdb->prefix . 'spellcheck_grammar_options';
	$ignore_table          = $wpdb->prefix . 'spellcheck_ignore';
	$dict_table            = $wpdb->prefix . 'spellcheck_dictionary';

	$wpscx_check_opt = $wpdb->get_results( "SHOW TABLES LIKE '$options_table'" );
	$check_word      = $wpdb->get_results( "SHOW TABLES LIKE '$words_table'" );
	$check_ig        = $wpdb->get_results( "SHOW TABLES LIKE '$ignore_table'" );
	$check_dict      = $wpdb->get_results( "SHOW TABLES LIKE '$dict_table'" );
	$check_grm       = $wpdb->get_results( "SHOW TABLES LIKE '$grammar_options_table'" );

	if ( ! isset( $wpsc_settings ) && 0 < sizeof( $wpscx_check_opt ) ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name from prefix; ORDER BY id ensures stable index-based access (options by insertion order).
		$wpsc_settings_temp = $wpdb->get_results( "SELECT * FROM $options_table ORDER BY id" );
		if ( isset( $wpsc_settings_temp ) && sizeof( $wpsc_settings_temp ) > 0 ) {
			$wpsc_settings = new SplFixedArray( sizeof( $wpsc_settings_temp ) + 1 );
			for ( $x = 0; $x < sizeof( $wpsc_settings_temp ); $x++ ) {
				$wpsc_settings[ $x ] = $wpsc_settings_temp[ $x ];
			}
			unset( $wpsc_settings_temp );
		}
	}

	if ( sizeof( (array) $wpsc_settings ) < 1 ) {

		if ( sizeof( $wpscx_check_opt ) !== 0 && sizeof( $check_word ) !== 0 && sizeof( $check_ig ) !== 0 && sizeof( $check_dict ) !== 0 ) {
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

	// Mark globals as loaded so subsequent calls return early (no duplicate queries).
	$wpsc_globals_loaded = true;

	
}

/**
 * Returns the admin UI theme preference (system, dark, or light). New installs default to system (match OS/browser).
 * Cached per request to avoid duplicate DB queries.
 *
 * @since 11.0
 * @return string 'system', 'dark', or 'light'
 */
function wpsc_get_admin_theme() {
	global $wpdb, $wpsc_admin_theme_cache;
	if ( isset( $wpsc_admin_theme_cache ) ) {
		return $wpsc_admin_theme_cache;
	}
	$options_table = $wpdb->prefix . 'spellcheck_options';
	$row           = $wpdb->get_row( "SELECT option_value FROM {$options_table} WHERE option_name = 'wpsc_admin_theme'" );
	if ( ! $row || ! in_array( $row->option_value, array( 'system', 'dark', 'light' ), true ) ) {
		$wpsc_admin_theme_cache = 'dark';
		return $wpsc_admin_theme_cache;
	}
	$wpsc_admin_theme_cache = $row->option_value;
	return $wpsc_admin_theme_cache;
}

/**
 * Clears the in-request cache for admin theme. Call after updating wpsc_admin_theme in the DB.
 *
 * @since 11.0
 */
function wpsc_clear_admin_theme_cache() {
	unset( $GLOBALS['wpsc_admin_theme_cache'] );
}

/**
 * Returns the admin theme to use for the current request. On the Options page after clicking Update,
 * returns the submitted theme so body class and enqueued styles reflect the new colors immediately
 * without a second refresh (the form is processed later in the same request).
 *
 * @since 11.0
 * @return string 'system', 'dark', or 'light'
 */
function wpsc_get_effective_admin_theme() {
	$page = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';
	$is_options_page = ( 'wp-spellcheck-options.php' === $page || 'class-wpsc-options.php' === $page );
	if ( is_admin() && $is_options_page
		&& isset( $_POST['submit'] ) && 'Update' === $_POST['submit']
		&& isset( $_POST['wpsc_admin_theme'] ) ) {
		$theme = sanitize_text_field( wp_unslash( $_POST['wpsc_admin_theme'] ) );
		if ( in_array( $theme, array( 'light', 'dark', 'system' ), true ) ) {
			return $theme;
		}
	}
	return wpsc_get_admin_theme();
}

/**
 * Returns the URL to the theme-appropriate loading spinner SVG (transparent background).
 * Dark theme → loading.svg (currentColor); light theme → loading-dark.svg (black).
 *
 * @since 11.0
 * @return string URL to admin/images/loading.svg or loading-dark.svg
 */
function wpsc_get_loading_spinner_url() {
	$theme = function_exists( 'wpsc_get_effective_admin_theme' ) ? wpsc_get_effective_admin_theme() : 'dark';
	$base  = plugin_dir_url( __FILE__ ) . 'admin/images/';
	return $base . ( 'light' === $theme ? 'loading-dark.svg' : 'loading.svg' );
}

global $wpscx_scdb_version;
global $wpscx_scan_delay;
$wpscx_scan_delay   = 0;
$wpscx_scdb_version = '1.0';

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
		wp_die( 'WP Spell Check has been deactivated. If you wish to use the plugin again you may activate it on the <a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">WordPress plugin page</a>.' );
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

/**
 * Whether the wpsc custom cron schedule should be registered.
 * Only when: Pro active, API valid ($wpscx_ent_included), Send Email Reports is on, and an email is saved.
 *
 * @since 10.0
 * @return bool
 */
function wpscx_should_register_cron_schedule() {
	static $result = null;
	if ( $result !== null ) {
		return $result;
	}
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	if ( ! is_plugin_active( 'wp-spell-check-pro/wpspellcheckpro.php' ) ) {
		$result = false;
		return false;
	}
	global $wpscx_ent_included;
	if ( empty( $wpscx_ent_included ) ) {
		$result = false;
		return false;
	}
	global $wpdb;
	$table = $wpdb->prefix . 'spellcheck_options';
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name from prefix; option_name is literal.
	$email = $wpdb->get_var( "SELECT option_value FROM {$table} WHERE option_name = 'email' LIMIT 1" );
	if ( $email !== 'true' ) {
		$result = false;
		return false;
	}
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name from prefix; option_name is literal.
	$email_address = $wpdb->get_var( "SELECT option_value FROM {$table} WHERE option_name = 'email_address' LIMIT 1" );
	$result = ( is_string( $email_address ) && trim( $email_address ) !== '' );
	return $result;
}

function wpscx_cron_add_custom( $schedules ) {
	if ( ! is_admin() && ! ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
		return $schedules;
	}
	if ( ! wpscx_should_register_cron_schedule() ) {
		return $schedules;
	}
	wpscx_set_global_vars();
	global $wpdb, $wpscx_check_opt;
	$table_name = $wpdb->prefix . 'spellcheck_options';

	// Reuse options table check from wpscx_set_global_vars() to avoid duplicate SHOW TABLES.
	if ( empty( $wpscx_check_opt ) || sizeof( $wpscx_check_opt ) === 0 ) {
		return $schedules;
	}
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
		case 'minutes':
		case 'minutely':
			$scan_recurrence = $scan_timer * 60;
			break;
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
	return $schedules;
}
add_action(
	'init',
	function () {
		if ( ! is_admin() && ! ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return;
		}
		if ( ! wpscx_should_register_cron_schedule() ) {
			return;
		}
		add_filter( 'cron_schedules', 'wpscx_cron_add_custom' );
	},
	20
);

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

$wpscx_plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$wpscx_plugin", 'wpscx_add_premium_link' );
add_filter( "plugin_action_links_$wpscx_plugin", 'wpscx_add_settings_link' );
?>