jQuery(document).ready(function() {
	//Code for switching tabs on scan results page
	jQuery("#wpsc-spellcheck-words").click(function() {
		jQuery("#wpsc-words-tab").removeClass("hidden");
		if (jQuery("#wpsc-empty-tab").hasClass("hidden") == false) jQuery("#wpsc-empty-tab").addClass("hidden");
		
		jQuery("#wpsc-empty-fields").removeClass("selected");
		if (jQuery("#wpsc-spellcheck-words").hasClass("selected") == false) jQuery("#wpsc-spellcheck-words").addClass("selected");
	});
	jQuery("#wpsc-empty-fields").click(function() {
		jQuery("#wpsc-empty-tab").removeClass("hidden");
		if (jQuery("#wpsc-words-tab").hasClass("hidden") == false) jQuery("#wpsc-words-tab").addClass("hidden");
		
		jQuery("#wpsc-spellcheck-words").removeClass("selected");
		if (jQuery("#wpsc-empty-fields").hasClass("selected") == false) jQuery("#wpsc-empty-fields").addClass("selected");
	});
});