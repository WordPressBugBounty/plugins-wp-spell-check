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

		require_once __DIR__ . '/class-wpsc-interface.php';
		if ( class_exists( 'Wpscx_Wordpress_Interface' ) ) {
			$interface = new Wpscx_Wordpress_Interface();
		}
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
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_theme' ), 20 );
		add_action( 'admin_notices', array( $this, 'nag_api_invalid' ) );
	}

	/**
	 * Add body class on plugin admin pages for consistent page background styling.
	 *
	 * @since 10.1
	 * @param string $classes Space-separated list of body classes.
	 * @return string
	 */
	function admin_body_class( $classes ) {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return $classes;
		}
		// All WPSC menu pages and Settings subpages (Options, Uninstall).
		$is_wpsc = ( strpos( $screen->id, 'wp-spellcheck' ) !== false
			|| strpos( $screen->id, 'wpsc' ) !== false );
		if ( $is_wpsc ) {
			$theme = function_exists( 'wpsc_get_effective_admin_theme' ) ? wpsc_get_effective_admin_theme() : 'dark';
			if ( 'system' === $theme ) {
				$theme_class = ' wpsc-theme-system';
			} elseif ( 'light' === $theme ) {
				$theme_class = ' wpsc-theme-light';
			} else {
				$theme_class = ' wpsc-theme-dark';
			}
			return $classes . ' wpsc-admin-page' . $theme_class;
		}
		return $classes;
	}

	/**
	 * Inline critical dark-theme CSS so first paint is dark (avoids white flash while admin-theme-dark.css loads).
	 *
	 * @since 11.0
	 * @param string $theme 'dark' or 'system'.
	 * @return string CSS to inject.
	 */
	private function get_critical_dark_css( $theme ) {
		$dark_bg = '#100921';
		$dark_text = '#e0e0e0';
		if ( 'dark' === $theme ) {
			return 'body.wpsc-admin-page.wpsc-theme-dark{background-color:' . $dark_bg . '}'
				. 'body.wpsc-admin-page.wpsc-theme-dark #wpcontent{background-color:' . $dark_bg . '!important}'
				. 'body.wpsc-admin-page.wpsc-theme-dark .wrap.wpsc-table,'
				. 'body.wpsc-admin-page.wpsc-theme-dark .wrap.wpsc-options-page{background-color:' . $dark_bg . ';color:' . $dark_text . '}';
		}
		// System: same rules inside prefers-color-scheme so first paint is dark when OS is dark.
		return '@media(prefers-color-scheme:dark){'
			. 'body.wpsc-admin-page.wpsc-theme-system{background-color:' . $dark_bg . '}'
			. 'body.wpsc-admin-page.wpsc-theme-system #wpcontent{background-color:' . $dark_bg . '!important}'
			. 'body.wpsc-admin-page.wpsc-theme-system .wrap.wpsc-table,'
			. 'body.wpsc-admin-page.wpsc-theme-system .wrap.wpsc-options-page{background-color:' . $dark_bg . ';color:' . $dark_text . '}}';
	}

	/**
	 * Enqueue dark theme stylesheet when admin theme is dark or system (system uses media query for prefers-color-scheme).
	 * Inlines critical dark CSS so the first paint is dark and avoids a white flash (FOUC).
	 *
	 * @since 11.0
	 */
	function enqueue_admin_theme() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}
		$is_wpsc = ( strpos( $screen->id, 'wp-spellcheck' ) !== false || strpos( $screen->id, 'wpsc' ) !== false );
		$theme = function_exists( 'wpsc_get_effective_admin_theme' ) ? wpsc_get_effective_admin_theme() : 'dark';
		if ( ! $is_wpsc || ! in_array( $theme, array( 'dark', 'system' ), true ) ) {
			return;
		}
		// Inline critical dark rules so they apply before admin-theme-dark.css loads (prevents white flash on refresh).
		wp_add_inline_style( 'wpsc-admin-styles', $this->get_critical_dark_css( $theme ) );
		global $wpsc_version;
		wp_enqueue_style(
			'wpsc-admin-theme-dark',
			plugin_dir_url( dirname( __FILE__ ) ) . 'css/admin-theme-dark.css',
			array( 'wpsc-admin-styles' ),
			$wpsc_version
		);
	}

	function nag_api_invalid() {
		global $wpdb;
		global $wpscx_ent_included;
		$options_table = $wpdb->prefix . 'spellcheck_options';

		// Do not show "API Key not entered" on the same request where the user just submitted the options form.
		// admin_notices runs before the options callback, so the DB is not yet updated; suppress to avoid a false nag.
		$is_options_page_post = isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === 'wp-spellcheck-options.php' && ! empty( $_POST ) && isset( $_POST['api_key'] );
		if ( $is_options_page_post ) {
			return;
		}

		if ( is_plugin_active( 'wp-spell-check-pro/wpspellcheckpro.php' ) ) {
			$pro_active = true;
		} else {
			$pro_active = false; }

		$result = $wpdb->get_results( "SELECT * FROM $options_table WHERE option_name = 'api_key'" );
		$key_value = ( ! empty( $result ) && isset( $result[0]->option_value ) ) ? $result[0]->option_value : '';
		if ( '' !== $key_value ) {
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
