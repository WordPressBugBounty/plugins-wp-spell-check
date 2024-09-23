<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; }
/* Admin Classes */
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
        
        const WPSCX_SLUG = 'Post Slug';
        const WPSCX_CAT = 'Category Slug';
        const WPSCX_TAG = 'Tag Slug';
        const WPSCX_PAGE = 'Page Slug';
class Sc_Table extends WP_List_Table {
    
	function __construct() {

		global $status, $page;
		if ( ! isset( $per_page ) ) {
			$per_page = 20;
		}
                if ( ! isset( $total_items ) ) {
                    $total_items = 0;
                }
                $order_by = '';
                if ( isset( $_REQUEST['orderby'] ) ) sanitize_text_field( $order_by = $_REQUEST['orderby'] );
                $order = '';
                if ( isset( $_REQUEST['order'] ) ) sanitize_text_field( $order_by = $_REQUEST['order'] );

		parent::__construct(
			array(
				'singular' => 'word',
				'plural'   => 'words',
				'ajax'     => true,
			)
		);

				$this->set_pagination_args(
					array(
						'total_items' => $total_items,
						'per_page'    => $per_page,
						'total_pages' => ceil( $total_items / $per_page ),
						'orderby'     => ! empty( $order_by ) && '' != $order_by ? $order_by : 'title',
						'order'       => ! empty( $order ) && '' != $order ? $order : 'asc',
					)
				);
	}

	function display() {
		wp_nonce_field( 'ajax-wpsc-list-nonce', '_ajax_wpsc_list_nonce' );
                if ( isset( $this->_pagination_args['order'] ) ) { $order = esc_html( $this->_pagination_args['order'] ); } else { $order = 'asc'; }
                if ( isset( $this->_pagination_args['orderby'] ) ) { $order_by = esc_html( $this->_pagination_args['orderby'] ); } else { $order_by = 'title'; }

		echo '<input id="order" type="hidden" name="order" value="' . $order . '" />';
		echo '<input id="orderby" type="hidden" name="orderby" value="' . $order_by . '" />';

		parent::display();
	}

	function ajax_response() {
		check_ajax_referer( 'ajax-wpsc-list-nonce', '_ajax_wpsc_list_nonce' );

		$this->prepare_items();

		extract( $this->_args );
		extract( $this->_pagination_args, EXTR_SKIP );

		ob_start();
		if ( ! empty( $_REQUEST['no_placeholder'] ) ) {
			$this->display_rows();
		} else {
			$this->display_rows_or_placeholder();
		}
		$rows = ob_get_clean();

		ob_start();
		$this->print_column_headers();
		$headers = ob_get_clean();

		ob_start();
		$this->pagination( 'top' );
		$pagination_top = ob_get_clean();

		ob_start();
		$this->pagination( 'bottom' );
		$pagination_bottom = ob_get_clean();

		$response                         = array( 'rows' => $rows );
		$response['pagination']['top']    = $pagination_top;
		$response['pagination']['bottom'] = $pagination_bottom;
		$response['column_headers']       = $headers;

		if ( isset( $total_items ) ) {
			$response['total_items_i18n'] = sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) );
		}

		if ( isset( $total_pages ) ) {
			$response['total_pages']      = $total_pages;
			$response['total_pages_i18n'] = number_format_i18n( $total_pages );
		}

		die( json_encode( $response ) );
	}

	function column_default( $item, $column_name ) {
		return print_r( $item, true );
	}


	function column_word( $item ) {
		set_time_limit( 600 );
		global $wpdb;
		global $wpscx_dict_list;
		global $wpsc_settings;
		global $wpscx_ent_included;
		$table_name       = $wpdb->prefix . 'spellcheck_options';
		$dict_table       = $wpdb->prefix . 'spellcheck_dictionary';
		$language_setting = $wpsc_settings[11];
		$dict_words       = $wpscx_dict_list;

		if ( $wpscx_ent_included ) {
			$loc = dirname( __FILE__ ) . '/../../wp-spell-check-pro/admin/dict/' . $language_setting->option_value . '.pws';
		} else {
			$loc = dirname( __FILE__ ) . '/dict/' . $language_setting->option_value . '.pws';
		}
                
                if ( !isset($item['ID']) ) $item['ID'] = '';

		$file     = fopen( $loc, 'r' );
		$contents = fread( $file, filesize( $loc ) );
		fclose( $file );

		$word_list = array();
		foreach ( $dict_words as $dict_word ) {
			array_push( $word_list, $dict_word->word );
		}

		$my_dictionary = $wpdb->get_results( "SELECT * FROM $dict_table;" );

		foreach ( $my_dictionary as $dict_word ) {
			array_push( $word_list, $dict_word->word );
		}

		$contents  = str_replace( "\r\n", "\n", $contents );
		$main_list = explode( "\n", $contents );

		$word_list = array_merge( $word_list, $main_list );

		$suggestions         = array();
		$suggestions_holding = array();
                $hasSuggestions     = false;

		$start      = round( microtime( true ), 5 );
		$first_word = stripslashes( $item['word'] );
		foreach ( $word_list as $words ) {
			if ( strlen( $words ) >= strlen( $first_word ) - 2 && strlen( $words ) <= strlen( $first_word ) + 2 ) {
				similar_text( strtoupper( $first_word ), strtoupper( $words ), $percentage );
				if ( $percentage > 85.00 ) {
					if ( strtoupper( $first_word[0] ) === $first_word[0] ) {
						array_push( $suggestions_holding, array( ucfirst( $words ), $percentage ) );
					} else {
						array_push( $suggestions_holding, array( lcfirst( $words ), $percentage ) ); }
				}
			}
		}

		for ( $x = 0; $x < sizeof( (array) $suggestions_holding ); $x++ ) {
			$temp       = '';
			$temp_per   = 0;
			$temp_index = 0;
			for ( $y = 0; $y < sizeof( (array) $suggestions_holding ); $y++ ) {
				if ( $suggestions_holding[ $y ][1] > $temp_per ) {
					$temp       = $suggestions_holding[ $y ][0];
					$temp_per   = $suggestions_holding[ $y ][1];
					$temp_index = $y;
				}
			}
			if ( '' !== $temp ) {
				array_push( $suggestions, $temp );
				$suggestions_holding[ $temp_index ][1] = 0;
                                $hasSuggestions = true;
			}
			if ( sizeof( (array) $suggestions ) >= 4 ) {
				break;
			}
		}
                
                for ($x = 0; $x <= 4; $x++) {
                    if ( ! isset( $suggestions[$x] ) ) $suggestions[$x] = '';
                }

		$sorting = '';
		if ( isset( $_GET['orderby'] ) && '' != $_GET['orderby'] ) {
			$sorting .= '&orderby=' . sanitize_text_field( $_GET['orderby'] );
		}
		if ( isset( $_GET['order'] ) && '' != $_GET['order'] ) {
			$sorting .= '&order=' . sanitize_text_field( $_GET['order'] );
		}
		if ( isset( $_GET['paged'] ) && '' != $_GET['paged'] ) {
			$sorting .= '&paged=' . intval( $_GET['paged'] );
		}

		if ( 'Empty Field' === $item['word'] ) {
			if ( WPSCX_PAGE === $item['page_type'] || WPSCX_SLUG === $item['page_type'] || WPSCX_TAG === $item['page_type'] || WPSCX_CAT === $item['page_type'] ) {
				$actions = array(
					'Ignore' => sprintf( '<input type="checkbox" class="wpsc-ignore-checkbox" name="ignore-word[]" value="' . $item['id'] . '" />Ignore' ),
				);
			} elseif ("SEO Page Description" === $item['page_type'] || "SEO Page Title" === $item['page_type'] || "SEO Post Description" === $item['page_type'] || "SEO Post Title" === $item['page_type']) {
                            $actions = array(
					'Edit'   => sprintf( '<a href="#" class="wpsc-edit-seo-button" page_type="' . $item['page_type'] . '" id="wpsc-word-' . $item['word'] . '">Edit</a>' ),
					'Ignore' => sprintf( '<input type="checkbox" class="wpsc-ignore-checkbox" name="ignore-word[]" value="' . $item['id'] . '" />Ignore' ),
				);
                        } else {
				$actions = array(
					'Edit'   => sprintf( '<a href="#" class="wpsc-edit-button" page_type="' . $item['page_type'] . '" id="wpsc-word-' . $item['word'] . '">Edit</a>' ),
					'Ignore' => sprintf( '<input type="checkbox" class="wpsc-ignore-checkbox" name="ignore-word[]" value="' . $item['id'] . '" />Ignore' ),
				);
			}
		} else {
			if ( WPSCX_PAGE === $item['page_type'] || WPSCX_SLUG === $item['page_type'] || WPSCX_TAG === $item['page_type'] || WPSCX_CAT === $item['page_type'] ) {
				$actions = array(
					'Ignore'            => sprintf( '<input type="checkbox" class="wpsc-ignore-checkbox" name="ignore-word[]" value="' . $item['id'] . '" />Ignore' ),
					'Add to Dictionary' => sprintf( '<input type="checkbox" class="wpsc-add-checkbox" name="add-word[]" value="' . $item['id'] . '" />Add to Dictionary' ),
				);
			} else {
                            if ($hasSuggestions) {
				$actions = array(
					'Ignore'             => sprintf( '<input type="checkbox" class="wpsc-ignore-checkbox" name="ignore-word[]" value="' . $item['id'] . '" />Ignore' ),
					'Add to Dictionary'  => sprintf( '<input type="checkbox" class="wpsc-add-checkbox" name="add-word[]" value="' . $item['id'] . '" />Add to Dictionary' ),
					'Suggested Spelling' => sprintf( '<br><a href="#" class="wpsc-suggest-button" suggestions="' . $suggestions[0] . '-' . $suggestions[1] . '-' . $suggestions[2] . '-' . $suggestions[3] . '">Suggested Spelling</a>' ),
					'Edit'               => sprintf( '<a href="#" class="wpsc-edit-button" page_type="' . $item['page_type'] . '" id="wpsc-word-' . str_replace("%", "%%", $item['word']) . '">Edit</a>' ),
				);
                            } else {
                                $actions = array(
					'Ignore'             => sprintf( '<input type="checkbox" class="wpsc-ignore-checkbox" name="ignore-word[]" value="' . $item['id'] . '" />Ignore' ),
					'Add to Dictionary'  => sprintf( '<input type="checkbox" class="wpsc-add-checkbox" name="add-word[]" value="' . $item['id'] . '" />Add to Dictionary' ),
					'Suggested Spelling' => sprintf( '<br><span style="color: grey;">Suggested Spelling</span>' ),
					'Edit'               => sprintf( '<a href="#" class="wpsc-edit-button" page_type="' . $item['page_type'] . '" id="wpsc-word-' . str_replace("%", "%%", $item['word']) . '">Edit</a>' ),
				);
                            }
			}
		}

		return sprintf(
			'%1$s<span style="background-color:#0096ff; float: left; margin: 3px 5px 0 -30px; display: block; width: 12px; height: 12px; border-radius: 16px; opacity: 1.0;"></span>%3$s',
			stripslashes( stripslashes( $item['word'] ) ),
			$item['ID'],
			$this->row_actions( $actions )
		);
	}


	function column_page_name( $item ) {
		$start     = round( microtime( true ), 5 );
		$sql_count = 0;
                if ( !isset($item['ID']) ) $item['ID'] = '';

		global $wpdb;
		$link = urldecode( get_permalink( $item['page_id'] ) );

		if ( 'Menu Item' === $item['page_type'] ) {
			$output = '<a href="/wp-admin/nav-menus.php?action=edit&menu=' . $item['page_id'] . '" id="wpsc-page-name" page="' . $item['page_id'] . '" title="' . $item['page_name'] . '"  target="_blank">View</a>';
		} elseif ( 'Contact Form 7' === $item['page_type'] || 'Contact Form 7 Auto Response' === $item['page_type'] || 'Contact Form 7 Form' === $item['page_type'] || 'Contact Form 7 Email Notification' === $item['page_type'] ) {
			$output = '<a href="admin.php?page=wpcf7&post=' . $item['page_id'] . '&action=edit" id="wpsc-page-name" page="' . $item['page_id'] . '" title="' . $item['page_name'] . '" target="_blank">View</a>';
		} elseif ( 'Post Title' === $item['page_type'] || 'Page Title' === $item['page_type'] || 'Yoast SEO Description' === $item['page_type'] || 'All in One SEO Description' === $item['page_type'] || 'Ultimate SEO Description' === $item['page_type'] || 'SEO Description' === $item['page_type'] || 'Yoast SEO Title' === $item['page_type'] || 'All in One SEO Title' === $item['page_type'] || 'Ultimate SEO Title' === $item['page_type'] || 'SEO Title' === $item['page_type'] || WPSCX_SLUG === $item['page_type'] || WPSCX_PAGE === $item['page_type'] ) {
			$output = '<a href="/wp-admin/post.php?post=' . $item['page_id'] . '&action=edit" id="wpsc-page-name" page="' . $item['page_id'] . '" title="' . $item['page_name'] . '"  target="_blank">View</a>';
		} elseif ( 'Slider Title' === $item['page_type'] || 'Slider Caption' === $item['page_type'] || 'Smart Slider Title' === $item['page_type'] || 'Smart Slider Caption' === $item['page_type'] ) {
			$output = '<a href="/wp-admin/post.php?post=' . $item['page_id'] . '&action=edit" id="wpsc-page-name" page="' . $item['page_id'] . '" title="' . $item['page_name'] . '" target="_blank">View</a>';
		} elseif ( 'Media Title' === $item['page_type'] || 'Media Description' === $item['page_type'] || 'Media Caption' === $item['page_type'] || 'Media Alternate Text' === $item['page_type'] ) {
			$output = '<a href="/wp-admin/post.php?post=' . $item['page_id'] . '&action=edit" id="wpsc-page-name" page="' . $item['page_id'] . '" title="' . $item['page_name'] . '" target="_blank">View</a>';
		} elseif ( 'Tag Title' === $item['page_type'] || 'Tag Description' === $item['page_type'] || WPSCX_TAG === $item['page_type'] ) {
			$output = '<a href="/wp-admin/term.php?taxonomy=post_tag&tag_ID=' . $item['page_id'] . '&post_type=post" id="wpsc-page-name" page="' . $item['page_id'] . '" title="' . $item['page_name'] . '" target="_blank">View</a>';
		} elseif ( 'WooCommerce Tag Description' === $item['page_type'] || 'WooCommerce Tag Title' === $item['page_type'] ) {
				$output = '<a href="/wp-admin/term.php?taxonomy=product_tag&tag_ID=' . $item['page_id'] . '&post_type=product" id="wpsc-page-name" page="' . $item['page_id'] . '" title="' . $item['page_name'] . '" target="_blank">View</a>';
		} elseif ( 'WooCommerce Category Description' === $item['page_type'] || 'WooCommerce Category Title' === $item['page_type'] ) {
				$output = '<a href="/wp-admin/term.php?taxonomy=product_cat&tag_ID=' . $item['page_id'] . '&post_type=product" id="wpsc-page-name" page="' . $item['page_id'] . '" title="' . $item['page_name'] . '" target="_blank">View</a>';
		} elseif ( 'Post Category' === $item['page_type'] || 'Category Description' === $item['page_type'] || WPSCX_CAT === $item['page_type'] || 'Category Title' === $item['page_type'] ) {
			$output = '<a href="/wp-admin/term.php?taxonomy=category&tag_ID=' . $item['page_id'] . '&post_type=post" id="wpsc-page-name" page="' . $item['page_id'] . '" title="' . $item['page_name'] . '" target="_blank">View</a>';
		} elseif ( 'Author Nickname' === $item['page_type'] || 'Author First Name' === $item['page_type'] || 'Author Last Name' === $item['page_type'] || 'Author Biography' === $item['page_type'] || 'Author SEO Title' === $item['page_type'] || 'Author SEO Description' === $item['page_type'] || 'twitter' === $item['page_type'] || 'facebook' === $item['page_type'] || 'Author facebook' === $item['page_type'] || 'Author twitter' === $item['page_type'] || 'Author googleplus' === $item['page_type'] ) {
			$output = '<a href="/wp-admin/user-edit.php?user_id=' . $item['page_id'] . ' " id="wpsc-page-name" page="' . $item['page_id'] . '" title="' . $item['page_name'] . '" target="_blank">View</a>';
		} elseif ( 'Sitename' === $item['page_type'] || 'Site Tagline' === $item['page_type'] ) {
			$output = '<a href="/wp-admin/options-general.php" title="' . $item['page_name'] . '" target="_blank">View</a>';
		} elseif ( 'Widget Content' === $item['page_type'] ) {
			$output = '<a href="/wp-admin/widgets.php" id="wpsc-page-name" page="' . $item['page_name'] . '" title="' . $item['page_name'] . '" target="_blank">View</a>';
		} elseif ( 'Post Custom Field' === $item['page_type'] || 'Page Custom Field' === $item['page_type'] ) {
						$postmeta = $wpdb->prefix . 'postmeta';
						$result   = $wpdb->get_results( "SELECT * FROM $postmeta WHERE meta_id = " . $item['page_id'] );
						$output   = '<a href="/wp-admin/post.php?post=' . $result[0]->post_id . '&action=edit" id="wpsc-page-name" page="' . $item['page_id'] . '" title="' . $item['page_name'] . '" target="_blank">View</a>';
		} else {
			$output = '<a href="' . $link . '" id="wpsc-page-name" page="' . $item['page_id'] . '" title="' . $item['page_name'] . '" target="_blank">View</a>';
		}
		if ( ( 'WP eCommerce Product Excerpt' === $item['page_type'] || 'WP eCommerce Product Name' === $item['page_type'] || 'WooCommerce Product Excerpt' === $item['page_type'] || 'WooCommerce Product Title' === $item['page_type'] || 'WooCommerce Product Short Description' === $item['page_type'] || 'WooCommerce Category Title' === $item['page_type'] || 'WooCommerce Category Description' === $item['page_type'] || 'WooCommerce Tag Title' === $item['page_type'] || 'WooCommerce Tag Description' === $item['page_type'] || 'WooCommerce Product Name' === $item['page_type'] || 'Page Title' === $item['page_type'] || 'Post Title' === $item['page_type'] || 'Yoast SEO Page Description' === $item['page_type'] || 'All in One SEO Page Description' === $item['page_type'] || 'Ultimate SEO Page Description' === $item['page_type'] || 'SEO Page Description' === $item['page_type'] || 'Yoast SEO Page Title' === $item['page_type'] || 'All in One SEO Page Title' === $item['page_type'] || 'Ultimate SEO Page Title' === $item['page_type'] || 'SEO Page Title' === $item['page_type'] || 'Yoast SEO Post Description' === $item['page_type'] || 'All in One SEO Post Description' === $item['page_type'] || 'Ultimate SEO Post Description' === $item['page_type'] || 'SEO Post Description' === $item['page_type'] || 'Yoast SEO Post Title' === $item['page_type'] || 'All in One SEO Post Title' === $item['page_type'] || 'Ultimate SEO Post Title' === $item['page_type'] || 'SEO Post Title' === $item['page_type'] || 'Yoast SEO Media Description' === $item['page_type'] || 'All in One SEO Media Description' === $item['page_type'] || 'Ultimate SEO Media Description' === $item['page_type'] || 'SEO Media Description' === $item['page_type'] || 'Yoast SEO Media Title' === $item['page_type'] || 'All in One SEO Media Title' === $item['page_type'] || 'Ultimate SEO Media Title' === $item['page_type'] || 'SEO Media Title' === $item['page_type'] ) && 'Empty Field' === $item['word'] ) {
			$output = '<a href="/wp-admin/post.php?post=' . $item['page_id'] . '&action=edit" id="wpsc-page-name" page="' . $item['page_id'] . '" title="' . $item['page_name'] . '" target="_blank">View</a>';
		}

		$actions = array(
			'View' => sprintf( $output ),
		);

		return sprintf(
			'%1$s <span style="color:silver"></span>%3$s',
			$item['page_name'],
			$item['ID'],
			$this->row_actions( $actions )
		);
	}


	function column_page_type( $item ) {

		$actions = array();
                if ( !isset($item['ID']) ) $item['ID'] = '';

		return sprintf(
			'%1$s <span style="color:silver"></span>%3$s',
			$item['page_type'],
			$item['ID'],
			$this->row_actions( $actions )
		);
	}

	function column_count( $item ) {

		$actions = array();
                if ( !isset($item['ID']) ) $item['ID'] = '';
                if ( !isset($item['count']) ) $item['count'] = '';

		return sprintf(
			'%1$s <span style="color:silver"></span>%3$s',
			$item['count'],
			$item['ID'],
			$this->row_actions( $actions )
		);
	}


	function get_columns($scanType = null) {
		global $wpscx_ent_included;
                
		if ( 'SEO' == $scanType ) {
			$columns = array(
				'cb'        => '<input type="checkbox" />',
				'word'      => 'SEO Empty Field',
				'page_name' => 'Page',
				'page_type' => 'Page Type',
			);
		} else {
			if ( $wpscx_ent_included ) {
					$columns = array(
						'cb'        => '<input type="checkbox" />',
						'word'      => 'Misspelled Words',
						'page_name' => 'Page',
						'page_type' => 'Page Type',
						'count'     => 'Count',
					);
			} else {
					$columns = array(
						'cb'        => '<input type="checkbox" />',
						'word'      => 'Misspelled Words',
						'page_name' => 'Page',
						'page_type' => 'Page Type',
					);
			}
		}
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

		echo '<tr class="wpsc-row" id="wpsc-row-' . esc_attr( $item['id'] ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}


	function prepare_items( $scan_finished = false ) {
		$start = round( microtime( true ), 5 );

		global $wpdb;
		global $wpscx_ent_included;
                if ( isset( $_REQUEST['orderby'] ) ) { $orderby = sanitize_text_field( $_REQUEST['orderby'] ); } else { $orderby = 'undefined'; }
                if ( isset( $_REQUEST['order'] ) ) { $order = sanitize_text_field( $_REQUEST['order'] ); } else { $order = 'desc'; }

		$per_page = 20;

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$table_name       = $wpdb->prefix . 'spellcheck_words';
		$dictionary_table = $wpdb->prefix . 'spellcheck_dictionary';

				$end = round( microtime( true ), 5 );
				$start = round( microtime( true ), 5 );

		if ( $scan_finished ) {
			if ( $wpscx_ent_included ) {
				$results = $wpdb->get_results( 'SELECT c.id, c.word, c.page_type, c.page_name, c.page_id, c2.cnt FROM (SELECT * FROM ' . $table_name . ' LIMIT 25000) AS c JOIN (SELECT CAST(word as BINARY) as word_cs, COUNT(*) as cnt FROM ' . $table_name . ' GROUP BY word_cs) as c2 ON (c2.word_cs = c.word) WHERE ignore_word is false ORDER BY c2.cnt DESC LIMIT 500;', OBJECT );
			} else {
				$results = $wpdb->get_results( 'SELECT c.id, c.word, c.page_type, c.page_name, c.page_id, c2.cnt FROM (SELECT * FROM ' . $table_name . ' LIMIT 25000) AS c JOIN (SELECT word, COUNT(*) as cnt FROM ' . $table_name . ' GROUP BY word) as c2 ON (c2.word = c.word) WHERE ignore_word is false ORDER BY c.id DESC LIMIT 50;', OBJECT );
			}
		} elseif ( isset( $_GET['s'] ) && '' !== $_GET['s'] ) {
                        $search = stripcslashes( $_GET['s'] );
                        $results = $wpdb->get_results( $wpdb->prepare( 'SELECT c.id, c.word, c.page_type, c.page_name, c.page_id, c2.cnt FROM ' . $table_name . ' AS c JOIN (SELECT CAST(word as BINARY) as word_cs, COUNT(*) as cnt FROM ' . $table_name . ' GROUP BY word_cs) as c2 ON (c2.word_cs = c.word) WHERE ignore_word is false AND word LIKE %s ORDER BY c2.cnt DESC;', '%' . $wpdb->esc_like( $search ) . '%' ), OBJECT );
		} elseif ( isset( $_GET['s-top'] ) && '' !== $_GET['s-top'] ) {
                        $search = stripcslashes( $_GET['s-top'] );
                        $results = $wpdb->get_results( $wpdb->prepare( 'SELECT c.id, c.word, c.page_type, c.page_name, c.page_id, c2.cnt FROM ' . $table_name . ' AS c JOIN (SELECT CAST(word as BINARY) as word_cs, COUNT(*) as cnt FROM ' . $table_name . ' GROUP BY word_cs) as c2 ON (c2.word_cs = c.word) WHERE ignore_word is false AND word LIKE %s ORDER BY c2.cnt DESC;', '%' . $wpdb->esc_like( $search ) . '%' ), OBJECT );
		} else {
			if ( $wpscx_ent_included ) {
				//$results = $wpdb->get_results( 'SELECT c.id, c.word, c.page_type, c.page_name, c.page_id, c2.cnt FROM ' . $table_name . ' AS c JOIN (SELECT CAST(word as BINARY) as word_cs, COUNT(*) as cnt FROM ' . $table_name . ' GROUP BY word_cs) as c2 ON (c2.word_cs = c.word) WHERE ignore_word is false ORDER BY c2.cnt DESC;', OBJECT );
                                $results = $wpdb->get_results( 'SELECT c.id, c.word, c.page_type, c.page_name, c.page_id, c2.cnt FROM (SELECT * FROM ' . $table_name . ' LIMIT 100000) AS c JOIN (SELECT CAST(word as BINARY) as word_cs, COUNT(*) as cnt FROM ' . $table_name . ' GROUP BY word_cs) as c2 ON (c2.word_cs = c.word) WHERE ignore_word is false ORDER BY c2.cnt DESC;', OBJECT );
			} else {
				//$results = $wpdb->get_results( 'SELECT c.id, c.word, c.page_type, c.page_name, c.page_id, c2.cnt FROM ' . $table_name . ' AS c JOIN (SELECT word, COUNT(*) as cnt FROM ' . $table_name . ' GROUP BY word) as c2 ON (c2.word = c.word) WHERE ignore_word is false ORDER BY c.id DESC;', OBJECT );
                                $results = $wpdb->get_results( 'SELECT c.id, c.word, c.page_type, c.page_name, c.page_id, c2.cnt FROM (SELECT * FROM ' . $table_name . ' LIMIT 100000) AS c JOIN (SELECT word, COUNT(*) as cnt FROM ' . $table_name . ' GROUP BY word) as c2 ON (c2.word = c.word) WHERE ignore_word is false ORDER BY c.id DESC;', OBJECT );
			}
		}

				$end = round( microtime( true ), 5 );
				$start = round( microtime( true ), 5 );

		$data = array();
		foreach ( $results as $word ) {
				array_push(
					$data,
					array(
						'id'        => $word->id,
						'word'      => $word->word,
						'page_name' => $word->page_name,
						'page_type' => $word->page_type,
						'page_id'   => $word->page_id,
						'count'     => $word->cnt,
					)
				);
		}

				$end = round( microtime( true ), 5 );
				$start = round( microtime( true ), 5 );

		function usort_reorder( $a, $b ) {
			$orderby = ( ! empty( $orderby ) ) ? $orderby : 'word';
			$order   = ( ! empty( $order ) ) ? $order : 'asc';

			$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
			return ( 'asc' === $order ) ? $result : -$result;
		}
		function usort_reorder_default( $a, $b ) {
                    $count_a = intval($a['count']);
                    $count_b = intval($b['count']);
                    //if (($count_a == 11 || $count_a == 17) && ($count_b == 11 || $count_b == 17)) echo "Comparing: $count_a and $count_b - " . $count_a - $count_b . "<br />"; // Debugging output
                    return $count_b - $count_a;
		}

		if ( ! empty( $orderby ) && $orderby !== 'undefined' ) {
			usort( $data, 'usort_reorder' );
		} else {
			if ( $wpscx_ent_included ) {
				usort( $data, 'usort_reorder_default' );
			}
		}
                                
                if ( isset( $_GET['paged'] ) && $_GET['paged'] != 'undefined' ) { $paged = $_GET['paged']; } else { $paged = 1; }
                
                //print_r(array_slice( $data, ( ( 0 ) * $per_page ), $per_page )); echo "<br>";
                //echo "Ent Included: $wpscx_ent_included <br>";

		$current_page = intval( $paged );
		$total_items  = count( $data );
		//$data         = array_slice( $data, ( ( $current_page ) * $per_page * -1 ), $per_page );
                $start_index = ($current_page - 1) * $per_page;
                $data = array_slice($data, $start_index, $per_page);
		$this->items  = $data;
                
                //echo "Current Page: $current_page <br>";
                //echo "Per Page: $per_page <br>";

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => floor( $total_items / $per_page ),
			)
		);
	}

	function prepare_empty_items() {

		global $wpdb;

		$per_page = 20;

		$columns  = $this->get_columns("SEO");
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );


		$table_name       = $wpdb->prefix . 'spellcheck_empty';
		$dictionary_table = $wpdb->prefix . 'spellcheck_dictionary';
		if ( isset( $_GET['s'] ) && '' !== $_GET['s'] ) {
                        $search = stripcslashes( $_GET['s'] );
                        $results = $wpdb->get_results( $wpdb->prepare( 'SELECT id, word, page_name, page_type, page_id FROM ' . $table_name . ' WHERE ignore_word is false AND page_name LIKE %s' , '%' . $wpdb->esc_like( $search ) . '%') );
		} elseif ( isset( $_GET['s-top'] ) && '' !== $_GET['s-top'] ) {
                        $search = stripcslashes( $_GET['s-top'] );
                        $results = $wpdb->get_results( $wpdb->prepare( 'SELECT id, word, page_name, page_type, page_id FROM ' . $table_name . ' WHERE ignore_word is false AND page_name LIKE %s' , '%' . $wpdb->esc_like( $search ) . '%') );
		} else {
			$results = $wpdb->get_results( 'SELECT id, word, page_name, page_type, page_id FROM ' . $table_name . ' WHERE ignore_word is false', OBJECT );
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

		function usort_empty_reorder( $a, $b ) {
			$orderby = ( ! empty( $orderby ) ) ? $orderby : 'word';
			$order   = ( ! empty( $order ) ) ? $order : 'asc';

			$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
			return ( 'asc' === $order ) ? $result : -$result;
		}
		usort( $data, 'usort_empty_reorder' );
                
                if ( isset( $_GET['paged'] ) && $_GET['paged'] != 'undefined' ) { $paged = $_GET['paged']; } else { $paged = 1; }

		$total_items  = count( $data );
		$data         = array_slice( $data, ( ( $paged - 1 ) * $per_page ), $per_page );
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

/* Admin Functions */

function wpscx_admin_render() {
		global $wpsc_version;
		global $wp_version;
		$wpsc_api = 'https://www.wpspellcheck.com/api/error-report.php';
                $mass_edit_message = '';
	$log_debug    = true; //Enables debugging log
		$utils    = new Wpscx_Results_Utils;
		set_time_limit( 600 );

		wp_enqueue_script( 'jquery-ui-dialog' );
	wp_enqueue_script( 'admin-js', plugin_dir_url( __FILE__ ) . '../js/feature-request.js' );
	wp_enqueue_script( 'feature-request', plugin_dir_url( __FILE__ ) . '../js/admin-js.js' );
		wp_enqueue_script( 'jquery.contextMenu', plugin_dir_url( __FILE__ ) . '../js/jquery.contextMenu.js' );
	wp_enqueue_script( 'jquery.ui.position', plugin_dir_url( __FILE__ ) . '../js/jquery.ui.position.js' );
		wp_enqueue_style( 'wpsc-admin-styles', plugin_dir_url( __DIR__ ) . 'css/admin-styles.css' );
		wp_enqueue_style( 'wpsc-sidebar', plugin_dir_url( __DIR__ ) . 'css/wpsc-sidebar.css' );
		wp_enqueue_style( 'wpsc-jquery-ui', plugin_dir_url( __DIR__ ) . 'css/jquery-ui.css' );

	$start = round( microtime( true ), 5 );
	ini_set( 'memory_limit', '8192M' );
	set_time_limit( 600 );
	global $wpdb;
	global $wpscx_ent_included;
	global $wpscx_base_page_max;
	$table_name     = $wpdb->prefix . 'spellcheck_words';
	$empty_table    = $wpdb->prefix . 'spellcheck_empty';
	$options_table  = $wpdb->prefix . 'spellcheck_options';
	$estimated_time = 6;

	$sql_count         = 0;
	$total_smartslider = 0;
	$total_huge_it     = 0;

	$message        = '';
		$show_popup = false;

	$settings = $wpdb->get_results( 'SELECT option_name, option_value FROM ' . $options_table );
	$sql_count++;

	$max_pages = intval( $settings[138]->option_value );

	if ( ! $wpscx_ent_included ) {
		$max_pages = $wpscx_base_page_max;
	}

		if ( isset( $_GET['submit'] ) && 'Stop Scans' === $_GET['submit'] ) {
			$message = 'All current spell check scans have been stopped.';
			wpscx_clear_scan();
		}
		if ( isset( $_GET['submit-empty'] ) && 'Stop Scans' === $_GET['submit-empty'] ) {
			$message = 'All current empty field scans have been stopped.';
			wpscx_clear_empty_scan();
		}

	if ( $settings[4]->option_value || $settings[12]->option_value || $settings[18]->option_value ) {
		$check_pages = 'true';
	} else {
		$check_pages = 'false';
	}
	if ( $settings[5]->option_value || $settings[13]->option_value || $settings[19]->option_value ) {
		$check_posts = 'true';
	} else {
		$check_posts = 'false';
	}
	$check_menus             = $settings[7]->option_value;
	$page_titles             = $settings[12]->option_value;
	$post_titles             = $settings[13]->option_value;
	$tags                    = $settings[14]->option_value;
	$categories              = $settings[15]->option_value;
	$seo_desc                = $settings[16]->option_value;
	$seo_titles              = $settings[17]->option_value;
	$page_slugs              = $settings[18]->option_value;
	$post_slugs              = $settings[19]->option_value;
	$check_sliders           = $settings[30]->option_value;
	$check_media             = $settings[31]->option_value;
	$check_ecommerce         = $settings[36]->option_value;
	$check_cf7               = $settings[37]->option_value;
	$check_tag_desc          = $settings[38]->option_value;
	$check_tag_slug          = $settings[39]->option_value;
	$check_cat_desc          = $settings[40]->option_value;
	$check_cat_slug          = $settings[41]->option_value;
	$check_custom            = $settings[42]->option_value;
	$check_authors           = $settings[44]->option_value;
	$check_authors_empty     = $settings[46]->option_value;
	$check_authors_empty     = $settings[47]->option_value;
	$check_menu_empty        = $settings[48]->option_value;
	$check_page_titles_empty = $settings[49]->option_value;
	$check_post_titles_empty = $settings[50]->option_value;
	$check_tag_desc_empty    = $settings[51]->option_value;
	$check_cat_desc_empty    = $settings[52]->option_value;
	$check_page_seo_empty    = $settings[53]->option_value;
	$check_post_seo_empty    = $settings[54]->option_value;
	$check_media_seo_empty   = $settings[55]->option_value;
	$check_media_empty       = $settings[56]->option_value;
	$check_ecommerce_empty   = $settings[57]->option_value;
	$check_widgets           = $settings[147]->option_value;
		$php_error           = $settings[149]->option_value;

	$postmeta_table    = $wpdb->prefix . 'postmeta';
	$post_table        = $wpdb->prefix . 'posts';
	$it_table          = $wpdb->prefix . 'huge_itslider_images';
	$smartslider_table = $wpdb->prefix . 'nextend_smartslider_slides';

	$total_pages = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'page'" );
	$sql_count++;
	$total_posts = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'post'" );
	$sql_count++;
	$total_media = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type = 'attachment'" );
	$sql_count++;

	$post_count  = $total_pages;
	$page_count  = $total_posts;
	$media_count = $total_media;

	$end = round( microtime( true ), 5 );
	$start = round( microtime( true ), 5 );

		if ( isset( $_GET['action'] ) && 'check' === $_GET['action'] ) {

			$total_products = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type='product' AND (post_status='draft' OR post_status='publish')" );
			$total_cf7      = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type='wpcf7_contact_form' AND (post_status='draft' OR post_status='publish')" );
			$total_menu     = $wpdb->get_var( "SELECT COUNT(*) FROM $post_table WHERE post_type='nav_menu_item' AND (post_status='draft' OR post_status='publish')" );
			$total_authors  = sizeof( (array) $wpdb->get_results( "SELECT * FROM $post_table GROUP BY post_author" ) );
			$sql_count++;
			$total_tags = sizeof( get_tags() );
			$sql_count++;
			$total_tag_desc = $total_tags;
			$total_tag_slug = $total_tags;
			$total_cat      = sizeof( get_categories() );
			$sql_count++;
			$total_cat_desc  = $total_cat;
			$total_cat_slug  = $total_cat;
			$total_seo_title = sizeof( (array) $wpdb->get_results( "SELECT * FROM $postmeta_table WHERE meta_key='_yoast_wpseo_title' OR meta_key='_aioseop_title' OR meta_key='_su_title'" ) );
			$sql_count++;
			$total_seo_desc = sizeof( (array) $wpdb->get_results( "SELECT * FROM $postmeta_table WHERE meta_key='_yoast_wpseo_metadesc' OR meta_key='_aioseop_description' OR meta_key='_su_description'" ) );
			$sql_count++;

			$total_generic_slider = get_pages(
				array(
					'number'       => PHP_INT_MAX,
					'hierarchical' => 0,
					'post_type'    => 'slider',
					'post_status'  => array(
						'publish',
						'draft',
					),
				)
			);
			$sql_count++;
			$total_sliders = $total_huge_it + $total_smartslider + sizeof( (array) $total_generic_slider );

			$total_other = $total_menu + $total_authors + $total_tags + $total_tag_desc + $total_tag_slug + $total_cat + $total_cat_desc + $total_cat_slug + $total_seo_title + $total_seo_desc;

			$total_page_slugs = $total_pages;
			$total_post_slugs = $total_posts;
			$total_page_title = $total_pages;
			$total_post_title = $total_posts;

			$estimated_time = intval( ( ( $total_pages + $total_posts ) / 3.5 ) + 3 );
		}
	$scan_message = '';

	$check_scan = wpscx_check_scan_progress();

		if ( $check_scan && isset( $_GET['wpsc-script'] ) && 'noscript' !== $_GET['wpsc-script'] ) {
			wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array( 'jquery' ) );
			wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( WPSC_ADMIN_AJAX ) ) );
			sleep( 1 );
		}

	$estimated_time = wpscx_time_elapsed( $estimated_time );

	if ( isset( $_GET['action'] ) && 'check' === $_GET['action'] && isset( $_GET['submit'] ) ) {
		$start_time = time();
		$wpdb->update( $options_table, array( 'option_value' => $start_time ), array( 'option_name' => 'scan_start_time' ) );
	}

	if ( isset( $_GET['action'] ) ) {
		if ( isset( $_GET['action'] ) && isset( $_GET['submit'] ) && 'check' === $_GET['action'] && 'Entire Site' === $_GET['submit'] ) {
			?>
				<script type="text/javascript">
					jQuery(document).ready(function() {
						window.setTimeout(function() { 
							console.log("Start Entire Site Scan");
							jQuery('.wpscScanSite').click();
						}, 1000);
					});
				</script>
			<?php
		}

		if ( isset( $_GET['action'] ) && isset( $_GET['submit'] ) && 'check' === $_GET['action'] && 'Clear Results' === $_GET['submit'] ) {
			$message = 'All spell check results have been cleared';
			wpscx_clear_results( 'full' );
		}

		$end = round( microtime( true ), 5 );
		$start = round( microtime( true ), 5 );

	}
        
	if ( isset( $_GET['ignore_word'] ) ) {
		if ( '' !== $_GET['ignore_word'] && !isset( $_GET['wpsc-scan-tab'] ) ) {
			$ignore_message = $utils->ignore_word( $_GET['ignore_word'] );
		} elseif ( '' !== $_GET['ignore_word'] && isset( $_GET['wpsc-scan-tab'] ) ) {
			$ignore_message = $utils->ignore_word_empty( $_GET['ignore_word'] );
		}
	}

		if ( isset( $_GET['add_word'] ) && '' !== $_GET['add_word'] ) {
			$dict_message = $utils->add_to_dictionary( $_GET['add_word'] );
		}

	if ( isset( $_GET['old_words'] ) ) {
		if ( '' !== $_GET['old_words'] && '' !== $_GET['new_words'] && '' !== $_GET['page_types'] && '' !== $_GET['old_word_ids'] ) {
                        if ( isset( $_GET['mass_edit'] )) { $mass_edit = $_GET['mass_edit']; } else { $mass_edit = ''; }
			$message = $utils->update_word_admin( $_GET['old_words'], $_GET['new_words'], $_GET['page_names'], $_GET['page_types'], $_GET['old_word_ids'], $mass_edit );
		} elseif ( '' !== $_GET['new_words'] && '' !== $_GET['page_types'] && '' !== $_GET['old_word_ids'] ) {
			$message = $utils->update_empty_admin( $_GET['new_words'], $_GET['page_names'], $_GET['page_types'], $_GET['old_word_ids'] );
		}
	}

	$word_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE ignore_word='false'" );
	$sql_count++;

	$end = round( microtime( true ), 5 );
	$start = round( microtime( true ), 5 );

	$pro_words   = 0;
	$empty_words = 0;
	if ( ! $wpscx_ent_included ) {
		$pro_words = $settings[21]->option_value;
	}
	$total_word_count = $settings[22]->option_value;
	$literacy_factor  = $settings[64]->option_value;

	if ( $check_scan && '' === $scan_message && isset( $settings[45]->option_value ) ) {
		$last_type    = $settings[45]->option_value;
		$scan_message = '<img src="' . esc_url( plugin_dir_url( __FILE__ ) ) . 'images/loading.gif" alt="Scan in Progress" /> A scan is currently in progress for <span class="sc-message" style="color: rgb(0, 150, 255); font-weight: bold;">' . $last_type[0]->option_value . '</span>. Estimated time for completion is ' . $estimated_time . ' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
	} elseif ( '' === $scan_message ) {
		$scan_message = 'No scan currently running';
	}

	$time_of_scan = $wpdb->get_results( "SELECT option_value FROM $options_table WHERE option_name='last_scan_finished';" );
	$sql_count++;
	if ( '0' === $time_of_scan[0]->option_value ) {
		$time_of_scan = '0 Minutes';
	} else {
		$time_of_scan = $time_of_scan[0]->option_value;
		if ( '' === $time_of_scan ) {
			$time_of_scan = '0 Seconds';
		}
	}

	$scan_type = $settings[45]->option_value;

	$post_status = array( 'publish', 'draft' );

	$page_scan  = $settings[28]->option_value;
	$post_scan  = $settings[29]->option_value;
	$media_scan = $settings[32]->option_value;

	$post_scan_count = $post_scan;

	$total_words = $settings[22]->option_value;

	wp_enqueue_script( 'results-nav', plugin_dir_url( __FILE__ ) . 'results-nav.js' );

	$list_table = new Sc_Table();
	$list_table->prepare_items();

		$date = new DateTime;
		$date->modify( '+1 day' );
		$expire_date = $date->format( 'l, F d' );

	?>
		<?php wpscx_show_feature_window(); ?>
		
	<style>span.Suggested{color: black;}.wpsc-suggested-spelling-list{vertical-align: initial!important;}.wpsc-edit-content h4,.wpsc-suggestion-content h4{color: red; font-weight: bold!important; }.wpsc-suggestion-content h4{display:inline-block;}input[type=submit]{border-radius:32px!important; box-shadow: none!important; text-shadow: none!important; border: none!important;}.search-box input[type=submit] { color: white; background-color: #00A0D2; border-color: #0073AA; } #cb-select-all-1,#cb-select-all-2 { display: none; } td.word { font-size: 15px; } p.submit { display: inline-block; margin-left: 8px; } h3.sc-message { width: 49%; display: inline-block; font-weight: normal; padding-left: 8px; } .wpsc-mouseover-text-page,.wpsc-mouseover-text-post,.wpsc-mouseover-text-refresh, .wpsc-mouseover-text-change { color: black; font-size: 12px; width: 225px; display: inline-block; position: absolute; margin: -13px 0 0 -300px; padding: 5px 15px 5px 30px; border: 1px solid #008200; border-radius: 50px; opacity: 0; background: white; z-index: -100; box-shadow: #008200 1px 1px 1px; font-weight: bold; } .wpsc-row .row-actions, .wpsc-row .row-actions *{ visibility: visible!important; left: 0!important; } #current-page-selector { width: 12%; } .hidden { display: none; } .wpsc-scan-nav-bar { border-bottom: 1px solid #BBB; } .wpsc-scan-nav-bar a { text-decoration: none; margin: 5px 5px -1px 5px; padding: 8px; border: 1px solid #BBB; display: inline-block; font-weight: bold; color: black; font-size: 14px; } .wpsc-scan-nav-bar a.selected { border-bottom: 1px solid white; background: white; } #wpsc-empty-fields-tab .button-primary { background: #73019a; border-color: #51006E; text-shadow: 1px 1px #51006d; box-shadow: 0 1px 0 #51006d; } #wpsc-empty-fields-tab .button-primary:hover { background: #9100c3 } #wpsc-empty-fields-tab .button-primary:active { background: #51006d; }.wpsc-scan-buttons input#submit:active { margin-top: -7px; } #wpsc-empty-fields-tab span.wpsc-bulk { display: none; } span.wpsc-bulk { color: black; } th#count { width: 80px; }.wpsc-mouseover-text-pro-feature-2, .wpsc-mouseover-text-pro-feature-3 { color: black!important; font-size: 12px; width: 225px; display: inline-block; position: absolute; margin: -13px 0 0 -270px; padding: 5px 15px; border: 1px solid #008200; border-radius: 50px; opacity: 0; background: white; z-index: -100; box-shadow: #008200 1px 1px 1px; font-weight: bold; }
	#wpsc-error-report  { display: block; width: 330px; height: 165px; position: fixed; left: calc(50% - 150px); top: calc(50% - 150px); border: 2px solid black; border-radius: 15px; background: white; z-index: 999999; padding: 15px; }
		#wpsc-error-confirm { display: block; width: 330px; height: 200px; position: fixed; left: calc(50% - 150px); top: calc(50% - 150px); border: 2px solid black; border-radius: 15px; background: white; z-index: 999999; padding: 15px; }
		#wpsc-error-report button, #wpsc-error-confirm .wpsc-error-confirm-contact { text-decoration: none; background: #008200; color: white; padding: 5px 20px; border-radius: 15px; position: absolute; bottom: 10px; }
		#wpsc-error-report a, #wpsc-error-confirm .wpsc-error-confirm-dismiss { text-decoration: none; color: grey; padding: 5px 20px; position: absolute; bottom: 10px; }
		#wpsc-table-results table { border-radius: 5px; box-shadow: 0px 0px 10px 0px rgb(0 0 0 / 50%); }
		.wpsc-mouseover-text-scfeature, .wpsc-mouseover-text-scfeature-2, .wpsc-mouseover-text-change, .wpsc-mouseover-text-change-2 { color: black; font-size: 12px; width: 225px; display: inline-block; position: absolute; margin: 0px; padding: 0px 0px 15px 0px; border: 2px solid #008200; border-radius: 7px; opacity: 0; background: white; z-index: -100; box-shadow: 2px 2px 10px 3px rgb(0 0 0 / 75%); font-weight: bold; max-width: 205px; }
		.wpsc-button-greyout, .wpsc-button-greyout:hover { background: darkgrey!important }
	</style>
	<script>
			var scanStartTime;
			<?php if ( $check_scan ) { ?>
				var scan_in_progress = true;
			<?php } else { ?>
				var scan_in_progress = false;
			<?php } ?>
		jQuery(document).ready(function() {
			var should_submit = false;
			var shown_box = false;
			var allow_next = false;
			var pending = false;
			var admin_url = "<?php echo esc_url( admin_url() ); ?>";
						var wpsc_popup = jQuery('#wpsc-error-report');
			
			jQuery(".wpsc-edit-update-button").click( function(event) {
				if (!should_submit) event.preventDefault();
				jQuery('.wpsc-mass-edit-chk').each(function() {
					if (jQuery(this).is(":checked") && shown_box == false) {
						shown_box = true;
						jQuery( "#wpsc-mass-edit-confirm" ).dialog({
						  resizable: false,
						  height: "auto",
						  width: 400,
						  modal: true,
						  buttons: {
							"Yes": function() {
							  jQuery( this ).dialog( "close" );
							  should_submit = true;
							  //jQuery("#wpsc-edit-update-button").click();
                                                          jQuery("#wpsc-edit-update-button-hidden").trigger('click');
							},
							Cancel: function() {
							  jQuery( this ).dialog( "close" );
							}
						  }
						});
				}
				});
				if (shown_box == false) {
					should_submit = true;
					//jQuery("#wpsc-edit-update-button").click();
                                        jQuery("#wpsc-edit-update-button-hidden").trigger('click');
				}
			  } );
			  
			  jQuery(".next-page, .prev-page, .last-page, .first-page").click(function (event) {
				if (!allow_next) event.preventDefault();
					pending = false;
					button = jQuery(this).attr('href');
					
					jQuery('.wpsc-ignore-checkbox, .wpsc-add-checkbox').each(function() {
						if (jQuery(this).is(":checked")) pending = true;
					});
					
					jQuery('.wpsc-mass-edit-chk').each(function() {
						if (jQuery(this).is(":checked")) pending = true;
					});
					
					if (pending) {
						jQuery( "#wpsc-mass-edit-block" ).dialog({
						  resizable: false,
						  height: "auto",
						  width: 400,
						  modal: true,
						  buttons: {
							Cancel: function() {
							  jQuery( this ).dialog( "close" );
							},
							"Move Forward Anyway": function() {
							  jQuery( this ).dialog( "close" );
							  allow_next = true;
							  window.location.replace(button);
							}
						  }
						});
					} else {
						allow_next = true;
						window.location.replace(button);
					}
			  });
			  
						//    jQuery(".wpsc-scan-buttons input").click(function (event) {
			//	if (!allow_next) event.preventDefault();
			//		pending = false;
			//		value = jQuery(this).attr('value');
			//		button = admin_url + 'admin.php?page=wp-spellcheck.php&action=check&submit=' + value;
			//		
			//		jQuery('.wpsc-ignore-checkbox, .wpsc-add-checkbox').each(function() {
			//			if (jQuery(this).is(":checked")) pending = true;
			//		});
			//		
			//		jQuery('.wpsc-mass-edit-chk').each(function() {
			//			if (jQuery(this).attr('value') != '') pending = true;
			//		});
			//		
			//		
			//		if (pending) {
			//			jQuery( "#wpsc-mass-edit-block" ).dialog({
			//			  resizable: false,
			//			  height: "auto",
			//			  width: 400,
			//			  modal: true,
			//			  buttons: {
			//				cancel: function() {
			//				  jQuery( this ).dialog( "close" );
			//				},
			//				"Move Forward Anyway": function() {
			//				  jQuery( this ).dialog( "close" );
			//				  allow_next = true;
			//				  window.location.replace(button);
			//				}
			//			  }
			//			});
			//		} else {
			//			allow_next = true;
			//			window.location.replace(button);
			//		}
			//  });
  
						
						jQuery("#wpsc-error-confirm a").click(function(event) {
							jQuery("#wpsc-error-confirm").css('display','none');
						});
		});
				
				function wpscx_recheck_scan_temp() {
						jQuery.ajax({
								url: ajax_object,
								type: "POST",
								data: {
										action: 'results_sc',
								},
								dataType: 'html',
								success: function(response) {
										if (response == 'true') { window.setInterval(wpscx_recheck_scan_temp(), 1000 ); console.log(response); }
										else { wpscx_finish_scan_temp(); console.log(response); }
								}
						});
				}
				
				function wpscx_finish_scan_temp() {
						var scanTime2 = new Date();
						var scanEndTime2 = scanTime2.getTime();
						var scanFinal2 = ( scanEndTime2 - scanStartTime) / 1000;
						console.log("Scan Time(pre finish scan):" + scanFinal2);
						jQuery.ajax({
								url: ajax_object,
								type: "POST",
								data: {
										action: 'wpscx_display_results',
								},
								dataType: 'html',
								success: function(response) {
										var scanTime = new Date();
										var scanEndTime = scanTime.getTime();
										var scanFinal = ( scanEndTime - scanStartTime) / 1000;
										console.log("Second Ajax Success" + scanFinal);
										jQuery('.wpscScan').removeClass('wpsc-button-greyout'); //Remove button greyout
										scan_in_progress = false;

										console.log("Scan Time:" + scanFinal);
										jQuery('#wpsc-table-results').html(response.replace("null",''));
										wpscx_connect_listeners();
										jQuery('#wpscScanMessage').html("The scan has finished");

										wpscx_show_stats(scanFinal);
									}
						});
				}
				
				function wpscx_show_stats(x) {
					 jQuery.ajax({
						url: ajax_object,
						type: "POST",
						data: {
								action: 'wpscx_get_stats',
								'scantime': x,
						},
						dataType: 'json',
						success: function(response) {
							//console.log(response);
							jQuery('.sc-literacy').html("Website Literacy Factor: " + response.literacyFactor + "%");
							jQuery('.sc-type').html("Errors found on <span style='color: rgb(0, 150, 255); font-weight: bold;'>" + response.scanType + ": " + response.totalErrors);
							if (Number(response.postCount) >= Number(response.totalPosts)) { jQuery('.sc-post').html("Posts scanned: " + response.totalPosts + "/" + response.totalPosts); } else { jQuery('.sc-post').html("Posts scanned: " + response.postCount + "/" + response.totalPosts); }
							if (Number(response.pageCount) >= Number(response.totalPages)) { jQuery('.sc-page').html("Pages scanned: " + response.totalPages + "/" + response.totalPages); } else { jQuery('.sc-page').html("Pages scanned: " + response.pageCount + "/" + response.totalPages); }
							if (Number(response.mediaCount) >= Number(response.totalMedia)) { jQuery('.sc-media').html("Media Files scanned: " + response.totalMedia + "/" + response.totalMedia); } else { jQuery('.sc-media').html("Media Files scanned: " + response.mediaCount + "/" + response.totalMedia); }
							jQuery('.sc-time').html("Last scan took " + response.scanTime);
							if (response.epsCount > 0) jQuery('.sc-eps').html("<strong>Pro Version: </strong>" + response.epsCount + " Spelling Errors on other parts of your website are hurting your professional image. <a href='https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgradespellch&utm_medium=spellcheck_scan&utm_content=9.21' target='_blank'>Click here</a> to upgrade to find and fix all the errors.");
							jQuery('.next-page').click(function(e) {
								e.preventDefault();
								window.location.href = "?page=wp-spellcheck.php&paged=2";
							});
                                                        var last_page = parseInt(response.totalErrors / 20) + 1
                                                        jQuery('.last-page').click(function(e) {
								e.preventDefault();
								window.location.href = "?page=wp-spellcheck.php&paged=" + last_page;
							});
                                                        
                                                        //console.log("Last Page: " + last_page);
						},
						error: function(xhr, status, thrownError) {
							console.log(thrownError);
						}
					});
				}
	</script>
	<?php
	$end = round( microtime( true ), 5 );
	$start = round( microtime( true ), 5 );
	?>
<div id="wpsc-mass-edit-block" title="Are you sure?" style="display: none;">
  <p>You have changes pending on the current page. Please go back and click save all changes.</p>
</div>
<div id="wpsc-mass-edit-confirm" title="Are you sure?" style="display: none;">
  <p>Have you backed up your database? This will update all areas of your website that you have selected WP Spell Check to scan. Are you sure you wish to proceed with the changes?</p>
</div>
		<div class="wrap wpsc-table">
			<h2><a href="admin.php?page=wp-spellcheck.php"><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../images/logo.png'; ?>" alt="WP Spell Check" /></a> <span style="position: relative; top: -8px;"> - Scan Results</span></h2>
			<div class="wpsc-scan-nav-bar">
				<a href="#scan-results" id="wpsc-scan-results" class="selected" name="wpsc-scan-results">Spelling Errors</a>
				<a href="<?php echo esc_url( admin_url() ); ?>admin.php?page=wp-spellcheck-grammar.php" id="wpsc-grammar" name="wpsc-grammar">Grammar</a>
				<a href="<?php echo esc_url( admin_url() ); ?>admin.php?page=wp-spellcheck-seo.php" id="wpsc-empty-fields" name="wpsc-empty-fields">SEO</a>
				<a href="<?php echo esc_url( admin_url() ); ?>admin.php?page=wp-spellcheck-html.php" id="wpsc-grammar" name="wpsc-grammar">Broken Code</a>
			</div>
			<div id="wpsc-scan-results-tab" style="margin-top: -17px;" 
			<?php
			if ( isset( $_GET['wpsc-scan-tab'] ) && 'empty' === $_GET['wpsc-scan-tab'] ) {
				echo 'class="hidden"';}
			?>
			>
			<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method='GET'>
							<div style="border-radius: 5px; box-shadow: 0px 0px 10px 0px rgb(0 0 0 / 50%); background: white;">
				<div class="wpsc-scan-buttons" style="padding-left: 8px;">
									<h3 style="margin-bottom: 0px; padding-top: 10px;">Click on the buttons below to spell check various parts of your website.</h3>
				<h3 style="display: inline-block;">Scan:</h3>
				<p class="submit wpsc-mouseleave-scfeature"><input style="background-color: #ffb01f; border-color: #ffb01f; box-shadow: 0px 1px 0px #ffb01f; text-shadow: 1px 1px 1px #ffb01f; font-weight: bold;" type="submit" name="submit" id="submit wpscEntireSite" class="button button-primary wpscScan wpscScanSite" value="Entire Site" ></p>
				<p class="submit wpsc-mouseleave-scfeature"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Pages" 
				<?php
				if ( 'false' === $check_pages ) {
					echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
					></p>
				<p class="submit wpsc-mouseleave-scfeature"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Posts" 
				<?php
				if ( 'false' === $check_posts ) {
					echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
					></p>
								<span>
									<span class="wpsc-mouseover-text-scfeature"><span style="display: block;text-align: center;font-size: 14px;padding: 10px 0 10px 0;background-color: #2271b1;color: white;margin-bottom: 8px;"> This is a Pro Feature</span><span style="padding: 0 10px; display: block;">To scan all parts of your website, <a href="https://www.wpspellcheck.com/pricing/" target="_blank">Click Here</a> to upgrade to WP Spell Check Pro.</span></span>
								<p class="submit 
								<?php
								if ( ! $wpscx_ent_included ) {
									echo 'wpsc-mouseover-scfeature'; }
								?>
								"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="SEO Titles" 
								<?php
								if ( 'false' === $seo_titles || ! $wpscx_ent_included ) {
									echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled"; }
								?>
></p>
								<p class="submit 
								<?php
								if ( ! $wpscx_ent_included ) {
									echo 'wpsc-mouseover-scfeature'; }
								?>
								"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="SEO Descriptions" 
								<?php
								if ( 'false' === $seo_desc || ! $wpscx_ent_included ) {
									echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled"; }
								?>
></p>
								<p class="submit 
								<?php
								if ( ! $wpscx_ent_included ) {
									echo 'wpsc-mouseover-scfeature'; }
								?>
								"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Media Files" 
								<?php
								if ( 'false' === $check_media || ! $wpscx_ent_included ) {
									echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled"; }
								?>
></p>
								</span>
				<p class="submit wpsc-mouseleave-scfeature"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Authors" 
				<?php
				if ( 'false' === $check_authors ) {
					echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
					></p>
								<?php
								if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
									?>
									<p class="submit wpsc-mouseleave-scfeature"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Contact Form 7" 
									<?php
									if ( 'false' === $check_cf7 ) {
										echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";
									}
									?>
									></p><?php } ?>
								<span>
									<span class="wpsc-mouseover-text-scfeature-2"><span style="display: block;text-align: center;font-size: 14px;padding: 10px 0 10px 0;background-color: #2271b1;color: white;margin-bottom: 8px;"> This is a Pro Feature</span><span style="padding: 0 10px; display: block;">To scan all parts of your website, <a href="https://www.wpspellcheck.com/pricing/" target="_blank">Click Here</a> to upgrade to WP Spell Check Pro.</span></span>
				<p class="submit 
				<?php
				if ( ! $wpscx_ent_included ) {
					echo 'wpsc-mouseover-scfeature-2';}
				?>
				"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Menus" 
				<?php
				if ( 'false' === $check_menus || ! $wpscx_ent_included ) {
					echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
	></p>
				<p class="submit 
				<?php
				if ( ! $wpscx_ent_included ) {
					echo 'wpsc-mouseover-scfeature-2';}
				?>
				"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Tags" 
				<?php
				if ( 'false' === $tags || ! $wpscx_ent_included ) {
					echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
	></p>
				<p class="submit 
				<?php
				if ( ! $wpscx_ent_included ) {
					echo 'wpsc-mouseover-scfeature-2';}
				?>
				"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Categories" 
				<?php
				if ( 'false' === $categories || ! $wpscx_ent_included ) {
					echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
	></p>	
				<p class="submit 
				<?php
				if ( ! $wpscx_ent_included ) {
					echo 'wpsc-mouseover-scfeature-2';}
				?>
				"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Sliders" 
				<?php
				if ( 'false' === $check_sliders || ! $wpscx_ent_included ) {
					echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
	></p>
				<?php
				if ( is_plugin_active( 'woocommerce/woocommerce.php' ) || is_plugin_active( 'wp-e-commerce/wp-shopping-cart.php' ) ) {
					?>
					<p class="submit 
					<?php
					if ( ! $wpscx_ent_included ) {
						echo 'wpsc-mouseover-scfeature';}
					?>
					"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="WooCommerce and WP-eCommerce Products" 
					<?php
					if ( 'false' === $check_ecommerce || ! $wpscx_ent_included ) {
						echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";
					}
					?>
></p><?php } ?>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan 
				<?php
				if ( ! $wpscx_ent_included ) {
					echo 'wpsc-mouseover-scfeature';}
				?>
				" value="Widgets" 
				<?php
				if ( 'false' === $check_widgets || ! $wpscx_ent_included ) {
					echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled";}
				?>
	></p>
				<p class="submit" style="margin-left: -11px;"><span style="position: relative; left: 15px;"> - </span><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../images/clear-results.png'; ?>" alt="Clear Spellcheck Results" style="width: 20px; position: relative; top: 5px; left: 27px;" /><input type="submit" name="submit" id="submit" style="padding-left: 30px; background-color: red;" class="button button-primary" value="Clear Results"></p>
								<p class="submit" style="margin-left: -11px;"><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../images/see-results.png'; ?>" alt="See Spellcheck Results" style="width: 20px; position: relative; top: 5px; left: 26px;" /><input type="submit" name="submit" id="submit" style="padding-left: 30px; background-color: red;" class="button button-primary" value="See Scan Results"></p>
				<p class="submit" style="margin-left: -11px;"><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../images/stop-scans.png'; ?>" alt="Stop Current Scans" style="width: 20px; position: relative; top: 5px; left: 25px;" /><input type="submit" name="submit" id="submit" style="padding-left: 30px; background-color: red;" class="button button-primary" value="Stop Scans"></p>
								<p class="submit" style="margin-left: -11px;"><a href="/wp-admin/admin.php?page=wp-spellcheck-options.php" target="_blank"><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . '../images/options.png'; ?>" alt="WP Spell Check Options" title="Options" style="width: 30px; position: relative; top: 11px; left: 20px; padding: 0px; border-radius: 25px;" /></a></p>
								</span>
				<?php if ( isset( $scan_type[0]->option_value ) && ( 'Entire Site' === $scan_type[0]->option_value || 'Page Content' === $scan_type[0]->option_value || 'Post Content' === $scan_type[0]->option_value ) && 'No scan currently running' === $scan_message && $wpscx_ent_included ) { ?>
				<?php } ?>
				</div>
				<div style="padding: 5px; font-size: 12px;">
				<input type="hidden" name="page" value="wp-spellcheck.php">
				<input type="hidden" name="action" value="check">
				<?php echo "<h3 class='sc-message sc-literacy'style='color: rgb(0, 150, 255); font-size: 1.4em;'>Website Literacy Factor: " . esc_html( $literacy_factor ) . '%'; ?>
								<?php echo "<h3 class='sc-message sc-type' style='color: rgb(0, 115, 0);'>Errors found on <span style='color: rgb(0, 150, 255); font-weight: bold;'>" . esc_html( $settings[45]->option_value ) . '</span>: ' . esc_html( $word_count ) . '</h3>'; ?>
								<?php
								if ( $settings[29]->option_value >= $page_count ) {
									echo "<h3 class='sc-message sc-post' style='color: rgb(0, 115, 0);'>Posts scanned: " . esc_html( $page_count ) . '/' . esc_html( $page_count );
								} else {
									echo "<h3 class='sc-message sc-post' style='color: rgb(0, 115, 0);'>Posts scanned: " . esc_html( $settings[29]->option_value ) . '/' . esc_html( $page_count ); }
								?>
								<?php
								if ( $settings[28]->option_value >= $post_count ) {
									echo "<h3 class='sc-message sc-page' style='color: rgb(0, 115, 0);'>Pages scanned: " . esc_html( $post_count ) . '/' . esc_html( $post_count );
								} else {
									echo "<h3 class='sc-message sc-page' style='color: rgb(0, 115, 0);'>Pages scanned: " . esc_html( $settings[28]->option_value ) . '/' . esc_html( $post_count ); }
								?>
								<?php
								if ( $wpscx_ent_included ) {
									echo "<h3 class='sc-message sc-media' style='color: rgb(0, 115, 0);'>Media files scanned: " . esc_html( $settings[32]->option_value ) . '/' . esc_html( $media_count ) . '</h3>'; }
								?>
				<?php echo "<h3 class='sc-message sc-time' style='color: rgb(0, 115, 0);'>Last scan took " . esc_html( $time_of_scan ) . '</h3>'; ?>
				<?php echo "<h3 class='sc-message' id='wpscScanMessage' style='color: rgb(0, 115, 0);'>" . html_entity_decode( esc_html( $scan_message ) ) . '</h3><br />'; ?>
				<?php
				if ( ! $wpscx_ent_included ) {
					if ( $word_count > 0 && $pro_words > 0 ) {
						echo "<h3 class='sc-message sc-eps' style='color: rgb(225, 0, 0);'><strong>Pro Version: </strong>" . esc_html( $pro_words ) . " Spelling Errors on other parts of your website are hurting your professional image. <a href='https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgradespellch&utm_medium=spellcheck_scan&utm_content=" . esc_html( $wpsc_version ) . "' target='_blank'>Click here</a> to upgrade to find and fix all the errors.</h3>";
					} else {
						echo "<h3 class='sc-message sc-eps' style='color: rgb(225, 0, 0);'></h3>";
					}
				}
				?>
				</div>
							</div>
			</form>
			<?php include( 'sidebar.php' ); ?>
			<?php 
                        if ( isset( $_GET['wpsc-scan-tab'] ) ) { $scan_tab = $_GET['wpsc-scan-tab']; } else { $scan_tab = ''; }
                        if ( ( '' !== $message || isset( $ignore_message[0] ) || isset( $dict_message[0] ) || '' !== $mass_edit_message ) && 'empty' !== $scan_tab ) { ?>
				<div style="text-align: center; background-color: white; padding: 5px; margin: 15px 0; width: 74%;" class="wpsc-mesage-container">
										<?php
										if ( isset( $ignore_message[0] ) ) {
											echo "<div class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . htmlspecialchars_decode( esc_html( $ignore_message[0] ) ) . '</div>';}
										?>
					<?php
					if ( isset( $dict_message[0] ) ) {
						echo "<div class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . htmlspecialchars_decode( esc_html( $dict_message[0] ) ) . '</div>';}
					?>
					<?php
					if ( '' !== $message ) {
						echo "<div class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . htmlspecialchars_decode( esc_html( $message ) ) . '</div>';}
					?>
					<?php
					if ( '' !== $mass_edit_message ) {
						echo "<div class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . htmlspecialchars_decode( esc_html( $mass_edit_message ) ) . '</div>';}
					?>
				</div>
				<?php } ?>
			<form id="words-list" method="get" style="width: 75%; float: left; margin-top: 10px;">
				<input name="wpsc-edit-update-button-hidden" id="wpsc-edit-update-button-hidden" type="submit" value="Save all Changes" class="button button-primary" style="display:none;"/>
				<p class="search-box" style="position: relative; margin-top: 8px;">
					<label class="screen-reader-text" for="search_id-search-input">search:</label>
					<input type="search" id="search_id-search-input-top" name="s-top" value="" placeholder="Search for Misspelled Words">
					<input type="submit" id="search-submit-top" class="button" value="search">
				</p>
				<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
				<input name="wpsc-edit-update-button" class="wpsc-edit-update-button" type="submit" value="Save all Changes" class="button button-primary" style="width: 16%; padding-top: 5px; padding-bottom: 5px; margin-left: 32.5%; display: block; background: #008200; border-color: #005200; color: white; font-weight: bold; position: absolute; margin-top: 7px;"/>
				<div id="wpsc-table-results">
				<?php
				$list_table->display();
				?>
								</div>
				<?php

				$end_display = time();

				?>
				<p class="search-box" style="margin-bottom: 15px;">
					<label class="screen-reader-text" for="search_id-search-input">search:</label>
					<input type="search" id="search_id-search-input" name="s" value="" placeholder="Search for Misspelled Words">
					<input type="submit" id="search-submit" class="button" value="search">
				</p>
				<input name="wpsc-edit-update-buttom" class="wpsc-edit-update-button" type="submit" value="Save all Changes" class="button button-primary" style="width: 16%; padding-top: 5px; padding-bottom: 5px; margin-left: 31.5%; display: block;  background: #008200; border-color: #005200; color: white; font-weight: bold; position: absolute; margin-top: -31px;"/>
			</form>
			
			<div style="padding: 15px; background: white; clear: both; width: 72%; font-family: helvetica, sans-serif; border-radius: 5px; box-shadow: 0px 0px 10px 0px rgb(0 0 0 / 50%);">
				<?php echo "<h3 class='sc-message sc-type' style='color: rgb(0, 115, 0);'>Errors found on <span style='color: rgb(0, 150, 255); font-weight: bold;'>" . esc_html( $settings[45]->option_value ) . '</span>: ' . esc_html( $word_count ) . '</h3>'; ?>
				<?php
				if ( $settings[29]->option_value >= $page_count ) {
					echo "<h3 class='sc-message sc-post' style='color: rgb(0, 115, 0);'>Posts scanned: " . esc_html( $page_count ) . '/' . esc_html( $page_count );
				} else {
					echo "<h3 class='sc-message sc-post' style='color: rgb(0, 115, 0);'>Posts scanned: " . esc_html( $settings[29]->option_value ) . '/' . esc_html( $page_count ); }
				?>
								<?php
								if ( $settings[28]->option_value >= $post_count ) {
									echo "<h3 class='sc-message sc-page' style='color: rgb(0, 115, 0);'>Pages scanned: " . esc_html( $post_count ) . '/' . esc_html( $post_count );
								} else {
									echo "<h3 class='sc-message sc-page' style='color: rgb(0, 115, 0);'>Pages scanned: " . esc_html( $settings[28]->option_value ) . '/' . esc_html( $post_count ); }
								?>
				<?php
				if ( $wpscx_ent_included ) {
					echo "<h3 class='sc-message sc-media' style='color: rgb(0, 115, 0);'>Media files scanned: " . esc_html( $settings[32]->option_value ) . '/' . esc_html( $media_count ) . '</h3>'; }
				?>
				<?php
				if ( $wpscx_ent_included ) {
					$url = plugins_url( '/wp-spell-check-pro/admin/changes.php' );
					echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'><a target='_blank' href='" . esc_html( $url ) . "'>Click here</a> to view the changelog</h3>";
				} else {
					?>
											<h3 class='sc-message' style='color: rgb(70, 70, 70);'>Click here to view the changelog<span class='wpsc-mouseover-button-change' style='border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?</span><span class="wpsc-mouseover-text-change"><span style="display: block;text-align: center;font-size: 14px;padding: 10px 0 10px 0;background-color: #2271b1;color: white;margin-bottom: 8px;"> This is a Pro Feature</span><span style="padding: 0 10px; display: block;">To view the changelog, <a href="https://www.wpspellcheck.com/pricing/?utm_source=baseplugin&utm_campaign=upgradespellch&utm_medium=changelog&utm_content=9.21" target="_blank">Click Here</a> to upgrade to WP Spell Check Pro.</span></span></h3>    
										<?php
				}

				?>
			</div>
		</div>
		<!-- Empty Fields  Tab -->
		
		</div>
		<!-- Quick Edit Clone Field -->
		<table style="display: none;" role="presentation">
			<tbody>
				<tr id="wpsc-editor-row" class="wpsc-editor">
					<td colspan="4">
						<div class="wpsc-edit-content">
													<h4 style="display: inline-block;">Change <u>%Word%</u> to</h4>
							<input type="text" size="60" name="word_update[]" style="margin-left: 0.5em;" value class="wpsc-edit-field edit-field">
														<br><span class="wpsc-bulk" 
														<?php
														if ( ! $wpscx_ent_included ) {
															echo "style='color: grey;'"; }
														?>
														><input name="wpsc-mass-edit[]" class="wpsc-mass-edit-chk" type="checkbox" value="" 
														<?php
														if ( ! $wpscx_ent_included ) {
															echo 'disabled'; }
														?>
 />Apply this change to the entire website
	<?php
	if ( ! $wpscx_ent_included ) {
		echo "<span class='wpsc-mouseover-pro-feature-3' style='border-radius: 29px; color: #008200!important; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?<span class='wpsc-mouseover-text-pro-feature-3' style='color: black!important;'>This is a pro version feature. <a href='https://www.wpspellcheck.com/pricing/' target='_blank'>Click Here</a> to upgrade</span></span></span>";}
	?>
	</span>
							<input type="hidden" name="edit_page_name[]" value>
							<input type="hidden" name="edit_page_type[]" value>
							<input type="hidden" name="edit_old_word[]" value>
							<input type="hidden" name="edit_old_word_id[]" value>
						</div>
						<div class="wpsc-buttons">
							<input type="button" class="button-secondary cancel alignleft wpsc-cancel-button" value="Cancel">
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
													<label><h4>Change <u>%Word%</u> to</h4>
							<select class="wpsc-suggested-spelling-list" name="suggested_word[]">
								<option id="wpsc-suggested-spelling-1" value></option>
								<option id="wpsc-suggested-spelling-2" value></option>
								<option id="wpsc-suggested-spelling-3" value></option>
								<option id="wpsc-suggested-spelling-4" value></option>
							</select><br>
														<div 
														<?php
														if ( ! $wpscx_ent_included ) {
															echo "style='color: grey;'"; }
														?>
														><input name="wpsc-mass-edit[]" class="wpsc-mass-edit-chk" type="checkbox" value="" 
														<?php
														if ( ! $wpscx_ent_included ) {
															echo 'disabled'; }
														?>
 />Apply this change to the entire website
	<?php
	if ( ! $wpscx_ent_included ) {
		echo "<span class='wpsc-mouseover-pro-feature-2' style='border-radius: 29px; color: #008200!important; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?<span class='wpsc-mouseover-text-pro-feature-2' style='color: black!important;'>This is a pro version feature.  <a href='https://www.wpspellcheck.com/pricing/' target='_blank'>Click Here</a> to upgrade</span></span></span>";}
	?>
	</div>
							<input type="hidden" name="suggest_page_name[]" value>
							<input type="hidden" name="suggest_page_type[]" value>
							<input type="hidden" name="suggest_old_word[]" value>
							<input type="hidden" name="suggest_old_word_id[]" value>
						</div>
						<div class="wpsc-buttons">
							<input type="button" class="button-secondary cancel alignleft wpsc-cancel-suggest-button" value="Cancel">
							<div style="clear: both;"></div>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
				<script>
				jQuery('.wpscScan').click(function(event) {
							event.preventDefault();
							if (scan_in_progress) return;
							scan_in_progress = true;
							ajax_object = '<?php echo admin_url( WPSC_ADMIN_AJAX ); ?>';
							var scanType = jQuery(this).val();
							//console.log(scanType);
							var scanTime = new Date();
							scanStartTime = scanTime.getTime();
							
							jQuery('#wpscScanMessage').html('<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ); ?>images/loading.gif" alt="Scan in Progress" /> Starting New Scan');
							jQuery('.wpscScan').addClass('wpsc-button-greyout'); //Greyout buttons
									
							jQuery.ajax({
									url: ajax_object,
									timout: 7200000, // 2 Hours
									type: "POST",
									data: {
											type: scanType,
											action: 'wpscx_start_scan',
									},
									dataType: 'html',
									success: function(response) {
										jQuery('#wpscScanMessage').html(response); //update the scan message to display the scan started message
										var scanEndTime = scanTime.getTime();
										var scanFinal = ( scanEndTime - scanStartTime) / 1000;
										console.log("First Ajax Success: " + scanFinal);
										
										window.setInterval(wpscx_finish_scan_temp(), 500 );
										jQuery('tr.wpsc-row').animate({opacity: 0}, 500, function() { jQuery('tr.wpsc-row').hide(); });
										jQuery('.wpsc-mesage-container').animate({opacity: 0}, 500, function() { jQuery('.wpsc-mesage-container').hide(); });
										
										jQuery(document).ready(function() {
											var mouseover_visible = false;
											jQuery('.wpsc-mouseover-button-refresh').mouseenter(function() {
												jQuery('.wpsc-mouseover-text-refresh').css('z-index','100');
												jQuery('.wpsc-mouseover-text-refresh').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
											}).mouseleave(function() {
												var isHoveredPopup = jQuery('.wpsc-mouseover-text-refresh').filter(function() {
													return jQuery(this).is(":hover");
												});
												var isHoveredParent = jQuery(this).parent().filter(function() {
													return jQuery(this).is(":hover");
												});
												if (!isHoveredPopup && !isHoveredParent) {
													jQuery('.wpsc-mouseover-text-refresh').css('z-index','-100');
													jQuery('.wpsc-mouseover-text-refresh').animate({opacity: 0}, 400);
													mouseover_visible = false;
												}
											});
											jQuery('.wpsc-mouseover-button-refresh').click(function() {
											if (!mouseover_visible) {
												jQuery('.wpsc-mouseover-text-refresh').stop();
												jQuery('.wpsc-mouseover-text-refresh').css('z-index','100');
												jQuery('.wpsc-mouseover-text-refresh').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
											} else {
												jQuery('.wpsc-mouseover-text-refresh').css('z-index','-100');
												jQuery('.wpsc-mouseover-text-refresh').animate({opacity: 0}, 400);
												mouseover_visible = false;
											}
											});

											jQuery('.wpsc-mouseover-button-refresh').parent().mouseleave(function() {
												//console.log("Parent Container Mouseleave Triggered");
												var isHoveredPopup = jQuery('.wpsc-mouseover-text-refresh:hover').length > 0;
												var isHoveredButton = jQuery('.wpsc-mouseover-button-refresh:hover').length > 0;
												//console.log("Popup: " + isHoveredPopup + " | Button: " + isHoveredButton);

												if (!isHoveredPopup && !isHoveredButton) {
													jQuery('.wpsc-mouseover-text-refresh').css('z-index','-100');
													jQuery('.wpsc-mouseover-text-refresh').animate({opacity: 0}, 400);
													mouseover_visible = false;
												}
											});
										});
									},
									error: function(xhr, status, thrownError) {
										//console.log(thrownError);
										//console.log(xhr.responseText);
										window.setInterval(wpscx_recheck_scan_temp(), 500 );
									}
							});
						});
				</script>
	<?php
	$end = round( microtime( true ), 5 );
	$start = round( microtime( true ), 5 );
}


function wpscx_admin_render_single( $wpsc_data, $page_id ) {
	$list_table = new Sc_Table();
	$list_table->prepare_items_single( $wpsc_data, $page_id );
        $message = '';
        $ignore_message[0] = '';
        $dict_message[0] = '';
        $mass_edit_message = '';
	?>
	<style>.search-box input[type=submit] { color: white; background-color: #00A0D2; border-color: #0073AA; } #cb-select-all-1,#cb-select-all-2 { display: none; } td.word { font-size: 15px; } p.submit { display: inline-block; margin-left: 8px; } h3.sc-message { width: 49%; display: inline-block; font-weight: normal; padding-left: 8px; } .wpsc-mouseover-text-page,.wpsc-mouseover-text-post,.wpsc-mouseover-text-refresh { color: black; font-size: 12px; width: 225px; display: inline-block; position: absolute; margin: -13px 0 0 -280px; padding: 5px; border: 1px solid black; border-radius: 50px; opacity: 0; background: white; z-index: -100; box-shadow: black 1px 1px 1px; } .wpsc-row .row-actions, .wpsc-row .row-actions *{ visibility: visible!important; left: 0!important; } #current-page-selector { width: 12%; } .hidden { display: none; } .wpsc-scan-nav-bar { border-bottom: 1px solid #BBB; } .wpsc-scan-nav-bar a { text-decoration: none; margin: 5px 5px -1px 5px; padding: 8px; border: 1px solid #BBB; display: inline-block; font-weight: bold; color: black; font-size: 14px; } .wpsc-scan-nav-bar a.selected { border-bottom: 1px solid white; background: white; } #wpsc-empty-fields-tab .button-primary { background: #73019a; border-color: #51006E; text-shadow: 1px 1px #51006d; box-shadow: 0 1px 0 #51006d; } #wpsc-empty-fields-tab .button-primary:hover { background: #9100c3 } #wpsc-empty-fields-tab .button-primary:active { background: #51006d; }.wpsc-scan-buttons input#submit:active { margin-top: -7px; } #wpsc-empty-fields-tab span.wpsc-bulk { display: none; } span.wpsc-bulk { color: black; } th#count { width: 80px; }
	</style>
	<script>
		jQuery(document).ready(function() {
			var should_submit = false;
			var shown_box = false;
			var allow_next = false;
			var pending = false;
			var admin_url = "<?php echo esc_url( admin_url() ); ?>";
			
			jQuery(".wpsc-edit-update-button").click( function(event) {
				if (!should_submit) event.preventDefault();
				jQuery('.wpsc-mass-edit-chk').each(function() {
					if (jQuery(this).is(":checked") && shown_box == false) {
						shown_box = true;
						jQuery( "#wpsc-mass-edit-confirm" ).dialog({
						  resizable: false,
						  height: "auto",
						  width: 400,
						  modal: true,
						  buttons: {
							"Yes": function() {
								jQuery( this ).dialog( "close" );
							
								var old_words = '';
								jQuery('[name="edit_old_word[]"], [name="suggest_old_word[]"]').each(function() {
									if (jQuery(this).attr('value').length !== 0) {
										old_words += "old_words[]=" + jQuery(this).attr('value').replace('(','%28') + "&";
									}
								});
								
								var page_types = '';
								jQuery('[name="edit_page_type[]"], [name="suggest_page_type[]"]').each(function() {
									if (jQuery(this).attr('value').length !== 0) {
										page_types += "page_types[]=" + jQuery(this).attr('value').replace('(','%28') + "&";
									}
								});

								var new_words = '';
								jQuery('[name="word_update[]"], [name="suggested_word[]"]').each(function() {
									if (jQuery(this).attr('value').length !== 0) {
										new_words += "new_words[]=" + jQuery(this).attr('value').replace('(','%28') + "&";
									}
								});

								var ignore_words = "";
								var add_words = "";
								var mass_edit = "";
								jQuery('[name="ignore-word[]"]').each(function() {
									if (jQuery(this).attr('checked')) {
										ignore_words += "ignore_word[]=" + jQuery(this).attr('value') + "&";
									}
								});
								jQuery('[name="add-word[]"]').each(function() {
									if (jQuery(this).attr('checked')) {
										add_words += "add_word[]=" + jQuery(this).attr('value') + "&";
									}
								});
								jQuery('.wpsc-mass-edit-chk').each(function() {
									if (jQuery(this).is(':checked')) mass_edit += "mass_edit[]=" + jQuery(this).attr('value') + "&";
								});
								
								var ajaxUrl = "wpsc-update.php?" + old_word_ids + old_words + page_names + page_types + new_words + ignore_words + add_words + mass_edit;
								
								jQuery.ajax({
									url: ajaxUrl,
									type: "GET",
									success: function(response) { 
										window.top.location.reload();
									}
								});
							},
							Cancel: function() {
							  jQuery( this ).dialog( "close" );
							}
						  }
						});
				}
				});
				if (shown_box == false) {
					should_submit = true;
					jQuery("#wpsc-edit-update-button-hidden").click();
				}
			  } );
			  
			  jQuery(".next-page, .prev-page, .last-page, .first-page").click(function (event) {
				if (!allow_next) event.preventDefault();
					pending = false;
					button = jQuery(this).attr('href');
					
					jQuery('.wpsc-ignore-checkbox, .wpsc-add-checkbox').each(function() {
						if (jQuery(this).is(":checked")) pending = true;
					});
					
					jQuery('.wpsc-mass-edit-chk').each(function() {
						if (jQuery(this).is(":checked")) pending = true;
					});
					
					
					if (pending) {
						jQuery( "#wpsc-mass-edit-block" ).dialog({
						  resizable: false,
						  height: "auto",
						  width: 400,
						  modal: true,
						  buttons: {
							Cancel: function() {
							  jQuery( this ).dialog( "close" );
							},
							"Move Forward Anyway": function() {
							  jQuery( this ).dialog( "close" );
							  allow_next = true;
							  window.location.replace(button);
							}
						  }
						});
					} else {
						allow_next = true;
						window.location.replace(button);
					}
			  });
			  
			  jQuery(".wpsc-scan-buttons input").click(function (event) {
				if (!allow_next) event.preventDefault();
					pending = false;
					value = jQuery(this).attr('value');
					button = admin_url + 'admin.php?page=wp-spellcheck.php&action=check&submit=' + value;
					
					jQuery('.wpsc-ignore-checkbox, .wpsc-add-checkbox').each(function() {
						if (jQuery(this).is(":checked")) pending = true;
					});
					
					jQuery('.wpsc-mass-edit-chk').each(function() {
						if (jQuery(this).is(":checked")) pending = true;
					});
					
					
					if (pending) {
						jQuery( "#wpsc-mass-edit-block" ).dialog({
						  resizable: false,
						  height: "auto",
						  width: 400,
						  modal: true,
						  buttons: {
							cancel: function() {
							  jQuery( this ).dialog( "close" );
							},
							"Move Forward Anyway": function() {
							  jQuery( this ).dialog( "close" );
							  allow_next = true;
							  window.location.replace(button);
							}
						  }
						});
					} else {
						allow_next = true;
						window.location.replace(button);
					}
			  });
		});
	</script>
	<?php
	$end = round( microtime( true ), 5 );
	$start = round( microtime( true ), 5 );
	?>
<div id="wpsc-mass-edit-block" title="Are you sure?" style="display: none;">
  <p>You have changes pending on the current page. Please go back and click save all changes.</p>
</div>
<div id="wpsc-mass-edit-confirm" title="Are you sure?" style="display: none;">
  <p>Have you backed up your database? This will update all areas of your website that you have selected WP Spell Check to scan. Are you sure you wish to proceed with the changes?</p>
</div>
		<div class="wrap wpsc-table">
		<?php if ( ( '' !== $message || '' !== $ignore_message[0] || '' !== $dict_message[0] || '' !== $mass_edit_message ) && 'empty' !== $_GET['wpsc-scan-tab'] ) { ?>
				<div style="text-align: center; background-color: white; padding: 5px; margin: 15px 0; width: 74%;">
					<?php
					if ( '' !== $message ) {
						echo "<div class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . esc_html( $message ) . '</div>';}
					?>
					<?php
					if ( '' !== $mass_edit_message ) {
						echo "<div class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . esc_html( $mass_edit_message ) . '</div>';}
					?>
					<?php
					if ( '' !== $ignore_message[0] ) {
						echo "<div class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . esc_html( $ignore_message[0] ) . '</div>';}
					?>
					<?php
					if ( '' !== $dict_message[0] ) {
						echo "<div class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . esc_html( $dict_message[0] ) . '</div>';}
					?>
				</div>
				<?php } ?>
			<form id="words-list" method="get" style="width: 100%; margin-top: 10px;">
				<input name="wpsc-edit-update-button-hidden" id="wpsc-edit-update-button-hidden" type="submit" value="Save all Changes" class="button button-primary" style="display:none;"/>
				<p class="search-box" style="position: relative; margin-top: 8px;">
					<label class="screen-reader-text" for="search_id-search-input">search:</label>
					<input type="search" id="search_id-search-input-top" name="s-top" value="" placeholder="Search for Misspelled Words">
					<input type="submit" id="search-submit-top" class="button" value="search">
				</p>
				<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
				<input name="wpsc-edit-update-button" class="wpsc-edit-update-button" type="submit" value="Save all Changes" class="button button-primary" style="width: 15%; margin-left: 32.5%; display: block; background: #008200; border-color: #005200; color: white; font-weight: bold; position: absolute; margin-top: 7px;"/>
						<?php
						$list_table->display();
						?>
				
			<?php

			$end_display = time();

			?>
				<p class="search-box" style="margin-top: 0.7em;">
					<label class="screen-reader-text" for="search_id-search-input">search:</label>
					<input type="search" id="search_id-search-input" name="s" value="" placeholder="Search for Misspelled Words">
					<input type="submit" id="search-submit" class="button" value="search">
				</p>
				<input name="wpsc-edit-update-buttom" class="wpsc-edit-update-button" type="submit" value="Save all Changes" class="button button-primary" style="width: 15%; margin-left: 31.5%; display: block;  background: #008200; border-color: #005200; color: white; font-weight: bold; position: absolute; margin-top: -31px;"/>
			</form>
		</div>
		<!-- Empty Fields  Tab -->
		<!-- Quick Edit Clone Field -->
		<table style="display: none;" role="presentation">
			<tbody>
				<tr id="wpsc-editor-row" class="wpsc-editor">
					<td colspan="4">
						<div class="wpsc-edit-content">
							<h4 style="display: inline-block;">Change %Word% to</h4>
							<input type="text" size="60" name="word_update[]" style="margin-left: 3em;" value class="wpsc-edit-field edit-field">
							<br><span class="wpsc-bulk"><input name="wpsc-mass-edit[]" class="wpsc-mass-edit-chk" type="checkbox" value="" />Apply this change to the entire website</span>
							<input type="hidden" name="edit_page_name[]" value>
							<input type="hidden" name="edit_page_type[]" value>
							<input type="hidden" name="edit_old_word[]" value>
							<input type="hidden" name="edit_old_word_id[]" value>
						</div>
						<div class="wpsc-buttons">
							<input type="button" class="button-secondary cancel alignleft wpsc-cancel-button" value="Cancel">
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
							<label><h4>Change <u>%Word%</u> to</h4>
							<select class="wpsc-suggested-spelling-list" name="suggested_word[]">
								<option id="wpsc-suggested-spelling-1" value></option>
								<option id="wpsc-suggested-spelling-2" value></option>
								<option id="wpsc-suggested-spelling-3" value></option>
								<option id="wpsc-suggested-spelling-4" value></option>
							</select><br>
							<input name="wpsc-mass-edit[]" class="wpsc-mass-edit-chk" type="checkbox" value="" />Apply this change to the entire website
							<input type="hidden" name="suggest_page_name[]" value>
							<input type="hidden" name="suggest_page_type[]" value>
							<input type="hidden" name="suggest_old_word[]" value>
							<input type="hidden" name="suggest_old_word_id[]" value>
						</div>
						<div class="wpsc-buttons">
							<input type="button" class="button-secondary cancel alignleft wpsc-cancel-suggest-button" value="Cancel">
							<div style="clear: both;"></div>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	<?php
}

?>
