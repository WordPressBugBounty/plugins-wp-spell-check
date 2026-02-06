function wpgcx_init_scan() {
	jQuery.ajax(
		{
			url: wpgcx_gram_ajax_object.ajax_url,
			type: "POST",
			data: {
				action: "results_gc",
				nonce: wpgcx_gram_ajax_object.wpgc_scan_nonce,
			},
			dataType: "html",
			success: function (response) {
				if (response == "true") {
					wpgcx_recheck_scan();
				} else {
					window.setInterval( wpgcx_init_scan(), 1000 );
				}
			},
		}
	);
	return true;
}

function wpgcx_recheck_scan() {
	jQuery.ajax(
		{
			url: wpgcx_gram_ajax_object.ajax_url,
			type: "POST",
			data: {
				action: "results_gc",
				nonce: wpgcx_gram_ajax_object.wpgc_scan_nonce,
			},
			dataType: "html",
			success: function (response) {
				if (response == "true") {
					window.setInterval( wpgcx_recheck_scan(), 1000 );
				} else {
					wpgcx_finish_scan();
				}
			},
		}
	);
	return true;
}

function wpgcx_finish_scan() {
	jQuery.ajax(
		{
			url: wpgcx_gram_ajax_object.ajax_url,
			type: "POST",
			data: {
				action: "finish_scan_gc",
				nonce: wpgcx_gram_ajax_object.wpgc_finish_scan_nonce,
			},
			dataType: "html",
			success: function (response) {
				window.location.href = encodeURI(
					"?page=wp-spellcheck-grammar.php&wpsc-script=noscript"
				);
			},
		}
	);
	return true;
}

window.setInterval( wpgcx_recheck_scan(), 500 );
