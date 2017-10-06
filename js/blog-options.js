/**
 * Created by the on 20.07.2015.
 */
jQuery(document).ready(function () {


    jQuery('.save-blog-options').on('click', function () {
        var options = document['next_ad_int']['option_values']['blog'];
        var result = {};

        jQuery.each(options, function (name, obj) {
            result[name] = {
                'option_value': obj.option_value()
            };
        });

        jQuery.post(
            ajaxurl,
            {
                'action': 'next_ad_int_blog_options',
                'data': result,
                'security': document['next_ad_int']['security']
            },
            function (response) {
                if (response == 0) {
                    var url = window.location.href;
                    url = url.split('&tab=');
                    url = url[0] + ('&tab=' + jQuery('.nav-tab-active').attr('tab_number'));
                    window.location.href = url;
                } else {

                    var options = document['next_ad_int']['option_values']['blog'];
                    var optionNames = [];
                    jQuery.each(options, function (name) {
                        optionNames.push(name);
                    });

                    var responseObject = jQuery.parseJSON(response);

                    optionNames.forEach(function (element) {

                        var errorMsgElement = jQuery("#" + element + "_error_msg");

                        if (responseObject[element] != undefined) {

                            if (!errorMsgElement.length) {
                                jQuery("#" + element + "__value").after('<p id="' + element + '_error_msg" style="color:red">' + responseObject[element] + "</p>");
                            }
                        }

                        if (errorMsgElement.length && responseObject[element] == undefined) {
                            errorMsgElement.remove();
                        }
                    });
                }
            });
    });


});