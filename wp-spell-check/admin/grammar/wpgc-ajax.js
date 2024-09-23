function wpgcx_init_scan() {
	jQuery.ajax({
		url: ajax_object.ajax_url,
		type: "POST",
		data: {
			action: 'results_gc',
		},
		dataType: 'html',
		success: function(response) {
			if (response == 'true') { wpgcx_recheck_scan(); console.log(response); }
			else { window.setInterval( wpgcx_init_scan(),1000 ); console.log(response); }
		}
	});
        return true;
}

function wpgcx_recheck_scan() {
	jQuery.ajax({
		url: ajax_object.ajax_url,
		type: "POST",
		data: {
			action: 'results_gc',
		},
		dataType: 'html',
		success: function(response) {
			if (response == 'true') { window.setInterval(wpgcx_recheck_scan(), 1000 ); console.log(response); }
			else { wpgcx_finish_scan(); console.log(response); }
		}
	});
        return true;
}

function wpgcx_finish_scan() {
	jQuery.ajax({
		url: ajax_object.ajax_url,
		type: "POST",
		data: {
			action: 'finish_scan_gc',
		},
		dataType: 'html',
		success: function(response) {
			window.location.href = encodeURI("?page=wp-spellcheck-grammar.php&wpsc-script=noscript");
		}
	});
        return true;
}

window.setInterval( wpgcx_recheck_scan(),500 );