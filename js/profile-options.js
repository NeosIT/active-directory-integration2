/**
 * Created by the on 20.07.2015.
 */
jQuery(document).ready(function () {
    function showOptionsOfSelectedProfile() {
        var selectedProfileId = jQuery('#selectedProfile').val();
        var profileOptionsOverview = '#profileOptionsOverview__' + selectedProfileId;

        jQuery('.profileOptionsOverview').hide();
        jQuery(profileOptionsOverview).show();
    }

    jQuery("#selectedProfile").change(showOptionsOfSelectedProfile);
    showOptionsOfSelectedProfile();

    //change the profile drop down menu to the last selected value
    if (window.location.href.indexOf('&profile=') > -1) {
        var profileNumber = window.location.href.split('&profile=')[1].split('&')[0];
        jQuery("#selectedProfile").val(parseInt(profileNumber));
    }

    jQuery('.save-blog-options').click(function () {
        var selectedProfileId = jQuery('#selectedProfile').val();
        var options = document['next_ad_int']['option_values'][selectedProfileId];
        var result = {};

        jQuery.each(options, function (name, obj) {
            result[name] = {
                'option_value': obj['option_value'](),
                'option_permission': obj['option_permission']()
            };
        });

        jQuery.post(
            ajaxurl,
            {
                'action': 'next_ad_int_profile_options',
                'data': {
                    'options': result,
                    'profile': selectedProfileId
                },
                'security': document['next_ad_int']['security']
            },
            function (response) {
                if (response == 0) {
                    var url = window.location.href;
                    url = url.split('&tab=')[0];
                    url = url.split('&profile=')[0];
                    url = url + '&tab=' + jQuery('.nav-tab-active[profile_number=' + selectedProfileId + ']').attr('tab_number') + '&profile=' + selectedProfileId;

                    window.location.href = url;
                } else {
                    alert(response);
                }
            }
        );
    });
})
;