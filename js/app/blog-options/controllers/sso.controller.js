(function () {
    app.controller('SsoController', SsoController);

    SsoController.$inject = ['$scope', 'ListService', 'DataService'];

    function SsoController($scope, ListService, DataService) {
        var vm = this;

        $scope.isSaveDisabled = false;

        $scope.permissionOptions = DataService.getPermissionOptions();

        $scope.$on('options', function (event, data) {
            $scope.option = {
                sso: $valueHelper.findValue("sso", data),
                sso_user: $valueHelper.findValue("sso_user", data),
                sso_password: $valueHelper.findValue("sso_password", data),
                sso_environment_variable: $valueHelper.findValue("sso_environment_variable", data),
                sso_disable_for_xmlrpc: $valueHelper.findValue("sso_disable_for_xmlrpc", data),
                kerberos_realm_mappings: $valueHelper.findValue("kerberos_realm_mappings", data)
            };

            if ($valueHelper.findValue("domain_sid", data) == '') {
                $scope.isSaveDisabled = true;
            }

            $scope.permission = {
                sso: $valueHelper.findPermission("sso", data),
                sso_user: $valueHelper.findPermission("sso_user", data),
                sso_password: $valueHelper.findPermission("sso_password", data),
                sso_environment_variable: $valueHelper.findPermission("sso_environment_variable", data),
                sso_disable_for_xmlrpc: $valueHelper.findPermission("sso_disable_for_xmlrpc", data),
                kerberos_realm_mappings: $valueHelper.findPermission("kerberos_realm_mappings", data)
            };
        });

        $scope.$on('validation', function (event, data) {
            $scope.messages = {
                sso: $valueHelper.findMessage("sso", data),
                sso_user: $valueHelper.findMessage("sso_user", data),
                sso_password: $valueHelper.findMessage("sso_password", data),
                sso_environment_variable: $valueHelper.findMessage("sso_environment_variable", data),
                kerberos_realm_mappings: $valueHelper.findMessage("kerberos_realm_mappings", data)
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