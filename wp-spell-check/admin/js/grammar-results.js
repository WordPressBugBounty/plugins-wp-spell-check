/**
 * Grammar Results Page JavaScript
 * Extracted from inline scripts in class-grammar-results.php
 * Handles scan initiation, progress tracking, and UI interactions
 */

(function ($) {
	"use strict";

	// Ensure localized data exists
	if (typeof wpgcGrammarResults === "undefined") {
		console.error(
			"wpgcGrammarResults object not found. Make sure script is localized properly."
		);
		return;
	}

	// Initialize scan state variables
	var scan_in_progress = wpgcGrammarResults.scan_in_progress || false;
	var scanStartTime;
	var loadingGifUrl = wpgcGrammarResults.loading_gif_url || "";

	// Initialize wpgcx_gram_ajax_object if not already defined (may be localized by wpgc-results-ajax script)
	// WordPress localizes scripts as global variables. Ensure wpgcx_gram_ajax_object always exists.
	// Check if WordPress already created it, otherwise create it from localized data
	if (typeof window.wpgcx_gram_ajax_object === "undefined") {
		// Try to use existing global wpgcx_gram_ajax_object if WordPress created it
		if (typeof wpgcx_gram_ajax_object !== "undefined") {
			window.wpgcx_gram_ajax_object = wpgcx_gram_ajax_object;
		} else {
			// Create wpgcx_gram_ajax_object from localized data
			window.wpgcx_gram_ajax_object = {
				ajax_url: wpgcGrammarResults.ajax_url,
				wpgc_start_scan_nonce: wpgcGrammarResults.wpgc_start_scan_nonce,
				wpgc_scan_nonce: wpgcGrammarResults.wpgc_scan_nonce,
				wpgc_finish_scan_nonce: wpgcGrammarResults.wpgc_finish_scan_nonce,
				wpsc_display_results_grammar_nonce:
				wpgcGrammarResults.wpsc_display_results_grammar_nonce,
				wpsc_get_stats_grammar_nonce:
				wpgcGrammarResults.wpsc_get_stats_grammar_nonce,
			};
		}
	}

	// Auto-click handler for classic editor "Entire Site" scan
	if (
	wpgcGrammarResults.classic_active &&
	wpgcGrammarResults.auto_click_enabled
	) {
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
	function wpgcx_recheck_scan_temp() {
		// wpgcx_gram_ajax_object is initialized at the top of the file

		$.ajax(
			{
				url: window.wpgcx_gram_ajax_object.ajax_url,
				type: "POST",
				data: {
					action: "results_gc",
				},
				dataType: "html",
				success: function (response) {
					if (response == "true") {
						window.setInterval( wpgcx_finish_scan_temp(), 2000 );
					} else {
						wpgcx_finish_scan_temp();
					}
				},
			}
		);
	}

	// Finish scan and display results
	function wpgcx_finish_scan_temp() {
		// wpgcx_gram_ajax_object is initialized at the top of the file

		var scanTime2    = new Date();
		var scanEndTime2 = scanTime2.getTime();
		var scanFinal2   = (scanEndTime2 - scanStartTime) / 1000;

		$.ajax(
			{
				url: window.wpgcx_gram_ajax_object.ajax_url,
				type: "POST",
				data: {
					action: "wpscx_display_results_grammar",
					nonce: window.wpgcx_gram_ajax_object.wpsc_display_results_grammar_nonce,
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

					wpgcx_show_stats( scanFinal );
				},
			}
		);
	}

	// Show scan statistics
	function wpgcx_show_stats(x) {
		// wpgcx_gram_ajax_object is initialized at the top of the file

		$.ajax(
			{
				url: window.wpgcx_gram_ajax_object.ajax_url,
				type: "POST",
				data: {
					action: "wpscx_get_stats_grammar",
					scantime: x,
					nonce: window.wpgcx_gram_ajax_object.wpsc_get_stats_grammar_nonce,
				},
				dataType: "json",
				success: function (response) {
					$( ".sc-type" ).html(
						"Errors found on <span style='color: rgb(0, 150, 255); font-weight: bold;'>" +
						response.scanType +
						": " +
						response.totalErrors
					);
					if (Number( response.pageCount ) >= Number( response.totalPages )) {
						$( ".sc-post" ).html(
							"Posts scanned: " + response.totalPosts + "/" + response.totalPosts
						);
					} else {
						$( ".sc-post" ).html(
							"Posts scanned: " + response.postCount + "/" + response.totalPosts
						);
					}
					if (Number( response.pageCount ) >= Number( response.totalPages )) {
						$( ".sc-page" ).html(
							"Pages scanned: " + response.totalPages + "/" + response.totalPages
						);
					} else {
						$( ".sc-page" ).html(
							"Pages scanned: " + response.pageCount + "/" + response.totalPages
						);
					}
					$( ".sc-time" ).html( "Last scan took " + response.scanTime );
					$( ".next-page" ).click(
						function (e) {
							e.preventDefault();
							window.location.href = "?page=wp-spellcheck-grammar.php&paged=2";
						}
					);
					var last_page =
					(parseInt( response.totalPosts ) + parseInt( response.totalPages )) / 20 +
					1;
					$( ".last-page" ).click(
						function (e) {
							e.preventDefault();
							window.location.href =
							"?page=wp-spellcheck-grammar.php&paged=" + last_page;
						}
					);
				},
				error: function (xhr, status, thrownError) {},
			}
		);
	}

	// Description toggle handler
	$( document ).ready(
		function () {
			$( ".wpgc-desc" ).click(
				function () {
					$( this ).find( ".wpgc-desc-content" ).toggleClass( "wpgc-desc-hover" );
				}
			);
		}
	);

	// Scan button click handler
	$( ".wpscScan" ).click(
		function (event) {
			event.preventDefault();
			if (scan_in_progress) {
				return;
			}
			scan_in_progress = true;

			// wpgcx_gram_ajax_object is initialized at the top of the file, but update nonces if needed
			if (
			typeof window.wpgcx_gram_ajax_object.wpgc_start_scan_nonce === "undefined"
			) {
				window.wpgcx_gram_ajax_object.wpgc_start_scan_nonce              =
				wpgcGrammarResults.wpgc_start_scan_nonce;
				window.wpgcx_gram_ajax_object.wpgc_scan_nonce                    =
				wpgcGrammarResults.wpgc_scan_nonce;
				window.wpgcx_gram_ajax_object.wpgc_finish_scan_nonce             =
				wpgcGrammarResults.wpgc_finish_scan_nonce;
				window.wpgcx_gram_ajax_object.wpsc_display_results_grammar_nonce =
				wpgcGrammarResults.wpsc_display_results_grammar_nonce;
				window.wpgcx_gram_ajax_object.wpsc_get_stats_grammar_nonce       =
				wpgcGrammarResults.wpsc_get_stats_grammar_nonce;
			}

			var scanType = $( this ).attr( "value" );

			var scanTime  = new Date();
			scanStartTime = scanTime.getTime();

			$( "#wpscScanMessage" ).html(
				'<img src="' +
				loadingGifUrl +
				'" alt="Scan in Progress" /> Starting New Scan'
			);
			$( ".wpscScan" ).addClass( "wpsc-button-greyout" ); // Greyout buttons

			$.ajax(
				{
					url: window.wpgcx_gram_ajax_object.ajax_url,
					type: "POST",
					data: {
						type: scanType,
						action: "wpscx_start_scan_grammar",
						nonce: window.wpgcx_gram_ajax_object.wpgc_start_scan_nonce,
					},
					dataType: "html",
					success: function (response) {
						$( "#wpscScanMessage" ).html( response ); // update the scan message to display the scan started message
						window.setInterval( wpgcx_finish_scan_temp(), 500 );
						$( "tr.wpsc-row" ).animate(
							{ opacity: 0 },
							500,
							function () {
								$( "tr.wpsc-row" ).hide();
							}
						);

						var scanTime    = new Date();
						var scanEndTime = scanTime.getTime();
						var scanFinal   = (scanEndTime - scanStartTime) / 1000;

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
											}
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
												}
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
						wpgcx_recheck_scan_temp();
					},
				}
			);
		}
	);
})( jQuery );
