(function () {
    app.controller('PermissionController', PermissionController);

    PermissionController.$inject = ['$scope', 'PersistService', 'ListService', 'DataService'];

    function PermissionController($scope, PersistService, ListService, DataService) {
        var vm = this;

        $scope.isSaveDisabled = false;

        $scope.$on('permissionItems', function (event, data) {
            $scope.permissionOptions = data;
        });

        $scope.new_authorization_group = '';

        $scope.wpRoles = [];

        $scope.wpRolesConfig = {
            // disable creation of new items
            create: true,
            maxItems: 1,
            //// attribute used for displaying the item
            labelField: 'display_name',
            //// attribute for retrieving the foreign key
            valueField: 'display_name',
            //// attribute for client side filtering in the remote result set
            searchField: "display_name"
        };

        $scope.$on('options', function (event, data) {
            $scope.option = {
                authorize_by_group: $valueHelper.findValue("authorize_by_group", data),
                authorization_group: $valueHelper.findValue("authorization_group", data, '').split(";"),
                role_equivalent_groups: JSON.parse('{"groups":[]}'),
                clean_existing_roles: $valueHelper.findValue("clean_existing_roles", data)
            };

            if ($valueHelper.findValue("domain_sid", data) == '') {
                $scope.isSaveDisabled = true;
            } else {
                $scope.isSaveDisabled = false;
            }

            $scope.permission = {
                authorize_by_group: $valueHelper.findPermission("authorize_by_group", data),
                authorization_group: $valueHelper.findPermission("authorization_group", data),
                role_equivalent_groups: $valueHelper.findPermission("role_equivalent_groups", data),
                clean_existing_roles: $valueHelper.findPermission("clean_existing_roles", data)
            };

            vm.parseRoleEquivalentStringToObjects(data["role_equivalent_groups"]);
        });

        $scope.$on('validation', function (event, data) {
            $scope.messages = {
                authorize_by_group: $valueHelper.findMessage("authorize_by_group", data),
                authorization_group: $valueHelper.findMessage("authorization_group", data),
                role_equivalent_groups: $valueHelper.findMessage("role_equivalent_groups", data),
                clean_existing_roles: $valueHelper.findMessage("clean_existing_roles", data)
            };
        });

        $scope.$on('wpRoles', function (event, data) {
            var result = [];

            for (var idx in data) {
                result.push({display_name: idx, value: data[idx]});
            }

            $scope.wpRoles = result;
        });

        $scope.$on('verification', function (event, data) {
            $scope.isSaveDisabled = false;
        });

        $scope.save = function () {
            var data = JSON.parse(angular.toJson($scope.option));

            data["authorization_group"] = ListService.parseListArrayToString($scope.option.authorization_group);
            data["role_equivalent_groups"] = vm.createRoleEquivalentDbString(data["role_equivalent_groups"].groups);

            PersistService.persist($scope, data, vm.profileId);
        };

        $scope.remove_authorization_group = function (index) {
            $scope.option.authorization_group = ListService.removeListItem(index, $scope.option.authorization_group);
        };

        $scope.add_authorization_group = function (newItem) {
            $scope.option.authorization_group = ListService.addListItem(newItem, $scope.option.authorization_group);
            $scope.new_authorization_group = "";
        };

        $scope.addTableItem = function (newItemField1, newItemField2) {
            $scope.option.role_equivalent_groups["groups"].push({
                "securityGroup": newItemField1,
                "wordpressRole": newItemField2
            });
            $scope.newItemField1 = "";
            $scope.newItemField2 = "";
        };

        $scope.removeTableItem = function (index) {
            $scope.option.role_equivalent_groups["groups"].splice(index, 1);
        };

        vm.parseRoleEquivalentStringToObjects = function (roleEquivalentString) {

            if (roleEquivalentString["option_value"] == "") {
                return;
            }

            var groups = roleEquivalentString["option_value"].split(";");
            for (var i = 0; i < groups.length; i++) {
                var group = groups[i].split("=");

                if (group[0] && group[1]) {
                    $scope.option.role_equivalent_groups["groups"].push({
                        "securityGroup": group[0],
                        "wordpressRole": group[1]
                    });

                    vm.addCustomItemToWordpressRoles(group[1]);
                }
            }
        };

        vm.createRoleEquivalentDbString = function (objBuffer) {
            var stringBuffer = "";

            for (var i = 0; i < objBuffer.length; i++) {
                if (objBuffer[i].securityGroup && objBuffer[i].wordpressRole) {
                    stringBuffer += objBuffer[i].securityGroup + "=" + objBuffer[i].wordpressRole + ";";
                }
            }

            return stringBuffer;
        };

        //TODO use same system for attributes; move to own AngularJS service
        vm.addCustomItemToWordpressRoles = function (itemKey) {
            var flag = false;

            for (key in $scope.wpRoles) {
                if ($scope.wpRoles[key]["display_name"] == itemKey) {
                    flag = true;
                }
            }

            if (!flag) {
                $scope.wpRoles.push({
                    "display_name": itemKey,
                    "value": itemKey.toLowerCase()
                });
            }
        };

        $scope.getPreparedOptions = function () {
            var data = DataService.cleanOptions($scope.option);
            data['authorization_group'] = ListService.parseListArrayToString($scope.option['authorization_group']);
            data['role_equivalent_groups'] = vm.createRoleEquivalentDbString($scope.option['role_equivalent_groups'].groups);
            return data;
        };

        $scope.containsErrors = function () {
            return (!$arrayUtil.containsOnlyNullValues($scope.messages));
        };

        /**
         * Added by sfi
         * This step is required, to add the input field vlaue to the list.
         * This way the input value will be saved without having to press the plus icon. If this method is not present in a controller,
         * the parent controller will be used (default).
         */
        $scope.save = function() {
            // check if the input field is not empty
            if($scope.new_authorization_group != '') {
                // add the input field value to the list of objects to be saved
                ListService.addListItem($scope.new_authorization_group, $scope.option.authorization_group);
                $scope.new_authorization_group = '';
            }

            if($scope.newItemField1 != '' && $scope.newItemField2) {
                console.log($scope.newItemField2);
                $scope.addTableItem($scope.newItemField1, $scope.newItemField2);
                $scope.newItemField1 = '';
                $scope.newItemField2 = '';
            }
            // call parent save
            $scope.$parent.save();
        };
    }
})();