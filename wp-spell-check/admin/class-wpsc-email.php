<?php

class Wpscx_Email {
	function email_admin() {
			global $wpdb;
			global $wpscx_ent_included;
			$table_name  = $wpdb->prefix . 'spellcheck_options';
			$words_table = $wpdb->prefix . 'spellcheck_words';
			$empty_table = $wpdb->prefix . 'spellcheck_empty';
			$html_table  = $wpdb->prefix . 'spellcheck_html';
			set_time_limit( 600 );
			sleep( 2 );

			$settings = $wpdb->get_results( 'SELECT option_value FROM ' . $table_name . ' WHERE option_name="email_address";' );

			$words_list = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $words_table . ' WHERE ignore_word is false' );
			$empty_list = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $empty_table . ' WHERE ignore_word is false' );
			$html_list  = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $html_table . ' WHERE ignore_word is false' );
			$login_url  = wp_login_url();

			$date        = gmdate( 'l jS' ) . ' of ' . gmdate( 'F Y' ) . ' at ' . gmdate( 'g:i:s A' );
			$options_url = get_site_url() . '/wp-admin/admin.php?page=wp-spellcheck-options.php';

			$output = '<strong>This email was sent from your website "' . get_option( 'blogname' ) . '" by the WP Spell Check plugin on ' . $date . '</strong><br /><br />';

			$output .= '<strong>We have finished the scan of your website and detected:</strong><br /><br />';

			$output .= '<strong>- ' . $words_list . ' Spelling Errors</strong><br />';

			$output .= '<strong>- ' . $empty_list . ' Empty Fields</strong> <br />';
			$output .= '<strong>- ' . $html_list . ' Broken Code</strong> <br /><br />';
			$output .= '<strong><a href="' . $login_url . '">Click here</a> to fix them now to improve your website Literacy Factor and SEO.</strong><br /><br />';

			$output .= '------------------------------------------------------------------------<br />';

		if ( ! $wpscx_ent_included ) {
			$output .= 'NOTE: You are using the free version of WP Spell check. <a href="https://www.wpspellcheck.com/pricing/">Upgrade</a> to Premium today to scan your entire site';
		}

			$headers  = "MIME-Version: 1.0\r\n";
			$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";

			$to_emails = explode( ',', $settings[0]->option_value );

			wp_mail( $to_emails, 'WP Spellcheck report for ' . get_option( 'blogname' ), $output, $headers );
	}

	function check_email_site_scan() {
		global $wpsc_settings;
		$scan_in_progress = wpscx_check_scan_progress();
		if ( 'true' === $wpsc_settings[0]->option_value && ! $scan_in_progress ) {
			$this->email_admin();
		}
	}

	function send_test_email() {
		global $wpdb;
		$table_name  = $wpdb->prefix . 'spellcheck_options';
		$words_table = $wpdb->prefix . 'spellcheck_words';
		set_time_limit( 600 );

		$settings   = $wpdb->get_results( 'SELECT option_value FROM ' . $table_name . ' WHERE option_name="email_address";' );
		$words_list = $wpdb->get_results( 'SELECT word FROM ' . $words_table . ' WHERE ignore_word is false' );

		$output   = 'This is a test email sent from WP Spell Check on ' . get_option( 'blogname' );
		$headers  = "MIME-Version: 1.0\r\n";
		$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";

		$to_emails   = explode( ',', $settings[0]->option_value );
		$valid_email = false;
		foreach ( $to_emails as $email_test ) {
			if ( ! filter_var( $email_test, FILTER_VALIDATE_EMAIL ) === false ) {
				$valid_email = true;
			}
		}
		if ( ! $valid_email ) {
			return 'Please enter a valid email address';
		}
		if ( wp_mail( $to_emails, 'Test Email from WP Spell Check', $output, $headers ) ) {
			return "<h3 style='color: rgb(0, 115, 0);'>A test email has been sent. Check your email and make sure you have received it. If you did not get it, install and setup WP Mail SMTP plugin.</h3>";
		} else {
			return 'An error has occurring in sending the test email';
		}
	}
}
