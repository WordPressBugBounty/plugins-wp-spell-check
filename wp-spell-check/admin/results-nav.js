jQuery(document).ready(function() {
        //Spell Check Links
        
        var next_url = jQuery("#wpsc-scan-results-tab .next-page").attr("href"); // Get the href of the next page button
        
        if (typeof next_url !== 'undefined') {
            var s = next_url.search(/paged/i); //Get the substring containing the page and sort data for the next page
            var n = next_url.substr(s,next_url.length);
            var new_url = next_url.replace(/admin\.php.*/i, "admin.php?page=wp-spellcheck.php&" + n); //Generate a new URL without any additional garbage data
        
            jQuery("#wpsc-scan-results-tab .next-page").attr("href", new_url); //Set the next button URL
        }
        
        var prev_url = jQuery("#wpsc-scan-results-tab .prev-page").attr("href"); // Get the href of the next page button
        
        if (typeof prev_url !== 'undefined') {
            var s = prev_url.search(/paged/i); //Get the substring containing the page and sort data for the next page
            var n = prev_url.substr(s,prev_url.length);
            var new_url = prev_url.replace(/admin\.php.*/i, "admin.php?page=wp-spellcheck.php&" + n); //Generate a new URL without any additional garbage data

            jQuery("#wpsc-scan-results-tab .prev-page").attr("href", new_url); //Set the next button URL
        }
        
        var last_url = jQuery("#wpsc-scan-results-tab .last-page").attr("href"); // Get the href of the next page button
        //console.log(last_url);
        
        if (typeof last_url !== 'undefined') {
            var s = last_url.search(/paged/i); //Get the substring containing the page and sort data for the next page
            var n = last_url.substr(s,last_url.length);
            var new_url = last_url.replace(/admin\.php.*/i, "admin.php?page=wp-spellcheck.php&" + n); //Generate a new URL without any additional garbage data

            jQuery("#wpsc-scan-results-tab .last-page").attr("href", new_url); //Set the next button URL
        }
        
        var first_url = jQuery("#wpsc-scan-results-tab .first-page").attr("href"); // Get the href of the next page button
        
        if (typeof first_url !== 'undefined') {
            var s = first_url.search(/paged/i); //Get the substring containing the page and sort data for the next page
            var n = first_url.substr(s,first_url.length);
            var new_url = first_url.replace(/admin\.php.*/i, "admin.php?page=wp-spellcheck.php&" + n); //Generate a new URL without any additional garbage data

            jQuery("#wpsc-scan-results-tab .first-page").attr("href", new_url); //Set the next button URL
        }
        
        //SEO Empty Field Links
        var next_url = jQuery("#wpsc-empty-fields-tab .next-page").attr("href"); // Get the href of the next page button
        
        if (typeof next_url !== 'undefined') {
            var s = next_url.search(/paged/i); //Get the substring containing the page and sort data for the next page
            var n = next_url.substr(s, 8);
            var new_url = next_url.replace(/admin\.php.*/i, "admin.php?page=wp-spellcheck-seo.php&" + n); //Generate a new URL without any additional garbage data
        }
        
        jQuery("#wpsc-empty-fields-tab .next-page").attr("href", new_url); //Set the next button URL
        
        var prev_url = jQuery("#wpsc-empty-fields-tab .prev-page").attr("href"); // Get the href of the next page button
        
        if (typeof prev_url !== 'undefined') {
            var s = prev_url.search(/paged/i); //Get the substring containing the page and sort data for the next page
            var n = prev_url.substr(s, 8);
            var new_url = prev_url.replace(/admin\.php.*/i, "admin.php?page=wp-spellcheck-seo.php&" + n); //Generate a new URL without any additional garbage data

            jQuery("#wpsc-empty-fields-tab .prev-page").attr("href", new_url); //Set the next button URL
        }
        
        var last_url = jQuery("#wpsc-empty-fields-tab .last-page").attr("href"); // Get the href of the next page button
        
        if (typeof last_url !== 'undefined') {
            var s = last_url.search(/paged/i); //Get the substring containing the page and sort data for the next page
            var n = last_url.substr(s,last_url.length);
            var new_url = last_url.replace(/admin\.php.*/i, "admin.php?page=wp-spellcheck.php&" + n); //Generate a new URL without any additional garbage data

            jQuery("#wpsc-empty-fields-tab .last-page").attr("href", new_url); //Set the next button URL
        }
        
        var first_url = jQuery("#wpsc-empty-fields-tab .first-page").attr("href"); // Get the href of the next page button
        
        if (typeof first_url !== 'undefined') {
            var s = first_url.search(/paged/i); //Get the substring containing the page and sort data for the next page
            var n = first_url.substr(s,first_url.length);
            var new_url = first_url.replace(/admin\.php.*/i, "admin.php?page=wp-spellcheck.php&" + n); //Generate a new URL without any additional garbage data

            jQuery("#wpsc-empty-fields-tab .first-page").attr("href", new_url); //Set the next button URL
        }
        
        //Grammar Links
        var next_url = jQuery("#wpgc-scan-results-tab .next-page").attr("href"); // Get the href of the next page button
        
        if (typeof next_url !== 'undefined') {
            var s = next_url.search(/paged/i); //Get the substring containing the page and sort data for the next page
            var n = next_url.substr(s,next_url.length);
            var new_url = next_url.replace(/admin\.php.*/i, "admin.php?page=wp-spellcheck-grammar.php&" + n); //Generate a new URL without any additional garbage data
        
            jQuery("#wpgc-scan-results-tab .next-page").attr("href", new_url); //Set the next button URL
        }
        
        var prev_url = jQuery("#wpgc-scan-results-tab .prev-page").attr("href"); // Get the href of the next page button
        
        if (typeof prev_url !== 'undefined') {
            var s = prev_url.search(/paged/i); //Get the substring containing the page and sort data for the next page
            var n = prev_url.substr(s,prev_url.length);
            var new_url = prev_url.replace(/admin\.php.*/i, "admin.php?page=wp-spellcheck-grammar.php&" + n); //Generate a new URL without any additional garbage data

            jQuery("#wpgc-scan-results-tab .prev-page").attr("href", new_url); //Set the next button URL
        }
        
        var last_url = jQuery("#wpgc-scan-results-tab.last-page").attr("href"); // Get the href of the next page button
        
        if (typeof last_url !== 'undefined') {
            var s = last_url.search(/paged/i); //Get the substring containing the page and sort data for the next page
            var n = last_url.substr(s,last_url.length);
            var new_url = last_url.replace(/admin\.php.*/i, "admin.php?page=wp-spellcheck.php&" + n); //Generate a new URL without any additional garbage data

            jQuery("#wpgc-scan-results-tab .last-page").attr("href", new_url); //Set the next button URL
        }
        
        var first_url = jQuery("#wpgc-scan-results-tab .first-page").attr("href"); // Get the href of the next page button
        
        if (typeof first_url !== 'undefined') {
            var s = first_url.search(/paged/i); //Get the substring containing the page and sort data for the next page
            var n = first_url.substr(s,first_url.length);
            var new_url = first_url.replace(/admin\.php.*/i, "admin.php?page=wp-spellcheck.php&" + n); //Generate a new URL without any additional garbage data

            jQuery("#wpgc-scan-results-tab .first-page").attr("href", new_url); //Set the next button URL
        }
        
         //Broken Code Links
        
        var next_url = jQuery("#wphc-scan-results-tab .next-page").attr("href"); // Get the href of the next page button
        
        if (typeof next_url !== 'undefined') {
            var s = next_url.search(/paged/i); //Get the substring containing the page and sort data for the next page
            var n = next_url.substr(s,next_url.length);
            var new_url = next_url.replace(/admin\.php.*/i, "admin.php?page=wp-spellcheck-html.php&" + n); //Generate a new URL without any additional garbage data
        
            jQuery("#wphc-scan-results-tab .next-page").attr("href", new_url); //Set the next button URL
        }
        
        var prev_url = jQuery("#wphc-scan-results-tab .prev-page").attr("href"); // Get the href of the next page button
        
        if (typeof prev_url !== 'undefined') {
            var s = prev_url.search(/paged/i); //Get the substring containing the page and sort data for the next page
            var n = prev_url.substr(s,prev_url.length);
            var new_url = prev_url.replace(/admin\.php.*/i, "admin.php?page=wp-spellcheck-html.php&" + n); //Generate a new URL without any additional garbage data

            jQuery("#wphc-scan-results-tab .prev-page").attr("href", new_url); //Set the next button URL
        }
        
         var last_url = jQuery("#wphc-scan-results-tab.last-page").attr("href"); // Get the href of the next page button
        
        if (typeof last_url !== 'undefined') {
            var s = last_url.search(/paged/i); //Get the substring containing the page and sort data for the next page
            var n = last_url.substr(s,last_url.length);
            var new_url = last_url.replace(/admin\.php.*/i, "admin.php?page=wp-spellcheck.php&" + n); //Generate a new URL without any additional garbage data

            jQuery("#wphc-scan-results-tab .last-page").attr("href", new_url); //Set the next button URL
        }
        
        var first_url = jQuery("#wphc-scan-results-tab .first-page").attr("href"); // Get the href of the next page button
        
        if (typeof first_url !== 'undefined') {
            var s = first_url.search(/paged/i); //Get the substring containing the page and sort data for the next page
            var n = first_url.substr(s,first_url.length);
            var new_url = first_url.replace(/admin\.php.*/i, "admin.php?page=wp-spellcheck.php&" + n); //Generate a new URL without any additional garbage data

            jQuery("#wphc-scan-results-tab .first-page").attr("href", new_url); //Set the next button URL
        }
});