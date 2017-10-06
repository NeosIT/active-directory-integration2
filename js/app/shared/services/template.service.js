(function () {
    app.service('TemplateService', TemplateService);

    TemplateService.$inject = ['$compile'];

    function TemplateService($compile) {
        return {
            renderTemplate: renderTemplate
        };

        /**
         * Render the template using the given {@code name} template.
         * Assign the given {@code $scope} and the rendered template to our {@code targetSelector}.
         *
         * @param $scope
         * @param targetSelector
         * @param name
         */
        function renderTemplate($scope, targetSelector, name) {
            var html = document.getElementById(name).innerHTML;
            var el = angular.element(html);
            var compiled = $compile(el);

            jQuery(targetSelector).append(el);
            compiled($scope);
        }
    }
})();