/**
 * Spell Check Scan AJAX Handler
 *
 * Handles AJAX polling to check the status of spell check scans.
 * Polls the server until scan completion, then redirects to results page.
 */
function wpscx_init_scan() {
	jQuery.ajax(
		{
			url: wpscx__spell_ajax_object.ajax_url,
			type: "POST",
			data: {
				action: "results_sc",
				nonce: wpscx__spell_ajax_object.wpsc_scan_nonce,
			},
			dataType: "html",
			success: function (response) {
				if (response == "true") {
					wpscx_recheck_scan();
				} else {
					setTimeout( wpscx_init_scan, 1000 );
				}
			},
		}
	);
	return true;
}

function wpscx_recheck_scan() {
	jQuery.ajax(
		{
			url: wpscx__spell_ajax_object.ajax_url,
			type: "POST",
			data: {
				action: "results_sc",
				nonce: wpscx__spell_ajax_object.wpsc_scan_nonce,
			},
			dataType: "html",
			success: function (response) {
				if (response == "true") {
					setTimeout( wpscx_recheck_scan, 1000 );
				} else {
					wpscx_finish_scan();
				}
			},
		}
	);
	return true;
}

function wpscx_finish_scan() {
	jQuery.ajax(
		{
			url: wpscx__spell_ajax_object.ajax_url,
			type: "POST",
			data: {
				action: "wpscx_finish_scan",
				nonce: wpscx__spell_ajax_object.wpsc_finish_scan_nonce,
			},
			dataType: "html",
			success: function (response) {
				var redirectUrl = "?page=wp-spellcheck.php&wpsc-script=noscript";
				if (
				typeof wpscx__spell_ajax_object !== "undefined" &&
				typeof wpscx__spell_ajax_object.wpsc_scan_tab !== "undefined" &&
				wpscx__spell_ajax_object.wpsc_scan_tab
				) {
					redirectUrl +=
					"&wpsc-scan-tab=" + wpscx__spell_ajax_object.wpsc_scan_tab;
				}
				window.location.href = encodeURI( redirectUrl );
			},
			error: function (xhr, status, thrownError) {},
		}
	);
	return true;
}

setTimeout( wpscx_recheck_scan, 1000 );
