jQuery(document).ready(function() {
	//Code for switching tabs on scan results page
	jQuery("#wpsc-general-options").click(function() {
		jQuery("#wpsc-general-options-tab").removeClass("hidden");
		if (jQuery("#wpsc-scan-options-tab").hasClass("hidden") == false) jQuery("#wpsc-scan-options-tab").addClass("hidden");
		if (jQuery("#wpsc-empty-options-tab").hasClass("hidden") == false) jQuery("#wpsc-empty-options-tab").addClass("hidden");
		if (jQuery("#wpgc-grammar-options-tab").hasClass("hidden") == false) jQuery("#wpgc-grammar-options-tab").addClass("hidden");
		if (jQuery("#wpgc-accessibility-options-tab").hasClass("hidden") == false) jQuery("#wpgc-accessibility-options-tab").addClass("hidden");
		
		jQuery("#wpsc-scan-options").removeClass("selected");
		jQuery("#wpsc-empty-options").removeClass("selected");
		jQuery("#wpsc-grammar-options").removeClass("selected");
		jQuery("#wpsc-accessibility-options").removeClass("selected");
		if (jQuery("#wpsc-general-options").hasClass("selected") == false) jQuery("#wpsc-general-options").addClass("selected");
		
		jQuery(".wpsc-nav-tab").attr("value", "general");
	});
	jQuery("#wpsc-scan-options").click(function() {
		jQuery("#wpsc-scan-options-tab").removeClass("hidden");
		if (jQuery("#wpsc-general-options-tab").hasClass("hidden") == false) jQuery("#wpsc-general-options-tab").addClass("hidden");
		if (jQuery("#wpsc-empty-options-tab").hasClass("hidden") == false) jQuery("#wpsc-empty-options-tab").addClass("hidden");
		if (jQuery("#wpgc-grammar-options-tab").hasClass("hidden") == false) jQuery("#wpgc-grammar-options-tab").addClass("hidden");
		if (jQuery("#wpgc-accessibility-options-tab").hasClass("hidden") == false) jQuery("#wpgc-accessibility-options-tab").addClass("hidden");
		
		jQuery("#wpsc-general-options").removeClass("selected");
		jQuery("#wpsc-empty-options").removeClass("selected");
		jQuery("#wpsc-grammar-options").removeClass("selected");
		jQuery("#wpsc-accessibility-options").removeClass("selected");
		if (jQuery("#wpsc-scan-options").hasClass("selected") == false) jQuery("#wpsc-scan-options").addClass("selected");
		jQuery(".wpsc-nav-tab").attr("value", "scan");
	});
	jQuery("#wpsc-empty-options").click(function() {
		jQuery("#wpsc-empty-options-tab").removeClass("hidden");
		if (jQuery("#wpsc-general-options-tab").hasClass("hidden") == false) jQuery("#wpsc-general-options-tab").addClass("hidden");
		if (jQuery("#wpsc-scan-options-tab").hasClass("hidden") == false) jQuery("#wpsc-scan-options-tab").addClass("hidden");
		if (jQuery("#wpgc-grammar-options-tab").hasClass("hidden") == false) jQuery("#wpgc-grammar-options-tab").addClass("hidden");
		if (jQuery("#wpgc-accessibility-options-tab").hasClass("hidden") == false) jQuery("#wpgc-accessibility-options-tab").addClass("hidden");
		
		jQuery("#wpsc-general-options").removeClass("selected");
		jQuery("#wpsc-scan-options").removeClass("selected");
		jQuery("#wpsc-grammar-options").removeClass("selected");
		jQuery("#wpsc-accessibility-options").removeClass("selected");
		if (jQuery("#wpsc-empty-options").hasClass("selected") == false) jQuery("#wpsc-empty-options").addClass("selected");
		jQuery(".wpsc-nav-tab").attr("value", "empty");
	});
	jQuery("#wpsc-grammar-options").click(function() {
		jQuery("#wpgc-grammar-options-tab").removeClass("hidden");
		if (jQuery("#wpsc-general-options-tab").hasClass("hidden") == false) jQuery("#wpsc-general-options-tab").addClass("hidden");
		if (jQuery("#wpsc-scan-options-tab").hasClass("hidden") == false) jQuery("#wpsc-scan-options-tab").addClass("hidden");
		if (jQuery("#wpsc-empty-options-tab").hasClass("hidden") == false) jQuery("#wpsc-empty-options-tab").addClass("hidden");
		if (jQuery("#wpgc-accessibility-options-tab").hasClass("hidden") == false) jQuery("#wpgc-accessibility-options-tab").addClass("hidden");
		
		jQuery("#wpsc-general-options").removeClass("selected");
		jQuery("#wpsc-scan-options").removeClass("selected");
		jQuery("#wpsc-empty-options").removeClass("selected");
		jQuery("#wpsc-accessibility-options").removeClass("selected");
		if (jQuery("#wpsc-grammar-options").hasClass("selected") == false) jQuery("#wpsc-grammar-options").addClass("selected");
		jQuery(".wpsc-nav-tab").attr("value", "grammar");
	});
	jQuery("#wpsc-accessibility-options").click(function() {
		jQuery("#wpgc-accessibility-options-tab").removeClass("hidden");
		if (jQuery("#wpsc-general-options-tab").hasClass("hidden") == false) jQuery("#wpsc-general-options-tab").addClass("hidden");
		if (jQuery("#wpsc-scan-options-tab").hasClass("hidden") == false) jQuery("#wpsc-scan-options-tab").addClass("hidden");
		if (jQuery("#wpsc-empty-options-tab").hasClass("hidden") == false) jQuery("#wpsc-empty-options-tab").addClass("hidden");
		if (jQuery("#wpgc-grammar-options-tab").hasClass("hidden") == false) jQuery("#wpgc-grammar-options-tab").addClass("hidden");
		
		jQuery("#wpsc-general-options").removeClass("selected");
		jQuery("#wpsc-scan-options").removeClass("selected");
		jQuery("#wpsc-empty-options").removeClass("selected");
		jQuery("#wpsc-grammar-options").removeClass("selected");
		if (jQuery("#wpsc-accessibility-options").hasClass("selected") == false) jQuery("#wpsc-accessibility-options").addClass("selected");
		jQuery(".wpsc-nav-tab").attr("value", "accessibility");
	});
	
	jQuery("#check-all").click(function() {
		if (jQuery(this).is(':checked')) {
			jQuery('#wpsc-scan-options-tab input:not(#check-all):not(.ignore-check-all)').prop('checked',true);
		} else {
			jQuery('#wpsc-scan-options-tab input:not(#check-all):not(.ignore-check-all)').prop('checked',false);
		}
	});
	
	jQuery("#check-all-empty").click(function() {
		if (jQuery(this).is(':checked')) {
			jQuery('#wpsc-empty-options-tab input:not(#check-all-empty):not(.ignore-check-all)').prop('checked', true);
		} else {
			jQuery('#wpsc-empty-options-tab input:not(#check-all-empty):not(.ignore-check-all)').prop('checked', false);
		}
	});
});
jQuery("#wpsc-grammar-options").removeClass("selected");