/**
 * SEO Results UI JavaScript
 * Extracted from inline scripts in wpsc-empty-results.php
 * Handles scan progress, SEO listeners, and UI interactions
 */
(function ($) {
	"use strict";

	// Get localized data (fallback to empty object if not available)
	var uiData =
	typeof wpsc_empty_results_ui !== "undefined" ? wpsc_empty_results_ui : {};

	// Initialize scan_in_progress from PHP variable
	var scan_in_progress = uiData.check_scan || false;
	var scanStartTime;

	// Ensure wpscx_seo_ajax_object exists (may come from emptyresults-ajax.js or use fallback)
	if (typeof wpscx_seo_ajax_object === "undefined") {
		window.wpscx_seo_ajax_object = {
			ajax_url: uiData.admin_ajax_url || "",
			wpsc_start_scan_empty_nonce: uiData.nonces
			? uiData.nonces.wpsc_start_scan_empty || ""
			: "",
			wpsc_empty_scan_nonce: uiData.nonces
			? uiData.nonces.wpsc_empty_scan || ""
			: "",
			wpsc_finish_empty_scan_nonce: uiData.nonces
			? uiData.nonces.wpsc_finish_empty_scan || ""
			: "",
			wpsc_display_results_empty_nonce: uiData.nonces
			? uiData.nonces.wpsc_display_results_empty || ""
			: "",
			wpsc_get_stats_empty_nonce: uiData.nonces
			? uiData.nonces.wpsc_get_stats_empty || ""
			: "",
			wpsc_openai_nonce: uiData.nonces ? uiData.nonces.wpsc_openai || "" : "",
		};
	} else {
		// Merge nonces if wpscx_seo_ajax_object exists but nonces are missing
		if (uiData.nonces) {
			if (
			! wpscx_seo_ajax_object.wpsc_openai_nonce &&
			uiData.nonces.wpsc_openai
			) {
				wpscx_seo_ajax_object.wpsc_openai_nonce = uiData.nonces.wpsc_openai;
			}
			if (
			! wpscx_seo_ajax_object.wpsc_start_scan_empty_nonce &&
			uiData.nonces.wpsc_start_scan_empty
			) {
				wpscx_seo_ajax_object.wpsc_start_scan_empty_nonce =
				uiData.nonces.wpsc_start_scan_empty;
			}
			if (
			! wpscx_seo_ajax_object.wpsc_empty_scan_nonce &&
			uiData.nonces.wpsc_empty_scan
			) {
				wpscx_seo_ajax_object.wpsc_empty_scan_nonce =
				uiData.nonces.wpsc_empty_scan;
			}
			if (
			! wpscx_seo_ajax_object.wpsc_finish_empty_scan_nonce &&
			uiData.nonces.wpsc_finish_empty_scan
			) {
				wpscx_seo_ajax_object.wpsc_finish_empty_scan_nonce =
				uiData.nonces.wpsc_finish_empty_scan;
			}
			if (
			! wpscx_seo_ajax_object.wpsc_display_results_empty_nonce &&
			uiData.nonces.wpsc_display_results_empty
			) {
				wpscx_seo_ajax_object.wpsc_display_results_empty_nonce =
				uiData.nonces.wpsc_display_results_empty;
			}
			if (
			! wpscx_seo_ajax_object.wpsc_get_stats_empty_nonce &&
			uiData.nonces.wpsc_get_stats_empty
			) {
				wpscx_seo_ajax_object.wpsc_get_stats_empty_nonce =
				uiData.nonces.wpsc_get_stats_empty;
			}
		}
		// Ensure ajax_url is set
		if ( ! wpscx_seo_ajax_object.ajax_url && uiData.admin_ajax_url) {
			wpscx_seo_ajax_object.ajax_url = uiData.admin_ajax_url;
		}
	}

	// BLOCK 1: Auto-click handler (conditional)
	if (uiData.auto_click_enabled) {
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

	// BLOCK 2: Edit button handler (currently commented out in original)
	$( document ).ready(
		function () {
			var should_submit = false;
			var shown_box     = false;

			$( ".wpsc-edit-update-button" ).click(
				function (event) {
					/*if (!should_submit) event.preventDefault();
					$('.wpsc-mass-edit-chk').each(function() {
					if ($(this).is(":checked") && shown_box == false) {
					shown_box = true;
					$( "#wpsc-mass-edit-confirm" ).dialog({
						resizable: false,
						height: "auto",
						width: 400,
						modal: true,
						buttons: {
						"Yes": function() {
							$( this ).dialog( "close" );
							should_submit = true;
							$("#wpsc-edit-update-button-hidden").trigger('click');
						},
						Cancel: function() {
							$( this ).dialog( "close" );
						}
						}
					});
					}
					});
					if (shown_box == false) {
					should_submit = true;
								$("#wpsc-edit-update-button").trigger('click');
					//$("#wpsc-edit-update-button-hidden").trigger('click');
							}*/
				}
			);
		}
	);

	// BLOCK 2: Scan progress functions
	function wpscex_recheck_scan_temp() {
		$.ajax(
			{
				url: wpscx_seo_ajax_object.ajax_url,
				type: "POST",
				data: {
					action: "emptyresults_sc",
				},
				dataType: "html",
				success: function (response) {
					if (response == "true") {
						window.setInterval( wpscex_recheck_scan_temp(), 500 );
					} else {
						wpscex_finish_scan_temp();
					}
				},
			}
		);
	}

	function wpscex_finish_scan_temp() {
		var scanTime    = new Date();
		var scanEndTime = scanTime.getTime();
		var scanFinal   = (scanEndTime - scanStartTime) / 1000;
		$.ajax(
			{
				url: wpscx_seo_ajax_object.ajax_url,
				type: "POST",
				data: {
					action: "wpscx_display_results_empty",
					nonce: wpscx_seo_ajax_object.wpsc_display_results_empty_nonce,
				},
				dataType: "html",
				success: function (response) {
					var scanTime    = new Date();
					var scanEndTime = scanTime.getTime();
					var scanFinal   = (scanEndTime - scanStartTime) / 1000;
					$( ".wpscScan" ).removeClass( "wpsc-button-greyout" ); // Remove button greyout
					scan_in_progress = false;
					$( "#wpsc-table-results" ).html( response.replace( "null", "" ) );

					// Call function from admin-js.js
					if (typeof wpscx_connect_listeners === "function") {
						wpscx_connect_listeners();
					}

					$( "#wpscScanMessage" ).html( "The scan has finished" );

					wpscex_show_stats( scanFinal );
				},
			}
		);
	}

	function wpscex_show_stats(x) {
		$.ajax(
			{
				url: wpscx_seo_ajax_object.ajax_url,
				type: "POST",
				data: {
					action: "wpscx_get_stats_empty",
					scantime: x,
					nonce: wpscx_seo_ajax_object.wpsc_get_stats_empty_nonce,
				},
				dataType: "json",
				success: function (response) {
					$( ".sc-factor" ).html(
						"Website Empty Fields Factor:" + response.emptyFactor + "%"
					);
					$( ".sc-type" ).html(
						"Errors found on <span>" +
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
					if (response.emptyEPS > 0) {
						var version =
						typeof wpsc_empty_results_ui !== "undefined" &&
						wpsc_empty_results_ui.version
						? wpsc_empty_results_ui.version
						: "";
						$( ".empty-eps-message" ).html(
							"<h3 class='sc-message error'><strong>Pro Version: </strong>" +
							response.emptyEPS +
							" SEO Empty Fields were found on your website. <a href='https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgradeSEO&utm_medium=seo_scan&utm_content=" +
							version +
							"' target='_blank'>Upgrade today</a> to boost your SEO and get <strong>AI suggestions for Page/post SEO</strong></h3>"
						);
					}
					$( ".sc-time" ).html( "Last scan took " + response.scanTime );
					$( ".next-page" ).click(
						function (e) {
							e.preventDefault();
							window.location.href = "?page=wp-spellcheck-seo.php&paged=2";
						}
					);
					var last_page = parseInt( response.totalErrors / 20 ) + 1;
					$( ".last-page" ).click(
						function (e) {
							e.preventDefault();
							window.location.href =
							"?page=wp-spellcheck-seo.php&paged=" + last_page;
						}
					);
				},
				error: function (xhr, status, thrownError) {},
			}
		);
	}

	// BLOCK 3: SEO Listener function
	function wpscx_seoListener() {
		$( ".wpsc-generate-seo-button" ).click(
			function (event) {
				event.preventDefault();

				var postID   = $( this )
				.closest( "tr" )
				.find( 'input[name="edit_page_name[]"]' )
				.attr( "value" );
				var postType = $( this )
				.closest( "tr" )
				.find( 'input[name="edit_page_type[]"]' )
				.attr( "value" );
				var wordID   = $( this )
				.closest( "tr" )
				.find( 'input[name="edit_old_word_id[]"]' )
				.attr( "value" );
				$( this )
				.closest( "tr" )
				.find( ".seo-progress" )
				.css( "display", "inline-block" );

				if (typeof wpscx_seo_ajax_object === "undefined") {
					wpscx_seo_ajax_object = {};
				}
				wpscx_seo_ajax_object.ajax_url = uiData.admin_ajax_url || "";
				if (typeof wpscx_seo_ajax_object.wpsc_openai_nonce === "undefined") {
					wpscx_seo_ajax_object.wpsc_openai_nonce = uiData.nonces
					? uiData.nonces.wpsc_openai || ""
					: "";
				}

				$.ajax(
					{
						url: wpscx_seo_ajax_object.ajax_url,
						type: "POST",
						data: {
							type: postType,
							id: postID,
							action: "wpscx_openAI_ajax",
							nonce: wpscx_seo_ajax_object.wpsc_openai_nonce,
						},
						dataType: "json",
						success: function (response) {
							$( "#wpsc-edit-seo-row-" + wordID )
							.find( ".seo-progress" )
							.css( "display", "none" );
							$( "#wpsc-edit-seo-row-" + wordID )
							.find( ".wpsc-edit-field" )
							.val( response.replace( /\n/g, " " ) );
							$( "#wpsc-edit-seo-row-" + wordID )
							.find( ".wpsc-edit-field" )
							.trigger( "input" );
						},
					}
				);
			}
		);

		$( ".edit-seo-title" ).on(
			"input",
			function (e) {
				var textLen = $( this ).val().length;

				if (textLen < 50) {
					$( this ).css( "border-color", "orange" );
				} else if (textLen > 70) {
					$( this ).css( "border-color", "red" );
				} else {
					$( this ).css( "border-color", "green" );
				}
			}
		);

		$( ".edit-seo-desc" ).on(
			"input",
			function (e) {
				var textLen = $( this ).val().length;

				if (textLen < 120) {
					$( this ).css( "border-color", "orange" );
				} else if (textLen > 160) {
					$( this ).css( "border-color", "red" );
				} else {
					$( this ).css( "border-color", "green" );
				}
			}
		);
	}
	window.wpscx_seoListener = wpscx_seoListener;

	// BLOCK 3: Scan button click handler
	$( ".wpscScan" ).click(
		function (event) {
			event.preventDefault();
			if (scan_in_progress) {
				return;
			}
			scan_in_progress = true;

			if (typeof wpscx_seo_ajax_object === "undefined") {
				wpscx_seo_ajax_object = {};
			}
			wpscx_seo_ajax_object.ajax_url = uiData.admin_ajax_url || "";
			if (
			typeof wpscx_seo_ajax_object.wpsc_start_scan_empty_nonce === "undefined"
			) {
				wpscx_seo_ajax_object.wpsc_start_scan_empty_nonce      = uiData.nonces
				? uiData.nonces.wpsc_start_scan_empty || ""
				: "";
				wpscx_seo_ajax_object.wpsc_empty_scan_nonce            = uiData.nonces
				? uiData.nonces.wpsc_empty_scan || ""
				: "";
				wpscx_seo_ajax_object.wpsc_finish_empty_scan_nonce     = uiData.nonces
				? uiData.nonces.wpsc_finish_empty_scan || ""
				: "";
				wpscx_seo_ajax_object.wpsc_display_results_empty_nonce = uiData.nonces
				? uiData.nonces.wpsc_display_results_empty || ""
				: "";
				wpscx_seo_ajax_object.wpsc_get_stats_empty_nonce       = uiData.nonces
				? uiData.nonces.wpsc_get_stats_empty || ""
				: "";
			}

			var scanType      = $( this ).attr( "value" );
			var loadingSpinnerUrl = uiData.loading_spinner_url || (uiData.plugin_url || "") + "images/loading.svg";

			$( "#wpscScanMessage" ).html(
				'<img src="' +
				loadingSpinnerUrl +
				'" alt="Scan in Progress" class="wpsc-loading-spinner" /> Starting New Scan'
			);
			$( ".wpscScan" ).addClass( "wpsc-button-greyout" ); // Greyout buttons

			var scanTime  = new Date();
			scanStartTime = scanTime.getTime();

			$.ajax(
				{
					url: wpscx_seo_ajax_object.ajax_url,
					type: "POST",
					data: {
						type: scanType,
						action: "wpscx_start_scan_empty",
						nonce: wpscx_seo_ajax_object.wpsc_start_scan_empty_nonce,
					},
					dataType: "html",
					success: function (response) {
						$( "#wpscScanMessage" ).html( response ); // update the scan message to display the scan started message
						window.setInterval( wpscex_finish_scan_temp(), 100 );
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
										var isHoveredPopup      = $( ".wpsc-mouseover-text-refresh" ).filter(
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
										if ( ! isHoveredPopup && ! isHoveredParent) {
											$( ".wpsc-mouseover-text-refresh" ).css( "z-index", "-100" );
											$( ".wpsc-mouseover-text-refresh" ).animate( { opacity: 0 }, 400 );
											mouseover_visible = false;
										}
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

								$( ".wpsc-mouseover-button-refresh" )
								.parent()
								.mouseleave(
									function () {
										var isHoveredPopup  =
										$( ".wpsc-mouseover-text-refresh:hover" ).length > 0;
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
						);
					},
					error: function (xhr, status, thrownError) {
						window.setInterval( wpscex_recheck_scan_temp(), 500 );
					},
				}
			);
		}
	);

	// Initialize SEO listener when DOM is ready
	$( document ).ready(
		function () {
			wpscx_seoListener();
		}
	);
})( jQuery );
