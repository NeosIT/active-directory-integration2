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
                enable_smartcard_user_login: $valueHelper.findValue("enable_smartcard_user_login", data),
                custom_login_page_enabled: $valueHelper.findValue("custom_login_page_enabled", data),
                custom_login_page_uri: $valueHelper.findValue("custom_login_page_uri", data),
                max_login_attempts: $valueHelper.findValue("max_login_attempts", data),
                block_time: $valueHelper.findValue("block_time", data),
                user_notification: $valueHelper.findValue("user_notification", data),
                admin_notification: $valueHelper.findValue("admin_notification", data),
                admin_email: $valueHelper.findValue("admin_email", data).split(";"),
				allow_xmlrpc_login: $valueHelper.findValue("allow_xmlrpc_login", data),
                from_email: $valueHelper.findValue("from_email", data)
            };

            if ($valueHelper.findValue("domain_sid", data) == '') {
                $scope.isSaveDisabled = true;
            }

            $scope.permission = {
                enable_smartcard_user_login: $valueHelper.findPermission("enable_smartcard_user_login", data),
                custom_login_page_enabled: $valueHelper.findPermission("custom_login_page_enabled", data),
                custom_login_page_uri: $valueHelper.findPermission("custom_login_page_uri", data),
                max_login_attempts: $valueHelper.findPermission("max_login_attempts", data),
                block_time: $valueHelper.findPermission("block_time", data),
                user_notification: $valueHelper.findPermission("user_notification", data),
                admin_notification: $valueHelper.findPermission("admin_notification", data),
                admin_email: $valueHelper.findPermission("admin_email", data),
                from_email: $valueHelper.findPermission("from_email", data),
                allow_xmlrpc_login: $valueHelper.findPermission("allow_xmlrpc_login", data),
            };
        });

        $scope.$on('validation', function (event, data) {
            $scope.messages = {
                enable_smartcard_user_login: $valueHelper.findMessage("enable_smartcard_user_login", data),
                custom_login_page_enabled: $valueHelper.findMessage("custom_login_page_enabled", data),
                custom_login_page_uri: $valueHelper.findMessage("custom_login_page_uri", data),
                max_login_attempts: $valueHelper.findMessage("max_login_attempts", data),
                block_time: $valueHelper.findMessage("block_time", data),
                user_notification: $valueHelper.findMessage("user_notification", data),
                admin_notification: $valueHelper.findMessage("admin_notification", data),
                admin_email: $valueHelper.findMessage("admin_email", data),
                from_email: $valueHelper.findMessage("from_email", data),
                allow_xmlrpc_login: $valueHelper.findMessage("allow_xmlrpc_login", data)
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

        $scope.save = function() {
            // check if the input field is not empty
            if($scope.new_admin_email != '') {
                // add the input field value to the list of objects to be saved
                ListService.addListItem($scope.new_admin_email, $scope.option.admin_email);
                $scope.new_admin_email = '';
            }
            $scope.$parent.save();
        };
    }
})();