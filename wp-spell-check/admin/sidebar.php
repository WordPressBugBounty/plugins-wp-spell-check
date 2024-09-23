<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; }
?>

<div style="float: right; width:23%; margin-left: 2%; margin-top: 50px">
				<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.0";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<script type="text/javascript">
//<![CDATA[
if (typeof newsletter_check !== "function") {
window.newsletter_check = function (f) {
	var re = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-]{1,})+\.)+([a-zA-Z0-9]{2,})+$/;
	if (!re.test(f.elements["ne"].value)) {
		alert("The email is not correct");
		return false;
	}
	for (var i=1; i<20; i++) {
	if (f.elements["np" + i] && f.elements["np" + i].value == "") {
		alert("");
		return false;
	}
	}
	if (f.elements["ny"] && !f.elements["ny"].checked) {
		alert("You must accept the privacy statement");
		return false;
	}
	return true;
}
}
//]]>
</script>

<div style="padding: 5px 5px 10px 5px; border-radius: 5px; background: white; box-shadow: 0px 0px 10px 0px rgba(0,0,0,0.5); text-align: center;">
				<a href="https://www.wpspellcheck.com/support/?utm_source=baseplugin&utm_campaign=toturial_rightside&utm_medium=spell_check&utm_content=<?php echo esc_html( $wpsc_version ); ?>" target="_blank"><img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ) . 'images/wpsc-sidebar.jpg'; ?>" style="max-width: 99%;" alt="Watch WP Spell Check Tutorials" /></a>
</div>
<hr style="margin: 1em 0;">
<div style="padding: 5px 5px 10px 5px; border-radius: 5px; background: white; text-align: center; box-shadow: 0px 0px 10px 0px rgba(0,0,0,0.5);">
				<h2>Follow us on Facebook</h2>
				<div class="fb-page" data-href="https://www.facebook.com/wpspellcheck/" data-width="180px" data-small-header="true" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="true"><blockquote cite="https://www.facebook.com/wpspellcheck/" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/wpspellcheck/">WP Spell Check</a></blockquote></div>
</div>
<hr style="margin: 1em 0;">
<div class="newsletter newsletter-subscription" style="padding: 5px 5px 10px 5px; border-radius: 5px; background: white; box-shadow: 0px 0px 10px 0px rgba(0,0,0,0.5); ">
<div class="wpsc-sidebar" style="margin-bottom: 15px; text-align: center;"><h2>Enjoying this plugin?</h2>Please help by giving us a <a class="review-button" href="https://wordpress.org/support/plugin/wp-spell-check/reviews/?filter=5" target="_blank">★★★★★ Rating</a></div>
</div>
<hr style="margin: 1em 0;">
			</div>
