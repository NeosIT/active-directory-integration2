(function () {
    app.controller('BlogController', BlogController);

    BlogController.$inject = ['$scope', '$timeout', 'DataService', 'PersistService', 'ngNotify'];

    function BlogController($scope, $timeout, DataService, PersistService, ngNotify) {
        $scope.runningRequests = 0;

        $scope.save = function () {
            var data = DataService.mergeScopeOptions($scope);

            PersistService.persistData(data.options).then(function (response) {
                $scope.$broadcast('validation', response);

                return response;
            }).then(function (result) {
                $scope.$emit('reset-form');

                if (result.status_success === true) {
                    ngNotify.set(document['next_ad_int']['saving-success'], 'success');

                } else {
                    ngNotify.set(document['next_ad_int']['saving-error'], 'error');
                }

            });
        };

        /**
         * Method used for evaluating if a value is present
         *
         * @returns {boolean}
         */
        $scope.is_input_empty = function(input_value) {
            return (input_value == '' || !input_value);
        };

        function activate() {
            $scope.runningRequests++;

            DataService.loadInitData().then(function (result) {
                $scope.$broadcast('options', result['options']);
                $scope.$broadcast('ldapAttributes', result['ldapAttributes']);
                $scope.$broadcast('dataTypes', result['dataTypes']);
                $scope.$broadcast('wpRoles', result['wpRoles']);

                $scope.runningRequests--;
            });
        }

        $timeout(activate);
    }
})();