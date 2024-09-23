<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; }
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
	Pro Add-on / Prices: https://www.wpspellcheck.com/pricing/
*/

function wpscx_check_page_title_empty( $scanner = null ) {
	if ( null === $scanner ) {
		$scanner = new Wpscx_Seo_Scanner;
	}
		$scanner->check_page_title_empty();
}
	add_action( 'admincheckpagetitlesemptybase', 'wpscx_check_page_title_empty' );

function wpscx_check_post_title_empty( $scanner = null ) {
	if ( null === $scanner ) {
		$scanner = new Wpscx_Seo_Scanner;
	}
			$scanner->check_post_title_empty();
}
	add_action( 'admincheckposttitlesemptybase', 'wpscx_check_post_title_empty' );

function wpscx_check_author_empty( $scanner = null ) {
	if ( null === $scanner ) {
		$scanner = new Wpscx_Seo_Scanner;
	}
		$scanner->check_author_empty( 0, null );
}
	add_action( 'admincheckauthorsempty', 'wpscx_check_author_empty' );

function wpscx_clear_results_empty() {
	global $wpdb;
	$table_name    = $wpdb->prefix . 'spellcheck_empty';
	$options_table = $wpdb->prefix . 'spellcheck_options';
	$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'pro_empty_count' ) ); //$ Clear out the pro errors count
	$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'empty_checked' ) ); //$ Clear out the total empty field count
	$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'page_count' ) ); //$ Clear out the page count
	$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'post_count' ) ); //$ Clear out the post count
	$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'media_count' ) ); //$Clear out the media count

	$wpdb->delete( $table_name, array( 'ignore_word' => false ) );
}

function wpscx_clear_empty_scan() {
	global $wpdb;
	global $wpsc_settings;
	$options_table = $wpdb->prefix . 'spellcheck_options';
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'entire_empty_scan' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'empty_author_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'empty_page_title_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'empty_post_title_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'empty_menu_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'empty_page_seo_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'empty_post_seo_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'empty_media_seo_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'empty_media_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'empty_ecommerce_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'empty_tag_desc_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'empty_cat_desc_sip' ) );
}

function wpscx_set_empty_scan_in_progress( $rng_seed = 0 ) {
	global $wpdb;
	global $wpscx_ent_included;
	global $wpsc_settings;
	$options_table = $wpdb->prefix . 'spellcheck_options';
	$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'entire_empty_scan' ) );
			sleep( 1 );

	$settings = $wpsc_settings;

	if ( 'true' === $settings[47]->option_value ) {
		$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_author_sip' ) );
		if ( 'true' === $settings[49]->option_value ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_page_title_sip' ) );
		}
		if ( 'true' === $settings[50]->option_value ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_post_title_sip' ) );
		}

		if ( $wpscx_ent_included ) {
			if ( 'true' === $settings[48]->option_value ) {
				$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_menu_sip' ) );
			}
			if ( 'true' === $settings[53]->option_value ) {
				$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_page_seo_sip' ) );
			}
			if ( 'true' === $settings[54]->option_value ) {
				$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_post_seo_sip' ) );
			}
			if ( 'true' === $settings[55]->option_value ) {
				$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_media_seo_sip' ) );
			}
			if ( 'true' === $settings[56]->option_value ) {
				$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_media_sip' ) );
			}
			if ( 'true' === $settings[57]->option_value ) {
				$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_ecommerce_sip' ) );
			}
			if ( 'true' === $settings[51]->option_value ) {
				$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_tag_desc_sip' ) );
			}
			if ( 'true' === $settings[52]->option_value ) {
				$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_cat_desc_sip' ) );
			}
		}
	}
}

function wpscx_clear_events_empty() {
	$time = wp_next_scheduled( 'admincheckmenusempty_ent' );
	wp_unschedule_event( $time, 'admincheckmenusempty_ent' );

	$time = wp_next_scheduled( 'admincheckpagetitlesempty_ent' );
	wp_unschedule_event( $time, 'admincheckpagetitlesempty_ent' );

	$time = wp_next_scheduled( 'admincheckposttitlesempty_ent' );
	wp_unschedule_event( $time, 'admincheckposttitlesempty_ent' );

	$time = wp_next_scheduled( 'admincheckpostseoempty_ent' );
	wp_unschedule_event( $time, 'admincheckpostseoempty_ent' );

	$time = wp_next_scheduled( 'admincheckmediaseoempty_ent' );
	wp_unschedule_event( $time, 'admincheckmediaseoempty_ent' );

	$time = wp_next_scheduled( 'admincheckmediaempty' );
	wp_unschedule_event( $time, 'admincheckmediaempty' );

	$time = wp_next_scheduled( 'admincheckecommerceempty_ent' );
	wp_unschedule_event( $time, 'admincheckecommerceempty_ent' );

	$time = wp_next_scheduled( 'admincheckposttagsdescempty_ent' );
	wp_unschedule_event( $time, 'admincheckposttagsdescempty_ent' );

	$time = wp_next_scheduled( 'admincheckcategoriesdescempty_ent' );
	wp_unschedule_event( $time, 'admincheckcategoriesdescempty_ent' );

	$time = wp_next_scheduled( 'admincheckauthorsempty' );
	wp_unschedule_event( $time, 'admincheckauthorsempty' );

	$time = wp_next_scheduled( 'admincheckmenusempty' );
	wp_unschedule_event( $time, 'admincheckmenusempty' );

	$time = wp_next_scheduled( 'admincheckpagetitlesempty' );
	wp_unschedule_event( $time, 'admincheckpagetitlesempty' );

	$time = wp_next_scheduled( 'admincheckposttitlesempty' );
	wp_unschedule_event( $time, 'admincheckposttitlesempty' );

	$time = wp_next_scheduled( 'admincheckpostseoempty' );
	wp_unschedule_event( $time, 'admincheckpostseoempty' );

	$time = wp_next_scheduled( 'admincheckmediaseoempty' );
	wp_unschedule_event( $time, 'admincheckmediaseoempty' );

	$time = wp_next_scheduled( 'admincheckmediaempty_pro' );
	wp_unschedule_event( $time, 'admincheckmediaempty_pro' );

	$time = wp_next_scheduled( 'admincheckecommerceempty' );
	wp_unschedule_event( $time, 'admincheckecommerceempty' );

	$time = wp_next_scheduled( 'admincheckposttagsdescempty' );
	wp_unschedule_event( $time, 'admincheckposttagsdescempty' );

	$time = wp_next_scheduled( 'admincheckcategoriesdescempty' );
	wp_unschedule_event( $time, 'admincheckcategoriesdescempty' );

	$time = wp_next_scheduled( 'admincheckpagetitlesemptybase' );
	wp_unschedule_event( $time, 'admincheckpagetitlesemptybase' );

	$time = wp_next_scheduled( 'admincheckposttitlesemptybase' );
	wp_unschedule_event( $time, 'admincheckposttitlesemptybase' );
}


function wpscx_scan_site_empty( $rng_seed = 0 ) {
	$start     = round( microtime( true ), 5 );
	$sql_count = 0;
	global $wpdb;
	global $wpscx_ent_included;

	wpscx_clear_empty_results();
	//wpsc_clear_events_empty(); //Clear out the event scheduler of any previous empty field events

	$table_name    = $wpdb->prefix . 'spellcheck_empty';
	$options_table = $wpdb->prefix . 'spellcheck_options';
	set_time_limit( 600 ); //$ Set PHP timeout limit
	$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'empty_scan_in_progress' ) );
	$sql_count++;
	$start_time = time();
	//$wpdb->update($options_table, array('option_value' => $start_time), array('option_name' => 'scan_start_time'));  $sql_count++;

	$settings = $wpdb->get_results( 'SELECT option_value FROM ' . $options_table ); //4 = Pages, 5 = Posts, 6 = Theme, 7 = Menus

			$scanner_base = new Wpscx_Seo_Scanner;

	if ( $wpscx_ent_included ) {
				$scanner_pro = new Wpscx_Seo_Scanner_pro;

		if ( 'true' === $settings[48]->option_value ) {
			wpscx_check_menus_empty_ent( $scanner_pro );
		}
		if ( 'true' === $settings[49]->option_value ) {
			wpscx_check_page_title_empty_ent( $scanner_pro );
		}
		if ( 'true' === $settings[50]->option_value ) {
			wpscx_check_post_title_empty_ent( $scanner_pro );
		}
		if ( 'true' === $settings[53]->option_value ) {
			wpscx_check_page_seo_empty_ent( $scanner_pro );
		}
		if ( 'true' === $settings[54]->option_value ) {
			wpscx_check_post_seo_empty_ent( $scanner_pro );
		}
		if ( 'true' === $settings[55]->option_value ) {
			wpscx_check_media_seo_empty_ent( $scanner_pro );
		}
		if ( 'true' === $settings[56]->option_value ) {
			wpscx_check_media_empty_ent( $scanner_pro );
		}
		if ( 'true' === $settings[57]->option_value ) {
			wpscx_check_ecommerce_empty_ent( $scanner_pro );
		}
		if ( 'true' === $settings[51]->option_value ) {
			wpscx_check_post_tag_descriptions_empty_ent( $scanner_pro );
		}
		if ( 'true' === $settings[52]->option_value ) {
			wpscx_check_post_categories_description_empty_ent( $scanner_pro );
		}
		if ( 'true' === $settings[47]->option_value ) {
			wpscx_check_author_empty( $scanner_base );
		}
	} else {
		if ( 'true' === $settings[47]->option_value ) {
			wpscx_check_author_empty( $scanner_base );
		}
		if ( 'true' === $settings[49]->option_value ) {
			wpscx_check_page_title_empty( $scanner_base );
		}
		if ( 'true' === $settings[50]->option_value ) {
			wpscx_check_post_title_empty( $scanner_base );
		}
	}

	if ( ! $wpscx_ent_included ) {
		$scanner_base->check_empty_wpsc();
	}

			$end = round( microtime( true ), 5 );
	wpscx_print_debug( 'Empty Entire Site', round( $end - $start, 5 ), $sql_count, round( memory_get_usage() / 1000, 5 ), 0 );
}
	add_action( 'adminscansiteempty', 'wpscx_scan_site_empty' );

function wpscx_check_empty_wpsc() {
		$scanner = new Wpscx_Seo_Scanner;
		$scanner->check_empty_wpsc();
}
	add_action( 'admincheckemptywpsc', 'wpscx_check_empty_wpsc' );

