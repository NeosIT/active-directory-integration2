(function () {
    app.service('PersistService', PersistService);

    PersistService.$inject = ['$http', '$result', 'NotificationService'];

    function PersistService($http, $result, NotificationService) {
        var vm = this;

        vm.assignProfile = function (blogs, profileId) {
            var data = {
                profile: profileId,
                allblogs: jQuery.map(blogs, function (item) {
                    return item['blog_id'];
                })
            };

            return $http.post('../admin-ajax.php', {
                action: 'next_ad_int_blog_profile_relationship',
                data: data,
                security: document['next_ad_int']['blog-rel-security']
            }).then(function () {

            });
        };

        vm.removeProfile = function (id) {
            return $http.post('../admin-ajax.php', {
                action: 'next_ad_int_profile_options',
                security: document.next_ad_int.security,
                subAction: 'removeProfile',
                id: id
            }).then($result);
        };

        vm.persistData = function (data) {
            return $http.post('../admin-ajax.php', {
                action: 'next_ad_int_profile_options',
                security: document.next_ad_int.security,
                subAction: 'persistProfileOptionsValues',
                data: data
            }).then($result);
        };

        vm.persist = function ($scope, _data, profileId) {
            var data = _data || JSON.parse(angular.toJson($scope.option));
            var dataPermission = JSON.parse(angular.toJson($scope.permission));

            var dataBuffer = {"options": {}};

            for (var option in data) {
                dataBuffer["options"][option] = {
                    "option_value": data[option],
                    "option_permission": dataPermission[option]
                };
            }

            dataBuffer["profile"] = profileId;

            return vm.persistData(dataBuffer).then($result).then(function (response) {
                NotificationService.showMessage(response);
            });
        };

        return vm;
    }
})();