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
    const WPSCX_PRO_LOC = 'wp-spell-check-pro/wpspellcheckpro.php';
    const WPSCX_SETTINGS = '[wpsc_settings]';
    const WPSCX_GRAMMAR = '[wpsc_grammar]';
    const WPSCX_DICT = '[wpsc_dictionary]';
    const WPSCX_IGNORE = '[wpsc_ignore]';

class Wpscx_Options {

    /* Clear out the database for uninstallation */
	function prepare_uninstall() {
			global $wpdb;

			$sql = 'DROP TABLE ' . $wpdb->prefix . 'spellcheck_dictionary;';
			$wpdb->query( $sql );
			$sql = 'DROP TABLE ' . $wpdb->prefix . 'spellcheck_ignore;';
			$wpdb->query( $sql );
			$sql = 'DROP TABLE ' . $wpdb->prefix . 'spellcheck_options;';
			$wpdb->query( $sql );
			$sql = 'DROP TABLE ' . $wpdb->prefix . 'spellcheck_words;';
			$wpdb->query( $sql );
			$sql = 'DROP TABLE ' . $wpdb->prefix . 'spellcheck_empty;';
			$wpdb->query( $sql );
			$sql = 'DROP TABLE ' . $wpdb->prefix . 'spellcheck_html;';
			$wpdb->query( $sql );
			$sql = 'DROP TABLE ' . $wpdb->prefix . 'spellcheck_grammar;';
			$wpdb->query( $sql );
			$sql = 'DROP TABLE ' . $wpdb->prefix . 'spellcheck_grammar_options;';
			$wpdb->query( $sql );
                        //$sql = 'DROP TABLE ' . $wpdb->prefix . 'spellcheck_errors;';
			//$wpdb->query( $sql );
                        //$sql = 'DROP TABLE ' . $wpdb->prefix . 'spellcheck_error_catch;';
			//$wpdb->query( $sql );

			global $current_user;
			$user_id = $current_user->ID;
			delete_user_meta( $user_id, 'wpsc_pro_notice_date' );
			delete_user_meta( $user_id, 'wpsc_pro_dismissed' );
			delete_user_meta( $user_id, 'wpsc_ignore_review_notice' );
			delete_user_meta( $user_id, 'wpsc_review_date' );
			delete_user_meta( $user_id, 'wpsc_times_dismissed_review' );
			delete_user_meta( $user_id, 'wpsc_pro_ignore_notice' );
			delete_user_meta( $user_id, 'wpsc_pro_notice_date' );
			delete_user_meta( $user_id, 'wpsc_ignore_install_notice' );
			delete_user_meta( $user_id, 'wpsc_last_check' );
			delete_user_meta( $user_id, 'wpsc_version' );
			delete_user_meta( $user_id, 'wpsc_outdated' );
			delete_user_meta( $user_id, 'wpsc_pro_last_check' );
			delete_user_meta( $user_id, 'wpsc_pro_version' );
			delete_user_meta( $user_id, 'wpsc_pro_outdated' );
			delete_user_meta( $user_id, 'wpsc_ent_last_check' );
			delete_user_meta( $user_id, 'wpsc_ent_version' );
			delete_user_meta( $user_id, 'wpsc_ent_outdated' );
			delete_user_meta( $user_id, 'wpsc_update_notice_date' );
			delete_user_meta( $user_id, 'wpsc_usedyslexic' );
			delete_user_meta( $user_id, 'wpsc_warning_report' );
			delete_user_meta( $user_id, 'wpsc_safe_mode' );
			delete_user_meta( $user_id, 'wpsc_error_msg' );
			update_option( 'wpsc_data_acti', '' );
	}
}

function wpscx_render_options() {
	global $wpdb;
	global $wpscx_ent_included;
	global $wpscx_key_valid;
	global $wpsc_version;
	$options       = new Wpscx_Options;
	$table_name    = $wpdb->prefix . 'spellcheck_options';
	$ignore_table  = $wpdb->prefix . 'spellcheck_ignore';
	$grammar_table = $wpdb->prefix . 'spellcheck_grammar_options';
	$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
	$words_table   = $wpdb->prefix . 'spellcheck_words';
	ini_set( 'memory_limit', '512M' ); //Sets the PHP memory limit


	wp_enqueue_script( 'jquery-ui-dialog' );
	wp_enqueue_script( 'admin-js', plugin_dir_url( __FILE__ ) . '../js/feature-request.js' );
	wp_enqueue_script( 'feature-request', plugin_dir_url( __FILE__ ) . '../js/admin-js.js' );
	wp_enqueue_script( 'jquery.contextMenu', plugin_dir_url( __FILE__ ) . '../js/jquery.contextMenu.js' );
	wp_enqueue_script( 'jquery.ui.position', plugin_dir_url( __FILE__ ) . '../js/jquery.ui.position.js' );
	wp_enqueue_style( 'wpsc-admin-styles', plugin_dir_url( __DIR__ ) . 'css/admin-styles.css' );
	wp_enqueue_style( 'wpsc-sidebar', plugin_dir_url( __DIR__ ) . 'css/wpsc-sidebar.css' );

	$message = '';
		if ( isset( $_POST['uninstall'] ) && 'Clean up Database and Deactivate Plugin' === $_POST['uninstall'] ) {
			$options->prepare_uninstall();
			deactivate_plugins( 'wp-spell-check/wpspellcheck.php' );
			if ( $wpscx_ent_included ) {
				deactivate_plugins( WPSCX_PRO_LOC );
			}
			wp_die( 'WP Spell Check has been deactivated. If you wish to use the plugin again you may activate it on the WordPress plugin page' );
		}

	$next_scan = wp_next_scheduled( 'admincheckcode', array( 10, 1 ) );
	wp_unschedule_event( $next_scan, 'admincheckcode', array( 10, 1 ) );

	if ( isset( $_POST['import'] ) && isset($_FILES['import_file']['name']) && $wpscx_ent_included ) {
		if ( 'Import Plugin Data' === $_POST['import'] ) {
			$extension = end( explode( '.', sanitize_text_field( $_FILES['import_file']['name'] ) ) );
			if ( 'ini' !== $extension ) {
				wp_die( __( 'Please Upload a valid .ini file' ) ); //Check to make sure the imported file is a .ini file
			}

			$import_file = sanitize_text_field( $_FILES['import_file']['tmp_name'] );
			if ( empty( $import_file ) ) {
				wp_die( __( 'Please upload a file with content. Last file has no content' ) ); //Check to make sure that the imported file isn't empty
			}

			$input = file_get_contents( $import_file ); //Get the contents of the uploaded file

			$content = explode( "\r\n", $input );

			$to_add           = '[none]';
			$dict_dupe        = "<h3 style='color: rgb(200, 0, 0);'> The following words were not added to My Dictionary because they were a duplicate:";
			$dict_dupe_ig     = "<h3 style='color: rgb(200, 0, 0);'> The following words were not added to My Dictionary because they were found in the Ignore List:";
			$ignore_dupe      = "<h3 style='color: rgb(200, 0, 0);'> The following words were not added to Ignore List because they were a duplicate:";
			$ignore_dupe_dict = "<h3 style='color: rgb(200, 0, 0);'> The following words were not added to Ignore List because they were found in My Dictionary:";

			$dict_display        = false;
			$dict_display_ig     = false;
			$ignore_display      = false;
			$ignore_display_dict = false;

			foreach ( $content as $item ) {
				if ( '' === $item ) {
					continue;
				}
				if ( WPSCX_SETTINGS === $item ) {
					$to_add = WPSCX_SETTINGS;
				}
				if ( WPSCX_GRAMMAR === $item ) {
					$to_add = WPSCX_GRAMMAR;
				}
				if ( WPSCX_DICT === $item ) {
					$to_add = WPSCX_DICT;
				}
				if ( WPSCX_IGNORE === $item ) {
					$to_add = WPSCX_IGNORE;
				}
					//Check for the headers of each section and set flag accordingly

				if ( WPSCX_SETTINGS === $to_add && WPSCX_SETTINGS !== $item ) {
						$settings = explode( '=', $item );
					if ( sizeof( (array) $settings ) === 2 ) {
						$wpdb->update( $table_name, array( 'option_value' => $settings[1] ), array( 'option_name' => $settings[0] ) ); //Update the main settings table
					}
				} elseif ( WPSCX_GRAMMAR === $to_add && WPSCX_GRAMMAR !== $item ) {
						$settings = explode( '=', $item );
					if ( sizeof( (array) $settings ) === 2 ) {
						$wpdb->update( $grammar_table, array( 'option_value' => $settings[1] ), array( 'option_name' => $settings[0] ) ); //Update the grammar settings table
					}
				} elseif ( WPSCX_DICT === $to_add && WPSCX_DICT !== $item ) {
						$check_dict   = $wpdb->get_results( 'SELECT * FROM ' . $dict_table . ' WHERE word="' . $item . '"' );
						$check_ignore = $wpdb->get_results( 'SELECT * FROM ' . $words_table . ' WHERE word="' . $item . '" AND ignore_word = true' );

					if ( sizeof( (array) $check_dict ) > 0 ) {
							$dict_display = true;
							$dict_dupe   .= ' ' . $item . ',';
					} elseif ( sizeof( (array) $check_ignore ) > 0 ) {
							$dict_display_ig = true;
							$dict_dupe_ig   .= ' ' . $item . ',';
					} else {
							$wpdb->insert( $dict_table, array( 'word' => $item ) ); //Update the dictionary table
					}
				} elseif ( WPSCX_IGNORE === $to_add && WPSCX_IGNORE !== $item ) {
						$check_dict   = $wpdb->get_results( 'SELECT * FROM ' . $dict_table . ' WHERE word="' . $item . '"' );
						$check_ignore = $wpdb->get_results( 'SELECT * FROM ' . $words_table . ' WHERE word="' . $item . '" AND ignore_word = true' );

					if ( sizeof( (array) $check_dict ) > 0 ) {
							$ignore_display_dict = true;
							$ignore_dupe_dict   .= ' ' . $item . ',';
					} elseif ( sizeof( (array) $check_ignore ) > 0 ) {
							$ignore_display = true;
							$ignore_dupe   .= ' ' . $item . ',';
					} else {
							$wpdb->insert(
								$words_table,
								array(
									'word'        => $item,
									'page_name'   => 'WPSC_Ignore',
									'ignore_word' => true,
									'page_type'   => 'wpsc_ignore',
								)
							); //Update the ignore table
					}
				}
			}
		}

			$dict_dupe        = trim( $dict_dupe, ',' );
			$dict_dupe_ig     = trim( $dict_dupe_ig, ',' );
			$ignore_dupe      = trim( $ignore_dupe, ',' );
			$ignore_dupe_dict = trim( $ignore_dupe_dict, ',' );

			$dict_dupe        .= '</h3>';
			$dict_dupe_ig     .= '</h3>';
			$ignore_dupe      .= '</h3>';
			$ignore_dupe_dict .= '</h3>';

		if ( $dict_display ) {
			echo 'True';
		}

			$message = "<h3 style='color: rgb(0, 115, 0);'>Plugin data has been successfully imported</h3>";

		if ( true === $dict_display ) {
			$message .= $dict_dupe;
		}
		if ( true === $dict_display_ig ) {
			$message .= $dict_dupe_ig;
		}
		if ( true === $ignore_display ) {
			$message .= $ignore_dupe;
		}
		if ( true === $ignore_display_dict ) {
			$message .= $ignore_dupe_dict;
		}
	}

	//set defaults for anything not already set

	if ( ! isset( $_POST['email'] ) ) {
		$_POST['email'] = '';
	}
	if ( ! isset( $_POST['ignore-caps'] ) ) {
		$_POST['ignore-caps'] = '';
	}
	if ( ! isset( $_POST['check-pages'] ) ) {
		$_POST['check-pages'] = '';
	}
	if ( ! isset( $_POST['check-posts'] ) ) {
		$_POST['check-posts'] = '';
	}
	if ( ! isset( $_POST['check-authors'] ) ) {
		$_POST['check-authors'] = '';
	}
	if ( ! isset( $_POST['check-sliders'] ) ) {
		$_POST['check-sliders'] = '';
	}
	if ( ! isset( $_POST['check-media'] ) ) {
		$_POST['check-media'] = '';
	}
	if ( ! isset( $_POST['check-menu'] ) ) {
		$_POST['check-menus'] = '';
	}
	if ( ! isset( $_POST['page-titles'] ) ) {
		$_POST['page-titles'] = '';
	}
	if ( ! isset( $_POST['post-titles'] ) ) {
		$_POST['post-titles'] = '';
	}
	if ( ! isset( $_POST['tags'] ) ) {
		$_POST['tags'] = '';
	}
	if ( ! isset( $_POST['check-tag-desc'] ) ) {
		$_POST['check-tag-desc'] = '';
	}
	if ( ! isset( $_POST['check-tag-slug'] ) ) {
		$_POST['check-tag-slug'] = '';
	}
	if ( ! isset( $_POST['categories'] ) ) {
		$_POST['categories'] = '';
	}
	if ( ! isset( $_POST['check-cat-desc'] ) ) {
		$_POST['check-cat-desc'] = '';
	}
	if ( ! isset( $_POST['check-cat-slug'] ) ) {
		$_POST['check-cat-slug'] = '';
	}
	if ( ! isset( $_POST['seo-titles'] ) ) {
		$_POST['seo-titles'] = '';
	}
	if ( ! isset( $_POST['seo-desc'] ) ) {
		$_POST['seo-desc'] = '';
	}
	if ( ! isset( $_POST['page-slugs'] ) ) {
		$_POST['page-slugs'] = '';
	}
	if ( ! isset( $_POST['post-slugs'] ) ) {
		$_POST['post-slugs'] = '';
	}
	if ( ! isset( $_POST['check-ecommerce'] ) ) {
		$_POST['check-ecommerce'] = '';
	}
	if ( ! isset( $_POST['check-custom'] ) ) {
		$_POST['check-custom'] = '';
	}
	if ( ! isset( $_POST['ignore-emails'] ) ) {
		$_POST['ignore-emails'] = '';
	}
	if ( ! isset( $_POST['ignore-websites'] ) ) {
		$_POST['ignore-websites'] = '';
	}
	if ( ! isset( $_POST['highlight-words'] ) ) {
		$_POST['highlight-words'] = '';
	}
	if ( ! isset( $_POST['check-cf7'] ) ) {
		$_POST['check-cf7'] = '';
	}
	if ( ! isset( $_POST['check-post-drafts'] ) ) {
		$_POST['check-post-drafts'] = '';
	}
	if ( ! isset( $_POST['check-page-drafts'] ) ) {
		$_POST['check-page-drafts'] = '';
	}
	if ( ! isset( $_POST['check-authors-empty'] ) ) {
		$_POST['check-authors-empty'] = '';
	}
	if ( ! isset( $_POST['check-page-titles-empty'] ) ) {
		$_POST['check-page-titles-empty'] = '';
	}
	if ( ! isset( $_POST['check-post-titles-empty'] ) ) {
		$_POST['check-post-titles-empty'] = '';
	}
	if ( ! isset( $_POST['check-menu-empty'] ) ) {
		$_POST['check-menu-empty'] = '';
	}
	if ( ! isset( $_POST['check-tag-desc-empty'] ) ) {
		$_POST['check-tag-desc-empty'] = '';
	}
	if ( ! isset( $_POST['check-cat-desc-empty'] ) ) {
		$_POST['check-cat-desc-empty'] = '';
	}
	if ( ! isset( $_POST['check-page-seo-empty'] ) ) {
		$_POST['check-page-seo-empty'] = '';
	}
	if ( ! isset( $_POST['check-post-seo-empty'] ) ) {
		$_POST['check-post-seo-empty'] = '';
	}
	if ( ! isset( $_POST['check-media-seo-empty'] ) ) {
		$_POST['check-media-seo-empty'] = '';
	}
	if ( ! isset( $_POST['check-ecommerce-empty'] ) ) {
		$_POST['check-ecommerce-empty'] = '';
	}
	if ( ! isset( $_POST['check-media-empty'] ) ) {
		$_POST['check-media-empty'] = '';
	}
	if ( ! isset( $_POST['check-pages-grammar'] ) ) {
		$_POST['check-pages-grammar'] = '';
	}
	if ( ! isset( $_POST['check-posts-grammar'] ) ) {
		$_POST['check-posts-grammar'] = '';
	}
	if ( ! isset( $_POST['check-widgets'] ) ) {
		$_POST['check-widgets'] = '';
	}
	if ( ! isset( $_POST['wpsc-scan-tab'] ) ) {
		$_POST['wpsc-scan-tab'] = '';
	}
	if ( ! isset( $_POST['uninstall'] ) ) {
		$_POST['uninstall'] = '';
	}

		if ( isset( $_POST['submit'] ) && ('Update' === $_POST['submit'] || 'Send Test' === $_POST['submit']) ) {
			check_admin_referer( 'wpsc_update_options' );
			$message = "<h3 style='color: rgb(0, 115, 0);'>Options Updated</h3>";
			if ( 'email' === $_POST['email'] ) {
				$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'email' ) );
			} else {
				$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'email' ) );
			}
                        if ( isset( $_POST['email_address'] ) ) { $wpdb->update( $table_name, array( 'option_value' => sanitize_email( $_POST['email_address'] ) ), array( 'option_name' => 'email_address' ) ); }
			if ( 'ignore-caps' === $_POST['ignore-caps'] ) {
				$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'ignore_caps' ) );
			} else {
				$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'ignore_caps' ) );
			}
			if ( 'check-pages' === $_POST['check-pages'] ) {
				$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_pages' ) );
			} else {
				$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_pages' ) );
			}
			if ( 'check-posts' === $_POST['check-posts'] ) {
				$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_posts' ) );
			} else {
				$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_posts' ) );
			}
			if ( 'check-authors' === $_POST['check-authors'] ) {
				$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_authors' ) );
			} else {
				$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_authors' ) );
			}
			if ( $wpscx_ent_included ) {
				if ( 'check-sliders' === $_POST['check-sliders'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_sliders' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_sliders' ) );
				}
				if ( 'check-media' === $_POST['check-media'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_media' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_media' ) );
				}

				if ( 'check-menu' === $_POST['check-menu'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_menus' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_menus' ) );
				}
				if ( 'page-titles' === $_POST['page-titles'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'page_titles' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'page_titles' ) );
				}
				if ( 'post-titles' === $_POST['post-titles'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'post_titles' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'post_titles' ) );
				}
				if ( 'tags' === $_POST['tags'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'tags' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'tags' ) );
				}
				if ( 'check-tag-desc' === $_POST['check-tag-desc'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_tag_desc' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_tag_desc' ) );
				}
				if ( 'check-tag-slug' === $_POST['check-tag-slug'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_tag_slug' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_tag_slug' ) );
				}
				if ( 'categories' === $_POST['categories'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'categories' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'categories' ) );
				}
				if ( 'check-cat-desc' === $_POST['check-cat-desc'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_cat_desc' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_cat_desc' ) );
				}
				if ( 'check-cat-slug' === $_POST['check-cat-slug'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_cat_slug' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_cat_slug' ) );
				}
				if ( 'seo-titles' === $_POST['seo-titles'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'seo_titles' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'seo_titles' ) );
				}
				if ( 'seo-desc' === $_POST['seo-desc'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'seo_desc' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'seo_desc' ) );
				}
				if ( 'page-slugs' === $_POST['page-slugs'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'page_slugs' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'page_slugs' ) );
				}
				if ( 'post-slugs' === $_POST['post-slugs'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'post_slugs' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'post_slugs' ) );
				}
				if ( 'check-ecommerce' === $_POST['check-ecommerce'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_ecommerce' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_ecommerce' ) );
				}
				if ( 'check-widgets' === $_POST['check-widgets'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_widgets' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_widgets' ) );
				}
			}
			if ( 'check-custom' === $_POST['check-custom'] ) {
				$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_custom' ) );
			} else {
				$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_custom' ) );
			}
			if ( 'ignore-emails' === $_POST['ignore-emails'] ) {
				$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'ignore_emails' ) );
			} else {
				$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'ignore_emails' ) );
			}
			if ( 'ignore-websites' === $_POST['ignore-websites'] ) {
				$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'ignore_websites' ) );
			} else {
				$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'ignore_websites' ) );
			}
			if ( 'highlight-words' === $_POST['highlight-words'] ) {
				$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'highlight_word' ) );
			} else {
				$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'highlight_word' ) );
			}
			if ( 'check-cf7' === $_POST['check-cf7'] ) {
				$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_cf7' ) );
			} else {
				$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_cf7' ) );
			}

			if ( 'check-post-drafts' === $_POST['check-post-drafts'] ) {
				$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_post_drafts' ) );
			} else {
				$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'scan_post_drafts' ) );
			}
			if ( 'check-page-drafts' === $_POST['check-page-drafts'] ) {
				$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'scan_page_drafts' ) );
			} else {
				$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'scan_page_drafts' ) );
			}

			if ( 'check-authors' === $_POST['check-authors-empty'] ) {
				$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_authors_empty' ) );
			} else {
				$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_authors_empty' ) );
			}
			if ( 'check-page-titles' === $_POST['check-page-titles-empty'] ) {
				$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_page_titles_empty' ) );
			} else {
				$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_page_titles_empty' ) );
			}
			if ( 'check-post-titles' === $_POST['check-post-titles-empty'] ) {
				$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_post_titles_empty' ) );
			} else {
				$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_post_titles_empty' ) );
			}
			if ( $wpscx_ent_included ) {
				if ( 'check-menu' === $_POST['check-menu-empty'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_menu_empty' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_menu_empty' ) );
				}
				if ( 'check-tag-desc' === $_POST['check-tag-desc-empty'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_tag_desc_empty' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_tag_desc_empty' ) );
				}
				if ( 'check-cat-desc' === $_POST['check-cat-desc-empty'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_cat_desc_empty' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_cat_desc_empty' ) );
				}
				if ( 'check-page-seo' === $_POST['check-page-seo-empty'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_page_seo_empty' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_page_seo_empty' ) );
				}
				if ( 'check-post-seo' === $_POST['check-post-seo-empty'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_post_seo_empty' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_post_seo_empty' ) );
				}
				if ( 'check-media-seo' === $_POST['check-media-seo-empty'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_media_seo_empty' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_media_seo_empty' ) );
				}
				if ( 'check-ecommerce' === $_POST['check-ecommerce-empty'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_ecommerce_empty' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_ecommerce_empty' ) );
				}
				if ( 'check-media' === $_POST['check-media-empty'] ) {
					$wpdb->update( $table_name, array( 'option_value' => 'true' ), array( 'option_name' => 'check_media_empty' ) );
				} else {
					$wpdb->update( $table_name, array( 'option_value' => 'false' ), array( 'option_name' => 'check_media_empty' ) );
				}
			}

			if ( 'check-pages' === $_POST['check-pages-grammar'] ) {
				$wpdb->update( $grammar_table, array( 'option_value' => 'true' ), array( 'option_name' => 'check_pages' ) );
			} else {
				$wpdb->update( $grammar_table, array( 'option_value' => 'false' ), array( 'option_name' => 'check_pages' ) );
			}
			if ( 'check-posts' === $_POST['check-posts-grammar'] ) {
				$wpdb->update( $grammar_table, array( 'option_value' => 'true' ), array( 'option_name' => 'check_posts' ) );
			} else {
				$wpdb->update( $grammar_table, array( 'option_value' => 'false' ), array( 'option_name' => 'check_posts' ) );
			}
			if ( $wpscx_ent_included ) {
				if ( is_numeric( $_POST['scan_frequency'] ) ) {
						wpscx_set_schedule();
				} else {
						$message = 'Please enter a valid number for scan frequency';
				}
			} else {
				$next_scan = wp_next_scheduled( 'adminscansite', array( 10 ) );
				wp_unschedule_event( $next_scan, 'adminscansite', array( 10 ) );
			}
                        if ( isset( $_POST['scan_frequency_interval'] ) ) { $wpdb->update( $table_name, array( 'option_value' => sanitize_text_field( $_POST['scan_frequency_interval'] ) ), array( 'option_name' => 'scan_frequency_interval' ) ); }
                        if ( isset( $_POST['language_setting'] ) ) { $wpdb->update( $table_name, array( 'option_value' => sanitize_text_field( $_POST['language_setting'] ) ), array( 'option_name' => 'language_setting' ) ); }
                        if ( isset( $_POST['api_key'] ) ) { $wpdb->update( $table_name, array( 'option_value' => sanitize_text_field( $_POST['api_key'] ) ), array( 'option_name' => 'api_key' ) ); }


			$pages = explode( PHP_EOL, sanitize_text_field( $_POST['pages-ignore'] ) );

			$wpdb->query( 'TRUNCATE TABLE ' . $ignore_table );

			foreach ( $pages as $page ) {
				if ( null !== $page ) {
					$wpdb->insert(
						$ignore_table,
						array(
							'keyword' => $page,
							'type'    => 'page',
						)
					);
				}
			}

			if ( is_plugin_active( WPSCX_PRO_LOC ) ) {
				$pro_data = get_plugin_data( dirname( __FILE__ ) . '/../../wp-spell-check-pro/wpspellcheckpro.php' );
				$pro_ver  = $pro_data['Version'];
				if ( 'Clean up Database and Deactivate Plugin' !== $_POST['uninstall'] && is_plugin_active( WPSCX_PRO_LOC ) && version_compare( $pro_ver, $wpsc_version ) === 0 ) {
					wpsc_do_ent_api_request( true ); //Refresh the API Key validation after updating it unless deactivating plugin
				}
			}
			global $wpscx_key_valid;
			global $wpscx_ent_included;

			$user_id = get_current_user_id();
			update_usermeta( $user_id, 'wpsc_usedyslexic', sanitize_text_field( $_POST['wpsc_usedyslexic'] ) );
			//-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif
			if ( 'no' === $_POST['wpsc_usedyslexic'] || 'yes_websiteonly' === $_POST['wpsc_usedyslexic'] ) { ?>
		<style>
			*:not(.ab-icon) { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif!important; }
		</style>
			<?php } else { ?>
		<style>
			*:not(.ab-icon) { font-family: open-dyslexic, sans-serif!important }
		</style>
				<?php
			}
		}

	$settings         = $wpdb->get_results( 'SELECT option_name, option_value FROM ' . $table_name );
	$grammar_settings = $wpdb->get_results( 'SELECT option_name, option_value FROM ' . $grammar_table );

	$grammar_pages = $grammar_settings[0]->option_value;
	$grammar_posts = $grammar_settings[1]->option_value;

	$email                    = $settings[0]->option_value;
	$email_address            = $settings[1]->option_value;
	$ignore_caps              = $settings[3]->option_value;
	$check_pages              = $settings[4]->option_value;
	$check_posts              = $settings[5]->option_value;
	$check_menus              = $settings[7]->option_value;
	$scan_frequency           = $settings[8]->option_value;
	$scan_frequency_interval  = $settings[9]->option_value;
	$email_frequency_interval = $settings[10]->option_value;
	$language_setting         = $settings[11]->option_value;
	$page_titles              = $settings[12]->option_value;
	$post_titles              = $settings[13]->option_value;
	$tags                     = $settings[14]->option_value;
	$categories               = $settings[15]->option_value;
	$seo_desc                 = $settings[16]->option_value;
	$seo_titles               = $settings[17]->option_value;
	$page_slugs               = $settings[18]->option_value;
	$post_slugs               = $settings[19]->option_value;
	$api_key                  = $settings[20]->option_value;
	$ignore_emails            = $settings[23]->option_value;
	$ignore_websites          = $settings[24]->option_value;
	$check_sliders            = $settings[30]->option_value;
	$check_media              = $settings[31]->option_value;
	$highlight_words          = $settings[33]->option_value;
	$check_ecommerce          = $settings[36]->option_value;
	$check_cf7                = $settings[37]->option_value;
	$check_tag_desc           = $settings[38]->option_value;
	$check_tag_slug           = $settings[39]->option_value;
	$check_cat_desc           = $settings[40]->option_value;
	$check_cat_slug           = $settings[41]->option_value;
	$check_custom             = $settings[42]->option_value;
	$check_authors            = $settings[44]->option_value;
	$check_authors_empty      = $settings[47]->option_value;
	$check_menu_empty         = $settings[48]->option_value;
	$check_page_titles_empty  = $settings[49]->option_value;
	$check_post_titles_empty  = $settings[50]->option_value;
	$check_tag_desc_empty     = $settings[51]->option_value;
	$check_cat_desc_empty     = $settings[52]->option_value;
	$check_page_seo_empty     = $settings[53]->option_value;
	$check_post_seo_empty     = $settings[54]->option_value;
	$check_media_seo_empty    = $settings[55]->option_value;
	$check_media_empty        = $settings[56]->option_value;
	$check_ecommerce_empty    = $settings[57]->option_value;
	$check_page_drafts        = $settings[136]->option_value;
	$check_post_drafts        = $settings[137]->option_value;
	$check_widgets            = $settings[147]->option_value;

	$page_data = $wpdb->get_results( 'SELECT keyword FROM ' . $ignore_table . " WHERE type='page';" );
	$page_list = '';
	foreach ( $page_data as $page ) {
		$page_list .= $page->keyword . PHP_EOL;
	}
		if ( isset( $_POST['test-email'] ) && 'Send Test' === $_POST['test-email'] ) {
			$wpdb->update( $table_name, array( 'option_value' => sanitize_email( $_POST['email_address'] ) ), array( 'option_name' => 'email_address' ) );
				$emailer   = new Wpscx_Email;
			$message       = $emailer->send_test_email();
			$email_address = sanitize_email( $_POST['email_address'] );
		}

	wp_enqueue_script( 'options-nav', plugin_dir_url( __FILE__ ) . 'options-nav.js' );

	?>
		<style> p.submit { display: inline-block; margin-left: 10px; } .hidden { display: none; } .wpsc-scan-nav-bar { border-bottom: 1px solid #BBB; } .wpsc-scan-nav-bar a { text-decoration: none; margin: 5px 1px -1px 1px; padding: 8px; border: 1px solid #BBB; display: inline-block; font-weight: bold; color: black; font-size: 14px; height: 16px; border-radius: 5px 5px 0 0; } .wpsc-scan-nav-bar a.selected { border-bottom: 1px solid white; background: white; }
				td { padding-left: 25px!important; max-width: 755px; }
                                .wpsc-message h3 { color: rgb(0, 200, 0); font-size: 20px; font-weight: bold; }
				.wpsc-mouseover-text-pro-feature, .wpsc-mouseover-text-freq, .wpsc-mouseover-text-email, .wpsc-mouseover-text-import, .wpsc-mouseover-text-export { color: black; font-size: 12px; width: 225px; display: inline-block; position: absolute; margin: 0px; padding: 0px 0px 15px 0px; border: 2px solid #008200; border-radius: 7px; opacity: 0; background: white; z-index: -100; box-shadow: 2px 2px 10px 3px rgb(0 0 0 / 75%); font-weight: bold; max-width: 205px; }</style>
	<?php wpscx_show_feature_window(); ?>
		<div class="wrap">
			<h2><a href="admin.php?page=wp-spellcheck.php"><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'images/logo.png'; ?>" alt="WP Spell Check" /></a> <span style="position: relative; top: -8px;"> - Options</span></h2>
					<?php
					if ( '' !== $api_key && ! is_plugin_active( WPSCX_PRO_LOC ) ) {
						echo "<div class='error' style='color: red; font-weight: bold; font-size: 14px'>WP Spell Check Pro Version must be active. Please activate WP Spell Check Pro</div>";
					} elseif ( ! $wpscx_key_valid && '' !== $api_key ) {
						echo "<div class='error' style='color: red; font-weight: bold; font-size: 14px'>API Key not valid</div>";
					} elseif ( $wpscx_key_valid ) {
						echo "<div class='updated' style='color: rgb(0, 115, 0); font-weight: bold; font-size: 14px'>API Key is valid</div>";
					}
					?>
			<?php
			if ( '' !== $message ) {
				echo "<span class='wpsc-message'>" . htmlspecialchars_decode( esc_html( $message ) ) . '</span>';}
			?>
			<div class="wpsc-scan-nav-bar" style="width: 75%;">
				<a href="#general-options" id="wpsc-general-options" 
				<?php
				if ( isset( $_POST['wpsc-scan-tab'] ) && 'scan' !== $_POST['wpsc-scan-tab'] && 'empty' !== $_POST['wpsc-scan-tab'] && 'grammar' !== $_POST['wpsc-scan-tab'] && 'accessibility' !== $_POST['wpsc-scan-tab'] ) {
					echo 'class="selected"';}
				?>
				 name="wpsc-general-options">General Settings</a>
				<a href="#scan-options" id="wpsc-scan-options" 
				<?php
				if ( isset( $_POST['wpsc-scan-tab'] ) && 'scan' === $_POST['wpsc-scan-tab'] ) {
					echo 'class="selected"';}
				?>
				 name="wpsc-scan-options">Spell Check Options</a>
				<a href="#grammar-options" id="wpsc-grammar-options" 
				<?php
				if ( isset( $_POST['wpsc-scan-tab'] ) && 'grammar' === $_POST['wpsc-scan-tab'] ) {
					echo 'class="selected"';}
				?>
				 name="wpsc-grammar-options">Grammar Options</a>
				<a href="#empty-options" id="wpsc-empty-options" 
				<?php
				if ( isset( $_POST['wpsc-scan-tab'] ) && 'empty' === $_POST['wpsc-scan-tab'] ) {
					echo 'class="selected"';}
				?>
				 name="wpsc-empty-options">SEO Options<span style="font-size: 8px;"></span></a>
				<a href="#accessibility-options" id="wpsc-accessibility-options" 
				<?php
				if ( isset( $_POST['wpsc-scan-tab'] ) && 'accessibility' === $_POST['wpsc-scan-tab'] ) {
					echo 'class="selected"';}
				?>
				 name="wpsc-accessibility-options">Accessibility Options<span style="font-size: 8px;"></span></a>
			</div>
			<form action="admin.php?page=wp-spellcheck-options.php" method="post" name="options" style="margin-top: -7px;" enctype="multipart/form-data">
						<?php wp_nonce_field( 'wpsc_update_options' ); ?>
			<input type="hidden" name="wpsc-scan-tab" class="wpsc-nav-tab" value="<?php echo esc_attr( $_POST['wpsc-scan-tab'] ); ?>">
			<div id="wpsc-general-options-tab" 
			<?php
			if ( isset( $_POST['wpsc-scan-tab'] ) && ( 'scan' === $_POST['wpsc-scan-tab'] || 'empty' === $_POST['wpsc-scan-tab'] || 'grammar' === $_POST['wpsc-scan-tab'] || 'accessibility' === $_POST['wpsc-scan-tab'] ) ) {
				echo 'class="hidden"';}
			?>
			>
							<div style="width: 75%; float: left;">
			<table class="form-table wpsc-general-options-table" style="background: white; border-radius: 5px; box-shadow: 0px 0px 10px 0px rgb(0 0 0 / 50%); display: block; padding-top: 20px;" role="presentation"><tbody>
							<?php
							if ( ! $wpscx_ent_included ) {
								?>
								<tr><td>You're using WP Spell Check Base Version - no API key needed! <br><br>To unlock Pro features, <a target="_blank" href="https://www.wpspellcheck.com/pricing/?utm_source=baseplugin&utm_campaign=upgradeoptions&utm_medium=spellcheck_options&utm_content=<?php echo esc_attr( $wpsc_version ); ?>">Click here</a> to upgrade</td></tr><?php } ?>
								<tr><td style="padding-top: 30px; width: 755px;"><label style="display: inline-block; margin-bottom: 6px;">Already Upgraded? Simply enter your API key in the box below<br>and make sure to install and activate the Pro plugin as well!</label><br /><input type="text" name="api_key" value="<?php echo esc_attr( $api_key ); ?>" placeholder="Paste your API key here"></td>
				<td style="padding-top: 30px;"><label style="display: inline-block; margin-bottom: 6px;">Language</label><br /><select style="display: inline-block; width: 140px; height: 27px; margin-top: 1px;" name="language_setting">
<option value="en_CA" 
	<?php
	if ( 'en_CA' === $language_setting ) {
		echo "selected='selected'";}
	?>
>English (Canada)</option>
<option value="en_US" 
	<?php
	if ( 'en_US' === $language_setting ) {
		echo "selected='selected'";}
	?>
>English (US)</option>
<option value="en_UK" 
	<?php
	if ( 'en_UK' === $language_setting ) {
		echo "selected='selected'";}
	?>
>English (UK)</option>
</select></td></tr>
				<tr><td><label>Pages/Posts to ignore (Please enter Page/Post titles and place one on each line)</label></td><td colspan="2"><textarea name="pages-ignore" rows="4" cols="50"><?php echo esc_html( $page_list ); ?></textarea></td></tr>
								<tr><td colspan="2"><hr style="width: 50%;"><p style="text-align: center; margin-top: -5px; font-weight: bold;">Choose if you want to scan the pages/posts that are saved as drafts</p></td></tr>
				<tr><td><input class="ignore-check-all" type="checkbox" name="check-page-drafts" value="check-page-drafts" 
				<?php
				if ( 'true' === $check_page_drafts ) {
					echo 'checked';}
				?>
				>Scan Page Drafts</td>
				<td><input class="ignore-check-all" type="checkbox" name="check-post-drafts" value="check-post-drafts" 
				<?php
				if ( 'true' === $check_post_drafts ) {
					echo 'checked';}
				?>
				>Scan Post Drafts</td></tr>
				
				<tr colspan="2"><td><input type="submit" name="submit" value="Update" class="button button-primary" /></td></tr>
						</table>
							<table class="form-table wpsc-general-options-table" style="background: white; border-radius: 5px; box-shadow: 0px 0px 10px 0px rgb(0 0 0 / 50%); margin-top: 20px; display: block; padding-top: 20px;" role="presentation"><tbody>
								<tr><td colspan="2"><p style="text-align: center; font-weight: bold;">Setup email notifications to get notified about errors on your website, especially broken HTML and broken shortcode notifications.</p></td></tr>
				<tr><td><span style="display: inline-block; margin-bottom: 6px;"><input type="checkbox" name="email" value="email" 
				<?php
				if ( 'true' === $email ) {
					echo 'checked';}
				?>
				 <?php
					if ( ! $wpscx_ent_included ) {
										echo 'disabled';}
					?>
>Send Email Reports</span><br /><input type="text" name="email_address" value="<?php echo esc_attr( $email_address ); ?>" 
																							<?php
																							if ( ! $wpscx_ent_included ) {
																												echo 'disabled';}
																							?>
><input type="hidden" name="page" value="wp-spellcheck-options.php">
				<input type="hidden" name="action" value="check">
		<input type="submit" name="test-email" id="test-email" class="button button-primary" value="Send Test" 
		<?php
		if ( ! $wpscx_ent_included ) {
			echo 'disabled';}
		?>
		>
	 <?php
		if ( ! $wpscx_ent_included ) {
			?>
			<span class="wpsc-mouseover-email" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-email"><span style="display: block;text-align: center;font-size: 14px;padding: 10px 0 10px 0;background-color: #2271b1;color: white;margin-bottom: 8px;"> This is a Pro Feature</span><span style="padding: 0 10px; display: block;">To receive email reports,  <a href="https://www.wpspellcheck.com/pricing/" target="_blank">Click Here</a> to upgrade to WP Spell Check Pro.</span></span><?php } ?></td><td><label style="display: inline-block; margin-bottom: 6px; margin-top: -2px;">Scan Frequency</label><br />Every <input size="5" name="scan_frequency" style="border: 1px solid #ddd" value="<?php echo esc_html( $scan_frequency ); ?>" 
																																																																																																																																																							   <?php
																																																																																																																																																								if ( ! $wpscx_ent_included ) {
																																																																																																																																																									echo 'disabled';}
																																																																																																																																																								?>
			><select style="height: 27px; margin-top: 0px; display: inline-block;" name="scan_frequency_interval" 
	<?php
	if ( ! $wpscx_ent_included ) {
		echo 'disabled';}
	?>
>
<option value="hourly" 
	<?php
	if ( 'hourly' === $scan_frequency_interval ) {
		echo "selected='selected'";}
	?>
>Hour(s)</option>
<option value="daily" 
	<?php
	if ( 'daily' === $scan_frequency_interval ) {
		echo "selected='selected'";}
	?>
>Day(s)</option>
</select><span class="wpsc-mouseover-button-freq" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-freq"><span style="display: block;text-align: center;font-size: 14px;padding: 10px 0 10px 0;background-color: #2271b1;color: white;margin-bottom: 8px;"> Scheduled Scans</span><span style="padding: 0 10px; display: block;">For scans twice a day, set to 12 hours.<br>For scans once per week, set to 7 days.<br>For scans once per month, set to 30 days.</span></span></td></tr>
				<?php if ( true !== is_plugin_active( 'wp-mail-smtp/wp_mail_smtp.php' ) ) { ?>
					<tr><td colspan="2">To ensure emails are delivered, try using <a target="_blank" href="https://en-ca.wordpress.org/plugins/wp-mail-smtp/">WP Mail SMTP</a></td></tr>
				<?php } ?>
				<?php if ( '' === $email_address && 'true' === $email ) { ?>
					<tr><td colspan="2">An email address must be entered in order to receive email alerts</td></tr>
				<?php } ?>
								<tr><td colspan="2"><hr style="width: 50%;"></td></tr>
				<tr><td><h2>Import/Export Plugin Data</h2></td></tr>
				<tr>
					<td><input type="checkbox" class="export-dict" name="export-dict" value="true" 
					<?php
					if ( ! $wpscx_ent_included ) {
						echo 'disabled';}
					?>
					 /> Export My Dictionary<br><br><input type="checkbox" class="export-ignore" name="export-ignore" value="true" 
	<?php
	if ( ! $wpscx_ent_included ) {
						echo 'disabled';}
	?>
 /> Export Ignore List</td>
					<td><input type="file" name="import_file" id="import-file" 
					<?php
					if ( ! $wpscx_ent_included ) {
						echo 'disabled';}
					?>
					></td>
				</tr>
				<tr>
					<td><input type="submit" class="wpsc-export-data" name="export" value="Export Plugin Data" 
					<?php
					if ( ! $wpscx_ent_included ) {
						echo 'disabled';}
					?>
					 />
	 <?php
		if ( ! $wpscx_ent_included ) {
			?>
						<span class="wpsc-mouseover-export" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-export"><span style="display: block;text-align: center;font-size: 14px;padding: 10px 0 10px 0;background-color: #2271b1;color: white;margin-bottom: 8px;"> This is a Pro Feature</span><span style="padding: 0 10px; display: block;">To export plugin settings,  <a href="https://www.wpspellcheck.com/pricing/" target="_blank">Click Here</a> to upgrade to WP Spell Check Pro.</span></span><?php } ?></td>
					<td><input type="submit" name="import" value="Import Plugin Data" 
					<?php
					if ( ! $wpscx_ent_included ) {
						echo 'disabled';}
					?>
					 />
	 <?php
		if ( ! $wpscx_ent_included ) {
			?>
						<span class="wpsc-mouseover-import" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-import"><span style="display: block;text-align: center;font-size: 14px;padding: 10px 0 10px 0;background-color: #2271b1;color: white;margin-bottom: 8px;"> This is a Pro Feature</span><span style="padding: 0 10px; display: block;">To import plugin settings,  <a href="https://www.wpspellcheck.com/pricing/" target="_blank">Click Here</a> to upgrade to WP Spell Check Pro.</span></span><?php } ?></td>
				</tr>
							<?php if ( ! $wpscx_ent_included ) { ?>
								<tr><td style="color: red; font-weight: bold; font-size: 16px;"><a href="https://www.wpspellcheck.com/pricing/?utm_source=baseplugin&utm_campaign=upgradeoptions&utm_medium=spellcheck_options&utm_content=<?php echo esc_attr( $wpsc_version ); ?>" target="_blank">Upgrade to Pro</a> to send email reports and import/export your plugin data to or from other websites.</td></tr>
								<?php } ?>
								<tr><td colspan="2"><hr style="width: 50%;"></td></tr>
				<tr><td colspan="3"  align="left"><input type="submit" name="uninstall" value="Clean up Database and Deactivate Plugin" /><span style="margin-left: 10px;">This will deactivate WP Spell Check on all sites on the network and clean up the database of any changes made by WP Spell Check. If you wish to use WP Spell Check again after, you may activate it on the WordPress plugins page</span></td></tr>
								<tr><td><a href="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'wpsc-data.ini'; ?>" download id='wpsc-settings-download' hidden></a></td></tr>
			</tbody>
			</table>
							</div>
			</div>
			<div id="wpsc-scan-options-tab" 
			<?php
			if ( isset( $_POST['wpsc-scan-tab'] ) && 'scan' !== $_POST['wpsc-scan-tab'] ) {
				echo 'class="hidden"';}
			?>
			>
				<table class="form-table" style="width: 75%; float: left; background: white; border-radius: 5px; box-shadow: 0px 0px 10px 0px rgb(0 0 0 / 50%);" role="presentation"><tbody>
										<tr><td colspan="3"><h3>Select which parts of your website you'd like to check for spelling errors and click on the Update button</h3></td></tr>
				<?php if ( $wpscx_ent_included ) { ?>
				<tr><td><input type="checkbox" id="check-all" name="check-all" value="check-all">Select All</td></tr>
				<tr><td style="width: 33%;"><input type="checkbox" name="check-pages" value="check-pages" 
					<?php
					if ( 'true' === $check_pages ) {
						echo 'checked';}
					?>
				>Pages</td>
				<td style="width: 33%;"><input type="checkbox" name="page-titles" value="page-titles" 
					<?php
					if ( 'true' === $page_titles ) {
						echo 'checked';}
					?>
				>Page Titles</td>
				<td style="width: 33%;"><input type="checkbox" name="page-slugs" value="page-slugs" 
					<?php
					if ( 'true' === $page_slugs ) {
						echo 'checked';}
					?>
				>Page Slugs</td>
				</tr>
				<tr><td><input type="checkbox" name="check-posts" value="check-posts" 
					<?php
					if ( 'true' === $check_posts ) {
						echo 'checked';}
					?>
				>Posts</td>
				<td><input type="checkbox" name="post-titles" value="post-titles" 
					<?php
					if ( 'true' === $post_titles ) {
						echo 'checked';}
					?>
				>Post Titles</td>
				<td><input type="checkbox" name="post-slugs" value="post-slugs" 
					<?php
					if ( 'true' === $post_slugs ) {
						echo 'checked';}
					?>
				>Post Slugs</td>
				</tr>
				<tr><td><input type="checkbox" name="tags" value="tags" 
					<?php
					if ( 'true' === $tags ) {
						echo 'checked';}
					?>
				>Tags</td>
				<td><input type="checkbox" name="check-tag-desc" value="check-tag-desc" 
					<?php
					if ( 'true' === $check_tag_desc ) {
						echo 'checked';}
					?>
				>Tag Descriptions</td>
				<td><input type="checkbox" name="check-tag-slug" value="check-tag-slug" 
					<?php
					if ( 'true' === $check_tag_slug ) {
						echo 'checked';}
					?>
				>Tag Slugs</td>
				</tr>
				<tr><td><input type="checkbox" name="categories" value="categories" 
					<?php
					if ( 'true' === $categories ) {
						echo 'checked';}
					?>
				>Categories</td>
				<td><input type="checkbox" name="check-cat-desc" value="check-cat-desc" 
					<?php
					if ( 'true' === $check_cat_desc ) {
						echo 'checked';}
					?>
				>Category Descriptions</td>
				<td><input type="checkbox" name="check-cat-slug" value="check-cat-slug" 
					<?php
					if ( 'true' === $check_cat_slug ) {
						echo 'checked';}
					?>
				>Category Slugs</td>
				</tr>
				<tr><td><input type="checkbox" name="check-media" value="check-media" 
					<?php
					if ( 'true' === $check_media ) {
						echo 'checked';}
					?>
				>Media Files</td>
				<td><input type="checkbox" name="seo-desc" value="seo-desc" 
					<?php
					if ( 'true' === $seo_desc ) {
						echo 'checked';}
					?>
				>SEO Descriptions</td>
				<td><input type="checkbox" name="seo-titles" value="seo-titles" 
					<?php
					if ( 'true' === $seo_titles ) {
						echo 'checked';}
					?>
				>SEO Titles</td>
				</tr>
				<tr><td><input type="checkbox" name="check-menu" value="check-menu" 
					<?php
					if ( 'true' === $check_menus ) {
						echo 'checked';}
					?>
				>WordPress Menus</td>
				<td><input type="checkbox" name="check-sliders" value="check-sliders" 
					<?php
					if ( 'true' === $check_sliders ) {
						echo 'checked';}
					?>
				>Sliders</td>				
				<td><input type="checkbox" name="check-ecommerce" value="check-ecommerce" 
					<?php
					if ( 'true' === $check_ecommerce ) {
						echo 'checked';}
					?>
				>WooCommerce and WP-eCommerce Products</td></tr>
				<tr>
				<td><input type="checkbox" name="check-cf7" value="check-cf7" 
					<?php
					if ( 'true' === $check_cf7 ) {
						echo 'checked';}
					?>
				>Contact Form 7</td>
				<td><input type="checkbox" name="check-authors" value="check-authors" 
					<?php
					if ( 'true' === $check_authors ) {
						echo 'checked';}
					?>
				>Authors</td>
				<td><input type="checkbox" name="check-widgets" value="check-widgets" 
					<?php
					if ( 'true' === $check_widgets ) {
						echo 'checked';}
					?>
				>Widgets</td></tr>
				<tr><td><div style="margin-top: 25px;"></div></td></tr>
				<tr><td style="vertical-align: top;"><input class="ignore-check-all" type="checkbox" name="ignore-caps" value="ignore-caps" 
					<?php
					if ( 'true' === $ignore_caps ) {
						echo 'checked';}
					?>
				>Ignore fully capitalized words</td>
				<td style="vertical-align: top;"><input class="ignore-check-all" type="checkbox" name="ignore-emails" value="ignore-emails" 
					<?php
					if ( 'true' === $ignore_emails ) {
						echo 'checked';}
					?>
				>Ignore Email Addresses</td></tr>
				<tr><td style="vertical-align: top;"><input class="ignore-check-all" type="checkbox" name="ignore-websites" value="ignore-websites" 
					<?php
					if ( 'true' === $ignore_websites ) {
						echo 'checked';}
					?>
				>Ignore Website URLs</td>
								<td style="vertical-align: top;"><input 
								<?php
								if ( ! $wpscx_ent_included ) {
									echo 'disabled';}
								?>
								 class="ignore-check-all" type="checkbox" name="highlight-words" value="highlight-words" 
					<?php
					if ( 'true' === $highlight_words && ( $wpscx_ent_included ) ) {
						echo 'checked';}
					?>
>Highlight Misspelled Words (For logged in admin only)
					<?php
					if ( ! $wpscx_ent_included ) {
						?>
									<span class='wpsc-mouseover-pro-feature' style='border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?<span class="wpsc-mouseover-text-pro-feature"><span style="display: block;text-align: center;font-size: 14px;padding: 10px 0 10px 0;background-color: #2271b1;color: white;margin-bottom: 8px;"> This is a Pro Feature</span><span style="padding: 0 10px; display: block;">To highlight misspelled words, <a href="https://www.wpspellcheck.com/pricing/" target="_blank">Click Here</a> to upgrade to WP Spell Check Pro..</span></span></span><?php } ?></td></tr>
								<tr colspan="2"><td><input type="submit" name="submit" value="Update" class="button button-primary" /></td></tr>
				<?php } else { ?>
				<tr><td><input type="checkbox" id="check-all" name="check-all" value="check-all">Select All</td></tr>
				<tr><td style="width: 33%;"><input type="checkbox" name="check-pages" value="check-pages" 
					<?php
					if ( 'true' === $check_pages ) {
						echo 'checked';}
					?>
				>Pages</td>
				<td style="width: 33%;"><input type="checkbox" name="check-posts" value="check-posts" 
					<?php
					if ( 'true' === $check_posts ) {
						echo 'checked';}
					?>
				>Posts</td>
				<td style="width: 33%;"><input type="checkbox" name="check-cf7" value="check-cf7" 
					<?php
					if ( 'true' === $check_cf7 ) {
						echo 'checked';}
					?>
				>Contact Form 7</td></tr>
				<tr><td><input type="checkbox" name="check-authors" value="check-authors" 
					<?php
					if ( 'true' === $check_authors ) {
						echo 'checked';}
					?>
				>Authors</td></tr>
				<tr><td><div style="margin-top: 25px;"></div></td></tr>
				<tr><td><input class="ignore-check-all" type="checkbox" name="ignore-caps" value="ignore-caps" 
					<?php
					if ( 'true' === $ignore_caps ) {
						echo 'checked';}
					?>
				>Ignore fully capitalized words</td>
				<td><input class="ignore-check-all" type="checkbox" name="ignore-emails" value="ignore-emails" 
					<?php
					if ( 'true' === $ignore_emails ) {
						echo 'checked';}
					?>
				>Ignore Email Addresses</td></tr>
				<tr><td><input class="ignore-check-all" type="checkbox" name="ignore-websites" value="ignore-websites" 
					<?php
					if ( 'true' === $ignore_websites ) {
						echo 'checked';}
					?>
				>Ignore Website URLs</td>
				<td><input 
					<?php
					if ( ! $wpscx_ent_included ) {
						echo 'disabled';}
					?>
				 class="ignore-check-all" type="checkbox" name="highlight-words" value="highlight-words" 
					<?php
					if ( 'true' === $highlight_words && ( $wpscx_ent_included ) ) {
						echo 'checked';}
					?>
>Highlight Misspelled Words (For logged in admin only)
					<?php
					if ( ! $wpscx_ent_included ) {
						?>
					<span class='wpsc-mouseover-pro-feature' style='border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?<span class="wpsc-mouseover-text-pro-feature"><span style="display: block;text-align: center;font-size: 14px;padding: 10px 0 10px 0;background-color: #2271b1;color: white;margin-bottom: 8px;"> This is a Pro Feature</span><span style="padding: 0 10px; display: block;">To highlight misspelled words, <a href="https://www.wpspellcheck.com/pricing/" target="_blank">Click Here</a> to upgrade to WP Spell Check Pro.</span></span></span><?php } ?></td></tr>
								<tr colspan="2"><td><input type="submit" name="submit" value="Update" class="button button-primary" /></td></tr>
								<tr><td colspan="3"><hr style="width: 50%;"></td></tr>
					<tr style="background: white;"><td colspan="3"><h3 style="color: red;"><a href="https://www.wpspellcheck.com/pricing/?utm_source=baseplugin&utm_campaign=upgradeoptions&utm_medium=spellcheck_options&utm_content=<?php echo esc_attr( $wpsc_version ); ?>" target="_blank">Upgrade to Pro</a> to scan the following</h3></td></tr>
					<tr style="background: white;"><td>WordPress Menus</td><td>Page Titles</td><td>Post Titles</td></tr>
					<tr style="background: white;"><td>Tags</td><td>Tag Descriptions</td><td>Tag Slugs</td></tr>
					<tr style="background: white;"><td>Category Slugs</td><td>Categories</td><td>Category Descriptions</td></tr>
					<tr style="background: white;"><td>SEO Descriptions</td><td>SEO Titles</td><td>Page Slugs</td></tr>
					<tr style="background: white;"><td>Post Slugs</td><td>Sliders</td><td>Media Files</td></tr>
					<tr style="background: white;"><td>WooCommerce and WP-eCommerce Products</td></tr>
				<?php } ?>
				<?php
				if ( $wpscx_ent_included ) {
					?>
					<tr><td colspan="3"  align="left"><span style="font-size: 14px; font-weight: bold; color: red;">Warning: When updating <span style="color: black; text-decoration: underline;">page/post slugs</span>, some links contained within the theme may not be updated. Consult your webmaster before updating page/post slugs.<br /><a href="https://www.wpspellcheck.com/about/faqs#update-slugs" target="_blank">Click here to learn more</a></span><br /><br /><span style="font-size: 14px; font-weight: bold; color: red;">When updating <span style="color: black; text-decoration: underline;">Media filenames</span> this may cause images to stop working on your website. This does not apply to descriptions, alternate text, or captions.</span></td></tr> <?php } ?>
			</tbody></table>
		</div>
		<div id="wpsc-empty-options-tab" 
		<?php
		if ( isset( $_POST['wpsc-scan-tab'] ) && 'empty' !== $_POST['wpsc-scan-tab'] ) {
			echo 'class="hidden"';}
		?>
		>
				<table class="form-table" style="width: 75%; float: left; background: white; border-radius: 5px; box-shadow: 0px 0px 10px 0px rgb(0 0 0 / 50%);" role="presentation"><tbody>
									<tr><td colspan="3"><h3>Select which parts of your website you'd like to audit for SEO Empty Fields to improve your SEO. <a href="https://www.wpspellcheck.com/plugin-support/how-to-improve-wordpress-seo/" target="_blank">See Video</a></h3></td></tr>
				<tr><td><input type="checkbox" id="check-all-empty" name="check-all-empty" value="check-all-empty">Select All</td></tr>
				<?php if ( $wpscx_ent_included ) { ?>
				<tr><td style="width: 33%;"><input type="checkbox" name="check-authors-empty" value="check-authors" 
					<?php
					if ( 'true' === $check_authors_empty ) {
						echo 'checked';}
					?>
				>Authors</td>
				<td style="width: 33%;"><input type="checkbox" name="check-menu-empty" value="check-menu" 
					<?php
					if ( 'true' === $check_menu_empty ) {
						echo 'checked';}
					?>
				>WordPress Menus</td>
				<td style="width: 33%;"><input type="checkbox" name="check-page-titles-empty" value="check-page-titles" 
					<?php
					if ( 'true' === $check_page_titles_empty ) {
						echo 'checked';}
					?>
				>Page Titles</td></tr>
				<tr><td><input type="checkbox" name="check-post-titles-empty" value="check-post-titles" 
					<?php
					if ( 'true' === $check_post_titles_empty ) {
						echo 'checked';}
					?>
				>Post Titles</td>
				<td><input type="checkbox" name="check-tag-desc-empty" value="check-tag-desc" 
					<?php
					if ( 'true' === $check_tag_desc_empty ) {
						echo 'checked';}
					?>
				>Tag Descriptions</td>
				<td><input type="checkbox" name="check-cat-desc-empty" value="check-cat-desc" 
					<?php
					if ( 'true' === $check_cat_desc_empty ) {
						echo 'checked';}
					?>
				>Category Descriptions</td></tr>
				<tr><td><input type="checkbox" name="check-page-seo-empty" value="check-page-seo" 
					<?php
					if ( 'true' === $check_page_seo_empty ) {
						echo 'checked';}
					?>
				>Page SEO</td>
				<td><input type="checkbox" name="check-post-seo-empty" value="check-post-seo" 
					<?php
					if ( 'true' === $check_post_seo_empty ) {
						echo 'checked';}
					?>
				>Post SEO</td>
				<td><input type="checkbox" name="check-media-seo-empty" value="check-media-seo" 
					<?php
					if ( 'true' === $check_media_seo_empty ) {
						echo 'checked';}
					?>
				>Media Files SEO</td></tr>
				<tr><td><input type="checkbox" name="check-media-empty" value="check-media" 
					<?php
					if ( 'true' === $check_media_empty ) {
						echo 'checked';}
					?>
				>Media Files</td>
				<td><input type="checkbox" name="check-ecommerce-empty" value="check-ecommerce" 
					<?php
					if ( 'true' === $check_ecommerce_empty ) {
						echo 'checked';}
					?>
				>WooCommerce and WP-eCommerce Products</td>
				</tr>
								<tr colspan="2"><td><input type="submit" name="submit" value="Update" class="button button-primary" /></td></tr>
				<?php } else { ?>
				<tr><td style="width: 33%;"><input type="checkbox" name="check-authors-empty" value="check-authors" 
					<?php
					if ( 'true' === $check_authors_empty ) {
						echo 'checked';}
					?>
				>Authors</td>
					<td style="width: 33%;"><input type="checkbox" name="check-page-titles-empty" value="check-page-titles" 
					<?php
					if ( 'true' === $check_page_titles_empty ) {
						echo 'checked';}
					?>
					>Page Titles</td>
					<td style="width: 33%;"><input type="checkbox" name="check-post-titles-empty" value="check-post-titles" 
					<?php
					if ( 'true' === $check_post_titles_empty ) {
						echo 'checked';}
					?>
					>Post Titles</td></tr>
								<tr colspan="2"><td><input type="submit" name="submit" value="Update" class="button button-primary" /></td></tr>
								<tr><td colspan="3"><hr style="width: 50%;"></td></tr>
					<tr style="background: white;"><td colspan="3"><h3 style="color: red;"><a href="https://www.wpspellcheck.com/pricing/?utm_source=baseplugin&utm_campaign=upgradeoptions&utm_medium=seo_options&utm_content=<?php echo esc_attr( $wpsc_version ); ?>" target="_blank">Upgrade to Pro</a> to scan the following</h3></td></tr>
					<tr style="background: white;"><td>WordPress Menus</td><td>Tag Descriptions</td><td>Category Descriptions</td></tr>
					<tr style="background: white;"><td>Page SEO</td><td>Post SEO</td><td>Media Files SEO</td></tr>
					<tr style="background: white;"><td>Media Files</td><td colspan="2">WooCommerce and WP-eCommerce Products</td></tr>
				<?php } ?>
			</tbody></table>
		</div>
		<div id="wpgc-grammar-options-tab" 
		<?php
		if ( isset( $_POST['wpsc-scan-tab'] ) && 'grammar' !== $_POST['wpsc-scan-tab'] ) {
			echo 'class="hidden"';}
		?>
		>
			<table class="form-table" style="width: 75%; float: left; background: white; border-radius: 5px; box-shadow: 0px 0px 10px 0px rgb(0 0 0 / 50%);" role="presentation"><tbody>
								<tr><td colspan="3" style="padding-bottom: 0px;"><h3>Select whether you want to which parts of your website you'd like to scan for grammar errors. Right  now our plugin checks for the following rules: Complex Expression, Hidden Verb, Passive Voice, Possessive Ending, Redundant Expression, Contractions</h3></td></tr>
                                                                <tr><td colspan="3" style="padding-top: 0px;"><h3 style="font-size: 17px;">The WP Spell Check Grammar feature is designed to work with WordPress Classic Editor. You will need to have it active in order to see the Grammar Scan results</h3></td></tr>
				<tr><td><div style="margin-top: 5px;"></div></td></tr>
				<tr>
					<td style="width: 33%;"><input type="checkbox" name="check-pages-grammar" value="check-pages" 
					<?php
					if ( 'true' === $grammar_pages ) {
						echo 'checked';}
					?>
					>Pages</td>
					<td><input type="checkbox" name="check-posts-grammar" value="check-posts" 
					<?php
					if ( 'true' === $grammar_posts ) {
						echo 'checked';}
					?>
					>Posts</td>
				</tr>
							<?php
							if ( ! $wpscx_ent_included ) {
								?>
								<tr style="background: white;"><td colspan="3"><h3 style="color: red;"><a href="https://www.wpspellcheck.com/pricing/?utm_source=baseplugin&utm_campaign=upgradeoptions&utm_medium=spellcheck_options&utm_content=<?php echo esc_attr( $wpsc_version ); ?>" target="_blank">Upgrade to Pro</a> to scan your entire site</h3></td></tr><?php } ?>
				<tr colspan="2"><td><input type="submit" name="submit" value="Update" class="button button-primary" /></td></tr>
			</table>
		</div>
		<div id="wpgc-accessibility-options-tab" 
		<?php
		if ( isset( $_POST['wpsc-scan-tab'] ) && 'accessibility' !== $_POST['wpsc-scan-tab'] ) {
			echo 'class="hidden"';}
		?>
		>
			<table class="form-table" style="width: 75%; float: left; background: white; border-radius: 5px; box-shadow: 0px 0px 10px 0px rgb(0 0 0 / 50%);" role="presentation"><tbody>
				<tr><td><div style="margin-top: 5px;"></div></td></tr>
				<tr>
					<td>Use OpenDyslexic Font<br />You can use the OpenDyslexic font on the website or on both the website and the admin. The OpenDyslexic font is designed to help people with dyslexia with their reading.</td>
					<td>
						<select name="wpsc_usedyslexic" id="wpsc_usedyslexic" >
							<?php
							$user_id = get_current_user_id();
							?>
							<option value="no" <?php selected( 'no', get_user_meta( $user_id, 'wpsc_usedyslexic', true ) ); ?>><?php _e( 'Do Not use the OpenDyslexic Font', 'opendyslexic' ); ?></option>
							<option value="yes_adminonly" <?php selected( 'yes_adminonly', get_user_meta( $user_id, 'wpsc_usedyslexic', true ) ); ?>><?php _e( 'Use only on the admin area (back-end)', 'opendyslexic' ); ?></option>
							<option value="yes_websiteonly" <?php selected( 'yes_websiteonly', get_user_meta( $user_id, 'wpsc_usedyslexic', true ) ); ?>><?php _e( 'Use only on the website (front-end)', 'opendyslexic' ); ?></option>
							<option value="yes_everywhere" <?php selected( 'yes_everywhere', get_user_meta( $user_id, 'wpsc_usedyslexic', true ) ); ?>><?php _e( 'Use both on the website and Admin area', 'opendyslexic' ); ?></option>
						</select>
					</td>
				</tr>
				<tr colspan="2"><td><input type="submit" name="submit" value="Update" class="button button-primary" /></td></tr>
			</table>
		</div>
		</form>
	<?php include( 'sidebar.php' ); ?>
</div>
	<?php
}
?>
