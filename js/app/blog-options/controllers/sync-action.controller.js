(function () {
    app.controller('SyncActionController', SyncActionController);

    SyncActionController.$inject = ['$scope'];

    function SyncActionController($scope) {
        var vm = this;

        vm.isSyncEnabled = $scope.syncEnabled == 1;
        vm.domainSidSet = $scope.sid == 1;
        vm.isUserSet = $scope.syncUserSet == 1;
        vm.isPasswordSet = $scope.syncPassSet == 1;

        $scope.enableSync = vm.isSyncEnabled &&
            vm.domainSidSet &&
            vm.isUserSet &&
            vm.isPasswordSet;
    }


})();