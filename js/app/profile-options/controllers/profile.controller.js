(function () {
    app.controller('ProfileController', ProfileController);

    ProfileController.$inject = [
        '$rootScope', '$scope', '$timeout', 'alertify', 'DataService', 'PersistService', 'NotificationService'
    ];

    function ProfileController($rootScope, $scope, $timeout, alertify, DataService, PersistService, NotificationService) {
        var vm = this;

        vm.lastActiveProfile = null;
        vm.defaultProfileData = null;
        vm.originalProfileData = null;
        vm.associations = [];

        $scope.associations = [];
        $scope.associationIds = '';
        $scope.profiles = [];
        $scope.activeProfile = null;
        $scope.runningRequests = 0;

        /**
         * Create a new profile and push it to the select box. The new profile will be selected automatically and the
         * default data will be broadcasted to the other controllers.
         */
        $scope.create = function () {
            var profile = angular.copy(document['next_ad_int']['new-profile']);

            $scope.profiles.push(profile);
            $scope.activeProfile = profile;
            vm.lastActiveProfile = profile;

            $scope.$broadcast('options', vm.defaultProfileData);
            $scope.$broadcast('profileId', profile.profileId);
        };

        /**
         * Remove the given profile from the select box and change to another valid profile.
         *
         * @param profile
         */
        vm.removeProfileFromSelect = function (profile) {
            // Find a profile and set it as current active profile
            $scope.activeProfile = $arrayUtil.findNextToItem(profile, $scope.profiles);
            $scope.profiles = $arrayUtil.remove(profile, $scope.profiles);

            // call our default change profile method
            $scope.changeProfile($scope.activeProfile, false);
        };

        /**
         * Show a confirm dialog to check if the user is sure to delete the given profile.
         */
        $scope.remove = function (profile) {
            // call alertify and show our confirm dialog
            var profileId = profile['profileId'];
            var profileAssociations = (profileId in vm.associations) ? vm.associations[profileId] : [];
            $rootScope.$broadcast('delete', profile, $scope.profiles, profileAssociations);
        };

        /**
         *
         * @param profile
         */
        vm.changeProfileInternal = function (profile) {
            var profileId = profile['profileId'];
            vm.lastActiveProfile = profile;

            if (-1 == profileId) {
                return;
            }

            // if the profile id is null, we handle it as a new profile
            if (null == profileId) {
                $scope.$broadcast('options', vm.defaultProfileData);
                vm.originalProfileData = vm.defaultProfileData;
                return;
            }

            // broadcast the id down to our controllers
            $scope.$broadcast('profileId', profileId);

            $scope.runningRequests++;

            // get the data for our profile
            DataService.loadProfile(profileId).then(function (result) {
                $scope.$broadcast('options', result);
                vm.originalProfileData = result;

                $scope.runningRequests--;
            });
        };

        /**
         * Check if the current profile has been edited.
         * - If the profile has been edited, notify the user about changes and check if he wants to discard the changes
         * - If the profile has not been edited, change the profile and load the data from the server or the default values.
         *
         * @param profile
         * @param checkForDirtyForm
         */
        $scope.changeProfile = function (profile, checkForDirtyForm) {
            // if the given profile is null or the profile did't change, prevent further execution
            if (null == profile || vm.lastActiveProfile == profile) {
                return;
            }

            // if our form is dirty, the user has to confirm the change
            if (!$scope.form.$pristine && checkForDirtyForm) {
                alertify.confirm(document['next_ad_int']['i18n']['discard-changes'], function () {
                    // access the last active profile, because the $scope.activeProfile already contains the new value
                    vm.lastActiveProfile['profileName'] = $valueHelper.findValue('profile_name', vm.originalProfileData);

                    // reset the for and set it clean
                    $scope.form.$setPristine();
                    $scope.$apply();

                    // change the profile to the new one
                    vm.changeProfileInternal(profile);
                }, function () {
                    // if the user cancels the action, we reset our profile state
                    $scope.activeProfile = vm.lastActiveProfile;
                    $scope.$apply();
                });

                return;
            }

            vm.changeProfileInternal(profile);
        };

        /**
         * Listen to the change-profile-name event to change the profile name in the select box
         */
        $scope.$on('change-profile-name', function (event, profileName) {
            if (null == $scope.activeProfile) {
                return;
            }

            $scope.activeProfile['profileName'] = profileName;
        });

        $scope.$on('remove-profile', function (event, profile) {
            vm.removeProfileFromSelect(profile);
        });

        $scope.$on('reset-form', function () {
            $scope.form.$setPristine();
        });

        /**
         * Method used for evaluating if a value is present
         *
         * @returns {boolean}
         */
        $scope.is_input_empty = function(input_value) {
            return (input_value == '' || !input_value);
        };

        $scope.save = function () {
            var data = DataService.mergeScopeOptions($scope);
            data["profile"] = $scope.activeProfile.profileId;

            PersistService.persistData(data).then(function (response) {
                $scope.$broadcast('validation', response);

                return response;
            }).then(function (result) {
                $scope.$emit('reset-form');

                if (void 0 == result['additionalInformation']) {
                    return;
                }

                var additionalData = result['additionalInformation'];
                $scope.activeProfile.profileId = additionalData.profileId;
                $scope.activeProfile.profileName = additionalData.profileName;

                NotificationService.showMessage(result);
            });
        };

        /**
         *
         */
        vm.loadData = function () {
            $scope.runningRequests++;

            DataService.loadInitData().then(function (result) {
                // set our profiles to our frontend
                $scope.profiles = [
                    document['next_ad_int']['none-profile']
                ].concat(result['profiles']);

                // select the first profile from the lsit
                var startIndex = ($scope.profiles.length == 1) ? 0 : 1;
                $scope.activeProfile = $arrayUtil.findFirst($scope.profiles.slice(startIndex, $scope.profiles.length));

                // assign the default data and associated profiles for later usage
                vm.defaultProfileData = result['defaultProfileData'];
                vm.associations = result['associatedProfiles'];

                // change the profile and broadcast our initial data downwards
                $scope.changeProfile($scope.activeProfile, false);
                $scope.$broadcast('ldapAttributes', result['ldapAttributes']);
                $scope.$broadcast('dataTypes', result['dataTypes']);
                $scope.$broadcast('permissionItems', result['permissionItems']);
                $scope.$broadcast('wpRoles', result['wpRoles']);

                $scope.runningRequests--;
            });
        };

        $timeout(vm.loadData);
    }
})();