<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop all plugin tables
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'spellcheck_dictionary' );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'spellcheck_ignore' );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'spellcheck_options' );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'spellcheck_words' );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'spellcheck_empty' );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'spellcheck_html' );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'spellcheck_grammar' );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'spellcheck_grammar_options' );

// Clear all scheduled cron jobs
wp_clear_scheduled_hook( 'wpscxscanall' );
wp_clear_scheduled_hook( 'adminscansite' );
wp_clear_scheduled_hook( 'admincheckcode' );
wp_clear_scheduled_hook( 'adminscansiteempty' );
wp_clear_scheduled_hook( 'admincheckmenusempty_ent' );
wp_clear_scheduled_hook( 'admincheckpagetitlesempty_ent' );
wp_clear_scheduled_hook( 'admincheckposttitlesempty_ent' );
wp_clear_scheduled_hook( 'admincheckpostseoempty_ent' );
wp_clear_scheduled_hook( 'admincheckmediaseoempty_ent' );
wp_clear_scheduled_hook( 'admincheckmediaempty' );
wp_clear_scheduled_hook( 'admincheckecommerceempty_ent' );
wp_clear_scheduled_hook( 'admincheckposttagsdescempty_ent' );
wp_clear_scheduled_hook( 'admincheckcategoriesdescempty_ent' );
wp_clear_scheduled_hook( 'admincheckauthorsempty' );
wp_clear_scheduled_hook( 'admincheckmenusempty' );
wp_clear_scheduled_hook( 'admincheckpagetitlesempty' );
wp_clear_scheduled_hook( 'admincheckposttitlesempty' );
wp_clear_scheduled_hook( 'admincheckpostseoempty' );
wp_clear_scheduled_hook( 'admincheckmediaseoempty' );
wp_clear_scheduled_hook( 'admincheckmediaempty_pro' );
wp_clear_scheduled_hook( 'admincheckecommerceempty' );
wp_clear_scheduled_hook( 'admincheckposttagsdescempty' );
wp_clear_scheduled_hook( 'admincheckcategoriesdescempty' );
wp_clear_scheduled_hook( 'admincheckpagetitlesemptybase' );
wp_clear_scheduled_hook( 'admincheckposttitlesemptybase' );
wp_clear_scheduled_hook( 'wpgcx_check_posts' );
wp_clear_scheduled_hook( 'wpgcx_check_pages' );
wp_clear_scheduled_hook( 'wpgcx_scan_site' );

// Delete WordPress options
delete_option( 'scdb_version' );
delete_option( 'wpsc_data_acti' );

// Clean up user meta for ALL users (uninstall.php runs without current_user context)
$users = get_users();
foreach ( $users as $user ) {
	delete_user_meta( $user->ID, 'wpsc_pro_notice_date' );
	delete_user_meta( $user->ID, 'wpsc_pro_dismissed' );
	delete_user_meta( $user->ID, 'wpsc_pro_times_dismissed' );
	delete_user_meta( $user->ID, 'wpsc_notice_timing' );
	delete_user_meta( $user->ID, 'wpsc_notice_timing_date' );
	delete_user_meta( $user->ID, 'wpsc_ignore_review_notice' );
	delete_user_meta( $user->ID, 'wpsc_review_date' );
	delete_user_meta( $user->ID, 'wpsc_times_dismissed_review' );
	delete_user_meta( $user->ID, 'wpsc_pro_ignore_notice' );
	delete_user_meta( $user->ID, 'wpsc_ignore_install_notice' );
	delete_user_meta( $user->ID, 'wpsc_last_check' );
	delete_user_meta( $user->ID, 'wpsc_version' );
	delete_user_meta( $user->ID, 'wpsc_outdated' );
	delete_user_meta( $user->ID, 'wpsc_pro_last_check' );
	delete_user_meta( $user->ID, 'wpsc_pro_version' );
	delete_user_meta( $user->ID, 'wpsc_pro_outdated' );
	delete_user_meta( $user->ID, 'wpsc_ent_last_check' );
	delete_user_meta( $user->ID, 'wpsc_ent_version' );
	delete_user_meta( $user->ID, 'wpsc_ent_outdated' );
	delete_user_meta( $user->ID, 'wpsc_update_notice_date' );
	delete_user_meta( $user->ID, 'wpsc_usedyslexic' );
	delete_user_meta( $user->ID, 'wpsc_warning_report' );
}
