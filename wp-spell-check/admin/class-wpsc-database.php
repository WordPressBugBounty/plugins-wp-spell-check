<?php
	const WPSCX_DEFAULT_TIME = '0 seconds';
class Wpscx_Database {
    
	public static function wpgc_database_init() {
		global $wpdb;

		$table_name    = $wpdb->prefix . 'spellcheck_grammar';
		$options_table = $wpdb->prefix . 'spellcheck_grammar_options';
		$html_table    = $wpdb->prefix . 'spellcheck_html';

		$charset_collate = '';

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}

		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE {$wpdb->collate}";
		}

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			page_id mediumint(9) NOT NULL,
			grammar mediumint(9) NOT NULL,
			is_ignored BOOLEAN,
			UNIQUE KEY id (id)
		) $charset_collate;";

		dbDelta( $sql );

		$sql = "CREATE TABLE $html_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			word varchar(120) NOT NULL,
			page_name varchar(255) NOT NULL,
			page_type varchar(255) NOT NULL,
			page_id int(9) NOT NULL,
			ignore_word bool DEFAULT false,
			UNIQUE KEY id (id)
		) $charset_collate;";

		dbDelta( $sql );

		$sql = "CREATE TABLE $options_table (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				option_name VARCHAR(100) NOT NULL,
				option_value VARCHAR(100) NOT NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";

		dbDelta( $sql );

		$check = $wpdb->get_results( 'SELECT * FROM ' . $options_table );

		if ( sizeof( $check ) < 1 ) {
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'check_pages',
					'option_value' => 'true',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'check_posts',
					'option_value' => 'true',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'scan_running',
					'option_value' => 'false',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'last_scan_time',
					'option_value' => '0 Seconds',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'pages_scanned',
					'option_value' => '0',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'posts_scanned',
					'option_value' => '0',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'last_scan_errors',
					'option_value' => '0',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'last_scan_type',
					'option_value' => '0',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'page_running',
					'option_value' => 'false',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'post_running',
					'option_value' => 'false',
				)
			);
		}
	}

	public static function wpsc_install_spellcheck() {
		global $wpdb;
		global $scdb_version;

		$table_name       = $wpdb->prefix . 'spellcheck_words';
		$dictionary_table = $wpdb->prefix . 'spellcheck_dictionary';
		$options_table    = $wpdb->prefix . 'spellcheck_options';
		$ignore_table     = $wpdb->prefix . 'spellcheck_ignore';
		$html_table       = $wpdb->prefix . 'spellcheck_html';

		$charset_collate = '';

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}

		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE {$wpdb->collate}";
		}

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			word varchar(100) NOT NULL,
			page_name varchar(100) NOT NULL,
			page_type varchar(100) NOT NULL,
			ignore_word bool DEFAULT false,
			UNIQUE KEY id (id)
		) $charset_collate;";

		dbDelta( $sql );

		$sql = "CREATE TABLE $dictionary_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			word VARCHAR(100) NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";

		dbDelta( $sql );

		$sql = "CREATE TABLE $options_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			option_name VARCHAR(100) NOT NULL,
			option_value VARCHAR(100) NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";

		dbDelta( $sql );

		$sql = "CREATE TABLE $ignore_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			keyword VARCHAR(100) NOT NULL,
			type VARCHAR(100) NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";

		dbDelta( $sql );

		$sql = "CREATE TABLE $html_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			word varchar(100) NOT NULL,
			page_name varchar(100) NOT NULL,
			page_type varchar(100) NOT NULL,
			page_id int(9) NOT NULL,
			ignore_word bool DEFAULT false,
			UNIQUE KEY id (id)
		) $charset_collate;";

			dbDelta( $sql );

		$check = $wpdb->get_results( 'SELECT * FROM ' . $options_table );

		if ( sizeof( $check ) < 1 ) {
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'email',
					'option_value' => 'false',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'email_address',
					'option_value' => '',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'email_frequency',
					'option_value' => '1',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'ignore_caps',
					'option_value' => 'false',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'check_pages',
					'option_value' => 'true',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'check_posts',
					'option_value' => 'true',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'check_theme',
					'option_value' => 'false',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'check_menus',
					'option_value' => 'true',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'scan_frequency',
					'option_value' => '1',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'scan_frequency_interval',
					'option_value' => 'daily',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'email_frequency_interval',
					'option_value' => 'daily',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'language_setting',
					'option_value' => 'en_US',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'page_titles',
					'option_value' => 'true',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'post_titles',
					'option_value' => 'true',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'tags',
					'option_value' => 'true',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'categories',
					'option_value' => 'true',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'seo_desc',
					'option_value' => 'true',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'seo_titles',
					'option_value' => 'true',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'page_slugs',
					'option_value' => 'true',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'post_slugs',
					'option_value' => 'true',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'api_key',
					'option_value' => '',
				)
			);

			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'pro_word_count',
					'option_value' => '0',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'total_word_count',
					'option_value' => '0',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'ignore_emails',
					'option_value' => 'true',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'ignore_websites',
					'option_value' => 'true',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'scan_in_progress',
					'option_value' => 'false',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'last_scan_started',
					'option_value' => '0',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'last_scan_finished',
					'option_value' => '0',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'page_count',
					'option_value' => '0',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'post_count',
					'option_value' => '0',
				)
			);
		}

		add_option( 'scdb_version', $scdb_version );

		$table_name    = $wpdb->prefix . 'spellcheck_grammar';
		$options_table = $wpdb->prefix . 'spellcheck_grammar_options';

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			page_id mediumint(9) NOT NULL,
			grammar mediumint(9) NOT NULL,
			is_ignored BOOLEAN,
			UNIQUE KEY id (id)
		) $charset_collate;";

		dbDelta( $sql );

		$sql = "CREATE TABLE $options_table (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				option_name VARCHAR(100) NOT NULL,
				option_value VARCHAR(100) NOT NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";

		dbDelta( $sql );

			$database = new Wpscx_Database;
		$database::wpgc_database_init(); //Initialize the grammar database
	}

	public static function wpsc_install_spellcheck_main() {
		global $wpdb;
                
                $database = new Wpscx_Database;
		$database::wpsc_install_spellcheck();
	}

	function wpsc_update_db_check() {
		if ( isset( $_POST['uninstall'] ) && 'Clean up Database and Deactivate Plugin' === $_POST['uninstall'] ) {
			return;
		}
		global $wpdb;
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$empty_table   = $wpdb->prefix . 'spellcheck_empty';
		$table_name    = $wpdb->prefix . 'spellcheck_words';
		$html_table    = $wpdb->prefix . 'spellcheck_html';

		wpscx_set_global_vars();
		global $check_opt;
		global $wpgc_settings;

			$options_check = $wpdb->get_results( "SHOW TABLES LIKE '$options_table'" );
		while ( sizeof( $options_check ) < 1 ) {
			sleep( 1 );
			$options_check = $wpdb->get_results( "SHOW TABLES LIKE '$options_table'" );
		}

			$charset_collate = '';

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}

		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE {$wpdb->collate}";
		}

			$sql = "CREATE TABLE $empty_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			word varchar(100) NOT NULL,
			page_name varchar(100) NOT NULL,
			page_type varchar(100) NOT NULL,
			ignore_word bool DEFAULT false,
			page_id mediumint(9),
			UNIQUE KEY id (id)
		) $charset_collate;";

			dbDelta( $sql );

			$sql = "CREATE TABLE $html_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			word varchar(100) NOT NULL,
			page_name varchar(100) NOT NULL,
			page_type varchar(100) NOT NULL,
			page_id int(9) NOT NULL,
			ignore_word bool DEFAULT false,
			UNIQUE KEY id (id)
		) $charset_collate;";

			dbDelta( $sql );

			$charset_collate = '';

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}

		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE {$wpdb->collate}";
		}

			$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			word varchar(100) NOT NULL,
			page_name varchar(100) NOT NULL,
			page_type varchar(100) NOT NULL,
			ignore_word bool DEFAULT false,
			page_id mediumint(9),
			UNIQUE KEY id (id)
		) $charset_collate;";

			dbDelta( $sql );

			$table_name    = $wpdb->prefix . 'spellcheck_grammar';
			$options_table = $wpdb->prefix . 'spellcheck_grammar_options';

			$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			page_id mediumint(9) NOT NULL,
			grammar mediumint(9) NOT NULL,
			is_ignored BOOLEAN,
			UNIQUE KEY id (id)
		) $charset_collate;";

			dbDelta( $sql );

			$sql = "CREATE TABLE $options_table (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				option_name VARCHAR(100) NOT NULL,
				option_value VARCHAR(100) NOT NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";

			dbDelta( $sql );

                
                if ( sizeof( $options_check ) !== 0 ) {
                        
                $check = $wpdb->get_results( 'SELECT * FROM ' . $options_table );

		if ( sizeof( $check ) < 1 ) {
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'check_pages',
					'option_value' => 'true',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'check_posts',
					'option_value' => 'true',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'scan_running',
					'option_value' => 'false',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'last_scan_time',
					'option_value' => '0 Seconds',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'pages_scanned',
					'option_value' => '0',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'posts_scanned',
					'option_value' => '0',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'last_scan_errors',
					'option_value' => '0',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'last_scan_type',
					'option_value' => '0',
				)
			);
		} elseif ( sizeof( $check ) < 9 ) {
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'page_running',
					'option_value' => 'false',
				)
			);
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'post_running',
					'option_value' => 'false',
				)
			);
		} elseif ( sizeof( $check ) < 11 ) {
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'scan_start_time',
					'option_value' => '0',
				)
			);
		} elseif ( sizeof( $check ) < 12 ) {
			$wpdb->insert(
				$options_table,
				array(
					'option_name'  => 'pro_error_count',
					'option_value' => '0',
				)
			);
		}
                
                }

			$options_table = $wpdb->prefix . 'spellcheck_options';

		if ( sizeof( $options_check ) !== 0 ) {
			$check = $wpdb->get_results( 'SELECT * FROM ' . $options_table );

			if ( sizeof( $check ) < 32 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_sliders',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_media',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'highlight_word',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'highlight_word',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'highlight_word',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_ecommerce',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_cf7',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_tag_desc',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_tag_slug',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_cat_desc',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_cat_slug',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_custom',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_scan_date',
						'option_value' => time(),
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_authors',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_scan_type',
						'option_value' => 'None',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_checked',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_authors_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_menu_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_page_titles_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_post_titles_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_tag_desc_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_cat_desc_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_page_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_post_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_media_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_media_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_ecommerce_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_scan_in_progress',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_empty_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_empty_type',
						'option_value' => 'None',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'literary_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_empty_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_page_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_post_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_max_pages',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check_time',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_scan_running',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_errors',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_widgets',
						'option_value' => 'true',
					)
				);
			} elseif ( sizeof( $check ) < 37 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_ecommerce',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_cf7',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_tag_desc',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_tag_slug',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_cat_desc',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_cat_slug',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_custom',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_scan_date',
						'option_value' => time(),
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_authors',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_scan_type',
						'option_value' => 'None',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_checked',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_authors_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_menu_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_page_titles_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_post_titles_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_tag_desc_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_cat_desc_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_page_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_post_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_media_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_media_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_ecommerce_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_scan_in_progress',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_empty_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_empty_type',
						'option_value' => 'None',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'literary_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_empty_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_page_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_post_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_max_pages',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check_time',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_scan_running',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_errors',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_widgets',
						'option_value' => 'true',
					)
				);
			} elseif ( sizeof( $check ) < 38 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_cf7',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_tag_desc',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_tag_slug',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_cat_desc',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_cat_slug',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_custom',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_scan_date',
						'option_value' => time(),
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_authors',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_scan_type',
						'option_value' => 'None',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_checked',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_authors_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_menu_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_page_titles_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_post_titles_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_tag_desc_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_cat_desc_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_page_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_post_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_media_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_media_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_ecommerce_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_scan_in_progress',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_empty_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_empty_type',
						'option_value' => 'None',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'literary_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_empty_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_page_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_post_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_max_pages',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check_time',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_scan_running',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_errors',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_widgets',
						'option_value' => 'true',
					)
				);
			} elseif ( sizeof( $check ) < 39 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_tag_desc',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_tag_slug',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_cat_desc',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_cat_slug',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_custom',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_scan_date',
						'option_value' => time(),
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_authors',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_scan_type',
						'option_value' => 'None',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_checked',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_authors_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_menu_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_page_titles_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_post_titles_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_tag_desc_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_cat_desc_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_page_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_post_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_media_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_media_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_ecommerce_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_scan_in_progress',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_empty_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_empty_type',
						'option_value' => 'None',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'literary_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_empty_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_page_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_post_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_max_pages',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check_time',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_scan_running',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_errors',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_widgets',
						'option_value' => 'true',
					)
				);
			} elseif ( sizeof( $check ) < 43 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_custom',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_scan_date',
						'option_value' => time(),
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_authors',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_scan_type',
						'option_value' => 'None',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_checked',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_authors_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_menu_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_page_titles_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_post_titles_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_tag_desc_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_cat_desc_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_page_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_post_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_media_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_media_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_ecommerce_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_scan_in_progress',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_empty_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_empty_type',
						'option_value' => 'None',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'literary_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_empty_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_page_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_post_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_max_pages',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check_time',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_scan_running',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_errors',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_widgets',
						'option_value' => 'true',
					)
				);
			} elseif ( sizeof( $check ) < 45 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_authors',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_scan_type',
						'option_value' => 'None',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_checked',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_authors_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_menu_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_page_titles_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_post_titles_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_tag_desc_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_cat_desc_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_page_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_post_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_media_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_media_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_ecommerce_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_scan_in_progress',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_empty_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_empty_type',
						'option_value' => 'None',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'literary_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_empty_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_page_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_post_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_max_pages',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check_time',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_scan_running',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_errors',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_widgets',
						'option_value' => 'true',
					)
				);
			} elseif ( sizeof( $check ) < 46 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_scan_type',
						'option_value' => 'None',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_checked',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_authors_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_menu_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_page_titles_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_post_titles_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_tag_desc_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_cat_desc_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_page_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_post_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_media_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_media_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_ecommerce_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_scan_in_progress',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_empty_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_empty_type',
						'option_value' => 'None',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'literary_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_empty_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_page_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_post_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_max_pages',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check_time',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_scan_running',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_errors',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_widgets',
						'option_value' => 'true',
					)
				);
			} elseif ( sizeof( $check ) < 47 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_checked',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_authors_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_menu_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_page_titles_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_post_titles_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_tag_desc_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_cat_desc_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_page_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_post_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_media_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_media_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_ecommerce_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_scan_in_progress',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_empty_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_empty_type',
						'option_value' => 'None',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'literary_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_empty_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_page_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_post_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_max_pages',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check_time',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_scan_running',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_errors',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_widgets',
						'option_value' => 'true',
					)
				);
			} elseif ( sizeof( $check ) < 48 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_authors_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_menu_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_page_titles_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_post_titles_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_tag_desc_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_cat_desc_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_page_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_post_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_media_seo_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_media_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_ecommerce_empty',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_scan_in_progress',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_empty_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_empty_type',
						'option_value' => 'None',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'literary_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_empty_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_page_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_post_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_max_pages',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check_time',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_scan_running',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_errors',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_widgets',
						'option_value' => 'true',
					)
				);
			} elseif ( sizeof( $check ) < 58 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_scan_in_progress',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_empty_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_empty_type',
						'option_value' => 'None',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'literary_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_empty_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_page_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_post_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_max_pages',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check_time',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_scan_running',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_errors',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_widgets',
						'option_value' => 'true',
					)
				);
			} elseif ( sizeof( $check ) < 60 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_empty_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_empty_type',
						'option_value' => 'None',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'literary_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_empty_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_page_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_post_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_max_pages',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check_time',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_scan_running',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_errors',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_widgets',
						'option_value' => 'true',
					)
				);
			} elseif ( sizeof( $check ) < 63 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_empty_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_empty_type',
						'option_value' => 'None',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'literary_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_empty_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_page_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_post_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_max_pages',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check_time',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_scan_running',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_errors',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_widgets',
						'option_value' => 'true',
					)
				);
			} elseif ( sizeof( $check ) < 64 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_empty_type',
						'option_value' => 'None',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_empty_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_page_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_post_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_max_pages',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check_time',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_scan_running',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_errors',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_widgets',
						'option_value' => 'true',
					)
				);
			} elseif ( sizeof( $check ) < 65 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'literary_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_factor',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_empty_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_page_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_post_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_max_pages',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check_time',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_scan_running',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_errors',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_widgets',
						'option_value' => 'true',
					)
				);
			} elseif ( sizeof( $check ) < 67 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'entire_empty_scan',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'seo_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cf7_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'tag_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'cat_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'page_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'post_slug_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'slider_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_seo_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_author_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_menu_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_page_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_post_title_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_tag_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_cat_desc_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_ecommerce_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_media_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'empty_free_sip_finish',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_page_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_post_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_max_pages',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check_time',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_scan_running',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_errors',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_widgets',
						'option_value' => 'true',
					)
				);
			} elseif ( sizeof( $check ) < 137 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_page_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'scan_post_drafts',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_max_pages',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check_time',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_scan_running',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_errors',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_widgets',
						'option_value' => 'true',
					)
				);
			} elseif ( sizeof( $check ) < 139 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'pro_max_pages',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check_time',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'api_check',
						'option_value' => '',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_scan_running',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_errors',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_widgets',
						'option_value' => 'true',
					)
				);
			} elseif ( sizeof( $check ) < 142 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_scan_running',
						'option_value' => 'false',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_errors',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_page_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_post_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_media_count',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_widgets',
						'option_value' => 'true',
					)
				);
			} elseif ( sizeof( $check ) < 147 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_last_scan_time',
						'option_value' => '0 Seconds',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'html_scan_start_time',
						'option_value' => '0',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_widgets',
						'option_value' => 'true',
					)
				);
			} elseif ( sizeof( $check ) < 149 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'check_widgets',
						'option_value' => 'true',
					)
				);
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'widget_sip',
						'option_value' => 'false',
					)
				);
			} elseif ( sizeof( $check ) < 151 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'last_php_error',
						'option_value' => 'None',
					)
				);
							$wpdb->insert(
								$options_table,
								array(
									'option_name'  => 'last_sql_error',
									'option_value' => 'None',
								)
							);
			} elseif ( sizeof( $check ) < 152 ) {
				$wpdb->insert(
					$options_table,
					array(
						'option_name'  => 'openAIKey',
						'option_value' => 'None',
					)
				);
			}
		}
	}

	function wpsc_update_db_check_main() {
		global $wpdb;
                
		$this->wpsc_update_db_check();
	}
}
