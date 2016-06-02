(function () {
    app.controller('SyncToWordpressController', SyncToWordpressController);

    SyncToWordpressController.$inject = ['$scope', '$http', 'ListService', 'DataService', 'alertify'];

    function SyncToWordpressController($scope, $http, ListService, DataService, alertify) {
        var vm = this;

        $scope.isSaveDisabled = false;

        $scope.permissionOptions = DataService.getPermissionOptions();
        $scope.new_sync_to_wordpress_security_groups = '';

        $scope.remove_sync_to_wordpress_security_groups = function (index) {
            $scope.option.sync_to_wordpress_security_groups = ListService.removeListItem(index, $scope.option.sync_to_wordpress_security_groups);
        };

        $scope.add_sync_to_wordpress_security_groups = function (newItem) {
            $scope.option.sync_to_wordpress_security_groups = ListService.addListItem(newItem, $scope.option.sync_to_wordpress_security_groups);
            $scope.new_sync_to_wordpress_security_groups = "";
        };

        $scope.$on('options', function (event, data) {
            $scope.option = {
                sync_to_wordpress_enabled: $valueHelper.findValue("sync_to_wordpress_enabled", data),
                sync_to_wordpress_user: $valueHelper.findValue("sync_to_wordpress_user", data),
                sync_to_wordpress_password: $valueHelper.findValue("sync_to_wordpress_password", data),
                sync_to_wordpress_security_groups: $valueHelper.findValue("sync_to_wordpress_security_groups", data).split(";"),
                disable_users: $valueHelper.findValue("disable_users", data),
                sync_to_wordpress_authcode: $valueHelper.findValue("sync_to_wordpress_authcode", data),
            };

            if ($valueHelper.findValue("domain_sid", data) == '') {
                $scope.isSaveDisabled = true;
            }
            
            $scope.permission = {
                sync_to_wordpress_enabled: $valueHelper.findPermission("sync_to_wordpress_enabled", data),
                sync_to_wordpress_user: $valueHelper.findPermission("sync_to_wordpress_user", data),
                sync_to_wordpress_password: $valueHelper.findPermission("sync_to_wordpress_password", data),
                sync_to_wordpress_security_groups: $valueHelper.findPermission("sync_to_wordpress_security_groups", data),
                disable_users: $valueHelper.findPermission("disable_users", data),
                sync_to_wordpress_authcode: $valueHelper.findPermission("sync_to_wordpress_authcode", data),
                verification_username : $valueHelper.findPermission("verification_username", data),
                verification_password : $valueHelper.findPermission("verification_password", data)
            };
        });

        $scope.$on('validation', function (event, data) {
            $scope.messages = {
                sync_to_wordpress_enabled: $valueHelper.findMessage("sync_to_wordpress_enabled", data),
                sync_to_wordpress_user: $valueHelper.findMessage("sync_to_wordpress_user", data),
                sync_to_wordpress_password: $valueHelper.findMessage("sync_to_wordpress_password", data),
                sync_to_wordpress_security_groups: $valueHelper.findMessage("sync_to_wordpress_security_groups", data),
                disable_users: $valueHelper.findMessage("disable_users", data),
                sync_to_wordpress_authcode: $valueHelper.findMessage("sync_to_wordpress_authcode", data),
                verification_status: $valueHelper.findValue("domain_sid", data)
            };
        });

        $scope.$on('verification', function (event, data) {
            $scope.isSaveDisabled = false;
        });

        $scope.newAuthCode = function () {
            alertify.confirm("Do you really want to regenerate a new AuthCode?", function () {
                $http.post('admin-ajax.php', {
                    action: 'adi2_blog_options',
                    security: document.adi2.security,
                    subAction: 'generateNewAuthCode'
                }).then(function successCallback(response) {
                    $scope.option.sync_to_wordpress_authcode = response.data['newAuthCode'];
                }, function errorCallback(response) {
                    // called asynchronously if an error occurs
                    // or server returns response with an error status.
                });
            }, function() {
                
            });
        };

        $scope.getPreparedOptions = function () {
            var data = DataService.cleanOptions($scope.option);
            data['sync_to_wordpress_security_groups'] = ListService.parseListArrayToString($scope.option.sync_to_wordpress_security_groups);
            return data;
        };

        $scope.containsErrors = function () {
            return (!$arrayUtil.containsOnlyNullValues($scope.messages));
        };
    }
})();