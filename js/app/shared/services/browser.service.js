(function () {
    app.service('BrowserService', BrowserService);

    BrowserService.$inject = ['$window'];

    function BrowserService($window) {
        return {
            /**
             * Delegate the call to window.location.reload().
             */
            reload: function () {
                $window.location.reload();
            }
        };
    }
})();