/**
 * Options Page Scripts
 * Handles import button state based on file selection (Pro).
 *
 * @since 9.22
 */
(function () {
	"use strict";

	// Handle Import button state based on file selection
	var importFileInput = document.getElementById( "import-file" );
	var importButton    = document.getElementById( "wpsc-import-button" );

	if (importFileInput && importButton) {
		// Check if Pro is active (button is not permanently disabled)
		var isProActive = ! importFileInput.disabled;

		if (isProActive) {
			// Initially disable and gray out the button
			importButton.disabled = true;
			importButton.setAttribute(
				"style",
				"background-color: #646970 !important; border-color: #646970 !important; color: #fff !important; cursor: not-allowed !important;",
			);

			// Listen for file selection changes
			importFileInput.addEventListener(
				"change",
				function () {
					if (this.files && this.files.length > 0) {
						// File selected - enable button and make it blue
						importButton.disabled = false;
						importButton.setAttribute(
							"style",
							"background-color: #2271b1 !important; border-color: #2271b1 !important; color: #fff !important; cursor: pointer !important;",
						);
					} else {
						// No file selected - disable button and make it gray
						importButton.disabled = true;
						importButton.setAttribute(
							"style",
							"background-color: #646970 !important; border-color: #646970 !important; color: #fff !important; cursor: not-allowed !important;",
						);
					}
				}
			);
		}
	}
})();
