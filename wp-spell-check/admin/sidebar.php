<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; }
?>

<div class="wpsc-sidebar-container">
<?php
if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
if ( ! is_plugin_active( 'wp-spell-check-pro/wpspellcheckpro.php' ) ) :
	global $wpsc_version;
	$ver         = is_string( $wpsc_version ) ? $wpsc_version : '';
	$upgrade_url = 'https://www.wpspellcheck.com/pricing/?utm_source=baseplugin&utm_campaign=upgrade_sidebar&utm_medium=spell_check&utm_content=' . rawurlencode( $ver );
	?>
<div class="wpsc-sidebar-upgrade-box">
	<h2><?php esc_html_e( 'Unlock WP Spell Check Pro', 'wp-spell-check' ); ?></h2>
	<ul class="wpsc-sidebar-upgrade-list">
		<li><?php esc_html_e( 'Scan entire site', 'wp-spell-check' ); ?></li>
		<li><?php esc_html_e( 'Fix all spelling errors', 'wp-spell-check' ); ?></li>
		<li><?php esc_html_e( 'See hidden issues', 'wp-spell-check' ); ?></li>
	</ul>
	<a class="wpsc-sidebar-upgrade-btn" href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '👉 Upgrade to Pro', 'wp-spell-check' ); ?></a>
</div>
<hr>
	<?php
endif;
?>
<div class="wpsc-sidebar-tutorial-box">
			<a href="https://www.wpspellcheck.com/support/?utm_source=baseplugin&utm_campaign=toturial_rightside&utm_medium=spell_check" target="_blank"><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'images/wpsc-sidebar.jpg'; ?>" alt="Watch WP Spell Check Tutorials" /></a>
</div>
<hr>
<div class="wpsc-sidebar-social-box">
				<h2>Stay In Touch</h2>
				<div class="wpsc-social-links">
					<a href="https://www.facebook.com/wpspellcheck/" target="_blank" rel="noopener noreferrer" class="wpsc-social-link wpsc-social-facebook" aria-label="Follow us on Facebook">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
							<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
						</svg>
						<span>Facebook</span>
					</a>
					<a href="https://x.com/wpspellcheck" target="_blank" rel="noopener noreferrer" class="wpsc-social-link wpsc-social-x" aria-label="Follow us on X">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
							<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
						</svg>
						<span>X</span>
					</a>
				</div>
</div>
<?php /* Rating section temporarily hidden.
<hr>
<div class="newsletter newsletter-subscription">
<div class="wpsc-sidebar"><h2>Enjoying this plugin?</h2>Please help by giving us a <a class="review-button" href="https://wordpress.org/support/plugin/wp-spell-check/reviews/" target="_blank">★★★★★ Rating</a></div>
</div>
<hr>
*/ ?>
			</div>
