document['next_ad_int'] = document['next_ad_int'] || {};
document['next_ad_int']['option_values'] = {};

document['next_ad_int'].saveOptionValueCallback = function (prefix, optionName, inputId, inputType, permissionSelectId) {
    var valueCallback = function () {
        if (inputType == 'radio') {
            return jQuery('input:radio[name = "' + inputId + '"]:checked').val();
        } else if (inputType == 'checkbox') {
            return jQuery('#' + inputId).is(':checked') ? '1' : '0';
        } else if (inputType == 'authcode') {
            var checkbox = jQuery('#' + inputId);

            if (checkbox.is(':checked')) {
                return '';
            } else {
                return checkbox.attr('authcode');
            }
        } else if (inputType == 'custom' || inputType == 'import_members_of_security_groups') {
            var value = "";
            //add a linebreak before every line after the first line
            var secondLine = false;
            //search for table rows
            jQuery('#' + inputId).children('tr').each(function (index1, line) {
                //add linebreak
                if (secondLine) {
                    value += "\n";
                }

                //add a separator (:) before the every element
                var secondUnit = false;
                //iterate over all units in the current line
                jQuery(line).children('td').children('.nsp-value').each(function (index2, unit) {
                    if(optionName == 'sync_to_wordpress_security_groups') {

                        //add the separator
                        if (secondUnit) {
                            value += ';';
                        }

                        value += jQuery(unit).val();

                        if (jQuery(unit).val() != '') {
                            value += ";";
                        }
                    } else {
                        //add the separator
                        if (secondUnit) {
                            value += ':';
                        }

                        //get and add value
                        if (jQuery(unit).attr('type') == 'radio') {
                            var name = jQuery(unit).attr('name');
                            value += jQuery('input:radio[name = "' + name + '"]:checked').val();
                        } else if (jQuery(unit).attr('type') == 'checkbox') {
                            value += jQuery(unit).is(':checked') ? '1' : '0';
                        } else {
                            value += jQuery(unit).val();
                        }
                    }

                    //passed first unit
                    if (!secondUnit) {
                        secondUnit = true;
                    }
                });

                //first line passed
                if (!secondLine) {
                    secondLine = true;
                }
            });

            return value;
        } else {
            return jQuery('#' + inputId).val();
        }
    };

    document['next_ad_int']['option_values'][prefix] = document['next_ad_int']['option_values'][prefix] || {};
    document['next_ad_int']['option_values'][prefix][optionName] = document['next_ad_int']['option_values'][prefix][optionName] || {};
    document['next_ad_int']['option_values'][prefix][optionName]['option_value'] = valueCallback;

    if (typeof permissionSelectId == "string") {
        var permissionCallback = function () {
            return parseInt(jQuery('#' + permissionSelectId).val());
        };

        document['next_ad_int']['option_values'][prefix][optionName]['option_permission'] = permissionCallback;
    }
};

jQuery(document).ready(function () {

    //change the selected tab
    var changeSelectedTabHeader = function (newSelectedTab) {
        //the parent element #optionPage_x
        var superParent = newSelectedTab.parent().parent();

        //remove the selection from all tab headers in the div #optionPage__x
        superParent.find('.tab-header').removeClass('nav-tab-active');

        //hide all tab bodies in the div #optionPage__x
        superParent.find('.tab-body').hide();

        //add the selection to the current tab header
        newSelectedTab.addClass('nav-tab-active');

        //show the tab body of the current tab header
        var target = '#' + newSelectedTab.attr('target');
        jQuery(target).show();

        //show only the help tabs of the current tab body
        showActiveHelpTabs();
    };

    //select the last selected tab
    if (window.location.href.indexOf('&tab=') > -1) {
        var tabNumber = window.location.href.split('&tab=')[1].split('&')[0];
        if (window.location.href.indexOf('&profile=') > -1) {
            var profileNumber = window.location.href.split('&profile=')[1].split('&')[0];
            changeSelectedTabHeader(jQuery('.nav-tab[tab_number=' + parseInt(tabNumber) + '][profile_number=' + profileNumber + ']'));
        } else {
            changeSelectedTabHeader(jQuery('.nav-tab[tab_number=' + parseInt(tabNumber) + ']'));
        }
    }

    // show the tab body of the clicked tab header
    jQuery('.tab-header').click(function () {
        changeSelectedTabHeader(jQuery(this));
    });

    //delete input field from text areas like "additional user attribute"
    //add function to remove button
    jQuery(document).on('click', '.nsp-textarea-remove', function () {
        //get the table row of the button
        var td = jQuery(this).parent();
        var tr = td.parent();

        //and remove this tr and its button and input fields
        tr.remove();
    });

    //add input field to a text area like "additional user attribute"
    jQuery(document).on('click', '.nsp-textarea-add', function () {
        //get div for the new input field
        var target = jQuery(this).attr('target');

        //get the blueprint for a new "line"
        var blueprint = jQuery(this).attr('clonetarget');

        //create clone
        var clone = jQuery('#' + blueprint).clone();
        //remove id and style from clone
        clone.removeAttr('id');
        clone.removeAttr('style');

        //init autocomplete field (if it exists)
        //add listener for autocomplete field (if it exists)
        if (clone.find('.nsp-autocomplete').length != 0) {
            clone.find('.nsp-autocomplete').each(function () {
                initAutocompleteField(jQuery(this));
            });
        }

        //add new element
        jQuery('#' + target).append(clone);
    });

    //get all autocomplete input fields and add all elements
    var initAutocompleteField = function (object) {
        //elements
        var elements = JSON.parse(object.attr('autocomplete-data'));
        var source = [];
        for (var name in elements) {
            // value = attribute name
            // label = attribute name (attribute description)
            source.push({value: name, label: name + ' (' + elements[name] + ')'});
        }

        //add array to the autocomplete field
        object.autocomplete({
            source: source,
            minLength: 0
        })
    };

    //init all autocomplete fields
    jQuery('.nsp-autocomplete').each(function () {
        initAutocompleteField(jQuery(this));
    });

    //This function only shows the helper tabs for the options visible at the current page.
    function showActiveHelpTabs() {

        //get all currently visible options from the current shown page
        var options = jQuery('.next_ad_int_option_line:visible');

        //get the left tab and the corresponding content div
        var tabs = jQuery('.contextual-help-tabs li');
        var divs = jQuery('.contextual-help-tabs-wrap div');

        //make all help-tabs invisible
        tabs.hide();
        divs.hide();

        //unselect all tabs and divs
        tabs.removeClass('active');
        divs.removeClass('active');

        //first visible element
        var first = true;
        //looping through all options
        options.each(function (index, item) {
            //current option element
            var option = jQuery(this);

            //get the corresponding option name (like port, enable_password_recovery etc.)
            var optionName = option.attr('option_name');

            //show all links for all visible options
            jQuery('#tab-link-' + optionName).show();

            //add the class 'active'
            if (first) {
                first = false;
                //make the first help tab link visible
                jQuery('#tab-link-' + optionName).addClass('active');
                //make the first help tab content visible
                jQuery('#tab-panel-' + optionName).show();
                jQuery('#tab-panel-' + optionName).addClass('active');
            }
        });
    }

    // This snipped fixes the helper tab menu not expanding correctly due to Bootstrap CSS overwriting WordPress "hidden" class
    // Reference: https://wordpress.stackexchange.com/questions/127179/how-to-fix-wordpress-dashboard-screen-option-help-button-its-not-working
    jQuery(document).ready(function ($) {
        $("#contextual-help-link").click(function () {
            $("#contextual-help-wrap").css("cssText", "display: block !important;");
        });
        $("#show-settings-link").click(function () {
            $("#screen-options-wrap").css("cssText", "display: block !important;");
        });
    });

    //Calling the showActiveHelpTabs function so that only settings from the first time loaded page are shown.
    jQuery( document ).ready(function() {
        showActiveHelpTabs();
    });


    //add click listener to the autocomplete fields for an overview over all elements in the autocomplete field
    jQuery(document).on('click', '.nsp-autocomplete', function () {
        jQuery(this).autocomplete("search", "");
    });
});