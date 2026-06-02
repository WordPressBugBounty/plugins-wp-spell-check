<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read file contents using WP_Filesystem for consistency with WordPress APIs.
 * Falls back to file_get_contents() if WP_Filesystem is unavailable or read fails.
 *
 * @param string $path Absolute path to the file.
 * @return string File contents, or empty string on failure.
 * @since 1.0.0
 */
function wpgcx_read_file_contents( $path ) {
	global $wp_filesystem;
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	if ( WP_Filesystem() && is_object( $wp_filesystem ) && method_exists( $wp_filesystem, 'get_contents' ) ) {
		$contents = $wp_filesystem->get_contents( $path );
		if ( false !== $contents ) {
			return $contents;
		}
	}
	$fallback = file_get_contents( $path );
	return false !== $fallback ? $fallback : '';
}

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

function wpgcx_enqueue_grammar_framework_styles() {
	global $wpsc_version;
	$screen = get_current_screen();
	// Only load on post edit pages (post.php or post-new.php)
	if ( $screen && 'post' === $screen->base ) {
		wp_enqueue_style(
			'wpgc-grammar-framework',
			plugin_dir_url( __DIR__ ) . 'css/grammar-framework.css',
			array(),
			$wpsc_version
		);
	}
}
add_action( 'admin_enqueue_scripts', 'wpgcx_enqueue_grammar_framework_styles', 5 );

/**
 * Prepare grammar framework data for JavaScript.
 * Extracted from wpgcx_highlight_errors() to enable early data preparation.
 *
 * @return array Prepared data array for JavaScript.
 */
function wpgcx_prepare_grammar_data() {
	static $grammar_data = null;

	// Return cached data if available.
	if ( null !== $grammar_data ) {
		return $grammar_data;
	}

	$post_id = '';
	if ( isset( $_GET['post'] ) ) {
		$post_id = sanitize_text_field( wp_unslash( $_GET['post'] ) );
	}

	global $wpdb;
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

	if ( isset( $_GET['wpgc-scan-page'] ) ) {
		if ( 'Spell Check' === $_GET['wpgc-scan-page'] ) {
			$spellcheck = true;
		}
	}

	$spelling_highlight = array();
	if ( '' !== $post_id ) {
		$wpsc_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $words_table WHERE page_id = %d", $post_id ) );
	} else {
		$wpsc_data = array();
	}
	if ( $spellcheck ) {
		$spellcheck_scanner = new Wpscx_Spellcheck_Scanner();
		$wpsc_data          = $spellcheck_scanner->scan_single( $post_id );
	}

	$word_list = array();
	foreach ( $dict_words as $dict_word ) {
		array_push( $word_list, $dict_word->word );
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'spellcheck_options'. Query contains no user input (hardcoded WHERE clause).
	$language_setting = $wpdb->get_results( 'SELECT option_value from ' . $table_name . ' WHERE option_name="language_setting";' );

	$loc      = __DIR__ . '/../dict/' . $language_setting[0]->option_value . '.pws';
	$contents = wpgcx_read_file_contents( $loc );

	$contents  = str_replace( "\r\n", "\n", $contents );
	$main_list = explode( "\n", $contents );

	$word_list = array_merge( $word_list, $main_list );

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
						++$suggestions;
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
							++$suggestions;
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
						++$suggestions;
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
							++$suggestions;
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
		$loc                     = __DIR__ . '/complex_expression.pws';
		$contents                = wpgcx_read_file_contents( $loc );
		$contents                = str_replace( "\r\n", "\n", $contents );
		$complex_expression_list = explode( "\n", $contents );

		$loc               = __DIR__ . '/contractions.pws';
		$contents          = wpgcx_read_file_contents( $loc );
		$contents          = str_replace( "\r\n", "\n", $contents );
		$contractions_list = explode( "\n", $contents );

		$loc          = __DIR__ . '/grammar.pws';
		$contents     = wpgcx_read_file_contents( $loc );
		$contents     = str_replace( "\r\n", "\n", $contents );
		$grammar_list = explode( "\n", $contents );

		$loc              = __DIR__ . '/hidden_verb.pws';
		$contents         = wpgcx_read_file_contents( $loc );
		$contents         = str_replace( "\r\n", "\n", $contents );
		$hidden_verb_list = explode( "\n", $contents );

		$loc                = __DIR__ . '/passive_voice.pws';
		$contents           = wpgcx_read_file_contents( $loc );
		$contents           = str_replace( "\r\n", "\n", $contents );
		$passive_voice_list = explode( "\n", $contents );

		$loc                    = __DIR__ . '/possessive_ending.pws';
		$contents               = wpgcx_read_file_contents( $loc );
		$contents               = str_replace( "\r\n", "\n", $contents );
		$possessive_ending_list = explode( "\n", $contents );

		$loc                       = __DIR__ . '/redundant_expression.pws';
		$contents                  = wpgcx_read_file_contents( $loc );
		$contents                  = str_replace( "\r\n", "\n", $contents );
		$redundant_expression_list = explode( "\n", $contents );

		$loc             = __DIR__ . '/suggestions.pws';
		$contents        = wpgcx_read_file_contents( $loc );
		$contents        = str_replace( "\r\n", "\n", $contents );
		$suggestion_list = explode( "\n", $contents );

		foreach ( $suggestion_list as $suggestion_line ) {
			$suggestion = explode( ':', $suggestion_line );
			if ( ! isset( $suggestion[1] ) ) {
				$suggestion[1] = '';
			}
			array_push( $suggest_list_full, array( $suggestion[0], $suggestion[1] ) );
		}

		// Get post content for spacing highlight processing.
		if ( isset( $_GET['post'] ) ) {
			$post = get_post( sanitize_text_field( wp_unslash( $_GET['post'] ) ) );
			if ( $post ) {
				$words_content = $post->post_content;
				$words_content = preg_replace( '/&lt;/', '<', $words_content );
				$words_content = preg_replace( '/&gt;/', '>', $words_content );
				$words_content = do_shortcode( $words_content );
				$words_content = preg_replace( '@<style[^>]*?>.*?</style>@siu', ' ', $words_content );
				$words_content = preg_replace( '@<script[^>]*?>.*?</script>@siu', ' ', $words_content );
				$words_content = preg_replace( '/(\<.*?\>)/', ' ', $words_content );
				$words_content = preg_replace( '/<iframe.+<\/iframe>/', ' ', $words_content );
				$words_content = html_entity_decode( wp_strip_all_tags( $words_content ), ENT_QUOTES, 'utf-8' );
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
		}
	}

	$divi_check = wp_get_theme();

	// Build URL for scan page buttons.
	$scan_page_url = '';
	if ( isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
		$scan_page_url = esc_url_raw( wp_unslash( $_SERVER['HTTP_HOST'] ) . wp_unslash( $_SERVER['REQUEST_URI'] ) );
	}

	$grammar_data = array(
		'spellingHighlight'            => $spelling_highlight,
		'complexHighlight'             => $complex_expression_list,
		'contractionsHighlight'        => $contractions_list,
		'grammarHighlight'             => $grammar_list,
		'hiddenHighlight'              => $hidden_verb_list,
		'passiveHighlight'             => $passive_voice_list,
		'possessiveHighlight'          => $possessive_ending_list,
		'redundantHighlight'           => $redundant_expression_list,
		'suggestions'                  => $suggest_list_full,
		'spellcheck'                   => $spellcheck,
		'builderCheck'                 => $divi_check->name,
		'complexExpressionHighlight'   => $complex_expression_highlight,
		'contractionsHighlightArray'   => $contractions_highlight,
		'grammarHighlightArray'        => $grammar_highlight,
		'hiddenVerbHighlight'          => $hidden_verb_highlight,
		'passiveVoiceHighlight'        => $passive_voice_highlight,
		'possessiveEndingHighlight'    => $possessive_ending_highlight,
		'redundantExpressionHighlight' => $redundant_expression_highlight,
		'scanPageUrl'                  => $scan_page_url,
	);

	return $grammar_data;
}

/**
 * Enqueue grammar framework JavaScript and localize script data.
 */
function wpgcx_enqueue_grammar_framework_scripts() {
	global $wpsc_version;
	$screen = get_current_screen();
	// Only load on post edit pages (post.php or post-new.php).
	if ( $screen && 'post' === $screen->base ) {
		wp_enqueue_script(
			'wpgc-grammar-framework',
			plugin_dir_url( __DIR__ ) . 'js/grammar-framework.js',
			array( 'jquery' ),
			$wpsc_version,
			true
		);

		$grammar_data = wpgcx_prepare_grammar_data();
		wp_localize_script(
			'wpgc-grammar-framework',
			'wpgcGrammarFramework',
			$grammar_data
		);
	}
}
add_action( 'admin_enqueue_scripts', 'wpgcx_enqueue_grammar_framework_scripts', 5 );

function wpgcx_check_pages() {
	$scanner = new Wpscx_Grammar_Scanner();

		$scanner->check_pages();
}
add_action( 'wpgcx_check_pages', 'wpgcx_check_pages' );

function wpgcx_check_posts() {
	$scanner = new Wpscx_Grammar_Scanner();

		$scanner->check_posts();
}
add_action( 'wpgcx_check_posts', 'wpgcx_check_posts' );

function wpgcx_scan_individual( $page_id ) {
	$scanner = new Wpscx_Grammar_Scanner();

		$scanner->scan_individual( $page_id );
}

function wpgcx_scan_site() {
	$scanner = new Wpscx_Grammar_Scanner();

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
	// add_meta_box( 'wpgc_meta_box', 'WP Spell Check', 'wpsc_create_meta_box',  array('post','page'), 'advanced', 'high' );
}
// add_action( 'add_meta_boxes', 'wpgcx_register_meta_boxes' );

function wpsc_create_meta_box( $post ) {
	?>
	<!--<div class="wpsc-modal-box"></div>
	<?php
	if ( isset( $_GET['wpsc-scan-page'] ) && 1 === $_GET['wpsc-scan-page'] ) {
				$spellcheck = new Wpscx_Spellcheck_Scanner();
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
		$id    = sanitize_text_field( wp_unslash( $_GET['post'] ) );

		$wpsc_data = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . esc_sql( $table ) . ' WHERE ignore_word is false AND page_id=%d', $id ) );

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

/**
 * Output dialog HTML for grammar framework.
 * Data preparation moved to wpgcx_prepare_grammar_data().
 * JavaScript moved to admin/js/grammar-framework.js.
 *
 * @param WP_Post $post The post object.
 */
function wpgcx_highlight_errors( $post ) {
	$post_id = '';
	if ( isset( $_GET['post'] ) ) {
		$post_id = sanitize_text_field( wp_unslash( $_GET['post'] ) );
	}

	// Trigger grammar scan if requested.
	if ( isset( $_GET['wpgc-scan-page'] ) && 'Gramme Check' === $_GET['wpgc-scan-page'] ) {
		wpgcx_scan_individual( $post_id );
	}

	// Output dialog HTML only.
	?>
	<div class="wpgc-dialog" style="display:none;">
		<ul>
		</ul>
	</div>
	<?php
}
add_action( 'edit_form_after_editor', 'wpgcx_highlight_errors' );

function wpgcx_publish_box() {
	$post_id = '';
	if ( isset( $_GET['post'] ) ) {
		$post_id = sanitize_text_field( wp_unslash( $_GET['post'] ) ); }
	if ( '' !== $post_id ) {
		global $wpdb;
		$results_table = $wpdb->prefix . 'spellcheck_grammar';
		$spell_table   = $wpdb->prefix . 'spellcheck_words';

		// if (isset($_GET['wpgc-scan-page'])) { if ($_GET['wpgc-scan-page'] == "Gramme Check") wpgcx_scan_individual($post_id); }

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
						<div><span style="background: #59c033; display: inline-block; width: 10px; height: 10px; margin-right: 5px; border-radius: 15px;"></span><strong>Grammar Errors:</strong> 
						<?php
						if ( isset( $score[0] ) ) {
							echo esc_html( $score[0]->grammar );
						} else {
							echo '0'; }
						?>
																																																	</div>
			<div><span class="wpgc-indicator-spelling"></span><strong>Spelling Errors:</strong> <?php echo esc_html( count( $spell_score ) ); ?></div>
			</div>
			<?php if ( isset( $_GET['wpgc-scan-page'] ) && 'Gramme Check' === $_GET['wpgc-scan-page'] ) { ?>
			<div style="color: #59c033;">Grammar check completed on this page</div>
			<?php } ?>
			<?php if ( isset( $_GET['wpgc-scan-page'] ) && 'Spell Check' === $_GET['wpgc-scan-page'] ) { ?>
			<div class="wpgc-success-message">Spelling check completed on this page</div>
			<?php } ?>
			<div class="wpgc-button-container-first">
				<input type="button" value="Spell Check" class="wpgc-scan-page wp-media-buttons button wpgc-btn-spell-check" />
				<input type="button" value="Highlight Spelling" class="wpgc-spelling-highlight wp-media-buttons button wpgc-btn-highlight-spelling" />
			</div>
			<div class="wpgc-button-container">
				<input type="button" value="Grammar Check" class="wpgc-scan-page-grammar wp-media-buttons button wpgc-btn-grammar-check" />
				<input type="button" value="Highlight Grammar" class="wpgc-grammar-highlight wp-media-buttons button wpgc-btn-highlight-grammar" />
			</div>
			<?php
		} else {
			?>
			<div>
			<div><strong>Click on the buttons below to proofread your page</strong></div>
			</div>
			<div class="wpgc-button-container-first">
				<input type="button" value="Spell Check" class="wpgc-scan-page wp-media-buttons button wpgc-btn-spell-check" />
				<input type="button" value="Highlight Spelling" class="wpgc-spelling-highlight wp-media-buttons button wpgc-btn-highlight-spelling" />
			</div>
			<div class="wpgc-button-container">
				<input type="button" value="Grammar Check" class="wpgc-scan-page-grammar wp-media-buttons button wpgc-btn-grammar-check" />
				<input type="button" value="Highlight Grammar" class="wpgc-grammar-highlight wp-media-buttons button wpgc-btn-highlight-grammar" />
			</div>
			<?php
		}
		?>
		<hr class="wpgc-separator">
		<?php
	} else {
		?>
		<div>
		<div><strong>Click on the buttons below to proofread your page</strong></div>
		</div>
		<div class="wpgc-button-container-first">
			<input type="button" value="Spell Check" class="wpgc-scan-page wp-media-buttons button wpgc-btn-spell-check" />
			<input type="button" value="Highlight Spelling" class="wpgc-spelling-highlight wp-media-buttons button wpgc-btn-highlight-spelling" />
		</div>
		<div class="wpgc-button-container">
			<input type="button" value="Grammar Check" class="wpgc-scan-page-grammar wp-media-buttons button wpgc-btn-grammar-check" />
			<input type="button" value="Highlight Grammar" class="wpgc-grammar-highlight wp-media-buttons button wpgc-btn-highlight-grammar" />
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
	check_ajax_referer( 'wpgc_scan', 'nonce' );
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
	check_ajax_referer( 'wpgc_finish_scan', 'nonce' );
	$start = round( microtime( true ), 5 );
	global $wpdb;
	global $wpscx_ent_included;
	global $wpsc_version;
	$options_table = $wpdb->prefix . 'spellcheck_grammar_options';
	$sql_count     = 0;

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'spellcheck_grammar_options'. Query contains no user input.
	$settings = $wpdb->get_results( 'SELECT option_value FROM ' . $options_table );
	if ( 'Entire Site' !== $settings[7]->option_value ) {
		return false;
	}

	$time = $wpdb->get_results( "SELECT * FROM $options_table WHERE option_name='scan_start_time'" );
	++$sql_count;
	$time = $time[0]->option_value;

	$end_time = time();

	$total_time = wpscx_time_elapsed( $end_time - $time );
	$wpdb->update( $options_table, array( 'option_value' => $total_time ), array( 'option_name' => 'last_scan_time' ) );
	++$sql_count;

	if ( $wpscx_ent_included ) {
		$end        = round( microtime( true ), 5 );
		$total_time = round( $end - $start, 5 );
		wpscx_print_debug_end( $wpsc_version . ' Grammar Check Pro', $total_time );
	} else {
		$end        = round( microtime( true ), 5 );
		$total_time = round( $end - $start, 5 );
		wpscx_print_debug_end( $wpsc_version . ' Grammar Check Base', $total_time );
	}
}

add_action( 'wp_ajax_results_gc', 'wpgcx_scan_function' );
add_action( 'wp_ajax_finish_scan_gc', 'wpgcx_finish_scan' );
?>
