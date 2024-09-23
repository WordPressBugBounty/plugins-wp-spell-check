function wpscx_init_scan() {
	jQuery.ajax({
		url: ajax_object.ajax_url,
		type: "POST",
		data: {
			action: 'results_sc',
		},
		dataType: 'html',
		success: function(response) {
			if (response == 'true') { wpscx_recheck_scan(); console.log(response); }
			else { window.setInterval( wpscx_init_scan(),1000 ); console.log(response); }
		}
	});
        return true;
}

function wpscx_recheck_scan() {
	jQuery.ajax({
		url: ajax_object.ajax_url,
		type: "POST",
		data: {
			action: 'results_sc',
		},
		dataType: 'html',
		success: function(response) {
			if (response == 'true') { window.setInterval(wpscx_recheck_scan(), 1000 ); console.log(response); }
			else { wpscx_finish_scan(); console.log(response); }
		}
	});
        return true;
}

function wpscx_finish_scan() {
	jQuery.ajax({
		url: ajax_object.ajax_url,
		type: "POST",
		data: {
			action: 'finish_scan',
		},
		dataType: 'html',
		success: function(response) {
			window.location.href = encodeURI("?page=wp-spellcheck.php&wpsc-script=noscript&wpsc-scan-tab=" + ajax_object.wpsc_scan_tab);
		}
	});
        return true;
}

window.setInterval( wpscx_recheck_scan(),1000 );