(function () {
    app.controller('DeleteController', DeleteController);

    DeleteController.$inject = ['$rootScope', '$scope', 'PersistService', 'BrowserService'];

    function DeleteController($rootScope, $scope, PersistService, BrowserService) {
        $scope.profiles = [];
        $scope.associations = [];
        $scope.associationIds = [];
        $scope.show = false;
        $scope.profile = null;
        $scope.profileId = null;
        $scope.newProfile = '-1';

        $scope.showProfileList = false;

        $scope.$on('delete', function (event, profile, profiles, associations) {
            $scope.profile = profile;
            $scope.profileId = profile['profileId'];
            $scope.showProfileList = (associations.length > 0 && associations.length < 5);
            $scope.profiles = profiles;

            $scope.associations = associations;
            $scope.associationIds = jQuery.map(associations, function (item) {
                return item['blog_id'];
            }).join(',');

            $scope.show = true;
        });

        /**
         * Filter out the current active profile.
         *
         * @param value
         * @param index
         * @param array
         *
         * return {boolean}
         */
        $scope.newProfileFilter = function (value, index, array) {
            return (value != $scope.activeProfile);
        };

        /**
         * Cancel the delete process.
         */
        $scope.cancel = function () {
            $scope.show = false;
        };

        /**
         * Confirm the delete process.
         */
        $scope.confirm = function () {
            // if the profile is new, remove it and do not trigger the server
            if (-1 == $scope.profileId) {
                return;
            }

            if (null == $scope.profileId) {
                $rootScope.$broadcast('remove-profile', $scope.profile);
                $scope.show = false;
                return;
            }

            PersistService.assignProfile($scope.associations, $scope.newProfile).then(function () {
                // if the profile has already been persisted, remove it from the server
                PersistService.removeProfile($scope.profileId).then(function (result) {
                    BrowserService.reload();
                });
            });
        };
    }
})();