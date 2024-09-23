<?php

class Wpscx_Scanner {
	private $settings_list;
	private $wpscx_ignore_list;
	private $to_scan;
	private $haystack;

	function __construct() {
		//wpscx_print_debug("wpscx scanner construct first line", time(), 0, round(memory_get_usage() / 1000,5), 0);
		global $wpdb;
		$settings_table = $wpdb->prefix . 'spellcheck_options';
		$dict_table     = $wpdb->prefix . 'spellcheck_dictionary';
		$ignore_table   = $wpdb->prefix . 'spellcheck_words';

		$this->settings_list = $wpdb->get_results( "SELECT * FROM $settings_table" );
		$this->ignore_list   = $wpdb->get_results( "SELECT word FROM $ignore_table WHERE ignore_word = true" );
		$wpscx_dict_list     = $wpdb->get_results( "SELECT * FROM $dict_table" );

		//wpscx_print_debug("wpscx scanner construct init", time(), 0, round(memory_get_usage() / 1000,5), 0);

		/*$loc = plugins_url("/dict/" . $wpsc_settings[11]->option_value . ".pws", __FILE__ );
		$contents = wp_remote_retrieve_body(  wp_remote_get( $loc ) );

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);

		//wpscx_print_debug("wpscx scanner construct Get Dict Files", time(), 0, round(memory_get_usage() / 1000,5), 0);

		foreach($dict_file as $value) {
			$this->haystack[strtoupper(stripslashes($value))] = 1;
		}

		foreach ($wpscx_dict_list as $value) {
			$this->haystack[strtoupper(stripslashes($value->word))] = 1;
		}

		foreach ($this->ignore_list as $value) {
			$this->haystack[strtoupper(stripslashes($value->word))] = 1;
		}*/
		//wpscx_print_debug("wpscx scanner construct last line", time(), 0, round(memory_get_usage() / 1000,5), 0);
	}

	function sql_insert( $error_list, $page_type, $table_name = '' ) {
	}

	function clean_text( $content ) {
		$content = preg_replace( '/\s/u', ' ', $content );
		$content = str_replace( '’', "'", $content );
		$content = str_replace( '`', "'", $content );
		$content = str_replace( '“', ' ', $content );
		$content = str_replace( "'''", "'", $content );
		$content = str_replace( '(', ' ', $content );
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
		$content = trim( $content, "'" );
		$content = preg_replace( '/(((?<=\s|^))[0-9|$][0-9.,]+((?=\s|$)|(c|k|b|s|m|st|th|nd|rd|mb|kg|gb|tb|yb|sec|hr|min|am|pm|a.m.|p.m.)(\s|$|,|<|\.)))/ui', '', $content );
		//Spanish characters: áÁéÉíÍñÑóÓúÚüÜ¿¡«»
		//French Characters: ÀàÂâÆæÈèÉéÊêËëÎîÏïÔôŒœÙùÛûÜüŸÿ
		$content = preg_replace( "/([^0-9'’`ÀàÂâÆæÈèÉéÊêËëÎîÏïÔôŒœÙùÛûÜüŸÿüáÁéÉíÍñÑóÓúÚüÜ¿¡«»€a-zA-Z]|'s)+(\s|$|\"|')/ius", ' ', $content );
		$content = preg_replace( "/(\s|^)(\S+[^ 0-9a-zA-Z'’`ÀàÂâÆæÈèÉéÊêËëÎîÏïÔôŒœÙùÛûÜüŸÿüáÁéÉíÍñÑóÓúÚüÜ¿¡«»€!@#$%^&*()\-=_+,.\/;'[\]\\<>?:\"{}|]+\S+)(\s|$)/u", ' ', $content );

		$content = str_replace( '§', ' ', $content );
		$content = str_replace( '¢', ' ', $content );
		$content = str_replace( '¨', ' ', $content );
		$content = str_replace( '\\', ' ', $content );
		$content = preg_replace( "/\r?\n|\r/u", ' ', $content );
                
                return $content;
	}

	function ignore_caps( $word ) {
		return ( strtoupper( $word ) !== $word || 'false' === $this->settings_list[3]->option_value );
	}

	function content_filter( $content ) {
		$divi_check = wp_get_theme();
		if ( 'Divi' === $divi_check->name || 'Divi' === $divi_check->parent_name || 'Bridge' === $divi_check->parent_name || 'Bridge' === $divi_check->name ) {
				global $wp_query;
				//$wp_query->is_singular = true;

				$content = apply_filters( 'the_content', $content );

				return $content;
		} else {
				return $content;
		}
	}

	function clean_script( $content ) {
		$content = preg_replace( '@<style[^>]*?>.*?</style>@siu', ' ', $content );
		$content = preg_replace( '@<script[^>]*?>.*?</script>@siu', ' ', $content );
		$content = preg_replace( '/(\<.*?\>)/', ' ', $content );
		$content = preg_replace( '/<iframe.+<\/iframe>/', ' ', $content );

		return $content;
	}

	function clean_shortcode( $content ) {
		return preg_replace( '/(\[.*?\])/', ' ', $content );
	}

	function clean_html( $content ) {
		return html_entity_decode( strip_tags( $content ), ENT_QUOTES, 'utf-8' );
	}

	function clean_email( $content ) {
		return preg_replace( '/\S+\@\S+\.\S+/', ' ', $content );
	}

	function clean_website( $content ) {
		$content = preg_replace( '/((http|https|ftp)\S+)/', '', $content );
		$content = preg_replace( '/www\.\S+/', '', $content );                
		$content = preg_replace( '/\S+\.(COM|NET|ORG|GOV|INFO|XYZ|US|TOP|LOAN|BIZ|WANG|WIN|CLUB|ONLINE|VIP|MOBI|BID|SITE|MEN|TECH|PRO|SPACE|SHOP|WEBSITE|ASIA|KIWI|XIN|LINK|PARTY|TRADE|LIFE|STORE|NAME|CLOUD|STREAM|CAT|LIVE|TEL|XXX|ACCOUNTANT|DATE|DOWNLOAD|BLOG|WORK|RACING|REVIEW|TODAY|CLICK|ROCKS|NYC|WORLD|EMAIL|SOLUTIONS|NEWS|TOKYO|DESIGN|GURU|LONDON|LTD|ONE|PUB|REALTY|COMPANY|BERLIN|WEBCAM|HOST|PHOTOGRAPHY|PRESS|SCIENCE|FAITH|JOBS|REALTOR|REN|CITY|OVH|RED|AGENCY|SERVICES|MEDIA|GROUP|CENTER|STUDIO|GLOBAL|NINJA|TECHNOLOGY|TIPS|BAYERN|EXPERT|SALE|AMSTERDAM|DIGITAL|ACADEMY|NETWORK|HAMBURG|gdn|DE|CN|UK|NL|EU|RU|TK|AR|BR|IT|PL|FR|AU|CH|CA|ES|JP|KR|DK|BE|SE|AT|CZ|IN|HU|NO|TW|NZ|MX|PT|CL|FI|HK|TR|TRAVEL|AERO|COOP|MUSEUM|SHOW)(?:\S+)?(?:\s|$)/i', ' ', $content );

		return $content;
	}

	function clean_all( $content, $wpsc_settings ) {
		if ( strpos( $content, '[fep_submission_form]' ) ) {
			return '';
		}
		try { // Try to clean up all of the content to prepare it for scanning
			$content = $this->clean_script( $content );
			$content = $this->clean_shortcode( $content );
			$content = $this->clean_html( $content );

			if ( 'true' === $this->settings_list[23]->option_value ) {
					$content = $this->clean_email( $content );
			}
			if ( 'true' === $this->settings_list[24]->option_value ) {
					$content = $this->clean_website( $content );
			}

			$content = $this->clean_text( $content, $debug );

			return $content;
		} catch ( Exception $e ) {
			return ''; //If an error occurred while cleaning the content, send back blank content to skip to next entry
		}
	}
}
