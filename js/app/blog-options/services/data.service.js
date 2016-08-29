(function () {
    app.service('DataService', DataService);

    DataService.$inject = ['$http', '$result'];

    function DataService($http, $result, ListService) {
        var vm = this,
            adminAjax = 'admin-ajax.php',
            action = 'next_ad_int_profile_options';

        /**
         * Get all permission options.
         *
         * TODO: get data from the server.
         *
         * @returns {*[]}
         */
        vm.getPermissionOptions = function () {
            return [
                {"value": "0", "description": "Input field is invisible."},
                {
                    "value": "1",
                    "description": "Visible, deactivated and invisible option value (suitable for passwords)."
                },
                {"value": 2, "description": "Visible, deactivated and visible option value."},
                {"value": "3", "description": "Blog admin sets the option value."}
            ]
        };

        /**
         * Clean the given options from the angular data.
         *
         * @param options
         *
         * @return options
         */
        vm.cleanOptions = function (options) {
            var dataString = angular.toJson(options);
            return JSON.parse(dataString);
        };

        vm.loadInitData = function () {
            return $http.post(adminAjax, {
                action: 'next_ad_int_blog_options',
                security: document.next_ad_int.security,
                subAction: 'getAllOptionsValues'
            }).then($result);
        };

        /**
         * Iterate through every child scope and get the
         *
         * @param $profileControllerScope
         * @returns {{options: {}}}
         */
        vm.mergeScopeOptions = function ($profileControllerScope) {
            var dataBuffer = {
                options: {}
            };

            // to prevent a bug, that caused the service to retrieve old data, we have to iterate through
            // the $$childTail and $$prevSibling
            for (var parentScope = $profileControllerScope.$$childTail; parentScope; parentScope = parentScope.$$childTail) {
                // get $$childHead first and then iterate that scope's $$nextSiblings
                for (var scope = parentScope; scope; scope = scope.$$prevSibling) {
                    // if our $scope does not contain our option our permission we skip this scope
                    if (typeof scope['option'] == 'undefined' && typeof scope['permission'] == 'undefined') {
                        continue;
                    }

                    // get our permissions from our scope and clean them
                    // check if the scope provides a getPreparedOptions and use it, else use the options and clean it
                    var options = (angular.isFunction(scope['getPreparedOptions']))
                        ? scope.getPreparedOptions()
                        : vm.cleanOptions(scope.option);

                    // push our data to our buffer
                    for (var option in options) {
                        dataBuffer.options[option] = options[option];
                    }
                }
            }

            return dataBuffer;
        };

        return vm;
    }
})();