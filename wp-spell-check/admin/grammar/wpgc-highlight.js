jQuery( document ).ready(
	function ($) {
		var g                      =
		typeof wpgcGrammarFramework !== "undefined" ? wpgcGrammarFramework : {};
		var spelling_highlight     = g.spellingHighlight || [];
		var complex_highlight      = g.complexHighlight || [];
		var contractions_highlight = g.contractionsHighlight || [];
		var grammar_highlight      = g.grammarHighlight || [];
		var hidden_highlight       = g.hiddenHighlight || [];
		var passive_highlight      = g.passiveHighlight || [];
		var possessive_highlight   = g.possessiveHighlight || [];
		var redundant_highlight    = g.redundantHighlight || [];
		var duplicate_highlight    = [];
		var suggestions            = g.suggestions || [];
		var spellcheck             = g.spellcheck || false;

		var allow_save        = "false";
		var current_highlight = "grammar";
		var html_to_add       = "";
		var word_to_edit;
		var suggest_split;

		if (spellcheck == true) {
			current_highlight = "spelling";
			spelling_highlight.forEach(
				function (item) {
					jQuery( ".wp-editor-area" ).html(
						jQuery( ".wp-editor-area" )
						.html()
						.replace(
							" " + item + " ",
							"<span class='hiddenSpellError wpgc-spelling' style='border-bottom: 2px solid #dd1414;'>" +
							item +
							"</span>",
						),
					);
				}
			);
		} else {
			complex_highlight.forEach(
				function (item) {
					jQuery( ".wp-editor-area" ).html(
						jQuery( ".wp-editor-area" )
						.html()
						.replace(
							" " + item + " ",
							" <span class='hiddenSpellError wpgc-complex' style='background: #1F65DC;'>" +
							item +
							"</span> ",
						),
					);
				}
			);

			contractions_highlight.forEach(
				function (item) {
					jQuery( ".wp-editor-area" ).html(
						jQuery( ".wp-editor-area" )
						.html()
						.replace(
							" " + item + " ",
							" <span class='hiddenSpellError wpgc-contraction' style='background: #59c033;'>" +
							item +
							"</span> ",
						),
					);
				}
			);

			grammar_highlight.forEach(
				function (item) {
					jQuery( ".wp-editor-area" ).html(
						jQuery( ".wp-editor-area" )
						.html()
						.replace(
							" " + item + " ",
							" <span class='hiddenSpellError wpgc-grammar' style='background: #59c033;'>" +
							item +
							"</span> ",
						),
					);
				}
			);

			hidden_highlight.forEach(
				function (item) {
					jQuery( ".wp-editor-area" ).html(
						jQuery( ".wp-editor-area" )
						.html()
						.replace(
							" " + item + " ",
							" <span class='hiddenSpellError wpgc-hidden' style='background: #59c033;'>" +
							item +
							"</span> ",
						),
					);
				}
			);

			passive_highlight.forEach(
				function (item) {
					jQuery( ".wp-editor-area" ).html(
						jQuery( ".wp-editor-area" )
						.html()
						.replace(
							" " + item + " ",
							" <span class='hiddenSpellError wpgc-passive' style='background: #59c033;'>" +
							item +
							"</span> ",
						),
					);
				}
			);

			redundant_highlight.forEach(
				function (item) {
					jQuery( ".wp-editor-area" ).html(
						jQuery( ".wp-editor-area" )
						.html()
						.replace(
							" " + item + " ",
							" <span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'>" +
							item +
							"</span> ",
						),
					);
				}
			);

			possessive_highlight.forEach(
				function (item) {
					jQuery( ".wp-editor-area" ).html(
						jQuery( ".wp-editor-area" )
						.html()
						.replace(
							" " + item + " ",
							" <span class='hiddenSpellError wpgc-possessive' style='background: #59c033;'>" +
							item +
							"</span> ",
						),
					);
				}
			);

			duplicate_highlight.forEach(
				function (item) {
					jQuery( ".wp-editor-area" ).html(
						jQuery( ".wp-editor-area" )
						.html()
						.replace(
							" " + item + " ",
							" <span class='hiddenSpellError wpgc-duplicate' style='background: #59c033;'>" +
							item +
							"</span> ",
						),
					);
				}
			);
		}

		jQuery( "#publishing-action .button" ).click(
			function (e) {
				if (allow_save != "true") {
					e.preventDefault();

					spelling_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-complex' style='border-bottom: 2px solid #dd1414;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
							jQuery( "#content_ifr" )
							.contents()
							.find( "#tinymce" )
							.html(
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html()
								.replace(
									' <span class="hiddenSpellError wpgc-complex" style="border-bottom: 2px solid #59c033;" data-mce-style="border-bottom: 2px solid #dd1414;">' +
									item +
									"</span> ",
									" " + item + " ",
								),
							);
						}
					);

					complex_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-complex' style='background: #a3c5ff;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-contraction' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-grammar' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-hidden' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-passive' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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

					allow_save = "true";

					jQuery( "#publishing-action .button" ).click();
				}
			}
		);
		jQuery( "#content-html" ).click(
			function (e) {
				jQuery( ".wp-editor-area" ).html(
					jQuery( ".wp-editor-area" )
					.html()
					.replace(
						/<span[^>]*class=[^>]*hiddenSpellError[^>]*>([\s\S]*?)<\/span>/gi,
						"$1",
					),
				);
				jQuery( "#content_ifr" )
				.contents()
				.find( "#tinymce" )
				.contents()
				.find( ".hiddenSpellError" )
				.contents()
				.unwrap();
				/*spelling_highlight.forEach(function (item) {
				jQuery(".wp-editor-area").html(jQuery(".wp-editor-area").html().replace(" &lt;span class='hiddenSpellError wpgc-complex' style='border-bottom: 2px solid #dd1414;'&gt;" + item + "&lt;/span&gt; ", " " + item + " "));
				jQuery("#content_ifr").contents().find("#tinymce").html(jQuery("#content_ifr").contents().find("#tinymce").html().replace(' <span class="hiddenSpellError wpgc-complex" style="border-bottom: 2px solid #59c033;" data-mce-style="border-bottom: 2px solid #dd1414;">' + item + '</span> ', " " + item + " "));
				});

				complex_highlight.forEach(function (item) {
				jQuery(".wp-editor-area").html(jQuery(".wp-editor-area").html().replace(" &lt;span class='hiddenSpellError wpgc-complex' style='background: #a3c5ff;'&gt;" + item + "&lt;/span&gt; ", " " + item + " "));
				jQuery("#content_ifr").contents().find("#tinymce").html(jQuery("#content_ifr").contents().find("#tinymce").html().replace(' <span class="hiddenSpellError wpgc-complex" style="background: #a3c5ff;" data-mce-style="background: #a3c5ff;">' + item + '</span> ', " " + item + " "));
				});

				contractions_highlight.forEach(function (item) {
				jQuery(".wp-editor-area").html(jQuery(".wp-editor-area").html().replace(" &lt;span class='hiddenSpellError wpgc-contraction' style='background: #59c033;'&gt;" + item + "&lt;/span&gt; ", " " + item + " "));
				jQuery("#content_ifr").contents().find("#tinymce").html(jQuery("#content_ifr").contents().find("#tinymce").html().replace(' <span class="hiddenSpellError wpgc-contraction" style="background: #59c033;" data-mce-style="background: #59c033;">' + item + '</span> ', " " + item + " "));
				});

				grammar_highlight.forEach(function (item) {
				jQuery(".wp-editor-area").html(jQuery(".wp-editor-area").html().replace(" &lt;span class='hiddenSpellError wpgc-grammar' style='background: #59c033;'&gt;" + item + "&lt;/span&gt; ", " " + item + " "));
				jQuery("#content_ifr").contents().find("#tinymce").html(jQuery("#content_ifr").contents().find("#tinymce").html().replace(' <span class="hiddenSpellError wpgc-grammar" style="background: #59c033;" data-mce-style="background: #59c033;">' + item + '</span> ', " " + item + " "));
				});

				hidden_highlight.forEach(function (item) {
				jQuery(".wp-editor-area").html(jQuery(".wp-editor-area").html().replace(" &lt;span class='hiddenSpellError wpgc-hidden' style='background: #59c033;'&gt;" + item + "&lt;/span&gt; ", " " + item + " "));
				jQuery("#content_ifr").contents().find("#tinymce").html(jQuery("#content_ifr").contents().find("#tinymce").html().replace(' <span class="hiddenSpellError wpgc-hidden" style="background: #59c033;" data-mce-style="background: #59c033;">' + item + '</span> ', " " + item + " "));
				});

				passive_highlight.forEach(function (item) {
				jQuery(".wp-editor-area").html(jQuery(".wp-editor-area").html().replace(" &lt;span class='hiddenSpellError wpgc-passive' style='background: #59c033;'&gt;" + item + "&lt;/span&gt; ", " " + item + " "));
				jQuery("#content_ifr").contents().find("#tinymce").html(jQuery("#content_ifr").contents().find("#tinymce").html().replace(' <span class="hiddenSpellError wpgc-passive" style="background: #59c033;" data-mce-style="background: #59c033;">' + item + '</span> ', " " + item + " "));
				});

				redundant_highlight.forEach(function (item) {
				jQuery(".wp-editor-area").html(jQuery(".wp-editor-area").html().replace(" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" + item + "&lt;/span&gt; ", " " + item + " "));
				jQuery("#content_ifr").contents().find("#tinymce").html(jQuery("#content_ifr").contents().find("#tinymce").html().replace(' <span class="hiddenSpellError wpgc-redundant" style="background: #59c033;" data-mce-style="background: #59c033;">' + item + '</span> ', " " + item + " "));
				});

				possessive_highlight.forEach(function (item) {
				jQuery(".wp-editor-area").html(jQuery(".wp-editor-area").html().replace(" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" + item + "&lt;/span&gt; ", " " + item + " "));
				jQuery("#content_ifr").contents().find("#tinymce").html(jQuery("#content_ifr").contents().find("#tinymce").html().replace(' <span class="hiddenSpellError wpgc-possessive" style="background: #59c033;" data-mce-style="background: #59c033;">' + item + '</span> ', " " + item + " "));
				});

				duplicate_highlight.forEach(function (item) {
				jQuery(".wp-editor-area").html(jQuery(".wp-editor-area").html().replace(" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" + item + "&lt;/span&gt; ", " " + item + " "));
				jQuery("#content_ifr").contents().find("#tinymce").html(jQuery("#content_ifr").contents().find("#tinymce").html().replace(' <span class="hiddenSpellError wpgc-duplicate" style="background: #59c033;" data-mce-style="background: #59c033;">' + item + '</span> ', " " + item + " "));
					});*/
				allow_save = "true";
			}
		);

		jQuery( "#content-tmce" ).click(
			function () {
				allow_save = "false";
			}
		);
		jQuery( ".wpgc-spelling-highlight" ).click(
			function () {
				if (current_highlight == "spelling") {
					spelling_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-spelling' style='border-bottom: 2px solid #dd1414;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
							jQuery( "#content_ifr" )
							.contents()
							.find( "#tinymce" )
							.html(
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html()
								.replace(
									' <span class="hiddenSpellError wpgc-spelling" style="border-bottom: 2px solid #dd1414;" data-mce-style="border-bottom: 2px solid #dd1414;">' +
									item +
									"</span> ",
									" " + item + " ",
								),
							);
						}
					);
					current_highlight = "none";
					allow_save        = true;
				} else {
					if (current_highlight == "grammar") {
						complex_highlight.forEach(
							function (item) {
								jQuery( ".wp-editor-area" ).html(
									jQuery( ".wp-editor-area" )
									.html()
									.replace(
										" &lt;span class='hiddenSpellError wpgc-complex' style='background: #59c033;'&gt;" +
										item +
										"&lt;/span&gt; ",
										" " + item + " ",
									),
								);
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
									.contents()
									.find( "#tinymce" )
									.html()
									.replace(
										' <span class="hiddenSpellError wpgc-complex" style="background: #1F65DC;" data-mce-style="background: #1F65DC;">' +
										item +
										"</span> ",
										" " + item + " ",
									),
								);
							}
						);

						contractions_highlight.forEach(
							function (item) {
									jQuery( ".wp-editor-area" ).html(
										jQuery( ".wp-editor-area" )
										.html()
										.replace(
											" &lt;span class='hiddenSpellError wpgc-contraction' style='background: #59c033;'&gt;" +
											item +
											"&lt;/span&gt; ",
											" " + item + " ",
										),
									);
									jQuery( "#content_ifr" )
									.contents()
									.find( "#tinymce" )
									.html(
										jQuery( "#content_ifr" )
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
									jQuery( ".wp-editor-area" ).html(
										jQuery( ".wp-editor-area" )
										.html()
										.replace(
											" &lt;span class='hiddenSpellError wpgc-grammar' style='background: #59c033;'&gt;" +
											item +
											"&lt;/span&gt; ",
											" " + item + " ",
										),
									);
									jQuery( "#content_ifr" )
									.contents()
									.find( "#tinymce" )
									.html(
										jQuery( "#content_ifr" )
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
									jQuery( ".wp-editor-area" ).html(
										jQuery( ".wp-editor-area" )
										.html()
										.replace(
											" &lt;span class='hiddenSpellError wpgc-hidden' style='background: #59c033;'&gt;" +
											item +
											"&lt;/span&gt; ",
											" " + item + " ",
										),
									);
									jQuery( "#content_ifr" )
									.contents()
									.find( "#tinymce" )
									.html(
										jQuery( "#content_ifr" )
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
									jQuery( ".wp-editor-area" ).html(
										jQuery( ".wp-editor-area" )
										.html()
										.replace(
											" &lt;span class='hiddenSpellError wpgc-passive' style='background: #59c033;'&gt;" +
											item +
											"&lt;/span&gt; ",
											" " + item + " ",
										),
									);
									jQuery( "#content_ifr" )
									.contents()
									.find( "#tinymce" )
									.html(
										jQuery( "#content_ifr" )
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
									jQuery( ".wp-editor-area" ).html(
										jQuery( ".wp-editor-area" )
										.html()
										.replace(
											" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" +
											item +
											"&lt;/span&gt; ",
											" " + item + " ",
										),
									);
									jQuery( "#content_ifr" )
									.contents()
									.find( "#tinymce" )
									.html(
										jQuery( "#content_ifr" )
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
									jQuery( ".wp-editor-area" ).html(
										jQuery( ".wp-editor-area" )
										.html()
										.replace(
											" &lt;span class='hiddenSpellError wpgc-possessive' style='background: #59c033;'&gt;" +
											item +
											"&lt;/span&gt; ",
											" " + item + " ",
										),
									);
									jQuery( "#content_ifr" )
									.contents()
									.find( "#tinymce" )
									.html(
										jQuery( "#content_ifr" )
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

						duplicate_highlight.forEach(
							function (item) {
									jQuery( ".wp-editor-area" ).html(
										jQuery( ".wp-editor-area" )
										.html()
										.replace(
											" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" +
											item +
											"&lt;/span&gt;",
											" " + item,
										),
									);
									jQuery( "#content_ifr" )
									.contents()
									.find( "#tinymce" )
									.html(
										jQuery( "#content_ifr" )
										.contents()
										.find( "#tinymce" )
										.html()
										.replace(
											' <span class="hiddenSpellError wpgc-duplicate" style="background: #59c033;" data-mce-style="background: #59c033;">' +
											item +
											"</span> ",
											" " + item,
										),
									);
							}
						);
					}
					spelling_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" " + item + " ",
									" &lt;span class='hiddenSpellError wpgc-spelling' style='border-bottom: 2px solid #dd1414;'&gt;" +
									item +
									"&lt;/span&gt;",
								),
							);
							jQuery( "#content_ifr" )
							.contents()
							.find( "#tinymce" )
							.html(
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html()
								.replace(
									" " + item + " ",
									'<span class="hiddenSpellError wpgc-spelling" style="border-bottom: 2px solid #dd1414;" data-mce-style="border-bottom: 2px solid #dd1414;">' +
									item +
									"</span> ",
								),
							);
						}
					);
					current_highlight = "spelling";
					allow_save        = "false";
				}
			}
		);

		jQuery( ".wpgc-grammar-highlight" ).click(
			function () {
				if (current_highlight == "grammar") {
					complex_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-complex' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
							jQuery( "#content_ifr" )
							.contents()
							.find( "#tinymce" )
							.html(
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html()
								.replace(
									' <span class="hiddenSpellError wpgc-complex" style="background: #1F65DC;" data-mce-style="background: #1F65DC;">' +
									item +
									"</span> ",
									" " + item + " ",
								),
							);
						}
					);

					contractions_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-contraction' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
							jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-grammar' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
							jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-hidden' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
							jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-passive' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
							jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
							jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-possessive' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
							jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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

					duplicate_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt;",
									" " + item,
								),
							);
							jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
									.contents()
									.find( "#tinymce" )
									.html()
									.replace(
										' <span class="hiddenSpellError wpgc-duplicate" style="background: #59c033;" data-mce-style="background: #59c033;">' +
										item +
										"</span> ",
										" " + item,
									),
								);
						}
					);
					contractions_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-contraction' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
							jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-grammar' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
							jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-hidden' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
							jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-passive' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
							jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
							jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-possessive' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt; ",
									" " + item + " ",
								),
							);
							jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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

					duplicate_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt;",
									" " + item,
								),
							);
							jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
									.contents()
									.find( "#tinymce" )
									.html()
									.replace(
										' <span class="hiddenSpellError wpgc-duplicate" style="background: #59c033;" data-mce-style="background: #59c033;">' +
										item +
										"</span> ",
										" " + item,
									),
								);
						}
					);
					allow_save        = "true";
					current_highlight = "none";
				} else {
					if (current_highlight == "spelling") {
						spelling_highlight.forEach(
							function (item) {
								jQuery( ".wp-editor-area" ).html(
									jQuery( ".wp-editor-area" )
									.html()
									.replace(
										" &lt;span class='hiddenSpellError wpgc-spelling' style='border-bottom: 2px solid #dd1414;'&gt;" +
										item +
										"&lt;/span&gt;",
									),
								);
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
									.contents()
									.find( "#tinymce" )
									.html()
									.replace(
										' <span class="hiddenSpellError wpgc-spelling" style="border-bottom: 2px solid #dd1414;" data-mce-style="border-bottom: 2px solid #dd1414;">' +
										item +
										"</span> ",
										" " + item + " ",
									),
								);
							}
						);
					}
					complex_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" " + item + " ",
									" &lt;span class='hiddenSpellError wpgc-complex' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt;",
								),
							);
							jQuery( "#content_ifr" )
							.contents()
							.find( "#tinymce" )
							.html(
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html()
								.replace(
									" " + item + " ",
									'<span class="hiddenSpellError wpgc-complex" style="background: #1F65DC;" data-mce-style="background: #1F65DC;">' +
									item +
									"</span> ",
								),
							);
						}
					);
					complex_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" " + item + " ",
									" &lt;span class='hiddenSpellError wpgc-complex' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt;",
								),
							);
							jQuery( "#content_ifr" )
							.contents()
							.find( "#tinymce" )
							.html(
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html()
								.replace(
									" " + item + " ",
									'<span class="hiddenSpellError wpgc-complex" style="background: #1F65DC;" data-mce-style="background: #1F65DC;">' +
									item +
									"</span> ",
								),
							);
						}
					);

					contractions_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" " + item + " ",
									" &lt;span class='hiddenSpellError wpgc-contraction' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt;",
								),
							);
							jQuery( "#content_ifr" )
							.contents()
							.find( "#tinymce" )
							.html(
								jQuery( "#content_ifr" )
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
					contractions_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" " + item + " ",
									" &lt;span class='hiddenSpellError wpgc-contraction' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt;",
								),
							);
							jQuery( "#content_ifr" )
							.contents()
							.find( "#tinymce" )
							.html(
								jQuery( "#content_ifr" )
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

					grammar_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" " + item + " ",
									" &lt;span class='hiddenSpellError wpgc-grammar' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt;",
								),
							);
							jQuery( "#content_ifr" )
							.contents()
							.find( "#tinymce" )
							.html(
								jQuery( "#content_ifr" )
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
					grammar_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" " + item + " ",
									" &lt;span class='hiddenSpellError wpgc-grammar' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt;",
								),
							);
							jQuery( "#content_ifr" )
							.contents()
							.find( "#tinymce" )
							.html(
								jQuery( "#content_ifr" )
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

					hidden_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" " + item + " ",
									" &lt;span class='hiddenSpellError wpgc-hidden' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt;",
								),
							);
							jQuery( "#content_ifr" )
							.contents()
							.find( "#tinymce" )
							.html(
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html()
								.replace(
									" " + item + " ",
									'<span class="hiddenSpellError wpgc-hidden" style="background: #59c033;" data-mce-style="background: #59c033;">' +
									item +
									"</span> ",
								),
							);
						}
					);
					hidden_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" " + item + " ",
									" &lt;span class='hiddenSpellError wpgc-hidden' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt;",
								),
							);
							jQuery( "#content_ifr" )
							.contents()
							.find( "#tinymce" )
							.html(
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html()
								.replace(
									" " + item + " ",
									'<span class="hiddenSpellError wpgc-hidden" style="background: #59c033;" data-mce-style="background: #59c033;">' +
									item +
									"</span> ",
								),
							);
						}
					);

					passive_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" " + item + " ",
									" &lt;span class='hiddenSpellError wpgc-passive' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt;",
								),
							);
							jQuery( "#content_ifr" )
							.contents()
							.find( "#tinymce" )
							.html(
								jQuery( "#content_ifr" )
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
					passive_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" " + item + " ",
									" &lt;span class='hiddenSpellError wpgc-passive' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt;",
								),
							);
							jQuery( "#content_ifr" )
							.contents()
							.find( "#tinymce" )
							.html(
								jQuery( "#content_ifr" )
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

					redundant_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" " + item + " ",
									" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt;",
								),
							);
							jQuery( "#content_ifr" )
							.contents()
							.find( "#tinymce" )
							.html(
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html()
								.replace(
									" " + item + " ",
									'<span class="hiddenSpellError wpgc-redundant" style="background: #59c033;" data-mce-style="background: #59c033;">' +
									item +
									"</span> ",
								),
							);
						}
					);
					redundant_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" " + item + " ",
									" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt;",
								),
							);
							jQuery( "#content_ifr" )
							.contents()
							.find( "#tinymce" )
							.html(
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html()
								.replace(
									" " + item + " ",
									'<span class="hiddenSpellError wpgc-redundant" style="background: #59c033;" data-mce-style="background: #59c033;">' +
									item +
									"</span> ",
								),
							);
						}
					);

					possessive_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" " + item + " ",
									" &lt;span class='hiddenSpellError wpgc-possessive' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt;",
								),
							);
							jQuery( "#content_ifr" )
							.contents()
							.find( "#tinymce" )
							.html(
								jQuery( "#content_ifr" )
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
					possessive_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" " + item + " ",
									" &lt;span class='hiddenSpellError wpgc-possessive' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt;",
								),
							);
							jQuery( "#content_ifr" )
							.contents()
							.find( "#tinymce" )
							.html(
								jQuery( "#content_ifr" )
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

					duplicate_highlight.forEach(
						function (item) {
							jQuery( ".wp-editor-area" ).html(
								jQuery( ".wp-editor-area" )
								.html()
								.replace(
									" " + item,
									" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" +
									item +
									"&lt;/span&gt;",
								),
							);
							jQuery( "#content_ifr" )
							.contents()
							.find( "#tinymce" )
							.html(
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html()
								.replace(
									" " + item + " ",
									' <span class="hiddenSpellError wpgc-duplicate" style="background: #59c033;" data-mce-style="background: #59c033;">' +
									item +
									"</span> ",
								),
							);
						}
					);
					allow_save        = "false";
					current_highlight = "grammar";
				}
			}
		);

		window.setTimeout(
			function () {
				var iframe = jQuery( "#content_ifr" ).contents();

				iframe.find( ".hiddenSpellError" ).click(
					function (e) {
						word_to_edit    = jQuery( this );
						word_check      = jQuery( this ).html();
						var error_class = jQuery( this ).attr( "class" );
						error_class     = error_class.split( " " )[1].split( "-" )[1];
						if (error_class == "academic") {
							error_class = "Jargon Language";
						}
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
						html_to_add = "<li style='color: grey;'>" + error_class + "</li><hr>";

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

						jQuery( ".wpgc-dialog ul" ).html( html_to_add );
						jQuery( ".wpgc-dialog" ).css( "top", e.pageY + 200 );
						jQuery( ".wpgc-dialog" ).css( "left", e.pageX );
						jQuery( ".wpgc-dialog" ).css( "display", "block" );
						jQuery( ".wpgc-dialog-close" ).click(
							function () {
								// var scroll_save = jQuery(window).scrollTop();
								word_to_edit.css( "background", "none" );
								jQuery( ".wpgc-dialog" ).css( "display", "none" );
								// jQuery(window).scrollTop(scroll_save);
							}
						);

						jQuery( ".wpgc-suggestion" ).click(
							function () {
								if (jQuery( this ).html() == "DELETE") {
									word_to_edit.html( "" );
								} else {
									word_to_edit.html( jQuery( this ).html() );
								}
								word_to_edit.css( "background", "none" );
								jQuery( ".wpgc-dialog" ).css( "display", "none" );
							}
						);

						jQuery( ".wpgc-dialog a" ).hover(
							function () {
									jQuery( this ).css( "background-color", "grey" );
							},
							function () {
								jQuery( this ).css( "background-color", "lightgrey" );
							},
						);
					}
				);

				iframe.on(
					"change",
					function () {
						iframe.find( ".hiddenSpellError" ).click(
							function (e) {
								word_to_edit    = jQuery( this );
								word_check      = jQuery( this ).html();
								var error_class = jQuery( this ).attr( "class" );
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
								html_to_add = "<li style='color: grey;'>Grammar</li><hr>";

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

								jQuery( ".wpgc-dialog ul" ).html( html_to_add );
								jQuery( ".wpgc-dialog" ).css( "top", e.pageY + 200 );
								jQuery( ".wpgc-dialog" ).css( "left", e.pageX );
								jQuery( ".wpgc-dialog" ).css( "display", "block" );
								jQuery( ".wpgc-dialog-close" ).click(
									function () {
										// var scroll_save = jQuery(window).scrollTop();
										word_to_edit.css( "background", "none" );
										jQuery( ".wpgc-dialog" ).css( "display", "none" );
										// jQuery(window).scrollTop(scroll_save);
									}
								);

								jQuery( ".wpgc-suggestion" ).click(
									function () {
										if (jQuery( this ).html() == "DELETE") {
											word_to_edit.html( "" );
										} else {
											word_to_edit.html( jQuery( this ).html() );
										}
										word_to_edit.css( "background", "none" );
										jQuery( ".wpgc-dialog" ).css( "display", "none" );
									}
								);

								jQuery( ".wpgc-dialog a" ).hover(
									function () {
										jQuery( this ).css( "background-color", "grey" );
									},
									function () {
										jQuery( this ).css( "background-color", "lightgrey" );
									},
								);
							}
						);
					}
				);

				jQuery( ".wpgc-complex-highlight" ).click(
					function () {
						wpgc_clear_results();
						complex_highlight.forEach(
							function (item) {
								jQuery( ".wp-editor-area" ).html(
									jQuery( ".wp-editor-area" )
									.html()
									.replace(
										" " + item + " ",
										" &lt;span class='hiddenSpellError wpgc-complex' style='background: #59c033;'&gt;" +
										item +
										"&lt;/span&gt;",
									),
								);
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
									.contents()
									.find( "#tinymce" )
									.html()
									.replace(
										" " + item + " ",
										'<span class="hiddenSpellError wpgc-complex" style="background: #1F65DC;" data-mce-style="background: #1F65DC;">' +
										item +
										"</span> ",
									),
								);
							}
						);
						wpgc_set_listeners();
					}
				);
				jQuery( ".wpgc-contraction-highlight" ).click(
					function () {
						wpgc_clear_results();
						contractions_highlight.forEach(
							function (item) {
								jQuery( ".wp-editor-area" ).html(
									jQuery( ".wp-editor-area" )
									.html()
									.replace(
										" " + item + " ",
										" &lt;span class='hiddenSpellError wpgc-contraction' style='background: #59c033;'&gt;" +
										item +
										"&lt;/span&gt;",
									),
								);
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
				jQuery( ".wpgc-grammar-highlight" ).click(
					function () {
						wpgc_clear_results();
						grammar_highlight.forEach(
							function (item) {
								jQuery( ".wp-editor-area" ).html(
									jQuery( ".wp-editor-area" )
									.html()
									.replace(
										" " + item + " ",
										" &lt;span class='hiddenSpellError wpgc-grammar' style='background: #59c033;'&gt;" +
										item +
										"&lt;/span&gt;",
									),
								);
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
				jQuery( ".wpgc-hidden-highlight" ).click(
					function () {
						wpgc_clear_results();
						hidden_highlight.forEach(
							function (item) {
								jQuery( ".wp-editor-area" ).html(
									jQuery( ".wp-editor-area" )
									.html()
									.replace(
										" " + item + " ",
										" &lt;span class='hiddenSpellError wpgc-hidden' style='background: #59c033;'&gt;" +
										item +
										"&lt;/span&gt; ",
									),
								);
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
				jQuery( ".wpgc-passive-highlight" ).click(
					function () {
						wpgc_clear_results();
						passive_highlight.forEach(
							function (item) {
								jQuery( ".wp-editor-area" ).html(
									jQuery( ".wp-editor-area" )
									.html()
									.replace(
										" " + item + " ",
										" &lt;span class='hiddenSpellError wpgc-passive' style='background: #59c033;'&gt;" +
										item +
										"&lt;/span&gt;",
									),
								);
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
				jQuery( ".wpgc-redundant-highlight" ).click(
					function () {
						wpgc_clear_results();
						redundant_highlight.forEach(
							function (item) {
								jQuery( ".wp-editor-area" ).html(
									jQuery( ".wp-editor-area" )
									.html()
									.replace(
										" " + item + " ",
										" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt; " +
										item +
										"&lt;/span&gt;",
									),
								);
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
				jQuery( ".wpgc-possessive-highlight" ).click(
					function () {
						wpgc_clear_results();
						possessive_highlight.forEach(
							function (item) {
								jQuery( ".wp-editor-area" ).html(
									jQuery( ".wp-editor-area" )
									.html()
									.replace(
										" " + item + " ",
										" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" +
										item +
										"&lt;/span&gt;",
									),
								);
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
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
				jQuery( ".wpgc-duplicate-highlight" ).click(
					function () {
						wpgc_clear_results();
						duplicate_highlight.forEach(
							function (item) {
								jQuery( ".wp-editor-area" ).html(
									jQuery( ".wp-editor-area" )
									.html()
									.replace(
										" " + item + " ",
										" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" +
										item +
										"&lt;/span&gt;",
									),
								);
								jQuery( "#content_ifr" )
								.contents()
								.find( "#tinymce" )
								.html(
									jQuery( "#content_ifr" )
									.contents()
									.find( "#tinymce" )
									.html()
									.replace(
										" " + item + " ",
										' <span class="hiddenSpellError wpgc-duplicate" style="background: #59c033;" data-mce-style="background: #59c033;">' +
										item +
										"</span> ",
									),
								);
							}
						);
						wpgc_set_listeners();
					}
				);
			},
			1000
		);
	}
);

var iframe_mouseup = jQuery( "#content_ifr" ).contents();
jQuery( document ).click(
	function (e) {
		if ( ! jQuery( e.target ).closest( ".wpgc-dialog" ).length) {
			jQuery( ".wpgc-dialog" ).css( "display", "none" );
		}
	}
);

function wpgc_clear_results() {
	var g                      =
	typeof wpgcGrammarFramework !== "undefined" ? wpgcGrammarFramework : {};
	var complex_highlight      = g.complexExpressionHighlight || [];
	var contractions_highlight = g.contractionsHighlightArray || [];
	var grammar_highlight      = g.grammarHighlightArray || [];
	var hidden_highlight       = g.hiddenVerbHighlight || [];
	var passive_highlight      = g.passiveVoiceHighlight || [];
	var possessive_highlight   = g.possessiveEndingHighlight || [];
	var redundant_highlight    = g.redundantExpressionHighlight || [];
	// var dup_spaces = jQuery(".wp-editor-area").html().match(/<span class='hiddenSpellError wpgc-duplicate' style='border-bottom: 2px solid #59c033;'>(((&amp;nbsp;) (&amp;nbsp;))+|\s\s+)<\/span>/g);
	complex_highlight.forEach(
		function (item) {
			jQuery( ".wp-editor-area" ).html(
				jQuery( ".wp-editor-area" )
				.html()
				.replace(
					" &lt;span class='hiddenSpellError wpgc-complex' style='background: #59c033;'&gt;" +
					item +
					"&lt;/span&gt; ",
					" " + item + " ",
				),
			);
			jQuery( "#content_ifr" )
			.contents()
			.find( "#tinymce" )
			.html(
				jQuery( "#content_ifr" )
				.contents()
				.find( "#tinymce" )
				.html()
				.replace(
					' <span class="hiddenSpellError wpgc-complex" style="background: #1F65DC;" data-mce-style="background: #1F65DC;">' +
					item +
					"</span> ",
					" " + item + " ",
				),
			);
		}
	);

	contractions_highlight.forEach(
		function (item) {
			jQuery( ".wp-editor-area" ).html(
				jQuery( ".wp-editor-area" )
				.html()
				.replace(
					" &lt;span class='hiddenSpellError wpgc-contraction' style='background: #59c033;'&gt;" +
					item +
					"&lt;/span&gt; ",
					" " + item + " ",
				),
			);
			jQuery( "#content_ifr" )
			.contents()
			.find( "#tinymce" )
			.html(
				jQuery( "#content_ifr" )
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
			jQuery( ".wp-editor-area" ).html(
				jQuery( ".wp-editor-area" )
				.html()
				.replace(
					" &lt;span class='hiddenSpellError wpgc-grammar' style='background: #59c033;'&gt;" +
					item +
					"&lt;/span&gt; ",
					" " + item + " ",
				),
			);
			jQuery( "#content_ifr" )
			.contents()
			.find( "#tinymce" )
			.html(
				jQuery( "#content_ifr" )
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
			jQuery( ".wp-editor-area" ).html(
				jQuery( ".wp-editor-area" )
				.html()
				.replace(
					" &lt;span class='hiddenSpellError wpgc-hidden' style='background: #59c033;'&gt;" +
					item +
					"&lt;/span&gt; ",
					" " + item + " ",
				),
			);
			jQuery( "#content_ifr" )
			.contents()
			.find( "#tinymce" )
			.html(
				jQuery( "#content_ifr" )
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
			jQuery( ".wp-editor-area" ).html(
				jQuery( ".wp-editor-area" )
				.html()
				.replace(
					" &lt;span class='hiddenSpellError wpgc-passive' style='background: #59c033;'&gt;" +
					item +
					"&lt;/span&gt; ",
					" " + item + " ",
				),
			);
			jQuery( "#content_ifr" )
			.contents()
			.find( "#tinymce" )
			.html(
				jQuery( "#content_ifr" )
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
			jQuery( ".wp-editor-area" ).html(
				jQuery( ".wp-editor-area" )
				.html()
				.replace(
					" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" +
					item +
					"&lt;/span&gt; ",
					" " + item + " ",
				),
			);
			jQuery( "#content_ifr" )
			.contents()
			.find( "#tinymce" )
			.html(
				jQuery( "#content_ifr" )
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
			jQuery( ".wp-editor-area" ).html(
				jQuery( ".wp-editor-area" )
				.html()
				.replace(
					" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" +
					item +
					"&lt;/span&gt; ",
					" " + item + " ",
				),
			);
			jQuery( "#content_ifr" )
			.contents()
			.find( "#tinymce" )
			.html(
				jQuery( "#content_ifr" )
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

	duplicate_highlight.forEach(
		function (item) {
			jQuery( ".wp-editor-area" ).html(
				jQuery( ".wp-editor-area" )
				.html()
				.replace(
					" &lt;span class='hiddenSpellError wpgc-redundant' style='background: #59c033;'&gt;" +
					item +
					"&lt;/span&gt; ",
					" " + item + " ",
				),
			);
			jQuery( "#content_ifr" )
			.contents()
			.find( "#tinymce" )
			.html(
				jQuery( "#content_ifr" )
				.contents()
				.find( "#tinymce" )
				.html()
				.replace(
					' <span class="hiddenSpellError wpgc-duplicate" style="background: #59c033;" data-mce-style="background: #59c033;">' +
					item +
					"</span> ",
					" " + item + " ",
				),
			);
		}
	);
}

function wpgc_set_listeners() {
	var iframe      = jQuery( "#content_ifr" ).contents();
	var g           =
	typeof wpgcGrammarFramework !== "undefined" ? wpgcGrammarFramework : {};
	var suggestions = g.suggestions || [];

	iframe.find( ".hiddenSpellError" ).click(
		function (e) {
			word_to_edit    = jQuery( this );
			word_check      = jQuery( this ).html();
			var error_class = jQuery( this ).attr( "class" );
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
			html_to_add = "<li style='color: grey;'>" + error_class + "</li><hr>";

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

			jQuery( ".wpgc-dialog ul" ).html( html_to_add );
			jQuery( ".wpgc-dialog" ).css( "top", e.pageY + 200 );
			jQuery( ".wpgc-dialog" ).css( "left", e.pageX );
			jQuery( ".wpgc-dialog" ).css( "display", "block" );
			jQuery( ".wpgc-dialog-close" ).click(
				function () {
					// var scroll_save = jQuery(window).scrollTop();
					word_to_edit.css( "background", "none" );
					jQuery( ".wpgc-dialog" ).css( "display", "none" );
					// jQuery(window).scrollTop(scroll_save);
				}
			);

			jQuery( ".wpgc-suggestion" ).click(
				function () {
					if (jQuery( this ).html() == "DELETE") {
						word_to_edit.html( "" );
					} else {
						word_to_edit.html( jQuery( this ).html() );
					}
					word_to_edit.css( "background", "none" );
					jQuery( ".wpgc-dialog" ).css( "display", "none" );
				}
			);

			jQuery( ".wpgc-dialog a" ).hover(
				function () {
					jQuery( this ).css( "background-color", "grey" );
				},
				function () {
					jQuery( this ).css( "background-color", "lightgrey" );
				},
			);
		}
	);
}
