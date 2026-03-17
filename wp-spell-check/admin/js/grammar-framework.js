/**
 * Grammar Framework JavaScript
 * Extracted from inline script in grammar_framework.php
 * Handles grammar/spellcheck highlighting UI functionality
 */

(function ($) {
	"use strict";

	// Ensure wpgcGrammarFramework object exists
	if (typeof wpgcGrammarFramework === "undefined") {
		console.error(
			"wpgcGrammarFramework object not found. Make sure script is localized properly.",
		);
		return;
	}

	function wpscx_create_regex(item) {
		if (item.charAt( 0 ).match( /[^a-z]/i )) {
			regex_item = new RegExp( "(?![^<]*>)" + item + "\\b(?!.*&gt;)", "gim" );
		} else {
			regex_item = new RegExp(
				"(?![^<]*>)(?![^&lt;]*&gt;)\\b" + item + "\\b(?!.*&gt;)",
				"gim",
			);
		}

		return regex_item;
	}

	function wpgc_strip_html(html) {
		var tmp       = document.createElement( "DIV" );
		tmp.innerHTML = html;
		return tmp.textContent || tmp.innerText || "";
	}

	function wpgcx_clear_results() {
		var complex_highlight      =
		wpgcGrammarFramework.complexExpressionHighlight || [];
		var contractions_highlight =
		wpgcGrammarFramework.contractionsHighlightArray || [];
		var grammar_highlight      = wpgcGrammarFramework.grammarHighlightArray || [];
		var hidden_highlight       = wpgcGrammarFramework.hiddenVerbHighlight || [];
		var passive_highlight      = wpgcGrammarFramework.passiveVoiceHighlight || [];
		var possessive_highlight   =
		wpgcGrammarFramework.possessiveEndingHighlight || [];
		var redundant_highlight    =
		wpgcGrammarFramework.redundantExpressionHighlight || [];

		complex_highlight.forEach(
			function (item) {
				$( ".wp-editor-area#content" ).html(
					$( ".wp-editor-area#content" )
					.html()
					.replace(
						" &lt;span class='hiddenSpellError wpgc-complex' style='background: #59c033;'&gt;" +
						item +
						"&lt;/span&gt; ",
						" " + item + " ",
					),
				);
				$( "#content_ifr, #excerpt_ifr" )
				.contents()
				.find( "#tinymce" )
				.html(
					$( "#content_ifr, #excerpt_ifr" )
					.contents()
					.find( "#tinymce" )
					.html()
					.replace(
						' <span class="hiddenSpellError wpgc-complex" style="background: #a3c5ff;" data-mce-style="background: #a3c5ff;">' +
						item +
						"</span> ",
						" " + item + " ",
					),
				);
			}
		);

		contractions_highlight.forEach(
			function (item) {
				$( ".wp-editor-area#content" ).html(
					$( ".wp-editor-area#content" )
					.html()
					.replace(
						" &lt;span class='hiddenSpellError wpgc-contraction' style='background: #59c033;'&gt;" +
						item +
						"&lt;/span&gt; ",
						" " + item + " ",
					),
				);
				$( "#content_ifr, #excerpt_ifr" )
				.contents()
				.find( "#tinymce" )
				.html(
					$( "#content_ifr, #excerpt_ifr" )
					.contents()
					.find( "#tinymce" )
					.html()
					.replace(
						' <span class="hiddenSpellError wpgc-contraction" style="background: #59c033;" data-mce-style="background: #59c033;">' +
						item +
						"</span> ",
						" " + item + " ",
					),
				);
			}
		);

		grammar_highlight.forEach(
			function (item) {
				$( ".wp-editor-area#content" ).html(
					$( ".wp-editor-area#content" )
					.html()
					.replace(
						" &lt;span class='hiddenSpellError wpgc-grammar' style='background: #59c033;'&gt;" +
						item +
						"&lt;/span&gt; ",
						" " + item + " ",
					),
				);
				$( "#content_ifr, #excerpt_ifr" )
				.contents()
				.find( "#tinymce" )
				.html(
					$( "#content_ifr, #excerpt_ifr" )
					.contents()
					.find( "#tinymce" )
					.html()
					.replace(
						' <span class="hiddenSpellError wpgc-grammar" style="background: #59c033;" data-mce-style="background: #59c033;">' +
						item +
						"</span> ",
						" " + item + " ",
					),
				);
			}
		);

		hidden_highlight.forEach(
			function (item) {
				$( ".wp-editor-area#content" ).html(
					$( ".wp-editor-area#content" )
					.html()
					.replace(
						" &lt;span class='hiddenSpellError wpgc-hidden' style='background: #59c033;'&gt;" +
						item +
						"&lt;/span&gt; ",
						" " + item + " ",
					),
				);
				$( "#content_ifr, #excerpt_ifr" )
				.contents()
				.find( "#tinymce" )
				.html(
					$( "#content_ifr, #excerpt_ifr" )
					.contents()
					.find( "#tinymce" )
					.html()
					.replace(
						' <span class="hiddenSpellError wpgc-hidden" style="background: #59c033;" data-mce-style="background: #59c033;">' +
						item +
						"</span> ",
						" " + item + " ",
					),
				);
			}
		);

		passive_highlight.forEach(
			function (item) {
				$( ".wp-editor-area#content" ).html(
					$( ".wp-editor-area#content" )
					.html()
					.replace(
						" &lt;span class='hiddenSpellError wpgc-passive' style='background: #59c033;'&gt;" +
						item +
						"&lt;/span&gt; ",
						" " + item + " ",
					),
				);
				$( "#content_ifr, #excerpt_ifr" )
				.contents()
				.find( "#tinymce" )
				.html(
					$( "#content_ifr, #excerpt_ifr" )
					.contents()
					.find( "#tinymce" )
					.html()
					.replace(
						' <span class="hiddenSpellError wpgc-passive" style="background: #59c033;" data-mce-style="background: #59c033;">' +
						item +
						"</span> ",
						" " + item + " ",
					),
				);
			}
		);

		redundant_highlight.forEach(
			function (item) {
				$( ".wp-editor-area#content" ).html(
					$( ".wp-editor-area#content" )
					.html()
					.replace(
						" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" +
						item +
						"&lt;/span&gt; ",
						" " + item + " ",
					),
				);
				$( "#content_ifr, #excerpt_ifr" )
				.contents()
				.find( "#tinymce" )
				.html(
					$( "#content_ifr, #excerpt_ifr" )
					.contents()
					.find( "#tinymce" )
					.html()
					.replace(
						' <span class="hiddenSpellError wpgc-redundant" style="background: #59c033;" data-mce-style="background: #59c033;">' +
						item +
						"</span> ",
						" " + item + " ",
					),
				);
			}
		);

		possessive_highlight.forEach(
			function (item) {
				$( ".wp-editor-area#content" ).html(
					$( ".wp-editor-area#content" )
					.html()
					.replace(
						" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" +
						item +
						"&lt;/span&gt; ",
						" " + item + " ",
					),
				);
				$( "#content_ifr, #excerpt_ifr" )
				.contents()
				.find( "#tinymce" )
				.html(
					$( "#content_ifr, #excerpt_ifr" )
					.contents()
					.find( "#tinymce" )
					.html()
					.replace(
						' <span class="hiddenSpellError wpgc-possessive" style="background: #59c033;" data-mce-style="background: #59c033;">' +
						item +
						"</span> ",
						" " + item + " ",
					),
				);
			}
		);
	}

	var allow_save        = "false";
	var current_highlight = "grammar";
	var html_to_add       = "";
	var word_to_edit;
	var word_check;
	var suggest_split;
	var regex_item;
	var editor;

	function wpgc_set_listeners() {
		window.setTimeout(
			function () {
				var iframe      = $( "#content_ifr, #excerpt_ifr" ).contents();
				var suggestions = wpgcGrammarFramework.suggestions || [];

				iframe.find( ".hiddenSpellError" ).click(
					function (e) {
						word_to_edit    = $( this );
						word_check      = $( this ).html();
						var error_class = $( this ).attr( "class" );
						error_class     = error_class.split( " " )[1].split( "-" )[1];
						if (error_class == "complex") {
							error_class = "Complex Expression";
						}
						if (error_class == "passive") {
							error_class = "Passive Voice";
						}
						if (error_class == "redundant") {
							error_class = "Redundant Expression";
						}
						if (error_class == "grammar") {
							error_class = "Grammar";
						}
						if (error_class == "hidden") {
							error_class = "Hidden Verb";
						}
						if (error_class == "possessive") {
							error_class = "Possessive Ending";
						}
						if (error_class == "contraction") {
							error_class = "Contraction";
						}
						html_to_add =
						"<li class='wpgc-dialog-error-type'>" + error_class + "</li><hr>";

						if (suggestions.length > 1) {
							suggestions.forEach(
								function (suggestion) {
									if (word_check == suggestion[0]) {
										if (suggestion[1] != null) {
											if (suggestion[1].includes( "," )) {
												suggest_split = suggestion[1].split( "," );
												suggest_split.forEach(
													function (split_word) {
														html_to_add +=
														"<li><a href='#_' class='wpgc-suggestion'>" +
														split_word +
														"</a></li>";
													}
												);
											} else if (suggestion[1].includes( "/" )) {
												suggest_split = suggestion[1].split( "/" );
												suggest_split.forEach(
													function (split_word) {
														html_to_add +=
														"<li><a href='#_' class='wpgc-suggestion'>" +
														split_word +
														"</a></li>";
													}
												);
											} else {
												html_to_add +=
												"<li><a href='#_' class='wpgc-suggestion'>" +
												suggestion[1] +
												"</a></li>";
											}
										}
									}
								}
							);
						}
						html_to_add +=
						"<hr><li><a href='#_' class='wpgc-dialog-close'>Ignore</a></li>";

						$( ".wpgc-dialog ul" ).html( html_to_add );
						$( ".wpgc-dialog" ).css( "top", e.pageY + 200 );
						$( ".wpgc-dialog" ).css( "left", e.pageX );
						$( ".wpgc-dialog" ).css( "display", "block" );
						$( ".wpgc-dialog-close" ).click(
							function () {
								word_to_edit.css( "background", "inherit" );
								$( ".wpgc-dialog" ).css( "display", "none" );
							}
						);

						$( ".wpgc-suggestion" ).click(
							function () {
								if ($( this ).html() == "DELETE") {
									word_to_edit.html( "" );
								} else {
									word_to_edit.html( $( this ).html() );
								}
								word_to_edit.css( "background", "inherit" );
								$( ".wpgc-dialog" ).css( "display", "none" );
							}
						);

						$( ".wpgc-dialog a" ).hover(
							function () {
								$( this ).css( "background-color", "grey" );
							},
							function () {
								$( this ).css( "background-color", "lightgrey" );
							},
						);
					}
				);
			},
			250
		);
	}

	$( document ).ready(
		function () {
			// Get data from localized script
			var spelling_highlight     = wpgcGrammarFramework.spellingHighlight || [];
			var complex_highlight      = wpgcGrammarFramework.complexHighlight || [];
			var contractions_highlight =
			wpgcGrammarFramework.contractionsHighlight || [];
			var grammar_highlight      = wpgcGrammarFramework.grammarHighlight || [];
			var hidden_highlight       = wpgcGrammarFramework.hiddenHighlight || [];
			var passive_highlight      = wpgcGrammarFramework.passiveHighlight || [];
			var possessive_highlight   = wpgcGrammarFramework.possessiveHighlight || [];
			var redundant_highlight    = wpgcGrammarFramework.redundantHighlight || [];
			var suggestions            = wpgcGrammarFramework.suggestions || [];
			var spellcheck             = wpgcGrammarFramework.spellcheck || false;
			var builder_check          = wpgcGrammarFramework.builderCheck || "";

			$( ".wp-editor-area#content" ).on(
				"change",
				function (event) {
					window.addEventListener(
						"beforeunload",
						function (event) {
							event.returnValue = "Are you sure you want to continue?";
						}
					);
				}
			);

			// Process spelling highlights - replace HTML entities
			for (var x = 0; x < spelling_highlight.length; x++) {
					spelling_highlight[x] = spelling_highlight[x].replace( "&eacute;", "é" );
					spelling_highlight[x] = spelling_highlight[x].replace( "&egrave;", "è" );
					spelling_highlight[x] = spelling_highlight[x].replace( "&ugrave;", "ù" );
					spelling_highlight[x] = spelling_highlight[x].replace( "&acirc;", "â" );
					spelling_highlight[x] = spelling_highlight[x].replace( "&ecirc;", "ê" );
					spelling_highlight[x] = spelling_highlight[x].replace( "&icirc;", "î" );
					spelling_highlight[x] = spelling_highlight[x].replace( "&ocirc;", "ô" );
					spelling_highlight[x] = spelling_highlight[x].replace( "&ucirc;", "û" );
					spelling_highlight[x] = spelling_highlight[x].replace( "&ccedil;", "ç" );
					spelling_highlight[x] = spelling_highlight[x].replace( "&euml;", "ë" );
					spelling_highlight[x] = spelling_highlight[x].replace( "&iuml;", "ï" );
					spelling_highlight[x] = spelling_highlight[x].replace( "&uuml;", "ü" );
					spelling_highlight[x] = spelling_highlight[x].replace( ">", "&gt;" );
					spelling_highlight[x] = spelling_highlight[x].replace( "<", "&lt;" );
			}

			// Process suggestions - replace HTML entities
			for (x = 0; x < suggestions.length; x++) {
				for (var y = 0; y < suggestions[x].length; y++) {
					if (suggestions[x][y] != null) {
						suggestions[x][y] = suggestions[x][y].replace( "&eacute;", "é" );
						suggestions[x][y] = suggestions[x][y].replace( "&egrave;", "è" );
						suggestions[x][y] = suggestions[x][y].replace( "&ugrave;", "ù" );
						suggestions[x][y] = suggestions[x][y].replace( "&acirc;", "â" );
						suggestions[x][y] = suggestions[x][y].replace( "&ecirc;", "ê" );
						suggestions[x][y] = suggestions[x][y].replace( "&icirc;", "î" );
						suggestions[x][y] = suggestions[x][y].replace( "&ocirc;", "ô" );
						suggestions[x][y] = suggestions[x][y].replace( "&ucirc;", "û" );
						suggestions[x][y] = suggestions[x][y].replace( "&ccedil;", "ç" );
						suggestions[x][y] = suggestions[x][y].replace( "&euml;", "ë" );
						suggestions[x][y] = suggestions[x][y].replace( "&iuml;", "ï" );
						suggestions[x][y] = suggestions[x][y].replace( "&uuml;", "ü" );
					}
				}
			}

			if (typeof $( ".wp-editor-area#content" ).html() === "undefined") {
				return;
			}
			var html_check  = new RegExp( "<(?!.*>)", "gim" );
			var html_result = $( ".wp-editor-area#content" ).html().match( html_check );
			if (html_result != null) {
				$( ".wpsc-editor-message" ).html(
					"Invalid HTML detected on page. Please fix the HTML in order to see proofreading highlights",
				);
				return;
			}
			html_check  = new RegExp( "&lt;(?!.*&gt;)", "gim" );
			html_result = $( ".wp-editor-area#content" ).html().match( html_check );
			if (html_result != null) {
				$( ".wpsc-editor-message" ).html(
					"Invalid HTML detected on page. Please fix the HTML in order to see proofreading highlights",
				);
				return;
			}

			if (builder_check != "Divi") {
				if ($( "#wp-content-wrap" ).hasClass( "tmce-active" )) {
					window.setTimeout(
						function () {
							var highlightTime = new Date();
							var totalTime     = highlightTime.getTime();
							if (spellcheck == true) {
								current_highlight = "spelling";
								window.setTimeout(
									function () {
										spelling_highlight.forEach(
											function (item) {
												regex_item  = wpscx_create_regex( item );
												var str_rep = item.replace( /\\(.)/gm, "$1" );
												$( ".wp-editor-area#content" ).html(
													$( ".wp-editor-area#content" )
													.html()
													.replace(
														regex_item,
														"<span class='hiddenSpellError wpgc-spelling' style='background: #FFC0C0;'>" +
														str_rep +
														"</span>",
													),
												);
													$( "#content_ifr, #excerpt_ifr" )
													.contents()
													.find( "#tinymce" )
													.html(
														$( "#content_ifr, #excerpt_ifr" )
														.contents()
														.find( "#tinymce" )
														.html()
														.replace(
															regex_item,
															"<span class='hiddenSpellError wpgc-spelling' style='background: #FFC0C0;'>" +
															str_rep +
															"</span>",
														),
													);
											}
										);

										complex_highlight.forEach(
											function (item) {
												regex_item = wpscx_create_regex( item );
												$( ".wp-editor-area#content" ).html(
													$( ".wp-editor-area#content" )
													.html()
													.replace(
														regex_item,
														"<span class='hiddenSpellError wpgc-complex' style='background: inherit'>" +
														item +
														"</span>",
													),
												);
														$( "#content_ifr, #excerpt_ifr" )
														.contents()
														.find( "#tinymce" )
														.html(
															$( "#content_ifr, #excerpt_ifr" )
															.contents()
															.find( "#tinymce" )
															.html()
															.replace(
																regex_item,
																"<span class='hiddenSpellError wpgc-complex' style='background: inherit'>" +
																item +
																"</span>",
															),
														);
											}
										);

										contractions_highlight.forEach(
											function (item) {
												regex_item = wpscx_create_regex( item );
												$( ".wp-editor-area#content" ).html(
													$( ".wp-editor-area#content" )
													.html()
													.replace(
														regex_item,
														"<span class='hiddenSpellError wpgc-contraction' style='background: inherit'>" +
														item +
														"</span>",
													),
												);
														$( "#content_ifr, #excerpt_ifr" )
														.contents()
														.find( "#tinymce" )
														.html(
															$( "#content_ifr, #excerpt_ifr" )
															.contents()
															.find( "#tinymce" )
															.html()
															.replace(
																regex_item,
																"<span class='hiddenSpellError wpgc-contraction' style='background: inherit'>" +
																item +
																"</span>",
															),
														);
											}
										);

										grammar_highlight.forEach(
											function (item) {
												regex_item = wpscx_create_regex( item );
												$( ".wp-editor-area#content" ).html(
													$( ".wp-editor-area#content" )
													.html()
													.replace(
														regex_item,
														"<span class='hiddenSpellError wpgc-grammar' style='background: inherit'>" +
														item +
														"</span>",
													),
												);
														$( "#content_ifr, #excerpt_ifr" )
														.contents()
														.find( "#tinymce" )
														.html(
															$( "#content_ifr, #excerpt_ifr" )
															.contents()
															.find( "#tinymce" )
															.html()
															.replace(
																regex_item,
																"<span class='hiddenSpellError wpgc-grammar' style='background: inherit'>" +
																item +
																"</span>",
															),
														);
											}
										);

										hidden_highlight.forEach(
											function (item) {
												regex_item = wpscx_create_regex( item );
												$( ".wp-editor-area#content" ).html(
													$( ".wp-editor-area#content" )
													.html()
													.replace(
														regex_item,
														"<span class='hiddenSpellError wpgc-hidden' style='background: inherit'>" +
														item +
														"</span>",
													),
												);
														$( "#content_ifr, #excerpt_ifr" )
														.contents()
														.find( "#tinymce" )
														.html(
															$( "#content_ifr, #excerpt_ifr" )
															.contents()
															.find( "#tinymce" )
															.html()
															.replace(
																regex_item,
																"<span class='hiddenSpellError wpgc-hidden' style='background: inherit'>" +
																item +
																"</span>",
															),
														);
											}
										);

										passive_highlight.forEach(
											function (item) {
												regex_item = wpscx_create_regex( item );
												$( ".wp-editor-area#content" ).html(
													$( ".wp-editor-area#content" )
													.html()
													.replace(
														regex_item,
														"<span class='hiddenSpellError wpgc-passive' style='background: inherit'>" +
														item +
														"</span>",
													),
												);
														$( "#content_ifr, #excerpt_ifr" )
														.contents()
														.find( "#tinymce" )
														.html(
															$( "#content_ifr, #excerpt_ifr" )
															.contents()
															.find( "#tinymce" )
															.html()
															.replace(
																regex_item,
																"<span class='hiddenSpellError wpgc-passive' style='background: inherit;'>" +
																item +
																"</span>",
															),
														);
											}
										);

										redundant_highlight.forEach(
											function (item) {
												regex_item = wpscx_create_regex( item );
												$( ".wp-editor-area#content" ).html(
													$( ".wp-editor-area#content" )
													.html()
													.replace(
														regex_item,
														"<span class='hiddenSpellError wpgc-redundant' style='background: inherit'>" +
														item +
														"</span>",
													),
												);
														$( "#content_ifr, #excerpt_ifr" )
														.contents()
														.find( "#tinymce" )
														.html(
															$( "#content_ifr, #excerpt_ifr" )
															.contents()
															.find( "#tinymce" )
															.html()
															.replace(
																regex_item,
																"<span class='hiddenSpellError wpgc-redundant' style='background: inherit;'>" +
																item +
																"</span>",
															),
														);
											}
										);

										possessive_highlight.forEach(
											function (item) {
												regex_item = wpscx_create_regex( item );
												$( ".wp-editor-area#content" ).html(
													$( ".wp-editor-area#content" )
													.html()
													.replace(
														regex_item,
														"<span class='hiddenSpellError wpgc-possessive' style='background: inherit'>" +
														item +
														"</span>",
													),
												);
														$( "#content_ifr, #excerpt_ifr" )
														.contents()
														.find( "#tinymce" )
														.html(
															$( "#content_ifr, #excerpt_ifr" )
															.contents()
															.find( "#tinymce" )
															.html()
															.replace(
																regex_item,
																"<span class='hiddenSpellError wpgc-possessive' style='background: inherit;'>" +
																item +
																"</span>",
															),
														);
											}
										);
										wpgc_set_listeners();
									},
									3000
								);
							} else {
								window.setTimeout(
									function () {
										complex_highlight.forEach(
											function (item) {
												regex_item = wpscx_create_regex( item );
												$( ".wp-editor-area#content" ).html(
													$( ".wp-editor-area#content" )
													.html()
													.replace(
														regex_item,
														"<span class='hiddenSpellError wpgc-complex' style='background: #a3c5ff;'>" +
														item +
														"</span>",
													),
												);
												$( "#content_ifr, #excerpt_ifr" )
													.contents()
													.find( "#tinymce" )
													.html(
														$( "#content_ifr, #excerpt_ifr" )
														.contents()
														.find( "#tinymce" )
														.html()
														.replace(
															regex_item,
															"<span class='hiddenSpellError wpgc-complex' style='background: #a3c5ff;'>" +
															item +
															"</span>",
														),
													);
											}
										);

										contractions_highlight.forEach(
											function (item) {
													regex_item = wpscx_create_regex( item );
													$( ".wp-editor-area#content" ).html(
														$( ".wp-editor-area#content" )
														.html()
														.replace(
															regex_item,
															"<span class='hiddenSpellError wpgc-contraction' style='background: #59c033;'>" +
															item +
															"</span>",
														),
													);
													$( "#content_ifr, #excerpt_ifr" )
														.contents()
														.find( "#tinymce" )
														.html(
															$( "#content_ifr, #excerpt_ifr" )
															.contents()
															.find( "#tinymce" )
															.html()
															.replace(
																regex_item,
																"<span class='hiddenSpellError wpgc-contraction' style='background: #59c033;'>" +
																item +
																"</span>",
															),
														);
											}
										);

										grammar_highlight.forEach(
											function (item) {
													regex_item = wpscx_create_regex( item );
													$( ".wp-editor-area#content" ).html(
														$( ".wp-editor-area#content" )
														.html()
														.replace(
															regex_item,
															"<span class='hiddenSpellError wpgc-grammar' style='background: #59c033;'>" +
															item +
															"</span>",
														),
													);
													$( "#content_ifr, #excerpt_ifr" )
														.contents()
														.find( "#tinymce" )
														.html(
															$( "#content_ifr, #excerpt_ifr" )
															.contents()
															.find( "#tinymce" )
															.html()
															.replace(
																regex_item,
																"<span class='hiddenSpellError wpgc-grammar' style='background: #59c033;'>" +
																item +
																"</span>",
															),
														);
											}
										);

										hidden_highlight.forEach(
											function (item) {
													regex_item = wpscx_create_regex( item );
													$( ".wp-editor-area#content" ).html(
														$( ".wp-editor-area#content" )
														.html()
														.replace(
															regex_item,
															"<span class='hiddenSpellError wpgc-hidden' style='background: #59c033;'>" +
															item +
															"</span>",
														),
													);
													$( "#content_ifr, #excerpt_ifr" )
														.contents()
														.find( "#tinymce" )
														.html(
															$( "#content_ifr, #excerpt_ifr" )
															.contents()
															.find( "#tinymce" )
															.html()
															.replace(
																regex_item,
																"<span class='hiddenSpellError wpgc-hidden' style='background: #59c033;'>" +
																item +
																"</span>",
															),
														);
											}
										);

										passive_highlight.forEach(
											function (item) {
													regex_item = wpscx_create_regex( item );
													$( ".wp-editor-area#content" ).html(
														$( ".wp-editor-area#content" )
														.html()
														.replace(
															regex_item,
															"<span class='hiddenSpellError wpgc-passive' style='background: #59c033;'>" +
															item +
															"</span>",
														),
													);
													$( "#content_ifr, #excerpt_ifr" )
														.contents()
														.find( "#tinymce" )
														.html(
															$( "#content_ifr, #excerpt_ifr" )
															.contents()
															.find( "#tinymce" )
															.html()
															.replace(
																regex_item,
																"<span class='hiddenSpellError wpgc-passive' style='background: #59c033;'>" +
																item +
																"</span>",
															),
														);
											}
										);

										redundant_highlight.forEach(
											function (item) {
													regex_item = wpscx_create_regex( item );
													$( ".wp-editor-area#content" ).html(
														$( ".wp-editor-area#content" )
														.html()
														.replace(
															regex_item,
															"<span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'>" +
															item +
															"</span>",
														),
													);
													$( "#content_ifr, #excerpt_ifr" )
														.contents()
														.find( "#tinymce" )
														.html(
															$( "#content_ifr, #excerpt_ifr" )
															.contents()
															.find( "#tinymce" )
															.html()
															.replace(
																regex_item,
																"<span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'>" +
																item +
																"</span>",
															),
														);
											}
										);

										possessive_highlight.forEach(
											function (item) {
													regex_item = wpscx_create_regex( item );
													$( ".wp-editor-area#content" ).html(
														$( ".wp-editor-area#content" )
														.html()
														.replace(
															regex_item,
															"<span class='hiddenSpellError wpgc-possessive' style='background: #59c033;'>" +
															item +
															"</span>",
														),
													);
													$( "#content_ifr, #excerpt_ifr" )
														.contents()
														.find( "#tinymce" )
														.html(
															$( "#content_ifr, #excerpt_ifr" )
															.contents()
															.find( "#tinymce" )
															.html()
															.replace(
																regex_item,
																"<span class='hiddenSpellError wpgc-possessive' style='background: #59c033;'>" +
																item +
																"</span>",
															),
														);
											}
										);

										spelling_highlight.forEach(
											function (item) {
													regex_item  = wpscx_create_regex( item );
													var str_rep = item.replace( /\\(.)/gm, "$1" );
													$( ".wp-editor-area#content" ).html(
														$( ".wp-editor-area#content" )
														.html()
														.replace(
															regex_item,
															"<span class='hiddenSpellError wpgc-spelling' style='background: inherit;'>" +
															str_rep +
															"</span>",
														),
													);
													$( "#content_ifr, #excerpt_ifr" )
														.contents()
														.find( "#tinymce" )
														.html(
															$( "#content_ifr, #excerpt_ifr" )
															.contents()
															.find( "#tinymce" )
															.html()
															.replace(
																regex_item,
																"<span class='hiddenSpellError wpgc-spelling' style='background: inherit;'>" +
																str_rep +
																"</span>",
															),
														);
											}
										);
										wpgc_set_listeners();
									},
									1000
								);
							}
							var highlightTime = new Date();
							var endTime       = highlightTime.getTime();
						},
						3000
					);
				}
			}

			// Button click handlers
			$( ".wpgc-scan-page" ).click(
				function () {
					var scanUrl = wpgcGrammarFramework.scanPageUrl || "";
					if (scanUrl) {
						window.location.href = scanUrl + "&wpgc-scan-page=Spell Check";
					}
				}
			);

			$( ".wpgc-scan-page-grammar" ).click(
				function () {
					var scanUrl = wpgcGrammarFramework.scanPageUrl || "";
					if (scanUrl) {
						window.location.href = scanUrl + "&wpgc-scan-page=Gramme Check";
					}
				}
			);

			$( "#publishing-action .button" ).click(
				function (e) {
					if (allow_save != "true") {
						e.preventDefault();
						$( ".wp-editor-area#content" )
						.contents()
						.find( ".hiddenSpellError" )
						.contents()
						.unwrap();
						$( "#content_ifr, #excerpt_ifr" )
						.contents()
						.find( "#tinymce" )
						.contents()
						.find( ".hiddenSpellError" )
						.contents()
						.unwrap();
						allow_save = "true";
						$( "#publishing-action .button" ).click();
					}
				}
			);

			$( "#save-action .button" ).click(
				function (e) {
					if (allow_save != "true") {
						e.preventDefault();
						$( ".wp-editor-area#content" )
						.contents()
						.find( ".hiddenSpellError" )
						.contents()
						.unwrap();
						$( "#content_ifr, #excerpt_ifr" )
						.contents()
						.find( "#tinymce" )
						.contents()
						.find( ".hiddenSpellError" )
						.contents()
						.unwrap();
						allow_save = "true";
						$( "#save-action .button" ).click();
					}
				}
			);

			$( ".switch-html" ).click(
				function (e) {
					$( ".wp-editor-area#content" )
					.contents()
					.find( ".hiddenSpellError" )
					.contents()
					.unwrap();
					$( "#content_ifr, #excerpt_ifr" )
					.contents()
					.find( "#tinymce" )
					.contents()
					.find( ".hiddenSpellError" )
					.contents()
					.unwrap();
					allow_save = "true";
				}
			);

			$( "#content-tmce" ).click(
				function () {
					allow_save = "false";
					wpgc_set_listeners();
				}
			);

			$( ".wpgc-spelling-highlight" ).click(
				function () {
					if (current_highlight == "spelling") {
						spelling_highlight.forEach(
							function (item) {
								$( ".wp-editor-area#content" )
								.contents()
								.find( ".wpgc-spelling" )
								.css( "background", "inherit" );
								$( "#content_ifr, #excerpt_ifr" )
								.contents()
								.find( "#tinymce" )
								.contents()
								.find( ".wpgc-spelling" )
								.css( "background", "inherit" );
							}
						);
						current_highlight = "none";
						allow_save        = true;
					} else {
						if (current_highlight == "grammar") {
							complex_highlight.forEach(
								function (item) {
									$( ".wp-editor-area#content" )
									.contents()
									.find( ".wpgc-complex" )
									.css( "background", "inherit" );
									$( "#content_ifr, #excerpt_ifr" )
									.contents()
									.find( "#tinymce" )
									.contents()
									.find( ".wpgc-complex" )
									.css( "background", "inherit" );
								}
							);

							contractions_highlight.forEach(
								function (item) {
										$( ".wp-editor-area#content" )
									.contents()
									.find( ".wpgc-contraction" )
									.css( "background", "inherit" );
										$( "#content_ifr, #excerpt_ifr" )
									.contents()
									.find( "#tinymce" )
									.contents()
									.find( ".wpgc-contraction" )
									.css( "background", "inherit" );
								}
							);

							grammar_highlight.forEach(
								function (item) {
										$( ".wp-editor-area#content" )
									.contents()
									.find( ".wpgc-grammar" )
									.css( "background", "inherit" );
										$( "#content_ifr, #excerpt_ifr" )
									.contents()
									.find( "#tinymce" )
									.contents()
									.find( ".wpgc-grammar" )
									.css( "background", "inherit" );
								}
							);

							hidden_highlight.forEach(
								function (item) {
										$( ".wp-editor-area#content" )
									.contents()
									.find( ".wpgc-hidden" )
									.css( "background", "inherit" );
										$( "#content_ifr, #excerpt_ifr" )
									.contents()
									.find( "#tinymce" )
									.contents()
									.find( ".wpgc-hidden" )
									.css( "background", "inherit" );
								}
							);

							passive_highlight.forEach(
								function (item) {
										$( ".wp-editor-area#content" )
									.contents()
									.find( ".wpgc-passive" )
									.css( "background", "inherit" );
										$( "#content_ifr, #excerpt_ifr" )
									.contents()
									.find( "#tinymce" )
									.contents()
									.find( ".wpgc-passive" )
									.css( "background", "inherit" );
								}
							);

							redundant_highlight.forEach(
								function (item) {
										$( ".wp-editor-area#content" )
									.contents()
									.find( ".wpgc-redundant" )
									.css( "background", "inherit" );
										$( "#content_ifr, #excerpt_ifr" )
									.contents()
									.find( "#tinymce" )
									.contents()
									.find( ".wpgc-redundant" )
									.css( "background", "inherit" );
								}
							);

							possessive_highlight.forEach(
								function (item) {
										$( ".wp-editor-area#content" )
									.contents()
									.find( ".wpgc-possessive" )
									.css( "background", "inherit" );
										$( "#content_ifr, #excerpt_ifr" )
									.contents()
									.find( "#tinymce" )
									.contents()
									.find( ".wpgc-possessive" )
									.css( "background", "inherit" );
								}
							);
						}
						spelling_highlight.forEach(
							function (item) {
								$( ".wp-editor-area#content" )
								.contents()
								.find( ".wpgc-spelling" )
								.css( "background", "#FFC0C0" );
								$( "#content_ifr, #excerpt_ifr" )
								.contents()
								.find( "#tinymce" )
								.contents()
								.find( ".wpgc-spelling" )
								.css( "background", "#FFC0C0" );
							}
						);
						current_highlight = "spelling";
						allow_save        = "false";
					}
					wpgc_set_listeners();
				}
			);

			$( ".wpgc-grammar-highlight" ).click(
				function () {
					if (current_highlight == "grammar") {
						complex_highlight.forEach(
							function (item) {
								$( ".wp-editor-area#content" )
								.contents()
								.find( ".wpgc-complex" )
								.css( "background", "inherit" );
								$( "#content_ifr, #excerpt_ifr" )
								.contents()
								.find( "#tinymce" )
								.contents()
								.find( ".wpgc-complex" )
								.css( "background", "inherit" );
							}
						);

						contractions_highlight.forEach(
							function (item) {
									$( ".wp-editor-area#content" )
								.contents()
								.find( ".wpgc-contraction" )
								.css( "background", "inherit" );
									$( "#content_ifr, #excerpt_ifr" )
								.contents()
								.find( "#tinymce" )
								.contents()
								.find( ".wpgc-contraction" )
								.css( "background", "inherit" );
							}
						);

						grammar_highlight.forEach(
							function (item) {
									$( ".wp-editor-area#content" )
								.contents()
								.find( ".wpgc-grammar" )
								.css( "background", "inherit" );
									$( "#content_ifr, #excerpt_ifr" )
								.contents()
								.find( "#tinymce" )
								.contents()
								.find( ".wpgc-grammar" )
								.css( "background", "inherit" );
							}
						);

						hidden_highlight.forEach(
							function (item) {
									$( ".wp-editor-area#content" )
								.contents()
								.find( ".wpgc-hidden" )
								.css( "background", "inherit" );
									$( "#content_ifr, #excerpt_ifr" )
								.contents()
								.find( "#tinymce" )
								.contents()
								.find( ".wpgc-hidden" )
								.css( "background", "inherit" );
							}
						);

						passive_highlight.forEach(
							function (item) {
									$( ".wp-editor-area#content" )
								.contents()
								.find( ".wpgc-passive" )
								.css( "background", "inherit" );
									$( "#content_ifr, #excerpt_ifr" )
								.contents()
								.find( "#tinymce" )
								.contents()
								.find( ".wpgc-passive" )
								.css( "background", "inherit" );
							}
						);

						redundant_highlight.forEach(
							function (item) {
									$( ".wp-editor-area#content" )
								.contents()
								.find( ".wpgc-redundant" )
								.css( "background", "inherit" );
									$( "#content_ifr, #excerpt_ifr" )
								.contents()
								.find( "#tinymce" )
								.contents()
								.find( ".wpgc-redundant" )
								.css( "background", "inherit" );
							}
						);

						possessive_highlight.forEach(
							function (item) {
									$( ".wp-editor-area#content" )
								.contents()
								.find( ".wpgc-possessive" )
								.css( "background", "inherit" );
									$( "#content_ifr, #excerpt_ifr" )
								.contents()
								.find( "#tinymce" )
								.contents()
								.find( ".wpgc-possessive" )
								.css( "background", "inherit" );
							}
						);
						allow_save        = "true";
						current_highlight = "none";
					} else {
						if (current_highlight == "spelling") {
							spelling_highlight.forEach(
								function (item) {
									$( ".wp-editor-area#content" )
									.contents()
									.find( ".wpgc-spelling" )
									.css( "background", "inherit" );
									$( "#content_ifr, #excerpt_ifr" )
									.contents()
									.find( "#tinymce" )
									.contents()
									.find( ".wpgc-spelling" )
									.css( "background", "inherit" );
								}
							);
						}
						complex_highlight.forEach(
							function (item) {
								$( ".wp-editor-area#content" )
								.contents()
								.find( ".wpgc-complex" )
								.css( "background", "#a3c5ff" );
								$( "#content_ifr, #excerpt_ifr" )
								.contents()
								.find( "#tinymce" )
								.contents()
								.find( ".wpgc-complex" )
								.css( "background", "#a3c5ff" );
							}
						);

						contractions_highlight.forEach(
							function (item) {
								$( ".wp-editor-area#content" )
								.contents()
								.find( ".wpgc-contraction" )
								.css( "background", "#59c033" );
								$( "#content_ifr, #excerpt_ifr" )
								.contents()
								.find( "#tinymce" )
								.contents()
								.find( ".wpgc-contraction" )
								.css( "background", "#59c033" );
							}
						);

						grammar_highlight.forEach(
							function (item) {
								$( ".wp-editor-area#content" )
								.contents()
								.find( ".wpgc-grammar" )
								.css( "background", "#59c033" );
								$( "#content_ifr, #excerpt_ifr" )
								.contents()
								.find( "#tinymce" )
								.contents()
								.find( ".wpgc-grammar" )
								.css( "background", "#59c033" );
							}
						);

						hidden_highlight.forEach(
							function (item) {
								$( ".wp-editor-area#content" )
								.contents()
								.find( ".wpgc-hidden" )
								.css( "background", "#59c033" );
								$( "#content_ifr, #excerpt_ifr" )
								.contents()
								.find( "#tinymce" )
								.contents()
								.find( ".wpgc-hidden" )
								.css( "background", "#59c033" );
							}
						);

						passive_highlight.forEach(
							function (item) {
								$( ".wp-editor-area#content" )
								.contents()
								.find( ".wpgc-passive" )
								.css( "background", "#59c033" );
								$( "#content_ifr, #excerpt_ifr" )
								.contents()
								.find( "#tinymce" )
								.contents()
								.find( ".wpgc-passive" )
								.css( "background", "#59c033" );
							}
						);

						redundant_highlight.forEach(
							function (item) {
								$( ".wp-editor-area#content" )
								.contents()
								.find( ".wpgc-redundant" )
								.css( "background", "#59c033" );
								$( "#content_ifr, #excerpt_ifr" )
								.contents()
								.find( "#tinymce" )
								.contents()
								.find( ".wpgc-redundant" )
								.css( "background", "#59c033" );
							}
						);

						possessive_highlight.forEach(
							function (item) {
								$( ".wp-editor-area#content" )
								.contents()
								.find( ".wpgc-possessive" )
								.css( "background", "#59c033" );
								$( ".wp-editor-area#content" )
								.contents()
								.find( ".wpgc-possessive" )
								.css( "background", "#59c033" );
								$( "#content_ifr, #excerpt_ifr" )
								.contents()
								.find( "#tinymce" )
								.contents()
								.find( ".wpgc-possessive" )
								.css( "background", "#59c033" );
							}
						);
						allow_save        = "false";
						current_highlight = "grammar";
					}
				}
			);

			window.setTimeout(
				function () {
					var iframe = $( "#content_ifr, #excerpt_ifr" ).contents();

					wpgc_set_listeners();

					iframe.on(
						"change",
						function () {
							wpgc_set_listeners();
						}
					);

					$( ".wpgc-complex-highlight" ).click(
						function () {
							wpgcx_clear_results();
							complex_highlight.forEach(
								function (item) {
									$( ".wp-editor-area#content" ).html(
										$( ".wp-editor-area#content" )
										.html()
										.replace(
											" " + item + " ",
											" &lt;span class='hiddenSpellError wpgc-complex' style='background: #59c033;'&gt;" +
											item +
											"&lt;/span&gt;",
										),
									);
									$( "#content_ifr, #excerpt_ifr" )
									.contents()
									.find( "#tinymce" )
									.html(
										$( "#content_ifr, #excerpt_ifr" )
										.contents()
										.find( "#tinymce" )
										.html()
										.replace(
											" " + item + " ",
											'<span class="hiddenSpellError wpgc-complex" style="background: #a3c5ff;" data-mce-style="background: #a3c5ff;">' +
											item +
											"</span> ",
										),
									);
								}
							);
							wpgc_set_listeners();
						}
					);

					$( ".wpgc-contraction-highlight" ).click(
						function () {
							wpgcx_clear_results();
							contractions_highlight.forEach(
								function (item) {
									$( ".wp-editor-area#content" ).html(
										$( ".wp-editor-area#content" )
										.html()
										.replace(
											" " + item + " ",
											" &lt;span class='hiddenSpellError wpgc-contraction' style='background: #59c033;'&gt;" +
											item +
											"&lt;/span&gt;",
										),
									);
									$( "#content_ifr, #excerpt_ifr" )
									.contents()
									.find( "#tinymce" )
									.html(
										$( "#content_ifr, #excerpt_ifr" )
										.contents()
										.find( "#tinymce" )
										.html()
										.replace(
											" " + item + " ",
											'<span class="hiddenSpellError wpgc-contraction" style="background: #59c033;" data-mce-style="background: #59c033;">' +
											item +
											"</span> ",
										),
									);
								}
							);
							wpgc_set_listeners();
						}
					);

					$( ".wpgc-grammar-highlight" ).click(
						function () {
							wpgcx_clear_results();
							grammar_highlight.forEach(
								function (item) {
									$( ".wp-editor-area#content" ).html(
										$( ".wp-editor-area#content" )
										.html()
										.replace(
											" " + item + " ",
											" &lt;span class='hiddenSpellError wpgc-grammar' style='background: #59c033;'&gt;" +
											item +
											"&lt;/span&gt;",
										),
									);
									$( "#content_ifr, #excerpt_ifr" )
									.contents()
									.find( "#tinymce" )
									.html(
										$( "#content_ifr, #excerpt_ifr" )
										.contents()
										.find( "#tinymce" )
										.html()
										.replace(
											" " + item + " ",
											'<span class="hiddenSpellError wpgc-grammar" style="background: #59c033;" data-mce-style="background: #59c033;">' +
											item +
											"</span> ",
										),
									);
								}
							);
							wpgc_set_listeners();
						}
					);

					$( ".wpgc-hidden-highlight" ).click(
						function () {
							wpgcx_clear_results();
							hidden_highlight.forEach(
								function (item) {
									$( ".wp-editor-area#content" ).html(
										$( ".wp-editor-area#content" )
										.html()
										.replace(
											" " + item + " ",
											" &lt;span class='hiddenSpellError wpgc-hidden' style='background: #59c033;'&gt;" +
											item +
											"&lt;/span&gt; ",
										),
									);
									$( "#content_ifr, #excerpt_ifr" )
									.contents()
									.find( "#tinymce" )
									.html(
										$( "#content_ifr, #excerpt_ifr" )
										.contents()
										.find( "#tinymce" )
										.html()
										.replace(
											" " + item + " ",
											' <span class="hiddenSpellError wpgc-hidden" style="background: #59c033;" data-mce-style="background: #59c033;">' +
											item +
											"</span> ",
										),
									);
								}
							);
							wpgc_set_listeners();
						}
					);

					$( ".wpgc-passive-highlight" ).click(
						function () {
							wpgcx_clear_results();
							passive_highlight.forEach(
								function (item) {
									$( ".wp-editor-area#content" ).html(
										$( ".wp-editor-area#content" )
										.html()
										.replace(
											" " + item + " ",
											" &lt;span class='hiddenSpellError wpgc-passive' style='background: #59c033;'&gt;" +
											item +
											"&lt;/span&gt;",
										),
									);
									$( "#content_ifr, #excerpt_ifr" )
									.contents()
									.find( "#tinymce" )
									.html(
										$( "#content_ifr, #excerpt_ifr" )
										.contents()
										.find( "#tinymce" )
										.html()
										.replace(
											" " + item + " ",
											'<span class="hiddenSpellError wpgc-passive" style="background: #59c033;" data-mce-style="background: #59c033;">' +
											item +
											"</span> ",
										),
									);
								}
							);
							wpgc_set_listeners();
						}
					);

					$( ".wpgc-redundant-highlight" ).click(
						function () {
							wpgcx_clear_results();
							redundant_highlight.forEach(
								function (item) {
									$( ".wp-editor-area#content" ).html(
										$( ".wp-editor-area#content" )
										.html()
										.replace(
											" " + item + " ",
											" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt; " +
											item +
											"&lt;/span&gt;",
										),
									);
									$( "#content_ifr, #excerpt_ifr" )
									.contents()
									.find( "#tinymce" )
									.html(
										$( "#content_ifr, #excerpt_ifr" )
										.contents()
										.find( "#tinymce" )
										.html()
										.replace(
											" " + item + " ",
											' <span class="hiddenSpellError wpgc-redundant" style="background: #59c033;" data-mce-style="background: #59c033;">' +
											item +
											"</span> ",
										),
									);
								}
							);
							wpgc_set_listeners();
						}
					);

					$( ".wpgc-possessive-highlight" ).click(
						function () {
							wpgcx_clear_results();
							possessive_highlight.forEach(
								function (item) {
									$( ".wp-editor-area#content" ).html(
										$( ".wp-editor-area#content" )
										.html()
										.replace(
											" " + item + " ",
											" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" +
											item +
											"&lt;/span&gt;",
										),
									);
									$( "#content_ifr, #excerpt_ifr" )
									.contents()
									.find( "#tinymce" )
									.html(
										$( "#content_ifr, #excerpt_ifr" )
										.contents()
										.find( "#tinymce" )
										.html()
										.replace(
											" " + item + " ",
											'<span class="hiddenSpellError wpgc-possessive" style="background: #59c033;" data-mce-style="background: #59c033;">' +
											item +
											"</span> ",
										),
									);
								}
							);
							wpgc_set_listeners();
						}
					);

					$( "#content_ifr, #excerpt_ifr" )
					.contents()
					.find( "#tinymce" )
					.click(
						function (e) {
							if ( ! $( e.target ).closest( ".hiddenSpellError" ).length) {
								$( ".wpgc-dialog" ).css( "display", "none" );
							}
						}
					);
				},
				3000
			);
		}
	);

	$( document ).click(
		function (e) {
			if ( ! $( e.target ).closest( ".wpgc-dialog" ).length) {
				$( ".wpgc-dialog" ).css( "display", "none" );
			}
		}
	);
})( jQuery );
