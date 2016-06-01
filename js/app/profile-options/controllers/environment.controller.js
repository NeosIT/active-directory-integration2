(function () {
    app.controller('EnvironmentController', EnvironmentController);

    EnvironmentController.$inject = ['$rootScope', '$scope','$http', 'ListService', 'DataService', 'ngNotify'];

    function EnvironmentController($rootScope, $scope, $http, ListService, DataService, ngNotify) {
        var vm = this;

        $scope.isSaveDisabled = false;

        $scope.$on('permissionItems', function (event, data) {
            $scope.permissionOptions = data;
        });

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
                use_tls: $valueHelper.findValue("use_tls", data),
                network_timeout: $valueHelper.findValue("network_timeout", data),
                base_dn: $valueHelper.findValue("base_dn", data),
                verification_username : '',
                verification_password : '',
                domain_sid: $valueHelper.findValue("domain_sid", data),
                verification_status_message: ''
            };

            if ($scope.option.domain_sid == '') {
                $scope.isSaveDisabled = true;
            } else {
                $scope.isSaveDisabled = false;
            }

            $scope.permission = {
                domain_controllers: $valueHelper.findPermission('domain_controllers', data),
                port: $valueHelper.findPermission('port', data),
                use_tls: $valueHelper.findPermission('use_tls', data),
                network_timeout: $valueHelper.findPermission('network_timeout', data),
                base_dn: $valueHelper.findPermission('base_dn', data),
                verification_username : $valueHelper.findPermission("verification_username", data),
                verification_password : $valueHelper.findPermission("verification_password", data),
                domain_sid: $valueHelper.findPermission("domain_sid", data)
            };
            

            if ($scope.option.domain_sid != '') {
                $scope.option.verification_status_message = "WordPress Site connected to Domain: "
            }
        });

        $scope.$on('validation', function (event, data) {
            $scope.messages = {
                domain_controllers: $valueHelper.findMessage('domain_controllers', data),
                port: $valueHelper.findMessage('port', data),
                use_tls: $valueHelper.findMessage('use_tls', data),
                network_timeout: $valueHelper.findMessage('network_timeout', data),
                base_dn: $valueHelper.findMessage('base_dn', data),
                verification_username : $valueHelper.findMessage("verification_username", data),
                verification_password : $valueHelper.findMessage("verification_password", data),
                domain_sid: $valueHelper.findMessage("domain_sid", data)
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

        $scope.verify = function () {

            var data = {
                domain_controllers: ListService.parseListArrayToString($scope.option.domain_controllers),
                port: $scope.option.port,
                use_tls: $scope.option.use_tls,
                network_timeout: $scope.option.network_timeout,
                base_dn: $scope.option.base_dn,
                verification_username: $scope.option.verification_username,
                verification_password: $scope.option.verification_password,
                profile: $scope.activeProfile.profileId
            };

            $http.post('../admin-ajax.php', {
                action: 'adi2_profile_options',
                security: document.adi2.security,
                data: data,
                subAction: 'verifyAdConnectionForProfile'
            }).then(function (response) {
                if (typeof response != 'undefined') {
                    $scope.messages = response.data;

                    if (response.data.hasOwnProperty("verification_successful")) {
                        $scope.option.verification_status_message = "Verification successful! WordPress site is now connected to Domain: ";
                        ngNotify.set('Verification successful!', 'success');
                        $scope.messages = {};
                        console.log(response.data);
                        $scope.option.domain_sid = response.data['verification_successful'];
                        $scope.isSaveDisabled = false;
                        $rootScope.$broadcast('verification', $scope.option.domain_sid);

                    } else {
                        ngNotify.set(response.data['verification_failed'], 'error');
                    }

                }
            });
        }
    }
})();