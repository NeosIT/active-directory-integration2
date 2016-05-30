(function () {
    app.controller('BlogController', BlogController);

    BlogController.$inject = ['$scope', '$timeout', 'DataService', 'PersistService', 'NotificationService'];

    function BlogController($scope, $timeout, DataService, PersistService, NotificationService) {
        $scope.save = function () {
            var data = DataService.mergeScopeOptions($scope);

            PersistService.persistData(data.options).then(function (response) {
                $scope.$broadcast('validation', response);

                return response;
            }).then(function (result) {
                $scope.$emit('reset-form');

                NotificationService.showMessage(result);
            });
        };

        function activate() {
            DataService.loadInitData().then(function (result) {
                $scope.$broadcast('options', result['options']);
                $scope.$broadcast('ldapAttributes', result['ldapAttributes']);
                $scope.$broadcast('dataTypes', result['dataTypes']);
                $scope.$broadcast('wpRoles', result['wpRoles']);
            });
        }

        $timeout(activate);
    }
})();