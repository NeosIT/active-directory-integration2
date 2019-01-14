(function () {
    app.controller('PasswordController', PasswordController);

    PasswordController.$inject = ['$scope', 'DataService'];

    function PasswordController($scope, DataService) {
        var vm = this;

        $scope.isSaveDisabled = false;        

        $scope.$on('permissionItems', function (event, data) {
            $scope.permissionOptions = data;
        });

        $scope.$on('options', function (event, data) {
            $scope.option = {
                no_random_password: $valueHelper.findValue("no_random_password", data),
                enable_password_change: $valueHelper.findValue("enable_password_change", data),
                fallback_to_local_password: $valueHelper.findValue("fallback_to_local_password", data),
                auto_update_password: $valueHelper.findValue("auto_update_password", data),
                enable_lost_password_recovery: $valueHelper.findValue("enable_lost_password_recovery", data),
            };

            if ($valueHelper.findValue("domain_sid", data) == '') {
                $scope.isSaveDisabled = true;
            } else {
                $scope.isSaveDisabled = false;
            }

            $scope.permission = {
                no_random_password: $valueHelper.findPermission("no_random_password", data),
                enable_password_change: $valueHelper.findPermission("enable_password_change", data),
                fallback_to_local_password: $valueHelper.findPermission("fallback_to_local_password", data),
                auto_update_password: $valueHelper.findPermission("auto_update_password", data),
                enable_lost_password_recovery: $valueHelper.findPermission("enable_lost_password_recovery", data),
            };
        });

        $scope.$on('validation', function (event, data) {
            $scope.messages = {
                no_random_password: $valueHelper.findMessage("no_random_password", data),
                enable_password_change: $valueHelper.findMessage("enable_password_change", data),
                fallback_to_local_password: $valueHelper.findMessage("fallback_to_local_password", data),
                auto_update_password: $valueHelper.findMessage("auto_update_password", data),
                enable_lost_password_recovery: $valueHelper.findMessage("enable_lost_password_recovery", data)
            };
        });

        $scope.$on('verification', function (event, data) {
            $scope.isSaveDisabled = false;
        });

        $scope.getPreparedOptions = function () {
            var data =  DataService.cleanOptions($scope.option);
            return data;
        };

        $scope.containsErrors = function () {
            return (!$arrayUtil.containsOnlyNullValues($scope.messages));
        };
    }
})();