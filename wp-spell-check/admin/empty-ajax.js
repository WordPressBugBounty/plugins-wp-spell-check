/**
 * Empty Fields/SEO Scan AJAX Handler
 *
 * Handles AJAX polling to check the status of empty fields/SEO scans.
 * Polls the server until scan completion, then redirects to results page.
 */
function wpscex_init_scan() {
	jQuery.ajax(
		{
			url: ajax_object.ajax_url,
			type: "POST",
			data: {
				action: "emptyresults_sc",
				nonce: ajax_object.wpsc_empty_scan_nonce,
			},
			dataType: "html",
			success: function (response) {
				if (response == "true") {
					wpscex_recheck_scan();
				} else {
					window.setInterval( wpscex_init_scan(), 1000 );
				}
			},
		}
	);
	return true;
}

function wpscex_recheck_scan() {
	jQuery.ajax(
		{
			url: ajax_object.ajax_url,
			type: "POST",
			data: {
				action: "emptyresults_sc",
				nonce: ajax_object.wpsc_empty_scan_nonce,
			},
			dataType: "html",
			success: function (response) {
				if (response == "true") {
					window.setInterval( wpscex_recheck_scan(), 1000 );
				} else {
					wpscex_finish_scan();
				}
			},
		}
	);
	return true;
}

function wpscex_finish_scan() {
	jQuery.ajax(
		{
			url: ajax_object.ajax_url,
			type: "POST",
			data: {
				action: "finish_empty_scan",
				nonce: ajax_object.wpsc_finish_empty_scan_nonce,
			},
			dataType: "html",
			success: function (response) {
				window.location.href = encodeURI(
					"?page=wp-spellcheck-seo.php&wpsc-script=noscript",
				);
			},
		}
	);
	return true;
}

window.setInterval( wpscex_recheck_scan(), 1000 );
