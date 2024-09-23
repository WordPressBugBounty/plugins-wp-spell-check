<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
	/*
	 * WP Spell Check Class to create a deactivation survey
	 */


class WpscxDeactivation {

	public $api = 'https://www.wpspellcheck.com/api/deactivate.php';

	public function __construct() {
			$theme = wp_get_theme();

			//Initialize JS, CSS, and HTML
			add_action( 'admin_print_scripts', array( $this, 'run_js' ), 20 );
			add_action( 'admin_print_scripts', array( $this, 'run_css' ) );
			add_action( 'admin_footer', array( $this, 'create_form' ) );
	}

	public function is_plugins_page() {
		$current_page = get_current_screen(); //Get current page

		if ( empty( $current_page ) ) {
			return false;
		}

		//Check if current page is the plugin page for single or multi site
		return in_array( $current_page->id, array( 'plugins', 'plugins-network' ), true );
	}

	public function run_js() {
				   global $wpsc_version;
		if ( ! $this->is_plugins_page() ) {
			return;
		}

		global $wp_version;
		?>
			<script type="text/javascript">
				jQuery(function($){
					var link = $('#the-list').find('[data-slug="wp-spell-check"] span.deactivate a');
					var popup = $('#wpsc_survey_modal');
					var wpsc_form = popup.find('form');
					isOpen = false;
					
					//Bring up survey when attempting to deactivate plugin
					link.on('click', function(event) {
						event.preventDefault();
						popup.css('display', 'table');
						isOpen = true;
					});
					
					//Deactive plugin as normal when they click skip survey link
					wpsc_form.on('click', '.wpsc_skip_survey', function(event) {
						event.preventDefault
						location.href = link.attr('href');
					});
					
					wpsc_form.on('change', 'input[name=opt]', function(event) {
						event.preventDefault();
						wpsc_form.find('.wpsc_survey_option_details, .error').hide();
						wpsc_form.find('.wpsc_survey_label').removeClass('selected');
						$(this).closest('.wpsc_survey_label').addClass('selected').find('.wpsc_survey_option_details').show();
					});
					
					//wpsc_form.on('change', 'input[name=contact]', function(event) {
					//	event.preventDefault();
					//	if ($('input[name=contact]:checked').val() == 'Yes') {
					//		$('.wpsc-contact-form').show();
					//	} else {
					//		$('.wpsc-contact-form').hide();
					//	}
					//});
					
					//Submit survey
					wpsc_form.submit(function(event) { 
						event.preventDefault();
                                                zapierURL = 'https://hooks.zapier.com/hooks/catch/2309605/3g2tsue/';
						
						if ( ! wpsc_form.find('input[type=radio]:checked').val()) {
							wpsc_form.find('wpsc_deactive_survey_message').prepend('<span class="error">Please select an option for deactivating</span>');
							return;
						}
						
						var form_data = {
							reason: wpsc_form.find('.selected input[type=radio]').val(),
							details: wpsc_form.find('.selected input[type=text]').val(),
							site: '<?php echo esc_url( home_url() ); ?>',
							wordpress_ver: '<?php echo esc_html( $wp_version ); ?>',
							php_ver: '<?php echo phpversion(); ?>',
							theme_name: '<?php echo wp_get_theme()->name; ?>',
                                                        parent_name: '<?php if ( is_object( wp_get_theme()->parent() ) ) { echo wp_get_theme()->parent()->name; } else { echo ""; } ?>',
							plugin_ver: '<?php echo esc_html( $wpsc_version ); ?>'
						}
						
						wpsc_form.find('.wpsc_survey_sending').css('display','block');
						
						var submit = $.post('<?php echo esc_html( $this->api ); ?>', form_data);
												
												if ($('input[name=contact]:checked').val() == 'Yes') { window,open("https://www.wpspellcheck.com/report-an-issue?utm_source=baseplugin&utm_campaign=plugindeac&utm_content=9.21","_blank"); }
												if ($('input[name=opt]:checked').val() == "Don't know what it does") { window,open("https://www.wpspellcheck.com/support?utm_source=baseplugin&utm_campaign=plugindeac&utm_content=9.21","_blank"); }
                                                                                                
                                                //Submit the form data to the Zapier webhook
                                                fetch(zapierURL, {
                                                    method: 'GET',
                                                    headers: {
                                                        'Accept': 'application/json',
                                                        'Content-Type': 'application/json'
                                                    },
                                                    body: JSON.stringify(form_data),
                                                })
                                                
                                                //Submit the form as normal
						submit.always(function() {
							location.href = link.attr('href');
						});
					});
					
					$(document).keyup(function(event) {
						if (27 === event.keyCode && isOpen) {
							popup.hide();
							isOpen = false;
							link.focus();
						}
					});
					
					$('.wpsc-close-survey').click(function(event) {
							popup.hide();
							isOpen = false;
							link.focus();
					});
				});
			</script>
			 <?php
	}

	public function run_css() {
		if ( ! $this->is_plugins_page() ) {
			return;
		}

		?>
			<style type="text/css">
				.wpsc_survey_modal {
				display: none;
				table-layout: fixed;
				position: fixed;
				z-index: 9999;
				width: 100%;
				height: 100%;
				text-align: center;
				font-size: 14px;
				top: 0;
				left: 0;
				background: rgba(0,0,0,0.8);
			}
			.wpsc_survey_wrapper {
				display: table-cell;
				vertical-align: middle;
			}
			.wpsc_survey_form {
				background-color: #fff;
				max-width: 550px;
				margin: 0 auto;
				padding: 30px;
				text-align: left;
			}
			.wpsc_deactivate_survey .error {
				display: block;
				color: red;
				margin: 0 0 10px 0;
			}
			.wpsc_survey_title {
				display: block;
				font-size: 18px;
				font-weight: 700;
				text-transform: uppercase;
				border-bottom: 1px solid #ddd;
				padding: 0 0 18px 0;
				margin: 0 0 18px 0;
			}
			.wpsc_survey_desc {
				display: block;
				font-weight: 600;
				margin: 0 0 18px 0;
			}
			.wpsc_survey_label {
				display: block;
				margin: 10px;
			}
			.wpsc_survey_option {
				margin: 0 0 10px 0;
			}
			.wpsc_survey_option_input {
				margin-right: 10px !important;
			}
						.wpsc-contact-form {
								width: 90%;
				margin: 10px 0 0 30px;
						}
			.wpsc_survey_option_details {
				display: none;
				width: 90%;
				margin: 10px 0 0 30px;
			}
			.wpsc_survey_footer {
				margin-top: 18px;
			}
			.wpsc_skip_survey {
				float: right;
				font-size: 13px;
				color: #ccc;
				text-decoration: none;
				padding-top: 7px;
			}
			.wpsc_survey_sending {
				display: none;
				margin: 0 0 10px;
			}
			.wpsc_survey_footer button {
				background: green!important;
				border-color: #006a00!important;
				box-shadow: 0 1px #006a00!important;
				text-shadow: 1px 1px 1px #006a00!important;
			}
			</style>
			
		<?php
	}


	public function create_form() {
		if ( ! $this->is_plugins_page() ) {
			return;
		}

		$numbers = range( 1, 4 );
		shuffle( $numbers );

		?>
			<<div class="wpsc_survey_modal" id="wpsc_survey_modal">
				<div class="wpsc_survey_wrapper">
						<form class="wpsc_survey_form">
						<a href="#" class="wpsc-close-survey" style="float: right; text-decoration: none; font-weight: bold;">X</a>
						<span class="wpsc_survey_title">Plugin Feedback</span>
						<span class="wpsc_survey_desc">Please share why you are deactivating WP Spell Check</span>
						<div class="wpsc_survey_options">
				   <?php
					foreach ( $numbers as $number ) {
						if ( 1 === $number ) {
							?>
							<label for="wpsc_survey_option_not_working" class="wpsc_survey_label">
								<input id="wpsc_survey_option_not_working" class="wpsc_survey_option_input" type="radio" name="opt" value="Not Working on my Site" />
								<span class="wpsc_survey_option_reason">It is not working on my site</span>
								<div class="wpsc_survey_option_details">Would you like us to contact you to fix this issue?<br />
									<label for="wpsc_survey_option_contact_yes" class="wpsc_survey_label_further" style="margin: 10px 0; display:block;">
										<input id="wpsc_survey_option_contact_yes" class="wpsc_survey_option_input" type="radio" name="contact" value="Yes">
										<span class="wpsc_survey_option_reason_yes">Yes</span><br />
									</label>
									<label for="wpsc_survey_option_contact_no" class="wpsc_survey_label_further">
										<input id="wpsc_survey_option_contact_no" class="wpsc_survey_option_input" type="radio" name="contact" value="No">
										<span class="wpsc_survey_option_reason_no">No</span><br />
									</label>
									<div style="position: absolute; margin: -57px 0 0 60px; width: 440px;" class="wpsc-contact-form"><a href="https://www.wpspellcheck.com/report-an-issue?utm_source=baseplugin&utm_campaign=plugindeac&utm_content=9.21" target="_blank">Get in touch</a> with us to fix this problem and get Free access to WP Spell Check Pro for 1 year! (valued at $99)</div>
								</div>
							</label>
							<?php
						} elseif ( 2 === $number ) {
							?>
							<label for="wpsc_survey_option_dont_know" class="wpsc_survey_label">
								<input id="wpsc_survey_option_dont_know" class="wpsc_survey_option_input" type="radio" name="opt" value="Don't know what it does" />
								<span class="wpsc_survey_option_reason">It is confusing as to how the plugin works</span>
								<div class="wpsc_survey_option_details">You may check out our <strong>free video tutorials</strong> for WP Spell Check <a href="https://www.wpspellcheck.com/support?utm_source=baseplugin&utm_campaign=plugindeac&utm_content=9.21" target="_blank">here</a></div>
							</label>
							  <?php
						} elseif ( 3 === $number ) {
							?>
							<label for="wpsc_survey_option_no_need" class="wpsc_survey_label">
								<input id="wpsc_survey_option_no_need" class="wpsc_survey_option_input" type="radio" name="opt" value="No longer need the plugin" />
								<span class="wpsc_survey_option_reason">I no longer need the plugin</span>
							</label>
							<?php
						} elseif ( 4 === $number ) {
							?>
							<label for="wpsc_survey_option_temp" class="wpsc_survey_label">
								<input id="wpsc_survey_option_temp" class="wpsc_survey_option_input" type="radio" name="opt" value="Temporary disable" />
								<span class="wpsc_survey_option_reason">This is temporary, I will reactivate soon</span>
							</label>
							<?php
						}
					}
					?>
							<label for="wpsc_survey_option_other" class="wpsc_survey_label">
								<input id="wpsc_survey_option_other" class="wpsc_survey_option_input" type="radio" name="opt" value="Other Reason" />
								<span class="wpsc_survey_option_reason">Other reason</span>
								<input class="wpsc_survey_option_details" type="text" placeholder="Please enter details" />
																<div class="wpsc_survey_option_details"><a href="https://www.wpspellcheck.com/report-an-issue?utm_source=baseplugin&utm_campaign=plugindeac&utm_content=9.21" target="_blank">Get in touch</a> with us to fix this problem and get Free access to WP Spell Check Pro for 1 year! (valued at $99)</div>
							</label>
						</div>
						<div class="wpsc_survey_footer">
							<span class="wpsc_survey_sending">Sending Feedback...</span>
							<button type="submit" class="wpsc_survey_submit button button-primary button-large">Submit & Deactivate</button>
							<a href="#" class="wpsc_skip_survey">Skip & Deactivate</a>
						</div>
					</form>
				</div>
			</div>
		 <?php
	}

}
?>
