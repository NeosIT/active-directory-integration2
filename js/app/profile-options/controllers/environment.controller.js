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
                network_timeout: $valueHelper.findValue("network_timeout", data),
                encryption: $valueHelper.findValue("encryption", data),
                base_dn: $valueHelper.findValue("base_dn", data),
                verification_username : '',
                verification_password : '',
                domain_sid: $valueHelper.findValue("domain_sid", data),
                netbios_name: $valueHelper.findValue("netbios_name", data),
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
                network_timeout: $valueHelper.findPermission('network_timeout', data),
                encryption: $valueHelper.findPermission('encryption', data),
                base_dn: $valueHelper.findPermission('base_dn', data),
                verification_username : $valueHelper.findPermission("verification_username", data),
                verification_password : $valueHelper.findPermission("verification_password", data),
                domain_sid: $valueHelper.findPermission("domain_sid", data),
                netbios_name: $valueHelper.findPermission("netbios_name", data)
            };
            

            if ($scope.option.domain_sid != '') {
                $scope.option.verification_status_message = document['next_ad_int']['verification-status'];
            }
        });

        $scope.$on('validation', function (event, data) {
            $scope.messages = {
                domain_controllers: $valueHelper.findMessage('domain_controllers', data),
                port: $valueHelper.findMessage('port', data),
                network_timeout: $valueHelper.findMessage('network_timeout', data),
                encryption: $valueHelper.findMessage('encryption', data),
                base_dn: $valueHelper.findMessage('base_dn', data),
                verification_username : $valueHelper.findMessage("verification_username", data),
                verification_password : $valueHelper.findMessage("verification_password", data),
                domain_sid: $valueHelper.findMessage("domain_sid", data),
                netbios_name: $valueHelper.findValue("netbios_name", data)
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

            // check if the input field is not empty
            if($scope.new_domain_controllers != '') {
                // add the input field value to the list of objects to be saved
                ListService.addListItem($scope.new_domain_controllers, $scope.option.domain_controllers);
                $scope.new_domain_controllers = '';
            }

            var data = {
                domain_controllers: ListService.parseListArrayToString($scope.option.domain_controllers),
                port: $scope.option.port,
                encryption: $scope.option.encryption,
                network_timeout: $scope.option.network_timeout,
                base_dn: $scope.option.base_dn,
                verification_username: $scope.option.verification_username,
                verification_password: $scope.option.verification_password,
                profile: $scope.activeProfile.profileId
            };

            $http.post('../admin-ajax.php', {
                action: 'next_ad_int_profile_options',
                security: document.next_ad_int.security,
                data: data,
                subAction: 'verifyAdConnection'
            }).then(function (response) {
                if (typeof response != 'undefined') {
                    $scope.messages = response.data;

                    if (response.data.hasOwnProperty("verification_successful_sid")) {
                        $scope.option.verification_status_message = document['next_ad_int']['verification-successful'];
                        ngNotify.set(document['next_ad_int']['verification-successful-notification'], 'success');
                        $scope.messages = {};
                        $scope.option.domain_sid = response.data['verification_successful_sid'];
                        $scope.option.netbios_name = response.data['verification_successful_netbios'];
                        $scope.isSaveDisabled = false;
                        $rootScope.$broadcast('verification', $scope.option.domain_sid);

                    } else {
                        ngNotify.set(document['next_ad_int']['verification-failed-notification'], 'error');
                    }

                }
            });
        }

        /**
         * Added by sfi
         * This step is required, to add the input field vlaue to the list.
         * This way the input value will be saved without having to press the plus icon. If this method is not present in a controller,
         * the parent controller will be used (default).
         */
        $scope.save = function() {
            // check if the input field is not empty
            if($scope.new_domain_controllers != '') {
                // add the input field value to the list of objects to be saved
                ListService.addListItem($scope.new_domain_controllers, $scope.option.domain_controllers);
                $scope.new_domain_controllers = '';
            }
            // call parent save
            $scope.$parent.save();
        }
    }
})();