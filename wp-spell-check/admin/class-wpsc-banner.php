<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
|--------------------------------------------------------------------------
| Admin notice toggles — set true / false here (defaults: both on).
| Override anytime from wp-config.php before wp-settings.php loads, e.g.:
| define( 'WPSC_BANNER_SHOW_REVIEW_NOTICE', false );
| define( 'WPSC_BANNER_SHOW_UPGRADE_NOTICE', false );
|--------------------------------------------------------------------------
*/
if ( ! defined( 'WPSC_BANNER_SHOW_REVIEW_NOTICE' ) ) {
	define( 'WPSC_BANNER_SHOW_REVIEW_NOTICE', true );
}
if ( ! defined( 'WPSC_BANNER_SHOW_UPGRADE_NOTICE' ) ) {
	define( 'WPSC_BANNER_SHOW_UPGRADE_NOTICE', true );
}

class Wpscx_Banner {

	function __construct() {}

	/**
	 * Output timed review / upgrade admin notices (manage_options only).
	 *
	 * @since 11.0
	 */
	function check_inactive_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( WPSC_BANNER_SHOW_UPGRADE_NOTICE ) {
			$this->check_upgrade_message();
		}
		if ( WPSC_BANNER_SHOW_REVIEW_NOTICE ) {
			$this->check_review_notice();
		}
	}

	/**
	 * Bump review-dismiss tier (0–4) after remind / never-again.
	 *
	 * @since 11.0
	 * @param int $user_id User ID.
	 */
	private function wpsc_bump_review_dismiss_tier( $user_id ) {
		$times_dismissed = get_user_meta( $user_id, 'wpsc_times_dismissed_review', true );
		if ( '' === $times_dismissed ) {
			$times_dismissed = '0';
		}
		if ( '0' === $times_dismissed ) {
			$next = '1';
		} elseif ( '1' === $times_dismissed ) {
			$next = '2';
		} elseif ( '2' === $times_dismissed ) {
			$next = '3';
		} elseif ( '3' === $times_dismissed ) {
			$next = '4';
		} else {
			$next = '4';
		}
		update_user_meta( $user_id, 'wpsc_times_dismissed_review', $next );
	}

	/**
	 * Bump Pro upgrade notice dismiss tier (uses wpsc_pro_dismissed).
	 *
	 * @since 11.0
	 * @param int $user_id User ID.
	 */
	private function wpsc_bump_pro_dismiss_tier( $user_id ) {
		$times_dismissed = get_user_meta( $user_id, 'wpsc_pro_dismissed', true );
		if ( '' === $times_dismissed ) {
			$times_dismissed = '0';
		}
		if ( '0' === $times_dismissed ) {
			$next = '1';
		} elseif ( '1' === $times_dismissed ) {
			$next = '2';
		} elseif ( '2' === $times_dismissed ) {
			$next = '3';
		} elseif ( '3' === $times_dismissed ) {
			$next = '4';
		} else {
			$next = '4';
		}
		update_user_meta( $user_id, 'wpsc_pro_dismissed', $next );
	}

	/**
	 * Parse Upgrade day offsets from stored notice timing string.
	 *
	 * @since 11.0
	 * @param int $user_id User ID.
	 * @return int[] Four day offsets.
	 */
	private function wpsc_get_upgrade_day_intervals( $user_id ) {
		$defaults = array( 0, 3, 12, 30 );
		$input    = $this->get_notice_timing( $user_id );
		$lines    = preg_split( '/\r\n|\n|\r/', $input );
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line || false === strpos( $line, 'Upgrade:' ) ) {
				continue;
			}
			$nums = trim( str_replace( 'Upgrade:', '', $line ) );
			$list = array_map( 'intval', explode( ',', $nums ) );
			if ( count( $list ) >= 4 ) {
				return array_slice( $list, 0, 4 );
			}
			break;
		}
		return $defaults;
	}

	function show_review_notice() {
		global $wpsc_upgrade_show;
		if ( ! empty( $wpsc_upgrade_show ) ) {
			return;
		}

		$reviews_url = 'https://wordpress.org/support/plugin/wp-spell-check/reviews/';
		$remind_url  = wp_nonce_url(
			add_query_arg( 'wpsc_ignore_review_notice', '1' ),
			'wpsc_dismiss_review',
			'_wpsc_review_nonce'
		);
		$hide_url    = wp_nonce_url(
			add_query_arg( 'wpsc_ignore_review_notice', '2' ),
			'wpsc_dismiss_review',
			'_wpsc_review_nonce'
		);

		printf(
			'<div class="wpsc-promo-notice wpsc-promo-notice--review" role="region" aria-label="%1$s"><div class="wpsc-promo-notice__inner"><div class="wpsc-promo-notice__badge" aria-hidden="true"><span class="dashicons dashicons-star-filled"></span></div><div class="wpsc-promo-notice__body"><p class="wpsc-promo-notice__title">%2$s</p><p class="wpsc-promo-notice__text">%3$s</p></div><div class="wpsc-promo-notice__actions"><a class="wpsc-promo-notice__btn wpsc-promo-notice__btn--primary" href="%4$s" target="_blank" rel="noopener noreferrer">%5$s</a><a class="wpsc-promo-notice__btn wpsc-promo-notice__btn--ghost" href="%6$s">%7$s</a><a class="wpsc-promo-notice__link" href="%8$s">%9$s</a></div></div></div>',
			esc_attr__( 'Review request', 'wp-spell-check' ),
			esc_html__( 'Enjoying WP Spell Check?', 'wp-spell-check' ),
			esc_html__( 'A quick rating on WordPress.org helps others discover the plugin and supports ongoing development.', 'wp-spell-check' ),
			esc_url( $reviews_url ),
			esc_html__( 'Rate on WordPress.org', 'wp-spell-check' ),
			esc_url( $remind_url ),
			esc_html__( 'Remind me later', 'wp-spell-check' ),
			esc_url( $hide_url ),
			esc_html__( 'I\'ve already rated the plugin', 'wp-spell-check' )
		);
	}

	function ignore_review_notice() {
		if ( ! WPSC_BANNER_SHOW_REVIEW_NOTICE ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verified below; redirect removes args.
		if ( ! isset( $_GET['wpsc_ignore_review_notice'], $_GET['_wpsc_review_nonce'] ) ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_GET['_wpsc_review_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'wpsc_dismiss_review' ) ) {
			return;
		}
		$which = sanitize_text_field( wp_unslash( $_GET['wpsc_ignore_review_notice'] ) );
		if ( '1' !== $which && '2' !== $which ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( '2' === $which ) {
			update_user_meta( $user_id, 'wpsc_ignore_review_notice', 'hide' );
		} else {
			update_user_meta( $user_id, 'wpsc_ignore_review_notice', 'true' );
		}
		update_user_meta( $user_id, 'wpsc_review_date', time() );
		$this->wpsc_bump_review_dismiss_tier( $user_id );

		wp_safe_redirect( remove_query_arg( array( 'wpsc_ignore_review_notice', '_wpsc_review_nonce' ) ) );
		exit;
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
			$notice_timing = $input;
		}

		$time = ( time() - ( 60 * 60 * 7 ) );
		if ( $time <= $notice_timing_date ) {
			$input = "Survey: 1,7,7,7;\r\nUpgrade: 0,3,12,30;";
			update_user_meta( $user_id, 'wpsc_notice_timing', $input, true );
			return $input;
		}
		return $notice_timing;
	}

	function check_review_notice() {
		if ( ! WPSC_BANNER_SHOW_REVIEW_NOTICE ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $current_user;
		$user_id = $current_user->ID;

		$notice_date     = get_user_meta( $user_id, 'wpsc_review_date', true );
		$ignore_review   = get_user_meta( $user_id, 'wpsc_ignore_review_notice', true );
		$times_dismissed = get_user_meta( $user_id, 'wpsc_times_dismissed_review', true );

		if ( 'hide' === $ignore_review ) {
			return;
		}

		$show_notice = false;

		if ( '' === $notice_date ) {
			$notice_date = time();
			add_user_meta( $user_id, 'wpsc_review_date', $notice_date, true );
		}

		if ( '' === $times_dismissed ) {
			add_user_meta( $user_id, 'wpsc_times_dismissed_review', '0', true );
			$times_dismissed = '0';
		}

		$input = $this->get_notice_timing( $user_id );

		$timing = explode( ';', $input );
		if ( empty( $timing[0] ) ) {
			return;
		}
		$timing_numbers = str_replace( 'Survey: ', '', trim( $timing[0] ) );
		$timing_list    = array_map( 'intval', explode( ',', $timing_numbers ) );
		while ( count( $timing_list ) < 4 ) {
			$timing_list[] = 7;
		}

		$time          = (int) $notice_date;
		$first_notice  = ( time() - ( 60 * 60 * 24 * $timing_list[0] ) );
		$second_notice = ( time() - ( 60 * 60 * 24 * $timing_list[1] ) );
		$third_notice  = ( time() - ( 60 * 60 * 24 * $timing_list[2] ) );
		$last_notices  = ( time() - ( 60 * 60 * 24 * $timing_list[3] ) );

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

		if ( $show_notice ) {
			$this->show_review_notice();
		}
	}


	function ignore_notice() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verified below.
		if ( ! isset( $_GET['wpsc_pro_ignore_notice'], $_GET['_wpsc_pro_notice_nonce'] ) ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpsc_pro_notice_nonce'] ) ), 'wpsc_dismiss_pro_notice' ) ) {
			return;
		}
		if ( '1' !== sanitize_text_field( wp_unslash( $_GET['wpsc_pro_ignore_notice'] ) ) ) {
			return;
		}

		$user_id = get_current_user_id();
		update_user_meta( $user_id, 'wpsc_pro_ignore_notice', 'true' );
		update_user_meta( $user_id, 'wpsc_pro_notice_date', time() );
		$this->wpsc_bump_pro_dismiss_tier( $user_id );

		wp_safe_redirect( remove_query_arg( array( 'wpsc_pro_ignore_notice', '_wpsc_pro_notice_nonce' ) ) );
		exit;
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
					<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) ); ?>images/install-character.png" alt="WP Spell Check">
					<div>Thank you for activating WP Spell Check</div>
					<div>
						<ul>
							<li><a class="wpsc-install-link-delay" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-spellcheck.php' ) ); ?>">Spell Check my website</a></li>
							<li>Or</li>
							<li><a class="wpsc-install-link" href="https://www.wpspellcheck.com/plugin-support/an-overview-of-the-plugin/?utm_source=baseplugin&utm_campaign=toturial_rightside&utm_medium=spell_check&utm_content=<?php echo esc_html( $wpsc_version ); ?>" target="_blank">Watch a brief Video tutorial</a></li>
						</ul>
					</div>
					<div><a href="#" class="wpsc-install-notice-dismiss">Dismiss this message</a></div>
				</div>
			<script type="text/javascript">
				jQuery(document).ready( function($) {
					$('.wpsc-install-notice-dismiss').click(function(e) {
						e.preventDefault();
						
						jQuery.ajax({
							url: '<?php echo esc_js( esc_url( admin_url( WPSC_ADMIN_AJAX ) ) ); ?>',
							type: "POST",
							data: {
								action: 'wpsc_dismiss',
								nonce: '<?php echo esc_js( wp_create_nonce( 'wpsc_dismiss_notice' ) ); ?>'
							},
							dataType: 'html'
						});
						
						$('.wpsc-install-notice').hide();
					});
										$('.wpsc-install-link').click(function(e) {
						jQuery.ajax({
							url: '<?php echo esc_js( esc_url( admin_url( WPSC_ADMIN_AJAX ) ) ); ?>',
							type: "POST",
							data: {
								action: 'wpsc_dismiss',
								nonce: '<?php echo esc_js( wp_create_nonce( 'wpsc_dismiss_notice' ) ); ?>'
							},
							dataType: 'html'
						});
						
						$('.wpsc-install-notice').hide();
					});
										$('.wpsc-install-link-delay').click(function(e) {
												e.preventDefault();
										
						jQuery.ajax({
							url: '<?php echo esc_js( esc_url( admin_url( WPSC_ADMIN_AJAX ) ) ); ?>',
							type: "POST",
							data: {
								action: 'wpsc_dismiss',
								nonce: '<?php echo esc_js( wp_create_nonce( 'wpsc_dismiss_notice' ) ); ?>'
							},
							dataType: 'html',
							success: function() {
								$('.wpsc-install-notice').hide();
								window.location.href = "<?php echo esc_js( esc_url( admin_url( 'admin.php?page=wp-spellcheck.php&install=hide' ) ) ); ?>";
							}
						});
					});
				});
			</script>
		<?php
	}

	function ignore_install_notice() {
		check_ajax_referer( 'wpsc_dismiss_notice', 'nonce' );
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
		// Activation "Thank you for activating" notice is shown only on the Plugins page (see wpspellcheck.php).
		// Do not show it on Spell Check pages here.
	}

	function ignore_upgrade_notice() {
		if ( ! WPSC_BANNER_SHOW_UPGRADE_NOTICE ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verified below.
		if ( ! isset( $_GET['wpsc_ignore_upgrade_notice'], $_GET['_wpsc_upgrade_notice_nonce'] ) ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpsc_upgrade_notice_nonce'] ) ), 'wpsc_dismiss_upgrade_notice' ) ) {
			return;
		}
		if ( '1' !== sanitize_text_field( wp_unslash( $_GET['wpsc_ignore_upgrade_notice'] ) ) ) {
			return;
		}

		$user_id = get_current_user_id();
		delete_user_meta( $user_id, 'wpsc_update_notice_date' );
		update_user_meta( $user_id, 'wpsc_update_notice_date', time() );

		wp_safe_redirect( remove_query_arg( array( 'wpsc_ignore_upgrade_notice', '_wpsc_upgrade_notice_nonce' ) ) );
		exit;
	}

	function show_upgrade_message() {
		global $wpsc_upgrade_show;
		$wpsc_upgrade_show = true;

		global $wpsc_version;
		$ver         = is_string( $wpsc_version ) ? $wpsc_version : '';
		$product_url = 'https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgrade_admin_notice&utm_medium=admin_notice&utm_content=' . rawurlencode( $ver );

		$dismiss_url = wp_nonce_url(
			add_query_arg( 'wpsc_pro_ignore_notice', '1' ),
			'wpsc_dismiss_pro_notice',
			'_wpsc_pro_notice_nonce'
		);

		printf(
			'<div class="wpsc-promo-notice wpsc-promo-notice--upgrade" role="region" aria-label="%1$s"><div class="wpsc-promo-notice__inner"><div class="wpsc-promo-notice__badge" aria-hidden="true"><span class="dashicons dashicons-awards"></span></div><div class="wpsc-promo-notice__body"><p class="wpsc-promo-notice__title">%2$s</p><p class="wpsc-promo-notice__text">%3$s</p></div><div class="wpsc-promo-notice__actions"><a class="wpsc-promo-notice__btn wpsc-promo-notice__btn--primary" href="%4$s" target="_blank" rel="noopener noreferrer">%5$s</a><a class="wpsc-promo-notice__btn wpsc-promo-notice__btn--ghost" href="%6$s">%7$s</a></div></div></div>',
			esc_attr__( 'Upgrade to Pro', 'wp-spell-check' ),
			esc_html__( 'Unlock WP Spell Check Pro', 'wp-spell-check' ),
			esc_html__( 'Scan your entire site, catch more issues, and use advanced checks built for serious sites.', 'wp-spell-check' ),
			esc_url( $product_url ),
			esc_html__( 'Explore Pro features', 'wp-spell-check' ),
			esc_url( $dismiss_url ),
			esc_html__( 'Dismiss', 'wp-spell-check' )
		);
	}

	function check_upgrade_message() {
		if ( ! WPSC_BANNER_SHOW_UPGRADE_NOTICE ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		global $current_user;
		global $wpscx_ent_included;
		global $wpsc_upgrade_show;
		$wpsc_upgrade_show = false;

		$user_id         = $current_user->ID;
		$notice_date     = get_user_meta( $user_id, 'wpsc_pro_notice_date', true );
		$times_dismissed = get_user_meta( $user_id, 'wpsc_pro_dismissed', true );
		$legacy_tier     = get_user_meta( $user_id, 'wpsc_pro_times_dismissed', true );
		if ( '' === $times_dismissed && '' !== $legacy_tier ) {
			update_user_meta( $user_id, 'wpsc_pro_dismissed', (string) $legacy_tier );
			$times_dismissed = (string) $legacy_tier;
		}
		$show_notice = false;

		if ( '' === $notice_date ) {
			$notice_date = time();
			add_user_meta( $user_id, 'wpsc_pro_notice_date', $notice_date, true );
		}

		if ( '' === $times_dismissed ) {
			add_user_meta( $user_id, 'wpsc_pro_dismissed', '0', true );
			$times_dismissed = '0';
		}

		$u_days        = $this->wpsc_get_upgrade_day_intervals( $user_id );
		$time          = (int) $notice_date;
		$first_notice  = ( time() - ( 60 * 60 * 24 * $u_days[0] ) );
		$second_notice = ( time() - ( 60 * 60 * 24 * $u_days[1] ) );
		$third_notice  = ( time() - ( 60 * 60 * 24 * $u_days[2] ) );
		$last_notices  = ( time() - ( 60 * 60 * 24 * $u_days[3] ) );

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

		if ( ! is_plugin_active( 'wp-spell-check-pro/wpspellcheckpro.php' ) && ! is_plugin_active( 'wp-spell-check-enterprise/wpspellcheckenterprise.php' ) && $show_notice && ! $wpscx_ent_included ) {
			$this->show_upgrade_message();
		}
	}
}
