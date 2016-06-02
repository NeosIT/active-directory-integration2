(function () {
    app.controller('SecurityController', SecurityController);

    SecurityController.$inject = ['$scope', 'ListService', 'DataService'];

    function SecurityController($scope, ListService, DataService) {
        var vm = this;

        $scope.isSaveDisabled = false;

        $scope.permissionOptions = DataService.getPermissionOptions();

        $scope.new_admin_email = '';

        $scope.remove_admin_email = function (index) {
            $scope.option.admin_email = ListService.removeListItem(index, $scope.option.admin_email);
        };

        $scope.add_admin_email = function (newItem) {
            $scope.option.admin_email = ListService.addListItem(newItem, $scope.option.admin_email);
            $scope.new_admin_email = "";
        };

        $scope.$on('options', function (event, data) {
            $scope.option = {
                auto_login: $valueHelper.findValue("auto_login", data),
                max_login_attempts: $valueHelper.findValue("max_login_attempts", data),
                block_time: $valueHelper.findValue("block_time", data),
                user_notification: $valueHelper.findValue("user_notification", data),
                admin_notification: $valueHelper.findValue("admin_notification", data),
                admin_email: $valueHelper.findValue("admin_email", data).split(";"),
            };

            if ($valueHelper.findValue("domain_sid", data) == '') {
                $scope.isSaveDisabled = true;
            }

            $scope.permission = {
                auto_login: $valueHelper.findPermission("auto_login", data),
                max_login_attempts: $valueHelper.findPermission("max_login_attempts", data),
                block_time: $valueHelper.findPermission("block_time", data),
                user_notification: $valueHelper.findPermission("user_notification", data),
                admin_notification: $valueHelper.findPermission("admin_notification", data),
                admin_email: $valueHelper.findPermission("admin_email", data),
                verification_username : $valueHelper.findPermission("verification_username", data),
                verification_password : $valueHelper.findPermission("verification_password", data)
            };
        });

        $scope.$on('validation', function (event, data) {
            $scope.messages = {
                auto_login: $valueHelper.findMessage("auto_login", data),
                max_login_attempts: $valueHelper.findMessage("max_login_attempts", data),
                block_time: $valueHelper.findMessage("block_time", data),
                user_notification: $valueHelper.findMessage("user_notification", data),
                admin_notification: $valueHelper.findMessage("admin_notification", data),
                admin_email: $valueHelper.findMessage("admin_email", data)
            };
        });

        $scope.$on('verification', function (event, data) {
            $scope.isSaveDisabled = false;
        });

        $scope.getPreparedOptions = function () {
            var data = DataService.cleanOptions($scope.option);
            data['admin_email'] = ListService.parseListArrayToString($scope.option.admin_email);
            return data;
        };

        $scope.containsErrors = function () {
            return (!$arrayUtil.containsOnlyNullValues($scope.messages));
        };
    }
})();