<?php

class Wpscx_Wordpress_Interface {
	function __construct() {
		$this->register_menu_hooks();
		$this->register_ajax_hooks();
		$this->register_banner_hooks();
		$this->register_dashboard_hooks();
		$this->register_opendyslexic_hooks();
	}

	function register_menu_hooks() {
		$menu = new Wpscx_Menu;

		add_action( 'admin_menu', array( $menu, 'add_menu' ) );
		add_action( 'admin_menu', array( $menu, 'add_tools_scan_menu' ) );
		add_action( 'admin_menu', array( $menu, 'add_settings_menu' ) );
		add_action( 'admin_menu', array( $menu, 'add_options_menu' ) );
		add_action( 'admin_menu', array( $menu, 'add_dictionary_menu' ) );
		add_action( 'admin_menu', array( $menu, 'add_ignore_menu' ) );
		add_action( 'admin_menu', array( $menu, 'add_pro_menu' ) );
		add_action( 'network_admin_menu', array( $menu, 'add_network_menu' ) );
		add_action( 'admin_head', array( $menu, 'menu_script' ) );
			if ( ! isset( $_POST['uninstall'] ) && current_user_can( 'manage_options' ) ) {
				add_action( 'admin_bar_menu', array( $menu, 'add_toolbar_menu' ), 999 );
			}
	}

	function register_dashboard_hooks() {
		$dashboard = new Wpscx_Dashboard;

		add_action( 'wp_dashboard_setup', array( $dashboard, 'add_dashboard_widget' ) );
	}

	function register_banner_hooks() {
		$banner = new Wpscx_Banner;
		global $wp_version;
		$ver_compare    = version_compare( $wp_version, '5.0.0' );
		$classic_active = is_plugin_active( 'classic-editor/classic-editor.php' );
		$page_action    = true;
		if ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
			$page_action = false;
		}

		if ( -1 === $ver_compare || $classic_active || $page_action ) {
			add_action( 'admin_notices', array( $banner, 'check_inactive_notice' ) );
			add_action( 'admin_init', array( $banner, 'ignore_review_notice' ) );
			add_action( 'admin_init', array( $banner, 'ignore_notice' ) );
			add_action( 'admin_init', array( $banner, 'ignore_upgrade_notice' ) );
			add_action( 'admin_head', array( $banner, 'check_install_notice' ) );
		}
	}

	function register_opendyslexic_hooks() {
		$opendyslexic = new Wpscx_Opendyslexic;

		add_action( 'profile_personal_options', array( $opendyslexic, 'profile_dyslexic' ) );
		add_action( 'edit_user_profile_update', array( $opendyslexic, 'update_dyslexic' ) );
		add_action( 'personal_options_update', array( $opendyslexic, 'update_dyslexic' ) );
		add_action( 'wp_head', array( $opendyslexic, 'dyslexic_css' ) );
		add_action( 'admin_head', array( $opendyslexic, 'dyslexic_css_admin' ) );
	}

	function register_ajax_hooks() {
		$ajax   = new Wpscx_Ajax;
		$banner = new Wpscx_Banner;

		add_action( 'wp_ajax_results_sc', array( $ajax, 'wpscx_scan_function' ) );
		add_action( 'wp_ajax_nopriv_results_sc', array( $ajax, 'wpscx_scan_function' ) );
		add_action( 'wp_ajax_emptyresults_sc', array( $ajax, 'wpscx_empty_scan_function' ) );
		add_action( 'wp_ajax_nopriv_emptyresults_sc', array( $ajax, 'wpscx_empty_scan_function' ) );
		add_action( 'wp_ajax_finish_scan', array( $ajax, 'wpscx_finish_scan' ) );
		add_action( 'wp_ajax_nopriv_finish_scan', array( $ajax, 'wpscx_finish_scan' ) );
		add_action( 'wp_ajax_finish_empty_scan', array( $ajax, 'wpscx_finish_empty_scan' ) );
		add_action( 'wp_ajax_nopriv_finish_empty_scan', array( $ajax, 'wpscx_finish_empty_scan' ) );

		add_action( 'wp_ajax_results_hc', array( $ajax, 'wphcx_scan_function' ) );
		add_action( 'wp_ajax_nopriv_results_hc', array( $ajax, 'wphcx_scan_function' ) );
		add_action( 'wp_ajax_finish_scan_hc', array( $ajax, 'wpscx_finish_html_scan' ) );
		add_action( 'wp_ajax_nopriv_finish_scan_hc', array( $ajax, 'wpscx_finish_html_scan' ) );

		add_action( 'wp_ajax_wpsc_dismiss', array( $banner, 'ignore_install_notice' ) );
		add_action( 'wp_ajax_nopriv_wpsc_dismiss', array( $banner, 'ignore_install_notice' ) );

		add_action( 'wp_ajax_wpscx_start_scan', array( $ajax, 'wpscx_start_scan' ) );
		add_action( 'wp_ajax_nopriv_wpscx_start_scan', array( $ajax, 'wpscx_start_scan' ) );
		add_action( 'wp_ajax_wpscx_start_scan_grammar', array( $ajax, 'wpscx_start_scan_grammar' ) );
		add_action( 'wp_ajax_nopriv_wpscx_start_scan_grammar', array( $ajax, 'wpscx_start_scan_grammar' ) );
		add_action( 'wp_ajax_wpscx_start_scan_bc', array( $ajax, 'wpscx_start_scan_bc' ) );
		add_action( 'wp_ajax_nopriv_wpscx_start_scan_bc', array( $ajax, 'wpscx_start_scan_bc' ) );
		add_action( 'wp_ajax_wpscx_start_scan_empty', array( $ajax, 'wpscx_start_scan_empty' ) );
		add_action( 'wp_ajax_nopriv_wpscx_start_scan_empty', array( $ajax, 'wpscx_start_scan_empty' ) );

		add_action( 'wp_ajax_wpscx_display_results', array( $ajax, 'wpscx_display_results' ) );
		add_action( 'wp_ajax_nopriv_wpscx_display_results', array( $ajax, 'wpscx_display_results' ) );
		add_action( 'wp_ajax_wpscx_get_stats', array( $ajax, 'wpscx_get_stats' ) );
		add_action( 'wp_ajax_nopriv_wpscx_get_stats', array( $ajax, 'wpscx_get_stats' ) );

		add_action( 'wp_ajax_wpscx_display_results_empty', array( $ajax, 'wpscx_display_results_empty' ) );
		add_action( 'wp_ajax_nopriv_wpscx_display_results_empty', array( $ajax, 'wpscx_display_results_empty' ) );
		add_action( 'wp_ajax_wpscx_get_stats_empty', array( $ajax, 'wpscx_get_stats_empty' ) );
		add_action( 'wp_ajax_nopriv_wpscx_get_stats_empty', array( $ajax, 'wpscx_get_stats_empty' ) );

		add_action( 'wp_ajax_wpscx_display_results_grammar', array( $ajax, 'wpscx_display_results_grammar' ) );
		add_action( 'wp_ajax_nopriv_wpscx_display_results_grammar', array( $ajax, 'wpscx_display_results_grammar' ) );
		add_action( 'wp_ajax_wpscx_get_stats_grammar', array( $ajax, 'wpscx_get_stats_grammar' ) );
		add_action( 'wp_ajax_nopriv_wpscx_get_stats_grammar', array( $ajax, 'wpscx_get_stats_grammar' ) );

		add_action( 'wp_ajax_wpscx_display_results_html', array( $ajax, 'wpscx_display_results_html' ) );
		add_action( 'wp_ajax_nopriv_wpscx_display_results_html', array( $ajax, 'wpscx_display_results_html' ) );
		add_action( 'wp_ajax_wpscx_get_stats_code', array( $ajax, 'wpscx_get_stats_code' ) );
		add_action( 'wp_ajax_nopriv_wpscx_get_stats_code', array( $ajax, 'wpscx_get_stats_code' ) );

		add_action( 'wp_ajax__ajax_fetch_custom_list', array( $ajax, '_ajax_fetch_wpsc_list_callback' ) );
                
                add_action( 'wp_ajax_wpscx_openAI_ajax', array( $ajax, 'wpscx_openAI_ajax' ) );
		add_action( 'wp_ajax_nopriv_wpscx_openAI_ajax', array( $ajax, 'wpscx_openAI_ajax' ) );
	}
}

class wpscx_popup {
    public $docURL;
    
    function showPopup() {
        
    }
    
    function hidePopup() {
        
    }
    
    function updateGoogleDoc() {
        
    }
}
