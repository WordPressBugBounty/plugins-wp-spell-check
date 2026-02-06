<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Wpscx_Admin {
	function __construct() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-includes/pluggable.php';
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		require_once WPSC_FRAMEWORK;
		require_once __DIR__ . '/class-wpsc-scanner.php';
		require_once __DIR__ . '/class-wpsc-menus.php';
		require_once __DIR__ . '/class-wpsc-utils.php';
		require_once __DIR__ . '/class-wpsc-banner.php';
		require_once __DIR__ . '/class-wpsc-ajax.php';
		require_once __DIR__ . '/class-wpsc-interface.php';
		require_once __DIR__ . '/class-wpsc-email.php';
		if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === 'wp-spellcheck-options.php' ) {
			require_once __DIR__ . '/class-wpsc-options.php';
		}
		if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === 'wp-spellcheck-dictionary.php' ) {
			require_once __DIR__ . '/class-wpsc-dictionary.php';
		}
		if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === 'wp-spellcheck-ignore.php' ) {
			require_once __DIR__ . '/class-wpsc-ignore.php';
		}
		if ( isset( $_GET['page'] ) && ( sanitize_text_field( wp_unslash( $_GET['page'] ) ) === 'wp-spellcheck.php' || sanitize_text_field( wp_unslash( $_GET['page'] ) ) === 'wp-spellcheck-seo.php' ) ) {
			require_once __DIR__ . '/class-wpsc-results.php';
		}
		if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === 'wp-spellcheck-seo.php' ) {
			require_once __DIR__ . '/wpsc-empty-results.php';
		}
		require_once __DIR__ . '/wpsc-empty.php';
		if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === 'wp-spellcheck-html.php' ) {
			require_once __DIR__ . '/class-html-results.php';
		}
		require_once __DIR__ . '/grammar/grammar_framework.php';
		if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === 'wp-spellcheck-grammar.php' ) {
			require_once __DIR__ . '/grammar/class-grammar-results.php';
		}
		require_once __DIR__ . '/class-deactive-survey.php';
		require_once __DIR__ . '/grammar/class-wpsc-grammar.php';
		require_once __DIR__ . '/class-wpsc-spellcheck.php';
		require_once __DIR__ . '/class-wpsc-seo.php';

		global $wpsc_version;
		define( 'WPSCX_VERSION', $wpsc_version );

		$this->register_admin_hooks();

		$interface = new Wpscx_Wordpress_Interface();
	}

	function admin_footer() {
		global $current_screen;
				$text = '';
		if ( ! empty( $current_screen->id ) && strpos( $current_screen->id, 'wp-spellcheck' ) !== false ) {
			$url = 'https://wordpress.org/support/plugin/wp-spell-check/reviews/';
			/* translators: 1: Opening strong tag, 2: Closing strong tag, 3: Star rating link, 4: Opening link tag for WordPress.org, 5: Closing link tag */
			$text = sprintf( esc_html__( 'Finding the plugin useful? Please rate %1$sWP Spell Check%2$s %3$s on %4$sWordPress.org%5$s. We appreciate your help!', 'wp-spell-check' ), '<strong>', '</strong>', '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">★★★★★</a>', '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">', '</a>' );
		}
		return $text;
	}

	function register_admin_hooks() {
		$plugin = plugin_basename( __FILE__ );

		add_filter( 'admin_footer_text', array( $this, 'admin_footer' ), 1, 2 );
		add_action( 'admin_notices', array( $this, 'nag_api_invalid' ) );
	}

	function nag_api_invalid() {
		global $wpdb;
		global $wpscx_ent_included;
		$options_table = $wpdb->prefix . 'spellcheck_options';
		if ( is_plugin_active( 'wp-spell-check-pro/wpspellcheckpro.php' ) ) {
			$pro_active = true;
		} else {
			$pro_active = false; }

		$result = $wpdb->get_results( "SELECT * FROM $options_table WHERE option_name = 'api_key'" );
		if ( '' !== $result[0]->option_value ) {
			$api_entered = false;
		} else {
			$api_entered = true; }

		if ( $pro_active && $api_entered && ! $wpscx_ent_included ) {
			echo "<div class='notice notice-warning'>"
			. "<p><span style='font-weight: bold; color: red;'>The WP Spell Check Pro API Key has NOT been entered.</span> Please go to the <a href='/wp-admin/admin.php?page=wp-spellcheck-options.php'>options page</a> to enter your API Key. <a href='https://www.wpspellcheck.com/account' target='_blank'>Click here</a> to get your API Key.</p>"
					. '</div>';
		}
	}
}
