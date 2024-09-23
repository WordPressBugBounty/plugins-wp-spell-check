<?php
function wpgcx_set_global_vars() {
	global $wpdb;
	global $wpgc_options;
	global $wpgc_scan_delay;

	$wpscx_scan_delay = 1;

	$options_table = $wpdb->prefix . 'spellcheck_grammar_options';

	$check_opt = $wpdb->get_results( "SHOW TABLES LIKE '$options_table'" );

	if ( sizeof( (array) $check_opt ) !== 0 ) {
		if ( sizeof( (array) $wpgc_options ) < 1 ) {
			$wpgc_options = $wpdb->get_results( "SELECT * FROM $options_table" );
		}
	}
}

function wpgcx_check_pages() {
	$scanner = new Wpscx_Grammar_Scanner;

		$scanner->check_pages();
}
add_action( 'wpgcx_check_pages', 'wpgcx_check_pages' );

function wpgcx_check_posts() {
	$scanner = new Wpscx_Grammar_Scanner;

		$scanner->check_posts();
}
add_action( 'wpgcx_check_posts', 'wpgcx_check_posts' );

function wpgcx_scan_individual( $page_id ) {
	$scanner = new Wpscx_Grammar_Scanner;

		$scanner->scan_individual( $page_id );
}

function wpgcx_scan_site() {
	$scanner = new Wpscx_Grammar_Scanner;

		$scanner->scan_site();
}
add_action( 'wpgcx_scan_site', 'wpgcx_scan_site' );

add_action( 'wpgc_scan_site', 'wpgc_scan_site' );

function wpgcx_clear_results() {
	global $wpdb;
	$results_table = $wpdb->prefix . 'spellcheck_grammar';
	$options_table = $wpdb->prefix . 'spellcheck_grammar_options';

	$wpdb->query( "DELETE FROM $results_table WHERE 1" );
	$wpdb->update( $options_table, array( 'option_value' => 0 ), array( 'option_name' => 'posts_scanned' ) );
	$wpdb->update( $options_table, array( 'option_value' => 0 ), array( 'option_name' => 'pages_scanned' ) );
	$wpdb->update( $options_table, array( 'option_value' => 0 ), array( 'option_name' => 'last_scan_errors' ) );
}

function wpgcx_register_meta_boxes() {
	//      add_meta_box( 'wpgc_meta_box', 'WP Spell Check', 'wpsc_create_meta_box',  array('post','page'), 'advanced', 'high' );
}
//add_action( 'add_meta_boxes', 'wpgcx_register_meta_boxes' );

function wpsc_create_meta_box( $post ) {
	?>
	<!--<div class="wpsc-modal-box"></div>
	<?php
	if ( isset( $_GET['wpsc-scan-page'] ) && 1 === $_GET['wpsc-scan-page'] ) {
				$spellcheck = new Wpscx_Spellcheck_Scanner;
		$wpsc_data          = $spellcheck->scan_single( $post->ID );

		if ( sizeof( (array) $wpsc_data ) > 0 ) {
			?>
			<table border="0" style="margin-bottom: 10px;">
				<tr style="border-bottom: 1px solid grey;">
					<td style="padding: 5px 10px;"><strong>Word</strong></td>
					<td style="padding: 5px 10px;"><strong>Type</strong></td>
					<td style="padding: 5px 10px;"><strong>Page Name</strong></td>
				</tr>
					<?php
					foreach ( $wpsc_data as $row ) {
						?>
								<tr>
									<td style="padding: 5px 10px;"><?php echo esc_html( $row['word'] ); ?></td>
									<td style="padding: 5px 10px;"><?php echo esc_html( $row['page_type'] ); ?></td>
									<td style="padding: 5px 10px;"><?php echo esc_html( $row['page_name'] ); ?></td>
								</tr>
							<?php
					}
					?>
			</table>
			<?php
		} else {
			echo 'No spelling errors have been found';
		}
	} else {
		global $wpdb;
		$table = $wpdb->prefix . 'spellcheck_words';
		$id    = sanitize_text_field( $_GET['post'] );

		$wpsc_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE ignore_word is false AND page_id=%d", $id ) );

		if ( sizeof( (array) $wpsc_data ) > 0 ) {
			?>
			<table border="0" style="margin-bottom: 10px;">
				<tr style="border-bottom: 1px solid grey;">
					<td style="padding: 5px 10px;"><strong>Word</strong></td>
					<td style="padding: 5px 10px;"><strong>Type</strong></td>
					<td style="padding: 5px 10px;"><strong>Page Name</strong></td>
				</tr>
					<?php
					foreach ( $wpsc_data as $row ) {
						?>
								<tr>
									<td style="padding: 5px 10px;"><?php echo esc_html( $row->word ); ?></td>
									<td style="padding: 5px 10px;"><?php echo esc_html( $row->page_type ); ?></td>
									<td style="padding: 5px 10px;"><?php echo esc_html( $row->page_name ); ?></td>
								</tr>
							<?php
					}
					?>
			</table>
			<?php
		} else {
			echo 'No spelling errors have been found';
		}
	}
	?>
	<a name="wpscmetabox"></a>-->
	<?php
}
function wpgcx_create_meta_box( $post ) {
	if ( isset( $_GET['wpgc-scan-page-grammar'] ) ) {
		if ( 'Gramme Check' === $_GET['wpgc-scan-page'] ) {
			wpgcx_scan_individual( $post->ID );
		}
	}
}
add_action( 'add_meta_boxes', 'wpgcx_create_meta_box' );

function wpgcx_check_duplicate( $content ) {
	$count = preg_match_all( '/  +/g', $content, $matches );
	return $count;
}

function wpgcx_check_errors( $to_check, $error_list ) {
	$count = 0;

	foreach ( $error_list as $error ) {
		$count = $count + substr_count( $to_check, ' ' . $error . ' ' );
	}
	return $count;
}

function wpgcx_parse_suggestions( $error_list, $suggestion_list ) {
		$results = array();

	if ( null !== $error_list ) {
		foreach ( $error_list as $error ) {
			foreach ( $suggestion_list as $suggestion ) {
				if ( $error === $suggestion[0] ) {
					array_push( $results, array( $error, $suggestion[1] ) );
				}
			}
		}
	}
		return $results;
}

function wpgcx_stop_scan() {
	global $wpdb;
	$options_table = $wpdb->prefix . 'spellcheck_grammar_options';

	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'scan_running' ) );
}

function wpgcx_highlight_errors( $post ) {
	$start = time();
        $post_id = '';

        if (isset($_GET['post'])) { $post_id = sanitize_text_field( $_GET['post'] ); }
	global $wpdb;
	global $wpscx_ent_included;
	global $wpscx_dict_list;
	global $wpsc_settings;
	$table_name        = $wpdb->prefix . 'spellcheck_options';
	$dict_table        = $wpdb->prefix . 'spellcheck_dictionary';
	$language_setting  = $wpsc_settings[11];
	$dict_words        = $wpscx_dict_list;
	$results_table     = $wpdb->prefix . 'spellcheck_grammar';
	$words_table       = $wpdb->prefix . 'spellcheck_words';
	$spellcheck        = false;
	$suggest_list_full = array();

	$init_finished = time();

	if ( isset( $_GET['wpgc-scan-page'] ) ) {
		if ( 'Spell Check' === $_GET['wpgc-scan-page'] ) {
			$spellcheck = true;
		}
	}
	if ( isset( $_GET['wpgc-scan-page'] ) ) {
		if ( 'Gramme Check' === $_GET['wpgc-scan-page'] ) {
			wpgcx_scan_individual( $post_id );
		}
	}
	$spelling_highlight = array();
	if ( '' !== $post_id ) {
		$wpsc_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $words_table WHERE page_id = %d", $post_id ) );
	} else {
		$wpsc_data = array();
	}
	if ( $spellcheck ) {
				$spellcheck_scanner = new Wpscx_Spellcheck_Scanner;
			$wpsc_data              = $spellcheck_scanner->scan_single( $post_id );
	}

	$single_scan_finished = time();

	$word_list = array();
	foreach ( $dict_words as $dict_word ) {
		array_push( $word_list, $dict_word->word );
	}

		$language_setting = $wpdb->get_results( 'SELECT option_value from ' . $table_name . ' WHERE option_name="language_setting";' );

		$loc      = dirname( __FILE__ ) . '/../dict/' . $language_setting[0]->option_value . '.pws';
		$file     = fopen( $loc, 'r' );
		$contents = fread( $file, filesize( $loc ) );
		fclose( $file );

		$contents  = str_replace( "\r\n", "\n", $contents );
		$main_list = explode( "\n", $contents );

		$word_list = array_merge( $word_list, $main_list );

	$dict_loaded = time();

	if ( $spellcheck ) {
		foreach ( $wpsc_data as $item ) {
			if ( ! in_array( $item['word'], $spelling_highlight, true ) ) {
				array_push( $spelling_highlight, preg_quote( htmlentities( $item['word'] ) ) );
				$spelling_suggestion = '';
				$suggestions         = 0;

				foreach ( $word_list as $words ) {

					$first_word = stripslashes( $item['word'] );
					if ( gettype( $words ) === 'string' ) {
						similar_text( strtoupper( $first_word ), strtoupper( $words ), $percentage );
					}
					if ( $percentage > 80.00 ) {
						$spelling_suggestion .= $words . ',';
						$suggestions++;
					}

					if ( $suggestions >= 4 ) {
						break;
					}
				}
				if ( $suggestions < 4 ) {
					foreach ( $word_list as $words ) {

						$first_word = stripslashes( $item['word'] );
						if ( gettype( $words ) === 'string' ) {
							similar_text( strtoupper( $first_word ), strtoupper( $words ), $percentage );
						}
						if ( $percentage > 60.00 ) {
							$spelling_suggestion .= $words . ',';
							$suggestions++;
						}

						if ( $suggestions >= 4 ) {
							break;
						}
					}
				}
				array_push( $suggest_list_full, array( htmlentities( $item['word'] ), htmlentities( $spelling_suggestion ) ) );
			}
		}
	} else {
		foreach ( $wpsc_data as $item ) {
			if ( ! in_array( $item->word, $spelling_highlight, true ) ) {
				array_push( $spelling_highlight, preg_quote( htmlentities( $item->word ) ) );
				$spelling_suggestion = '';
				$suggestions         = 0;

				foreach ( $word_list as $words ) {

					$first_word = stripslashes( $item->word );
					if ( gettype( $words ) === 'string' ) {
						similar_text( strtoupper( $first_word ), strtoupper( $words ), $percentage );
					}
					if ( $percentage > 80.00 ) {
						$spelling_suggestion .= $words . ',';
						$suggestions++;
					}

					if ( $suggestions >= 4 ) {
						break;
					}
				}
				if ( $suggestions < 4 ) {
					foreach ( $word_list as $words ) {

						$first_word = stripslashes( $item->word );
						if ( gettype( $words ) === 'string' ) {
							similar_text( strtoupper( $first_word ), strtoupper( $words ), $percentage );
						}
						if ( $percentage > 60.00 ) {
							$spelling_suggestion .= $words . ',';
							$suggestions++;
						}

						if ( $suggestions >= 4 ) {
							break;
						}
					}
				}
				array_push( $suggest_list_full, array( htmlentities( $item->word ), htmlentities( $spelling_suggestion ) ) );
			}
		}
	}

	$suggested_spelling_setup = time();

	if ( '' !== $post_id ) {
		$score = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $results_table WHERE page_id=%d", $post_id ) );
	} else {
		$score = array();
	}

	$complex_expression_highlight   = array();
	$contractions_highlight         = array();
	$grammar_highlight              = array();
	$hidden_verb_highlight          = array();
	$passive_voice_highlight        = array();
	$possessive_ending_highlight    = array();
	$redundant_expression_highlight = array();
	$suggestion_list                = array();
        $complex_expression_list        = array();
        $contractions_list              = array();
        $grammar_list                   = array();
        $hidden_verb_list               = array();
        $passive_voice_list             = array();
        $possessive_ending_list         = array();
        $redundant_expression_list      = array();
        
        
	if ( sizeof( (array) $score ) > 0 ) {

		$loc      = dirname( __FILE__ ) . '/complex_expression.pws';
		$file     = fopen( $loc, 'r' );
		$contents = fread( $file, filesize( $loc ) );
		fclose( $file );

		$contents                = str_replace( "\r\n", "\n", $contents );
		$complex_expression_list = explode( "\n", $contents );
                
                //echo "Location: $loc <br>";
                //echo "Array Size: " . sizeof($complex_expression_list); echo "<br>";

		$loc      = dirname( __FILE__ ) . '/contractions.pws';
		$file     = fopen( $loc, 'r' );
		$contents = fread( $file, filesize( $loc ) );
		fclose( $file );

		$contents          = str_replace( "\r\n", "\n", $contents );
		$contractions_list = explode( "\n", $contents );
                
                //echo "Location: $loc <br>";
                //echo "Array Size: " . sizeof($contractions_list); echo "<br>";

		$loc      = dirname( __FILE__ ) . '/grammar.pws';
		$file     = fopen( $loc, 'r' );
		$contents = fread( $file, filesize( $loc ) );
		fclose( $file );

		$contents     = str_replace( "\r\n", "\n", $contents );
		$grammar_list = explode( "\n", $contents );
                
                //echo "Location: $loc <br>";
                //echo "Array Size: " . sizeof($grammar_list); echo "<br>";

		$loc      = dirname( __FILE__ ) . '/hidden_verb.pws';
		$file     = fopen( $loc, 'r' );
		$contents = fread( $file, filesize( $loc ) );
		fclose( $file );

		$contents         = str_replace( "\r\n", "\n", $contents );
		$hidden_verb_list = explode( "\n", $contents );
                
                //echo "Location: $loc <br>";
                //echo "Array Size: " . sizeof($hidden_verb_list); echo "<br>";

		$loc      = dirname( __FILE__ ) . '/passive_voice.pws';
		$file     = fopen( $loc, 'r' );
		$contents = fread( $file, filesize( $loc ) );
		fclose( $file );

		$contents           = str_replace( "\r\n", "\n", $contents );
		$passive_voice_list = explode( "\n", $contents );
                
                //echo "Location: $loc <br>";
                //echo "Array Size: " . sizeof($passive_voice_list); echo "<br>";

		$loc      = dirname( __FILE__ ) . '/possessive_ending.pws';
		$file     = fopen( $loc, 'r' );
		$contents = fread( $file, filesize( $loc ) );
		fclose( $file );

		$contents               = str_replace( "\r\n", "\n", $contents );
		$possessive_ending_list = explode( "\n", $contents );
                
                //echo "Location: $loc <br>";
                //echo "Array Size: " . sizeof($possessive_ending_list); echo "<br>";

		$loc      = dirname( __FILE__ ) . '/redundant_expression.pws';
		$file     = fopen( $loc, 'r' );
		$contents = fread( $file, filesize( $loc ) );
		fclose( $file );

		$contents                  = str_replace( "\r\n", "\n", $contents );
		$redundant_expression_list = explode( "\n", $contents );
                
                //echo "Location: $loc <br>";
                //echo "Array Size: " . sizeof($redundant_expression_list); echo "<br>";

		$loc      = dirname( __FILE__ ) . '/suggestions.pws';
		$file     = fopen( $loc, 'r' );
		$contents = fread( $file, filesize( $loc ) );
		fclose( $file );

		$contents        = str_replace( "\r\n", "\n", $contents );
		$suggestion_list = explode( "\n", $contents );

		foreach ( $suggestion_list as $suggestion_line ) {
			$suggestion = explode( ':', $suggestion_line );
                        if ( !isset( $suggestion[1] ) ) $suggestion[1] = '';
			array_push( $suggest_list_full, array( $suggestion[0], $suggestion[1] ) );
		}

		$words_content = $post->post_content;
		$words_content = preg_replace( '/&lt;/', '<', $words_content );
		$words_content = preg_replace( '/&gt;/', '>', $words_content );

		$words_content = do_shortcode( $words_content );

		$words_content = preg_replace( '@<style[^>]*?>.*?</style>@siu', ' ', $words_content );
		$words_content = preg_replace( '@<script[^>]*?>.*?</script>@siu', ' ', $words_content );
		$words_content = preg_replace( '/(\<.*?\>)/', ' ', $words_content );
		$words_content = preg_replace( '/<iframe.+<\/iframe>/', ' ', $words_content );
		$words_content = html_entity_decode( strip_tags( $words_content ), ENT_QUOTES, 'utf-8' );
		$words_content = preg_replace( '/(\[et_pb.*?\])/', ' ', $words_content );
		$words_content = preg_replace( '/(\[\/et_pb.*?\])/', ' ', $words_content );
		$words_content = preg_replace( '/(\[[1-9].*?\])/', ' ', $words_content );
		$words_content = preg_replace( '/(\[ICBOapproval.*?\])/', ' ', $words_content );

		preg_match_all( '/(\.|\?|\!|\,|\:|\;)([a-z]|[A-Z])+/', $words_content, $spacing_highlight );

		foreach ( $spacing_highlight[0] as $parse_suggest ) {
			$original_suggest = $parse_suggest;
			$parse_suggest    = str_replace( '.', '. ', $parse_suggest );
			$parse_suggest    = str_replace( '?', '? ', $parse_suggest );
			$parse_suggest    = str_replace( '!', '! ', $parse_suggest );
			$parse_suggest    = str_replace( ',', ', ', $parse_suggest );
			$parse_suggest    = str_replace( ';', '; ', $parse_suggest );
			$parse_suggest    = str_replace( ':', ': ', $parse_suggest );
			array_push( $suggest_list_full, array( $original_suggest, $parse_suggest ) );
		}
	}
	$finalized_time = time();

	$divi_check = wp_get_theme();

	?>
		<div class="wpgc-dialog" style="display:none;">
			<ul>
			</ul>
		</div>
		<script type="text/javascript">
						function wpscx_create_regex(item) {
							if (item.charAt(0).match(/[^a-z]/i)) {
								regex_item = new RegExp('(?![^<]*>)' + item + "\\b(?!.*&gt;)" , "gim");
							} else {
								regex_item = new RegExp("(?![^<]*>)(?![^&lt;]*&gt;)\\b" + item + "\\b(?!.*&gt;)" , "gim"); //(?![^\[]*\])
							}
							
							return regex_item;
						}
					
			jQuery(document).ready(function($) {
				var spelling_highlight = <?php echo json_encode( $spelling_highlight ); ?>;
				var complex_highlight = <?php echo json_encode( $complex_expression_list ); ?>;
				var contractions_highlight = <?php echo json_encode( $contractions_list ); ?>;
				var grammar_highlight = <?php echo json_encode( $grammar_list ); ?>;
				var hidden_highlight = <?php echo json_encode( $hidden_verb_list ); ?>;
				var passive_highlight = <?php echo json_encode( $passive_voice_list ); ?>;
				var possessive_highlight = <?php echo json_encode( $possessive_ending_list ); ?>;
				var redundant_highlight = <?php echo json_encode( $redundant_expression_list ); ?>;
				var suggestions = <?php echo json_encode( $suggest_list_full ); ?>;
				var spellcheck = <?php echo json_encode( $spellcheck ); ?>;
				var builder_check = <?php echo json_encode( $divi_check->name ); ?>;
				
				if (spelling_highlight == null) spelling_highlight = [];
				if (complex_highlight == null) complex_highlight = [];
				if (contractions_highlight == null) contractions_highlight = [];
				if (grammar_highlight == null) grammar_highlight = [];
				if (hidden_highlight == null) hidden_highlight = [];
				if (passive_highlight == null) passive_highlight = [];
				if (possessive_highlight == null) possessive_highlight = [];
				if (redundant_highlight == null) redundant_highlight = [];
				if (suggestions == null) suggestions = [];
				if (spellcheck == null) spellcheck = false;

				var allow_save = "false";
				var current_highlight = "grammar";
				var html_to_add = "";
				var word_to_edit;
				var suggest_split;
				var regex_item;
                                var editor;
                                
                                jQuery(".wp-editor-area#content").change( function(event) { 
                                    window.addEventListener("beforeunload", function(event) {
                                        event.returnValue = "Are you sure you want to continue?";
                                    });
                                });
				
				for(x = 0; x < spelling_highlight.length; x++) {
					spelling_highlight[x] = spelling_highlight[x].replace("&eacute;", "é");
					spelling_highlight[x] = spelling_highlight[x].replace("&egrave;", "è");
					spelling_highlight[x] = spelling_highlight[x].replace("&ugrave;", "ù");
					spelling_highlight[x] = spelling_highlight[x].replace("&acirc;", "â");
					spelling_highlight[x] = spelling_highlight[x].replace("&ecirc;", "ê");
					spelling_highlight[x] = spelling_highlight[x].replace("&icirc;", "î");
					spelling_highlight[x] = spelling_highlight[x].replace("&ocirc;", "ô");
					spelling_highlight[x] = spelling_highlight[x].replace("&ucirc;", "û");
					spelling_highlight[x] = spelling_highlight[x].replace("&ccedil;", "ç");
					spelling_highlight[x] = spelling_highlight[x].replace("&euml;", "ë");
					spelling_highlight[x] = spelling_highlight[x].replace("&iuml;", "ï");
					spelling_highlight[x] = spelling_highlight[x].replace("&uuml;", "ü");
										spelling_highlight[x] = spelling_highlight[x].replace(">", "&gt;");
										spelling_highlight[x] = spelling_highlight[x].replace("<", "&lt;");
				}
				
				for(x = 0; x < suggestions.length; x++) {
					for (y = 0; y < suggestions[x].length; y++) {
						if (suggestions[x][y] != null) {
							suggestions[x][y] = suggestions[x][y].replace("&eacute;", "é");
							suggestions[x][y] = suggestions[x][y].replace("&egrave;", "è");
							suggestions[x][y] = suggestions[x][y].replace("&ugrave;", "ù");
							suggestions[x][y] = suggestions[x][y].replace("&acirc;", "â");
							suggestions[x][y] = suggestions[x][y].replace("&ecirc;", "ê");
							suggestions[x][y] = suggestions[x][y].replace("&icirc;", "î");
							suggestions[x][y] = suggestions[x][y].replace("&ocirc;", "ô");
							suggestions[x][y] = suggestions[x][y].replace("&ucirc;", "û");
							suggestions[x][y] = suggestions[x][y].replace("&ccedil;", "ç");
							suggestions[x][y] = suggestions[x][y].replace("&euml;", "ë");
							suggestions[x][y] = suggestions[x][y].replace("&iuml;", "ï");
							suggestions[x][y] = suggestions[x][y].replace("&uuml;", "ü");
						}
					}
				}
				if (typeof jQuery(".wp-editor-area#content").html() === 'undefined') return;
				html_check = new RegExp("<(?!.*>)", 'gim');
				html_result = jQuery(".wp-editor-area#content").html().match(html_check);
								//console.log(jQuery(".wp-editor-area#content").html().match(html_check));
				if (html_result != null) {
					jQuery('.wpsc-editor-message').html('Invalid HTML detected on page. Please fix the HTML in order to see proofreading highlights');
					return;
				}
								html_check = new RegExp("&lt;(?!.*&gt;)", 'gim');
				html_result = jQuery(".wp-editor-area#content").html().match(html_check);
								//console.log(jQuery(".wp-editor-area#content").html().match(html_check));
				if (html_result != null) {
					jQuery('.wpsc-editor-message').html('Invalid HTML detected on page. Please fix the HTML in order to see proofreading highlights');
					return;
				}
								  
								  
				if (builder_check != "Divi") {
				if (jQuery('#wp-content-wrap').hasClass('tmce-active')) {
									//jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace("&lt;","<"));
									//jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace("&gt;",">"));
									window.setTimeout(function() {
										var highlightTime = new Date();
										var totalTime = highlightTime.getTime();
					if (spellcheck == true) {
						current_highlight = "spelling"
												window.setTimeout(function() {
						spelling_highlight.forEach(function (item) {
													regex_item = wpscx_create_regex(item);
													var str_rep = item.replace(/\\(.)/mg, "$1");
													jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(regex_item, "<span class='hiddenSpellError wpgc-spelling' style='background: #FFC0C0;'>" + str_rep + "</span>"));
													jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(regex_item, "<span class='hiddenSpellError wpgc-spelling' style='background: #FFC0C0;'>" + str_rep + "</span>"));
													console.log(regex_item);
						});
						
						complex_highlight.forEach(function (item) {
							regex_item = wpscx_create_regex(item);
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(regex_item, "<span class='hiddenSpellError wpgc-complex' style='background: inherit'>" + item + "</span>"));
														jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(regex_item, "<span class='hiddenSpellError wpgc-complex' style='background: inherit'>" + item + "</span>"));
						});
						
						contractions_highlight.forEach(function (item) {
							regex_item = wpscx_create_regex(item);
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(regex_item, "<span class='hiddenSpellError wpgc-contraction' style='background: inherit'>" + item + "</span>"));
														jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(regex_item, "<span class='hiddenSpellError wpgc-contraction' style='background: inherit'>" + item + "</span>"));
						});
						
						grammar_highlight.forEach(function (item) {
							regex_item = wpscx_create_regex(item);
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(regex_item, "<span class='hiddenSpellError wpgc-grammar' style='background: inherit'>" + item + "</span>"));
														jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(regex_item, "<span class='hiddenSpellError wpgc-grammar' style='background: inherit'>" + item + "</span>"));
						});
						
						hidden_highlight.forEach(function (item) {
							regex_item = wpscx_create_regex(item);
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(regex_item, "<span class='hiddenSpellError wpgc-hidden' style='background: inherit'>" + item + "</span>"));
														jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(regex_item, "<span class='hiddenSpellError wpgc-hidden' style='background: inherit'>" + item + "</span>"));
						});
						
						passive_highlight.forEach(function (item) {
							regex_item = wpscx_create_regex(item);
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(regex_item, "<span class='hiddenSpellError wpgc-passive' style='background: inherit'>" + item + "</span>"));
														jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(regex_item, "<span class='hiddenSpellError wpgc-passive' style='background: inherit;'>" + item + "</span>"));
						});
						
						redundant_highlight.forEach(function (item) {
							regex_item = wpscx_create_regex(item);
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(regex_item, "<span class='hiddenSpellError wpgc-redundant' style='background: inherit'>" + item + "</span>"));
														jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(regex_item, "<span class='hiddenSpellError wpgc-redundant' style='background: inherit;'>" + item + "</span>"));
						});
						
						possessive_highlight.forEach(function (item) {
							regex_item = wpscx_create_regex(item);
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(regex_item, "<span class='hiddenSpellError wpgc-possessive' style='background: inherit'>" + item + "</span>"));
														jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(regex_item, "<span class='hiddenSpellError wpgc-possessive' style='background: inherit;'>" + item + "</span>"));
						});
                                                wpgc_set_listeners();
						}, 3000);
					} else {
											window.setTimeout(function() {
						complex_highlight.forEach(function (item) {
							regex_item = wpscx_create_regex(item);
							//console.log(regex_item);
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(regex_item, "<span class='hiddenSpellError wpgc-complex' style='background: #a3c5ff;'>" + item + "</span>"));
                                                        jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(regex_item, "<span class='hiddenSpellError wpgc-complex' style='background: #a3c5ff;'>" + item + "</span>"));
                                                        //console.log(item);
						});
						
						contractions_highlight.forEach(function (item) {
							regex_item = wpscx_create_regex(item);
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(regex_item, "<span class='hiddenSpellError wpgc-contraction' style='background: #59c033;'>" + item + "</span>"));
														jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(regex_item, "<span class='hiddenSpellError wpgc-contraction' style='background: #59c033;'>" + item + "</span>"));
														//console.log(item);
						});
						
						grammar_highlight.forEach(function (item) {
							regex_item = wpscx_create_regex(item);
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(regex_item, "<span class='hiddenSpellError wpgc-grammar' style='background: #59c033;'>" + item + "</span>"));
														jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(regex_item, "<span class='hiddenSpellError wpgc-grammar' style='background: #59c033;'>" + item + "</span>"));
														//console.log(item);
						});
						
						hidden_highlight.forEach(function (item) {
							regex_item = wpscx_create_regex(item);
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(regex_item, "<span class='hiddenSpellError wpgc-hidden' style='background: #59c033;'>" + item + "</span>"));
														jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(regex_item, "<span class='hiddenSpellError wpgc-hidden' style='background: #59c033;'>" + item + "</span>"));
														//console.log(item);
						});
						
						passive_highlight.forEach(function (item) {
							regex_item = wpscx_create_regex(item);
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(regex_item, "<span class='hiddenSpellError wpgc-passive' style='background: #59c033;'>" + item + "</span>"));
														jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(regex_item, "<span class='hiddenSpellError wpgc-passive' style='background: #59c033;'>" + item + "</span>"));
														//console.log(item);
						});
						
						redundant_highlight.forEach(function (item) {
							regex_item = wpscx_create_regex(item);
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(regex_item, "<span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'>" + item + "</span>"));
														jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(regex_item, "<span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'>" + item + "</span>"));
														//console.log(item);
						});
						
						possessive_highlight.forEach(function (item) {
							regex_item = wpscx_create_regex(item);
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(regex_item, "<span class='hiddenSpellError wpgc-possessive' style='background: #59c033;'>" + item + "</span>"));
														jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(regex_item, "<span class='hiddenSpellError wpgc-possessive' style='background: #59c033;'>" + item + "</span>"));
														//console.log(item);
						});
												
												spelling_highlight.forEach(function (item) {
							regex_item = wpscx_create_regex(item);
														var str_rep = item.replace(/\\(.)/mg, "$1");
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(regex_item, "<span class='hiddenSpellError wpgc-spelling' style='background: inherit;'>" + str_rep + "</span>"));
														jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(regex_item, "<span class='hiddenSpellError wpgc-spelling' style='background: inherit;'>" + str_rep + "</span>"));
														//console.log(regex_item);
						});
                                                wpgc_set_listeners();
												}, 1000);
					}
										var highlightTime = new Date();
										var endTime = highlightTime.getTime();
										//console.log("Highlight Time: " + ( endTime - totalTime ) / 1000);
										}, 3000);
										
				}
				}
				
				jQuery('.wpgc-scan-page').click(function() {
					window.location.href = "<?php echo esc_url_raw( $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) . '&wpgc-scan-page=Spell Check'; ?>";
				});
				jQuery('.wpgc-scan-page-grammar').click(function() {
					window.location.href = "<?php echo esc_url_raw( $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) . '&wpgc-scan-page=Gramme Check'; ?>";
				});
				
				jQuery("#publishing-action .button").click(function(e) {
					if (allow_save != 'true') {
						e.preventDefault();
						jQuery(".wp-editor-area#content").contents().find(".hiddenSpellError").contents().unwrap();
						jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".hiddenSpellError").contents().unwrap();
						allow_save = 'true';
						
						jQuery("#publishing-action .button").click();
					}
				});
								
								jQuery("#save-action .button").click(function(e) {
									if (allow_save != 'true') {
						e.preventDefault();
						jQuery(".wp-editor-area#content").contents().find(".hiddenSpellError").contents().unwrap();
						jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".hiddenSpellError").contents().unwrap();
						allow_save = 'true';
						
						jQuery("#save-action .button").click();
					}
								});
				
				jQuery(".switch-html").click(function(e) {
					jQuery(".wp-editor-area#content").contents().find(".hiddenSpellError").contents().unwrap();
					jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".hiddenSpellError").contents().unwrap();
					allow_save = 'true';
				});
				
				jQuery("#content-tmce").click(function() {
					allow_save = 'false';
					wpgc_set_listeners();
				});
				jQuery(".wpgc-spelling-highlight").click(function() {
					if (current_highlight == 'spelling') {
						spelling_highlight.forEach(function (item) {
								jQuery(".wp-editor-area#content").contents().find(".wpgc-spelling").css("background","inherit");
								jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-spelling").css("background","inherit");
							});
						current_highlight = "none";
						allow_save = true;
					} else {
						if(current_highlight == 'grammar') {
						complex_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-complex").css("background","inherit");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-complex").css("background","inherit");
						});
						
						contractions_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-contraction").css("background","inherit");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-contraction").css("background","inherit");
						});

						grammar_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-grammar").css("background","inherit");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-grammar").css("background","inherit");
						});
						
						hidden_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-hidden").css("background","inherit");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-hidden").css("background","inherit");
						});
						
						passive_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-passive").css("background","inherit");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-passive").css("background","inherit");
						});

						redundant_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-redundant").css("background","inherit");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-redundant").css("background","inherit");
						});

						possessive_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-possessive").css("background","inherit");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-possessive").css("background","inherit");
						});
						}
						spelling_highlight.forEach(function (item) {
								jQuery(".wp-editor-area#content").contents().find(".wpgc-spelling").css("background","#FFC0C0");
								jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-spelling").css("background","#FFC0C0");
							});
						current_highlight = "spelling";
						allow_save = "false";
					}
                                        wpgc_set_listeners();
				});
				
				jQuery(".wpgc-grammar-highlight").click(function() {
					if (current_highlight == "grammar") {
						complex_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-complex").css("background","inherit");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-complex").css("background","inherit");
						});
						
						contractions_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-contraction").css("background","inherit");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-contraction").css("background","inherit");
						});

						grammar_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-grammar").css("background","inherit");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-grammar").css("background","inherit");
						});
						
						hidden_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-hidden").css("background","inherit");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-hidden").css("background","inherit");
						});
						
						passive_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-passive").css("background","inherit");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-passive").css("background","inherit");
						});

						redundant_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-redundant").css("background","inherit");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-redundant").css("background","inherit");
						});

						possessive_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-possessive").css("background","inherit");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-possessive").css("background","inherit");
						});
						allow_save = 'true';
						current_highlight = "none";
					} else {
						if(current_highlight == "spelling") {
							spelling_highlight.forEach(function (item) {
								jQuery(".wp-editor-area#content").contents().find(".wpgc-spelling").css("background","inherit");
								jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-spelling").css("background","inherit");
							});
						}
						complex_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-complex").css("background","#a3c5ff");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-complex").css("background","#a3c5ff");
						});
						
						contractions_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-contraction").css("background","#59c033");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-contraction").css("background","#59c033");
						});

						grammar_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-grammar").css("background","#59c033");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-grammar").css("background","#59c033");
						});
						
						hidden_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-hidden").css("background","#59c033");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-hidden").css("background","#59c033");
						});
						
						passive_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-passive").css("background","#59c033");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-passive").css("background","#59c033");
						});

						redundant_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-redundant").css("background","#59c033");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-redundant").css("background","#59c033");
						});

						possessive_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").contents().find(".wpgc-possessive").css("background","#59c033");
							jQuery(".wp-editor-area#content").contents().find(".wpgc-possessive").css("background","#59c033");
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").contents().find(".wpgc-possessive").css("background","#59c033");
						});
						allow_save = 'false';
						current_highlight = "grammar";
					}		});

				window.setTimeout(function(){ 
					var iframe = jQuery("#content_ifr, #excerpt_ifr").contents();
					
					wpgc_set_listeners();
					
					iframe.change(function() { wpgc_set_listeners(); });
					
					jQuery(".wpgc-complex-highlight").click(function() {
						wpgcx_clear_results();
						complex_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(' ' + item + ' ', " &lt;span class='hiddenSpellError wpgc-complex' style='background: #59c033;'&gt;" + item + "&lt;/span&gt;"));
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(' ' + item + ' ', '<span class="hiddenSpellError wpgc-complex" style="background: #a3c5ff;" data-mce-style="background: #a3c5ff;">' + item + '</span> '));
						});
						wpgc_set_listeners();
					});
					jQuery(".wpgc-contraction-highlight").click(function() {
						wpgcx_clear_results();
						contractions_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(' ' + item + ' ', " &lt;span class='hiddenSpellError wpgc-contraction' style='background: #59c033;'&gt;" + item + "&lt;/span&gt;"));
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(' ' + item + ' ', '<span class="hiddenSpellError wpgc-contraction" style="background: #59c033;" data-mce-style="background: #59c033;">' + item + '</span> '));
						});
						wpgc_set_listeners();
					});
					jQuery(".wpgc-grammar-highlight").click(function() {
						wpgcx_clear_results();
						grammar_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(' ' + item + ' ', " &lt;span class='hiddenSpellError wpgc-grammar' style='background: #59c033;'&gt;" + item + "&lt;/span&gt;"));
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(' ' + item + ' ', '<span class="hiddenSpellError wpgc-grammar" style="background: #59c033;" data-mce-style="background: #59c033;">' + item + '</span> '));
						});
						wpgc_set_listeners();
					});
					jQuery(".wpgc-hidden-highlight").click(function() {
						wpgcx_clear_results();
						hidden_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(" " + item + " ", " &lt;span class='hiddenSpellError wpgc-hidden' style='background: #59c033;'&gt;" + item + "&lt;/span&gt; "));
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(" " + item + " ", ' <span class="hiddenSpellError wpgc-hidden" style="background: #59c033;" data-mce-style="background: #59c033;">' + item + '</span> '));
						});
						wpgc_set_listeners();
					});
					jQuery(".wpgc-passive-highlight").click(function() {
						wpgcx_clear_results();
						passive_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(' ' + item + ' ', " &lt;span class='hiddenSpellError wpgc-passive' style='background: #59c033;'&gt;" + item + "&lt;/span&gt;"));
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(' ' + item + ' ', '<span class="hiddenSpellError wpgc-passive" style="background: #59c033;" data-mce-style="background: #59c033;">' + item + '</span> '));
						});
						wpgc_set_listeners();
					});
					jQuery(".wpgc-redundant-highlight").click(function() {
						wpgcx_clear_results();
						redundant_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(' ' + item + ' ', " &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt; " + item + "&lt;/span&gt;"));
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(' ' + item + ' ', ' <span class="hiddenSpellError wpgc-redundant" style="background: #59c033;" data-mce-style="background: #59c033;">' + item + '</span> '));
						});
						wpgc_set_listeners();
					});
					jQuery(".wpgc-possessive-highlight").click(function() {
						wpgcx_clear_results();
						possessive_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(' ' + item + ' ', " &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" + item + "&lt;/span&gt;"));
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(' ' + item + ' ', '<span class="hiddenSpellError wpgc-possessive" style="background: #59c033;" data-mce-style="background: #59c033;">' + item + '</span> '));
						});
						wpgc_set_listeners();
					});
					jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").click(function(e) {
						
						if(!jQuery(e.target).closest('.hiddenSpellError').length) {
							jQuery(".wpgc-dialog").css( "display", "none");
						}
					});
				}, 3000);
			});
			jQuery(document).click(function(e) {
				
				if(!jQuery(e.target).closest('.wpgc-dialog').length) {
					jQuery(".wpgc-dialog").css( "display", "none");
				}
			});
			
			function wpgc_strip_html(html) {
				var tmp = document.createElement("DIV");
				tmp.innerHTML = html;
				return tmp.textContent || tmp.innerText || "";
			}
			
			function wpgcx_clear_results() {
				var complex_highlight = <?php echo json_encode( $complex_expression_highlight ); ?>;
				var contractions_highlight = <?php echo json_encode( $contractions_highlight ); ?>;
				var grammar_highlight = <?php echo json_encode( $grammar_highlight ); ?>;
				var hidden_highlight = <?php echo json_encode( $hidden_verb_highlight ); ?>;
				var passive_highlight = <?php echo json_encode( $passive_voice_highlight ); ?>;
				var possessive_highlight = <?php echo json_encode( $possessive_ending_highlight ); ?>;
				var redundant_highlight = <?php echo json_encode( $redundant_expression_highlight ); ?>;
				//var dup_spaces = jQuery(".wp-editor-area#content").html().match(/<span class='hiddenSpellError wpgc-duplicate' style='border-bottom: 2px solid #59c033;'>(((&amp;nbsp;) (&amp;nbsp;))+|\s\s+)<\/span>/g);
				complex_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(" &lt;span class='hiddenSpellError wpgc-complex' style='background: #59c033;'&gt;" + item + "&lt;/span&gt; ", " " + item + " "));
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(' <span class="hiddenSpellError wpgc-complex" style="background: #a3c5ff;" data-mce-style="background: #a3c5ff;">' + item + '</span> ', " " + item + " "));
						});
						
						contractions_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(" &lt;span class='hiddenSpellError wpgc-contraction' style='background: #59c033;'&gt;" + item + "&lt;/span&gt; ", " " + item + " "));
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(' <span class="hiddenSpellError wpgc-contraction" style="background: #59c033;" data-mce-style="background: #59c033;">' + item + '</span> ', " " + item + " "));
						});
						
						grammar_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(" &lt;span class='hiddenSpellError wpgc-grammar' style='background: #59c033;'&gt;" + item + "&lt;/span&gt; ", " " + item + " "));
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(' <span class="hiddenSpellError wpgc-grammar" style="background: #59c033;" data-mce-style="background: #59c033;">' + item + '</span> ', " " + item + " "));
						});
						
						hidden_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(" &lt;span class='hiddenSpellError wpgc-hidden' style='background: #59c033;'&gt;" + item + "&lt;/span&gt; ", " " + item + " "));
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(' <span class="hiddenSpellError wpgc-hidden" style="background: #59c033;" data-mce-style="background: #59c033;">' + item + '</span> ', " " + item + " "));
						});
						
						passive_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(" &lt;span class='hiddenSpellError wpgc-passive' style='background: #59c033;'&gt;" + item + "&lt;/span&gt; ", " " + item + " "));
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(' <span class="hiddenSpellError wpgc-passive" style="background: #59c033;" data-mce-style="background: #59c033;">' + item + '</span> ', " " + item + " "));
						});
						
						redundant_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" + item + "&lt;/span&gt; ", " " + item + " "));
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(' <span class="hiddenSpellError wpgc-redundant" style="background: #59c033;" data-mce-style="background: #59c033;">' + item + '</span> ', " " + item + " "));
						});
						
						possessive_highlight.forEach(function (item) {
							jQuery(".wp-editor-area#content").html(jQuery(".wp-editor-area#content").html().replace(" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" + item + "&lt;/span&gt; ", " " + item + " "));
							jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html(jQuery("#content_ifr, #excerpt_ifr").contents().find("#tinymce").html().replace(' <span class="hiddenSpellError wpgc-possessive" style="background: #59c033;" data-mce-style="background: #59c033;">' + item + '</span> ', " " + item + " "));
						});
			}
			
			function wpgc_set_listeners() {
				window.setTimeout(function(){ 
				var iframe = jQuery("#content_ifr, #excerpt_ifr").contents();
				var suggestions = <?php echo json_encode( $suggest_list_full ); ?>;
					
				iframe.find('.hiddenSpellError').click(function(e) {
                                    console.log("WPSC-Click");
					//if (jQuery(this).css("background") == "inherit") return;
							word_to_edit = jQuery(this);
							word_check = jQuery(this).html();
							var error_class = jQuery(this).attr("class");
							error_class = error_class.split(" ")[1].split("-")[1];
							if (error_class == "complex") error_class = "Complex Expression";
							if (error_class == "passive") error_class = "Passive Voice";
							if (error_class == "redundant") error_class = "Redundant Expression";
							if (error_class == "grammar") error_class = "Grammar";
							if (error_class == "hidden") error_class = "Hidden Verb";
							if (error_class == "possessive") error_class = "Possessive Ending";
							if (error_class == "contraction") error_class = "Contraction";
							html_to_add = "<li style='color: grey;'>" + error_class + "</li><hr>";
							
							if (suggestions.length > 1) {
							suggestions.forEach(function(suggestion) {
								if (word_check == suggestion[0]) {
									if (suggestion[1] != null) {
										if (suggestion[1].includes(",")) {
											suggest_split = suggestion[1].split(",");
											suggest_split.forEach(function(split_word) {
												html_to_add += "<li><a href='#_' class='wpgc-suggestion'>" + split_word + "</a></li>";
											});
										} else if (suggestion[1].includes("/")) {
											suggest_split = suggestion[1].split("/");
											suggest_split.forEach(function(split_word) {
												html_to_add += "<li><a href='#_' class='wpgc-suggestion'>" + split_word + "</a></li>";
											});
										} else {
											html_to_add += "<li><a href='#_' class='wpgc-suggestion'>" + suggestion[1] + "</a></li>";
										}
									}
								}
							});
						}
							html_to_add += "<hr><li><a href='#_' class='wpgc-dialog-close'>Ignore</a></li>";
							
							jQuery(".wpgc-dialog ul").html(html_to_add);
							jQuery(".wpgc-dialog").css( "top", e.pageY + 200);
							jQuery(".wpgc-dialog").css( "left", e.pageX);
							jQuery(".wpgc-dialog").css( "display", "block");
							jQuery('.wpgc-dialog-close').click(function() {
								//var scroll_save = jQuery(window).scrollTop();							
								word_to_edit.css("background","inherit");
								jQuery(".wpgc-dialog").css( "display", "none");
								//jQuery(window).scrollTop(scroll_save);
							});
							
							jQuery(".wpgc-suggestion").click(function() {
								if (jQuery(this).html() == "DELETE") {
									word_to_edit.html("");
								} else {
									word_to_edit.html(jQuery(this).html());
								}
								word_to_edit.css("background","inherit");
								jQuery(".wpgc-dialog").css( "display", "none");
							});
							
							jQuery(".wpgc-dialog a").hover(function() {
								jQuery(this).css("background-color","grey");
							}, function() {
								jQuery(this).css("background-color","lightgrey");
							});
						});
					},250);
			}
		</script>
	<?php
}
add_action( 'edit_form_after_editor', 'wpgcx_highlight_errors' );

function wpgcx_publish_box() {
    $post_id = '';
        if (isset($_GET['post'])) { $post_id = sanitize_text_field( $_GET['post'] ); }
	if ( '' !== $post_id ) {
		global $wpdb;
		$results_table = $wpdb->prefix . 'spellcheck_grammar';
		$spell_table   = $wpdb->prefix . 'spellcheck_words';

		//if (isset($_GET['wpgc-scan-page'])) { if ($_GET['wpgc-scan-page'] == "Gramme Check") wpgcx_scan_individual($post_id); }

		$score       = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $results_table WHERE page_id=%d", $post_id ) );
		$spell_score = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $spell_table WHERE page_id=%d AND ignore_word=0 AND (page_type='Page Content' or page_type='Post Content')", $post_id ) );

		if ( isset( $_GET['wpgc-scan-page'] ) ) {
			if ( 'Spell Check' === $_GET['wpgc-scan-page'] ) {
				$spellcheck_scanner = new Wpscx_Spellcheck_Scanner();
				$spell_score        = $spellcheck_scanner->scan_single( $post_id ); }
		}
		if ( sizeof( (array) $score ) > 0 || sizeof( (array) $spell_score ) > 0 ) {
			?>
			<div>
			<div class="wpsc-editor-message"></div>
                        <div><span style="background: #59c033; display: inline-block; width: 10px; height: 10px; margin-right: 5px; border-radius: 15px;"></span><strong>Grammar Errors:</strong> <?php if (isset($score[0])) { echo esc_html( $score[0]->grammar ); } else { echo "0"; } ?></div>
			<div><span style="background: #FFC0C0; display: inline-block; width: 10px; height: 10px; margin-right: 5px; border-radius: 15px;"></span><strong>Spelling Errors:</strong> <?php echo esc_html( count( $spell_score ) ); ?></div>
			</div>
			<?php if ( isset( $_GET['wpgc-scan-page'] ) && 'Gramme Check' === $_GET['wpgc-scan-page'] ) { ?>
			<div style="color: #59c033;">Grammar check completed on this page</div>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					//jQuery('.wpgc-grammar-highlight').click();
				});
			</script>
			<?php } ?>
			<?php if ( isset( $_GET['wpgc-scan-page'] ) && 'Spell Check' === $_GET['wpgc-scan-page'] ) { ?>
			<div style="color: #59c033;">Spelling check completed on this page</div>
			<script type="text/javascript">
				jQuery(document).ready(function() {
//					jQuery('.wpgc-spelling-highlight').click();
				});
			</script>
			<?php } ?>
			<div style="width: 49%; display: inline-block; margin-top: 10px;">
				<input type="button" value="Spell Check" class="wpgc-scan-page wp-media-buttons button"  style="width: 100%; padding: 0px; background-color: #f72d2d; border-color: #f72d2d; color: white;" />
				<input type="button" value="Highlight Spelling" class="wpgc-spelling-highlight wp-media-buttons button" style="margin-top: 5px; width: 100%; padding: 0px; background-color: #f72d2d; border-color: #f72d2d; color: white;" />
			</div>
			<div style="width: 49%; display: inline-block;">
				<input type="button" value="Grammar Check" class="wpgc-scan-page-grammar wp-media-buttons button"  style="width: 100%; padding: 0px; background-color: #59c033; border-color: #59c033; color: white;" />
				<input type="button" value="Highlight Grammar" class="wpgc-grammar-highlight wp-media-buttons button"  style="margin-top: 5px; width: 100%; padding: 0px; background-color: #59c033; border-color: #59c033; color: white;" />
			</div>
			<?php
		} else {
			?>
			<div>
			<div><strong>Click on the buttons below to proofread your page</strong></div>
			</div>
			<div style="width: 49%; display: inline-block; margin-top: 10px;">
				<input type="button" value="Spell Check" class="wpgc-scan-page wp-media-buttons button"  style="width: 100%; padding: 0px; background-color: #f72d2d; border-color: #f72d2d; color: white;" />
				<input type="button" value="Highlight Spelling" class="wpgc-spelling-highlight wp-media-buttons button" style="margin-top: 5px; width: 100%; padding: 0px; background-color: #f72d2d; border-color: #f72d2d; color: white;" />
			</div>
			<div style="width: 49%; display: inline-block;">
				<input type="button" value="Grammar Check" class="wpgc-scan-page-grammar wp-media-buttons button"  style="width: 100%; padding: 0px; background-color: #59c033; border-color: #59c033; color: white;" />
				<input type="button" value="Highlight Grammar" class="wpgc-grammar-highlight wp-media-buttons button"  style="margin-top: 5px; width: 100%; padding: 0px; background-color: #59c033; border-color: #59c033; color: white;" />
			</div>
			<?php
		}
		?>
		<hr style="width: 279.217px; margin-left: -10px;">
		<?php
	} else {
		?>
		<div>
		<div><strong>Click on the buttons below to proofread your page</strong></div>
		</div>
		<div style="width: 49%; display: inline-block; margin-top: 10px;">
			<input type="button" value="Spell Check" class="wpgc-scan-page wp-media-buttons button"  style="width: 100%; padding: 0px; background-color: #f72d2d; border-color: #f72d2d; color: white;" />
			<input type="button" value="Highlight Spelling" class="wpgc-spelling-highlight wp-media-buttons button" style="margin-top: 5px; width: 100%; padding: 0px; background-color: #f72d2d; border-color: #f72d2d; color: white;" />
		</div>
		<div style="width: 49%; display: inline-block;">
			<input type="button" value="Grammar Check" class="wpgc-scan-page-grammar wp-media-buttons button"  style="width: 100%; padding: 0px; background-color: #59c033; border-color: #59c033; color: white;" />
			<input type="button" value="Highlight Grammar" class="wpgc-grammar-highlight wp-media-buttons button"  style="margin-top: 5px; width: 100%; padding: 0px; background-color: #59c033; border-color: #59c033; color: white;" />
		</div>
		<?php
	}
}
add_action( 'post_submitbox_start', 'wpgcx_publish_box' );

function wpgcx_clear_scan() {
		global $wpdb;
		global $wpsc_settings;
		$options_table = $wpdb->prefix . 'spellcheck_grammar_options';
		$settings      = $wpsc_settings;

		$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'page_running' ) );
		$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'post_running' ) );
}

function wpgcx_check_scan_progress() {
	global $wpdb;
	$options_table = $wpdb->prefix . 'spellcheck_grammar_options';

	$check_page = $wpdb->get_results( "SELECT * FROM $options_table WHERE option_name = 'page_running'" );
	$check_post = $wpdb->get_results( "SELECT * FROM $options_table WHERE option_name = 'post_running'" );

	$scan_in_progress = false;

	if ( 'true' === $check_page[0]->option_value || 'true' === $check_post[0]->option_value ) {
		$scan_finished = true;
	}

	return $scan_in_progress;
}


function wpgcx_scan_function() {
	global $wpdb;
	$options_table = $wpdb->prefix . 'spellcheck_grammar_options';

	$scan_finished = false;

	$check_page = $wpdb->get_results( "SELECT * FROM $options_table WHERE option_name = 'page_running'" );
	$check_post = $wpdb->get_results( "SELECT * FROM $options_table WHERE option_name = 'post_running'" );

	if ( 'true' === $check_page[0]->option_value || 'true' === $check_post[0]->option_value ) {
		$scan_finished = true;
	}

	if ( $scan_finished ) {
		echo 'true';
	} else {
		echo 'false';
	}

	die();
}

function wpgcx_finish_scan() {
	$start = round( microtime( true ), 5 );
	global $wpdb;
	global $wpscx_ent_included;
	$options_table = $wpdb->prefix . 'spellcheck_grammar_options';
	$sql_count     = 0;

	$settings = $wpdb->get_results( 'SELECT option_value FROM ' . $options_table );
	if ( 'Entire Site' !== $settings[7]->option_value ) {
		return false;
	}

	$time = $wpdb->get_results( "SELECT * FROM $options_table WHERE option_name='scan_start_time'" );
	$sql_count++;
	$time = $time[0]->option_value;

	$end_time = time();

	$total_time = wpscx_time_elapsed( $end_time - $time );
	$wpdb->update( $options_table, array( 'option_value' => $total_time ), array( 'option_name' => 'last_scan_time' ) );
	$sql_count++;

	if ( $wpscx_ent_included ) {
		$end        = round( microtime( true ), 5 );
		$total_time = round( $end - $start, 5 );
		wpscx_print_debug_end( '9.21 Grammar Check Pro', $total_time );
	} else {
		$end        = round( microtime( true ), 5 );
		$total_time = round( $end - $start, 5 );
		wpscx_print_debug_end( '9.21 Grammar Check Base', $total_time );
	}
}

add_action( 'wp_ajax_results_gc', 'wpgcx_scan_function' );
add_action( 'wp_ajax_nopriv_results_gc', 'wpgcx_scan_function' );
add_action( 'wp_ajax_finish_scan_gc', 'wpgcx_finish_scan' );
add_action( 'wp_ajax_nopriv_finish_scan_gc', 'wpgcx_finish_scan' );
?>
