/**
 * Broken Code Results Page JavaScript
 * Extracted from inline scripts in class-html-results.php
 * Handles scan initiation, progress tracking, and UI interactions
 */

(function ($) {
	"use strict";

	// Ensure localized data exists
	if (typeof wphcHtmlResults === "undefined") {
		console.error(
			"wphcHtmlResults object not found. Make sure script is localized properly.",
		);
		return;
	}

	// Initialize scan state variables
	var scan_in_progress = wphcHtmlResults.scan_in_progress || false;
	var scanStartTime;

	// Initialize ajax_object if not already defined (may be localized by wphc-results-ajax script)
	// WordPress localizes scripts as global variables. Ensure ajax_object always exists.
	// Check if WordPress already created it, otherwise create it from localized data
	if (typeof window.ajax_object === "undefined") {
		// Try to use existing global ajax_object if WordPress created it
		if (typeof ajax_object !== "undefined") {
			window.ajax_object = ajax_object;
		} else {
			// Create ajax_object from localized data
			window.ajax_object = {
				ajax_url: wphcHtmlResults.ajax_url,
				wpsc_start_scan_bc_nonce: wphcHtmlResults.wpsc_start_scan_bc_nonce,
				wpsc_hc_scan_nonce: wphcHtmlResults.wpsc_hc_scan_nonce,
				wpsc_finish_html_scan_nonce:
				wphcHtmlResults.wpsc_finish_html_scan_nonce,
				wpsc_display_results_html_nonce:
				wphcHtmlResults.wpsc_display_results_html_nonce,
				wpsc_get_stats_code_nonce: wphcHtmlResults.wpsc_get_stats_code_nonce,
			};
		}
	}

	// Auto-click handler for "Entire Site" scan
	if (wphcHtmlResults.auto_click_enabled) {
		$( document ).ready(
			function () {
				window.setTimeout(
					function () {
						$( ".wpscScanSite" ).click();
					},
					1000
				);
			}
		);
	}

	// Scan progress check function
	function wphcx_recheck_scan_temp() {
		// ajax_object is initialized at the top of the file

		$.ajax(
			{
				url: window.ajax_object.ajax_url,
				type: "POST",
				data: {
					action: "results_hc",
					nonce: window.ajax_object.wpsc_hc_scan_nonce,
				},
				dataType: "html",
				success: function (response) {
					if (response == "true") {
						window.setInterval( wphcx_recheck_scan_temp(), 1000 );
					} else {
						wphcx_finish_scan_temp();
					}
				},
				error: function (xhr, status, thrownError) {},
			}
		);
	}

	// Finish scan and display results
	function wphcx_finish_scan_temp() {
		// ajax_object is initialized at the top of the file

		$.ajax(
			{
				url: window.ajax_object.ajax_url,
				type: "POST",
				data: {
					action: "wpscx_display_results_html",
					nonce: window.ajax_object.wpsc_display_results_html_nonce,
				},
				dataType: "html",
				success: function (response) {
					var scanTime    = new Date();
					var scanEndTime = scanTime.getTime();
					var scanFinal   = (scanEndTime - scanStartTime) / 1000;
					$( ".wpscScan" ).removeClass( "wpsc-button-greyout" ); // Remove button greyout
					scan_in_progress = false;
					$( "#wpsc-table-results" ).html( response.replace( "null", "" ) );
					if (typeof wpscx_connect_listeners === "function") {
						wpscx_connect_listeners();
					}
					$( "#wpscScanMessage" ).html( "The scan has finished" );

					wphcx_show_stats( scanFinal );
				},
				error: function (xhr, status, thrownError) {},
			}
		);
	}

	// Show scan statistics
	function wphcx_show_stats(x) {
		// ajax_object is initialized at the top of the file

		$.ajax(
			{
				url: window.ajax_object.ajax_url,
				type: "POST",
				data: {
					action: "wpscx_get_stats_code",
					scantime: x,
					nonce: window.ajax_object.wpsc_get_stats_code_nonce,
				},
				dataType: "json",
				success: function (response) {
					$( ".sc-type" ).html(
						"Errors found on <span class='wpsc-site-span'> Entire Site</span>: " +
						response.totalErrors,
					);
					if (Number( response.pageCount ) >= Number( response.totalPages )) {
						$( ".sc-post" ).html(
							"Posts scanned: " + response.totalPosts + "/" + response.totalPosts,
						);
					} else {
						$( ".sc-post" ).html(
							"Posts scanned: " + response.postCount + "/" + response.totalPosts,
						);
					}
					if (Number( response.pageCount ) >= Number( response.totalPages )) {
						$( ".sc-page" ).html(
							"Pages scanned: " + response.totalPages + "/" + response.totalPages,
						);
					} else {
						$( ".sc-page" ).html(
							"Pages scanned: " + response.pageCount + "/" + response.totalPages,
						);
					}
					$( ".sc-time" ).html( "Last scan took " + response.scanTime );
					$( ".next-page" ).click(
						function (e) {
							e.preventDefault();
							window.location.href = "?page=wp-spellcheck-html.php&paged=2";
						}
					);
					var last_page = parseInt( response.totalErrors / 20 ) + 1;
					$( ".last-page" ).click(
						function (e) {
							e.preventDefault();
							window.location.href =
							"?page=wp-spellcheck-html.php&paged=" + last_page;
						}
					);
				},
				error: function (xhr, status, thrownError) {},
			}
		);
	}

	// Scan button click handler
	$( ".wpscScan" ).click(
		function (event) {
			event.preventDefault();
			if (scan_in_progress) {
				return;
			}
			scan_in_progress = true;

			// ajax_object is initialized at the top of the file, but update nonces if needed
			if (typeof window.ajax_object.wpsc_start_scan_bc_nonce === "undefined") {
				window.ajax_object.ajax_url                        = wphcHtmlResults.ajax_url;
				window.ajax_object.wpsc_start_scan_bc_nonce        =
				wphcHtmlResults.wpsc_start_scan_bc_nonce;
				window.ajax_object.wpsc_hc_scan_nonce              =
				wphcHtmlResults.wpsc_hc_scan_nonce;
				window.ajax_object.wpsc_finish_html_scan_nonce     =
				wphcHtmlResults.wpsc_finish_html_scan_nonce;
				window.ajax_object.wpsc_display_results_html_nonce =
				wphcHtmlResults.wpsc_display_results_html_nonce;
				window.ajax_object.wpsc_get_stats_code_nonce       =
				wphcHtmlResults.wpsc_get_stats_code_nonce;
			}

			var scanType = $( this ).attr( "value" );

			$( "#wpscScanMessage" ).html(
				'<img src="' +
				wphcHtmlResults.loading_gif_url +
				'" alt="Scan in Progress" /> Starting New Scan',
			);
			$( ".wpscScan" ).addClass( "wpsc-button-greyout" ); // Greyout buttons

			var scanTime  = new Date();
			scanStartTime = scanTime.getTime();

			$.ajax(
				{
					url: window.ajax_object.ajax_url,
					type: "POST",
					data: {
						type: scanType,
						action: "wpscx_start_scan_bc",
						nonce: window.ajax_object.wpsc_start_scan_bc_nonce,
					},
					dataType: "html",
					success: function (response) {
						var scanTime    = new Date();
						var scanEndTime = scanTime.getTime();
						var scanFinal   = (scanEndTime - scanStartTime) / 1000;

						$( "#wpscScanMessage" ).html( response ); // update the scan message to display the scan started message
						window.setInterval( wphcx_recheck_scan_temp(), 500 );
						$( "tr.wpsc-row" ).animate(
							{ opacity: 0 },
							500,
							function () {
								$( "tr.wpsc-row" ).hide();
							}
						);

						$( document ).ready(
							function () {
								var mouseover_visible = false;
								$( ".wpsc-mouseover-button-refresh" )
								.mouseenter(
									function () {
										$( ".wpsc-mouseover-text-refresh" ).css( "z-index", "100" );
										$( ".wpsc-mouseover-text-refresh" ).animate(
											{ opacity: 1.0 },
											400,
											function () {
												mouseover_visible = true;
											},
										);
									}
								)
								.mouseleave(
									function () {
											$( ".wpsc-mouseover-text-refresh" ).css( "z-index", "-100" );
											$( ".wpsc-mouseover-text-refresh" ).animate( { opacity: 0 }, 400 );
											mouseover_visible = false;
									}
								);
								$( ".wpsc-mouseover-button-refresh" ).click(
									function () {
										if ( ! mouseover_visible) {
											$( ".wpsc-mouseover-text-refresh" ).stop();
											$( ".wpsc-mouseover-text-refresh" ).css( "z-index", "100" );
											$( ".wpsc-mouseover-text-refresh" ).animate(
												{ opacity: 1.0 },
												400,
												function () {
													mouseover_visible = true;
												},
											);
										} else {
											$( ".wpsc-mouseover-text-refresh" ).css( "z-index", "-100" );
											$( ".wpsc-mouseover-text-refresh" ).animate( { opacity: 0 }, 400 );
											mouseover_visible = false;
										}
									}
								);
							}
						);
					},
					error: function (xhr, status, thrownError) {
						window.setInterval( wphcx_recheck_scan_temp(), 500 );
					},
				}
			);
		}
	);
})( jQuery );
