function wpscx_getSearchParameters() {
      var prmstr = window.location.search.substr(1);
      return prmstr != null && prmstr != "" ? wpscx_transformToAssocArray(prmstr) : {};
}

function wpscx_transformToAssocArray( prmstr ) {
    var params = {};
    var prmarr = prmstr.split("&");
    for ( var i = 0; i < prmarr.length; i++) {
        var tmparr = prmarr[i].split("=");
        params[tmparr[0]] = tmparr[1];
    }
    return params;
}

jQuery(document).ready(function() {
	//Set up onclick events	
	jQuery('.wpsc-ignore-checkbox').click(function( event ) {
		var parent = jQuery(this).closest('.wpsc-row');
		var parent_id = parent.attr('id').split('-')[2];
                console.log('#wpsc-row-' + parent_id + ' .wpsc-add-checkbox');
                jQuery('.wpsc-add-checkbox[value=' + parent_id + ']').prop('checked', false);
		var suggest_id = jQuery('#wpsc-edit-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);
		var suggest_id = jQuery('#wpsc-suggest-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);
                var suggest_id = jQuery('#wpsc-edit-seo-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);
		
		if (jQuery(this).closest('.Ignore').hasClass('wpsc-highlight-row')) {
			jQuery(this).closest('.Ignore').removeClass('wpsc-highlight-row');
			jQuery('#wpsc-row-' + parent_id + ' span:not(.Ignore)').removeClass('wpsc-unselected-row');
			jQuery(this).closest('.row-actions').css("left","-9999px");
		} else { 
			jQuery(this).closest('.Ignore').addClass('wpsc-highlight-row');
			jQuery('#wpsc-row-' + parent_id + ' span:not(.Ignore)').removeClass('wpsc-highlight-row');
			jQuery('#wpsc-row-' + parent_id + ' span:not(.Ignore)').addClass('wpsc-unselected-row');
			jQuery('#wpsc-row-' + parent_id + ' .Ignore').removeClass('wpsc-unselected-row');
			jQuery(this).closest('.row-actions').css("left","0px");
		}
	});

	jQuery('.wpsc-add-checkbox').click(function( event ) {
		var parent = jQuery(this).closest('.wpsc-row');
		var parent_id = parent.attr('id').split('-')[2];
		jQuery('.wpsc-ignore-checkbox[value=' + parent_id + ']').prop('checked', false);
		var suggest_id = jQuery('#wpsc-edit-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);
		var suggest_id = jQuery('#wpsc-suggest-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);	

		if (jQuery(this).closest('.Dictionary').hasClass('wpsc-highlight-row')) {
			jQuery(this).closest('.Dictionary').removeClass('wpsc-highlight-row');
			jQuery('#wpsc-row-' + parent_id + ' span:not(.Dictionary)').removeClass('wpsc-unselected-row');
			jQuery('#wpsc-row-' + parent_id + ' .Dictionary').removeClass('wpsc-highlight-row');	
			jQuery(this).closest('.row-actions').css("left","-9999px");
		} else { 
			jQuery(this).closest('.Dictionary').addClass('wpsc-highlight-row');
			jQuery('#wpsc-row-' + parent_id + ' span:not(.Dictionary)').removeClass('wpsc-highlight-row');	
			jQuery('#wpsc-row-' + parent_id + ' span:not(.Dictionary)').addClass('wpsc-unselected-row');
			jQuery('#wpsc-row-' + parent_id + ' .Dictionary').removeClass('wpsc-unselected-row');
			jQuery(this).closest('.row-actions').css("left","0px");
		}
	});

	jQuery('.wpsc-dictionary-edit-button').click(function( event ) {
		event.preventDefault();
		var parent = jQuery(this).closest('.wpsc-row');
		var old_word = jQuery(this).attr('id').split('-')[2]; 

		var parent_id = parent.attr('id').split('-')[2];
		
		wpscx_show_editor(parent_id, old_word);
                
                jQuery('.wpsc-cancel-button').on('click',function() {
                    var parent = jQuery(this).closest('tr');
                    wpscx_hide_editor(parent);
                    var parent_id = jQuery(this).closest('tr').attr('id').split('-')[3];
                    jQuery('#wpsc-row-' + parent_id + ' .Edit').removeClass('wpsc-highlight-row');
                    jQuery('#wpsc-row-' + parent_id + ' span').removeClass('wpsc-unselected-row');
                });
                
                jQuery('.wpsc-update-button').click(function() {
                    var old_word_id = jQuery(this).closest('tr').attr('id').split('-')[3]; 
                    var updated_word = jQuery('#wpsc-edit-row-' + old_word_id + ' .wpsc-edit-field').val(); //Get the new word
                    var old_word = jQuery('#wpsc-row-' + old_word_id).find('.wpsc-dictionary-edit-button').attr('id').split('-')[2]; //Get the old word

                    //alert("?page=wp-spellcheck-dictionary.php&old_word=" + old_word + "&new_word=" + updated_word); //This is for testing
                    var page_params = wpscx_getSearchParameters();
                    var sorting = '';
                    if (page_params['orderby'] != 'undefined') sorting += '&orderby=' + page_params['orderby'];
                    if (page_params['order'] != 'undefined') sorting += '&order=' + page_params['order'];

                    old_word = old_word.replace('(','%28');
                    updated_word = updated_word.replace('(','%28');

                    var page_num = wpscx_GetURLParameter('paged');
                    window.location.href = "?page=wp-spellcheck-dictionary.php&old_word=" + old_word + "&new_word=" + updated_word + "&paged=" + page_num + sorting; //Refresh the page, passing the word to be updated via PHP
                });
	});

	jQuery('.wpsc-edit-button').click(function( event ) {
		event.preventDefault();
		var parent = jQuery(this).closest('.wpsc-row');
		var old_word = jQuery(this).attr('id').split('-')[2]; 
		var old_word_id = jQuery(this).closest('tr').attr('id').split('-')[2]; 
		var page_name = jQuery('#wpsc-row-' + old_word_id).find('#wpsc-page-name').attr('page'); //Get the page ID
		var page_type = jQuery('#wpsc-row-' + old_word_id).find('.wpsc-edit-button').attr('page_type'); //Get the page type
		

		var parent_id = parent.attr('id').split('-')[2];
		var suggest_id = jQuery('#wpsc-suggest-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);
		var suggest_id = jQuery('#wpsc-edit-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);
		if (old_word == 'Empty Field') {
			wpscx_show_editor(parent_id, '', old_word_id, page_name, page_type);
		} else {
			wpscx_show_editor(parent_id, old_word, old_word_id, page_name, page_type);
		}
		jQuery('[type=checkbox][value=' + parent_id + ']').prop('checked', false);

		jQuery(this).closest('.Edit').addClass('wpsc-highlight-row');
		jQuery('#wpsc-row-' + parent_id + ' span:not(.Edit)').removeClass('wpsc-highlight-row');
		jQuery('#wpsc-row-' + parent_id + ' span:not(.Edit)').addClass('wpsc-unselected-row');
			jQuery('#wpsc-row-' + parent_id + ' .Edit').removeClass('wpsc-unselected-row');
			
			jQuery('.wpsc-cancel-button').click(function() {
				
				var parent = jQuery(this).closest('tr');
				wpscx_hide_editor(parent);
				var parent_id = jQuery(this).closest('tr').attr('id').split('-')[3];
				jQuery('#wpsc-row-' + parent_id + ' .Edit').removeClass('wpsc-highlight-row');
				jQuery('#wpsc-row-' + parent_id + ' span').removeClass('wpsc-unselected-row');
			});
                        
                        jQuery(document).ready(function() {
                            var mouseover_visible_edit = false;
                            jQuery('.wpsc-mouseover-pro-feature-3').mouseenter(function() {
                            jQuery('.wpsc-mouseover-text-pro-feature-3').css('z-index','100');
                            jQuery('.wpsc-mouseover-text-pro-feature-3').animate({opacity: 1.0}, 400, function() { mouseover_visible_edit = true; });
                            }).mouseleave(function() {
                            jQuery('.wpsc-mouseover-text-pro-feature-3').css('z-index','-100');
                            jQuery('.wpsc-mouseover-text-pro-feature-3').animate({opacity: 0}, 400);
                            mouseover_visible_edit = false;
                            });
                            jQuery('.wpsc-mouseover-pro-feature-3').click(function() {
                            if (!mouseover_visible_edit) {
                            jQuery('.wpsc-mouseover-text-pro-feature-3').stop();
                            jQuery('.wpsc-mouseover-text-pro-feature-3').css('z-index','100');
                            jQuery('.wpsc-mouseover-text-pro-feature-3').animate({opacity: 1.0}, 400, function() { mouseover_visible_edit = true; });
                            } else {
                            jQuery('.wpsc-mouseover-text-pro-feature-3').css('z-index','-100');
                            jQuery('.wpsc-mouseover-text-pro-featuret-3').animate({opacity: 0}, 400);
                            mouseover_visible_edit = false;
                            }
                            });
                            });
                        });
                        
        jQuery('.wpsc-edit-seo-button').click(function( event ) {
		event.preventDefault();
		var parent = jQuery(this).closest('.wpsc-row');
		var old_word = jQuery(this).attr('id').split('-')[2]; 
		var old_word_id = jQuery(this).closest('tr').attr('id').split('-')[2]; 
		var page_name = jQuery('#wpsc-row-' + old_word_id).find('#wpsc-page-name').attr('page'); //Get the page ID
		var page_type = jQuery('#wpsc-row-' + old_word_id).find('.wpsc-edit-seo-button').attr('page_type'); //Get the page type
                var page_title = jQuery('#wpsc-row-' + old_word_id).find('#wpsc-page-name').attr('title'); //Get the page Title
		

		var parent_id = parent.attr('id').split('-')[2];
		var suggest_id = jQuery('#wpsc-suggest-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);
		var suggest_id = jQuery('#wpsc-edit-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);
                var suggest_id = jQuery('#wpsc-edit-seo-row-' + parent_id).closest('tr');
                wpscx_hide_editor(suggest_id);
                
		if (old_word == 'Empty Field') {
			wpscx_show_editor_seo(parent_id, '', old_word_id, page_name, page_type, page_title);
		} else {
			wpscx_show_editor_seo(parent_id, old_word, old_word_id, page_name, page_type, page_title);
		}
		jQuery('[type=checkbox][value=' + parent_id + ']').prop('checked', false);

		jQuery(this).closest('.Edit').addClass('wpsc-highlight-row');
		jQuery('#wpsc-row-' + parent_id + ' span:not(.Edit)').removeClass('wpsc-highlight-row');
		jQuery('#wpsc-row-' + parent_id + ' span:not(.Edit)').addClass('wpsc-unselected-row');
			jQuery('#wpsc-row-' + parent_id + ' .Edit').removeClass('wpsc-unselected-row');
			
			jQuery('.wpsc-cancel-button').click(function() {
				
				var parent = jQuery(this).closest('tr');
				wpscx_hide_editor(parent);
				var parent_id = jQuery(this).closest('tr').attr('id').split('-')[3];
				jQuery('#wpsc-row-' + parent_id + ' .Edit').removeClass('wpsc-highlight-row');
				jQuery('#wpsc-row-' + parent_id + ' span').removeClass('wpsc-unselected-row');
			});
                        
                        wpscx_seoListener();
                        
                        jQuery(document).ready(function() {
                            var mouseover_visible_edit = false;
                            jQuery('.wpsc-mouseover-pro-feature-3').mouseenter(function() {
                            jQuery('.wpsc-mouseover-text-pro-feature-3').css('z-index','100');
                            jQuery('.wpsc-mouseover-text-pro-feature-3').animate({opacity: 1.0}, 400, function() { mouseover_visible_edit = true; });
                            }).mouseleave(function() {
                            jQuery('.wpsc-mouseover-text-pro-feature-3').css('z-index','-100');
                            jQuery('.wpsc-mouseover-text-pro-feature-3').animate({opacity: 0}, 400);
                            mouseover_visible_edit = false;
                            });
                            jQuery('.wpsc-mouseover-pro-feature-3').click(function() {
                            if (!mouseover_visible_edit) {
                            jQuery('.wpsc-mouseover-text-pro-feature-3').stop();
                            jQuery('.wpsc-mouseover-text-pro-feature-3').css('z-index','100');
                            jQuery('.wpsc-mouseover-text-pro-feature-3').animate({opacity: 1.0}, 400, function() { mouseover_visible_edit = true; });
                            } else {
                            jQuery('.wpsc-mouseover-text-pro-feature-3').css('z-index','-100');
                            jQuery('.wpsc-mouseover-text-pro-featuret-3').animate({opacity: 0}, 400);
                            mouseover_visible_edit = false;
                            }
                            });
                            });
                        });

	jQuery('.wpsc-suggest-button').click(function( event ) {
		event.preventDefault();
		var parent = jQuery(this).closest('.wpsc-row');
		var old_word_id = jQuery(this).closest('tr').attr('id').split('-')[2]; 
		var old_word = jQuery('#wpsc-row-' + old_word_id).find('.wpsc-edit-button').attr('id').split('-')[2]; 
		var page_name = jQuery('#wpsc-row-' + old_word_id).find('#wpsc-page-name').attr('page'); //Get the page ID
		var page_type = jQuery('#wpsc-row-' + old_word_id).find('.wpsc-edit-button').attr('page_type'); //Get the page type

		//alert (page_name);

		var parent_id = parent.attr('id').split('-')[2];
		
		var suggest_id = jQuery('#wpsc-edit-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);
		var suggest_id = jQuery('#wpsc-suggest-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);
		
		wpscx_show_suggestions(parent_id, old_word, old_word_id, page_name, page_type);
		jQuery('[type=checkbox][value=' + parent_id + ']').prop('checked', false);
		var suggest_id = jQuery('#wpsc-edit-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);

		jQuery(this).closest('.Suggested').addClass('wpsc-highlight-row');
		jQuery('#wpsc-row-' + parent_id + ' span:not(.Suggested)').removeClass('wpsc-highlight-row');
		jQuery('#wpsc-row-' + parent_id + ' span:not(.Suggested)').addClass('wpsc-unselected-row');
		jQuery('#wpsc-row-' + parent_id + ' .Suggested').removeClass('wpsc-unselected-row');
                
                jQuery(document).ready(function() {
                    var mouseover_visible_edit = false;
                    jQuery('.wpsc-mouseover-pro-feature-2').mouseenter(function() {
                        jQuery('.wpsc-mouseover-text-pro-feature-2').css('z-index','100');
                        jQuery('.wpsc-mouseover-text-pro-feature-2').animate({opacity: 1.0}, 400, function() { mouseover_visible_edit = true; });
                    }).mouseleave(function() {
                        var isHoveredPopup = jQuery('.wpsc-mouseover-text-pro-feature-2').filter(function() {
                            return jQuery(this).is(":hover");
                        });
                        var isHoveredParent = jQuery(this).parent().filter(function() {
                            return jQuery(this).is(":hover");
                        });
                        
                        if (!isHoveredPopup && !isHoveredParent) {
                            jQuery('.wpsc-mouseover-text-pro-feature-2').css('z-index','-100');
                            jQuery('.wpsc-mouseover-text-pro-feature-2').animate({opacity: 0}, 400);
                            mouseover_visible_edit = false;
                        }
                    });
                    jQuery('.wpsc-mouseover-pro-feature-2').click(function() {
                        if (!mouseover_visible_edit) {
                            jQuery('.wpsc-mouseover-text-pro-feature-2').stop();
                            jQuery('.wpsc-mouseover-text-pro-feature-2').css('z-index','100');
                            jQuery('.wpsc-mouseover-text-pro-feature-2').animate({opacity: 1.0}, 400, function() { mouseover_visible_edit = true; });
                        } else {
                            jQuery('.wpsc-mouseover-text-pro-feature-2').css('z-index','-100');
                            jQuery('.wpsc-mouseover-text-pro-featuret-2').animate({opacity: 0}, 400);
                            mouseover_visible_edit = false;
                        }
                    });
                    
                    jQuery('.wpsc-mouseover-pro-feature-2').parent().mouseleave(function() {
                        //console.log("Parent Container Mouseleave Triggered");
                        var isHoveredPopup = jQuery('.wpsc-mouseover-text-pro-feature-2:hover').length > 0;
                        var isHoveredButton = jQuery('.wpsc-mouseover-pro-feature-2:hover').length > 0;
                        //console.log("Popup: " + isHoveredPopup + " | Button: " + isHoveredButton);

                        if (!isHoveredPopup && !isHoveredButton) {
                            jQuery('.wpsc-mouseover-text-pro-feature-2').css('z-index','-100');
                            jQuery('.wpsc-mouseover-text-pro-feature-2').animate({opacity: 0}, 400);
                            mouseover_visible_edit = false;
                        }
                    });
                });
	});
        
        jQuery('.wpsc-cancel-suggest-button').on('click',function(event) {
                event.preventDefault();
		var parent = jQuery(this).closest('tr');
		wpscx_hide_editor(parent);
		var parent_id = jQuery(this).closest('tr').attr('id').split('-')[3];
		jQuery('#wpsc-row-' + parent_id + ' .Suggested').removeClass('wpsc-highlight-row');
		jQuery('#wpsc-row-' + parent_id + ' span').removeClass('wpsc-unselected-row');
	});
        
	jQuery('.wpsc-cancel-button').on('click',function(event) {
                event.preventDefault();
		
		var parent = jQuery(this).closest('tr');
		wpscx_hide_editor(parent);
		var parent_id = jQuery(this).closest('tr').attr('id').split('-')[3];
		jQuery('#wpsc-row-' + parent_id + ' .Edit').removeClass('wpsc-highlight-row');
		jQuery('#wpsc-row-' + parent_id + ' span').removeClass('wpsc-unselected-row');
	});

	jQuery('.wpsc-update-button').click(function() {
		var old_word_id = jQuery(this).closest('tr').attr('id').split('-')[3]; 
		var updated_word = jQuery('#wpsc-edit-row-' + old_word_id + ' .wpsc-edit-field').val(); //Get the new word
		var old_word = jQuery('#wpsc-row-' + old_word_id).find('.wpsc-dictionary-edit-button').attr('id').split('-')[2]; //Get the old word

		//alert("?page=wp-spellcheck-dictionary.php&old_word=" + old_word + "&new_word=" + updated_word); //This is for testing
		var page_params = wpscx_getSearchParameters();
		var sorting = '';
		if (page_params['orderby'] != 'undefined') sorting += '&orderby=' + page_params['orderby'];
		if (page_params['order'] != 'undefined') sorting += '&order=' + page_params['order'];

		old_word = old_word.replace('(','%28');
		updated_word = updated_word.replace('(','%28');

		var page_num = wpscx_GetURLParameter('paged');
		window.location.href = "?page=wp-spellcheck-dictionary.php&old_word=" + old_word + "&new_word=" + updated_word + "&paged=" + page_num + sorting; //Refresh the page, passing the word to be updated via PHP
	});

jQuery('#wpsc-edit-update-button-hidden').click(function(event) {
		event.preventDefault();
		var old_word_ids = '';
		jQuery('[name="edit_old_word_id[]"], [name="suggest_old_word_id[]"]').each(function() {
			if (jQuery(this).attr('value').length !== 0) {
				old_word_ids += "old_word_ids[]=" + jQuery(this).attr('value').replace('(','%28') + "&";
			}
		});
		
		var old_words = '';
		jQuery('[name="edit_old_word[]"], [name="suggest_old_word[]"]').each(function() {
			if (jQuery(this).attr('value').length !== 0) {
				old_words += "old_words[]=" + jQuery(this).attr('value').replace('(','%28').replace('&','%amp;').replace('+','%pls;').replace('#','%hash;') + "&";
			}
		});

		var page_names = '';
		jQuery('[name="edit_page_name[]"], [name="suggest_page_name[]"]').each(function() {
			if (jQuery(this).attr('value').length !== 0) {
				page_names += "page_names[]=" + jQuery(this).attr('value').replace('(','%28') + "&";
			}
		});
		
		var page_types = '';
		jQuery('[name="edit_page_type[]"], [name="suggest_page_type[]"]').each(function() {
			if (jQuery(this).attr('value').length !== 0) {
				page_types += "page_types[]=" + jQuery(this).attr('value').replace('(','%28') + "&";
			}
		});

		var new_words = '';
		jQuery('[name="word_update[]"], [name="suggested_word[]"]').each(function() {
			if (jQuery(this).val() != undefined) {
				new_words += "new_words[]=" + jQuery(this).val().replace('(','%28').replace('&','%amp;').replace('+','%pls;').replace('#','%hash;') + "&";
			}
		});

		var ignore_words = "";
		var add_words = "";
		var mass_edit = "";
		jQuery('[name="ignore-word[]"]').each(function() {
			if (jQuery(this).prop('checked')) {
				ignore_words += "ignore_word[]=" + jQuery(this).attr('value') + "&";
			}
		});
		jQuery('[name="add-word[]"]').each(function() {
			if (jQuery(this).prop('checked')) {
				add_words += "add_word[]=" + jQuery(this).attr('value') + "&";
			}
		});
		jQuery('.wpsc-mass-edit-chk').each(function() {
			if (jQuery(this).is(':checked')) mass_edit += "mass_edit[]=" + jQuery(this).attr('value') + "&";
		});

		var page_params = wpscx_getSearchParameters();
		var sorting = '';
		var tab_select = '';
		if (jQuery(this).hasClass('empty-tab')) tab_select = '&wpsc-scan-tab=empty';
		//if (page_params['orderby'] != 'undefined') sorting += '&orderby=' + page_params['orderby'];
		//if (page_params['order'] != 'undefined') sorting += '&order=' + page_params['order'];

		var page_num = wpscx_GetURLParameter('paged');
		var url = "?page=wp-spellcheck.php&" + old_word_ids + old_words + page_names + page_types + new_words + ignore_words + add_words + mass_edit + "&paged=" + page_num + sorting + tab_select;
		window.location.href = encodeURI(url); //Refresh the page, passing the word to be updated via PHP
	});

	jQuery('.wpsc-update-suggest-button').click(function() {
				var old_word_ids = '';
		jQuery('[name="edit_old_word_id[]"]').each(function() {
			if (jQuery(this).attr('value') != undefined) {
				old_word_ids += "old_word_ids[]=" + jQuery(this).attr('value').replace('(','%28') + "&";
			}
		});
		jQuery('[name="suggest_old_word_id[]"]').each(function() {
			if (jQuery(this).attr('value') != undefined) {
				old_word_ids += "old_word_ids[]=" + jQuery(this).attr('value').replace('(','%28') + "&";
			}
		});
		var old_words = '';
		jQuery('[name="edit_old_word[]"]').each(function() {
			if (jQuery(this).attr('value') != undefined) {
				old_words += "old_words[]=" + jQuery(this).attr('value').replace('(','%28') + "&";
			}
		});
		jQuery('[name="suggest_old_word[]"]').each(function() {
			if (jQuery(this).attr('value') != undefined) {
				old_words += "old_words[]=" + jQuery(this).attr('value').replace('(','%28') + "&";
			}
		});
		var page_names = '';
		jQuery('[name="edit_page_name[]"]').each(function() {
			if (jQuery(this).attr('value') != undefined) {
				page_names += "page_names[]=" + jQuery(this).attr('value').replace('(','%28') + "&";
			}
		});
		jQuery('[name="suggest_page_name[]"]').each(function() {
			if (jQuery(this).attr('value') != undefined) {
				page_names += "page_names[]=" + jQuery(this).attr('value').replace('(','%28') + "&";
			}
		});
		var page_types = '';
		jQuery('[name="edit_page_type[]"]').each(function() {
			if (jQuery(this).attr('value') != undefined) {
				page_types += "page_types[]=" + jQuery(this).attr('value').replace('(','%28') + "&";
			}
		});
		jQuery('[name="suggest_page_type[]"]').each(function() {
			if (jQuery(this).attr('value') != undefined) {
				page_types += "page_types[]=" + jQuery(this).attr('value').replace('(','%28') + "&";
			}
		});
		var new_words = '';
		jQuery('[name="word_update[]"]').each(function() {
			if (jQuery(this).attr('value') != undefined) {
				new_words += "new_words[]=" + jQuery(this).attr('value').replace('(','%28') + "&";
			}
		});
		jQuery('[name="suggested_word[]"]').each(function() {
			if (jQuery(this).attr('value') != undefined) {
				new_words += "new_words[]=" + jQuery(this).attr('value').replace('(','%28') + "&";
			}
		});
		var ignore_words = "";
		var add_words = "";
		jQuery('[name="ignore-word[]"]').each(function() {
			if (jQuery(this).prop('checked')) {
				ignore_words += "ignore_word[]=" + jQuery(this).attr('value') + "&";
			}
		});
		jQuery('[name="add-word[]"]').each(function() {
			if (jQuery(this).prop('checked')) {
				add_words += "add_word[]=" + jQuery(this).attr('value') + "&";
			}
		});

		var page_params = wpscx_getSearchParameters();
		var sorting = '';
		if (page_params['orderby'] != 'undefined') sorting += '&orderby=' + page_params['orderby'];
		if (page_params['order'] != 'undefined') sorting += '&order=' + page_params['order'];

		var page_num = wpscx_GetURLParameter('paged');
		var url = "?page=wp-spellcheck.php&" + old_word_ids + old_words + page_names + page_types + new_words + ignore_words + add_words + "&paged=" + page_num + sorting;
		window.location.href = encodeURI(url); //Refresh the page, passing the word to be updated via PHP
	});
});

function wpscx_connect_listeners_empty() {
    
}

function wpscx_connect_listeners() {
    jQuery('.wpsc-add-checkbox').click(function( event ) {
		var parent = jQuery(this).closest('.wpsc-row');
		var parent_id = parent.attr('id').split('-')[2];
		jQuery('.wpsc-ignore-checkbox[value=' + parent_id + ']').prop('checked', false);
		var suggest_id = jQuery('#wpsc-edit-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);
		var suggest_id = jQuery('#wpsc-suggest-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);	

		if (jQuery(this).closest('.Dictionary').hasClass('wpsc-highlight-row')) {
			jQuery(this).closest('.Dictionary').removeClass('wpsc-highlight-row');
			jQuery('#wpsc-row-' + parent_id + ' span:not(.Dictionary)').removeClass('wpsc-unselected-row');
			jQuery('#wpsc-row-' + parent_id + ' .Dictionary').removeClass('wpsc-highlight-row');	
			jQuery(this).closest('.row-actions').css("left","-9999px");
		} else { 
			jQuery(this).closest('.Dictionary').addClass('wpsc-highlight-row');
			jQuery('#wpsc-row-' + parent_id + ' span:not(.Dictionary)').removeClass('wpsc-highlight-row');	
			jQuery('#wpsc-row-' + parent_id + ' span:not(.Dictionary)').addClass('wpsc-unselected-row');
			jQuery('#wpsc-row-' + parent_id + ' .Dictionary').removeClass('wpsc-unselected-row');
			jQuery(this).closest('.row-actions').css("left","0px");
		}
	});

	jQuery('.wpsc-dictionary-edit-button').click(function( event ) {
		event.preventDefault();
		var parent = jQuery(this).closest('.wpsc-row');
		var old_word = jQuery(this).attr('id').split('-')[2]; 

		var parent_id = parent.attr('id').split('-')[2];
		
		wpscx_show_editor(parent_id, old_word);
                
                jQuery('.wpsc-cancel-button').on('click',function() {
                    var parent = jQuery(this).closest('tr');
                    wpscx_hide_editor(parent);
                    var parent_id = jQuery(this).closest('tr').attr('id').split('-')[3];
                    jQuery('#wpsc-row-' + parent_id + ' .Edit').removeClass('wpsc-highlight-row');
                    jQuery('#wpsc-row-' + parent_id + ' span').removeClass('wpsc-unselected-row');
                });
                
                jQuery('.wpsc-update-button').click(function() {
                    var old_word_id = jQuery(this).closest('tr').attr('id').split('-')[3]; 
                    var updated_word = jQuery('#wpsc-edit-row-' + old_word_id + ' .wpsc-edit-field').val(); //Get the new word
                    var old_word = jQuery('#wpsc-row-' + old_word_id).find('.wpsc-dictionary-edit-button').attr('id').split('-')[2]; //Get the old word

                    //alert("?page=wp-spellcheck-dictionary.php&old_word=" + old_word + "&new_word=" + updated_word); //This is for testing
                    var page_params = wpscx_getSearchParameters();
                    var sorting = '';
                    if (page_params['orderby'] != 'undefined') sorting += '&orderby=' + page_params['orderby'];
                    if (page_params['order'] != 'undefined') sorting += '&order=' + page_params['order'];

                    old_word = old_word.replace('(','%28');
                    updated_word = updated_word.replace('(','%28');

                    var page_num = wpscx_GetURLParameter('paged');
                    window.location.href = "?page=wp-spellcheck-dictionary.php&old_word=" + old_word + "&new_word=" + updated_word + "&paged=" + page_num + sorting; //Refresh the page, passing the word to be updated via PHP
                });
	});

	jQuery('.wpsc-edit-button').click(function( event ) {
		event.preventDefault();
		var parent = jQuery(this).closest('.wpsc-row');
		var old_word = jQuery(this).attr('id').split('-')[2]; 
		var old_word_id = jQuery(this).closest('tr').attr('id').split('-')[2]; 
		var page_name = jQuery('#wpsc-row-' + old_word_id).find('#wpsc-page-name').attr('page'); //Get the page ID
		var page_type = jQuery('#wpsc-row-' + old_word_id).find('.wpsc-edit-button').attr('page_type'); //Get the page type
		

		var parent_id = parent.attr('id').split('-')[2];
		var suggest_id = jQuery('#wpsc-suggest-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);
		var suggest_id = jQuery('#wpsc-edit-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);
		if (old_word == 'Empty Field') {
			wpscx_show_editor(parent_id, '', old_word_id, page_name, page_type);
		} else {
			wpscx_show_editor(parent_id, old_word, old_word_id, page_name, page_type);
		}
		jQuery('[type=checkbox][value=' + parent_id + ']').prop('checked', false);

		jQuery(this).closest('.Edit').addClass('wpsc-highlight-row');
		jQuery('#wpsc-row-' + parent_id + ' span:not(.Edit)').removeClass('wpsc-highlight-row');
		jQuery('#wpsc-row-' + parent_id + ' span:not(.Edit)').addClass('wpsc-unselected-row');
			jQuery('#wpsc-row-' + parent_id + ' .Edit').removeClass('wpsc-unselected-row');
			
			jQuery('.wpsc-cancel-button').click(function() {
				
				var parent = jQuery(this).closest('tr');
				wpscx_hide_editor(parent);
				var parent_id = jQuery(this).closest('tr').attr('id').split('-')[3];
				jQuery('#wpsc-row-' + parent_id + ' .Edit').removeClass('wpsc-highlight-row');
				jQuery('#wpsc-row-' + parent_id + ' span').removeClass('wpsc-unselected-row');
			});
                        
                        jQuery(document).ready(function() {
                            var mouseover_visible_edit = false;
                            jQuery('.wpsc-mouseover-pro-feature-3').mouseenter(function() {
                            jQuery('.wpsc-mouseover-text-pro-feature-3').css('z-index','100');
                            jQuery('.wpsc-mouseover-text-pro-feature-3').animate({opacity: 1.0}, 400, function() { mouseover_visible_edit = true; });
                            }).mouseleave(function() {
                            jQuery('.wpsc-mouseover-text-pro-feature-3').css('z-index','-100');
                            jQuery('.wpsc-mouseover-text-pro-feature-3').animate({opacity: 0}, 400);
                            mouseover_visible_edit = false;
                            });
                            jQuery('.wpsc-mouseover-pro-feature-3').click(function() {
                            if (!mouseover_visible_edit) {
                            jQuery('.wpsc-mouseover-text-pro-feature-3').stop();
                            jQuery('.wpsc-mouseover-text-pro-feature-3').css('z-index','100');
                            jQuery('.wpsc-mouseover-text-pro-feature-3').animate({opacity: 1.0}, 400, function() { mouseover_visible_edit = true; });
                            } else {
                            jQuery('.wpsc-mouseover-text-pro-feature-3').css('z-index','-100');
                            jQuery('.wpsc-mouseover-text-pro-featuret-3').animate({opacity: 0}, 400);
                            mouseover_visible_edit = false;
                            }
                            });
                            });
                        });
                        
                        
        jQuery('.wpsc-edit-seo-button').click(function( event ) {
		event.preventDefault();
		var parent = jQuery(this).closest('.wpsc-row');
		var old_word = jQuery(this).attr('id').split('-')[2]; 
		var old_word_id = jQuery(this).closest('tr').attr('id').split('-')[2]; 
		var page_name = jQuery('#wpsc-row-' + old_word_id).find('#wpsc-page-name').attr('page'); //Get the page ID
		var page_type = jQuery('#wpsc-row-' + old_word_id).find('.wpsc-edit-seo-button').attr('page_type'); //Get the page type
                var page_title = jQuery('#wpsc-row-' + old_word_id).find('#wpsc-page-name').attr('title'); //Get the page Title
		

		var parent_id = parent.attr('id').split('-')[2];
		var suggest_id = jQuery('#wpsc-suggest-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);
		var suggest_id = jQuery('#wpsc-edit-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);
		if (old_word == 'Empty Field') {
			wpscx_show_editor_seo(parent_id, '', old_word_id, page_name, page_type, page_title);
		} else {
			wpscx_show_editor_seo(parent_id, old_word, old_word_id, page_name, page_type, page_title);
		}
		jQuery('[type=checkbox][value=' + parent_id + ']').prop('checked', false);

		jQuery(this).closest('.Edit').addClass('wpsc-highlight-row');
		jQuery('#wpsc-row-' + parent_id + ' span:not(.Edit)').removeClass('wpsc-highlight-row');
		jQuery('#wpsc-row-' + parent_id + ' span:not(.Edit)').addClass('wpsc-unselected-row');
			jQuery('#wpsc-row-' + parent_id + ' .Edit').removeClass('wpsc-unselected-row');
			
			jQuery('.wpsc-cancel-button').click(function() {
				
				var parent = jQuery(this).closest('tr');
				wpscx_hide_editor(parent);
				var parent_id = jQuery(this).closest('tr').attr('id').split('-')[3];
				jQuery('#wpsc-row-' + parent_id + ' .Edit').removeClass('wpsc-highlight-row');
				jQuery('#wpsc-row-' + parent_id + ' span').removeClass('wpsc-unselected-row');
			});
                        
                        wpscx_seoListener();
                        
                        jQuery(document).ready(function() {
                            var mouseover_visible_edit = false;
                            jQuery('.wpsc-mouseover-pro-feature-3').mouseenter(function() {
                            jQuery('.wpsc-mouseover-text-pro-feature-3').css('z-index','100');
                            jQuery('.wpsc-mouseover-text-pro-feature-3').animate({opacity: 1.0}, 400, function() { mouseover_visible_edit = true; });
                            }).mouseleave(function() {
                            jQuery('.wpsc-mouseover-text-pro-feature-3').css('z-index','-100');
                            jQuery('.wpsc-mouseover-text-pro-feature-3').animate({opacity: 0}, 400);
                            mouseover_visible_edit = false;
                            });
                            jQuery('.wpsc-mouseover-pro-feature-3').click(function() {
                            if (!mouseover_visible_edit) {
                            jQuery('.wpsc-mouseover-text-pro-feature-3').stop();
                            jQuery('.wpsc-mouseover-text-pro-feature-3').css('z-index','100');
                            jQuery('.wpsc-mouseover-text-pro-feature-3').animate({opacity: 1.0}, 400, function() { mouseover_visible_edit = true; });
                            } else {
                            jQuery('.wpsc-mouseover-text-pro-feature-3').css('z-index','-100');
                            jQuery('.wpsc-mouseover-text-pro-featuret-3').animate({opacity: 0}, 400);
                            mouseover_visible_edit = false;
                            }
                            });
                            });
                        });

	jQuery('.wpsc-suggest-button').click(function( event ) {
		event.preventDefault();
		var parent = jQuery(this).closest('.wpsc-row');
		var old_word_id = jQuery(this).closest('tr').attr('id').split('-')[2]; 
		var old_word = jQuery('#wpsc-row-' + old_word_id).find('.wpsc-edit-button').attr('id').split('-')[2]; 
		var page_name = jQuery('#wpsc-row-' + old_word_id).find('#wpsc-page-name').attr('page'); //Get the page ID
		var page_type = jQuery('#wpsc-row-' + old_word_id).find('.wpsc-edit-button').attr('page_type'); //Get the page type

		//alert (page_name);

		var parent_id = parent.attr('id').split('-')[2];
		
		var suggest_id = jQuery('#wpsc-edit-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);
		var suggest_id = jQuery('#wpsc-suggest-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);
		
		wpscx_show_suggestions(parent_id, old_word, old_word_id, page_name, page_type);
		jQuery('[type=checkbox][value=' + parent_id + ']').prop('checked', false);
		var suggest_id = jQuery('#wpsc-edit-row-' + parent_id).closest('tr');
		wpscx_hide_editor(suggest_id);

		jQuery(this).closest('.Suggested').addClass('wpsc-highlight-row');
		jQuery('#wpsc-row-' + parent_id + ' span:not(.Suggested)').removeClass('wpsc-highlight-row');
		jQuery('#wpsc-row-' + parent_id + ' span:not(.Suggested)').addClass('wpsc-unselected-row');
		jQuery('#wpsc-row-' + parent_id + ' .Suggested').removeClass('wpsc-unselected-row');
                
                jQuery(document).ready(function() {
                    var mouseover_visible_edit = false;
                    jQuery('.wpsc-mouseover-pro-feature-2').mouseenter(function() {
                        jQuery('.wpsc-mouseover-text-pro-feature-2').css('z-index','100');
                        jQuery('.wpsc-mouseover-text-pro-feature-2').animate({opacity: 1.0}, 400, function() { mouseover_visible_edit = true; });
                    }).mouseleave(function() {
                        var isHoveredPopup = jQuery('.wpsc-mouseover-text-pro-feature-2').filter(function() {
                            return jQuery(this).is(":hover");
                        });
                        var isHoveredParent = jQuery(this).parent().filter(function() {
                            return jQuery(this).is(":hover");
                        });
                        
                        if (!isHoveredPopup && !isHoveredParent) {
                            jQuery('.wpsc-mouseover-text-pro-feature-2').css('z-index','-100');
                            jQuery('.wpsc-mouseover-text-pro-feature-2').animate({opacity: 0}, 400);
                            mouseover_visible_edit = false;
                        }
                    });
                    jQuery('.wpsc-mouseover-pro-feature-2').click(function() {
                        if (!mouseover_visible_edit) {
                            jQuery('.wpsc-mouseover-text-pro-feature-2').stop();
                            jQuery('.wpsc-mouseover-text-pro-feature-2').css('z-index','100');
                            jQuery('.wpsc-mouseover-text-pro-feature-2').animate({opacity: 1.0}, 400, function() { mouseover_visible_edit = true; });
                        } else {
                            jQuery('.wpsc-mouseover-text-pro-feature-2').css('z-index','-100');
                            jQuery('.wpsc-mouseover-text-pro-featuret-2').animate({opacity: 0}, 400);
                            mouseover_visible_edit = false;
                        }
                    });
                    
                    jQuery('.wpsc-mouseover-pro-feature-2').parent().mouseleave(function() {
                        //console.log("Parent Container Mouseleave Triggered");
                        var isHoveredPopup = jQuery('.wpsc-mouseover-text-pro-feature-2:hover').length > 0;
                        var isHoveredButton = jQuery('.wpsc-mouseover-pro-feature-2:hover').length > 0;
                        //console.log("Popup: " + isHoveredPopup + " | Button: " + isHoveredButton);

                        if (!isHoveredPopup && !isHoveredButton) {
                            jQuery('.wpsc-mouseover-text-pro-feature-2').css('z-index','-100');
                            jQuery('.wpsc-mouseover-text-pro-feature-2').animate({opacity: 0}, 400);
                            mouseover_visible_edit = false;
                        }
                    });
                });
	});
        
        //jQuery('.next-page').attr('href',jQuery('.next-page').attr('href').replace('admin-ajax.php?','admin.php?page=wp-spellcheck.php&'));
        //jQuery('.last-page').attr('href',jQuery('.last-page').attr('href').replace('admin-ajax.php?','admin.php?page=wp-spellcheck.php&'));
}
	
//Display the editor for a single word
function wpscx_show_editor(parent_id, old_word, old_word_id, page_name, page_type) {
	var parent = jQuery('#wpsc-row-' + parent_id),
		editor_id = 'wpsc-edit-row-' + parent_id,
		edit_row;

	//Remove all other quick edit fields
	//parent.closest('table').find('tr.wpsc-editor').each(function() {
	//	wpscx_hide_editor(jQuery(this));
	//});

	//Create edit field for the selected word
	edit_row = jQuery('#wpsc-editor-row').clone(true).attr('id', editor_id);
	edit_row.toggleClass('alternate', parent.hasClass('alternate'));
	//Add the word to the field
	edit_row.find('input[type=text]').attr('value', old_word.replace('\\',''));
	if (page_type == "Yoast SEO Title" || page_type == "All in One SEO Title" || page_type == "Ultimate SEO Title" || page_type == "SEO Title" || page_type == "SEO Post Title" || page_type == "SEO Page Title" || page_type == "SEO Media Title") {
		edit_row.find('input[type=text]').addClass("edit-seo-title");
	} else if (page_type == "Yoast SEO Description" || page_type == "All in One SEO Description" || page_type == "Ultimate SEO Description" || page_type == "SEO Description" || page_type == "SEO Page Description" || page_type == "SEO Post Description" || page_type == "SEO Media Description") {
		edit_row.find('input[type=text]').addClass("edit-seo-desc");
	}
	edit_row.find('[name="edit_page_name[]"]').attr('value', page_name);
	edit_row.find('[name="edit_page_type[]"]').attr('value', page_type);
	edit_row.find('[name="edit_old_word[]"]').attr('value', old_word);
	edit_row.find('[name="edit_old_word_id[]"]').attr('value', old_word_id);
	edit_row.find('.wpsc-mass-edit-chk').attr('value',old_word_id);
	parent.after(edit_row);

	edit_row.show();
	edit_row.find('input[type=text]').focus();
	
	edit_row.html(edit_row.html().replace("%Word%",old_word));
	wpscx_add_event_handlers();
}

//Display the editor for a single word for SEO Title or Description, with OpenAI generation button
function wpscx_show_editor_seo(parent_id, old_word, old_word_id, page_name, page_type, page_title) {
	var parent = jQuery('#wpsc-row-' + parent_id),
		editor_id = 'wpsc-edit-seo-row-' + parent_id,
		edit_row;

	//Remove all other quick edit fields
	//parent.closest('table').find('tr.wpsc-editor').each(function() {
	//	wpscx_hide_editor(jQuery(this));
	//});

	//Create edit field for the selected word
	edit_row = jQuery('#wpsc-editor-row-seo').clone(true).attr('id', editor_id);
	edit_row.toggleClass('alternate', parent.hasClass('alternate'));
	//Add the word to the field
	edit_row.find('input[type=text]').attr('value', old_word.replace('\\',''));
	if (page_type == "Yoast SEO Title" || page_type == "All in One SEO Title" || page_type == "Ultimate SEO Title" || page_type == "SEO Title" || page_type == "SEO Post Title" || page_type == "SEO Page Title" || page_type == "SEO Media Title") {
		edit_row.find('input[type=text]').addClass("edit-seo-title");
	} else if (page_type == "Yoast SEO Description" || page_type == "All in One SEO Description" || page_type == "Ultimate SEO Description" || page_type == "SEO Description" || page_type == "SEO Page Description" || page_type == "SEO Post Description" || page_type == "SEO Media Description") {
		edit_row.find('input[type=text]').addClass("edit-seo-desc");
	}
	edit_row.find('[name="edit_page_name[]"]').attr('value', page_name);
	edit_row.find('[name="edit_page_type[]"]').attr('value', page_type);
	edit_row.find('[name="edit_old_word[]"]').attr('value', old_word);
	edit_row.find('[name="edit_old_word_id[]"]').attr('value', old_word_id);
	edit_row.find('.wpsc-mass-edit-chk').attr('value',old_word_id);
	parent.after(edit_row);

	edit_row.show();
	edit_row.find('input[type=text]').focus();
	
	edit_row.html(edit_row.html().replace("%TYPE%",page_type));
        edit_row.html(edit_row.html().replace("%TITLE%",page_title));
        if (page_type == "SEO Post Title" || page_type == "SEO Page Title") { 
            edit_row.find(".wpsc-generate-seo-button").val("Generate SEO Title with AI");
            edit_row.find(".edit-seo-title").css("width", "60%");
            edit_row.html(edit_row.html().replace("%SEOTEXT%","A SEO Title should be between 50 and 70 characters long"));
        }
        if (page_type == "SEO Post Description" || page_type == "SEO Page Description") {
            edit_row.find(".wpsc-generate-seo-button").val("Generate SEO Description with AI");
            edit_row.find(".edit-seo-desc").css("width", "90%");
            edit_row.html(edit_row.html().replace("%SEOTEXT%","A SEO Description should be between 120 and 160 characters long"));
        }
	wpscx_add_event_handlers();
}

function wpscx_add_event_handlers() {
	jQuery('.edit-seo-title').keydown(function() {
		if (jQuery(this).attr('value').length > 56) {
			jQuery(this).css('color','red');
		} else {
			jQuery(this).css('color','#32373c');
		}
	});
	
	jQuery('.edit-seo-desc').keydown(function() {
		if (jQuery(this).attr('value').length > 156) {
			jQuery(this).css('color','red');
		} else {
			jQuery(this).css('color','#32373c');
		}
	});
}

//Display the spelling suggestions for a single word
function wpscx_show_suggestions(parent_id, old_word, old_word_id, page_name, page_type) {
	var parent = jQuery('#wpsc-row-' + parent_id),
		suggest_id = 'wpsc-suggest-row-' + parent_id,
		suggest_row;

	//Remove all other suggestion or quick edit fields
	//parent.closest('table').find('tr.wpsc-editor').each(function() {
	//	wpscx_hide_editor(jQuery(this));
	//});

	//Create suggestion field for the selected word
	suggest_row = jQuery('#wpsc-suggestion-row').clone(true).attr('id', suggest_id);
	suggest_row.toggleClass('alternate', parent.hasClass('alternate'));
	parent.after(suggest_row);

	//Populate the data for the suggested spellings
	var old_words = jQuery('#wpsc-row-' + parent_id).find('.wpsc-suggest-button').attr('suggestions'); //Get the suggested words
        var x = 0;
	for (x = 1; x <= 4; x++) {
		jQuery('#wpsc-suggest-row-' + parent_id).find('#wpsc-suggested-spelling-' + x).attr('value',old_words.split('-')[x - 1]);
		jQuery('#wpsc-suggest-row-' + parent_id).find('#wpsc-suggested-spelling-' + x).html(old_words.split('-')[x - 1]);
	}
	suggest_row.find('[name="suggest_page_name[]"]').attr('value', page_name);
	suggest_row.find('[name="suggest_page_type[]"]').attr('value', page_type);
	suggest_row.find('[name="suggest_old_word[]"]').attr('value', old_word);
	suggest_row.find('[name="suggest_old_word_id[]"]').attr('value', old_word_id);
	suggest_row.find('.wpsc-mass-edit-chk').attr('value',old_word_id);
        suggest_row.html(suggest_row.html().replace("%Word%",old_word));

	suggest_row.show();	
        
        jQuery('.wpsc-cancel-suggest-button').on('click',function(event) {
                event.preventDefault();
		var parent = jQuery(this).closest('tr');
		wpscx_hide_editor(parent);
		var parent_id = jQuery(this).closest('tr').attr('id').split('-')[3];
		jQuery('#wpsc-row-' + parent_id + ' .Suggested').removeClass('wpsc-highlight-row');
		jQuery('#wpsc-row-' + parent_id + ' span').removeClass('wpsc-unselected-row');
	});
}

//Hide the editor
function wpscx_hide_editor(parent_id) {
	var edit_row = isNaN(parent_id) ? parent_id : jQuery('#wpsc-edit-row' + parent_id);
	//alert('test');
	edit_row.remove();
}

//Used to retrieve URL parameters
function wpscx_GetURLParameter(sParam)
{
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    for (var i = 0; i < sURLVariables.length; i++)
    {
        var sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] == sParam)
        {
            return sParameterName[1];
        }
    }
}

//Used for the popup message on the results and options pages
jQuery(document).ready(function() {
var mouseover_visible = false;
jQuery('.wpsc-mouseover-button-post').mouseenter(function() {
jQuery('.wpsc-mouseover-text-post').css('z-index','100');
jQuery('.wpsc-mouseover-text-post').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
}).mouseleave(function() {
jQuery('.wpsc-mouseover-text-post').css('z-index','-100');
jQuery('.wpsc-mouseover-text-post').animate({opacity: 0}, 400);
mouseover_visible = false;
});
jQuery('.wpsc-mouseover-button-post').click(function() {
if (!mouseover_visible) {
jQuery('.wpsc-mouseover-text-post').stop();
jQuery('.wpsc-mouseover-text-post').css('z-index','100');
jQuery('.wpsc-mouseover-text-post').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
} else {
jQuery('.wpsc-mouseover-text-post').css('z-index','-100');
jQuery('.wpsc-mouseover-text-post').animate({opacity: 0}, 400);
mouseover_visible = false;
}
});
});

jQuery(document).ready(function() {
var mouseover_visible = false;
jQuery('.wpsc-mouseover-button-refresh').mouseenter(function() {
    jQuery('.wpsc-mouseover-text-refresh').css('z-index','100');
    jQuery('.wpsc-mouseover-text-refresh').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
}).mouseleave(function() {
    var isHoveredPopup = jQuery('.wpsc-mouseover-text-refresh').filter(function() {
        return jQuery(this).is(":hover");
    });
    var isHoveredParent = jQuery(this).parent().filter(function() {
        return jQuery(this).is(":hover");
    });
    
    jQuery('.wpsc-mouseover-text-refresh').css('z-index','-100');
    jQuery('.wpsc-mouseover-text-refresh').animate({opacity: 0}, 400);
    mouseover_visible = false;
});
jQuery('.wpsc-mouseover-button-refresh').click(function() {
if (!mouseover_visible) {
    jQuery('.wpsc-mouseover-text-refresh').stop();
    jQuery('.wpsc-mouseover-text-refresh').css('z-index','100');
    jQuery('.wpsc-mouseover-text-refresh').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
} else {
    jQuery('.wpsc-mouseover-text-refresh').css('z-index','-100');
    jQuery('.wpsc-mouseover-text-refresh').animate({opacity: 0}, 400);
    mouseover_visible = false;
}
});

jQuery('.wpsc-mouseover-button-refresh').parent().mouseleave(function() {
    //console.log("Parent Container Mouseleave Triggered");
    var isHoveredPopup = jQuery('.wpsc-mouseover-text-refresh:hover').length > 0;
    var isHoveredButton = jQuery('.wpsc-mouseover-button-refresh:hover').length > 0;
    //console.log("Popup: " + isHoveredPopup + " | Button: " + isHoveredButton);

    if (!isHoveredPopup && !isHoveredButton) {
        jQuery('.wpsc-mouseover-text-refresh').css('z-index','-100');
        jQuery('.wpsc-mouseover-text-refresh').animate({opacity: 0}, 400);
        mouseover_visible = false;
    }
});
});

jQuery(document).ready(function() {
var mouseover_visible = false;
jQuery('.wpsc-mouseover-button-change-2').mouseenter(function() {
    jQuery('.wpsc-mouseover-text-change-2').css('z-index','100');
    jQuery('.wpsc-mouseover-text-change-2').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
}).mouseleave(function() {
    var isHoveredPopup = jQuery('.wpsc-mouseover-text-change-2').filter(function() {
        return jQuery(this).is(":hover");
    });
    var isHoveredParent = jQuery(this).parent('.sc-message').filter(function() {
        return jQuery(this).is(":hover");
    });
    
    if (!isHoveredPopup && !isHoveredParent) {
        jQuery('.wpsc-mouseover-text-change-2').css('z-index','-100');
        jQuery('.wpsc-mouseover-text-change-2').animate({opacity: 0}, 400);
        mouseover_visible = false;
    }
});
jQuery('.wpsc-mouseover-button-change-2').click(function() {
    if (!mouseover_visible) {
        jQuery('.wpsc-mouseover-text-change-2').stop();
        jQuery('.wpsc-mouseover-text-change-2').css('z-index','100');
        jQuery('.wpsc-mouseover-text-change-2').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
    } else {
        jQuery('.wpsc-mouseover-text-change-2').css('z-index','-100');
        jQuery('.wpsc-mouseover-text-change-2').animate({opacity: 0}, 400);
        mouseover_visible = false;
    }
});

jQuery('.wpsc-mouseover-button-change-2').parent('.sc-message').mouseleave(function() {
    //console.log("Parent Container Mouseleave Triggered");
    var isHoveredPopup = jQuery('.wpsc-mouseover-text-change-2:hover').length > 0;
    var isHoveredButton = jQuery('.wpsc-mouseover-button-change-2:hover').length > 0;
    //console.log("Popup: " + isHoveredPopup + " | Button: " + isHoveredButton);

    if (!isHoveredPopup && !isHoveredButton) {
        jQuery('.wpsc-mouseover-text-change-2').css('z-index','-100');
        jQuery('.wpsc-mouseover-text-change-2').animate({opacity: 0}, 400);
        mouseover_visible = false;
    }
});
});

jQuery(document).ready(function() {
var mouseover_visible = false;
jQuery('.wpsc-mouseover-button-change').mouseenter(function() {
    jQuery('.wpsc-mouseover-text-change').css('z-index','100');
    jQuery('.wpsc-mouseover-text-change').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
}).mouseleave(function() {
    var isHoveredPopup = jQuery('.wpsc-mouseover-text-change').filter(function() {
        return jQuery(this).is(":hover");
    });
    var isHoveredParent = jQuery(this).parent('.sc-message').filter(function() {
        return jQuery(this).is(":hover");
    });
    
    if (!isHoveredPopup && !isHoveredParent) {
        jQuery('.wpsc-mouseover-text-change').css('z-index','-100');
        jQuery('.wpsc-mouseover-text-change').animate({opacity: 0}, 400);
        mouseover_visible = false;
    }
});
jQuery('.wpsc-mouseover-button-change').click(function() {
    if (!mouseover_visible) {
        jQuery('.wpsc-mouseover-text-change').stop();
        jQuery('.wpsc-mouseover-text-change').css('z-index','100');
        jQuery('.wpsc-mouseover-text-change').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
    } else {
        jQuery('.wpsc-mouseover-text-change').css('z-index','-100');
        jQuery('.wpsc-mouseover-text-change').animate({opacity: 0}, 400);
        mouseover_visible = false;
    }
});

jQuery('.wpsc-mouseover-button-change').parent('.sc-message').mouseleave(function() {
    //console.log("Parent Container Mouseleave Triggered");
    var isHoveredPopup = jQuery('.wpsc-mouseover-text-change:hover').length > 0;
    var isHoveredButton = jQuery('.wpsc-mouseover-button-change:hover').length > 0;
    //console.log("Popup: " + isHoveredPopup + " | Button: " + isHoveredButton);

    if (!isHoveredPopup && !isHoveredButton) {
        jQuery('.wpsc-mouseover-text-change').css('z-index','-100');
        jQuery('.wpsc-mouseover-text-change').animate({opacity: 0}, 400);
        mouseover_visible = false;
    }
});
});

jQuery(document).ready(function() {
var mouseover_visible = false;
jQuery('.wpsc-mouseover-button-page').mouseenter(function() {
jQuery('.wpsc-mouseover-text-page').css('z-index','100');
jQuery('.wpsc-mouseover-text-page').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
}).mouseleave(function() {
jQuery('.wpsc-mouseover-text-page').css('z-index','-100');
jQuery('.wpsc-mouseover-text-page').animate({opacity: 0}, 400);
mouseover_visible = false;
});
jQuery('.wpsc-mouseover-button-page').click(function() {
if (!mouseover_visible) {
jQuery('.wpsc-mouseover-text-page').stop();
jQuery('.wpsc-mouseover-text-page').css('z-index','100');
jQuery('.wpsc-mouseover-text-page').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
} else {
jQuery('.wpsc-mouseover-text-page').css('z-index','-100');
jQuery('.wpsc-mouseover-text-page').animate({opacity: 0}, 400);
mouseover_visible = false;
}
});
});

jQuery(document).ready(function() {
    var mouseover_visible = false;
    jQuery('.wpsc-mouseover-pro-feature').mouseenter(function() {
        jQuery('.wpsc-mouseover-text-pro-feature').css('z-index','100');
        jQuery('.wpsc-mouseover-text-pro-feature').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
    }).mouseleave(function() {
        var isHoveredPopup = jQuery('.wpsc-mouseover-text-pro-feature').filter(function() {
            return jQuery(this).is(":hover");
        });
        var isHoveredParent = jQuery(this).parent().filter(function() {
            return jQuery(this).is(":hover");
        });
        
        if (!isHoveredPopup && !isHoveredParent) {
            jQuery('.wpsc-mouseover-text-pro-feature').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-pro-feature').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
    jQuery('.wpsc-mouseover-pro-feature').click(function() {
        if (!mouseover_visible) {
            jQuery('.wpsc-mouseover-text-pro-feature').stop();
            jQuery('.wpsc-mouseover-text-pro-feature').css('z-index','100');
            jQuery('.wpsc-mouseover-text-pro-feature').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
        } else {
            jQuery('.wpsc-mouseover-text-pro-feature').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-pro-featuret').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
    
    jQuery('.wpsc-mouseover-pro-feature').parent().mouseleave(function() {
        //console.log("Parent Container Mouseleave Triggered");
        var isHoveredPopup = jQuery('.wpsc-mouseover-text-pro-feature:hover').length > 0;
        var isHoveredButton = jQuery('.wpsc-mouseover-pro-feature:hover').length > 0;
        //console.log("Popup: " + isHoveredPopup + " | Button: " + isHoveredButton);

        if (!isHoveredPopup && !isHoveredButton) {
            jQuery('.wpsc-mouseover-text-pro-feature').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-pro-feature').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
});

jQuery(document).ready(function() {
var mouseover_visible_edit = false;
jQuery('.wpsc-mouseover-pro-feature-2').mouseenter(function() {
jQuery('.wpsc-mouseover-text-pro-feature-2').css('z-index','100');
jQuery('.wpsc-mouseover-text-pro-feature-2').animate({opacity: 1.0}, 400, function() { mouseover_visible_edit = true; });
}).mouseleave(function() {
jQuery('.wpsc-mouseover-text-pro-feature-2').css('z-index','-100');
jQuery('.wpsc-mouseover-text-pro-feature-2').animate({opacity: 0}, 400);
mouseover_visible_edit = false;
});
jQuery('.wpsc-mouseover-pro-feature-2').click(function() {
if (!mouseover_visible_edit) {
jQuery('.wpsc-mouseover-text-pro-feature-2').stop();
jQuery('.wpsc-mouseover-text-pro-feature-2').css('z-index','100');
jQuery('.wpsc-mouseover-text-pro-feature-2').animate({opacity: 1.0}, 400, function() { mouseover_visible_edit = true; });
} else {
jQuery('.wpsc-mouseover-text-pro-feature-2').css('z-index','-100');
jQuery('.wpsc-mouseover-text-pro-featuret-2').animate({opacity: 0}, 400);
mouseover_visible_edit = false;
}
});
});

jQuery(document).ready(function() {
var mouseover_visible_edit = false;
jQuery('.wpsc-mouseover-pro-feature-3').mouseenter(function() {
jQuery('.wpsc-mouseover-text-pro-feature-3').css('z-index','100');
jQuery('.wpsc-mouseover-text-pro-feature-3').animate({opacity: 1.0}, 400, function() { mouseover_visible_edit = true; });
}).mouseleave(function() {
jQuery('.wpsc-mouseover-text-pro-feature-3').css('z-index','-100');
jQuery('.wpsc-mouseover-text-pro-feature-3').animate({opacity: 0}, 400);
mouseover_visible_edit = false;
});
jQuery('.wpsc-mouseover-pro-feature-3').click(function() {
if (!mouseover_visible_edit) {
jQuery('.wpsc-mouseover-text-pro-feature-3').stop();
jQuery('.wpsc-mouseover-text-pro-feature-3').css('z-index','100');
jQuery('.wpsc-mouseover-text-pro-feature-3').animate({opacity: 1.0}, 400, function() { mouseover_visible_edit = true; });
} else {
jQuery('.wpsc-mouseover-text-pro-feature-3').css('z-index','-100');
jQuery('.wpsc-mouseover-text-pro-featuret-3').animate({opacity: 0}, 400);
mouseover_visible_edit = false;
}
});
});

jQuery(document).ready(function() {
var mouseover_visible = false;
jQuery('.wpsc-mouseover-button-freq').mouseenter(function() {
jQuery('.wpsc-mouseover-text-freq').css('z-index','100');
jQuery('.wpsc-mouseover-text-freq').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
}).mouseleave(function() {
jQuery('.wpsc-mouseover-text-freq').css('z-index','-100');
jQuery('.wpsc-mouseover-text-freq').animate({opacity: 0}, 400);
mouseover_visible = false;
});
jQuery('.wpsc-mouseover-button-freq').click(function() {
if (!mouseover_visible) {
jQuery('.wpsc-mouseover-text-freq').stop();
jQuery('.wpsc-mouseover-text-freq').css('z-index','100');
jQuery('.wpsc-mouseover-text-freq').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
} else {
jQuery('.wpsc-mouseover-text-freq').css('z-index','-100');
jQuery('.wpsc-mouseover-text-freq').animate({opacity: 0}, 400);
mouseover_visible = false;
}
});
});

jQuery(document).ready(function() {
    var mouseover_visible = false;
    jQuery('.wpsc-mouseover-email').mouseenter(function() {
        jQuery('.wpsc-mouseover-text-email').css('z-index','100');
        jQuery('.wpsc-mouseover-text-email').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
    }).mouseleave(function() {
        var isHoveredPopup = jQuery('.wpsc-mouseover-text-email').filter(function() {
            return jQuery(this).is(":hover");
        });
        var isHoveredParent = jQuery(this).parent().filter(function() {
            return jQuery(this).is(":hover");
        });
        
        if (!isHoveredPopup && !isHoveredParent) {
            jQuery('.wpsc-mouseover-text-email').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-email').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
    jQuery('.wpsc-mouseover-email').click(function() {
        if (!mouseover_visible) {
            jQuery('.wpsc-mouseover-text-email').stop();
            jQuery('.wpsc-mouseover-text-email').css('z-index','100');
            jQuery('.wpsc-mouseover-text-email').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
        } else {
            jQuery('.wpsc-mouseover-text-email').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-email').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
    
    jQuery('.wpsc-mouseover-email').parent().mouseleave(function() {
        //console.log("Parent Container Mouseleave Triggered");
        var isHoveredPopup = jQuery('.wpsc-mouseover-text-email:hover').length > 0;
        var isHoveredButton = jQuery('.wpsc-mouseover-email:hover').length > 0;
        //console.log("Popup: " + isHoveredPopup + " | Button: " + isHoveredButton);

        if (!isHoveredPopup && !isHoveredButton) {
            jQuery('.wpsc-mouseover-text-email').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-email').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
});

jQuery(document).ready(function() {
    var mouseover_visible = false;
    jQuery('.wpsc-mouseover-import').mouseenter(function() {
        jQuery('.wpsc-mouseover-text-import').css('z-index','100');
        jQuery('.wpsc-mouseover-text-import').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
    }).mouseleave(function() {
        var isHoveredPopup = jQuery('.wpsc-mouseover-text-import').filter(function() {
            return jQuery(this).is(":hover");
        });
        var isHoveredParent = jQuery(this).parent().filter(function() {
            return jQuery(this).is(":hover");
        });
        
        if (!isHoveredPopup && !isHoveredParent) {
            jQuery('.wpsc-mouseover-text-import').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-import').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
    jQuery('.wpsc-mouseover-import').click(function() {
        if (!mouseover_visible) {
            jQuery('.wpsc-mouseover-text-import').stop();
            jQuery('.wpsc-mouseover-text-import').css('z-index','100');
            jQuery('.wpsc-mouseover-text-import').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
        } else {
            jQuery('.wpsc-mouseover-text-import').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-import').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
    
    jQuery('.wpsc-mouseover-import').parent().mouseleave(function() {
        //console.log("Parent Container Mouseleave Triggered");
        var isHoveredPopup = jQuery('.wpsc-mouseover-text-import:hover').length > 0;
        var isHoveredButton = jQuery('.wpsc-mouseover-import:hover').length > 0;
        //console.log("Popup: " + isHoveredPopup + " | Button: " + isHoveredButton);

        if (!isHoveredPopup && !isHoveredButton) {
            jQuery('.wpsc-mouseover-text-import').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-import').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
});

jQuery(document).ready(function() {
    var mouseover_visible = false;
    jQuery('.wpsc-mouseover-export').mouseenter(function() {
        jQuery('.wpsc-mouseover-text-export').css('z-index','100');
        jQuery('.wpsc-mouseover-text-export').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
    }).mouseleave(function() {
        var isHoveredPopup = jQuery('.wpsc-mouseover-text-export').filter(function() {
            return jQuery(this).is(":hover");
        });
        var isHoveredParent = jQuery(this).parent().filter(function() {
            return jQuery(this).is(":hover");
        });
        
        if (!isHoveredPopup && !isHoveredParent) {
            jQuery('.wpsc-mouseover-text-export').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-export').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
    jQuery('.wpsc-mouseover-export').click(function() {
        if (!mouseover_visible) {
            jQuery('.wpsc-mouseover-text-export').stop();
            jQuery('.wpsc-mouseover-text-export').css('z-index','100');
            jQuery('.wpsc-mouseover-text-export').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
        } else {
            jQuery('.wpsc-mouseover-text-export').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-export').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
    
    jQuery('.wpsc-mouseover-export').parent().mouseleave(function() {
        //console.log("Parent Container Mouseleave Triggered");
        var isHoveredPopup = jQuery('.wpsc-mouseover-text-export:hover').length > 0;
        var isHoveredButton = jQuery('.wpsc-mouseover-export:hover').length > 0;
        //console.log("Popup: " + isHoveredPopup + " | Button: " + isHoveredButton);

        if (!isHoveredPopup && !isHoveredButton) {
            jQuery('.wpsc-mouseover-text-export').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-export').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
});

jQuery(document).ready(function() {
    var mouseover_visible = false;
    jQuery('.wpsc-mouseover-scfeature').mouseenter(function(e) {
        jQuery('.wpsc-mouseover-text-scfeature').css('z-index','100');
        jQuery('.wpsc-mouseover-text-scfeature').css('left',e.pageX - 172);
        jQuery('.wpsc-mouseover-text-scfeature').css('top','400');
        jQuery('.wpsc-mouseover-text-scfeature').animate({opacity: 0.95}, 400, function() { mouseover_visible = true; });
    }).mouseleave(function() {
        var isHoveredPopup = jQuery('.wpsc-mouseover-text-scfeature').filter(function() {
            return jQuery(this).is(":hover");
        });
        var isHoveredParent = jQuery(this).parent().filter(function() {
            return jQuery(this).is(":hover");
        });
        
        if (!isHoveredPopup && !isHoveredParent) {
            jQuery('.wpsc-mouseover-text-scfeature').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-scfeature').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
    jQuery('.wpsc-mouseover-scfeature').click(function() {
        if (!mouseover_visible) {
            jQuery('.wpsc-mouseover-text-scfeature').stop();
            jQuery('.wpsc-mouseover-text-scfeature').css('z-index','100');
            jQuery('.wpsc-mouseover-text-scfeature').animate({opacity: 0.95}, 400, function() { mouseover_visible = true; });
        } else {
            jQuery('.wpsc-mouseover-text-scfeature').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-scfeature').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
    
    jQuery('.wpsc-mouseover-scfeature').parent().mouseleave(function() {
        //console.log("Parent Container Mouseleave Triggered");
        var isHoveredPopup = jQuery('.wpsc-mouseover-text-scfeature:hover').length > 0;
        var isHoveredButton = jQuery('.wpsc-mouseover-scfeature:hover').length > 0;
        //console.log("Popup: " + isHoveredPopup + " | Button: " + isHoveredButton);

        if (!isHoveredPopup && !isHoveredButton) {
            jQuery('.wpsc-mouseover-text-scfeature').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-scfeature').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
});

jQuery(document).ready(function() {
    var mouseover_visible = false;
    jQuery('.wpsc-mouseover-scfeature-2').mouseenter(function(e) {
        jQuery('.wpsc-mouseover-text-scfeature-2').css('z-index','100');
        jQuery('.wpsc-mouseover-text-scfeature-2').css('left',e.pageX - 172);
        jQuery('.wpsc-mouseover-text-scfeature-2').css('top','400');
        jQuery('.wpsc-mouseover-text-scfeature-2').animate({opacity: 0.95}, 400, function() { mouseover_visible = true; });
    }).mouseleave(function() {
        var isHoveredPopup = jQuery('.wpsc-mouseover-text-scfeature-2').filter(function() {
            return jQuery(this).is(":hover");
        });
        var isHoveredParent = jQuery(this).parent().filter(function() {
            return jQuery(this).is(":hover");
        });
        
        if (!isHoveredPopup && !isHoveredParent) {
            jQuery('.wpsc-mouseover-text-scfeature-2').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-scfeature-2').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
    jQuery('.wpsc-mouseover-scfeature').click(function() {
        if (!mouseover_visible) {
            jQuery('.wpsc-mouseover-text-scfeature-2').stop();
            jQuery('.wpsc-mouseover-text-scfeature-2').css('z-index','100');
            jQuery('.wpsc-mouseover-text-scfeature-2').animate({opacity: 0.95}, 400, function() { mouseover_visible = true; });
        } else {
            jQuery('.wpsc-mouseover-text-scfeature-2').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-scfeature-2').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
    
    jQuery('.wpsc-mouseover-scfeature-2').parent().mouseleave(function() {
        //console.log("Parent Container Mouseleave Triggered");
        var isHoveredPopup = jQuery('.wpsc-mouseover-text-scfeature-2:hover').length > 0;
        var isHoveredButton = jQuery('.wpsc-mouseover-scfeature-2:hover').length > 0;
        //console.log("Popup: " + isHoveredPopup + " | Button: " + isHoveredButton);

        if (!isHoveredPopup && !isHoveredButton) {
            jQuery('.wpsc-mouseover-text-scfeature-2').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-scfeature-2').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
});

jQuery(document).ready(function() {
    var mouseover_visible = false;
    jQuery('.wpsc-mouseover-emfeature-seo').mouseenter(function(e) {
        jQuery('.wpsc-mouseover-text-emfeature-seo').css('z-index','100');
        jQuery('.wpsc-mouseover-text-emfeature-seo').css('left',e.pageX - 182);
        jQuery('.wpsc-mouseover-text-emfeature-seo').css('top','390');
        jQuery('.wpsc-mouseover-text-emfeature-seo').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
    }).mouseleave(function() {
        var isHoveredPopup = jQuery('.wpsc-mouseover-text-emfeature-seo').filter(function() {
            return jQuery(this).is(":hover");
        });
        var isHoveredParent = jQuery(this).parent().filter(function() {
            return jQuery(this).is(":hover");
        });
        
        if (!isHoveredPopup && !isHoveredParent) {
            jQuery('.wpsc-mouseover-text-emfeature-seo').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-emfeature-seo').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
    jQuery('.wpsc-mouseover-emfeature-seo').click(function() {
        if (!mouseover_visible) {
            jQuery('.wpsc-mouseover-text-emfeature-seo').stop();
            jQuery('.wpsc-mouseover-text-emfeature-seo').css('z-index','100');
            jQuery('.wpsc-mouseover-text-emfeature-seo').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
        } else {
            jQuery('.wpsc-mouseover-text-emfeature-seo').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-emfeature-seo').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
    
    jQuery('.wpsc-mouseover-emfeature-seo').parent().mouseleave(function() {
        //console.log("Parent Container Mouseleave Triggered");
        var isHoveredPopup = jQuery('.wpsc-mouseover-text-emfeature-seo:hover').length > 0;
        var isHoveredButton = jQuery('.wpsc-mouseover-emfeature-seo:hover').length > 0;
        //console.log("Popup: " + isHoveredPopup + " | Button: " + isHoveredButton);

        if (!isHoveredPopup && !isHoveredButton) {
            jQuery('.wpsc-mouseover-text-emfeature-seo').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-emfeature-seo').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
});

jQuery(document).ready(function() {
    var mouseover_visible = false;
    jQuery('.wpsc-mouseover-emfeature').mouseenter(function(e) {
        jQuery('.wpsc-mouseover-text-emfeature').css('z-index','100');
        jQuery('.wpsc-mouseover-text-emfeature').css('left',e.pageX - 182);
        jQuery('.wpsc-mouseover-text-emfeature').css('top','390');
        jQuery('.wpsc-mouseover-text-emfeature').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
    }).mouseleave(function() {
        var isHoveredPopup = jQuery('.wpsc-mouseover-text-emfeature').filter(function() {
            return jQuery(this).is(":hover");
        });
        var isHoveredParent = jQuery(this).parent().filter(function() {
            return jQuery(this).is(":hover");
        });
        
        if (!isHoveredPopup && !isHoveredParent) {
            jQuery('.wpsc-mouseover-text-emfeature').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-emfeature').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
    jQuery('.wpsc-mouseover-emfeature').click(function() {
        if (!mouseover_visible) {
            jQuery('.wpsc-mouseover-text-emfeature').stop();
            jQuery('.wpsc-mouseover-text-emfeature').css('z-index','100');
            jQuery('.wpsc-mouseover-text-emfeature').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
        } else {
            jQuery('.wpsc-mouseover-text-emfeature').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-emfeature').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
    
    jQuery('.wpsc-mouseover-emfeature').parent().mouseleave(function() {
        //console.log("Parent Container Mouseleave Triggered");
        var isHoveredPopup = jQuery('.wpsc-mouseover-text-emfeature:hover').length > 0;
        var isHoveredButton = jQuery('.wpsc-mouseover-emfeature:hover').length > 0;
        //console.log("Popup: " + isHoveredPopup + " | Button: " + isHoveredButton);

        if (!isHoveredPopup && !isHoveredButton) {
            jQuery('.wpsc-mouseover-text-emfeature').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-emfeature').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
});

jQuery(document).ready(function() {
    var mouseover_visible = false;
    jQuery('.wpsc-mouseover-emfeature-2').mouseenter(function(e) {
        jQuery('.wpsc-mouseover-text-emfeature-2').css('z-index','100');
        jQuery('.wpsc-mouseover-text-emfeature-2').css('left',e.pageX - 182);
        jQuery('.wpsc-mouseover-text-emfeature-2').css('top','390');
        jQuery('.wpsc-mouseover-text-emfeature-2').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
    }).mouseleave(function() {
        var isHoveredPopup = jQuery('.wpsc-mouseover-text-emfeature-2').filter(function() {
            return jQuery(this).is(":hover");
        });
        var isHoveredParent = jQuery(this).parent().filter(function() {
            return jQuery(this).is(":hover");
        });
        
        if (!isHoveredPopup && !isHoveredParent) {
            jQuery('.wpsc-mouseover-text-emfeature-2').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-emfeature-2').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
    jQuery('.wpsc-mouseover-emfeature').click(function() {
        if (!mouseover_visible) {
            jQuery('.wpsc-mouseover-text-emfeature-2').stop();
            jQuery('.wpsc-mouseover-text-emfeature-2').css('z-index','100');
            jQuery('.wpsc-mouseover-text-emfeature-2').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
        } else {
            jQuery('.wpsc-mouseover-text-emfeature-2').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-emfeature-2').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
    
    jQuery('.wpsc-mouseover-emfeature-2').parent().mouseleave(function() {
        //console.log("Parent Container Mouseleave Triggered");
        var isHoveredPopup = jQuery('.wpsc-mouseover-text-emfeature-2:hover').length > 0;
        var isHoveredButton = jQuery('.wpsc-mouseover-emfeature-2:hover').length > 0;
        //console.log("Popup: " + isHoveredPopup + " | Button: " + isHoveredButton);

        if (!isHoveredPopup && !isHoveredButton) {
            jQuery('.wpsc-mouseover-text-emfeature-2').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-emfeature-2').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
});

jQuery(document).ready(function() {
    var mouseover_visible = false;
    jQuery('.wpsc-mouseover-emfeature-3').mouseenter(function(e) {
        jQuery('.wpsc-mouseover-text-emfeature-3').css('z-index','100');
        jQuery('.wpsc-mouseover-text-emfeature-3').css('left',e.pageX - 182);
        jQuery('.wpsc-mouseover-text-emfeature-3').css('top','390');
        jQuery('.wpsc-mouseover-text-emfeature-3').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
    }).mouseleave(function() {
        var isHoveredPopup = jQuery('.wpsc-mouseover-text-emfeature-3').filter(function() {
            return jQuery(this).is(":hover");
        });
        var isHoveredParent = jQuery(this).parent().filter(function() {
            return jQuery(this).is(":hover");
        });
        
        if (!isHoveredPopup && !isHoveredParent) {
            jQuery('.wpsc-mouseover-text-emfeature-3').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-emfeature-3').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
    jQuery('.wpsc-mouseover-emfeature-3').click(function() {
        if (!mouseover_visible) {
            jQuery('.wpsc-mouseover-text-emfeature-3').stop();
            jQuery('.wpsc-mouseover-text-emfeature-3').css('z-index','100');
            jQuery('.wpsc-mouseover-text-emfeature-3').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
        } else {
            jQuery('.wpsc-mouseover-text-emfeature-3').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-emfeature-3').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
    
    jQuery('.wpsc-mouseover-emfeature-3').parent().mouseleave(function() {
        //console.log("Parent Container Mouseleave Triggered");
        var isHoveredPopup = jQuery('.wpsc-mouseover-text-emfeature-3:hover').length > 0;
        var isHoveredButton = jQuery('.wpsc-mouseover-emfeature-3:hover').length > 0;
        //console.log("Popup: " + isHoveredPopup + " | Button: " + isHoveredButton);

        if (!isHoveredPopup && !isHoveredButton) {
            jQuery('.wpsc-mouseover-text-emfeature-3').css('z-index','-100');
            jQuery('.wpsc-mouseover-text-emfeature-3').animate({opacity: 0}, 400);
            mouseover_visible = false;
        }
    });
});