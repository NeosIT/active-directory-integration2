/**
 * UI utility class for assigning a profile to a to a site
 */
document['next_ad_int'] = document['next_ad_int'] || {};
document['next_ad_int']['profile_of_blog'] = {};

jQuery(document).ready(function () {

    jQuery('#assignProfile').on('click', function (event) {
        event.preventDefault();

        var form = jQuery(this).closest('form');
        var data = {
            profile: form.find('[name="profile"]').val(),
            allblogs: form.find('[name="allblogs[]"]:checked').map(function () {
                return jQuery(this).val();
            }).get()
        };

        jQuery.post(ajaxurl, {
            'action': 'next_ad_int_blog_profile_relationship',
            'data': data,
            'security': document['next_ad_int']['security']
        }, function (response) {
            if (response == 0) {
                location.reload();
            } else {
                alert(response);
            }
        });
    });

    jQuery('#assignDefaultProfile').on('click', function (event) {
        event.preventDefault();

        var form = jQuery(this).closest('form');
        var data = {
            'default-profile': form.find('[name="profile"]').val()
        };

        jQuery.post(ajaxurl, {
            'action': 'next_ad_int_blog_profile_relationship',
            'data': data,
            'security': document['next_ad_int']['security']
        }, function (response) {
            if (response == 0) {
                location.reload();
            } else {
                alert(response);
            }
        });
    });
});
