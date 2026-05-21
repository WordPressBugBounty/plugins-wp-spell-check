<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================================
// DEBUG LOG
// ============================================================================

define( 'WPSCX_DEBUG_LOGGING_ENABLED', false );
// ============================================================================
// END DEBUG LOGGING SETTINGS
// ============================================================================

/**
 * Check if a path is writable using WP_Filesystem API.
 * Used for debug log directory checks to satisfy WordPress coding standards.
 *
 * @param string $path Full path to file or directory.
 * @return bool True if writable, false otherwise.
 * @since 1.0.0
 */
function wpscx_path_is_writable( $path ) {
	global $wp_filesystem;
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	if ( ! WP_Filesystem() || ! is_object( $wp_filesystem ) || ! method_exists( $wp_filesystem, 'is_writable' ) ) {
		return false;
	}
	return $wp_filesystem->is_writable( $path );
}

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
/* WP Spell Check classes */

/* Main WP Spell Check Functions */



/**
 * Get the debug log file path
 * Creates the log directory if it doesn't exist
 * Returns false if debug logging is disabled
 *
 * @return string|false Full path to debug.log file, or false if logging disabled
 */
function wpscx_get_debug_log_path() {
	// Check if debug logging is enabled
	if ( ! defined( 'WPSCX_DEBUG_LOGGING_ENABLED' ) || ! WPSCX_DEBUG_LOGGING_ENABLED ) {
		return false;
	}

	// Get WordPress uploads directory and create log subdirectory
	$upload_dir = wp_upload_dir();
	$log_dir    = $upload_dir['basedir'] . '/wp-spell-check-logs';

	// Create directory if it doesn't exist
	if ( ! file_exists( $log_dir ) ) {
		wp_mkdir_p( $log_dir );
	}

	// Only return path if directory exists and is writable (avoids file_put_contents "Failed to open stream" warnings).
	if ( ! is_dir( $log_dir ) || ! wpscx_path_is_writable( $log_dir ) ) {
		return false;
	}

	return $log_dir . '/debug.log';
}

/**
 * Snapshot $wpdb->num_queries at the start of a timed scan step.
 *
 * @since 11.2
 * @return int Query counter value.
 */
function wpscx_debug_queries_at_start() {
	global $wpdb;
	return isset( $wpdb->num_queries ) ? (int) $wpdb->num_queries : 0;
}

/**
 * Queries executed since wpscx_debug_queries_at_start().
 *
 * @since 11.2
 * @param int $query_start Value from wpscx_debug_queries_at_start().
 * @return int
 */
function wpscx_debug_queries_since( $query_start ) {
	global $wpdb;
	return max( 0, (int) $wpdb->num_queries - (int) $query_start );
}

/**
 * Print debug information to log file
 * Only logs if WPSCX_DEBUG_LOGGING_ENABLED is set to true
 *
 * @param string   $scan Scan type identifier
 * @param float    $time Execution time
 * @param int      $sql  SQL query count (ignored when $query_start is set)
 * @param float    $memory Memory usage in KB
 * @param mixed    $error Error count or message
 * @param int|null $query_start Optional value from wpscx_debug_queries_at_start() for accurate SQL totals.
 */
function wpscx_print_debug( $scan, $time, $sql, $memory, $error, $query_start = null ) {
	// Get log path (returns false if logging disabled or path not writable)
	$loc = wpscx_get_debug_log_path();
	if ( false === $loc ) {
		return; // Logging disabled or log dir missing/unwritable, exit early
	}

	if ( null !== $query_start ) {
		$sql = wpscx_debug_queries_since( $query_start );
	}

	global $wpsc_settings;

	// Pad scan name to fixed width (25 chars) to align Time: column.
	$scan_padded = str_pad( substr( trim( $scan ), 0, 25 ), 25, ' ', STR_PAD_RIGHT );

	// Fixed-width time/memory so tabbed SQL/Memory/Errors columns line up (avoids 9.0E-5 style drift).
	$time_field   = str_pad( 'Time: ' . sprintf( '%.5f', (float) $time ) . '.', 15, ' ', STR_PAD_RIGHT );
	$memory_field = sprintf( '%.3f', (float) $memory );

	// Format: Time → SQL → Memory → Errors → Number of Options Loaded (one tab between each field)
	// Only write if directory is writable; use WP_Filesystem for host compatibility.
	if ( wpscx_path_is_writable( dirname( $loc ) ) ) {
		global $wp_filesystem;
		if ( is_object( $wp_filesystem ) && method_exists( $wp_filesystem, 'get_contents' ) && method_exists( $wp_filesystem, 'put_contents' ) ) {
			$existing = $wp_filesystem->get_contents( $loc );
			$line     = $scan_padded . ' ' . $time_field . "\tSQL: $sql\tMemory: $memory_field KB.\tErrors: $error\tNumber of Options Loaded: " . sizeof( (array) $wpsc_settings ) . "\r\n";
			$wp_filesystem->put_contents( $loc, ( false !== $existing ? $existing : '' ) . $line );
		}
	}
	// file_put_contents( $loc, "Length: $length \r\n", FILE_APPEND );
}

/**
 * Print debug end marker to log file
 * Only logs if WPSCX_DEBUG_LOGGING_ENABLED is set to true
 *
 * @param string $scan_type Type of scan being completed
 * @param int    $total_time Total execution time (unused but kept for compatibility)
 */
function wpscx_print_debug_end( $scan_type, $total_time = 0 ) {
	// Get log path (returns false if logging disabled or path not writable)
	$loc = wpscx_get_debug_log_path();
	if ( false === $loc ) {
		return; // Logging disabled or log dir missing/unwritable, exit early
	}

	// Only write if directory is writable; use WP_Filesystem for host compatibility.
	if ( wpscx_path_is_writable( dirname( $loc ) ) ) {
		global $wp_filesystem;
		if ( is_object( $wp_filesystem ) && method_exists( $wp_filesystem, 'get_contents' ) && method_exists( $wp_filesystem, 'put_contents' ) ) {
			$existing = $wp_filesystem->get_contents( $loc );
			$line     = "-------------------------$scan_type | " . gmdate( 'd-M-Y H:i:s', current_time( 'timestamp', 0 ) ) . "------------------------------\r\n\r\n\r\n";
			$wp_filesystem->put_contents( $loc, ( false !== $existing ? $existing : '' ) . $line );
		}
	}
}

function wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) {
	if ( '' === $word || preg_match( '/^[^a-zA-ZÀÂÆÈÉÊËÎÏÔŒÙÛÜŸÁÉÍÑÓÚÜ]+$/', $word ) || ! wpscx_ignore_caps( $wpsc_settings, $word ) ) {
		return false;
	}
	if ( isset( $wpsc_haystack[ strtoupper( $word ) ] ) ) {
		if ( 1 === $wpsc_haystack[ strtoupper( $word ) ] ) {
			return false;
		}
	}

	return true;
}

function wpscx_check_empty( $field, $is_haystack = false ) {
	if ( $is_haystack ) {
		return ( 'true' !== $field );
	} else {
		return ( $field == '' );
	}
}

/**
 * Meta Slider admin edit URL. Slideshow CPT has show_ui false; use metaslider admin page.
 * For slide rows, resolve parent slideshow ID via ml-slider taxonomy (term name = slideshow post ID).
 *
 * @param int    $post_id   page_id from scan results (slideshow or slide post ID).
 * @param string $page_type Meta Slider page_type string.
 * @return string Admin URL to the slideshow editor.
 */
function wpscx_metaslider_admin_url( $post_id, $page_type = '' ) {
	$slideshow_id = (int) $post_id;

	if ( 'Meta Slider Group' !== $page_type ) {
		$post_type = get_post_type( $post_id );
		if ( 'ml-slider' !== $post_type ) {
			$terms = wp_get_object_terms( (int) $post_id, 'ml-slider' );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$slideshow_id = (int) $terms[0]->name;
				if ( $slideshow_id <= 0 ) {
					$slideshow_id = (int) $terms[0]->slug;
				}
			}
		}
	}

	return admin_url( 'admin.php?page=metaslider&id=' . $slideshow_id );
}

/**
 * Smart Slider 3 admin edit URL. Slide errors open the slide editor; group errors open the slider editor.
 *
 * @param int    $record_id page_id from scan results (slider or slide ID).
 * @param string $page_type Smart Slider page_type string.
 * @return string Admin URL.
 */
function wpscx_smartslider3_admin_url( $record_id, $page_type = '' ) {
	global $wpdb;

	$record_id = (int) $record_id;
	$fallback  = admin_url( 'admin.php?page=smart-slider3' );

	if ( $record_id <= 0 ) {
		return $fallback;
	}

	$slides_table = $wpdb->prefix . 'nextend2_smartslider3_slides';
	$xref_table   = $wpdb->prefix . 'nextend2_smartslider3_sliders_xref';

	$slider_id = $record_id;
	if ( 'Smart Slider Group' !== $page_type ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name built from $wpdb->prefix + fixed suffix.
		$slider_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT slider FROM {$slides_table} WHERE id = %d LIMIT 1", $record_id ) );
		if ( $slider_id <= 0 ) {
			return $fallback;
		}
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name built from $wpdb->prefix + fixed suffix.
	$group_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT group_id FROM {$xref_table} WHERE slider_id = %d LIMIT 1", $slider_id ) );

	if ( 'Smart Slider Group' === $page_type ) {
		return add_query_arg(
			array(
				'page'              => 'smart-slider3',
				'nextendcontroller' => 'slider',
				'nextendaction'     => 'edit',
				'sliderid'          => $record_id,
				'groupID'           => $group_id,
			),
			admin_url( 'admin.php' )
		);
	}

	return add_query_arg(
		array(
			'page'              => 'smart-slider3',
			'nextendcontroller' => 'slides',
			'nextendaction'     => 'edit',
			'sliderid'          => $slider_id,
			'slideid'           => $record_id,
			'groupID'           => $group_id,
		),
		admin_url( 'admin.php' )
	);
}

/**
 * Batched postmeta for Meta Slider slide SEO/link fields (one query).
 *
 * @param int[] $slide_ids Slide post IDs.
 * @return array<int, array<string, string>> post_id => meta_key => meta_value.
 */
function wpscx_metaslider_load_slide_meta( $slide_ids ) {
	global $wpdb;

	$meta_by_slide = array();
	$slide_ids     = array_unique( array_filter( array_map( 'intval', (array) $slide_ids ) ) );
	if ( empty( $slide_ids ) ) {
		return $meta_by_slide;
	}

	$meta_keys        = array(
		'ml-slider_title',
		'ml-slider_inherit_image_title',
		'ml-slider_inherit_image_alt',
		'_wp_attachment_image_alt',
		'ml-slider_link-alt',
	);
	$id_placeholders  = implode( ',', array_fill( 0, count( $slide_ids ), '%d' ) );
	$key_placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );
	$prepare_args     = array_merge( $slide_ids, $meta_keys );
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPlaceholder -- Placeholders built from counted arrays only.
	$sql = "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id IN ($id_placeholders) AND meta_key IN ($key_placeholders)";
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Query is prepared via call_user_func_array( array( $wpdb, 'prepare' ), ... ); Plugin Check cannot detect dynamic prepare().
	$rows = $wpdb->get_results( call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $prepare_args ) ) );

	foreach ( (array) $rows as $row ) {
		$pid = (int) $row->post_id;
		if ( ! isset( $meta_by_slide[ $pid ] ) ) {
			$meta_by_slide[ $pid ] = array();
		}
		$meta_by_slide[ $pid ][ (string) $row->meta_key ] = (string) $row->meta_value;
	}

	return $meta_by_slide;
}

/**
 * Whether Meta Slider uses attachment title/alt for a slide field (inherit on).
 *
 * @param array<int, array<string, string>> $meta_by_slide From wpscx_metaslider_load_slide_meta().
 * @param int                               $slide_id      Slide post ID.
 * @param string                            $field         title|alt.
 * @return bool
 */
function wpscx_metaslider_inherit_enabled( $meta_by_slide, $slide_id, $field ) {
	$key = 'ml-slider_inherit_image_' . $field;
	if ( ! isset( $meta_by_slide[ $slide_id ][ $key ] ) ) {
		return false;
	}

	return filter_var( $meta_by_slide[ $slide_id ][ $key ], FILTER_VALIDATE_BOOLEAN );
}

/**
 * Spell-check a Meta Slider text value and append errors to a SplFixedArray list.
 *
 * @param string       $text          Field text.
 * @param string       $slide_label   Page name label for results.
 * @param int          $slide_id      Slide post ID.
 * @param SplFixedArray $error_list   Error list (word, page_name, page_id).
 * @param int          $error_count   Current error count (by ref).
 * @param int          $word_count    Word counter (by ref).
 * @param int          $total_words   Total words (by ref).
 * @param array        $wpsc_haystack Dictionary haystack.
 * @param array        $wpsc_settings Plugin settings.
 */
function wpscx_metaslider_add_errors_from_text( $text, $slide_label, $slide_id, $error_list, &$error_count, &$word_count, &$total_words, $wpsc_haystack, $wpsc_settings ) {
	if ( '' === (string) $text ) {
		return;
	}

	$word_list = wpscx_clean_all( (string) $text, $wpsc_settings );
	$words     = explode( ' ', $word_list );
	foreach ( $words as $word ) {
		++$word_count;
		++$total_words;
		$word = trim( $word, "\"`\xEF\xBF\xBD\xEF\xBF\xBD" );
		if ( wpscx_check_word( $word, $wpsc_haystack, $wpsc_settings ) ) {
			$hold    = new SplFixedArray( 3 );
			$hold[0] = $word;
			$hold[1] = $slide_label;
			$hold[2] = $slide_id;
			$error_list->setSize( $error_list->getSize() + 1 );
			$error_list[ $error_count ] = $hold;
			++$error_count;
		}
	}
}

/**
 * Recursively collect string leaf values from decoded JSON for spellchecking.
 * Skips URLs, hex colors, simple CSS dimensions, Smart Slider pipe tokens (|*|),
 * HTML target names (_blank), and $variable placeholders.
 *
 * @since 1.0.0
 *
 * @param mixed $value Decoded JSON (typically array from json_decode( ..., true )).
 * @return string Space-separated text suitable for wpscx_clean_all / word splitting.
 */
function wpscx_extract_json_text( $value ) {
	if ( is_array( $value ) ) {
		$parts = array();
		foreach ( $value as $child ) {
			$part = wpscx_extract_json_text( $child );
			if ( '' !== $part ) {
				$parts[] = $part;
			}
		}
		return implode( ' ', $parts );
	}

	if ( ! is_string( $value ) || '' === $value ) {
		return '';
	}

	// Skip absolute / protocol-relative URLs and obvious hostnames.
	if ( preg_match( '#^(https?:)?//#i', $value ) || preg_match( '#^www\.#i', $value ) ) {
		return '';
	}

	// Skip hex color tokens.
	if ( preg_match( '/^#?[0-9a-fA-F]{3,8}$/', $value ) ) {
		return '';
	}

	// Skip numeric CSS dimensions and angles (e.g. 12px, 1.5em, 90deg).
	if ( preg_match( '/^\d+(\.\d+)?(px|em|rem|vh|vw|vmin|vmax|ch|ex|%|pt|pc|in|cm|mm|deg|rad|grad|turn|s|ms|Hz|kHz)?$/i', $value ) ) {
		return '';
	}

	// Smart Slider 3 (and similar) store layer metrics as pipe-delimited tokens, e.g. 0|*|0|*|0|*|px — not human prose.
	if ( false !== strpos( $value, '|*|' ) ) {
		return '';
	}

	// HTML anchor target values often appear as JSON string leaves.
	if ( preg_match( '/^_(blank|self|parent|top)$/i', $value ) ) {
		return '';
	}

	// SS3 variable placeholders as whole-string leaves (e.g. $upload), not currency like $5.
	if ( preg_match( '/^\$[A-Za-z_][A-Za-z0-9_]*$/', $value ) ) {
		return '';
	}

	return $value;
}

/**
 * Collect user-facing copy from Smart Slider 3 slide JSON (layer tree + item.values).
 * Only reads known value keys per item type; ignores layout, fonts, and style payloads.
 *
 * @since 1.0.0
 *
 * @param array|null $decoded json_decode( $slide_json, true ).
 * @return string Space-separated text for spellchecking.
 */
function wpscx_extract_smartslider3_slide_text( $decoded ) {
	if ( ! is_array( $decoded ) ) {
		return '';
	}
	$layers = $decoded;
	if ( isset( $decoded['layers'] ) && is_array( $decoded['layers'] ) ) {
		$layers = $decoded['layers'];
	}
	$parts = array();
	wpscx_ss3_collect_layer_text( $layers, $parts );
	return implode( ' ', array_filter( $parts ) );
}

/**
 * @param mixed $layers Layer list from SS3 slide JSON.
 * @param array $parts  Collected strings (by ref).
 */
function wpscx_ss3_collect_layer_text( $layers, array &$parts ) {
	if ( ! is_array( $layers ) ) {
		return;
	}
	foreach ( $layers as $layer ) {
		if ( ! is_array( $layer ) ) {
			continue;
		}
		if ( ! empty( $layer['layers'] ) && is_array( $layer['layers'] ) ) {
			wpscx_ss3_collect_layer_text( $layer['layers'], $parts );
		}
		// Row layouts nest columns under "cols"; each column has its own "layers" list (SS3 default structure).
		if ( ! empty( $layer['cols'] ) && is_array( $layer['cols'] ) ) {
			wpscx_ss3_collect_layer_text( $layer['cols'], $parts );
		}
		$item = null;
		if ( ! empty( $layer['item'] ) && is_array( $layer['item'] ) ) {
			$item = $layer['item'];
		} elseif ( ! empty( $layer['items'][0] ) && is_array( $layer['items'][0] ) ) {
			$item = $layer['items'][0];
		}
		if ( null !== $item ) {
			wpscx_ss3_append_item_text( $item, $parts );
		}
	}
}

/**
 * @param array $item SS3 layer item (type + values).
 * @param array $parts Collected strings (by ref).
 */
function wpscx_ss3_append_item_text( array $item, array &$parts ) {
	$type = isset( $item['type'] ) ? (string) $item['type'] : '';
	if ( '' === $type || 'missing' === $type ) {
		return;
	}
	$values = isset( $item['values'] ) && is_array( $item['values'] ) ? $item['values'] : array();
	foreach ( wpscx_ss3_item_text_value_keys( $type ) as $key ) {
		if ( empty( $values[ $key ] ) || ! is_string( $values[ $key ] ) ) {
			continue;
		}
		$parts[] = $values[ $key ];
	}
}

/**
 * Value keys under item.values that hold spellable user text (per SS3 item types).
 *
 * @param string $type Item type slug.
 * @return string[] Value keys to read.
 */
function wpscx_ss3_item_text_value_keys( $type ) {
	static $map = array(
		'heading' => array( 'heading', 'title' ),
		'text'    => array( 'content', 'contenttablet', 'contentmobile' ),
		'button'  => array( 'content' ),
		'image'   => array( 'alt', 'title' ),
		'youtube' => array( 'alt', 'playbuttonalt' ),
		'vimeo'   => array( 'alt', 'playbuttonalt' ),
	);
	if ( isset( $map[ $type ] ) ) {
		return $map[ $type ];
	}
	// Pro / third-party layer types: only common copy fields (no style, size, or URL keys).
	return array( 'heading', 'content', 'html', 'caption', 'subtitle', 'label', 'text', 'title', 'before', 'after', 'highlighted' );
}

function wpscx_get_post_edit_url( $post_id ) {
	$edit_link = get_edit_post_link( (int) $post_id, 'raw' );
	if ( $edit_link ) {
		return $edit_link;
	}
	return admin_url( 'post.php?post=' . (int) $post_id . '&action=edit' );
}

function wpscx_construct_url( $type, $id ) {
	$blog = get_site_url();

	$url = wpscx_get_post_edit_url( $id );

	if ( 'Menu Item' === $type ) {
		$url = $blog . '/wp-admin/nav-menus.php?action=edit&menu=' . $id;
	} elseif ( 'Contact Form 7' === $type ) {
		$url = $blog . '"admin.php?page=wpcf7&post=' . $id . '&action=edit';
	} elseif ( 'Post Title' === $type || 'Page Title' === $type || 'Yoast SEO Description' === $type || 'All in One SEO Description' === $type || 'SEO Description' === $type || 'Yoast SEO Title' === $type || 'All in One SEO Title' === $type || 'SEO Title' === $type || 'Post Slug' === $type || 'Page Slug' === $type ) {
		$url = wpscx_get_post_edit_url( $id );
	} elseif ( 'Smart Slider Title' === $type || 'Smart Slider Caption' === $type || 'Smart Slider Group' === $type || 'Smart Slider Content' === $type ) {
		$url = wpscx_smartslider3_admin_url( (int) $id, $type );
	} elseif ( 'Meta Slider Group' === $type || 'Meta Slider Slide Title' === $type || 'Meta Slider Caption' === $type || 'Meta Slider Content' === $type || 'Meta Slider Image Title' === $type || 'Meta Slider Image Alt' === $type || 'Meta Slider Link Alt' === $type ) {
		$url = wpscx_metaslider_admin_url( (int) $id, $type );
	} elseif ( 'Media Title' === $type || 'Media Description' === $type || 'Media Caption' === $type || 'Media Alternate Text' === $type ) {
		$url = wpscx_get_post_edit_url( $id );
	} elseif ( 'Tag Title' === $type || 'Tag Description' === $type || 'Tag Slug' === $type ) {
		$url = $blog . '/wp-admin/term.php?taxonomy=post_tag&tag_ID=' . $id . '&post_type=post';
	} elseif ( 'Post Category' === $type || 'Category Description' === $type || 'Category Slug' === $type ) {
		$url = $blog . '/wp-admin/term.php?taxonomy=category&tag_ID=' . $id . '&post_type=post';
	} elseif ( 'Author Nickname' === $type || 'Author First Name' === $type || 'Author Last Name' === $type || 'Author Biography' === $type || 'Author SEO Title' === $type || 'Author SEO Description' === $type || 'twitter' === $type || 'facebook' === $type ) {
		$url = $blog . '/wp-admin/user-edit.php?user_id=' . $id;
	} elseif ( 'Site Name' === $type || 'Site Tagline' === $type ) {
		$url = $blog . '/wp-admin/options-general.php';
	} else {
		$url = wpscx_get_post_edit_url( $id );
	}

	return $url;
}

function wpscx_finalize( $start_time ) {
	global $wpdb;
	$options_table = $wpdb->prefix . 'spellcheck_options';

	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'scan_in_progress' ) );

	$end_time   = time();
	$total_time = wpscx_time_elapsed( $end_time - $start_time + 6 );
	$wpdb->update( $options_table, array( 'option_value' => $total_time ), array( 'option_name' => 'last_scan_finished' ) );
}

function wpscx_sql_insert( $error_list, $page_type, $table_name = '' ) {
	global $wpdb;
	if ( '' === $table_name ) {
		$table_name = $wpdb->prefix . 'spellcheck_words';
	}
	if ( 'Empty Field' === $page_type ) {
		$table_name = $wpdb->prefix . 'spellcheck_empty';
	}

	if ( 'Multi' === $page_type ) {
		$sql = "INSERT INTO $table_name (word, page_name, page_type, page_id) VALUES ";
		if ( $error_list->getSize() > 0 ) {
			for ( $x = 0; $x < $error_list->getSize(); $x++ ) {
				if ( isset( $error_list[ $x ][0] ) && null !== $error_list[ $x ][0] ) {
					$sql .= "('" . esc_sql( $error_list[ $x ][0] ) . "', '" . esc_sql( $error_list[ $x ][1] ) . "', '" . $error_list[ $x ][3] . "', " . $error_list[ $x ][2] . '), ';
				}
				if ( 0 === $x % 100000 ) {
					$sql = trim( $sql, ', ' );
					if ( "INSERT INTO $table_name (word, page_name, page_type, page_id) VALUES" !== $sql ) {
						// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string. User data is sanitized with esc_sql().
						$wpdb->query( $sql );
					}
					$sql = "INSERT INTO $table_name (word, page_name, page_type, page_id) VALUES ";
				}
			}
			$sql = trim( $sql, ', ' );
			if ( "INSERT INTO $table_name (word, page_name, page_type, page_id) VALUES" !== $sql ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string. User data is sanitized with esc_sql().
				$wpdb->query( $sql );
			}
		}
	} elseif ( 'Empty Field' === $page_type ) {
		if ( sizeof( (array) $error_list ) > 0 ) {
			$sql = "INSERT INTO $table_name (word, page_name, page_type, page_id) VALUES ";

			foreach ( $error_list as $error ) {
				if ( '' !== $error['word'] && null !== $error['word'] ) {
					$sql .= "('" . esc_sql( $error['word'] ) . "', '" . esc_sql( $error['page_name'] ) . "', '" . $error['page_type'] . "', " . $error['page_id'] . '), ';
				}
			}
			$sql = trim( $sql, ', ' );
			if ( '' !== $sql ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string. User data is sanitized with esc_sql().
				$wpdb->query( $sql );
			}
		}
	} else {
		$sql = "INSERT INTO $table_name (word, page_name, page_type, page_id) VALUES ";
		if ( $error_list->getSize() > 1 ) {
			for ( $x = 0; $x < ( $error_list->getSize() - 1 ); $x++ ) {
				if ( '' !== $error_list[ $x ][0] && null !== $error_list[ $x ][0] ) {
					$sql .= "('" . esc_sql( $error_list[ $x ][0] ) . "', '" . esc_sql( $error_list[ $x ][1] ) . "', '" . $page_type . "', " . $error_list[ $x ][2] . '), ';
				}
				if ( 0 === $x % 100000 ) {
					$sql = trim( $sql, ', ' );
					if ( "INSERT INTO $table_name (word, page_name, page_type, page_id) VALUES" !== $sql ) {
						// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string. User data is sanitized with esc_sql().
						$wpdb->query( $sql );
					}
					$sql = "INSERT INTO $table_name (word, page_name, page_type, page_id) VALUES ";
				}
			}
			$sql = trim( $sql, ', ' );
			if ( "INSERT INTO $table_name (word, page_name, page_type, page_id) VALUES" !== $sql ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string. User data is sanitized with esc_sql().
				$wpdb->query( $sql );
			}
		}
	}
}

function wpgcx_sql_insert( $error_list ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'spellcheck_grammar';
	$sql        = "INSERT INTO $table_name (page_id, grammar) VALUES ";
	if ( $error_list->getSize() > 1 ) {
		for ( $x = 0; $x < ( $error_list->getSize() - 1 ); $x++ ) {
			if ( '' !== $error_list[ $x ][0] ) {
				$sql .= '(' . esc_sql( $error_list[ $x ][0] ) . ', ' . esc_sql( $error_list[ $x ][1] ) . '), ';
			}
		}
		$sql = trim( $sql, ', ' );
		if ( "INSERT INTO $table_name (page_id, grammar) VALUES" !== $sql ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'spellcheck_grammar'. User data is sanitized with esc_sql().
			$wpdb->query( $sql );
		}
	}
}

function wpscx_clean_text( $content, $debug = false ) {
	$content = preg_replace( '/\s/u', ' ', $content );
	$content = str_replace( '’', "'", $content );
	$content = str_replace( '`', "'", $content );
	$content = str_replace( '“', ' ', $content );
	$content = str_replace( "'''", "'", $content );
	$content = str_replace( '(', ' ', $content );
	$content = str_replace( '{', ' ', $content );
	$content = str_replace( ')', ' ', $content );
	$content = str_replace( '-', ' ', $content );
	$content = str_replace( '"', ' ', $content );
	$content = str_replace( '/', ' ', $content );
	$content = str_replace( '‘', ' ', $content );
	$content = str_replace( '–', ' ', $content );
	$content = str_replace( '—', ' ', $content );
	$content = str_replace( '•', ' ', $content );
	$content = str_replace( '′', ' ', $content );
	$content = str_replace( '', ' ', $content );
	$content = str_replace( '‐', ' ', $content );
	$content = str_replace( '‑', ' ', $content );
	$content = str_replace( '…', ' ', $content );
	$content = str_replace( '&ZeroWidthSpace;', ' ', $content );
	$content = trim( $content, "'" );
	$content = preg_replace( '/(((?<=\s|^))[0-9|$][0-9.,]+((?=\s|$)|(c|k|b|s|m|st|th|nd|rd|mb|kg|gb|tb|yb|sec|hr|min|am|pm|a.m.|p.m.)(\s|$|,|<|\.)))/ui', '', $content );
	// Spanish characters: áÁéÉíÍñÑóÓúÚüÜ¿¡«»
	// French Characters: ÀàÂâÆæÈèÉéÊêËëÎîÏïÔôŒœÙùÛûÜüŸÿ
	$content = preg_replace( "/([^\s$\"'])*([^0-9a-zA-Z'’`ÀàÂâÆæÈèÉéÊêËëÎîÏïÔôŒœÙùÛûÜüŸÿüáÁéÉíÍñÑóÓúÚüÜ\|¿¡«»€!@$%^&*()\-=_+,.\/;'[\]\\<>?:\"{}\s]|'s)+([^\s$\"'])*/ius", ' ', $content );
	$content = preg_replace( "/([^0-9'’`ÀàÂâÆæÈèÉéÊêËëÎîÏïÔôŒœÙùÛûÜüŸÿüáÁéÉíÍñÑóÓúÚüÜ¿¡«»€a-zA-Z]|'s)+(\s|$|\"|')/ius", ' ', $content );
	$content = preg_replace( "/(\s|^)(\S+[^ 0-9a-zA-Z'’`ÀàÂâÆæÈèÉéÊêËëÎîÏïÔôŒœÙùÛûÜüŸÿüáÁéÉíÍñÑóÓúÚüÜ¿¡«»€!@$%^&*()\-=_+,.\/;'[\]\\<>?:\"{}|]+\S+)(\s|$)/u", ' ', $content );

	$content = str_replace( '§', ' ', $content );
	$content = str_replace( '¢', ' ', $content );
	$content = str_replace( '¨', ' ', $content );
	$content = str_replace( '\\', ' ', $content );
	$content = preg_replace( "/\r?\n|\r/u", ' ', $content );

	return $content;
}

function wpscx_clean_slug( $slug ) {
	return str_replace( '-', ' ', $slug );
}

function wpscx_ignore_caps( $wpsc_settings, $word ) {
	if ( preg_match( '/[a-zA-ZÀÂÆÈÉÊËÎÏÔŒÙÛÜŸÁÉÍÑÓÚÜ]/', $word ) ) {
		return ( strtoupper( $word ) !== $word || 'false' === $wpsc_settings[3]->option_value );
	} else {
		return true;
	}
}

function wpscx_dictionary_init( $dict_file ) {
	global $wpdb;
	$table_name    = $wpdb->prefix . 'spellcheck_words';
	$dict_table    = $wpdb->prefix . 'spellcheck_dictionary';
	$wpsc_haystack = null;

	$wpscx_dict_list = $wpdb->get_results( "SELECT * FROM $dict_table;" );
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'spellcheck_words'.
	$wpscx_ignore_list = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE ignore_word=%d', 1 ) );

	foreach ( $dict_file as $value ) {
		$wpsc_haystack[ strtoupper( stripslashes( $value ) ) ] = 1;
	}

	foreach ( $wpscx_dict_list as $value ) {
		$wpsc_haystack[ strtoupper( stripslashes( $value->word ) ) ] = 1;
	}

	foreach ( $wpscx_ignore_list as $value ) {
		$wpsc_haystack[ strtoupper( stripslashes( $value->word ) ) ] = 1;
	}

	return $wpsc_haystack;
}

function wpscx_content_filter( $content ) {
	$divi_check = wp_get_theme();
	if ( 'Divi' === $divi_check->name || 'Divi' === $divi_check->parent_name || 'Bridge' === $divi_check->parent_name || 'Bridge' === $divi_check->name ) {
		global $wp_query;
		// $wp_query->is_singular = true;

		$content = apply_filters( 'the_content', $content );

		return $content;
	} else {
		return $content;
	}
}

function wpscx_divi_check( $content ) {
	$divi_check = wp_get_theme();
	if ( 'Divi' === $divi_check->name || 'Divi' === $divi_check->parent_name || 'Bridge' === $divi_check->parent_name || 'Bridge' === $divi_check->name ) {
		global $wp_query;
		// $wp_query->is_singular = true;

		$content = apply_filters( 'the_content', $content );

		return $content;
	} else {
		return $content;
	}
}

function wpscx_script_cleanup( $content ) {
	$content = (string) ( $content ?? '' );
	$content = preg_replace( '@<style[^>]*?>.*?</style>@siu', ' ', $content );
	$content = preg_replace( '@<script[^>]*?>.*?</script>@siu', ' ', $content );
	$content = preg_replace( '/(\<.*?\>)/', ' ', $content );
	$content = preg_replace( '/<iframe.+<\/iframe>/', ' ', $content );

	return $content;
}

function wpscx_clean_shortcode( $content ) {
	return preg_replace( '/(\[.*?\])/', ' ', $content );
}

function wpscx_html_cleanup( $content ) {
	return html_entity_decode( wp_strip_all_tags( $content ), ENT_QUOTES, 'utf-8' );
}

function wpscx_email_cleanup( $content ) {
	return preg_replace( '/\S+\@\S+\.\S+/', ' ', $content );
}

function wpscx_website_cleanup( $content ) {
	$content = preg_replace( '/((http|https|ftp)\S+)/', '', $content );
	$content = preg_replace( '/www\.\S+/', '', $content );
	$content = preg_replace( '/\b\S+\.(?:COM|NET|ORG|GOV|INFO|XYZ|US|TOP|LOAN|BIZ|WANG|WIN|CLUB|ONLINE|VIP|MOBI|BID|SITE|MEN|TECH|PRO|SPACE|SHOP|WEBSITE|ASIA|KIWI|XIN|LINK|PARTY|TRADE|LIFE|STORE|NAME|CLOUD|STREAM|CAT|LIVE|TEL|XXX|ACCOUNTANT|DATE|DOWNLOAD|BLOG|WORK|RACING|REVIEW|TODAY|CLICK|ROCKS|NYC|WORLD|EMAIL|SOLUTIONS|NEWS|TOKYO|DESIGN|GURU|LONDON|LTD|ONE|PUB|REALTY|COMPANY|BERLIN|WEBCAM|HOST|PHOTOGRAPHY|PRESS|SCIENCE|FAITH|JOBS|REALTOR|REN|CITY|OVH|RED|AGENCY|SERVICES|MEDIA|GROUP|CENTER|STUDIO|GLOBAL|NINJA|TECHNOLOGY|TIPS|BAYERN|EXPERT|SALE|AMSTERDAM|DIGITAL|ACADEMY|NETWORK|HAMBURG|gdn|DE|CN|UK|NL|EU|RU|TK|AR|BR|IT|PL|FR|AU|CH|CA|ES|JP|KR|DK|BE|SE|AT|CZ|IN|HU|NO|TW|NZ|MX|PT|CL|FI|HK|TR|TRAVEL|AERO|COOP|MUSEUM|SHOW)(?:\S+)?\b/i', ' ', $content );

	return $content;
}

function wpscx_clean_all( $content, $wpsc_settings, $debug = false ) {
	$content = wpscx_script_cleanup( $content );
	$content = wpscx_clean_shortcode( $content );
	$content = wpscx_html_cleanup( $content );

	if ( 'true' === $wpsc_settings[23]->option_value ) {
		$content = wpscx_email_cleanup( $content );
	}

	if ( 'true' === $wpsc_settings[24]->option_value ) {
		$content = wpscx_website_cleanup( $content );
	}

	$content = wpscx_clean_text( $content, $debug );

	return $content;
}

function wpgcx_clean_all( $content, $wpsc_settings ) {
	$content = wpscx_script_cleanup( $content );
	$content = wpscx_clean_shortcode( $content );
	$content = wpscx_html_cleanup( $content );

	if ( isset( $wpsc_settings[23]->option_value ) && 'true' === $wpsc_settings[23]->option_value ) {
		$content = wpscx_email_cleanup( $content );
	}

	if ( isset( $wpsc_settings[24]->option_value ) && 'true' === $wpsc_settings[24]->option_value ) {
		$content = wpscx_website_cleanup( $content );
	}

	return $content;
}

function wpbcx_clean_all( $content, $wpsc_settings ) {
	$content = wpscx_script_cleanup( $content );

	if ( isset( $wpsc_settings[23] ) && $wpsc_settings[23] && 'true' === $wpsc_settings[23]->option_value ) {
		$content = wpscx_email_cleanup( $content );
	}

	if ( isset( $wpsc_settings[24] ) && $wpsc_settings[24] && 'true' === $wpsc_settings[24]->option_value ) {
		$content = wpscx_website_cleanup( $content );
	}

	return $content;
}

function wpscx_check_broken_code() {
	global $wpscx_ent_included;
	if ( ! $wpscx_ent_included ) {
		return;
	}
	wphcx_clear_results();
	$scanner = new Wpscx_Broken_Code_Scanner_Pro();

	$scanner->wpscx_scan_all();
}
add_action( 'admincheckcode', 'wpscx_check_broken_code', 10, 2 );

function wpscx_check_broken_html() {
	global $wpscx_ent_included;
	if ( ! $wpscx_ent_included ) {
		return;
	}
	$scanner = new Wpscx_Broken_Code_Scanner_Pro();

	$scanner->wpscx_scan_html();
}
add_action( 'admincheckhtml', 'wpscx_check_broken_html', 10, 2 );

function wpscx_check_broken_shortcode() {
	global $wpscx_ent_included;
	if ( ! $wpscx_ent_included ) {
		return;
	}
	$scanner = new Wpscx_Broken_Code_Scanner_Pro();

	$scanner->wpscx_scan_shortcode();
}
add_action( 'admincheckshortcode', 'wpscx_check_broken_shortcode', 10, 2 );

function wpscx_check_pages( $wpsc_haystack = null, $is_running = false ) {
	$scanner = new Wpscx_Spellcheck_Scanner();

	$scanner->check_pages();
}
add_action( 'admincheckpages', 'wpscx_check_pages', 10, 2 );

function wpscx_check_posts( $wpsc_haystack = null, $is_running = false ) {
	$scanner = new Wpscx_Spellcheck_Scanner();

	$scanner->check_posts();
}
add_action( 'admincheckposts', 'wpscx_check_posts', 10, 2 );

function wpscx_check_author_spelling( $wpsc_haystack = null, $is_running = false ) {
	$scanner = new Wpscx_Spellcheck_Scanner();

	$scanner->check_author_spelling();
}

function wpscx_check_site_name( $wpsc_haystack = null, $is_running = false ) {
	$scanner = new Wpscx_Spellcheck_Scanner();

	$scanner->check_site_name();
}

function wpscx_check_site_tagline( $wpsc_haystack = null, $is_running = false ) {
	$scanner = new Wpscx_Spellcheck_Scanner();

	$scanner->check_site_tagline();
}

function wpscx_check_authors( $wpsc_haystack = null, $is_running = false ) {
	$scanner = new Wpscx_Spellcheck_Scanner();

	$scanner->check_authors();
}
add_action( 'admincheckauthors', 'wpscx_check_authors' );

function wpscx_check_cf7( $wpsc_haystack = null, $is_running = false ) {
	$scanner = new Wpscx_Spellcheck_Scanner();

	$scanner->check_cf7( $wpsc_haystack, $is_running );
}
add_action( 'admincheckcf7', 'wpscx_check_cf7' );

function wphcx_clear_results( $clear_type = '' ) {
	global $wpdb;
	$table_name    = $wpdb->prefix . 'spellcheck_html';
	$options_table = $wpdb->prefix . 'spellcheck_options';
	$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'html_page_count' ) );
	$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'html_post_count' ) );
	$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'html_media_count' ) );

	$wpdb->delete( $table_name, array( 'ignore_word' => false ) );
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'spellcheck_html'. ALTER TABLE is DDL and cannot use prepare(). Table name is escaped with esc_sql().
	$wpdb->get_results( 'ALTER TABLE ' . esc_sql( $table_name ) . ' AUTO_INCREMENT = 1' );
}

function wphcx_clear_scan() {
	global $wpdb;
	$options_table = $wpdb->prefix . 'spellcheck_options';

	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'html_scan_running' ) );
}

function wpscx_clear_results( $clear_type = '' ) {
	global $wpdb;
	$table_name    = $wpdb->prefix . 'spellcheck_words';
	$options_table = $wpdb->prefix . 'spellcheck_options';
	$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'total_word_count' ) );
	$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'page_count' ) );
	$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'post_count' ) );
	$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'media_count' ) );

	$wpdb->delete( $table_name, array( 'ignore_word' => false ) );
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'spellcheck_words'. ALTER TABLE is DDL and cannot use prepare(). Table name is escaped with esc_sql().
	$wpdb->get_results( 'ALTER TABLE ' . esc_sql( $table_name ) . ' AUTO_INCREMENT = 1' );
	if ( 'full' === $clear_type ) {
		$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'pro_word_count' ) );
	}
}

function wpscx_clear_empty_results( $clear_type = '' ) {
	global $wpdb;
	$table_name    = $wpdb->prefix . 'spellcheck_empty';
	$options_table = $wpdb->prefix . 'spellcheck_options';
	$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'empty_page_count' ) );
	$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'empty_post_count' ) );
	$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'empty_media_count' ) );
	$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'pro_empty_count' ) );

	$wpdb->delete( $table_name, array( 'ignore_word' => false ) );
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'spellcheck_empty'. ALTER TABLE is DDL and cannot use prepare(). Table name is escaped with esc_sql().
	$wpdb->get_results( 'ALTER TABLE ' . esc_sql( $table_name ) . ' AUTO_INCREMENT = 1' );

	if ( 'full' === $clear_type ) {
		$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'empty_factor' ) );
		$wpdb->update( $options_table, array( 'option_value' => '0' ), array( 'option_name' => 'pro_empty_count' ) );
	}
}



function wpscx_set_scan_in_progress( $rng_seed = 0 ) {
	global $wpdb;
	global $wpscx_ent_included;
	global $wpsc_settings;
	$options_table = $wpdb->prefix . 'spellcheck_options';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'spellcheck_options'. Query contains no user input.
	$settings = $wpdb->get_results( 'SELECT option_value FROM ' . $options_table );

	$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'entire_scan' ) );

	if ( 'true' === $settings[4]->option_value ) {
		$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'page_sip' ) );
	}
	if ( 'true' === $settings[5]->option_value ) {
		$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'post_sip' ) );
	}
	if ( 'true' === $settings[37]->option_value && is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
		$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'cf7_sip' ) );
	}
	if ( 'true' === $settings[44]->option_value ) {
		$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'author_sip' ) );
	}

	if ( $wpscx_ent_included ) {
		if ( 'true' === $settings[7]->option_value ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'menu_sip' ) );
		}
		if ( 'true' === $settings[14]->option_value || 'true' === $settings[38]->option_value || 'true' === $settings[39]->option_value ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'tag_title_sip' ) );
		}
		if ( 'true' === $settings[15]->option_value || 'true' === $settings[40]->option_value || 'true' === $settings[41]->option_value ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'cat_title_sip' ) );
		}
		if ( 'true' === $settings[16]->option_value ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'seo_desc_sip' ) );
		}
		if ( 'true' === $settings[17]->option_value ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'seo_title_sip' ) );
		}
		if ( 'true' === $settings[30]->option_value ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'slider_sip' ) );
		}
		if ( 'true' === $settings[31]->option_value ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'media_sip' ) );
		}
		if ( 'true' === $settings[36]->option_value && is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$wpdb->update( $options_table, array( 'option_value' => 'true' ), array( 'option_name' => 'ecommerce_sip' ) );
		}
	}
}

function wpscx_clear_scan() {
	global $wpdb;
	global $wpsc_settings;
	$options_table = $wpdb->prefix . 'spellcheck_options';
	$settings      = $wpsc_settings;

	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'entire_scan' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'page_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'post_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'cf7_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'author_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'menu_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'tag_title_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'cat_title_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'seo_desc_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'seo_title_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'slider_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'media_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'tag_desc_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'tag_slug_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'cat_desc_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'cat_slug_sip' ) );
	$wpdb->update( $options_table, array( 'option_value' => 'false' ), array( 'option_name' => 'ecommerce_sip' ) );
}

function wpscx_scan_all( $rng_seed = 0, $log_debug = true ) {
	global $wpsc_settings;

	wpscx_set_global_vars();

	wpscx_scan_site_event( $rng_seed, $log_debug );
	wpscx_scan_site_empty( $rng_seed );
	wpscx_check_broken_code();

	if ( 'true' === $wpsc_settings[0]->option_value ) {
		$emailer = new Wpscx_Email();
		$emailer->email_admin();
	}
}
add_action( 'wpscxscanall', 'wpscx_scan_all' );



function wpscx_scan_site_event( $rng_seed = 0, $log_debug = true ) {
	$start         = round( microtime( true ), 5 );
	$wpscx_debug_q = wpscx_debug_queries_at_start();
	ini_set( 'memory_limit', '512M' ); // Sets the PHP memory limit
	set_time_limit( 600 );
	global $wpdb;
	global $wpscx_ent_included;
	global $wpsc_settings;
	$table_name    = $wpdb->prefix . 'spellcheck_words';
	$options_table = $wpdb->prefix . 'spellcheck_options';
	$page_list     = null;
	$post_list     = null;
	$sql_count     = 0;

	wpscx_set_global_vars();

	if ( 10 === $rng_seed ) {
		wpscx_clear_results();
	}

	if ( $wpscx_ent_included ) {
		if ( '' != $wpsc_settings[11]->option_value ) {
			$loc = plugins_url( '/wp-spell-check-pro/admin/dict/' . $wpsc_settings[11]->option_value . '.pws' );
		} else {
			$loc = plugins_url( '/dict/en_US.pws', __FILE__ );
		}
	} elseif ( '' != $wpsc_settings[11]->option_value ) {
		$loc = plugins_url( '/dict/' . $wpsc_settings[11]->option_value . '.pws', __FILE__ );
	} else {
		$loc = plugins_url( '/dict/en_US.pws', __FILE__ );
	}
	$contents = wp_remote_retrieve_body( wp_remote_get( $loc ) );

	$contents  = str_replace( "\r\n", "\n", $contents );
	$dict_file = explode( "\n", $contents );

	$wpsc_haystack = wpscx_dictionary_init( $dict_file );

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe; ORDER BY id ensures stable index-based access (matches wpscx_set_global_vars).
	$settings = $wpdb->get_results( 'SELECT option_value FROM ' . $options_table . ' ORDER BY id' );
	++$sql_count;

	if ( ! $wpscx_ent_included ) {
		$scanner = new Wpscx_Spellcheck_Scanner();
		$scanner->check_errors( $wpsc_haystack );
	}

	// $start = round(microtime(true),5);
	if ( $wpscx_ent_included ) {
		if ( 'true' === $settings[5]->option_value || 'true' === $settings[13]->option_value || 'true' === $settings[19]->option_value ) {
			wpscx_check_posts_ent( $wpsc_haystack, true );
		}
		if ( 'true' === $settings[4]->option_value || 'true' === $settings[12]->option_value || 'true' === $settings[18]->option_value ) {
			wpscx_check_pages_ent( $wpsc_haystack, true );
		}
		if ( 'true' === $settings[36]->option_value && is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			wpscx_check_ecommerce_ent( $wpsc_haystack, true );
		}
		if ( 'true' === $settings[7]->option_value ) {
			wpscx_check_menus_ent( $wpsc_haystack, true );
		}
		if ( 'true' === $settings[14]->option_value || 'true' === $settings[38]->option_value || 'true' === $settings[39]->option_value ) {
			wpscx_check_post_tags_ent( $wpsc_haystack, true );
		}
		if ( 'true' === $settings[15]->option_value || 'true' === $settings[41]->option_value || 'true' === $settings[40]->option_value ) {
			wpscx_check_post_categories_ent( $wpsc_haystack, true );
		}
		if ( 'true' === $settings[16]->option_value ) {
			wpscx_check_yoast_ent( $wpsc_haystack, true );
		}
		if ( 'true' === $settings[17]->option_value ) {
			wpscx_check_seo_titles_ent( $wpsc_haystack, true );
		}
		if ( 'true' === $settings[30]->option_value ) {
			wpscx_check_sliders_ent( $wpsc_haystack, true );
		}
		if ( 'true' === $settings[31]->option_value ) {
			wpscx_check_media_ent( $wpsc_haystack, true );
		}
		if ( 'true' === $settings[37]->option_value && ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) ) {
			wpscx_check_cf7( $wpsc_haystack, true );
		}
		if ( 'true' === $settings[44]->option_value ) {
			wpscx_check_authors( $wpsc_haystack, true );
		}
		if ( 'true' === $settings[147]->option_value ) {
			wpscx_check_widgets( $wpsc_haystack, true );
		}
	} else {
		if ( 'true' === $settings[4]->option_value ) {
			wpscx_check_pages( $wpsc_haystack, true );
		}
		if ( 'true' === $settings[5]->option_value ) {
			wpscx_check_posts( $wpsc_haystack, true );
		}
		if ( 'true' === $settings[44]->option_value ) {
			wpscx_check_authors( $wpsc_haystack, true );
		}
		if ( 'true' === $settings[37]->option_value && ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) ) {
			wpscx_check_cf7( $wpsc_haystack, true );
		}
	}

	$end = round( microtime( true ), 5 );
	if ( $log_debug ) {
		wpscx_print_debug( 'Scan Times', round( $end - $start, 5 ), 0, round( memory_get_usage() / 1000, 5 ), 'N/A', $wpscx_debug_q );
	}
}
add_action( 'adminscansite', 'wpscx_scan_site_event' );

function wpscx_time_elapsed( $secs ) {
	if ( $secs > 300000000 ) {
		$secs = 0;
	}
	// $secs += 0;
	$bit = array(
		' year'   => intval( intval( $secs / 31556926 ) % 12 ),
		' week'   => intval( intval( $secs / 604800 ) % 52 ),
		' day'    => intval( intval( $secs / 86400 ) % 7 ),
		' hour'   => intval( intval( $secs / 3600 ) % 24 ),
		' minute' => intval( intval( $secs / 60 ) % 60 ),
		' second' => intval( $secs % 60 ),
	);

	foreach ( $bit as $k => $v ) {
		if ( $v > 1 ) {
			$ret[] = $v . $k . 's';
		}
		if ( 1 === $v ) {
			$ret[] = $v . $k;
		}
	}
	if ( isset( $ret ) ) {
		array_splice( $ret, count( $ret ) - 1, 0, ' ' );
	}
	$ret[] = '';

	return join( ' ', $ret );
}


function wpscx_show_feature_window() {
	/*
	echo "<div class='request-feature-container'>";
	echo "<div class='request-feature-popup' style='display: none;'>";
	echo "<a href='' class='close-popup'>X</a>";
	echo "<h3>We love hearing from you</h3>";
	echo "<p>Please report your problem to make the WP Spell Check plugin better</p>";
	echo "<a href='https://www.wpspellcheck.com/report-a-problem' target='_blank'><button>Report a Problem</button></a>";
	echo "<p>Please note: Support requests will not be handled through this form</p>";
	echo "</div>";
	echo "<div class='request-feature'><a href='' class='request-feature-link'>Report a Problem</a></div>";
	echo"</div>";*/
}


function wphcx_check_scan_progress() {
	global $wpdb;
	global $wpsc_settings;

	$scan_in_progress = false;

	if ( isset( $wpsc_settings[141] ) && $wpsc_settings[141] && 'true' === $wpsc_settings[141]->option_value ) {
		$scan_in_progress = true;
	}

	return $scan_in_progress;
}

function wpscx_check_scan_progress() {
	global $wpdb;
	global $wpsc_settings;

	$scan_in_progress = false;

	for ( $x = 66; $x <= 86; $x++ ) {
		if ( isset( $wpsc_settings[ $x ] ) && $wpsc_settings[ $x ] && 'true' === $wpsc_settings[ $x ]->option_value ) {
			$scan_in_progress = true;
		}
	}

	return $scan_in_progress;
}

function wpscx_check_empty_scan_progress() {
	global $wpdb;
	global $wpsc_settings;

	$scan_in_progress = false;

	for ( $x = 87; $x <= 98; $x++ ) {
		if ( 'true' === $wpsc_settings[ $x ]->option_value ) {
			$scan_in_progress = true;
		}
	}

	return $scan_in_progress;
}

function wpscx_regex_pattern( $to_replace ) {
	$to_replace = preg_quote( $to_replace );
	$to_replace = str_replace( "'", "['|’|‘]", $to_replace );
	$to_replace = str_replace( '"', '["|“|”]', $to_replace );
	// $regex = "/(?![^<]*>)(?<=\s|^|-|>|\"|“|\[)" . $to_replace . "(?=[^0-9'’`ÀàÂâÆæÈèÉéÊêËëÎîÏïÔôŒœÙùÛûÜüŸÿüáÁéÉíÍñÑóÓúÚüÜ¿¡«»€a-zA-Z])/m";
	$regex = "/(?![^<]*>)(?<=\s|^|-|>|\"|“|\[|\(|'|’|”|‘|“|’s|'s|‘s|\)|\^)" . $to_replace . "(?=\s|$|-|<|\.|\"|”\s|\]|,|\*|\)|!|'|\?|>|’|”|‘|“|\(|\^)/um";
	return $regex;
}
