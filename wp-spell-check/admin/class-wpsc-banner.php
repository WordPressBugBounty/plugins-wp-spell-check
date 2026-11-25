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

	/**
	 * Black Friday coupon for pricing URL and notice footer (plugin release only).
	 *
	 * @var string
	 */
	private static $wpsc_black_friday_coupon_code = 'BF2026';

	/**
	 * Cyber Monday coupon for pricing URL and notice footer (plugin release only).
	 *
	 * @var string
	 */
	private static $wpsc_cyber_monday_coupon_code = 'CM2026';

	/**
	 * Show Cyber Monday banner outside its date window (for design review). Set false before release.
	 *
	 * @var bool
	 */
	private static $wpsc_cyber_monday_preview_active = false;

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
		$this->check_pro_inactive_notice();
		$seasonal_notice = $this->check_black_friday_sale_notice();
		$seasonal_notice = $this->check_cyber_monday_sale_notice() || $seasonal_notice;
		if ( $seasonal_notice ) {
			return;
		}
		if ( WPSC_BANNER_SHOW_UPGRADE_NOTICE ) {
			$this->check_upgrade_message();
		}
		if ( WPSC_BANNER_SHOW_REVIEW_NOTICE ) {
			$this->check_review_notice();
		}
	}

	/**
	 * Sale end: 00:00 on Cyber Monday (last full day is the Sunday before).
	 *
	 * @since 12.2
	 * @param int               $year Calendar year.
	 * @param DateTimeZone|null $tz   Site timezone.
	 * @return DateTimeImmutable
	 */
	private function wpsc_get_black_friday_sale_end( $year, $tz ) {
		$thursday_count = 0;
		for ( $day = 1; $day <= 30; $day++ ) {
			$date = new DateTimeImmutable( sprintf( '%d-11-%02d 00:00:00', $year, $day ), $tz );
			if ( 4 === (int) $date->format( 'N' ) ) {
				++$thursday_count;
				if ( 4 === $thursday_count ) {
					return $date->modify( '+4 days' );
				}
			}
		}

		return new DateTimeImmutable( $year . '-12-01 00:00:00', $tz );
	}

	/**
	 * Whether the annual Black Friday sale window is active (Nov 23–Sunday before Cyber Monday).
	 *
	 * @since 12.2
	 * @return bool
	 */
	private function wpsc_is_black_friday_sale_active() {
		$tz    = wp_timezone();
		$year  = (int) wp_date( 'Y', time(), $tz );
		$start = new DateTimeImmutable( $year . '-11-23 00:00:00', $tz );
		$end   = $this->wpsc_get_black_friday_sale_end( $year, $tz );
		$now   = new DateTimeImmutable( 'now', $tz );
		return $now >= $start && $now < $end;
	}

	/**
	 * Whether the Cyber Monday sale window is active (Nov 30–Dec 6, site timezone).
	 *
	 * @since 12.2
	 * @return bool
	 */
	private function wpsc_is_cyber_monday_sale_active() {
		if ( self::$wpsc_cyber_monday_preview_active ) {
			return true;
		}

		$tz    = wp_timezone();
		$year  = (int) wp_date( 'Y', time(), $tz );
		$start = new DateTimeImmutable( $year . '-11-30 00:00:00', $tz );
		$end   = new DateTimeImmutable( $year . '-12-07 00:00:00', $tz );
		$now   = new DateTimeImmutable( 'now', $tz );
		return $now >= $start && $now < $end;
	}

	/**
	 * Logo markup for seasonal sale banners.
	 *
	 * @since 12.2
	 * @return string
	 */
	private function wpsc_get_sale_banner_logo_markup() {
		$plugin_main = dirname( __DIR__ ) . '/wpspellcheck.php';
		$logo_files  = array(
			'images/logo-square.png',
			'images/logo.png',
			'admin/images/logo.png',
			'admin/images/logo-icon-16x16.png',
			'images/logo-icon-16x16.png',
		);
		foreach ( $logo_files as $logo_relative ) {
			if ( file_exists( dirname( __DIR__ ) . '/' . $logo_relative ) ) {
				return '<img class="wpsc-bf-banner__logo" src="' . esc_url( plugins_url( $logo_relative, $plugin_main ) ) . '" width="40" height="40" alt="" decoding="async" />';
			}
		}
		return '';
	}

	/**
	 * Coupon footnote line for seasonal sale banners.
	 *
	 * @since 12.2
	 * @param string $coupon_code Sanitized coupon code.
	 * @return string
	 */
	private function wpsc_get_sale_banner_footnote_html( $coupon_code ) {
		$footnote_html = '<p class="wpsc-bf-banner__footnote wpsc-bf-banner__limited wpsc-bf-banner__offer-line">'
			. '<strong><em>' . esc_html__( 'Limited Time Offer', 'wp-spell-check' ) . '</em></strong>'
			. '<span class="wpsc-bf-banner__offer-sep" aria-hidden="true">:</span> ';
		if ( '' !== $coupon_code ) {
			$footnote_html .= '<span class="wpsc-bf-banner__coupon-label">' . esc_html__( 'Use coupon code', 'wp-spell-check' ) . '</span> '
				. '<span class="wpsc-bf-banner__code">' . esc_html( $coupon_code ) . '</span> '
				. '<span class="wpsc-bf-banner__coupon-hint">' . esc_html__( 'at checkout', 'wp-spell-check' ) . '</span>';
		}
		$footnote_html .= '</p>';
		return $footnote_html;
	}

	/**
	 * Whether the site has an active Pro or Enterprise license (hide Black Friday promo).
	 *
	 * Base-plugin only: uses existing globals and cached api_check — no remote API calls.
	 *
	 * @since 12.2
	 * @return bool
	 */
	private function wpsc_site_has_valid_pro_license() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( is_plugin_active( 'wp-spell-check-enterprise/wpspellcheckenterprise.php' ) ) {
			return true;
		}

		$pro_plugin = 'wp-spell-check-pro/wpspellcheckpro.php';
		if ( ! is_plugin_active( $pro_plugin ) ) {
			return false;
		}

		global $wpscx_ent_included;
		if ( ! empty( $wpscx_ent_included ) ) {
			return true;
		}

		if ( isset( $wpscx_ent_included ) && ! $wpscx_ent_included ) {
			return false;
		}

		return $this->wpsc_read_cached_pro_api_check_valid();
	}

	/**
	 * Read cached api_check from the options table (no remote validation).
	 *
	 * @since 12.2
	 * @return bool
	 */
	private function wpsc_read_cached_pro_api_check_valid() {
		if ( ! function_exists( 'wpscx_set_global_vars' ) ) {
			return false;
		}

		wpscx_set_global_vars();

		global $wpdb;
		global $wpscx_check_opt;

		if ( empty( $wpscx_check_opt ) || 0 === count( (array) $wpscx_check_opt ) ) {
			return false;
		}

		$options_table = $wpdb->prefix . 'spellcheck_options';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from $wpdb->prefix.
		$api_check = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$options_table} WHERE option_name = %s LIMIT 1", 'api_check' ) );

		return ( 'true' === $api_check );
	}

	/**
	 * Black Friday upgrade notice (replaces standard upgrade/review promos while active).
	 *
	 * @since 12.2
	 * @return bool True when the notice was output.
	 */
	function check_black_friday_sale_notice() {
		if ( ! $this->wpsc_is_black_friday_sale_active() ) {
			return false;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( $this->wpsc_site_has_valid_pro_license() ) {
			return false;
		}

		$user_id      = get_current_user_id();
		$dismissed    = get_user_meta( $user_id, 'wpsc_bf_notice_dismissed', true );
		$remind_until = (int) get_user_meta( $user_id, 'wpsc_bf_notice_remind_until', true );
		if ( 'hide' === $dismissed ) {
			return false;
		}
		if ( $remind_until > time() ) {
			return false;
		}

		$this->show_black_friday_sale_notice();
		return true;
	}

	/**
	 * Handle Black Friday notice remind / dismiss (admin_init).
	 *
	 * @since 12.2
	 */
	function ignore_black_friday_notice() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verified below.
		if ( ! isset( $_GET['wpsc_bf_notice_action'], $_GET['_wpsc_bf_notice_nonce'] ) ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( ! $this->wpsc_is_black_friday_sale_active() ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpsc_bf_notice_nonce'] ) ), 'wpsc_dismiss_bf_notice' ) ) {
			return;
		}

		$which   = sanitize_text_field( wp_unslash( $_GET['wpsc_bf_notice_action'] ) );
		$user_id = get_current_user_id();

		if ( '1' === $which ) {
			update_user_meta( $user_id, 'wpsc_bf_notice_remind_until', time() + DAY_IN_SECONDS );
		} elseif ( '2' === $which ) {
			update_user_meta( $user_id, 'wpsc_bf_notice_dismissed', 'hide' );
		} else {
			return;
		}

		wp_safe_redirect( remove_query_arg( array( 'wpsc_bf_notice_action', '_wpsc_bf_notice_nonce' ) ) );
		exit;
	}

	/**
	 * Cyber Monday upgrade notice (replaces standard upgrade/review promos while active).
	 *
	 * @since 12.2
	 * @return bool True when the notice was output.
	 */
	function check_cyber_monday_sale_notice() {
		if ( ! $this->wpsc_is_cyber_monday_sale_active() ) {
			return false;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( $this->wpsc_site_has_valid_pro_license() ) {
			return false;
		}

		$user_id      = get_current_user_id();
		$dismissed    = get_user_meta( $user_id, 'wpsc_cm_notice_dismissed', true );
		$remind_until = (int) get_user_meta( $user_id, 'wpsc_cm_notice_remind_until', true );
		if ( 'hide' === $dismissed ) {
			return false;
		}
		if ( $remind_until > time() ) {
			return false;
		}

		$this->show_cyber_monday_sale_notice();
		return true;
	}

	/**
	 * Handle Cyber Monday notice remind / dismiss (admin_init).
	 *
	 * @since 12.2
	 */
	function ignore_cyber_monday_notice() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verified below.
		if ( ! isset( $_GET['wpsc_cm_notice_action'], $_GET['_wpsc_cm_notice_nonce'] ) ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( ! $this->wpsc_is_cyber_monday_sale_active() ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpsc_cm_notice_nonce'] ) ), 'wpsc_dismiss_cm_notice' ) ) {
			return;
		}

		$which   = sanitize_text_field( wp_unslash( $_GET['wpsc_cm_notice_action'] ) );
		$user_id = get_current_user_id();

		if ( '1' === $which ) {
			update_user_meta( $user_id, 'wpsc_cm_notice_remind_until', time() + DAY_IN_SECONDS );
		} elseif ( '2' === $which ) {
			update_user_meta( $user_id, 'wpsc_cm_notice_dismissed', 'hide' );
		} else {
			return;
		}

		wp_safe_redirect( remove_query_arg( array( 'wpsc_cm_notice_action', '_wpsc_cm_notice_nonce' ) ) );
		exit;
	}

	/**
	 * Output Black Friday sale admin notice markup.
	 *
	 * @since 12.2
	 */
	function show_black_friday_sale_notice() {
		global $wpsc_version;
		$ver = is_string( $wpsc_version ) ? $wpsc_version : '';

		$product_url = 'https://www.wpspellcheck.com/pricing/?utm_source=baseplugin&utm_campaign=black_friday&utm_medium=admin_notice&utm_content=' . rawurlencode( $ver );
		$coupon      = sanitize_text_field( self::$wpsc_black_friday_coupon_code );
		if ( '' !== $coupon ) {
			$product_url = add_query_arg( 'coupon', rawurlencode( $coupon ), $product_url );
		}

		$body_text = esc_html__( 'Save 30% on WP Spell Check Pro—full-site proofreading for spelling, grammar, SEO, and broken code in one place.', 'wp-spell-check' );

		$footnote_html = $this->wpsc_get_sale_banner_footnote_html( $coupon );
		$logo_markup   = $this->wpsc_get_sale_banner_logo_markup();
		$cta_text = sprintf(
			/* translators: %d: discount percentage. */
			esc_html__( 'Upgrade & save %d%%', 'wp-spell-check' ),
			30
		);

		$remind_url = wp_nonce_url(
			add_query_arg( 'wpsc_bf_notice_action', '1' ),
			'wpsc_dismiss_bf_notice',
			'_wpsc_bf_notice_nonce'
		);
		$dismiss_url = wp_nonce_url(
			add_query_arg( 'wpsc_bf_notice_action', '2' ),
			'wpsc_dismiss_bf_notice',
			'_wpsc_bf_notice_nonce'
		);

		printf(
			'<div class="wpsc-promo-notice wpsc-promo-notice--black-friday" role="region" aria-label="%1$s">'
			. '<div class="wpsc-bf-banner__shimmer" aria-hidden="true"></div>'
			. '<div class="wpsc-bf-banner__orb wpsc-bf-banner__orb--left" aria-hidden="true"></div>'
			. '<div class="wpsc-bf-banner__orb wpsc-bf-banner__orb--right" aria-hidden="true"></div>'
			. '<div class="wpsc-bf-banner__inner">'
			. '<div class="wpsc-bf-banner__mark" aria-hidden="true">'
			. '%2$s'
			. '</div>'
			. '<div class="wpsc-bf-banner__copy">'
			. '<p class="wpsc-bf-banner__headline">%3$s <span class="wpsc-bf-banner__discount">%4$s</span></p>'
			. '<p class="wpsc-bf-banner__text">%5$s</p>'
			. '<div class="wpsc-bf-banner__meta">%6$s</div>'
			. '</div>'
			. '<div class="wpsc-bf-banner__actions">'
			. '<a class="wpsc-bf-banner__btn" href="%7$s" target="_blank" rel="noopener noreferrer">%8$s <span class="wpsc-bf-banner__btn-arrow" aria-hidden="true">→</span></a>'
			. '</div>'
			. '</div>'
			. '<div class="wpsc-bf-banner__footer">'
			. '<a class="wpsc-bf-banner__footer-link" href="%9$s"><span class="dashicons dashicons-clock" aria-hidden="true"></span>%10$s</a>'
			. '<a class="wpsc-bf-banner__footer-link" href="%11$s"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span>%12$s</a>'
			. '</div>'
			. '</div>',
			esc_attr__( 'Black Friday sale', 'wp-spell-check' ),
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is safe: trusted banner HTML from wpsc_get_sale_banner_logo_markup(), src escaped with esc_url().
			$logo_markup,
			esc_html__( 'Black Friday Sale —', 'wp-spell-check' ),
			esc_html__( '30% off WP Spell Check Pro', 'wp-spell-check' ),
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is safe: already escaped with esc_html__().
			$body_text,
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is safe: trusted banner HTML from wpsc_get_sale_banner_footnote_html(), strings escaped with esc_html__() / esc_html().
			$footnote_html,
			esc_url( $product_url ),
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is safe: already escaped with esc_html__() in sprintf().
			$cta_text,
			esc_url( $remind_url ),
			esc_html__( 'Remind me later', 'wp-spell-check' ),
			esc_url( $dismiss_url ),
			esc_html__( 'Dismiss this message', 'wp-spell-check' )
		);
	}

	/**
	 * Output Cyber Monday sale admin notice markup.
	 *
	 * @since 12.2
	 */
	function show_cyber_monday_sale_notice() {
		global $wpsc_version;
		$ver = is_string( $wpsc_version ) ? $wpsc_version : '';

		$product_url = 'https://www.wpspellcheck.com/pricing/?utm_source=baseplugin&utm_campaign=cyber_monday&utm_medium=admin_notice&utm_content=' . rawurlencode( $ver );
		$coupon      = sanitize_text_field( self::$wpsc_cyber_monday_coupon_code );
		if ( '' !== $coupon ) {
			$product_url = add_query_arg( 'coupon', rawurlencode( $coupon ), $product_url );
		}

		$body_text       = esc_html__( 'Save 25% on WP Spell Check Pro—full-site proofreading for spelling, grammar, SEO, and broken code in one place.', 'wp-spell-check' );
		$footnote_html   = $this->wpsc_get_sale_banner_footnote_html( $coupon );
		$logo_markup     = $this->wpsc_get_sale_banner_logo_markup();
		$cta_text        = sprintf(
			/* translators: %d: discount percentage. */
			esc_html__( 'Upgrade & save %d%%', 'wp-spell-check' ),
			25
		);
		$remind_url      = wp_nonce_url(
			add_query_arg( 'wpsc_cm_notice_action', '1' ),
			'wpsc_dismiss_cm_notice',
			'_wpsc_cm_notice_nonce'
		);
		$dismiss_url     = wp_nonce_url(
			add_query_arg( 'wpsc_cm_notice_action', '2' ),
			'wpsc_dismiss_cm_notice',
			'_wpsc_cm_notice_nonce'
		);

		printf(
			'<div class="wpsc-promo-notice wpsc-promo-notice--cyber-monday" role="region" aria-label="%1$s">'
			. '<div class="wpsc-bf-banner__shimmer" aria-hidden="true"></div>'
			. '<div class="wpsc-bf-banner__orb wpsc-bf-banner__orb--left" aria-hidden="true"></div>'
			. '<div class="wpsc-bf-banner__orb wpsc-bf-banner__orb--right" aria-hidden="true"></div>'
			. '<div class="wpsc-bf-banner__inner">'
			. '<div class="wpsc-bf-banner__mark" aria-hidden="true">'
			. '%2$s'
			. '</div>'
			. '<div class="wpsc-bf-banner__copy">'
			. '<p class="wpsc-bf-banner__headline">%3$s <span class="wpsc-bf-banner__discount">%4$s</span></p>'
			. '<p class="wpsc-bf-banner__text">%5$s</p>'
			. '<div class="wpsc-bf-banner__meta">%6$s</div>'
			. '</div>'
			. '<div class="wpsc-bf-banner__actions">'
			. '<a class="wpsc-bf-banner__btn" href="%7$s" target="_blank" rel="noopener noreferrer">%8$s <span class="wpsc-bf-banner__btn-arrow" aria-hidden="true">→</span></a>'
			. '</div>'
			. '</div>'
			. '<div class="wpsc-bf-banner__footer">'
			. '<a class="wpsc-bf-banner__footer-link" href="%9$s"><span class="dashicons dashicons-clock" aria-hidden="true"></span>%10$s</a>'
			. '<a class="wpsc-bf-banner__footer-link" href="%11$s"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span>%12$s</a>'
			. '</div>'
			. '</div>',
			esc_attr__( 'Cyber Monday sale', 'wp-spell-check' ),
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is safe: trusted banner HTML from wpsc_get_sale_banner_logo_markup(), src escaped with esc_url().
			$logo_markup,
			esc_html__( 'Cyber Monday Sale —', 'wp-spell-check' ),
			esc_html__( '25% off WP Spell Check Pro', 'wp-spell-check' ),
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is safe: already escaped with esc_html__().
			$body_text,
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is safe: trusted banner HTML from wpsc_get_sale_banner_footnote_html(), strings escaped with esc_html__() / esc_html().
			$footnote_html,
			esc_url( $product_url ),
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is safe: already escaped with esc_html__() in sprintf().
			$cta_text,
			esc_url( $remind_url ),
			esc_html__( 'Remind me later', 'wp-spell-check' ),
			esc_url( $dismiss_url ),
			esc_html__( 'Dismiss this message', 'wp-spell-check' )
		);
	}

	/**
	 * Admin notice when Pro is installed but not active.
	 *
	 * @since 12.1
	 */
	function check_pro_inactive_notice() {
		$pro_plugin = 'wp-spell-check-pro/wpspellcheckpro.php';
		if ( ! file_exists( WP_PLUGIN_DIR . '/' . $pro_plugin ) || is_plugin_active( $pro_plugin ) ) {
			return;
		}
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$activate_url = wp_nonce_url(
			self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $pro_plugin ) ),
			'activate-plugin_' . $pro_plugin
		);

		printf(
			'<div class="wpsc-promo-notice wpsc-promo-notice--warning" role="alert" aria-label="%1$s"><div class="wpsc-promo-notice__inner"><div class="wpsc-promo-notice__badge" aria-hidden="true"><span class="dashicons dashicons-warning"></span></div><div class="wpsc-promo-notice__body"><p class="wpsc-promo-notice__title">%2$s</p><p class="wpsc-promo-notice__text">%3$s</p></div><div class="wpsc-promo-notice__actions"><a class="wpsc-promo-notice__btn wpsc-promo-notice__btn--primary" href="%4$s">%5$s</a></div></div></div>',
			esc_attr__( 'WP Spell Check Pro is installed but not active', 'wp-spell-check' ),
			esc_html__( 'WP Spell Check Pro is installed but not active', 'wp-spell-check' ),
			esc_html__( 'Activate Pro to unlock your licensed features.', 'wp-spell-check' ),
			esc_url( $activate_url ),
			esc_html__( 'Activate WP Spell Check Pro', 'wp-spell-check' )
		);
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
		$product_url = 'https://www.wpspellcheck.com/pricing/?utm_source=baseplugin&utm_campaign=upgrade_admin_notice&utm_medium=admin_notice&utm_content=' . rawurlencode( $ver );

		$dismiss_url = wp_nonce_url(
			add_query_arg( 'wpsc_pro_ignore_notice', '1' ),
			'wpsc_dismiss_pro_notice',
			'_wpsc_pro_notice_nonce'
		);

		printf(
			'<div class="wpsc-promo-notice wpsc-promo-notice--upgrade" role="region" aria-label="%1$s"><div class="wpsc-promo-notice__inner"><div class="wpsc-promo-notice__badge" aria-hidden="true"><span class="dashicons dashicons-awards"></span></div><div class="wpsc-promo-notice__body"><p class="wpsc-promo-notice__title">%2$s</p><p class="wpsc-promo-notice__text">%3$s</p></div><div class="wpsc-promo-notice__actions"><a class="wpsc-promo-notice__btn wpsc-promo-notice__btn--primary" href="%4$s" target="_blank" rel="noopener noreferrer">%5$s</a><a class="wpsc-promo-notice__btn wpsc-promo-notice__btn--ghost" href="%6$s">%7$s</a></div></div></div>',
			esc_attr__( 'Upgrade to Pro', 'wp-spell-check' ),
			esc_html__( 'Unlock WP Spell Check Pro', 'wp-spell-check' ),
			esc_html__( 'Visitors spot typos fast and they cost you trust. Pro scans your entire site, so you catch mistakes before customers do.', 'wp-spell-check' ),
			esc_url( $product_url ),
			esc_html__( 'Upgrade to Pro Now', 'wp-spell-check' ),
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

		$pro_plugin = 'wp-spell-check-pro/wpspellcheckpro.php';
		if ( ! file_exists( WP_PLUGIN_DIR . '/' . $pro_plugin ) && ! is_plugin_active( $pro_plugin ) && ! is_plugin_active( 'wp-spell-check-enterprise/wpspellcheckenterprise.php' ) && $show_notice && ! $wpscx_ent_included ) {
			$this->show_upgrade_message();
		}
	}
}
