function wphcx_init_scan() {
	jQuery.ajax({
		url: ajax_object.ajax_url,
		type: "POST",
		data: {
			action: 'results_hc',
		},
		dataType: 'html',
		success: function(response) {
			if (response == 'true') { wphcx_recheck_scan(); console.log(response); }
			else { window.setInterval( wphcx_init_scan(),1000 ); console.log(response); }
		}
	});
        return true;
}

function wphcx_recheck_scan() {
	jQuery.ajax({
		url: ajax_object.ajax_url,
		type: "POST",
		data: {
			action: 'results_hc',
		},
		dataType: 'html',
		success: function(response) {
			if (response == 'true') { window.setInterval(wphcx_recheck_scan(), 1000 ); console.log(response); }
			else { wphcx_finish_scan(); console.log(response); }
		}
	});
        return true;
}

function wphcx_finish_scan() {
	jQuery.ajax({
		url: ajax_object.ajax_url,
		type: "POST",
		data: {
			action: 'finish_scan_hc',
		},
		dataType: 'html',
		success: function(response) {
			window.location.href = encodeURI("?page=wp-spellcheck-html.php&wpsc-script=noscript");
		}
	});
        return true;
}

window.setInterval( wphcx_recheck_scan(),500 );