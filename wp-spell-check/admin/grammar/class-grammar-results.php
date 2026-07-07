<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
Admin Classes */
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
	Pro Add-on / Prices: https://www.wpspellcheck.com/product-tour/
*/
class Wpgcx_Table extends WP_List_Table {



	function __construct() {
		global $status, $page;

		parent::__construct(
			array(
				'singular' => 'result',
				'plural'   => 'results',
				'ajax'     => true,
			)
		);
	}

	function column_default( $item, $column_name ) {
		return print_r( $item, true );
	}


	/*
	function column_page($item) {
		set_time_limit(600);
		global $wpdb;
		global $wpgc_options;


		$actions = array (
			'Ignore'                => sprintf('<input type="checkbox" class="wpgc-ignore-checkbox" name="ignore-word[]" value="' . $item['id'] . '" />Ignore'),
			'Edit'                  => sprintf('<a href="#" class="wpsc-edit-button-grammar" page_type="' . $item['page_type'] . '" id="wpsc-word-' . $item['word'] . '">Edit</a>'),
		);


		return sprintf('%1$s<span style="background-color:#0096ff; float: left; margin: 3px 5px 0 -30px; display: block; width: 12px; height: 12px; border-radius: 16px; opacity: 1.0;"></span>%3$s',
			stripslashes(stripslashes($item['word'])),
			$item['ID'],
			$this->row_actions($actions)
		);
	}*/

	function column_page( $item ) {

		$actions = array();

		$page_name = get_the_title( $item['page_id'] );
		if ( ! isset( $item['ID'] ) ) {
			$item['ID'] = '';
		}

		$edit_url = get_edit_post_link( (int) $item['page_id'], 'raw' );
		$actions  = array(
			'Edit' => sprintf( '<a href="%s" class="wpsc-edit-button-grammar" target="_blank">Edit</a>', esc_url( $edit_url ) ),
		);

		return sprintf(
			'%1$s <span style="color:silver"></span>%3$s',
			$page_name,
			$item['ID'],
			$this->row_actions( $actions )
		);
	}

	function column_grammar( $item ) {

		$actions = array();
		if ( ! isset( $item['ID'] ) ) {
			$item['ID'] = '';
		}

		return sprintf(
			'%1$s <span style="color:silver"></span>%3$s',
			$item['grammar'],
			$item['ID'],
			$this->row_actions( $actions )
		);
	}


	function get_columns() {
		global $wpdb;
		global $wpscx_ent_included;
		global $wpgc_settings;

		$options_list = $wpgc_settings;
		$grammar      = '<div style="position: relative; height: 100%;"># of Grammar Errors</div>';

		$columns = array(
			'cb'      => '<input type="checkbox" />',
			'page'    => 'Page',
			'grammar' => $grammar,
		);
		return $columns;
	}


	function get_sortable_columns() {
		$sortable_columns = array(
			'page' => array( 'page', false ),
		);
		return $sortable_columns;
	}


	function single_row( $item ) {
		static $row_class = 'wpsc-row';
		$row_class        = ( '' === $row_class ? ' class="alternate"' : '' );

		echo '<tr class="wpsc-row' . ( '' !== $row_class ? ' alternate' : '' ) . '" id="wpsc-row-' . esc_attr( $item['id'] ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}


	function prepare_items() {
		global $wpdb;

		$per_page = 20;
		$results  = array();

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$table_name = $wpdb->prefix . 'spellcheck_grammar';
		$search     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		if ( '' !== $search ) {
			$results = $wpdb->get_results( $wpdb->prepare( 'SELECT a.*, b.post_title FROM `wp_spellcheck_grammar` a JOIN wp_posts b ON a.page_id = b.id WHERE b.post_title LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' ), OBJECT );
		} else {
			$search = isset( $_GET['s-top'] ) ? sanitize_text_field( wp_unslash( $_GET['s-top'] ) ) : '';
			if ( '' !== $search ) {
				$results = $wpdb->get_results( $wpdb->prepare( 'SELECT a.*, b.post_title FROM `wp_spellcheck_grammar` a JOIN wp_posts b ON a.page_id = b.id WHERE b.post_title LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' ), OBJECT );
			} else {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'spellcheck_grammar'. Query contains no user input.
				$results = $wpdb->get_results( 'SELECT * FROM ' . $table_name . ' ORDER BY grammar DESC', OBJECT );
			}
		}

		$data    = array();
		$results = ( isset( $results ) && is_array( $results ) ) ? $results : array();

		foreach ( $results as $word ) {
			array_push(
				$data,
				array(
					'id'      => $word->id,
					'page_id' => $word->page_id,
					'grammar' => $word->grammar,
				)
			);
		}

		function usort_reorder( $a, $b ) {
			$orderby = ( ! empty( sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'word';
			$order   = ( ! empty( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'asc';

			$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
			return ( 'asc' === $order ) ? $result : -$result;
		}
		// usort($data, 'usort_reorder');

		$current_page = $this->get_pagenum();
		$total_items  = count( $data );
		$data         = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
		$this->items  = $data;

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}
}

/**
 * Enqueue styles via admin_enqueue_scripts hook to prevent FOUC
 * This ensures styles load in <head> before HTML body renders
 */
function wpgcx_enqueue_grammar_styles( $hook ) {
	// Only enqueue on the grammar page
	if ( isset( $_GET['page'] ) && 'wp-spellcheck-grammar.php' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
		global $wpsc_version;
		wp_enqueue_style( 'wpsc-admin-styles', plugin_dir_url( dirname( __DIR__ ) ) . 'css/admin-styles.css', array(), $wpsc_version );
		wp_enqueue_style( 'wpsc-sidebar', plugin_dir_url( dirname( __DIR__ ) ) . 'css/wpsc-sidebar.css', array(), $wpsc_version );
		wp_enqueue_style( 'wpsc-sidebar-inline', plugin_dir_url( dirname( __DIR__ ) ) . 'admin/css/sidebar-inline.css', array( 'wpsc-sidebar' ), $wpsc_version );
		wp_enqueue_style( 'wpsc-jquery-ui', plugin_dir_url( dirname( __DIR__ ) ) . 'css/wpscx-jquery-ui.css', array(), $wpsc_version );
		// Enqueue grammar results JavaScript
		wp_enqueue_script( 'wpgc-grammar-results', plugin_dir_url( dirname( __DIR__ ) ) . 'admin/js/grammar-results.js', array( 'jquery' ), $wpsc_version, true );
	}
}
add_action( 'admin_enqueue_scripts', 'wpgcx_enqueue_grammar_styles', 1 );

function wpgcx_render_results() {
	// Styles are now enqueued via admin_enqueue_scripts hook (wpgcx_enqueue_grammar_styles)
	// to prevent FOUC - they load in <head> before HTML body renders

	$start = round( microtime( true ), 5 );
	ini_set( 'memory_limit', '8192M' );
	set_time_limit( 600 );
	global $wpdb;
	global $wpscx_ent_included;
	global $wpscx_base_page_max;
	global $wpsc_version;
	$classic_active   = is_plugin_active( 'classic-editor/classic-editor.php' );
	$table_name       = $wpdb->prefix . 'spellcheck_grammar';
	$options_table    = $wpdb->prefix . 'spellcheck_grammar_options';
	$sc_options_table = $wpdb->prefix . 'spellcheck_options';
	$post_table       = $wpdb->prefix . 'posts';
	$time_estimate    = 0;

	wp_enqueue_script( 'jquery-ui-dialog' );
	// wp_enqueue_script( 'admin-js', plugin_dir_url( __FILE__ ) . '../../js/feature-request.js' );
	wp_enqueue_script( 'feature-request', plugin_dir_url( __FILE__ ) . '../../js/admin-js.js' );
	wp_enqueue_script( 'wpscx-jquery-contextMenu', plugin_dir_url( __FILE__ ) . '../../js/wpscx-jquery.contextMenu.js' );
	wp_enqueue_script( 'wpscx-jquery-ui-position', plugin_dir_url( __FILE__ ) . '../../js/wpscx-jquery.ui.position.js' );

	if ( ! isset( $_GET['action'] ) ) {
		$_GET['action'] = '';
	}
	if ( ! isset( $_GET['submit'] ) ) {
		$_GET['submit'] = '';
	}

	wpscx_set_global_vars();
	$wpgc_settings = $wpdb->get_results( "SELECT option_value FROM $options_table;" );

	$message = '';

	$options_list = $wpgc_settings;
	$total_posts  = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'post'" );

	$pro_word_count = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='pro_error_count';" );
	$pro_words      = ( ! empty( $pro_word_count ) && isset( $pro_word_count[0]->option_value ) ) ? $pro_word_count[0]->option_value : '0';

	$scan_message = 'No scan currently running';

	$scan_progress = $wpdb->get_results( "SELECT * FROM $options_table WHERE option_name='scan_running'" );

	if ( ! empty( $scan_progress ) && isset( $scan_progress[0]->option_value ) && 'true' === $scan_progress[0]->option_value && isset( $_GET['wpsc-script'] ) && 'noscript' !== $_GET['wpsc-script'] ) {
		$scan_message = '<img src="' . esc_url( wpsc_get_loading_spinner_url() ) . '" alt="Scan in Progress" class="wpsc-loading-spinner" /> A scan is currently in progress for <span class="sc-message" style="color: rgb(0, 150, 255); font-weight: bold;">' . ( isset( $options_list[7]->option_value ) ? $options_list[7]->option_value : '' ) . '</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck-grammar.php">Click here</a> to see scan results.';
	}

	$check_scan = wpgcx_check_scan_progress();

	$post_types     = get_post_types();
	$post_type_list = array();
	foreach ( $post_types as $type ) {
		if ( 'revision' !== $type && 'page' !== $type && 'slider' !== $type && 'attachment' !== $type && 'optionsframework' !== $type && 'product' !== $type && 'wpcf7_contact_form' !== $type && 'wpforms' !== $type && 'nav_menu_item' !== $type && 'gal_display_source' !== $type && 'lightbox_library' !== $type && 'wpcf7s' !== $type ) {
			array_push( $post_type_list, $type );
		}
	}

	$post_status = array( 'publish', 'draft' );

	$post_count  = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type='post' AND (post_status='draft' OR post_status='publish')" );
	$page_count  = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type='page' AND (post_status='draft' OR post_status='publish')" );
	$total_pages = $page_count;

	$post_scan_count = $options_list[5]->option_value;
	if ( $post_scan_count > $post_count ) {
		$post_scan_count = $post_count;
	}
	$total_posts = $post_count;

	$max_pages = $wpdb->get_results( "SELECT option_value FROM $sc_options_table WHERE option_name = 'pro_max_pages'" );
	$max_pages = intval( $max_pages[0]->option_value );

	if ( ! $wpscx_ent_included ) {
		$max_pages = $wpscx_base_page_max;
	}

	if ( 'noscript' !== $check_scan && isset( $_GET['wpsc-script'] ) ) {
		wp_enqueue_script( 'wpgc-results-ajax', plugin_dir_url( __FILE__ ) . '/wpgc-ajax.js', array( 'jquery' ) );
		wp_localize_script(
			'wpgc-results-ajax',
			'wpgcx_gram_ajax_object',
			array(
				'ajax_url'                           => admin_url( WPSC_ADMIN_AJAX ),
				'wpgc_start_scan_nonce'              => wp_create_nonce( 'wpgc_start_scan' ),
				'wpgc_scan_nonce'                    => wp_create_nonce( 'wpgc_scan' ),
				'wpgc_finish_scan_nonce'             => wp_create_nonce( 'wpgc_finish_scan' ),
				'wpsc_display_results_grammar_nonce' => wp_create_nonce( 'wpsc_display_results_grammar' ),
				'wpsc_get_stats_grammar_nonce'       => wp_create_nonce( 'wpsc_get_stats_grammar' ),
			)
		);
	}

	// Localize grammar results script with required data
	wp_localize_script(
		'wpgc-grammar-results',
		'wpgcGrammarResults',
		array(
			'scan_in_progress'                   => $check_scan ? true : false,
			'loading_gif_url'                    => esc_url( wpsc_get_loading_spinner_url() ),
			'ajax_url'                           => admin_url( WPSC_ADMIN_AJAX ),
			'classic_active'                     => $classic_active,
			'auto_click_enabled'                 => ( isset( $_GET['action'] ) && isset( $_GET['submit'] ) && 'check' === $_GET['action'] && 'Entire Site' === $_GET['submit'] && $classic_active ),
			'wpgc_start_scan_nonce'              => wp_create_nonce( 'wpgc_start_scan' ),
			'wpgc_scan_nonce'                    => wp_create_nonce( 'wpgc_scan' ),
			'wpgc_finish_scan_nonce'             => wp_create_nonce( 'wpgc_finish_scan' ),
			'wpsc_display_results_grammar_nonce' => wp_create_nonce( 'wpsc_display_results_grammar' ),
			'wpsc_get_stats_grammar_nonce'       => wp_create_nonce( 'wpsc_get_stats_grammar' ),
		)
	);

	/*
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Posts') {
		wpgcx_clear_results(); //Clear out results table in preparation for a new scan
		$rng_seed = rand(0,999999999);
		$time_estimate = intval($total_posts / 8);
		$time_estimate= wpscx_time_elapsed($time_estimate);
		$wpdb->update($options_table, array('option_value' => 0), array('option_name' => 'pro_error_count'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_running'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'post_running'));
		$wpdb->update($options_table, array('option_value' => 'Posts'), array('option_name' => 'last_scan_type'));
		$wpdb->update($options_table, array("option_value" => '0'), array("option_name" => "last_scan_errors"));
		$scan_message = '<img src="' . esc_url( wpsc_get_loading_spinner_url() ) . '" alt="Scan in Progress" class="wpsc-loading-spinner" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Posts</span>. Estimated time for completion is ' . $time_estimate. ' seconds. The page will automatically refresh when the scan has finished.';

		wp_enqueue_script( 'wpgc-results-ajax', plugin_dir_url( __FILE__ ) . '/wpgc-ajax.js', array('jquery') );
		wp_localize_script( 'wpgc-results-ajax', 'wpgcx_gram_ajax_object', array(
			'ajax_url' => admin_url( WPSC_ADMIN_AJAX ),
			'wpgc_scan_nonce' => wp_create_nonce( 'wpgc_scan' ),
			'wpgc_finish_scan_nonce' => wp_create_nonce( 'wpgc_finish_scan' )
		) );

		wp_schedule_single_event(time(), 'wpgcx_check_posts', array ($rng_seed, true));
	} elseif ($_GET['action'] == 'check' && $_GET['submit'] == 'Pages') {
		wpgcx_clear_results(); //Clear out results table in preparation for a new scan
		$rng_seed = rand(0,999999999);
		$time_estimate = intval($total_posts / 8);
		$time_estimate= wpscx_time_elapsed($time_estimate);
		$wpdb->update($options_table, array('option_value' => 0), array('option_name' => 'pro_error_count'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_running'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'page_running'));
		$wpdb->update($options_table, array('option_value' => 'Pages'), array('option_name' => 'last_scan_type'));
		$wpdb->update($options_table, array("option_value" => '0'), array("option_name" => "last_scan_errors"));
		$scan_message = '<img src="' . esc_url( wpsc_get_loading_spinner_url() ) . '" alt="Scan in Progress" class="wpsc-loading-spinner" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Pages</span>. Estimated time for completion is ' . $time_estimate. ' seconds. The page will automatically refresh when the scan has finished.';

		wp_enqueue_script( 'wpgc-results-ajax', plugin_dir_url( __FILE__ ) . '/wpgc-ajax.js', array('jquery') );
		wp_localize_script( 'wpgc-results-ajax', 'wpgcx_gram_ajax_object', array(
			'ajax_url' => admin_url( WPSC_ADMIN_AJAX ),
			'wpgc_scan_nonce' => wp_create_nonce( 'wpgc_scan' ),
			'wpgc_finish_scan_nonce' => wp_create_nonce( 'wpgc_finish_scan' )
		) );

		wp_schedule_single_event(time(), 'wpgcx_check_pages', array ($rng_seed, true));
	} elseif ($_GET['action'] == 'check' && $_GET['submit'] == 'Entire Site') {
		$time_estimate = intval(($total_posts + $total_pages) / 8);
		$time_estimate= wpscx_time_elapsed($time_estimate);

		$wpdb->update($options_table, array('option_value' => 0), array('option_name' => 'pro_error_count'));
		$scan_message = '<img src="' . esc_url( wpsc_get_loading_spinner_url() ) . '" alt="Scan in Progress" class="wpsc-loading-spinner" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Entire Site</span>. Estimated time for completion is ' . $time_estimate. ' seconds. The page will automatically refresh when the scan has finished.';

		wp_enqueue_script( 'wpgc-results-ajax', plugin_dir_url( __FILE__ ) . '/wpgc-ajax.js', array('jquery') );
		wp_localize_script( 'wpgc-results-ajax', 'wpgcx_gram_ajax_object', array(
			'ajax_url' => admin_url( WPSC_ADMIN_AJAX ),
			'wpgc_scan_nonce' => wp_create_nonce( 'wpgc_scan' ),
			'wpgc_finish_scan_nonce' => wp_create_nonce( 'wpgc_finish_scan' )
		) );

		$wpdb->update($options_table, array("option_value" => '0'), array("option_name" => "last_scan_errors"));
		$wpdb->update($options_table, array('option_value' => 'Entire Site'), array('option_name' => 'last_scan_type'));
		sleep(1);;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_running'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'post_running'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'page_running'));

		wp_schedule_single_event(time(), 'wpgcx_scan_site', array ($rng_seed, true));
	}*/
	// Auto-click functionality moved to grammar-results.js
	if ( ( isset( $_GET['action'] ) && isset( $_GET['submit'] ) ) && 'check' === $_GET['action'] && 'Entire Site' === $_GET['submit'] && $classic_active ) {
		// Auto-click handled by grammar-results.js via localized data
	}
	if ( ( isset( $_GET['action'] ) && isset( $_GET['submit'] ) ) && 'check' === $_GET['action'] && 'Clear Results' === $_GET['submit'] ) {
		$scan_message = 'All spell check results have been cleared';
		wpgcx_clear_results();
	}
	if ( isset( $_GET['submit'] ) && 'Stop Scans' === $_GET['submit'] ) {
		$scan_message = 'All current spell check scans have been stopped.';
		wpgcx_clear_scan();
	}

	if ( isset( $_GET['submit'] ) && 'Create Pages' === $_GET['submit'] ) {

		for ( $x = 5001; $x <= 10000; $x++ ) {
			$post_args = array(
				'post_title'   => 'Post-' . $x,
				'post_content' => 'Grammark helps improve writing style & grammar and teaches students to self-edit. Basically, it finds things that grammarians consider bad, highlights them, and suggests improvements. So writers can measure progress, it gives a "score" based on problems per document length, updated whenever the writer fixes a problem.',
				'post_status'  => 'publish',
				'post_type'    => 'post',
				'post_author'  => get_current_user_id(),
			);
		}
	}

	$list_table = new Wpgcx_Table();
	$list_table->prepare_items();
	?>
	<?php // wpscx_show_feature_window(); ?>
	<?php // wpscx_check_install_notice(); ?>
	<!-- Scan progress functions moved to grammar-results.js -->

	<div id="wpsc-dialog-confirm" title="Are you sure?" style="display: none;">
		<p>Would you like to Proceed with the changes?</p>
	</div>
	<div class="wrap wpsc-table wpsc-page-grammar">
		<h2><a href="admin.php?page=wp-spellcheck-grammar.php"><img
					src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'images/logo.png'; ?>"
					alt="WP Spell Check" /></a> <span style="position: relative; top: -8px;"> - Grammar Scan Results</span>
		</h2>
		<?php
		if ( ! $classic_active ) {
			?>
			<h3 style="color: red;">WP Spell Check Grammar scan requires WordPress Classic Editor to be installed and active.
			</h3><?php } ?>
		<div class="wpsc-scan-nav-bar">
			<a href="<?php echo esc_url( admin_url() ); ?>admin.php?page=wp-spellcheck.php" id="wpsc-scan-results"
				name="wpsc-scan-results">Spelling Errors</a>
			<a href="#" class="selected" id="wpsc-grammar" name="wpsc-grammar">Grammar</a>
			<a href="<?php echo esc_url( admin_url() ); ?>admin.php?page=wp-spellcheck-seo.php" id="wpsc-empty-fields"
				name="wpsc-empty-fields">SEO</a>
			<a href="<?php echo esc_url( admin_url() ); ?>admin.php?page=wp-spellcheck-html.php" id="wpsc-grammar"
				name="wpsc-grammar">Broken Code</a>
		</div>
		<div id="wpgc-scan-results-tab" style="margin-top: -17px;" 
		<?php
		if ( isset( $_GET['wpsc-scan-tab'] ) && 'empty' === $_GET['wpsc-scan-tab'] ) {
			echo 'class="hidden"';
		}
		?>
		>
			<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method='GET'>
				<div class="wpsc-scan-container">
					<div class="wpsc-scan-buttons" style="padding-left: 8px;">
						<h3 style="margin-bottom: 0px; padding-top: 10px;">Click on the buttons below to grammar check your
							pages and/or pots.</h3>
						<h3 style="display: inline-block;">Scan:</h3>
						<p class="submit"><input
								type="submit" name="submit" id="submit" class="button button-primary wpscScan wpscScanSite"
								value="Entire Site" 
								<?php
								if ( ! $classic_active ) {
									echo ' disabled';
								}
								?>
								>
						</p>
						<p class="submit"><input type="submit" name="submit" id="submit"
								class="button button-primary wpscScan" value="Pages" 
								<?php
								if ( 'false' === $options_list[0]->option_value || ! $classic_active ) {
									echo ' disabled';
								}
								?>
								>
						</p>
						<p class="submit"><input type="submit" name="submit" id="submit"
								class="button button-primary wpscScan" value="Posts" 
								<?php
								if ( 'false' === $options_list[1]->option_value || ! $classic_active ) {
									echo ' disabled';
								}
								?>
								>
						</p>
						<p class="submit wpsc-action-button-wrapper" style="margin-left: -11px;"><span style="position: relative; left: 15px;"> -
							</span><img
								src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../../images/clear-results.png'; ?>"
								alt="Clear Error Results"
								class="wpsc-action-icon wpsc-icon-clear-results" style="width: 20px; position: relative; top: 5px; left: 27px;" /><input type="submit"
								name="submit" id="submit" class="button button-primary wpsc-btn-clear-results" value="Clear Results"></p>
						<p class="submit wpsc-action-button-wrapper" style="margin-left: -11px;"><img
								src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../../images/see-results.png'; ?>"
								alt="See Error Results"
								class="wpsc-action-icon wpsc-icon-see-results" style="width: 20px; position: relative; top: 5px; left: 26px;" /><input type="submit"
								name="submit" id="submit" class="button button-primary wpsc-btn-see-results" value="See Scan Results"></p>
						<p class="submit wpsc-action-button-wrapper" style="margin-left: -11px;"><img
								src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../../images/stop-scans.png'; ?>"
								alt="Stop Current Scans"
								class="wpsc-action-icon wpsc-icon-stop-scans" style="width: 20px; position: relative; top: 5px; left: 25px;" /><input type="submit"
								name="submit" id="submit" class="button button-primary wpsc-btn-stop-scans" value="Stop Scans"></p>
						<p class="submit" style="margin-left: -11px;"><a
								href="/wp-admin/admin.php?page=wp-spellcheck-options.php" target="_blank"><img
									src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../../images/options.png'; ?>"
									alt="WP Spell Check Options" title="Options"
									style="width: 30px; position: relative; top: 11px; left: 20px; padding: 0px; border-radius: 25px;" /></a>
						</p>
						<!--<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" style="background-color: red;" value="Create Pages"></p>-->
					</div>
					<div style="padding: 5px; font-size: 12px;">
						<input type="hidden" name="page" value="wp-spellcheck-grammar.php">
						<input type="hidden" name="action" value="check">
						<?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);' id='wpscScanMessage'>" . esc_html( $scan_message ) . '</h3><br />'; ?>
						<?php echo "<h3 class='sc-message sc-time' style='color: rgb(0, 115, 0);'>Last scan took " . esc_html( $options_list[3]->option_value ) . '</h3><br>'; ?>
						<?php
						if ( ! $wpscx_ent_included ) {
							if ( $options_list[6]->option_value > 0 && ! $wpscx_ent_included ) {
								echo "<h3 class='sc-message' style='color: rgb(225, 0, 0);'><strong>Pro Version: </strong>Grammar and styling errors were found on other parts of your website. <a href='https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgradegram&utm_medium=grammar_scan&utm_content=" . esc_attr( $wpsc_version ) . "' target='_blank'>Click here</a> to upgrade to find and fix them all.</h3>";
							} else {
								// echo "<h3 class='sc-message' style='color: rgb(225, 0, 0);'><a href='https://www.wpspellcheck.com/product-tour/' target='_blank'>Upgrade</a> to scan all parts of your website.</h3>";
							}
						}
						?>
					</div>
				</div>
			</form>
			<?php include __DIR__ . '/../sidebar.php'; ?>
			<form id="words-list" method="get" style="width: 75%; float: left; margin-top: 10px;">
				<p class="search-box" style="position: relative; margin-top: 0.5em;">
					<label class="screen-reader-text" for="search_id-search-input">search:</label>
					<input type="search" id="search_id-search-input-top" name="s-top" value=""
						placeholder="Search for Page Names">
					<input type="submit" id="search-submit-top" class="button" value="search">
				</p>
				<input type="hidden" name="page" value="<?php echo esc_attr( isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '' ); ?>" />
				<div id="wpsc-table-results">
					<?php $list_table->display(); ?>
				</div>
				<p class="search-box" style="margin-top: 0.7em;">
					<label class="screen-reader-text" for="search_id-search-input">search:</label>
					<input type="search" id="search_id-search-input" name="s" value="" placeholder="Search for Page Names">
					<input type="submit" id="search-submit" class="button" value="search">
				</p>
			</form>

			<div
				class="wpsc-stats-summary" style="padding: 15px; clear: both; width: 72%; font-family: helvetica, sans-serif;">
				<?php echo "<h3 class='sc-message sc-type' style='color: rgb(0, 115, 0);'>Errors found on <span style='color: rgb(0, 150, 255); font-weight: bold;'>" . esc_html( $options_list[7]->option_value ) . '</span>: ' . esc_html( $options_list[6]->option_value ) . '</h3>'; ?>
				<?php echo "<h3 class='sc-message sc-page' style='color: rgb(0, 115, 0);'>Pages scanned: " . esc_html( $options_list[4]->option_value ) . '/' . esc_html( $total_pages ) . '</h3>'; ?>
				<?php echo "<h3 class='sc-message sc-post' style='color: rgb(0, 115, 0);'>Posts scanned: " . esc_html( $options_list[5]->option_value ) . '/' . esc_html( $total_posts ) . '</h3>'; ?>
			</div>
		</div>
	</div>
	<!-- Quick Edit Clone Field -->
	<table style="display: none;" role="presentation">
		<tbody>
			<tr id="wpsc-editor-row" class="wpsc-editor">
				<td colspan="4">
					<div class="wpsc-edit-content">
						<h4 style="display: inline-block;">Edit %Word%</h4>
						<input type="text" size="60" name="word_update[]" style="margin-left: 3em;" value
							class="wpsc-edit-field edit-field">
						<input type="hidden" name="edit_page_name[]" value>
						<input type="hidden" name="edit_page_type[]" value>
						<input type="hidden" name="edit_old_word[]" value>
						<input type="hidden" name="edit_old_word_id[]" value>
					</div>
					<div class="wpsc-buttons">
						<input type="button" class="button-secondary cancel alignleft wpsc-cancel-button" value="Cancel">
						<!--<input type="checkbox" name="global-edit" value="global-edit"> Apply changes to entire website-->
						<div style="clear: both;"></div>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
	<!-- Suggested Spellings Clone Field -->
	<table style="display: none;" role="presentation">
		<tbody>
			<tr id="wpsc-suggestion-row" class="wpsc-editor">
				<td colspan="4">
					<div class="wpsc-suggestion-content">
						<label><span>Suggested Spellings</span>
							<select class="wpsc-suggested-spelling-list" name="suggested_word[]">
								<option id="wpsc-suggested-spelling-1" value></option>
								<option id="wpsc-suggested-spelling-2" value></option>
								<option id="wpsc-suggested-spelling-3" value></option>
								<option id="wpsc-suggested-spelling-4" value></option>
							</select>
							<input type="hidden" name="suggest_page_name[]" value>
							<input type="hidden" name="suggest_page_type[]" value>
							<input type="hidden" name="suggest_old_word[]" value>
							<input type="hidden" name="suggest_old_word_id[]" value>
					</div>
					<div class="wpsc-buttons">
						<input type="button" class="button-secondary cancel alignleft wpsc-cancel-suggest-button"
							value="Cancel">
						<!--<input type="checkbox" name="global-suggest" value="global-suggest"> Apply changes to entire website-->
						<div style="clear: both;"></div>
					</div>
				</td>
			</tr>
		</tbody>
	</table>

	<!-- Button handlers moved to grammar-results.js -->
	<?php
}




?>