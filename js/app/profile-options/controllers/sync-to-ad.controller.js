(function () {
    app.controller('SyncToAdController', SyncToAdController);

    SyncToAdController.$inject = ['$scope', '$http', 'DataService', 'alertify'];

    function SyncToAdController($scope, $http, DataService, alertify) {
        var vm = this;

        $scope.isSaveDisabled = false;

        $scope.$on('permissionItems', function (event, data) {
            $scope.permissionOptions = data;
        });

        $scope.$on('options', function (event, data) {
            $scope.option = {
                sync_to_ad: $valueHelper.findValue("sync_to_ad", data),
                sync_to_ad_use_global_user: $valueHelper.findValue("sync_to_ad_use_global_user", data),
                sync_to_ad_global_user: $valueHelper.findValue("sync_to_ad_global_user", data),
                sync_to_ad_global_password: $valueHelper.findValue("sync_to_ad_global_password", data),
                sync_to_ad_authcode: $valueHelper.findValue("sync_to_ad_authcode", data),
            };

            if ($valueHelper.findValue("domain_sid", data) == '') {
                $scope.isSaveDisabled = true;
            } else {
                $scope.isSaveDisabled = false;
            }

            $scope.permission = {
                sync_to_ad: $valueHelper.findPermission("sync_to_ad", data),
                sync_to_ad_use_global_user: $valueHelper.findPermission("sync_to_ad_use_global_user", data),
                sync_to_ad_global_user: $valueHelper.findPermission("sync_to_ad_global_user", data),
                sync_to_ad_global_password: $valueHelper.findPermission("sync_to_ad_global_password", data),
                sync_to_ad_authcode: $valueHelper.findPermission("sync_to_ad_authcode", data),
            };
        });

        $scope.$on('validation', function (event, data) {
            $scope.messages = {
                sync_to_ad: $valueHelper.findMessage("sync_to_ad", data),
                sync_to_ad_use_global_user: $valueHelper.findMessage("sync_to_ad_use_global_user", data),
                sync_to_ad_global_user: $valueHelper.findMessage("sync_to_ad_global_user", data),
                sync_to_ad_global_password: $valueHelper.findMessage("sync_to_ad_global_password", data),
                sync_to_ad_authcode: $valueHelper.findMessage("sync_to_ad_authcode", data)
            };
        });

        $scope.$on('verification', function (event, data) {
            $scope.isSaveDisabled = false;
        });

        $scope.newAuthCode = function () {
            alertify.confirm(document['next_ad_int']['auth-code-confirmation'], function () {
                $http.post('../admin-ajax.php', {
                    action: 'next_ad_int_profile_options',
                    security: document.next_ad_int.security,
                    subAction: 'generateNewAuthCode'
                }).then(function successCallback(response) {
                    $scope.option.sync_to_ad_authcode = response.data['newAuthCode'];
                }, function errorCallback(response) {
                    // called asynchronously if an error occurs
                    // or server returns response with an error status.
                });
            }, function() {
                
            });                
        };

        $scope.getPreparedOptions = function () {
            var data =  DataService.cleanOptions($scope.option);
            return data;
        };

        $scope.containsErrors = function () {
            return (!$arrayUtil.containsOnlyNullValues($scope.messages));
        };
    }
})();