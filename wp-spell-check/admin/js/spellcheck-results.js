/**
 * WP Spell Check - Results Page JavaScript
 *
 * Handles all UI interactions for the spell check results page.
 * Extracted from inline scripts in class-wpsc-results.php
 *
 * @since 9.21
 */

(function ($) {
	"use strict";

	// Global variables
	var scanStartTime;
	var scan_in_progress          = false;
	var auto_click_fired          = false; // Track if auto-click has already fired to prevent loops
	var admin_bar_trigger_handled = false; // Track if admin bar trigger was handled

	/**
	 * Initialize results page functionality
	 */
	function initResultsPage() {
		// Set scan_in_progress from localized data
		if (typeof wpscResultsPage !== "undefined" && wpscResultsPage.checkScan) {
			scan_in_progress = true;
		}

		// Handle admin bar link clicks - trigger scan without URL params
		if (window.location.search.indexOf( "wpsc-trigger-scan=1" ) !== -1) {
			admin_bar_trigger_handled = true; // Mark as handled to prevent legacy handler
			// Clear URL params immediately to prevent loop
			if (window.history && window.history.replaceState) {
				var cleanUrl = window.location.pathname + "?page=wp-spellcheck.php";
				window.history.replaceState( {}, "", cleanUrl );
			}
			// Trigger scan via AJAX
			if ( ! scan_in_progress) {
				var scanButton = $( ".wpscScanSite" );
				if (scanButton.length > 0) {
					// Mark button as clicked before programmatic click to prevent form handler
					scanButton.data( "wpsc-clicked", true );
					// Trigger AJAX directly instead of clicking to avoid form handler interference
					startScanAjax( "Entire Site", scanButton );
				} else {
					// Fallback: trigger AJAX directly
					startScanAjax( "Entire Site", null );
				}
			}
		}

		// Auto-click "Entire Site" scan button if needed (for backward compatibility with direct URL params)
		// Only fire if admin bar trigger was NOT handled (prevents double-firing)
		if (
		typeof wpscResultsPage !== "undefined" &&
		wpscResultsPage.autoClickEntireSite &&
		! auto_click_fired &&
		! admin_bar_trigger_handled
		) {
			auto_click_fired = true; // Mark as fired to prevent loops
			// Clear URL params to prevent loop on reload
			if (window.history && window.history.replaceState) {
				var cleanUrl = window.location.pathname + "?page=wp-spellcheck.php";
				window.history.replaceState( {}, "", cleanUrl );
			}
			window.setTimeout(
				function () {
					// Only fire if scan is not already in progress (prevents race conditions)
					if ( ! scan_in_progress) {
						var scanButton = $( ".wpscScanSite" );
						if (scanButton.length > 0) {
							// Mark button as clicked and trigger AJAX directly to avoid form handler
							scanButton.data( "wpsc-clicked", true );
							startScanAjax( "Entire Site", scanButton );
						}
					}
				},
				1000
			);
		}

		// Initialize button handlers
		initEditUpdateButton();
		initPaginationButtons();
		initScanButtons();
		initMouseoverInteractions();
	}

	/**
	 * Initialize edit/update button handlers
	 */
	function initEditUpdateButton() {
		var should_submit = false;
		var shown_box     = false;
		var admin_url     =
		typeof wpscResultsPage !== "undefined" && wpscResultsPage.adminUrl
		? wpscResultsPage.adminUrl
		: "";

		$( ".wpsc-edit-update-button" )
		.off( "click" )
		.on(
			"click",
			function (event) {
				if ( ! should_submit) {
					event.preventDefault();
				}

				$( ".wpsc-mass-edit-chk" ).each(
					function () {
						if ($( this ).is( ":checked" ) && shown_box === false) {
							shown_box = true;
							$( "#wpsc-mass-edit-confirm" ).dialog(
								{
									resizable: false,
									height: "auto",
									width: 400,
									modal: true,
									buttons: {
										Yes: function () {
											$( this ).dialog( "close" );
											should_submit = true;
											$( "#wpsc-edit-update-button-hidden" ).trigger( "click" );
										},
										Cancel: function () {
											$( this ).dialog( "close" );
										},
									},
								}
							);
						}
					}
				);

				if (shown_box === false) {
						should_submit = true;
						$( "#wpsc-edit-update-button-hidden" ).trigger( "click" );
				}
			}
		);
	}

	/**
	 * Initialize pagination button handlers
	 */
	function initPaginationButtons() {
		var allow_next = false;
		var pending    = false;

		$( ".next-page, .prev-page, .last-page, .first-page" )
		.off( "click" )
		.on(
			"click",
			function (event) {
				if ( ! allow_next) {
					event.preventDefault();
				}

				pending    = false;
				var button = $( this ).attr( "href" );

				$( ".wpsc-ignore-checkbox, .wpsc-add-checkbox" ).each(
					function () {
						if ($( this ).is( ":checked" )) {
							pending = true;
						}
					}
				);

				$( ".wpsc-mass-edit-chk" ).each(
					function () {
						if ($( this ).is( ":checked" )) {
							pending = true;
						}
					}
				);

				if (pending) {
						$( "#wpsc-mass-edit-block" ).dialog(
							{
								resizable: false,
								height: "auto",
								width: 400,
								modal: true,
								buttons: {
									Cancel: function () {
										$( this ).dialog( "close" );
									},
									"Move Forward Anyway": function () {
										$( this ).dialog( "close" );
										allow_next = true;
										window.location.replace( button );
									},
								},
							}
						);
				} else {
					allow_next = true;
					window.location.replace( button );
				}
			}
		);
	}

	/**
	 * Start scan via AJAX (extracted to reusable function)
	 */
	function startScanAjax(scanType, buttonElement) {
		if (scan_in_progress) {
			return;
		}

		scan_in_progress = true;

		// Initialize wpscx__spell_ajax_object if not already defined
		var wpscx__spell_ajax_object =
		typeof wpscx__spell_ajax_object !== "undefined"
		? wpscx__spell_ajax_object
		: {};

		// Use localized data if available, otherwise use fallback
		if (typeof wpscResultsPage !== "undefined") {
			wpscx__spell_ajax_object.ajax_url = wpscResultsPage.ajaxUrl;
			if (
			typeof wpscx__spell_ajax_object.wpsc_start_scan_nonce === "undefined"
			) {
				wpscx__spell_ajax_object.wpsc_start_scan_nonce      =
				wpscResultsPage.nonces.wpsc_start_scan;
				wpscx__spell_ajax_object.wpsc_scan_nonce            =
				wpscResultsPage.nonces.wpsc_scan;
				wpscx__spell_ajax_object.wpsc_finish_scan_nonce     =
				wpscResultsPage.nonces.wpsc_finish_scan;
				wpscx__spell_ajax_object.wpsc_display_results_nonce =
				wpscResultsPage.nonces.wpsc_display_results;
				wpscx__spell_ajax_object.wpsc_get_stats_nonce       =
				wpscResultsPage.nonces.wpsc_get_stats;
			}
		} else {
			// Fallback: try to get from existing wpscx__spell_ajax_object or use defaults
			if (typeof wpscx__spell_ajax_object === "undefined") {
				wpscx__spell_ajax_object = {};
			}
		}

		var scanTime  = new Date();
		scanStartTime = scanTime.getTime();

		var loadingGif =
		typeof wpscResultsPage !== "undefined" && wpscResultsPage.loadingGif
		? wpscResultsPage.loadingGif
		: "";

		$( "#wpscScanMessage" ).html(
			'<img src="' +
			loadingGif +
			'" alt="Scan in Progress" class="wpsc-loading-spinner" /> Starting New Scan'
		);
		$( ".wpscScan" ).addClass( "wpsc-button-greyout" ); // Greyout buttons

		$.ajax(
			{
				url: wpscx__spell_ajax_object.ajax_url,
				timeout: 7200000, // 2 Hours
				type: "POST",
				data: {
					type: scanType,
					action: "wpscx_start_scan",
					nonce: wpscx__spell_ajax_object.wpsc_start_scan_nonce,
				},
				dataType: "html",
				success: function (response) {
					$( "#wpscScanMessage" ).html( response ); // Update the scan message
					var scanEndTime = scanTime.getTime();
					var scanFinal   = (scanEndTime - scanStartTime) / 1000;

					setTimeout( wpscx_recheck_scan_temp, 500 );
					$( "tr.wpsc-row" ).animate(
						{ opacity: 0 },
						500,
						function () {
							$( "tr.wpsc-row" ).hide();
						}
					);
					$( ".wpsc-mesage-container" ).animate(
						{ opacity: 0 },
						500,
						function () {
							$( ".wpsc-mesage-container" ).hide();
						}
					);
				},
				error: function (xhr, status, thrownError) {
					setTimeout( wpscx_recheck_scan_temp, 500 );
				},
			}
		);
	}

	/**
	 * Initialize scan button handlers
	 */
	function initScanButtons() {
		$( ".wpscScan" )
		.off( "click" )
		.on(
			"click",
			function (event) {
				// Mark this button as clicked to help form submit handler detect it
				$( this ).data( "wpsc-clicked", true );

				event.preventDefault();
				event.stopPropagation();
				event.stopImmediatePropagation();

				var scanType = $( this ).val();
				startScanAjax( scanType, $( this ) );
			}
		);

		// Handle scan buttons input clicks (for single page view)
		// NOTE: This handler should NOT fire for buttons with .wpscScan class (those use AJAX)
		// Only handle buttons that don't have the .wpscScan class
		$( ".wpsc-scan-buttons input" )
		.off( "click" )
		.on(
			"click",
			function (event) {
				// Skip buttons that have .wpscScan class - they're handled by the AJAX handler above
				if ($( this ).hasClass( "wpscScan" )) {
					return; // Let the .wpscScan handler handle it
				}

				// Prevent default form submission
				event.preventDefault();

				var allow_next  = false;
				var pending     = false;
				var value       = $( this ).attr( "value" );
				var admin_url   =
				typeof wpscResultsPage !== "undefined" && wpscResultsPage.adminUrl
				? wpscResultsPage.adminUrl
				: "";
				var nonce_clear = $( 'input[name="_wpnonce_clear_results"]' ).val();
				var nonce_stop  = $( 'input[name="_wpnonce_stop_scans"]' ).val();
				var button      =
				admin_url +
				"admin.php?page=wp-spellcheck.php&action=check&submit=" +
				encodeURIComponent( value );
				// Add appropriate nonce based on button value
				if (value === "Clear Results" && nonce_clear) {
					button +=
					"&_wpnonce_clear_results=" + encodeURIComponent( nonce_clear );
				} else if (value === "Stop Scans" && nonce_stop) {
					button += "&_wpnonce_stop_scans=" + encodeURIComponent( nonce_stop );
				}

				$( ".wpsc-ignore-checkbox, .wpsc-add-checkbox" ).each(
					function () {
						if ($( this ).is( ":checked" )) {
							pending = true;
						}
					}
				);

				$( ".wpsc-mass-edit-chk" ).each(
					function () {
						if ($( this ).is( ":checked" )) {
							pending = true;
						}
					}
				);

				if (pending) {
					if ( ! allow_next) {
						// Prevent default
					}
					$( "#wpsc-mass-edit-block" ).dialog(
						{
							resizable: false,
							height: "auto",
							width: 400,
							modal: true,
							buttons: {
								cancel: function () {
									$( this ).dialog( "close" );
								},
								"Move Forward Anyway": function () {
									$( this ).dialog( "close" );
									allow_next = true;
									window.location.replace( button );
								},
							},
						}
					);
				} else {
					allow_next = true;
					window.location.replace( button );
				}
			}
		);
	}

	/**
	 * Initialize mouseover interactions for refresh button
	 */
	function initMouseoverInteractions() {
		var mouseover_visible = false;

		$( ".wpsc-mouseover-button-refresh" )
		.off( "mouseenter mouseleave click" )
		.on(
			"mouseenter",
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
		.on(
			"mouseleave",
			function () {
				var isHoveredPopup  = $( ".wpsc-mouseover-text-refresh" ).filter(
					function () {
						return $( this ).is( ":hover" );
					}
				);
				var isHoveredParent = $( this )
				.parent()
				.filter(
					function () {
						return $( this ).is( ":hover" );
					}
				);
				if ( ! isHoveredPopup.length && ! isHoveredParent.length) {
						$( ".wpsc-mouseover-text-refresh" ).css( "z-index", "-100" );
						$( ".wpsc-mouseover-text-refresh" ).animate( { opacity: 0 }, 400 );
						mouseover_visible = false;
				}
			}
		)
		.on(
			"click",
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

		$( ".wpsc-mouseover-button-refresh" )
		.parent()
		.off( "mouseleave" )
		.on(
			"mouseleave",
			function () {
				var isHoveredPopup  = $( ".wpsc-mouseover-text-refresh:hover" ).length > 0;
				var isHoveredButton =
				$( ".wpsc-mouseover-button-refresh:hover" ).length > 0;

				if ( ! isHoveredPopup && ! isHoveredButton) {
					$( ".wpsc-mouseover-text-refresh" ).css( "z-index", "-100" );
					$( ".wpsc-mouseover-text-refresh" ).animate( { opacity: 0 }, 400 );
					mouseover_visible = false;
				}
			}
		);
	}

	/**
	 * Recheck scan progress
	 */
	function wpscx_recheck_scan_temp() {
		var wpscx__spell_ajax_object =
		typeof wpscx__spell_ajax_object !== "undefined"
		? wpscx__spell_ajax_object
		: {};

		// Use localized data if available
		if (typeof wpscResultsPage !== "undefined") {
			wpscx__spell_ajax_object.ajax_url        = wpscResultsPage.ajaxUrl;
			wpscx__spell_ajax_object.wpsc_scan_nonce =
			wpscResultsPage.nonces.wpsc_scan;
		}

		var requestData = {
			action: "results_sc",
			nonce: wpscx__spell_ajax_object.wpsc_scan_nonce,
		};

		$.ajax(
			{
				url: wpscx__spell_ajax_object.ajax_url,
				type: "POST",
				data: requestData,
				dataType: "html",
				success: function (response) {
					if (response === "true") {
						setTimeout( wpscx_recheck_scan_temp, 1000 );
					} else {
						wpscx_finish_scan_temp();
					}
				},
				error: function (xhr, status, thrownError) {
					// Error handling
				},
			}
		);
	}

	/**
	 * Finish scan and display results
	 */
	function wpscx_finish_scan_temp() {
		var wpscx__spell_ajax_object =
		typeof wpscx__spell_ajax_object !== "undefined"
		? wpscx__spell_ajax_object
		: {};

		// Use localized data if available
		if (typeof wpscResultsPage !== "undefined") {
			wpscx__spell_ajax_object.ajax_url                   = wpscResultsPage.ajaxUrl;
			wpscx__spell_ajax_object.wpsc_display_results_nonce =
			wpscResultsPage.nonces.wpsc_display_results;
		}

		var scanTime2    = new Date();
		var scanEndTime2 = scanTime2.getTime();
		var scanFinal2   = (scanEndTime2 - scanStartTime) / 1000;

		$.ajax(
			{
				url: wpscx__spell_ajax_object.ajax_url,
				type: "POST",
				data: {
					action: "wpscx_display_results",
					nonce: wpscx__spell_ajax_object.wpsc_display_results_nonce,
				},
				dataType: "html",
				success: function (response) {
					var scanTime    = new Date();
					var scanEndTime = scanTime.getTime();
					var scanFinal   = (scanEndTime - scanStartTime) / 1000;
					$( ".wpscScan" ).removeClass( "wpsc-button-greyout" ); // Remove button greyout
					scan_in_progress = false;

					$( "#wpsc-table-results" ).html( response.replace( "null", "" ) );

					// Call connect listeners if available
					if (typeof wpscx_connect_listeners === "function") {
						wpscx_connect_listeners();
					}

					$( "#wpscScanMessage" ).html( "The scan has finished" );
					wpscx_show_stats( scanFinal );
				},
				error: function (xhr, status, thrownError) {
					// Error handling
				},
			}
		);
	}

	/**
	 * Show scan statistics
	 */
	function wpscx_show_stats(x) {
		var wpscx__spell_ajax_object =
		typeof wpscx__spell_ajax_object !== "undefined"
		? wpscx__spell_ajax_object
		: {};

		// Use localized data if available
		if (typeof wpscResultsPage !== "undefined") {
			wpscx__spell_ajax_object.ajax_url             = wpscResultsPage.ajaxUrl;
			wpscx__spell_ajax_object.wpsc_get_stats_nonce =
			wpscResultsPage.nonces.wpsc_get_stats;
		}

		$.ajax(
			{
				url: wpscx__spell_ajax_object.ajax_url,
				type: "POST",
				data: {
					action: "wpscx_get_stats",
					scantime: x,
					nonce: wpscx__spell_ajax_object.wpsc_get_stats_nonce,
				},
				dataType: "json",
				success: function (response) {
					$( ".sc-literacy" ).html(
						"Website Literacy Factor: " + response.literacyFactor + "%"
					);
					$( ".sc-type" ).html(
						"Errors found on <span style='color: rgb(0, 150, 255); font-weight: bold;'>" +
						response.scanType +
						": " +
						response.totalErrors
					);

					if (Number( response.postCount ) >= Number( response.totalPosts )) {
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

					if (Number( response.mediaCount ) >= Number( response.totalMedia )) {
						$( ".sc-media" ).html(
							"Media Files scanned: " +
							response.totalMedia +
							"/" +
							response.totalMedia
						);
					} else {
						$( ".sc-media" ).html(
							"Media Files scanned: " +
							response.mediaCount +
							"/" +
							response.totalMedia
						);
					}

					$( ".sc-time" ).html( "Last scan took " + response.scanTime );

					if (response.epsCount > 0) {
						var version =
						typeof wpscResultsPage !== "undefined" && wpscResultsPage.version
						? wpscResultsPage.version
						: "";
						$( ".sc-eps" ).html(
							"<strong>Pro Version: </strong>" +
							response.epsCount +
							" Spelling Errors on other parts of your website are hurting your professional image. <a href='https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgradespellch&utm_medium=spellcheck_scan&utm_content=" +
							version +
							"' target='_blank'>Click here</a> to upgrade to find and fix all the errors."
						);
					}

					$( ".next-page" )
					.off( "click" )
					.on(
						"click",
						function (e) {
							e.preventDefault();
							window.location.href = "?page=wp-spellcheck.php&paged=2";
						}
					);

					var last_page = parseInt( response.totalErrors / 20 ) + 1;
					$( ".last-page" )
					.off( "click" )
					.on(
						"click",
						function (e) {
							e.preventDefault();
							window.location.href = "?page=wp-spellcheck.php&paged=" + last_page;
						}
					);
				},
				error: function (xhr, status, thrownError) {},
			}
		);
	}

	// Make functions globally available for backward compatibility
	window.wpscx_recheck_scan_temp = wpscx_recheck_scan_temp;
	window.wpscx_finish_scan_temp  = wpscx_finish_scan_temp;
	window.wpscx_show_stats        = wpscx_show_stats;

	// Initialize when document is ready
	$( document ).ready(
		function () {
			// Monitor form submission and prevent it for .wpscScan buttons
			$( "form" ).on(
				"submit",
				function (event) {
					// Find the submit button that triggered this form submission
					var submitButton = null;
					var form         = $( this );

					// Check all submit buttons in this form for .wpscScan class
					form.find( "input[type=submit].wpscScan" ).each(
						function () {
							// Check if this button was clicked (marked by click handler)
							if ($( this ).data( "wpsc-clicked" )) {
									submitButton = $( this );
									return false; // break
							}
						}
					);

					// If no marked button found, check active element
					if ( ! submitButton || submitButton.length === 0) {
						var activeElement = $( document.activeElement );
						if (activeElement.is( "input[type=submit].wpscScan" )) {
							submitButton = activeElement;
						}
					}

					// If the submit button has .wpscScan class, prevent form submission and trigger AJAX
					if (
					submitButton &&
					submitButton.length > 0 &&
					submitButton.hasClass( "wpscScan" )
					) {
						event.preventDefault();
						event.stopPropagation();
						event.stopImmediatePropagation();

						// Trigger AJAX scan directly (since click handler may not fire)
						var scanType = submitButton.val();
						startScanAjax( scanType, submitButton );

						// Clear the clicked flag
						submitButton.data( "wpsc-clicked", false );
						return false;
					}
				}
			);

			initResultsPage();
		}
	);
})( jQuery );
