<?php
        const WPSCX_IGNORE_STRING = 'The following words were already found in the ignore list: ';
        const WPSCX_DICT_STRING = 'The following words were already found in the dictionary: ';
class Wpscx_Dashboard {

	function __construct() {}

	function add_dashboard_widget() {
		if ( current_user_can( 'manage_options' ) ) {
				wp_add_dashboard_widget(
					'wp_spellcheck_widget',
					'WP Spell Check',
					array( $this, 'create_dashboard_widget' )
				);
		}
	}


	function create_dashboard_widget() {
			global $wpdb;

			$table_name = $wpdb->prefix . 'spellcheck_words';

			$options_table = $wpdb->prefix . 'spellcheck_options';
			$empty_table   = $wpdb->prefix . 'spellcheck_empty';

			$check_db = $wpdb->get_results( "SHOW TABLES LIKE '$options_table'" );

		if ( sizeof( $check_db ) >= 1 ) {
			$empty_count = $wpdb->get_var( "SELECT COUNT(*) FROM $empty_table WHERE ignore_word!=1" );
			$word_count  = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE ignore_word!=1" );

			$literacy_factor = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='literary_factor';" );
			$literacy_factor = $literacy_factor[0]->option_value;
			$empty_factor    = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='empty_factor';" );
			$empty_factor    = $empty_factor[0]->option_value;
			echo "<p><span style='color: rgb(0, 115, 0); font-weight: bold;'>Website Literacy Factor: </span><span style='color: red; font-weight: bold;'>" . esc_html( $literacy_factor ) . '%</span><br />';
			echo "<span style='color: rgb(0, 115, 0); font-weight: bold;'>Website Empty Fields Factor: </span><span style='color: red; font-weight: bold;'>" . esc_html( $empty_factor ) . '%</span><br />';
			echo 'The last spell check scan found ' . esc_html( $word_count ) . ' spelling errors<br />';
			echo 'The last empty fields scan found ' . esc_html( $empty_count ) . ' empty fields<br />';
			echo "<a href='/wp-admin/admin.php?page=wp-spellcheck.php'>Click here</a> To view and fix errors</p>";
		}
	}
}

class Wpscx_OpenAI {
    private function generateSEODescription($text, $api_key) {
        // Define the API endpoint URL
        $api_url = "https://api.openai.com/v1/chat/completions";
        $query = "Generate SEO Description: " . $text . " ";

        // Define the request parameters
        $request_params = array(
            "model" => "gpt-3.5-turbo",
            "messages" => array(
                array(
                    "role" => "user",
                    "content" => $query
                )
            ),
            "max_tokens" => 32,
            "temperature" => 0
        );

        // Encode the request parameters as JSON
        $request_data = json_encode($request_params);

        // Define the cURL options
        $options = array(
            CURLOPT_URL => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: Bearer ".$api_key
            ),
            CURLOPT_POSTFIELDS => $request_data
        );

        // Initialize the cURL session
        $curl = curl_init();

        // Set the cURL options
        curl_setopt_array($curl, $options);

        // Execute the cURL request
        $response = curl_exec($curl);

        // Close the cURL session
        curl_close($curl);

        // Decode the response as JSON
        $response_data = json_decode($response, true);
            //print_r($response_data);
        
        /*$loc        = dirname( __FILE__ ) . '/../../../../debug.log';
	$debug_file = fopen( $loc, 'a' );
	$debug_var  = fwrite( $debug_file, "Text: " . $text . "\r\n" );
        $debug_var  = fwrite( $debug_file, "Response: " . print_r($response_data, true) . "\r\n" );
	fclose( $debug_file );*/

        // Check if the API returned an error
        if (!empty($response_data["error"])) {
            return "Error: ".$response_data["error"]["message"];
        }

        // Extract the generated description from the response
        $description = $response_data["choices"][0]["message"]["content"];
        

        // Return the generated description
        return $description;
    }

    private function generateSEOTitle($text, $api_key) {
        // Define the API endpoint URL
        $api_url = "https://api.openai.com/v1/chat/completions";
        $query = "Generate SEO Title: " . $text . " ";

        // Define the request parameters
        $request_params = array(
            "model" => "gpt-3.5-turbo",
            "messages" => array(
                array(
                    "role" => "user",
                    "content" => $query
                )
            ),
            "max_tokens" => 20,
            "temperature" => 0
        );

        // Encode the request parameters as JSON
        $request_data = json_encode($request_params);

        // Define the cURL options
        $options = array(
            CURLOPT_URL => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: Bearer ".$api_key
            ),
            CURLOPT_POSTFIELDS => $request_data
        );

        // Initialize the cURL session
        $curl = curl_init();

        // Set the cURL options
        curl_setopt_array($curl, $options);

        // Execute the cURL request
        $response = curl_exec($curl);

        // Close the cURL session
        curl_close($curl);

        // Decode the response as JSON
        $response_data = json_decode($response, true);
            //print_r($response_data);
        
        //$loc        = dirname( __FILE__ ) . '/debug.log';
	//$debug_file = fopen( $loc, 'a' );
        //$debug_var  = fwrite( $debug_file, "Response: " . print_r($response_data, true) . "\r\n" );
	//fclose( $debug_file );

        // Check if the API returned an error
        if (!empty($response_data["error"])) {
            return "Error: ".$response_data["error"]["message"];
        }

        // Extract the generated description from the response
        $description = $response_data["choices"][0]["message"]["content"];

        // Return the generated description
        return $description;
    }
    
    public function getTitle($post_id) {
        global $wpsc_settings;
        $api_key = $wpsc_settings[151]->option_value;
        
        $post = get_post($post_id);
        $text = $post->post_title . " " . $post->post_content;
        $text = strip_tags($text);
        
        if (strlen($text) > 10000) {
            $text = substr($text,0,10000);
        }
        
        return trim(str_replace("SEO Title: ", "", $this->generateSEOTitle($text, $api_key)));
    }
    
    public function getDesc($post_id) {
        global $wpsc_settings;
        $api_key = $wpsc_settings[151]->option_value;
        
        $post = get_post($post_id);
        $text = $post->post_title . " " . $post->post_content;
        $text = strip_tags($text);
        
        if (strlen($text) > 10000) {
            $text = substr($text,0,10000);
        }
        
        return trim(str_replace("SEO Description: ", "", $this->generateSEODescription($text, $api_key)));
    }
}

class Wpscx_Opendyslexic {

	function __construct() {}

	function profile_dyslexic( $user ) {
            global $current_user;
            $user_id = $current_user->ID;
		?>
			<table class="form-table" role="presentation">
					<tr>
							<td id="wpsc-opendyslexictable"><label><?php _e( 'Opendyslexic font', 'opendyslexic' ); ?></label></td>
							<td><p><?php _e( 'You can use the OpenDyslexic font on the website or on both the website and the admin. The OpenDyslexic font is designed to help people with dyslexia with their reading. ', 'opendyslexic' ); ?></p></td>
					</tr>
			<tr>
			<td></td>
			<td>
	 <select name="wpsc_usedyslexic" id="wpsc_usedyslexic" >
							<option value="no" <?php selected( 'no', get_user_meta( $user_id, 'wpsc_usedyslexic', true ) ); ?>><?php _e( 'Do Not use the OpenDyslexic Font', 'opendyslexic' ); ?></option>
							<option value="yes_adminonly" <?php selected( 'yes_adminonly', get_user_meta( $user_id, 'wpsc_usedyslexic', true ) ); ?>><?php _e( 'Use only on the admin area (back-end)', 'opendyslexic' ); ?></option>
							<option value="yes_websiteonly" <?php selected( 'yes_websiteonly', get_user_meta( $user_id, 'wpsc_usedyslexic', true ) ); ?>><?php _e( 'Use only on the website (front-end)', 'opendyslexic' ); ?></option>
							<option value="yes_everywhere" <?php selected( 'yes_everywhere', get_user_meta( $user_id, 'wpsc_usedyslexic', true ) ); ?>><?php _e( 'Use both on the website and Admin area', 'opendyslexic' ); ?></option>
						</select>
		</td>
			</tr>
			</table>
		<?php
	}

	function update_dyslexic( $user_id ) {
		if ( current_user_can( 'edit_user', $user_id ) && isset( $_POST['wpsc_usedyslexic'] ) ) {
			update_usermeta( $user_id, 'wpsc_usedyslexic', sanitize_text_field( $_POST['wpsc_usedyslexic'] ) );
		}
	}



	function dyslexic_css() {
		$user_ID          = get_current_user_id();
		$use_opendyslexic = get_user_meta( $user_ID, 'wpsc_usedyslexic', true );
		?>
			<style> @font-face { font-family: open-dyslexic; src: url('<?php echo plugin_dir_url( __FILE__ ); ?>OpenDyslexic-Regular.ttf'); } </style>
		<?php
		if ( 'yes_everywhere' === $use_opendyslexic || 'yes_websiteonly' === $use_opendyslexic ) {
			?>
			<style type="text/css">
			*:not(.ab-icon) { font-family: open-dyslexic, sans-serif !important }
			</style>
			<?php
		}
	}

	function dyslexic_css_admin() {
		$user_ID          = get_current_user_id();
		$use_opendyslexic = get_user_meta( $user_ID, 'wpsc_usedyslexic', true );
		?>
			<style> @font-face { font-family: open-dyslexic; src: url('<?php echo plugin_dir_url( __FILE__ ); ?>OpenDyslexic-Regular.ttf'); } </style>
		<?php
		if ( 'yes_everywhere' === $use_opendyslexic || 'yes_adminonly' === $use_opendyslexic ) {
			?>
			<style type="text/css">
			*:not(.ab-icon) { font-family: open-dyslexic, sans-serif !important }
			</style>
			<?php
		}
	}
}

class Wpscx_Results_Utils {
    
	function ignore_word( $ids ) {
		global $wpdb;
		global $wpscx_ent_included;
		$word_list         = array();
		$table_name        = $wpdb->prefix . 'spellcheck_words';
		$dict_table        = $wpdb->prefix . 'spellcheck_dictionary';
		$show_error_ignore = false;
		$show_error_dict   = false;
		$word_list[0]      = '';
		$added             = '';
		$dict_msg          = '';
		$ignore_msg        = '';
		foreach ( $ids as $id ) {
			$words       = $wpdb->get_results( $wpdb->prepare( 'SELECT word FROM ' . $table_name . ' WHERE id=%d;', sanitize_text_field( $id ) ) );
			if (isset($words[0])) $word        = $words[0]->word;
			$ignore_word = str_replace( "'", "\'", $word );
			$ignore_word = str_replace( "'", "\'", $ignore_word );
			$check_word  = $wpdb->get_results( 'SELECT * FROM ' . $table_name . ' WHERE word="' . $ignore_word . '" AND ignore_word = true' );
			$check_dict  = $wpdb->get_results( 'SELECT * FROM ' . $dict_table . ' WHERE word="' . $word . '"' );
			if ( sizeof( (array) $check_word ) <= 0 && sizeof( (array) $check_dict ) <= 0 ) {
				$wpdb->update( $table_name, array( 'ignore_word' => true ), array( 'id' => $id ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE id != %d AND word=%s", array( $id, addslashes( $word ) ) ) );
				$added .= stripslashes( $word ) . ', ';

			} else {
				if ( sizeof( (array) $check_dict ) <= 0 ) {
					$ignore_msg       .= stripslashes( $word ) . ', ';
					$show_error_ignore = true;
				} else {
					$dict_msg       .= stripslashes( $word ) . ', ';
					$show_error_dict = true;
				}
			}
			if ( $wpscx_ent_included ) {
				wpscx_print_changelog_dict( 'ignore list', $word );
			}
		}
		if ( $show_error_ignore ) {
			$ignore_msg   = trim( $dict_msg, ', ' );
			$word_list[1] = WPSCX_IGNORE_STRING . $ignore_msg;
		}
		if ( $show_error_dict ) {
			$dict_msg     = trim( $dict_msg, ', ' );
			$word_list[2] = WPSCX_DICT_STRING . $dict_msg;
		}
		$added = trim( $added, ', ' );
		if ( strpos( $added, ', ' ) !== false ) {
			$word_list[0] = 'The following words have been added to ignore list: ' . $added;
		} else {
			$word_list[0] = 'The following word has been added to ignore list: ' . $added;
		}
		return $word_list;
	}

	function ignore_word_empty( $ids ) {
		global $wpdb;
		global $wpscx_ent_included;
		$word_list         = array();
		$table_name        = $wpdb->prefix . 'spellcheck_empty';
		$dict_table        = $wpdb->prefix . 'spellcheck_dictionary';
		$show_error_ignore = false;
		$show_error_dict   = false;
		$word_list[0]      = '';
		foreach ( $ids as $id ) {
			$words       = $wpdb->get_results( $wpdb->prepare( 'SELECT word FROM ' . $table_name . ' WHERE id=%d;', sanitize_text_field( $id ) ) );
			$word        = $words[0]->word;
			$ignore_word = str_replace( "'", "\'", $word );
			$ignore_word = str_replace( "'", "\'", $ignore_word );
                        
                        $wpdb->update( $table_name, array( 'ignore_word' => true ), array( 'id' => $id ) );

                        //$wpdb->delete( $table_name, array( 'id' => $id ) );
			if ( $wpscx_ent_included ) {
				wpscx_print_changelog_dict( 'ignore list', $word );
			}
		}
		if ( $show_error_ignore ) {
			$word_list[1] = trim( $word_list[1], ', ' );
			$word_list[1] = WPSCX_IGNORE_STRING . $word_list[1];
		}
		if ( $show_error_dict ) {
			$word_list[2] = trim( $word_list[2], ', ' );
			$word_list[2] = WPSCX_DICT_STRING . $word_list[2];
		}
		$word_list[0] = trim( $word_list[0], ', ' );

                $word_list[0] = 'The ignore list for SEO Empty Fields has been updated';
		return $word_list;
	}

	function add_to_dictionary( $ids ) {
		global $wpdb;
		global $wpscx_ent_included;
		$table_name        = $wpdb->prefix . 'spellcheck_words';
		$dictionary_table  = $wpdb->prefix . 'spellcheck_dictionary';
		$word_list         = array();
                $word_list[0] = '';
                $word_list[1] = '';
                $word_list[2] = '';
                $check_dict = 0;
		$show_error_ignore = false;
		$show_error_dict   = false;
		foreach ( $ids as $id ) {
                        $words        = $wpdb->get_results( $wpdb->prepare( 'SELECT word FROM ' . $table_name . ' WHERE id=%d;', sanitize_text_field( $id ) ) );
                        if (!isset($words[0]->word)) continue;
			$word         = $words[0]->word;
			$word         = str_replace( '%28', '(', $word );
			$ignore_word  = str_replace( "'", "\'", $word );
			$ignore_word  = str_replace( "'", "\'", $ignore_word );
			$check        = $wpdb->get_results( 'SELECT * FROM ' . $dictionary_table . ' WHERE word="' . $word . '"' );
			$ignore_check = $wpdb->get_results( 'SELECT * FROM ' . $table_name . ' WHERE word="' . $ignore_word . '" AND ignore_word = true' );

			if ( sizeof( (array) $check ) < 1 && sizeof( (array) $ignore_check ) < 1 ) {
				$wpdb->insert( $dictionary_table, array( 'word' => stripslashes( $word ) ) );

				$wpdb->delete( $table_name, array( 'word' => $word ) );
				$word_list[0] = $word_list[0] . stripslashes( $word ) . ', ';

			} else {
				if ( sizeof( (array) $check_dict ) <= 0 ) {
					$word_list[1]     .= stripslashes( $word ) . ', ';
					$show_error_ignore = true;
				} else {
					$word_list[2]   .= stripslashes( $word ) . ', ';
					$show_error_dict = true;
				}
			}

			if ( $wpscx_ent_included ) {
				wpscx_print_changelog_dict( 'dictionary', $word );
			}
		}
		if ( $show_error_ignore ) {
			$word_list[1] = trim( $word_list[1], ', ' );
			$word_list[1] = WPSCX_IGNORE_STRING . $word_list[1];
		}
		if ( $show_error_dict ) {
			$word_list[2] = trim( $word_list[2], ', ' );
			$word_list[2] = WPSCX_DICT_STRING . $word_list[2];
		}
		$word_list[0] = trim( $word_list[0], ', ' );
		if ( strpos( $word_list[0], ', ' ) !== false ) {
			$word_list[0] = 'The following words have been added to dictionary: ' . $word_list[0];
		} else {
			$word_list[0] = 'The following word has been added to dictionary: ' . $word_list[0];
		}
		return $word_list;
	}
        
                
        function update_prepare_text($old_word, $new_word) {
            $old_word  = sanitize_text_field( $old_word );
            $new_word  = sanitize_text_field( $new_word );
            $old_word = str_replace( '&amp;', '&', $old_word );
            $new_word = str_replace( '&amp;', '&', $new_word );
            $old_word = str_replace( '%amp;', '&', $old_word );
            $new_word = str_replace( '%amp;', '&', $new_word );
            $old_word = str_replace( '%28', '(', $old_word );
            $new_word = str_replace( '%28', '(', $new_word );
            $old_word = str_replace( '%27', "'", $old_word );
            $new_word = str_replace( '%27', "'", $new_word );
            $old_word = str_replace( '%pls;', '+', $old_word );
            $new_word = str_replace( '%pls;', '+', $new_word );
            $old_word = str_replace( '%hash;', '#', $old_word );
            $new_word = str_replace( '%hash;', '#', $new_word );
            $old_word = stripslashes( stripslashes( $old_word ) );
            $new_word = stripslashes( stripslashes( $new_word ) );

            $old_word = trim( $old_word );
            //$old_word = htmlentities($old_word);
            //$new_word = htmlentities($new_word);
            
            return array( $old_word, $new_word );
        }

	/*
	*
	* When editing words, individual words get updated first then ones checked off to apply to entire site
	* If duplicates are detected in either list, the one which appears first in the results list takes priority
	* If duplicates are between each list, individual updates take priority over entire site changes
	*
	*/
	function update_word_admin( $old_words, $new_words, $page_names, $page_types, $old_word_ids, $mass_edit ) {
		//print_r($new_words);
		global $wpdb;
		global $wpscx_ent_included;
		$table_name     = $wpdb->prefix . 'posts';
		$words_table    = $wpdb->prefix . 'spellcheck_words';
		$terms_table    = $wpdb->prefix . 'terms';
		$meta_table     = $wpdb->prefix . 'postmeta';
		$taxonomy_table = $wpdb->prefix . 'term_taxonomy';
		$user_table     = $wpdb->prefix . 'usermeta';
		$dict_table     = $wpdb->prefix . 'spellcheck_dictionary';
		$word_list      = '';

		$mass_edit_list     = array();
		$mass_edit_list_new = array();
		$mass_edit_words    = array();
		$wpscx_ignore_list  = array();

		$my_dictionary = $wpdb->get_results( "SELECT * FROM $dict_table;" );

		foreach ( $my_dictionary as $dict_word ) {
			array_push( $wpscx_ignore_list, $dict_word->word );
		}

		$my_ignore = $wpdb->get_results( "SELECT * FROM $words_table WHERE ignore_word = 1;" );

		foreach ( $my_ignore as $dict_word ) {
			array_push( $wpscx_ignore_list, $dict_word->word );
		}

		for ( $x = 0; $x < sizeof( (array) $old_words ); $x++ ) {
                        $words = $this->update_prepare_text( $old_words[ $x ], $new_words[ $x ] );
                        $old_words[ $x ] = $words[0];
                        $new_words[ $x ] = $words[1];
			$page_names[ $x ] = sanitize_text_field( $page_names[ $x ] );

			if ( in_array( $old_words[ $x ], $wpscx_ignore_list, true ) ) {
				continue;
			}

			$edit_flag = false;
			if ( is_array( $mass_edit ) ) {
				foreach ( $mass_edit as $edit_id ) {
					if ( $edit_id === $old_word_ids[ $x ] && ! in_array( $old_words[ $x ], $mass_edit_words ) ) {
						array_push(
							$mass_edit_list,
							array(
								'old_word' => $old_words[ $x ],
								'new_word' => $new_words[ $x ],
							)
						);
						array_push( $mass_edit_words, $old_words[ $x ] );
						$edit_flag = true;
					}
				}
			}
			if ( $edit_flag ) {
				continue;
			}
			$word_id         = $old_word_ids[ $x ];
			$new_words[ $x ] = str_replace( '$', '\$', $new_words[ $x ] );

			if ( 'Post Content' === $page_types[ $x ] || 'Page Content' === $page_types[ $x ] || 'Media Description' === $page_types[ $x ] || 'WooCommerce Product' === $page_types[ $x ] || 'WP eCommerce Product' === $page_types[ $x ] ) {
                            $new_words[ $x ] = str_replace( '\$', '$', $new_words[ $x ] );

				$page_result     = $wpdb->get_results( $wpdb->prepare( 'SELECT post_content, post_title FROM ' . $table_name . ' WHERE ID="%s"', $page_names[ $x ] ) );
				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $page_result[0]->post_content ) );
				$old_name        = $page_result[0]->post_title;
                                
                                /*$loc        = dirname( __FILE__ ) . '/debug.log';
                                $debug_file = fopen( $loc, 'a' );
                                $debug_var  = fwrite( $debug_file, "New Function Result\r\n" . print_r($words, true) . "\r\n" );
                                $debug_var  = fwrite( $debug_file, "Old Word: " . $old_words[ $x ] . "\r\n" );
                                $debug_var  = fwrite( $debug_file, "Regex Pattern: " . wpscx_regex_pattern( $old_words[ $x ] ) . "\r\n" );
                                $debug_var  = fwrite( $debug_file, "Original Content\r\n" . html_entity_decode( $page_result[0]->post_content ) . "\r\n" );
                                $debug_var  = fwrite( $debug_file, "Updated Content\r\n" . $updated_content . "\r\n" );
                                fclose( $debug_file );*/

				$meta_result = $wpdb->get_results( $wpdb->prepare( 'SELECT meta_value FROM ' . $meta_table . ' WHERE post_id="%s" AND meta_key="_elementor_data"', $page_names[ $x ] ) );

				//Update Elementor Postmeta data
				if ( sizeof( $meta_result ) >= 1 ) {
                                    $old_word      = stripslashes ( trim( json_encode( $old_words[ $x ] ), '"' ) );
                                    $new_word      = stripslashes ( trim( json_encode( $new_words[ $x ] ), '"' ) );
                                    $rows_affected = $wpdb->query( str_replace( '\$', '$', ( $wpdb->prepare( "UPDATE {$wpdb->postmeta} " . "SET `meta_value` = REPLACE(`meta_value`, '%s', '%s') " . "WHERE `meta_key` = '_elementor_data' AND `post_id` = '%d' ;", $old_word, $new_word, $page_names[ $x ] ) ) ) );
				}

				//Update SeedProd Page Builder
				//$rows_affected = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} " . "SET `post_content_filtered` = REPLACE(`post_content_filtered`, '%s', '%s') " . "WHERE `ID` = '%s'", $old_words[ $x ], $new_words[ $x ], $page_names[ $x ] ) );

					//Update Visual composer
					$meta_result  = $wpdb->get_results( $wpdb->prepare( 'SELECT meta_value FROM ' . $meta_table . ' WHERE post_id="%d" AND meta_key="vcv-pageContent"', $page_names[ $x ] ) );
                                        if ( isset($meta_result[0]->meta_value) ) {
                                            $updated_meta = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], $meta_result[0]->meta_value );
                                            update_post_meta( $page_names[ $x ], 'vcv-pageContent', sanitize_text_field( $updated_meta ) ); 
                                        }

					//Update Page Builder by SiteOrigin Postmeta data
					/*$oldWord = $old_words[$x];
					$new_word = $new_words[$x];
					$rows_affected = $wpdb->query(
					"UPDATE {$wpdb->postmeta} " .
					"SET `meta_value` = REPLACE(`meta_value`, '" . $old_word . "', '" . $new_word . "') " .
					"WHERE `meta_key` = 'panels_data' AND `post_id` = '" . $page_names[$x] . "'" );*/
					$meta_result  = $wpdb->get_results( $wpdb->prepare( 'SELECT meta_value FROM ' . $meta_table . ' WHERE post_id="%d" AND meta_key="panels_data"', $page_names[ $x ] ) );
                                        if (isset($meta_result[0]->meta_value)) {
					$updated_meta = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), sanitize_text_field( $new_words[ $x ] ), maybe_unserialize( $meta_result[0]->meta_value ) );
					$updated_meta = preg_replace_callback(
						'!s:\d+:"(.*?)";!s',
						function( $m ) {
							return 's:' . strlen( $m[1] ) . ':"' . $m[1] . '";';
						},
						$updated_meta
					);
                                        }
				if ( isset( $meta_result[0]->meta_value['widgets'] ) ) {
					$test_data = maybe_unserialize( $meta_result[0]->meta_value )['widgets'];
					for ( $z = 0; $z < sizeof( $test_data ); $z++ ) {
						foreach ( $test_data[ $z ] as $key => $val ) {
							$test_data[0][ $key ] = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], $val );
						}
					}
					update_post_meta( $page_names[ $x ], 'panels_data', serialize( $test_data ) );
				}

					//Update Beaver Builder
					//$meta_result = $wpdb->get_results('SELECT meta_value FROM ' . $meta_table . ' WHERE post_id="' . $page_names[$x] . '" AND meta_key="_fl_builder_data"');
					//$updated_meta = preg_replace(wpscx_regex_pattern($old_words[$x]), $new_words[$x], $meta_result[0]->meta_value);
					//$updated_meta = preg_replace_callback('!s:\d+:"(.*?)";!s', function($m) { return "s:" . strlen($m[1]) . ':"'.$m[1].'";'; }, $updated_meta);
					//update_post_meta($page_names[$x], '_fl_builder_data', $updated_meta);

				$wpdb->update( $table_name, array( 'post_content' => $updated_content ), array( 'ID' => $page_names[ $x ] ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Page Custom Field' === $page_types[ $x ] || 'Post Custom Field' === $page_types[ $x ] ) {
				$meta_result  = $wpdb->get_results( $wpdb->prepare( 'SELECT meta_value FROM ' . $meta_table . ' WHERE meta_id="%s"', $page_names[ $x ] ) );
				$updated_meta = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], $meta_result[0]->meta_value );

				$wpdb->update( $meta_table, array( 'meta_value' => $updated_meta ), array( 'meta_id' => $page_names[ $x ] ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Contact Form 7 Form' === $page_types[ $x ] || 'Contact Form 7 Email Notification' === $page_types[ $x ] || 'Contact Form 7 Auto Response' === $page_types[ $x ] ) {

				$page_result = $wpdb->get_results( $wpdb->prepare( 'SELECT post_content, post_title FROM ' . $table_name . ' WHERE ID="%s"', $page_names[ $x ] ) );

				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $page_result[0]->post_content ) );

				$old_name = $page_result[0]->post_title;
				$wpdb->update( $table_name, array( 'post_content' => $updated_content ), array( 'ID' => $page_names[ $x ] ) );
				if ( 'Contact Form 7 Form' === $page_types[ $x ] ) {
					$meta_result  = $wpdb->get_results( $wpdb->prepare( 'SELECT meta_value FROM ' . $meta_table . ' WHERE post_id="%s" AND meta_key="_form"', $page_names[ $x ] ) );
					$updated_meta = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], $meta_result[0]->meta_value );
					//$wpdb->update($meta_table, array('meta_value' => $updated_meta), array('post_id' => $page_names[$x], 'meta_key' => '_form'));
					$updated_meta = sanitize_textarea_field( $updated_meta );
					update_post_meta( $page_names[ $x ], '_form', $updated_meta );
				} elseif ( 'Contact Form 7 Email Notification' === $page_types[ $x ] ) {
					$meta_result  = $wpdb->get_results( $wpdb->prepare( 'SELECT meta_value FROM ' . $meta_table . ' WHERE post_id="%s" AND meta_key="_mail"', $page_names[ $x ] ) );
					$updated_meta = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), sanitize_textarea_field( $new_words[ $x ] ), maybe_unserialize( $meta_result[0]->meta_value ) );
					//$wpdb->update($meta_table, array('meta_value' => $updated_meta), array('post_id' => $page_names[$x], 'meta_key' => '_mail'));

					update_post_meta( $page_names[ $x ], '_mail', $updated_meta );
                                        
                                        $meta_result  = $wpdb->get_results( $wpdb->prepare( 'SELECT meta_value FROM ' . $meta_table . ' WHERE post_id="%s" AND meta_key="_mail_2"', $page_names[ $x ] ) );
					$updated_meta = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), sanitize_textarea_field( $new_words[ $x ] ), maybe_unserialize( $meta_result[0]->meta_value ) );
					//$wpdb->update($meta_table, array('meta_value' => $updated_meta), array('post_id' => $page_names[$x], 'meta_key' => '_mail_2'));
					$updated_meta = preg_replace_callback(
						'!s:\d+:"(.*?)";!s',
						function( $m ) {
							return 's:' . strlen( $m[1] ) . ':"' . $m[1] . '";';
						},
						$updated_meta
					);

					update_post_meta( $page_names[ $x ], '_mail_2', $updated_meta );
				} elseif ( 'Contact Form 7 Auto Response' === $page_types[ $x ] ) {
					$meta_result  = $wpdb->get_results( $wpdb->prepare( 'SELECT meta_value FROM ' . $meta_table . ' WHERE post_id="%s" AND meta_key="_mail_2"', $page_names[ $x ] ) );
					$updated_meta = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), sanitize_textarea_field( $new_words[ $x ] ), maybe_unserialize( $meta_result[0]->meta_value ) );
					//$wpdb->update($meta_table, array('meta_value' => $updated_meta), array('post_id' => $page_names[$x], 'meta_key' => '_mail_2'));
					$updated_meta = preg_replace_callback(
						'!s:\d+:"(.*?)";!s',
						function( $m ) {
							return 's:' . strlen( $m[1] ) . ':"' . $m[1] . '";';
						},
						$updated_meta
					);

					update_post_meta( $page_names[ $x ], '_mail_2', $updated_meta );
				}
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'WooCommerce Product Short Description' === $page_types[ $x ] || 'WP eCommerce Product Excerpt' === $page_types[ $x ] ) {

				$page_result = $wpdb->get_results( $wpdb->prepare( 'SELECT post_content, post_title, post_excerpt FROM ' . $table_name . ' WHERE ID="%s"' ), $page_names[ $x ] );

				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $page_result[0]->post_excerpt ) );

				$old_name = $page_result[0]->post_title;
				$wpdb->update( $table_name, array( 'post_excerpt' => $updated_content ), array( 'ID' => $page_names[ $x ] ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Menu Item' === $page_types[ $x ] || 'Post Title' === $page_types[ $x ] || 'Page Title' === $page_types[ $x ] || 'Slider Title' === $page_types[ $x ] || 'Media Title' === $page_types[ $x ] || 'WP eCommerce Product Name' === $page_types[ $x ] || 'WooCommerce Title' === $page_types[ $x ] ) {

				$menu_result     = $wpdb->get_results( $wpdb->prepare( 'SELECT post_title FROM ' . $table_name . ' WHERE ID="%s"', $page_names[ $x ] ) );
				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $menu_result[0]->post_title ) );

				$old_name = $menu_result[0]->post_title;
				$wpdb->update( $table_name, array( 'post_title' => $updated_content ), array( 'ID' => $page_names[ $x ] ) );
				$wpdb->update( $words_table, array( 'page_name' => $updated_content ), array( 'page_name' => $old_name ) ); //Update the title of the page/post/menu in the spellcheck database
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Author Nickname' === $page_types[ $x ] ) {
				$author_result   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $user_table WHERE user_id=%d AND meta_key='nickname'", $page_names[ $x ] ) );
				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $author_result[0]->meta_value ) );

				$wpdb->update(
					$user_table,
					array( 'meta_value' => $updated_content ),
					array(
						'user_id'  => $page_result[0]->post_author,
						'meta_key' => 'nickname',
					)
				);
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Author First Name' === $page_types[ $x ] ) {
				$author_result   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $user_table WHERE user_id=%d AND meta_key='first_name'", $page_names[ $x ] ) );
				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $author_result[0]->meta_value ) );

				$wpdb->update(
					$user_table,
					array( 'meta_value' => $updated_content ),
					array(
						'user_id'  => $page_names[ $x ],
						'meta_key' => 'first_name',
					)
				);
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Author Last Name' === $page_types[ $x ] ) {
				$author_result   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $user_table WHERE user_id=%d AND meta_key='last_name'", $page_names[ $x ] ) );
				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $author_result[0]->meta_value ) );

				$wpdb->update(
					$user_table,
					array( 'meta_value' => $updated_content ),
					array(
						'user_id'  => $page_names[ $x ],
						'meta_key' => 'last_name',
					)
				);
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Author Biography' === $page_types[ $x ] ) {
				$author_result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $user_table WHERE user_id=%d AND meta_key='description'", $page_names[ $x ] ) );

				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $author_result[0]->meta_value ) );

				$wpdb->update(
					$user_table,
					array( 'meta_value' => $updated_content ),
					array(
						'user_id'  => $page_names[ $x ],
						'meta_key' => 'description',
					)
				);
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Author SEO Title' === $page_types[ $x ] ) {
				$author_result   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $user_table WHERE user_id=%d AND meta_key='wpseo_title'", $page_names[ $x ] ) );
				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $author_result[0]->meta_value ) );

				$wpdb->update(
					$user_table,
					array( 'meta_value' => $updated_content ),
					array(
						'user_id'  => $page_names[ $x ],
						'meta_key' => 'wpseo_title',
					)
				);
				$wpdb->delete(
					$words_table,
					array(
						'word' => $old_words[ $x ],
						'id'   => $old_word_ids[ $x ],
					)
				);
			} elseif ( 'Author SEO Description' === $page_types[ $x ] ) {
				$author_result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $user_table WHERE user_id=%d AND meta_key='wpseo_metadesc'", $page_names[ $x ] ) );

				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $author_result[0]->meta_value ) );

				$wpdb->update(
					$user_table,
					array( 'meta_value' => $updated_content ),
					array(
						'user_id'  => $page_names[ $x ],
						'meta_key' => 'wpseo_metadesc',
					)
				);
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Site Name' === $page_types[ $x ] ) {
				$opt_table = $wpdb->prefix . 'options';

				$site_result     = $wpdb->get_results( "SELECT * FROM $opt_table WHERE option_name='blogname'" );
				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $site_result[0]->option_value ) );

				$wpdb->update( $opt_table, array( 'option_value' => $updated_content ), array( 'option_name' => 'blogname' ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Site Tagline' === $page_types[ $x ] ) {
				$opt_table = $wpdb->prefix . 'options';

				$site_result     = $wpdb->get_results( "SELECT * FROM $opt_table WHERE option_name='blogdescription'" );
				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $site_result[0]->option_value ) );

				$wpdb->update( $opt_table, array( 'option_value' => $updated_content ), array( 'option_name' => 'blogdescription' ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Slider Caption' === $page_types[ $x ] ) {

				$menu_result     = $wpdb->get_results( $wpdb->prepare( 'SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="%s"', $page_names[ $x ] ) );
				$caption         = get_post_meta( $menu_result[0]->ID, 'my_slider_caption', true );
				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $caption ) );

				$updated_content = sanitize_text_field( $updated_content );
				update_post_meta( $menu_result[0]->ID, 'my_slider_caption', $updated_content );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Smart Slider Caption' === $page_types[ $x ] ) {

				$slider_table    = $wpdb->prefix . 'wp_nextend2_smartsliders_slides';
				$menu_result     = $wpdb->get_results( $wpdb->prepare( 'SELECT slide FROM ' . $slider_table . ' WHERE id="%s"', $page_names[ $x ] ) );
				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $menu_result[0]->slide ) );

				$wpdb->update( $slider_table, array( 'description' => $updated_content ), array( 'id' => $page_names[ $x ] ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Smart Slider Title' === $page_types[ $x ] ) {

				$slider_table    = $wpdb->prefix . 'wp_nextend2_smartsliders_slides';
				$menu_result     = $wpdb->get_results( $wpdb->prepare( 'SELECT title FROM ' . $slider_table . ' WHERE id="%s"', $page_names[ $x ] ) );
				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $menu_result[0]->title ) );

				$wpdb->update( $slider_table, array( 'title' => $updated_content ), array( 'id' => $page_names[ $x ] ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Media Alternate Text' === $page_types[ $x ] ) {

				$menu_result     = $wpdb->get_results( $wpdb->prepare( 'SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="%s"', $page_names[ $x ] ) );
				$caption         = get_post_meta( $menu_result[0]->ID, '_wp_attachment_image_alt', true );
				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $caption ) );

				$updated_content = sanitize_text_field( $updated_content );
				update_post_meta( $menu_result[0]->ID, '_wp_attachment_image_alt', $updated_content );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Media Caption' === $page_types[ $x ] ) {

				$page_result = $wpdb->get_results( $wpdb->prepare( 'SELECT post_excerpt, post_title FROM ' . $table_name . ' WHERE ID="%s"', $page_names[ $x ] ) );

				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $page_result[0]->post_excerpt ) );

				$old_name = $page_result[0]->post_title;
				$wpdb->update( $table_name, array( 'post_excerpt' => $updated_content ), array( 'ID' => $page_names[ $x ] ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Tag Title' === $page_types[ $x ] || 'Category Title' === $page_types[ $x ] || 'WooCommerce Category Title' === $page_types[ $x ] || 'WooCommerce Tag Title' === $page_types[ $x ] ) {

				$tag_result = $wpdb->get_results( $wpdb->prepare( 'SELECT name FROM ' . $terms_table . ' WHERE term_id=%d', $page_names[ $x ] ) );

				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $tag_result[0]->name ) );

				$wpdb->update( $terms_table, array( 'name' => $updated_content ), array( 'name' => $tag_result[0]->name ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Tag Description' === $page_types[ $x ] || 'WooCommerce Tag Description' === $page_types[ $x ] ) {

				$tag_result = $wpdb->get_results( $wpdb->prepare( 'SELECT description FROM ' . $taxonomy_table . ' WHERE term_id=%d', $page_names[ $x ] ) );

				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $tag_result[0]->description ) );

				$wpdb->update( $taxonomy_table, array( 'description' => $updated_content ), array( 'description' => $tag_result[0]->description ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Category Description' === $page_types[ $x ] || 'WooCommerce Category Description' === $page_types[ $x ] ) {

				$tag_result = $wpdb->get_results( $wpdb->prepare( 'SELECT description FROM ' . $taxonomy_table . ' WHERE term_id=%d', $page_names[ $x ] ) );

				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $tag_result[0]->description ) );

				$wpdb->update( $taxonomy_table, array( 'description' => $updated_content ), array( 'description' => $tag_result[0]->description ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Post Custom Field' === $page_types[ $x ] ) {

				$page_result = $wpdb->get_results( $wpdb->prepare( 'SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="%s"', $page_names[ $x ] ) );
				$desc_result = $wpdb->get_results( $wpdb->prepare( 'SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=%d AND meta_value LIKE %s', $page_result[0]->ID, '%' . $wpdb->esc_like( $old_words[ $x ] ) . '%' ) );

				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $desc_result[0]->meta_value ) );

				$old_name = $page_result[0]->post_title;
				$wpdb->update( $meta_table, array( 'meta_value' => $updated_content ), array( 'post_id' => $page_result[0]->ID ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Yoast SEO Description' === $page_types[ $x ] ) {

				$page_result = $wpdb->get_results( $wpdb->prepare( 'SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="%s"', $page_names[ $x ] ) );
				$desc_result = $wpdb->get_results( $wpdb->prepare( 'SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=%d AND meta_key="_yoast_wpseo_metadesc"', $page_result[0]->ID ) );

				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $desc_result[0]->meta_value ) );

				$old_name = $page_result[0]->post_title;
				$wpdb->update(
					$meta_table,
					array( 'meta_value' => $updated_content ),
					array(
						'post_id'  => $page_result[0]->ID,
						'meta_key' => '_yoast_wpseo_metadesc',
					)
				);
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'All in One SEO Description' === $page_types[ $x ] ) {

				$page_result = $wpdb->get_results( $wpdb->prepare( 'SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="%s"', $page_names[ $x ] ) );
				$desc_result = $wpdb->get_results( $wpdb->prepare( 'SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=%d AND meta_key="_aioseo_description"', $page_result[0]->ID ) );

				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $desc_result[0]->meta_value ) );

				$old_name = $page_result[0]->post_title;
				$wpdb->update(
					$meta_table,
					array( 'meta_value' => $updated_content ),
					array(
						'post_id'  => $page_result[0]->ID,
						'meta_key' => '_aioseo_description',
					)
				);
				$wpdb->update( $wpdb->prefix . 'aioseo_posts', array( 'description' => $updated_content ), array( 'post_id' => $page_result[0]->ID ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Ultimate SEO Description' === $page_types[ $x ] ) {

				$page_result = $wpdb->get_results( $wpdb->prepare( 'SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="%s"', $page_names[ $x ] ) );
				$desc_result = $wpdb->get_results( $wpdb->prepare( 'SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=%d AND meta_key="_su_description"', $page_result[0]->ID ) );

				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $desc_result[0]->meta_value ) );

				$old_name = $page_result[0]->post_title;
				$wpdb->update(
					$meta_table,
					array( 'meta_value' => $updated_content ),
					array(
						'post_id'  => $page_result[0]->ID,
						'meta_key' => '_su_description',
					)
				);
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Rank Math SEO Description' === $page_types[ $x ] ) {

				$page_result = $wpdb->get_results( $wpdb->prepare( 'SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="%s"', $page_names[ $x ] ) );
				$desc_result = $wpdb->get_results( $wpdb->prepare( 'SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=%d AND meta_key="rank_math_description"', $page_result[0]->ID ) );

				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $desc_result[0]->meta_value ) );

				$old_name = $page_result[0]->post_title;
				$wpdb->update(
					$meta_table,
					array( 'meta_value' => $updated_content ),
					array(
						'post_id'  => $page_result[0]->ID,
						'meta_key' => 'rank_math_description',
					)
				);
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Yoast SEO Title' == $page_types[ $x ] ) {

				$page_result = $wpdb->get_results( $wpdb->prepare( 'SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="%s"', $page_names[ $x ] ) );
				$desc_result = $wpdb->get_results( $wpdb->prepare( 'SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=%d AND meta_key="_yoast_wpseo_title"', $page_result[0]->ID ) );

				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $desc_result[0]->meta_value ) );

				$old_name = $page_result[0]->post_title;
				$wpdb->update(
					$meta_table,
					array( 'meta_value' => $updated_content ),
					array(
						'post_id'  => $page_result[0]->ID,
						'meta_key' => '_yoast_wpseo_title',
					)
				);
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'All in One SEO Title' === $page_types[ $x ] ) {
				$page_result = $wpdb->get_results( $wpdb->prepare( 'SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="%s"', $page_names[ $x ] ) );
				$desc_result = $wpdb->get_results( $wpdb->prepare( 'SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=%d AND meta_key="_aioseo_title"', $page_result[0]->ID ) );

				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $desc_result[0]->meta_value ) );

				$old_name = $page_result[0]->post_title;
				$wpdb->update(
					$meta_table,
					array( 'meta_value' => $updated_content ),
					array(
						'post_id'  => $page_result[0]->ID,
						'meta_key' => '_aioseo_title',
					)
				);
					$wpdb->update( $wpdb->prefix . 'aioseo_posts', array( 'title' => $updated_content ), array( 'post_id' => $page_result[0]->ID ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Ultimate SEO Title' === $page_types[ $x ] ) {
				$page_result = $wpdb->get_results( $wpdb->prepare( 'SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="%s"', $page_names[ $x ] ) );
				$desc_result = $wpdb->get_results( $wpdb->prepare( 'SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=%d AND meta_key="_su_title"', $page_result[0]->ID ) );

				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $desc_result[0]->meta_value ) );

				$old_name = $page_result[0]->post_title;
				$wpdb->update(
					$meta_table,
					array( 'meta_value' => $updated_content ),
					array(
						'post_id'  => $page_result[0]->ID,
						'meta_key' => '_su_title',
					)
				);
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Rank Math SEO Title' === $page_types[ $x ] ) {
				$page_result = $wpdb->get_results( $wpdb->prepare( 'SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="%s"', $page_names[ $x ] ) );
				$desc_result = $wpdb->get_results( $wpdb->prepare( 'SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=%d AND meta_key="rank_math_title"', $page_result[0]->ID ) );

				$updated_content = preg_replace( wpscx_regex_pattern( $old_words[ $x ] ), $new_words[ $x ], html_entity_decode( $desc_result[0]->meta_value ) );

				$old_name = $page_result[0]->post_title;
				$wpdb->update(
					$meta_table,
					array( 'meta_value' => $updated_content ),
					array(
						'post_id'  => $page_result[0]->ID,
						'meta_key' => 'rank_math_title',
					)
				);
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			} elseif ( 'Widget Content' === $page_types[ $x ] ) {
				$widget_instances = get_option( 'widget_text' );

				foreach ( array_keys( $widget_instances ) as $index ) {
					if ( $widget_instances[ $index ]['title'] === $page_names[ $x ] ) {
							$widget_instances[ $index ]['text'] = str_replace( $old_words[ $x ], $new_words[ $x ], html_entity_decode( $widget_instances[ $index ]['text'] ) );
					}
				}

				update_option( 'widget_text', $widget_instances );
				$wpdb->query( $wpdb->prepare( "DELETE FROM $words_table WHERE id=%d", $word_id ) );
			}

			$page_url        = get_permalink( $page_names[ $x ] );
			$page_title      = get_the_title( $page_names[ $x ] );
			$new_words[ $x ] = str_replace( '\$', '$', $new_words[ $x ] );
			$word_list      .= stripslashes( $old_words[ $x ] ) . ' to ' . $new_words[ $x ] . ', ';

			$url = wpscx_construct_url( $page_types[ $x ], $page_names[ $x ] );
			if ( $wpscx_ent_included ) {
				wpscx_print_changelog( $old_words[ $x ], $new_words[ $x ], $page_types[ $x ], $url );
			}
		}

		$return_message = '';
		if ( $wpscx_ent_included ) {
			$url       = plugins_url( '/wp-spell-check-pro/admin/changes.php' );
			$view_link = '<a target="_blank" href="' . $url . '">Click here</a> to view the changelog';
		} else {
			$view_link = '<span class="sc-message" style="color: grey; width: auto!important; font-weight: 700!important; display: inline!important; padding: 0px!important;">Click here to view the changelog</a><span class="wpsc-mouseover-button-change-2" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-change-2"><span style="display: block;text-align: center;font-size: 14px;padding: 10px 0 10px 0;background-color: #2271b1;color: white;margin-bottom: 8px;"> This is a Pro Feature</span><span style="padding: 0 10px; display: block;">To view the changelog, <a href="https://www.wpspellcheck.com/pricing/?utm_source=baseplugin&utm_campaign=upgradespellch&utm_medium=changelog&utm_content=9.21" target="_blank">Click Here</a> to upgrade to WP Spell Check Pro.</span></span>';
		}
		if ( sizeof( (array) $mass_edit_list ) > 0 && $wpscx_ent_included ) {
			$return_message  = wpsc_mass_edit( $mass_edit_list );
			$return_message .= '<br />';
		}

		$word_list = trim( $word_list, ', ' );
		//echo "Word List: |" . $word_list . "|";
		if ( strpos( $word_list, ', ' ) !== false ) {
			return $return_message . 'The following words have been updated: ' . $word_list . '<br>' . $view_link;
		} else {
			if ( '' != $word_list ) {
				return $return_message . 'The following word has been updated: ' . $word_list . '<br>' . $view_link;
			} else {
				return $return_message . $view_link;
			}
		}
	}

	function update_empty_admin( $new_words, $page_names, $page_types, $old_word_ids ) {
		global $wpdb;
		$table_name     = $wpdb->prefix . 'posts';
		$words_table    = $wpdb->prefix . 'spellcheck_empty';
		$terms_table    = $wpdb->prefix . 'terms';
		$meta_table     = $wpdb->prefix . 'postmeta';
		$taxonomy_table = $wpdb->prefix . 'term_taxonomy';
		$user_table     = $wpdb->prefix . 'usermeta';
		$word_list      = '';
		$seo_error      = false;
                
                //echo "Debug Printout<br>";
                //print_r($page_types);

		for ( $x = 0; $x < sizeof( (array) $new_words ); $x++ ) {
                    if ( empty($page_names) || empty($new_words) ) { continue; }
			$new_words[ $x ]  = str_replace( '%28', '(', $new_words[ $x ] );
			$new_words[ $x ]  = str_replace( '%27', "'", $new_words[ $x ] );
			$new_words[ $x ]  = stripslashes( $new_words[ $x ] );
			$new_words[ $x ]  = sanitize_text_field( $new_words[ $x ] );
			$page_names[ $x ] = sanitize_text_field( $page_names[ $x ] );

			if ( 'Media Description' === $page_types[ $x ] ) {

				$page_result = $wpdb->get_results( $wpdb->prepare( 'SELECT post_content FROM ' . $table_name . ' WHERE ID="%s"', $page_names[ $x ] ) );

				$updated_content = $new_words[ $x ];

				$wpdb->update( $table_name, array( 'post_content' => $updated_content ), array( 'ID' => $page_names[ $x ] ) );
				$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
			} elseif ( 'WooCommerce Product Excerpt' === $page_types[ $x ] || 'WP eCommerce Product Excerpt' === $page_types[ $x ] ) {

				$page_result = $wpdb->get_results( $wpdb->prepare( 'SELECT post_content, post_title, post_excerpt FROM ' . $table_name . ' WHERE ID="%s"', $page_names[ $x ] ) );

				$updated_content = $new_words[ $x ];

				$old_name = $page_result[0]->post_title;
				$wpdb->update( $table_name, array( 'post_excerpt' => $updated_content ), array( 'ID' => $page_names[ $x ] ) );
				$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
			} elseif ( 'Menu Item' === $page_types[ $x ] || 'Post Title' === $page_types[ $x ] || 'Page Title' === $page_types[ $x ] || 'Slider Title' === $page_types[ $x ] || 'WP eCommerce Product Name' === $page_types[ $x ] || 'WooCommerce Product Name' === $page_types[ $x ] ) {

				$menu_result     = $wpdb->get_results( $wpdb->prepare( 'SELECT post_title FROM ' . $table_name . ' WHERE ID="%s"', $page_names[ $x ] ) );
				$updated_content = $new_words[ $x ];

				$wpdb->update( $table_name, array( 'post_title' => $updated_content ), array( 'ID' => $page_names[ $x ] ) );
				$wpdb->update( $words_table, array( 'page_name' => $updated_content ), array( 'id' => $old_word_ids[ $x ] ) ); //Update the title of the page/post/menu in the spellcheck database
				$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
			} elseif ( 'Author Nickname' === $page_types[ $x ] ) {
				$author_result   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $user_table WHERE user_id=%d AND meta_key='nickname'", $page_names[ $x ] ) );
				$updated_content = $new_words[ $x ];

				$wpdb->update(
					$user_table,
					array( 'meta_value' => $updated_content ),
					array(
						'user_id'  => $page_names[ $x ],
						'meta_key' => 'nickname',
					)
				);
				$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
			} elseif ( 'Author First Name' === $page_types[ $x ] ) {
                                $author_result   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $user_table WHERE user_id=%d AND meta_key='first_name'", $page_names[ $x ] ) );
				$updated_content = $new_words[ $x ];

				$wpdb->update(
					$user_table,
					array( 'meta_value' => $updated_content ),
					array(
						'user_id'  => $page_names[ $x ],
						'meta_key' => 'first_name',
					)
				);
				$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
			} elseif ( 'Author Last Name' === $page_types[ $x ] ) {
                                $author_result   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $user_table WHERE user_id=%d AND meta_key='last_name'", $page_names[ $x ] ) );
				$updated_content = $new_words[ $x ];

				$wpdb->update(
					$user_table,
					array( 'meta_value' => $updated_content ),
					array(
						'user_id'  => $page_names[ $x ],
						'meta_key' => 'last_name',
					)
				);
				$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
			} elseif ( 'Author Biography' === $page_types[ $x ] ) {
                                $author_result   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $user_table WHERE user_id=%d AND meta_key='description'", $page_names[ $x ] ) );
				$updated_content = $new_words[ $x ];

				$wpdb->update(
					$user_table,
					array( 'meta_value' => $updated_content ),
					array(
						'user_id'  => $page_names[ $x ],
						'meta_key' => 'description',
					)
				);
				$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
			} elseif ( 'Author twitter' === $page_types[ $x ] ) {
                                $author_result   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $user_table WHERE user_id=%d AND meta_key='twitter'", $page_names[ $x ] ) );
				$updated_content = $new_words[ $x ];

				$wpdb->update(
					$user_table,
					array( 'meta_value' => $updated_content ),
					array(
						'user_id'  => $page_names[ $x ],
						'meta_key' => 'twitter',
					)
				);
				$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
			} elseif ( 'Author googleplus' === $page_types[ $x ] ) {
                                $author_result   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $user_table WHERE user_id=%d AND meta_key='googleplus'", $page_names[ $x ] ) );
				$updated_content = $new_words[ $x ];

				$wpdb->update(
					$user_table,
					array( 'meta_value' => $updated_content ),
					array(
						'user_id'  => $page_names[ $x ],
						'meta_key' => 'googleplus',
					)
				);
				$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
			} elseif ( 'Author facebook' === $page_types[ $x ] ) {
                                $author_result   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $user_table WHERE user_id=%d AND meta_key='facebook'", $page_names[ $x ] ) );
				$updated_content = $new_words[ $x ];

				$wpdb->update(
					$user_table,
					array( 'meta_value' => $updated_content ),
					array(
						'user_id'  => $page_names[ $x ],
						'meta_key' => 'facebook',
					)
				);
				$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
			} elseif ( 'Author SEO Title' === $page_types[ $x ] ) {
                                $author_result   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $user_table WHERE user_id=%d AND meta_key='wpseo_title'", $page_names[ $x ] ) );
				$updated_content = $new_words[ $x ];

				if ( sizeof( (array) $author_result ) <= 0 ) {
					$wpdb->insert(
						$user_table,
						array(
							'meta_value' => $updated_content,
							'meta_key'   => 'wpseo_title',
							'user_id'    => $page_names[ $x ],
						)
					);
				} else {
					$wpdb->update(
						$user_table,
						array( 'meta_value' => $updated_content ),
						array(
							'user_id'  => $page_result[0]->post_author,
							'meta_key' => 'wpseo_title',
						)
					);
				}
				$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
			} elseif ( 'Author SEO Description' === $page_types[ $x ] ) {
                                $author_result   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $user_table WHERE user_id=%d AND meta_key='wpseo_metadesc'", $page_names[ $x ] ) );
				$updated_content = $new_words[ $x ];

				if ( sizeof( (array) $author_result ) <= 0 ) {
					$wpdb->insert(
						$user_table,
						array(
							'meta_value' => $updated_content,
							'meta_key'   => 'wpseo_metadesc',
							'user_id'    => $page_result[0]->post_author,
						)
					);
				} else {
					$wpdb->update(
						$user_table,
						array( 'meta_value' => $updated_content ),
						array(
							'user_id'  => $page_names[ $x ],
							'meta_key' => 'wpseo_metadesc',
						)
					);
				}
				$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
			} elseif ( 'Media Alternate Text' === $page_types[ $x ] ) {

				$menu_result     = $wpdb->get_results( $wpdb->prepare( 'SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="%s"',  $page_names[ $x ] ) );
				$caption         = get_post_meta( $menu_result[0]->ID, '_wp_attachment_image_alt', true );
				$updated_content = $new_words[ $x ];

				$updated_content = sanitize_text_field( $updated_content );
				update_post_meta( $menu_result[0]->ID, '_wp_attachment_image_alt', $updated_content );
				$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
			} elseif ( 'Media Caption' === $page_types[ $x ] ) {

				$page_result = $wpdb->get_results( $wpdb->prepare( 'SELECT post_excerpt, post_title FROM ' . $table_name . ' WHERE ID="%s"',  $page_names[ $x ] ) );

				$updated_content = $new_words[ $x ];

				$wpdb->update( $table_name, array( 'post_excerpt' => $updated_content ), array( 'ID' => $page_names[ $x ] ) );
				$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
			} elseif ( 'Tag Description' === $page_types[ $x ] ) {

				$tag_result = $wpdb->get_results( $wpdb->prepare( 'SELECT description FROM ' . $taxonomy_table . ' WHERE term_id=%d',  $page_names[ $x ] ) );

				$updated_content = $new_words[ $x ];

				$wpdb->update( $taxonomy_table, array( 'description' => $updated_content ), array( 'term_id' => $page_names[ $x ] ) );
				$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
			} elseif ( 'Category Description' === $page_types[ $x ] ) {

				$tag_result = $wpdb->get_results( $wpdb->prepare( 'SELECT description FROM ' . $taxonomy_table . ' WHERE term_id=%d',  $page_names[ $x ] ) );

				$updated_content = $new_words[ $x ];

				$wpdb->update( $taxonomy_table, array( 'description' => $updated_content ), array( 'term_id' => $page_names[ $x ] ) );
				$wpdb->delete( $words_table, array( 'word' => $old_word_ids[ $x ] ) );
			} elseif ( 'SEO Page Title' === $page_types[ $x ] || 'SEO Post Title' === $page_types[ $x ] || 'SEO Media Title' === $page_types[ $x ] ) {
				if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) ) {

					$wpdb->insert(
						$meta_table,
						array(
							'post_id'    => $page_names[ $x ],
							'meta_key'   => '_yoast_wpseo_title',
							'meta_value' => $new_words[ $x ],
						)
					);

					$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
				} elseif ( is_plugin_active( 'seo-ultimate/seo-ultimate.php' ) ) {
					$wpdb->insert(
						$meta_table,
						array(
							'post_id'    => $page_names[ $x ],
							'meta_key'   => '_su_title',
							'meta_value' => $new_words[ $x ],
						)
					);

					$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
				} elseif ( is_plugin_active( 'all-in-one-seo-pack/all_in_one_seo_pack.php' ) ) {
					$wpdb->insert(
						$meta_table,
						array(
							'post_id'    => $page_names[ $x ],
							'meta_key'   => '_aioseop_title',
							'meta_value' => $new_words[ $x ],
						)
					);

					$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
				} elseif ( is_plugin_active( 'seo-by-rank-math/rank-math.php' ) ) {
						$wpdb->insert(
							$meta_table,
							array(
								'post_id'    => $page_names[ $x ],
								'meta_key'   => 'rank_math_title',
								'meta_value' => $new_words[ $x ],
							)
						);

					$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
				} else {
					$seo_error = true;
				}
			} elseif ( 'SEO Page Description' === $page_types[ $x ] || 'SEO Post Description' === $page_types[ $x ] || 'SEO Media Description' === $page_types[ $x ] ) {
				if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) ) {

					$wpdb->insert(
						$meta_table,
						array(
							'post_id'    => $page_names[ $x ],
							'meta_key'   => '_yoast_wpseo_metadesc',
							'meta_value' => $new_words[ $x ],
						)
					);

					$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
				} elseif ( is_plugin_active( 'seo-ultimate/seo-ultimate.php' ) ) {
					$wpdb->insert(
						$meta_table,
						array(
							'post_id'    => $page_names[ $x ],
							'meta_key'   => '_su_description',
							'meta_value' => $new_words[ $x ],
						)
					);

					$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
				} elseif ( is_plugin_active( 'all-in-one-seo-pack/all_in_one_seo_pack.php' ) ) {
					$wpdb->insert(
						$meta_table,
						array(
							'post_id'    => $page_names[ $x ],
							'meta_key'   => '_aioseop_description',
							'meta_value' => $new_words[ $x ],
						)
					);

					$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
				} elseif ( is_plugin_active( 'seo-by-rank-math/rank-math.php' ) ) {
						$wpdb->insert(
							$meta_table,
							array(
								'post_id'    => $page_names[ $x ],
								'meta_key'   => 'rank_math_description',
								'meta_value' => $new_words[ $x ],
							)
						);

					$wpdb->delete( $words_table, array( 'id' => $old_word_ids[ $x ] ) );
				} else {
					$seo_error = true;
				}
			}

			$page_url     = get_permalink( $page_names[ $x ] );
			$page_title   = get_the_title( $page_names[ $x ] );
			$current_time = gmdate( 'l F d, g:i a' );
			//$loc = dirname(__FILE__) . "/spellcheck.debug";
			//$debug_file = fopen($loc, 'a');
			//$debug_var = fwrite( $debug_file, " Empty Field | New Word: " . $new_words[$x] . " | Type: " . $page_types[$x] . " | Page Name: " . $page_title . " | Page URL: " . $page_url . " | Timestamp: " . $current_time . "\r\n\r\n" );
			//fclose($debug_file);
		}

		$message = '';
		if ( $seo_error ) {
			$message = "<div style='color: #FF0000'>SEO fields could not be updated because no active SEO plugin could be detected</div>";
		}
		return 'Empty Fields have been updated' . $message;
	}
}

function wpscx_preview_highlights( $content ) {

	if ( ! isset( $_GET['preview'] ) || 'true' !== $_GET['preview'] ) {
		return $content;
	}

	$content = str_replace( 'background: #FFC0C0', 'background: None', $content );
	$content = str_replace( 'background: #a3c5ff;', 'background: None', $content );
	$content = str_replace( 'background: #59c033;', 'background: None', $content );
	return $content;
}

if ( isset( $_GET['preview'] ) && 'true' === $_GET['preview'] ) {
	add_filter( 'the_content', 'wpscx_preview_highlights' );
}
