/**
 * HTML/Broken Code Scan AJAX Handler
 *
 * Handles AJAX polling to check the status of HTML/broken code scans.
 * Polls the server until scan completion, then redirects to results page.
 */
function wphcx_init_scan() {
	jQuery.ajax(
		{
			url: wphcx_broken_ajax_object.ajax_url,
			type: "POST",
			data: {
				action: "results_hc",
				nonce: wphcx_broken_ajax_object.wpsc_hc_scan_nonce,
			},
			dataType: "html",
			success: function (response) {
				if (response == "true") {
					wphcx_recheck_scan();
				} else {
					window.setInterval( wphcx_init_scan(), 1000 );
				}
			},
		}
	);
	return true;
}

function wphcx_recheck_scan() {
	jQuery.ajax(
		{
			url: wphcx_broken_ajax_object.ajax_url,
			type: "POST",
			data: {
				action: "results_hc",
				nonce: wphcx_broken_ajax_object.wpsc_hc_scan_nonce,
			},
			dataType: "html",
			success: function (response) {
				if (response == "true") {
					window.setInterval( wphcx_recheck_scan(), 1000 );
				} else {
					wphcx_finish_scan();
				}
			},
		}
	);
	return true;
}

function wphcx_finish_scan() {
	jQuery.ajax(
		{
			url: wphcx_broken_ajax_object.ajax_url,
			type: "POST",
			data: {
				action: "finish_scan_hc",
				nonce: wphcx_broken_ajax_object.wpsc_finish_html_scan_nonce,
			},
			dataType: "html",
			success: function (response) {
				window.location.href = encodeURI(
					"?page=wp-spellcheck-html.php&wpsc-script=noscript"
				);
			},
		}
	);
	return true;
}

window.setInterval( wphcx_recheck_scan(), 500 );
