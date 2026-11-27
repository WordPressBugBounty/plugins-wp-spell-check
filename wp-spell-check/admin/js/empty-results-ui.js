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
  var activeScanType = "";
  var scan_start_request_pending = false;
  var scan_server_confirmed = false;

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
        !wpscx_seo_ajax_object.wpsc_openai_nonce &&
        uiData.nonces.wpsc_openai
      ) {
        wpscx_seo_ajax_object.wpsc_openai_nonce = uiData.nonces.wpsc_openai;
      }
      if (
        !wpscx_seo_ajax_object.wpsc_start_scan_empty_nonce &&
        uiData.nonces.wpsc_start_scan_empty
      ) {
        wpscx_seo_ajax_object.wpsc_start_scan_empty_nonce =
          uiData.nonces.wpsc_start_scan_empty;
      }
      if (
        !wpscx_seo_ajax_object.wpsc_empty_scan_nonce &&
        uiData.nonces.wpsc_empty_scan
      ) {
        wpscx_seo_ajax_object.wpsc_empty_scan_nonce =
          uiData.nonces.wpsc_empty_scan;
      }
      if (
        !wpscx_seo_ajax_object.wpsc_finish_empty_scan_nonce &&
        uiData.nonces.wpsc_finish_empty_scan
      ) {
        wpscx_seo_ajax_object.wpsc_finish_empty_scan_nonce =
          uiData.nonces.wpsc_finish_empty_scan;
      }
      if (
        !wpscx_seo_ajax_object.wpsc_display_results_empty_nonce &&
        uiData.nonces.wpsc_display_results_empty
      ) {
        wpscx_seo_ajax_object.wpsc_display_results_empty_nonce =
          uiData.nonces.wpsc_display_results_empty;
      }
      if (
        !wpscx_seo_ajax_object.wpsc_get_stats_empty_nonce &&
        uiData.nonces.wpsc_get_stats_empty
      ) {
        wpscx_seo_ajax_object.wpsc_get_stats_empty_nonce =
          uiData.nonces.wpsc_get_stats_empty;
      }
    }
    // Ensure ajax_url is set
    if (!wpscx_seo_ajax_object.ajax_url && uiData.admin_ajax_url) {
      wpscx_seo_ajax_object.ajax_url = uiData.admin_ajax_url;
    }
  }

  // BLOCK 1: Auto-click handler (conditional)
  if (uiData.auto_click_enabled) {
    $(document).ready(function () {
      window.setTimeout(function () {
        $(".wpscScanSite").click();
      }, 1000);
    });
  }

  // BLOCK 2: Edit button handler (currently commented out in original)
  $(document).ready(function () {
    var should_submit = false;
    var shown_box = false;

    $(".wpsc-edit-update-button").click(function (event) {
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
    });
  });

  function wpscexBuildScanInProgressMessage(scanType) {
    var loadingSpinnerUrl =
      uiData.loading_spinner_url ||
      (uiData.plugin_url || "") + "images/loading.svg";
    return (
      '<img src="' +
      loadingSpinnerUrl +
      '" alt="Scan in Progress" class="wpsc-loading-spinner" /> A scan is currently in progress for <span class="sc-message" style="color: rgb(0, 150, 255); font-weight: bold;">' +
      scanType +
      '</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck-seo.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>'
    );
  }

  // BLOCK 2: Scan progress functions
  function wpscex_recheck_scan_temp() {
    $.ajax({
      url: wpscx_seo_ajax_object.ajax_url,
      type: "POST",
      data: {
        action: "emptyresults_sc",
        nonce: wpscx_seo_ajax_object.wpsc_empty_scan_nonce,
      },
      dataType: "html",
      success: function (response) {
        if (response === "true") {
          if (!scan_server_confirmed && activeScanType) {
            $("#wpscScanMessage").html(
              wpscexBuildScanInProgressMessage(activeScanType),
            );
            scan_server_confirmed = true;
          }
          window.setTimeout(wpscex_recheck_scan_temp, 1000);
        } else if (scan_server_confirmed && !scan_start_request_pending) {
          wpscex_finish_scan_temp();
        } else if (scan_server_confirmed && scan_start_request_pending) {
          window.setTimeout(wpscex_recheck_scan_temp, 500);
        } else if (scan_start_request_pending) {
          window.setTimeout(wpscex_recheck_scan_temp, 500);
        } else {
          wpscex_finish_scan_temp();
        }
      },
    });
  }

  function wpscex_finish_scan_temp() {
    var scanTime = new Date();
    var scanEndTime = scanTime.getTime();
    var scanFinal = (scanEndTime - scanStartTime) / 1000;
    $.ajax({
      url: wpscx_seo_ajax_object.ajax_url,
      type: "POST",
      data: {
        action: "wpscx_display_results_empty",
        nonce: wpscx_seo_ajax_object.wpsc_display_results_empty_nonce,
      },
      dataType: "html",
      success: function (response) {
        var scanTime = new Date();
        var scanEndTime = scanTime.getTime();
        var scanFinal = (scanEndTime - scanStartTime) / 1000;
        $(".wpscScan").removeClass("wpsc-button-greyout"); // Remove button greyout
        scan_in_progress = false;
        $("#wpsc-table-results").html(response.replace("null", ""));

        // Call function from admin-js.js
        if (typeof wpscx_connect_listeners === "function") {
          wpscx_connect_listeners();
        }

        $("#wpscScanMessage").html("The scan has finished");

        wpscex_show_stats(scanFinal);
      },
    });
  }

  function wpscex_show_stats(x) {
    $.ajax({
      url: wpscx_seo_ajax_object.ajax_url,
      type: "POST",
      data: {
        action: "wpscx_get_stats_empty",
        scantime: x,
        nonce: wpscx_seo_ajax_object.wpsc_get_stats_empty_nonce,
      },
      dataType: "json",
      success: function (response) {
        $(".sc-factor").html(
          "Website Empty Fields Factor:" + response.emptyFactor + "%",
        );
        $(".sc-type").html(
          "Errors found on <span>" +
            response.scanType +
            ": " +
            response.totalErrors,
        );
        if (Number(response.pageCount) >= Number(response.totalPages)) {
          $(".sc-post").html(
            "Posts scanned: " + response.totalPosts + "/" + response.totalPosts,
          );
        } else {
          $(".sc-post").html(
            "Posts scanned: " + response.postCount + "/" + response.totalPosts,
          );
        }
        if (Number(response.pageCount) >= Number(response.totalPages)) {
          $(".sc-page").html(
            "Pages scanned: " + response.totalPages + "/" + response.totalPages,
          );
        } else {
          $(".sc-page").html(
            "Pages scanned: " + response.pageCount + "/" + response.totalPages,
          );
        }
        if (Number(response.mediaCount) >= Number(response.totalMedia)) {
          $(".sc-media").html(
            "Media Files scanned: " +
              response.totalMedia +
              "/" +
              response.totalMedia,
          );
        } else {
          $(".sc-media").html(
            "Media Files scanned: " +
              response.mediaCount +
              "/" +
              response.totalMedia,
          );
        }
        if (response.emptyEPS > 0) {
          var version =
            typeof wpsc_empty_results_ui !== "undefined" &&
            wpsc_empty_results_ui.version
              ? wpsc_empty_results_ui.version
              : "";
          var formattedCount = Number(response.emptyEPS).toLocaleString();
          var upgradeUrl =
            "https://www.wpspellcheck.com/pricing/?utm_source=baseplugin&utm_campaign=upgradeSEO&utm_medium=seo_scan&utm_content=" +
            version;
          $(".empty-eps-message").html(
            "<h3 class='sc-message error'><strong>" +
              formattedCount +
              " SEO opportunities found</strong><br />Missing SEO info could be costing you traffic.<br />Unlock the full report and AI suggestions.<br /><strong><a href='" +
              upgradeUrl +
              "' target='_blank'>&#128073; Upgrade to Pro</a></strong></h3>",
          );
        }
        $(".sc-time").html("Last scan took " + response.scanTime);
        $(".next-page").click(function (e) {
          e.preventDefault();
          window.location.href = "?page=wp-spellcheck-seo.php&paged=2";
        });
        var last_page = parseInt(response.totalErrors / 20) + 1;
        $(".last-page").click(function (e) {
          e.preventDefault();
          window.location.href =
            "?page=wp-spellcheck-seo.php&paged=" + last_page;
        });
      },
      error: function (xhr, status, thrownError) {},
    });
  }

  // BLOCK 3: SEO Listener function
  function wpscx_openai_key_configured() {
    return !!uiData.openai_key_configured;
  }

  function wpscx_seoListener() {
    $(".wpsc-generate-seo-button").click(function (event) {
      event.preventDefault();
      if (!wpscx_openai_key_configured()) {
        return;
      }

      var postID = $(this)
        .closest("tr")
        .find('input[name="edit_page_name[]"]')
        .attr("value");
      var postType = $(this)
        .closest("tr")
        .find('input[name="edit_page_type[]"]')
        .attr("value");
      var wordID = $(this)
        .closest("tr")
        .find('input[name="edit_old_word_id[]"]')
        .attr("value");
      $(this)
        .closest("tr")
        .find(".seo-progress")
        .css("display", "inline-block");

      if (typeof wpscx_seo_ajax_object === "undefined") {
        wpscx_seo_ajax_object = {};
      }
      wpscx_seo_ajax_object.ajax_url = uiData.admin_ajax_url || "";
      if (typeof wpscx_seo_ajax_object.wpsc_openai_nonce === "undefined") {
        wpscx_seo_ajax_object.wpsc_openai_nonce = uiData.nonces
          ? uiData.nonces.wpsc_openai || ""
          : "";
      }

      $.ajax({
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
          $("#wpsc-edit-seo-row-" + wordID)
            .find(".seo-progress")
            .css("display", "none");
          $("#wpsc-edit-seo-row-" + wordID)
            .find(".wpsc-edit-field")
            .val(response.replace(/\n/g, " "));
          $("#wpsc-edit-seo-row-" + wordID)
            .find(".wpsc-edit-field")
            .trigger("input");
        },
      });
    });

    $(".edit-seo-title").on("input", function (e) {
      var textLen = $(this).val().length;

      if (textLen < 50) {
        $(this).css("border-color", "orange");
      } else if (textLen > 70) {
        $(this).css("border-color", "red");
      } else {
        $(this).css("border-color", "green");
      }
    });

    $(".edit-seo-desc").on("input", function (e) {
      var textLen = $(this).val().length;

      if (textLen < 120) {
        $(this).css("border-color", "orange");
      } else if (textLen > 160) {
        $(this).css("border-color", "red");
      } else {
        $(this).css("border-color", "green");
      }
    });
  }
  window.wpscx_seoListener = wpscx_seoListener;

  $(document).on("mouseenter", ".wpsc-generate-seo-wrap", function () {
    if (!$(this).find(".wpsc-generate-seo-button.wpsc-generate-seo-disabled").length) {
      return;
    }
    var $tip = $(this).find(".wpsc-mouseover-text-generate-seo-api");
    $tip
      .stop()
      .css({ display: "block", zIndex: 100 })
      .animate({ opacity: 1 }, 400);
  });
  $(document).on("mouseleave", ".wpsc-generate-seo-wrap", function () {
    var $wrap = $(this);
    if (
      $wrap.find(".wpsc-generate-seo-button:hover").length ||
      $wrap.find(".wpsc-mouseover-text-generate-seo-api:hover").length
    ) {
      return;
    }
    $wrap
      .find(".wpsc-mouseover-text-generate-seo-api")
      .stop()
      .animate({ opacity: 0 }, 400, function () {
        $(this).css({ display: "none", zIndex: -100 });
      });
  });

  // BLOCK 3: Scan button click handler
  $(".wpscScan").click(function (event) {
    event.preventDefault();
    if (scan_in_progress) {
      return;
    }
    scan_in_progress = true;
    activeScanType = $(this).attr("value") || "";
    scan_start_request_pending = true;
    scan_server_confirmed = false;

    if (typeof wpscx_seo_ajax_object === "undefined") {
      wpscx_seo_ajax_object = {};
    }
    wpscx_seo_ajax_object.ajax_url = uiData.admin_ajax_url || "";
    if (
      typeof wpscx_seo_ajax_object.wpsc_start_scan_empty_nonce === "undefined"
    ) {
      wpscx_seo_ajax_object.wpsc_start_scan_empty_nonce = uiData.nonces
        ? uiData.nonces.wpsc_start_scan_empty || ""
        : "";
      wpscx_seo_ajax_object.wpsc_empty_scan_nonce = uiData.nonces
        ? uiData.nonces.wpsc_empty_scan || ""
        : "";
      wpscx_seo_ajax_object.wpsc_finish_empty_scan_nonce = uiData.nonces
        ? uiData.nonces.wpsc_finish_empty_scan || ""
        : "";
      wpscx_seo_ajax_object.wpsc_display_results_empty_nonce = uiData.nonces
        ? uiData.nonces.wpsc_display_results_empty || ""
        : "";
      wpscx_seo_ajax_object.wpsc_get_stats_empty_nonce = uiData.nonces
        ? uiData.nonces.wpsc_get_stats_empty || ""
        : "";
    }

    var scanType = activeScanType;
    var loadingSpinnerUrl =
      uiData.loading_spinner_url ||
      (uiData.plugin_url || "") + "images/loading.svg";

    $("#wpscScanMessage").html(
      '<img src="' +
        loadingSpinnerUrl +
        '" alt="Scan in Progress" class="wpsc-loading-spinner" /> Starting New Scan',
    );
    $(".wpscScan").addClass("wpsc-button-greyout"); // Greyout buttons

    var scanTime = new Date();
    scanStartTime = scanTime.getTime();
    window.setTimeout(wpscex_recheck_scan_temp, 500);

    $.ajax({
      url: wpscx_seo_ajax_object.ajax_url,
      timeout: 7200000,
      type: "POST",
      data: {
        type: scanType,
        action: "wpscx_start_scan_empty",
        nonce: wpscx_seo_ajax_object.wpsc_start_scan_empty_nonce,
      },
      dataType: "html",
      success: function (response) {
        scan_start_request_pending = false;
        if (!scan_server_confirmed) {
          $("#wpscScanMessage").html(response);
          window.setTimeout(wpscex_recheck_scan_temp, 0);
          $("#wpsc-table-results tr.wpsc-row").fadeOut(500, function () {
            $(this).remove();
          });
        }
        $(".wpsc-mesage-container").animate({ opacity: 0 }, 500, function () {
          $(".wpsc-mesage-container").hide();
        });

        $(document).ready(function () {
          var mouseover_visible = false;
          $(".wpsc-mouseover-button-refresh")
            .mouseenter(function () {
              $(".wpsc-mouseover-text-refresh").css("z-index", "100");
              $(".wpsc-mouseover-text-refresh").animate(
                { opacity: 1.0 },
                400,
                function () {
                  mouseover_visible = true;
                },
              );
            })
            .mouseleave(function () {
              var isHoveredPopup = $(".wpsc-mouseover-text-refresh").filter(
                function () {
                  return $(this).is(":hover");
                },
              );
              var isHoveredParent = $(this)
                .parent()
                .filter(function () {
                  return $(this).is(":hover");
                });
              if (!isHoveredPopup && !isHoveredParent) {
                $(".wpsc-mouseover-text-refresh").css("z-index", "-100");
                $(".wpsc-mouseover-text-refresh").animate({ opacity: 0 }, 400);
                mouseover_visible = false;
              }
            });
          $(".wpsc-mouseover-button-refresh").click(function () {
            if (!mouseover_visible) {
              $(".wpsc-mouseover-text-refresh").stop();
              $(".wpsc-mouseover-text-refresh").css("z-index", "100");
              $(".wpsc-mouseover-text-refresh").animate(
                { opacity: 1.0 },
                400,
                function () {
                  mouseover_visible = true;
                },
              );
            } else {
              $(".wpsc-mouseover-text-refresh").css("z-index", "-100");
              $(".wpsc-mouseover-text-refresh").animate({ opacity: 0 }, 400);
              mouseover_visible = false;
            }
          });

          $(".wpsc-mouseover-button-refresh")
            .parent()
            .mouseleave(function () {
              var isHoveredPopup =
                $(".wpsc-mouseover-text-refresh:hover").length > 0;
              var isHoveredButton =
                $(".wpsc-mouseover-button-refresh:hover").length > 0;

              if (!isHoveredPopup && !isHoveredButton) {
                $(".wpsc-mouseover-text-refresh").css("z-index", "-100");
                $(".wpsc-mouseover-text-refresh").animate({ opacity: 0 }, 400);
                mouseover_visible = false;
              }
            });
        });
      },
      error: function (xhr, status, thrownError) {
        window.setTimeout(wpscex_recheck_scan_temp, 500);
      },
    });
  });

  // Initialize SEO listener when DOM is ready
  $(document).ready(function () {
    wpscx_seoListener();
  });
})(jQuery);
