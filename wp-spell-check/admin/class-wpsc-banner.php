<?php

class Wpscx_Banner {

	function __construct() {}

	function check_inactive_notice() {
		global $current_user;
		$user_id     = $current_user->ID;
		$show_notice = false;
		global $wpdb;
		$table_name = $wpdb->prefix . 'spellcheck_options';

		$last_active = ( time() + ( 60 ) );

		$first_notice  = ( time() + ( 60 * 60 * 24 * 5 ) );
		$second_notice = ( time() + ( 60 * 60 * 24 * 20 ) );
		$third_notice  = ( time() + ( 60 * 60 * 24 * 30 ) );
		$last_notices  = ( time() + ( 60 * 60 * 24 * 30 ) );

	}




	function show_review_notice() {
		global $current_user;
		global $wpsc_upgrade_show;
		$user_id = $current_user->ID;
		if ( ! isset( $_GET['page'] ) ) {
			$_GET['page'] = '';
		}
		$page = sanitize_text_field( sanitize_text_field( $_GET['page'] ) );

		if ( '' !== $page ) {
			$page = '&page=' . $page;
		}
			$output = '';
		if ( !preg_match( '/hide-message/m', $output ) && !$wpsc_upgrade_show ) {
			echo esc_html( $output );
		}
	}

	function ignore_review_notice() {
		global $current_user;
		$user_id = $current_user->ID;
		if ( isset( $_GET['wpsc_ignore_review_notice'] ) && '1' === $_GET['wpsc_ignore_review_notice'] ) {
			add_user_meta( $user_id, 'wpsc_ignore_review_notice', 'true', true );
			update_user_meta( $user_id, 'wpsc_ignore_review_notice', 'true' );

			$notice_date = time();
			add_user_meta( $user_id, 'wpsc_review_date', $notice_date, true );
			update_user_meta( $user_id, 'wpsc_review_date', $notice_date );

			$times_dismissed = get_user_meta( $user_id, 'wpsc_times_dismissed_review', true );
			if ( '0' === $times_dismissed ) {
				$times_dismissed = '1';
			}
			if ( '1' === $times_dismissed ) {
				$times_dismissed = '2';
			}
			if ( '2' === $times_dismissed ) {
				$times_dismissed = '3';
			}
			if ( '3' === $times_dismissed ) {
				$times_dismissed = '4';
			}
			update_user_meta( $user_id, 'wpsc_times_dismissed_review', $times_dismissed );
		} elseif ( isset( $_GET['wpsc_ignore_review_notice'] ) && '2' === $_GET['wpsc_ignore_review_notice'] ) {
			add_user_meta( $user_id, 'wpsc_ignore_review_notice', 'hide', true );
			update_user_meta( $user_id, 'wpsc_ignore_review_notice', 'hide' );

			$notice_date = time();
			add_user_meta( $user_id, 'wpsc_review_date', $notice_date, true );
			update_user_meta( $user_id, 'wpsc_review_date', $notice_date );

			$times_dismissed = get_user_meta( $user_id, 'wpsc_times_dismissed_review', true );
			if ( '0' === $times_dismissed ) {
				$times_dismissed = '1';
			}
			if ( '1' === $times_dismissed ) {
				$times_dismissed = '2';
			}
			if ( '2' === $times_dismissed ) {
				$times_dismissed = '3';
			}
			if ( '3' === $times_dismissed ) {
				$times_dismissed = '4';
			}
			update_user_meta( $user_id, 'wpsc_times_dismissed_review', $times_dismissed );
		}
	}

	function get_notice_timing( $user_id ) {
			$notice_timing      = get_user_meta( $user_id, 'wpsc_notice_timing', true );
			$notice_timing_date = get_user_meta( $user_id, 'wpsc_notice_timing_date', true );

		if ( '' === $notice_timing_date ) {
			$notice_timing_date = time();
			add_user_meta( $user_id, 'wpsc_notice_timing_date', $notice_timing_date, true );
		}
		if ( '' === $notice_timing ) {
			$input = "Survey: 1,7,7,7;\r\nUpgrade: 0,3,12,30;";

			add_user_meta( $user_id, 'wpsc_notice_timing', $input, true );
		}

			$time = ( time() - ( 60 * 60 * 7 ) );
		if ( $time <= $notice_timing_date ) {
			$input = "Survey: 1,7,7,7;\r\nUpgrade: 0,3,12,30;";

			update_user_meta( $user_id, 'wpsc_notice_timing', $input, true );
			return $input;
		} else {
			return $notice_timing;
		}
	}

	function check_review_notice() {
		if ( ! ini_get( 'allow_url_fopen' ) ) {
			return;
		}

		global $current_user;
		$user_id = $current_user->ID;

		$notice_date     = get_user_meta( $user_id, 'wpsc_review_date', true );
		$ignore_review   = get_user_meta( $user_id, 'wpsc_ignore_review_notice', true );
		$times_dismissed = get_user_meta( $user_id, 'wpsc_times_dismissed_review', true );

		$show_notice = false;

		if ( '' === $notice_date ) {
			$notice_date = time();
			add_user_meta( $user_id, 'wpsc_review_date', $notice_date, true );
		}

		if ( '' === $times_dismissed ) {
			add_user_meta( $user_id, 'wpsc_times_dismissed_review', '0', true );
		}

				$input = $this->get_notice_timing( $user_id );

		$timing         = explode( ';', $input );
		$timing_numbers = str_replace( 'Survey: ', '', $timing[0] );
		$timing_list    = explode( ',', $timing_numbers );

		$time          = $notice_date;
		$first_notice  = ( time() - ( 60 * 60 * 24 * intval( $timing_list[0] ) ) );
		$second_notice = ( time() - ( 60 * 60 * 24 * intval( $timing_list[1] ) ) );
		$third_notice  = ( time() - ( 60 * 60 * 24 * intval( $timing_list[2] ) ) );
		$last_notices  = ( time() - ( 60 * 60 * 24 * intval( $timing_list[3] ) ) );

		if ( '0' === $times_dismissed ) {
			if ( $first_notice > $time ) {
				$show_notice = true;
			}
		} elseif ( '1' === $times_dismissed ) {
			if ( $second_notice > $time ) {
				$show_notice = true;
			}
		} elseif ( '2' === $times_dismissed ) {
			if ( $third_notice > $time ) {
				$show_notice = true;
			}
		} elseif ( $last_notices > $time ) {
			$show_notice = true;
		}

	}


	function ignore_notice() {
		global $current_user;
		$user_id = $current_user->ID;
		if ( isset( $_GET['wpsc_pro_ignore_notice'] ) && '1' === $_GET['wpsc_pro_ignore_notice'] ) {
			add_user_meta( $user_id, 'wpsc_pro_ignore_notice', 'true', true );
			update_user_meta( $user_id, 'wpsc_pro_ignore_notice', 'true' );

			$notice_date = time();
			update_user_meta( $user_id, 'wpsc_pro_notice_date', $notice_date );

			$times_dismissed = get_user_meta( $user_id, 'wpsc_pro_times_dismissed', true );
			if ( '0' === $times_dismissed ) {
				$times_dismissed = '1';
			}
			if ( '1' === $times_dismissed ) {
				$times_dismissed = '2';
			}
			if ( '2' === $times_dismissed ) {
				$times_dismissed = '3';
			}
			if ( '4' === $times_dismissed ) {
				$times_dismissed = '4';
			}
			update_user_meta( $user_id, 'wpsc_pro_times_dismissed', $times_dismissed );
		}
	}





	public static function show_install_notice() {
		if ( isset( $_GET['install'] ) && 'hide' === $_GET['install'] ) {
			return;
		}
				global $wpsc_version;
				wpscx_set_global_vars();
		?>
		<div class="wpsc-install-notice">
					<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) ); ?>images/logo.png" alt="WP Spell Check">
					<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) ); ?>images/install-character.png" alt="WP Spell Check" style="position: absolute; left: 5px; bottom: 10px; width: 125px;">
					<div style="text-align: center; font-weight: bold; font-size: 28px; margin: 5px 0 25px 0;">Thank you for activating WP Spell Check</div>
					<div style="position: absolute; top: 40%; width: 45%; left: 30%; text-align: left;">
						<ul style="list-style: disc;">
							<li style="font-size: 18px;"><a class="wpsc-install-link-delay" href="/wp-admin/admin.php?page=wp-spellcheck.php">Spell Check my website</a></li>
							<li style="list-style-type: none; text-align: center; padding-right: 25%;">Or</li>
							<li style="font-size: 18px;"><a class="wpsc-install-link" href="https://www.wpspellcheck.com/plugin-support/an-overview-of-the-plugin/?utm_source=baseplugin&utm_campaign=toturial_rightside&utm_medium=spell_check&utm_content=<?php echo esc_html( $wpsc_version ); ?>" target="_blank">Watch a brief Video tutorial</a></li>
						</ul>
					</div>
					<div style="text-align: center; position: absolute; bottom: 17px; width: 100%;"><a href="#" style="text-decoration: none; font-size: 16px;" class="wpsc-install-notice-dismiss">Dismiss this message</a></div>
				</div>
			<script type="text/javascript">
				jQuery(document).ready( function($) {
										//$( "#wp-admin-bar-WP_Spell_Check").prepend('<div class="wpsc-install-notice"><div><span style="color: #013c68;">Thank you for activating WP Spell Check.</span><span style="color: green;">Click Up Here!</span><a class="wpsc-install-notice-dismiss" href="<?php echo esc_url( add_query_arg( array( 'wpsc_ignore_install_notice' => '1' ) ) ); ?>">Dismiss<span style="display: inline-block!important; font-size: 10px!important; position: relative; top: -7px; left: 2px;">X</span></a></div><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'images/install-notice.png'; ?>" /></div>');
					
					$('.wpsc-install-notice-dismiss').click(function(e) {
						e.preventDefault();
						
						jQuery.ajax({
							url: '<?php echo esc_url( admin_url( WPSC_ADMIN_AJAX ) ); ?>',
							type: "POST",
							data: {
								action: 'wpsc_dismiss',
							},
							dataType: 'html'
						});
						
						$('.wpsc-install-notice').hide();
					});
										$('.wpsc-install-link').click(function(e) {
						jQuery.ajax({
							url: '<?php echo esc_url( admin_url( WPSC_ADMIN_AJAX ) ); ?>',
							type: "POST",
							data: {
								action: 'wpsc_dismiss',
							},
							dataType: 'html'
						});
						
						$('.wpsc-install-notice').hide();
					});
										$('.wpsc-install-link-delay').click(function(e) {
												e.preventDefault();
										
						jQuery.ajax({
							url: '<?php echo esc_url( admin_url( WPSC_ADMIN_AJAX ) ); ?>',
							type: "POST",
							data: {
								action: 'wpsc_dismiss',
							},
							dataType: 'html'
						});
						
						$('.wpsc-install-notice').hide();
												window.location.href = "/wp-admin/admin.php?page=wp-spellcheck.php&install=hide";
					});
				});
			</script>
		<?php
	}

	function ignore_install_notice() {
		global $current_user;
		$user_id   = $current_user->ID;
		$dismissed = get_user_meta( $user_id, 'wpsc_ignore_install_notice', true );
		if ( '' === $dismissed ) {
			add_user_meta( $user_id, 'wpsc_ignore_install_notice', 'true', true );
		} else {
			update_user_meta( $user_id, 'wpsc_ignore_install_notice', 'true' );
		}
	}

	function check_install_notice() {
		global $current_user;
		$user_id   = $current_user->ID;
		$dismissed = get_user_meta( $user_id, 'wpsc_ignore_install_notice', true );

                if ( isset ( $_GET['page'] ) ) { $cur_page = sanitize_text_field( $_GET['page'] ); } else { $cur_page = ''; }
		if ( ( current_user_can( 'manage_options' ) ) && 'true' !== $dismissed && ( 'wp-spellcheck.php' === $cur_page || 'wp-spellcheck-grammar.php' === $cur_page || 'wp-spellcheck-seo.php' === $cur_page || 'wp-spellcheck-html.php' === $cur_page || 'wp-spellcheck-options.php' === $cur_page || 'wp-spellcheck-dictionary.php' === $cur_page || 'wp-spellcheck-ignore.php' === $cur_page ) ) {
						$banner = new Wpscx_Banner;
						$banner::show_install_notice();
		}
	}

	function ignore_upgrade_notice() {
		global $current_user;
		$user_id = $current_user->ID;
		if ( isset( $_GET['wpsc_ignore_upgrade_notice'] ) && '1' === $_GET['wpsc_ignore_upgrade_notice'] ) {
			delete_user_meta( $user_id, 'wpsc_update_notice_date' );
			add_user_meta( $user_id, 'wpsc_update_notice_date', time(), true );
		}
	}

	function show_upgrade_message() {
		global $wpsc_upgrade_show;
		$wpsc_upgrade_show = true;
		if ( ! isset( $_GET['page'] ) ) {
			$_GET['page'] = '';
		}
		$page   = sanitize_text_field( $_GET['page'] );
		$output = '';
		echo esc_html( $output );
	}

	function check_upgrade_message() {
		if ( ! ini_get( 'allow_url_fopen' ) ) {
			return;
		}

		global $current_user;
		global $wpscx_ent_included;
		global $wpsc_upgrade_show;
		$wpsc_upgrade_show = false;

		$user_id         = $current_user->ID;
		$notice_date     = get_user_meta( $user_id, 'wpsc_pro_notice_date', true );
		$times_dismissed = get_user_meta( $user_id, 'wpsc_pro_dismissed', true );
		$show_notice     = false;

		if ( '' === $notice_date ) {
			$notice_date = time();
			add_user_meta( $user_id, 'wpsc_pro_notice_date', $notice_date, true );
		}

		if ( '' === $times_dismissed ) {
			add_user_meta( $user_id, 'wpsc_pro_dismissed', '0', true );
		}

		$time          = $notice_date;
		$first_notice  = ( time() - ( 60 * 60 * 24 * 0 ) );
		$second_notice = ( time() - ( 60 * 60 * 24 * 3 ) );
		$third_notice  = ( time() - ( 60 * 60 * 24 * 12 ) );
		$last_notices  = ( time() - ( 60 * 60 * 24 * 30 ) );

		if ( '0' === $times_dismissed ) {
			if ( $first_notice > $time ) {
				$show_notice = true;
			}
		} elseif ( '1' === $times_dismissed ) {
			if ( $second_notice > $time ) {
				$show_notice = true;
			}
		} elseif ( '2' === $times_dismissed ) {
			if ( $third_notice > $time ) {
				$show_notice = true;
			}
		} elseif ( $last_notices > $time ) {
			$show_notice = true;
		}

		if ( ( current_user_can( 'manage_options' ) ) && ! is_plugin_active( 'wp-spell-check-pro/wpspellcheckpro.php' ) && ! is_plugin_active( 'wp-spell-check-enterprise/wpspellcheckenterprise.php' ) && $show_notice && ! $wpscx_ent_included ) {
			$this->show_upgrade_message();
		}
	}
}

