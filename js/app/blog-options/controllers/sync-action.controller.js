(function () {
    app.controller('SyncActionController', SyncActionController);

    SyncActionController.$inject = ['$scope'];

    function SyncActionController($scope) {
        var vm = this;

        vm.isSyncEnabled = $scope.syncEnabled == 1;
        vm.domainSidSet = $scope.sid != "";

        $scope.enableSync = vm.isSyncEnabled && vm.domainSidSet;
    }


})();