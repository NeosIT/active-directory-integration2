/**
 * Created by the on 20.07.2015.
 */

jQuery(document).ready(function () {

    //show/hide the 'configure' box
    jQuery('.change_profile_link').click(function () {
        var span = jQuery('#' + this.target);
        if (span.is(':visible')) {
            span.hide();
        } else {
            span.show();
        }
    });

    jQuery('#addProfileLink').click(function () {
        var div = jQuery('#addProfileDiv');
        if (div.is(':visible')) {
            div.hide();
        } else {
            div.show();
        }
    });

    jQuery('.change_profile_button').click(function () {
        var element = jQuery(this);
        var id = element.attr('target_id');
        var name = jQuery('#' + element.attr('target_name')).val();
        var description = jQuery('#' + element.attr('target_description')).val();

        jQuery.post(
            ajaxurl,
            {
                'action': 'next_ad_int_profile',
                'data': {
                    'type': 'change',
                    'id': id,
                    'name': name,
                    'description': description
                },
                'security': document['next_ad_int']['security']
            },
            function (response) {
                if (response == 0) {
                    location.reload();
                } else {
                    alert(response);
                }
            }
        );
    });

    jQuery('.delete_profile_link').click(function () {
        var element = jQuery(this);
        var id = element.attr('target');
        var message = element.attr('message');

        // confirm profile deletion
        if (!confirm(message)) {
            return;
        }

        jQuery.post(
            ajaxurl,
            {
                'action': 'next_ad_int_profile',
                'data': {
                    'type': 'delete',
                    'id': id
                },
                'security': document['next_ad_int']['security']
            },
            function (response) {
                if (response == 0) {
                    location.reload();
                } else {
                    alert(response);
                }
            }
        );
    });

    /**
     *
     */
    jQuery('#saveNewProfile').click(function () {
        jQuery.post(
            ajaxurl,
            {
                'action': 'next_ad_int_profile',
                'data': {
                    'type': 'add',
                    'name': jQuery('#addProfileName').val(),
                    'description': jQuery('#addProfileDescription').val()
                },
                'security': document['next_ad_int']['security']
            },
            function (response) {
                if (response == 0) {
                    //location.reload();
                } else {
                    alert(response);
                }
            }
        );
    });
});