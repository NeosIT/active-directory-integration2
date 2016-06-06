(function () {
    app.controller('EnvironmentController', EnvironmentController);

    EnvironmentController.$inject = ['$scope', 'ListService', 'DataService'];

    function EnvironmentController($scope, ListService, DataService) {
        var vm = this;

        $scope.permissionOptions = DataService.getPermissionOptions();
        $scope.new_domain_controllers = '';

        $scope.remove_domain_controllers = function (index) {
            $scope.option.domain_controllers = ListService.removeListItem(index, $scope.option.domain_controllers);
        };

        $scope.add_domain_controllers = function (newItem) {
            $scope.option.domain_controllers = ListService.addListItem(newItem, $scope.option.domain_controllers);
            $scope.new_domain_controllers = "";
        };

        $scope.$on('options', function (event, data) {
            $scope.option = {
                domain_controllers: $valueHelper.findValue('domain_controllers', data, '').split(";"),
                port: $valueHelper.findValue("port", data),
                network_timeout: $valueHelper.findValue("network_timeout", data),
                encryption: $valueHelper.findValue("encryption", data),
                base_dn: $valueHelper.findValue("base_dn", data)
            };

            $scope.permission = {
                domain_controllers: $valueHelper.findPermission('domain_controllers', data),
                port: $valueHelper.findPermission('port', data),
                network_timeout: $valueHelper.findPermission('network_timeout', data),
                encryption: $valueHelper.findPermission('encryption', data),
                base_dn: $valueHelper.findPermission('base_dn', data)
            };
        });

        $scope.$on('validation', function (event, data) {
            $scope.messages = {
                domain_controllers: $valueHelper.findMessage('domain_controllers', data),
                port: $valueHelper.findMessage('port', data),
                network_timeout: $valueHelper.findMessage('network_timeout', data),
                encryption: $valueHelper.findMessage('encryption', data),
                base_dn: $valueHelper.findMessage('base_dn', data)
            };
        });

        $scope.getPreparedOptions = function () {
            var data = DataService.cleanOptions($scope.option);
            data['domain_controllers'] = ListService.parseListArrayToString($scope.option.domain_controllers);
            return data;
        };

        $scope.containsErrors = function () {
            return (!$arrayUtil.containsOnlyNullValues($scope.messages));
        };
    }
})();