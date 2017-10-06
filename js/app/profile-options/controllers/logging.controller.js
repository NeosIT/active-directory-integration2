(function () {
    app.controller('LoggingController', LoggingController);

    LoggingController.$inject = ['$scope', '$http', 'PersistService', 'ngNotify'];

    function LoggingController($scope, $http, PersistService, ngNotify) {
        var vm = this;

        $scope.isSaveDisabled = false;

        $scope.$on('permissionItems', function (event, data) {
            $scope.permissionOptions = data;
        });


        $scope.$on('options', function (event, data) {
            $scope.option = {
                logger_enable_logging: $valueHelper.findValue("logger_enable_logging", data),
                logger_custom_path: $valueHelper.findValue("logger_custom_path", data),
            };

            if ($valueHelper.findValue("domain_sid", data) == '') {
                $scope.isSaveDisabled = true;
            } else {
                $scope.isSaveDisabled = false;
            }

            $scope.permission = {
                logger_enable_logging: $valueHelper.findPermission("logger_enable_logging", data),
                logger_custom_path: $valueHelper.findPermission("logger_custom_path", data),
            };
        });

        $scope.$on('validation', function (event, data) {
            $scope.messages = {
                logger_enable_logging: $valueHelper.findMessage("logger_enable_logging", data),
                logger_custom_path: $valueHelper.findMessage("logger_custom_path", data),
            };
        });

        $scope.$on('verification', function (event, data) {
            $scope.isSaveDisabled = false;
        });


        $scope.containsErrors = function () {
            return (!$arrayUtil.containsOnlyNullValues($scope.messages));
        };


        $scope.activateLogging = function () {
            console.log("Save Logging information");
            console.log($scope.$parent.activeProfile.profileId);

            var data = {
                options : {
                    logger_enable_logging : {
                        option_value : $scope.option.logger_enable_logging,
                        option_permission : $scope.permission.logger_enable_logging
                    },
                    logger_custom_path : {
                        option_value : $scope.option.logger_custom_path,
                        option_permission : $scope.permission.logger_custom_path
                    },
                    profile_name : {
                        option_value : $scope.$parent.activeProfile.profileName,
                        option_permission : undefined
                    }
                },
                profile: $scope.$parent.activeProfile.profileId
            };

            PersistService.persistData(data).then(function (response) {

                if (response.type === 'success') {
                    ngNotify.set(document['next_ad_int']['saving-success'], 'success');

                } else {
                    ngNotify.set(document['next_ad_int']['saving-error'], 'error');
                }
            });
        }

    }
})();