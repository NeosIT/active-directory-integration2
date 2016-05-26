(function () {
    app.service('PersistService', PersistService);

    PersistService.$inject = ['$http', '$result', 'ngNotify'];

    function PersistService($http, $result, ngNotify) {
        var vm = this;

        vm.persistData = function (data) {
            
            //exclude domains_id from persist
            data.verification_username = "";
            data.verification_password = "";
            delete data.verification_status;
            delete data.verification_status_message;

            console.log(data);
            
            return $http.post('admin-ajax.php', {
                action: 'adi2_blog_options',
                security: document.adi2.security,
                subAction: 'persistOptionsValues',
                data: data
            }).then($result);
        };

        vm.persist = function ($scope, _data) {
            var data = _data || JSON.parse(angular.toJson($scope.option));

            vm.persistData(data).then(function (response) {
                if (typeof response != 'undefined') {
                    $scope.messages = response.data;
                    ngNotify.set('Something went wrong!', 'error')
                } else {
                    $scope.messages = {};
                    ngNotify.set('Save successful!', 'success');
                }
            });
        };

        return vm;
    }
})();