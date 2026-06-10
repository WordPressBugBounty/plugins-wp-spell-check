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
class Wphcx_Table extends WP_List_Table {



	function __construct() {
		global $status, $page;

		parent::__construct(
			array(
				'singular' => 'word',
				'plural'   => 'words',
				'ajax'     => true,
			)
		);
	}

	function column_default( $item, $column_name ) {
		return print_r( $item, true );
	}


	function column_word( $item ) {

		$actions = array(
			// 'Ignore'                  => sprintf('<input type="checkbox" class="wpsc-ignore-checkbox" name="ignore-word[]" value="' . $item['id'] . '" />Ignore'),
			'Edit' => sprintf( '<a href="post.php?post=' . $item['page_id'] . '&action=edit" id="wpsc-page-name" page="' . $item['page_id'] . '" target="_blank">Edit</a>' ),
		);
		if ( ! isset( $item['ID'] ) ) {
			$item['ID'] = '';
		}

		return sprintf(
			'%1$s%3$s',
			stripslashes( stripslashes( $item['word'] ) ),
			$item['ID'],
			$this->row_actions( $actions )
		);
	}


	function column_page_name( $item ) {
		global $wpdb;
		$link = urldecode( get_permalink( $item['page_id'] ) );
		if ( ! isset( $item['ID'] ) ) {
			$item['ID'] = '';
		}

		$actions = array(
			'View' => sprintf( '<a href="' . $link . '" id="wpsc-page-name" page="' . $item['page_id'] . '" target="_blank">View</a>' ),
		);

		return sprintf(
			'%1$s <span></span>%3$s',
			$item['page_name'],
			$item['ID'],
			$this->row_actions( $actions )
		);
	}


	function column_page_type( $item ) {

		$actions = array();
		if ( ! isset( $item['ID'] ) ) {
			$item['ID'] = '';
		}

		return sprintf(
			'%1$s <span></span>%3$s',
			$item['page_type'],
			$item['ID'],
			$this->row_actions( $actions )
		);
	}


	function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'word'      => 'Broken Code',
			'page_name' => 'Page',
			'page_type' => 'Page Type',
		);
		return $columns;
	}


	function get_sortable_columns() {
		$sortable_columns = array(
			'word'      => array( 'word', false ),
			'page_name' => array( 'page_name', false ),
			'page_type' => array( 'page_type', false ),
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
		global $wpscx_ent_included;

		$per_page = 20;

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$table_name       = $wpdb->prefix . 'spellcheck_html';
		$dictionary_table = $wpdb->prefix . 'spellcheck_dictionary';
		if ( isset( $_GET['submit'] ) && 'Find Broken Shortcodes' === $_GET['submit'] && $wpscx_ent_included ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'spellcheck_html'. User input is properly prepared via $wpdb->prepare().
			$results = $wpdb->get_results( $wpdb->prepare( 'SELECT id, word, page_name, page_type, page_id FROM ' . $table_name . ' WHERE ignore_word is false AND word LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' ) );
		} elseif ( isset( $_GET['s'] ) && '' !== $_GET['s'] ) {
			$search = sanitize_text_field( wp_unslash( $_GET['s'] ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'spellcheck_html'. User input is properly prepared via $wpdb->prepare().
			$results = $wpdb->get_results( $wpdb->prepare( 'SELECT id, word, page_name, page_type, page_id FROM ' . $table_name . ' WHERE ignore_word is false AND word LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' ) );
		} elseif ( isset( $_GET['s-top'] ) && '' !== $_GET['s-top'] ) {
			$search = sanitize_text_field( wp_unslash( $_GET['s-top'] ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'spellcheck_html'. User input is properly prepared via $wpdb->prepare().
			$results = $wpdb->get_results( $wpdb->prepare( 'SELECT id, word, page_name, page_type, page_id FROM ' . $table_name . ' WHERE ignore_word is false AND word LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe: constructed from $wpdb->prefix + hardcoded string 'spellcheck_html'. Query contains no user input.
			$results = $wpdb->get_results( $wpdb->prepare( 'SELECT id, word, page_name, page_type, page_id FROM ' . $table_name . ' WHERE ignore_word is false' ) );
		}
		$data = array();
		foreach ( $results as $word ) {
			if ( '' != $word->word ) {
				array_push(
					$data,
					array(
						'id'        => $word->id,
						'word'      => $word->word,
						'page_name' => $word->page_name,
						'page_type' => $word->page_type,
						'page_id'   => $word->page_id,
					)
				);
			}
		}

		function usort_reorder( $a, $b ) {
			if ( isset( $_REQUEST['orderby'] ) ) {
				$orderby = sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) );
			} else {
				$orderby = 'word';
			}
			if ( isset( $_REQUEST['order'] ) ) {
				$orderby = sanitize_text_field( wp_unslash( $_REQUEST['order'] ) );
			} else {
				$order = 'asc';
			}

			$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
			return ( 'asc' === $order ) ? $result : -$result;
		}
		usort( $data, 'usort_reorder' );

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

add_action( 'admin_enqueue_scripts', 'wphcx_html_results_enqueue_assets' );

function wphcx_html_results_enqueue_assets( $hook ) {
	if ( ! isset( $_GET['page'] ) || 'wp-spellcheck-html.php' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
		return;
	}

	global $wpsc_version;
	wp_enqueue_style( 'wpsc-admin-styles', plugin_dir_url( __DIR__ ) . 'css/admin-styles.css', array(), $wpsc_version );
	wp_enqueue_style( 'wpsc-sidebar', plugin_dir_url( __DIR__ ) . 'css/wpsc-sidebar.css', array(), $wpsc_version );
	wp_enqueue_style( 'wpsc-sidebar-inline', plugin_dir_url( __DIR__ ) . 'admin/css/sidebar-inline.css', array( 'wpsc-sidebar' ), $wpsc_version );
	wp_enqueue_style( 'wpsc-jquery-ui', plugin_dir_url( __DIR__ ) . 'css/wpscx-jquery-ui.css', array(), $wpsc_version );
	// Enqueue html results JavaScript
	wp_enqueue_script( 'wphc-seo-results', plugin_dir_url( __DIR__ ) . 'admin/js/broken-code-results.js', array( 'jquery' ), $wpsc_version, true );
}

function wphcx_admin_render() {
	require_once 'class-wpsc-brokencode.php';

	$start = round( microtime( true ), 5 );
	ini_set( 'memory_limit', '8192M' );
	set_time_limit( 600 );
	global $wpdb;
	global $wpscx_ent_included;
	global $wpsc_version;
	$table_name    = $wpdb->prefix . 'spellcheck_grammar';
	$options_table = $wpdb->prefix . 'spellcheck_options';
	$error_table   = $wpdb->prefix . 'spellcheck_html';
	$post_table    = $wpdb->prefix . 'posts';
	$time_estimate = 0;
	$pro_scan_msg  = '';
	$pro_error_msg = '';

	wp_enqueue_script( 'jquery-ui-dialog' );
	// wp_enqueue_script( 'admin-js', plugin_dir_url( __FILE__ ) . '../js/feature-request.js' );
	wp_enqueue_script( 'feature-request', plugin_dir_url( __FILE__ ) . '../js/admin-js.js' );
	wp_enqueue_script( 'wpscx-jquery-contextMenu', plugin_dir_url( __FILE__ ) . '../js/wpscx-jquery.contextMenu.js' );
	wp_enqueue_script( 'wpscx-jquery-ui-position', plugin_dir_url( __FILE__ ) . '../js/wpscx-jquery.ui.position.js' );

	if ( ! isset( $_GET['action'] ) ) {
		$_GET['action'] = '';
	}
	if ( ! isset( $_GET['submit'] ) ) {
		$_GET['submit'] = '';
	}
	if ( ! isset( $_GET['wpsc-script'] ) ) {
		$_GET['wpsc-script'] = '';
	}

	wpscx_set_global_vars();
	global $wpsc_settings;

	$message = '';

	$options_list = $wpsc_settings;
	$total_pages  = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'page'" );
	$total_posts  = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'post'" );

	$post_scan_count = $options_list[144]->option_value;
	if ( $post_scan_count > $total_posts ) {
		$post_scan_count = $total_posts;
	}

	$scan_message = 'No scan currently running';

	$scan_progress = $wpdb->get_results( "SELECT * FROM $options_table WHERE option_name='html_scan_running'" );

	if ( 'true' === $scan_progress[0]->option_value ) {
		$scan_message = 'sip';
	}

	$check_scan = wphcx_check_scan_progress();

	$post_status = array( 'publish', 'draft' );

	$post_count  = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type='post' AND (post_status='draft' OR post_status='publish')" );
	$page_count  = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type='page' AND (post_status='draft' OR post_status='publish')" );
	$error_count = $wpdb->get_var( "SELECT COUNT(*) FROM $error_table WHERE ignore_word = 0" );

	$max_pages = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name = 'pro_max_pages'" );
	$max_pages = intval( $max_pages[0]->option_value );

	$estimated_time = intval( ( ( $total_pages + $total_posts ) / 3.5 ) + 3 );

	$estimated_time = wpscx_time_elapsed( $estimated_time );

	if ( ! $wpscx_ent_included ) {
		$max_pages = 500;
	}

	if ( 'noscript' !== $check_scan && isset( $_GET['wpsc-script'] ) && '' !== sanitize_text_field( wp_unslash( $_GET['wpsc-script'] ) ) ) {
		wp_enqueue_script( 'wphc-results-ajax', plugin_dir_url( __FILE__ ) . '/wphc-ajax.js', array( 'jquery' ) );
		wp_localize_script(
			'wphc-results-ajax',
			'wphcx_broken_ajax_object',
			array(
				'ajax_url'                        => admin_url( WPSC_ADMIN_AJAX ),
				'wpsc_start_scan_bc_nonce'        => wp_create_nonce( 'wpsc_start_scan_bc' ),
				'wpsc_hc_scan_nonce'              => wp_create_nonce( 'wpsc_hc_scan' ),
				'wpsc_finish_html_scan_nonce'     => wp_create_nonce( 'wpsc_finish_html_scan' ),
				'wpsc_display_results_html_nonce' => wp_create_nonce( 'wpsc_display_results_html' ),
				'wpsc_get_stats_code_nonce'       => wp_create_nonce( 'wpsc_get_stats_code' ),
			)
		);
	}

	// Localize html results script with required data
	wp_localize_script(
		'wphc-seo-results',
		'wphcHtmlResults',
		array(
			'scan_in_progress'                => $check_scan ? true : false,
			'loading_gif_url'                 => esc_url( wpsc_get_loading_spinner_url() ),
			'ajax_url'                        => admin_url( WPSC_ADMIN_AJAX ),
			'auto_click_enabled'              => ( isset( $_GET['action'] ) && isset( $_GET['submit'] ) && 'check' === $_GET['action'] && 'Entire Site' === $_GET['submit'] ),
			'wpsc_start_scan_bc_nonce'        => wp_create_nonce( 'wpsc_start_scan_bc' ),
			'wpsc_hc_scan_nonce'              => wp_create_nonce( 'wpsc_hc_scan' ),
			'wpsc_finish_html_scan_nonce'     => wp_create_nonce( 'wpsc_finish_html_scan' ),
			'wpsc_display_results_html_nonce' => wp_create_nonce( 'wpsc_display_results_html' ),
			'wpsc_get_stats_code_nonce'       => wp_create_nonce( 'wpsc_get_stats_code' ),
		)
	);
	if ( ( isset( $_GET['action'] ) && isset( $_GET['submit'] ) ) && 'check' === $_GET['action'] && 'Scan Site' === $_GET['submit'] ) {
		$code_scanner = new Wpscx_Broken_Code_Scanner();

		$pro_error_count = $code_scanner->wpscx_scan_all_eps();

		$pro_error_msg = "<h3 class='wpsc-error-message'>" . $pro_error_count . ' Broken code errors were found on your website.</h3>';
	}
	if ( ( isset( $_GET['action'] ) && isset( $_GET['submit'] ) ) && 'check' === $_GET['action'] && 'Clear Results' === $_GET['submit'] ) {
		$scan_message = 'All spell check results have been cleared';
		wphcx_clear_results();
	}
	if ( isset( $_GET['submit'] ) && 'Stop Scans' === $_GET['submit'] ) {
		$scan_message = 'All current spell check scans have been stopped.';
		wphcx_clear_scan();
	}

	$list_table = new Wphcx_Table();
	$list_table->prepare_items();
	?>
	<?php // wpscx_show_feature_window(); ?>

	<div class="wrap wpsc-table wpsc-html-results-page wpsc-page-html">
		<div id="wpsc-dialog-confirm" title="Are you sure?">
			<p>Would you like to Proceed with the changes?</p>
		</div>
		<h2><a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-spellcheck-grammar.php' ) ); ?>"><img
					src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'images/logo.png'; ?>"
					alt="WP Spell Check" /></a> <span> - Broken Code Scan
				Results</span></h2>
		<div class="wpsc-scan-nav-bar">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-spellcheck.php' ) ); ?>" id="wpsc-scan-results" name="wpsc-scan-results">Spelling
				Errors</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-spellcheck-grammar.php' ) ); ?>" id="wpsc-grammar" name="wpsc-grammar">Grammar</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-spellcheck-seo.php' ) ); ?>" id="wpsc-empty-fields" name="wpsc-empty-fields">SEO</a>
			<a href="#" class="selected" id="wpsc-html" name="wpsc-html">Broken Code</a>
		</div>
		<?php if ( $wpscx_ent_included ) { ?>
			<div id="wphc-scan-results-tab" 
			<?php
			if ( isset( $_GET['wpsc-scan-tab'] ) && 'empty' === $_GET['wpsc-scan-tab'] ) {
				echo 'class="hidden"';
			}
			?>
			>
				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method='GET'>
					<div class="wpsc-scan-container">
						<div class="wpsc-scan-buttons">
							<h3>Click on the buttons below to find the broken
								code errors and broken shortcodes on your site</h3>
							<h3>This function shows all the broken shortcodes and HTML code
								displaying on pages. </h3>
							<h3>Make sure you go to your <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-spellcheck-options.php' ) ); ?>">Options
									page</a> to set up
								automatic reports to be notified if broken code is found</h3>
							<h3 class="scan-label">Scan:</h3>
							<p class="submit"><input type="submit" name="submit" id="submit"
									class="button button-primary wpscScan wpscScanSite" value="Entire Site"></p>
							<p class="submit"><input type="submit" name="submit" id="submit"
									class="button button-primary wpscScan" value="Broken HTML"></p>
							<p class="submit"><input type="submit" name="submit" id="submit"
									class="button button-primary wpscScan" value="Broken Shortcodes"></p>
							<p class="submit wpsc-action-button-wrapper"><span class="wpsc-action-dash"> -
								</span><img
									src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../images/clear-results.png'; ?>"
									alt="Clear Results Table" class="wpsc-action-icon wpsc-icon-clear-results" /><input type="submit"
									name="submit" id="submit" class="button button-primary wpsc-btn-clear-results"
									value="Clear Results"></p>
							<p class="submit wpsc-action-button-wrapper"><img
									src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../images/see-results.png'; ?>"
									alt="See Results" class="wpsc-action-icon wpsc-icon-see-results" /><input type="submit" name="submit"
									id="submit" class="button button-primary wpsc-btn-see-results" value="See Scan Results"></p>
							<p class="submit wpsc-action-button-wrapper"><img
									src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../images/stop-scans.png'; ?>"
									alt="Stop Current Scans" class="wpsc-action-icon wpsc-icon-stop-scans" /><input type="submit" name="submit"
									id="submit" class="button button-primary wpsc-btn-stop-scans" value="Stop Scans"></p>
							<p class="submit wpsc-action-button-wrapper"><a
									href="<?php echo esc_url( admin_url( 'admin.php?page=wp-spellcheck-options.php' ) ); ?>" target="_blank"><img
										src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../images/options.png'; ?>"
										alt="WP Spell Check Options" title="Options" class="wpsc-options-icon" /></a>
							</p>
						</div>
						<div class="wpsc-stats-container">
							<input type="hidden" name="page" value="wp-spellcheck-html.php">
							<input type="hidden" name="action" value="check">
							<?php if ( $scan_message == 'sip' ) { ?>

								<h3 class='sc-message' id='wpscScanMessage'><img
										src='<?php echo esc_url( wpsc_get_loading_spinner_url() ); ?>'
										alt='Scan in Progress' class='wpsc-loading-spinner' /> A scan is currently in progress for <span
										class='sc-message wpsc-site-span'>Entire site</span>. <a
										href='<?php echo esc_url( admin_url( 'admin.php?page=wp-spellcheck-html.php' ) ); ?>'>Click here</a> to see scan results.
								</h3>
							<?php } else { ?>
								<h3 class='sc-message' id='wpscScanMessage'>
									<?php echo esc_html( $scan_message ); ?>
								</h3>
							<?php } ?>
							<h3 class='sc-message sc-time'>Last scan took
								<?php echo esc_html( $options_list[27]->option_value ); ?>
							</h3><br />
							<?php
							if ( ( ( $post_count + $page_count ) > $max_pages ) & $wpscx_ent_included ) {
								?>
								<h3 class='sc-message error'>You have more than
									<?php echo esc_attr( $max_pages ); ?> Pages/Posts. <a
										href='https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=&utm_medium=bc_scan&utm_content=<?php echo esc_attr( isset( $wpsc_version ) ? $wpsc_version : '11.4' ); ?>'
										target='_blank'>Upgrade</a> to scan all of your website.
								</h3>
								<?php
							}
							if ( ! $wpscx_ent_included ) {
								?>
								<h3 class='sc-message error'><a
										href='https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgradeBroken_code&utm_medium=bc_scan&utm_content=<?php echo esc_attr( $wpsc_version ); ?>'
										target='_blank'>Upgrade</a> to scan all parts of your website.</h3>
							<?php } ?>
						</div>
					</div>
				</form>
				<?php include 'sidebar.php'; ?>
				<form id="words-list" method="get">
					<p class="search-box">
						<label class="screen-reader-text" for="search_id-search-input">search:</label>
						<input type="search" id="search_id-search-input-top" name="s-top" value=""
							placeholder="Search for Page Names">
						<input type="submit" id="search-submit-top" class="button" value="search">
					</p>
					<input type="hidden" name="page" value="<?php echo esc_attr( isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '' ); ?>" />
					<div id="wpsc-table-results">
						<?php $list_table->display(); ?>
					</div>
					<p class="search-box">
						<label class="screen-reader-text" for="search_id-search-input">search:</label>
						<input type="search" id="search_id-search-input" name="s" value="" placeholder="Search for Page Names">
						<input type="submit" id="search-submit" class="button" value="search">
					</p>
				</form>
				<div class="wpsc-stats-summary">
					<h3 class='sc-message sc-type'>Errors found on <span>Entire Site</span>:
						<?php echo esc_html( $error_count ); ?>
					</h3>
					<h3 class='sc-message sc-page'>Pages scanned:
						<?php echo esc_html( $options_list[143]->option_value ); ?> / <?php echo esc_html( $page_count ); ?>
					</h3>
					<h3 class='sc-message sc-post'>Posts scanned:
						<?php echo esc_html( $post_scan_count ); ?> / </php echo esc_html( $total_posts ); ?>
					</h3>
				</div>
			</div>
		<?php } else { ?>
			<?php if ( '' === $pro_error_msg ) { ?>
				<?php include 'sidebar.php'; ?>
				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method='GET'>
					<input type="hidden" name="page" value="wp-spellcheck-html.php">
					<input type="hidden" name="action" value="check">
					<h3>Click the button below to find out how many broken code errors are on your site</h3>
					<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary wpscScanSite"
							value="Scan Site"></p>
				</form>
				<?php
			} else {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is safe: $pro_error_msg contains hardcoded HTML string, already escaped with esc_html()
				echo htmlspecialchars_decode( esc_html( $pro_error_msg ) );
			}
			?>
			<h3><a href="https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgradeBroken_code&utm_medium=bc_scan&utm_content=<?php echo esc_attr( isset( $wpsc_version ) ? $wpsc_version : '11.4' ); ?>"
					target="_blank">Upgrade to pro</a> to find broken HTML and broken Shortcodes on your website.</h3>
			<h3 class="wpsc-examples-heading">Examples</h3>
			<h4>Broken Shortcode</h4>
			<div>Shortcodes may show up on your pages if a plugin was deactivated. It will increase the bounce rate on your
				website and hurt your SEO.<br>Example:</div>
			<div>[broken_shortcode setting=1]</div>
			<h4>Broken HTML</h4>
			<div>When HTML tags are not closed properly, HTML code could be displayed on the output of your pages. This will
				also increase the bounce rate on your website and hurt your SEO.<br>Example:</div>
			<div>&lt;h1&gt;Broken Header Title&lt;/h1&gt;
				<h3><a href="https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgradeBroken_code&utm_medium=bc_scan&utm_content=<?php echo esc_attr( isset( $wpsc_version ) ? $wpsc_version : '11.4' ); ?>"
						target="_blank">Upgrade to pro</a> to find and fix the errors. You will also get notified when errors
					show up on your website.</h3>
			<?php } ?>
		</div>
		<?php
}




?>