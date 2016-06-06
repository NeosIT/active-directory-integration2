(function () {
    app.controller('GeneralController', GeneralController);

    GeneralController.$inject = ['$scope', 'DataService'];

    /**
     * Controller for the "General" tab of the profile configuration
     * @author Christopher Klein <ckl@neos-it.de>
     */
    function GeneralController($scope, DataService) {
        var vm = this;

        $scope.isSaveDisabled = false;

        $scope.permissionOptions = DataService.getPermissionOptions();

        $scope.option = {
            profile_name: '',
        };

        $scope.$watch(function () {
            return $scope.option.profile_name;
        }, function (newValue, oldValue, scope) {
            $scope.$emit('change-profile-name', newValue);
        });

        $scope.$on('options', function (event, data) {
            $scope.option = {
                profile_name: $valueHelper.findValue('profile_name', data) ? $valueHelper.findValue('profile_name', data) : '',
                support_license_key: $valueHelper.findValue('support_license_key', data),
                is_active: $valueHelper.findValue('is_active', data) ? true : false,
                show_menu_test_authentication: $valueHelper.findValue('show_menu_test_authentication', data) ? true : false,
                show_menu_sync_to_ad: $valueHelper.findValue('show_menu_sync_to_ad', data) ? true : false,
                show_menu_sync_to_wordpress: $valueHelper.findValue('show_menu_sync_to_wordpress', data) ? true : false,
            };

            if ($valueHelper.findValue("domain_sid", data) == '') {
                $scope.isSaveDisabled = true;
            }

            $scope.permission = {
                support_license_key: $valueHelper.findPermission('support_license_key', data),
                is_active: $valueHelper.findPermission('is_active', data),
                show_menu_test_authentication: $valueHelper.findPermission('show_menu_test_authentication', data),
                show_menu_sync_to_ad: $valueHelper.findPermission('show_menu_sync_to_ad', data),
                show_menu_sync_to_wordpress: $valueHelper.findPermission('show_menu_sync_to_wordpress', data),
                verification_username : $valueHelper.findPermission("verification_username", data),
                verification_password : $valueHelper.findPermission("verification_password", data),
            };
        });

        $scope.$on('validation', function (event, data) {
            $scope.messages = {
                support_license_key: $valueHelper.findMessage('support_license_key', data),
                is_active: $valueHelper.findMessage('is_active', data),
                show_menu_test_authentication: $valueHelper.findMessage('show_menu_test_authentication', data),
                show_menu_sync_to_ad: $valueHelper.findMessage('show_menu_sync_to_ad', data),
                show_menu_sync_to_wordpress: $valueHelper.findMessage('show_menu_sync_to_wordpress', data)
            };
        });
        
        $scope.$on('verification', function (event, data) {
            $scope.isSaveDisabled = false;
        });

        $scope.getPreparedOptions = function () {
            return DataService.cleanOptions($scope.option);
        };

        $scope.containsErrors = function () {
            return (!$arrayUtil.containsOnlyNullValues($scope.messages));
        };
    }
})();