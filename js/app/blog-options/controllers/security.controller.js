(function () {
    app.controller('SecurityController', SecurityController);

    SecurityController.$inject = ['$scope', 'ListService', 'DataService'];

    function SecurityController($scope, ListService, DataService) {
        var vm = this;

        $scope.isSaveDisabled = false;

        $scope.permissionOptions = DataService.getPermissionOptions();

        $scope.$on('options', function (event, data) {
            $scope.option = {
                enable_smartcard_user_login: $valueHelper.findValue("enable_smartcard_user_login", data),
                custom_login_page_enabled: $valueHelper.findValue("custom_login_page_enabled", data),
                custom_login_page_uri: $valueHelper.findValue("custom_login_page_uri", data),
				allow_xmlrpc_login: $valueHelper.findValue("allow_xmlrpc_login", data),
            };

            if ($valueHelper.findValue("domain_sid", data) == '') {
                $scope.isSaveDisabled = true;
            }

            $scope.permission = {
                enable_smartcard_user_login: $valueHelper.findPermission("enable_smartcard_user_login", data),
                custom_login_page_enabled: $valueHelper.findPermission("custom_login_page_enabled", data),
                custom_login_page_uri: $valueHelper.findPermission("custom_login_page_uri", data),
                allow_xmlrpc_login: $valueHelper.findPermission("allow_xmlrpc_login", data),
            };
        });

        $scope.$on('validation', function (event, data) {
            $scope.messages = {
                enable_smartcard_user_login: $valueHelper.findMessage("enable_smartcard_user_login", data),
                custom_login_page_enabled: $valueHelper.findMessage("custom_login_page_enabled", data),
                custom_login_page_uri: $valueHelper.findMessage("custom_login_page_uri", data),
                allow_xmlrpc_login: $valueHelper.findMessage("allow_xmlrpc_login", data)
            };
        });

        $scope.$on('verification', function (event, data) {
            $scope.isSaveDisabled = false;
        });

        $scope.getPreparedOptions = function () {
            var data = DataService.cleanOptions($scope.option);
            return data;
        };

        $scope.containsErrors = function () {
            return (!$arrayUtil.containsOnlyNullValues($scope.messages));
        };

        $scope.save = function() {
            $scope.$parent.save();
        };
    }
})();