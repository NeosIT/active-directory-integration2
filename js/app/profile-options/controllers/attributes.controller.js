(function () {
    app.controller('AttributesController', AttributesController);

    AttributesController.$inject = ['$scope', 'DataService'];

    function AttributesController($scope, DataService) {
        var vm = this;

        $scope.isSaveDisabled = false;

        $scope.$on('permissionItems', function (event, data) {
            $scope.permissionOptions = data;
        });

        $scope.optionsValues = [];
        $scope.ldapAttributes = [];
        $scope.dataTypes = [];
        $scope.newWordpressAttribute = "next_ad_int_";

        $scope.$on('dataTypes', function (event, data) {
            $scope.dataTypes = vm.parseDataTypes(data);
        });

        $scope.$on('ldapAttributes', function (event, data) {
            $scope.ldapAttributes = $scope.ldapAttributes.concat(vm.parseLdapAttributes(data));
        });

        $scope.attributeConfig = {
            // disable creation of new items
            create: true,
            maxItems: 1,
            //// attribute used for displaying the item
            labelField: 'display_name',
            //// attribute for retrieving the foreign key
            valueField: 'value',
            //// attribute for client side filtering in the remote result set
            searchField: "display_name"
        };

        $scope.dataTypeConfig = {
            // disable creation of new items
            create: false,
            maxItems: 1,
            //// attribute used for displaying the item
            labelField: 'display_name',
            //// attribute for retrieving the foreign key
            valueField: 'display_name',
            //// attribute for client side filtering in the remote result set
            searchField: "display_name"
        };
        
        $scope.$on('options', function (event, data) {

            $scope.optionsValues = data;

            $scope.option = {
                additional_user_attributes: JSON.parse('{"attributes":[]}'),
            };

            if ($valueHelper.findValue("domain_sid", data) == '') {
                $scope.isSaveDisabled = true;
            } else {
                $scope.isSaveDisabled = false;
            }

            $scope.permission = {
                additional_user_attributes: $scope.optionsValues["additional_user_attributes"]["option_permission"],
            };

            vm.parseAttributeStringToObjects($scope.optionsValues["additional_user_attributes"]);

        });

        $scope.$on('validation', function (event, data) {
            $scope.messages = {
                additional_user_attributes: $valueHelper.findMessage("additional_user_attributes", data)
            };
        });

        $scope.$on('verification', function (event, data) {
            $scope.isSaveDisabled = false;
        });

        $scope.addAttribute = function (newAdAttribute, newDataType, newWordpressAttribute, newDescription, newViewInUserProfile, newSyncToAd, newOverwriteWithEmptyValue) {


            // Prevents adding undefined as value for the checkboxes when adding them to the list. //TODO In eigenen Service/Methode auslagern.
            if (newViewInUserProfile != true) {
                newViewInUserProfile = false;
            }

            if (newSyncToAd != true) {
                newSyncToAd = false;
            }

            if (newOverwriteWithEmptyValue != true) {
                newOverwriteWithEmptyValue = false;
            }

            $scope.option.additional_user_attributes["attributes"].push({
                "adAttribute": newAdAttribute,
                "dataType": newDataType,
                "wordpressAttribute": newWordpressAttribute,
                "description": newDescription,
                "viewInUserProfile": newViewInUserProfile,
                "syncToAd": newSyncToAd,
                "overwriteWithEmptyValue": newOverwriteWithEmptyValue
            });

            vm.resetAddAttributeFields();
        };

        $scope.removeAttribute = function (index) {
            $scope.option.additional_user_attributes["attributes"].splice(index, 1);
        };

        vm.createAttributeDbString = function (objBuffer) {
            var stringBuffer = "";
            for (var i = 0; i < objBuffer.length; i++) {

                if (typeof objBuffer[i].description == 'undefined') {
                    objBuffer[i].description = "";
                }
                
                stringBuffer += objBuffer[i].adAttribute + ":" + objBuffer[i].dataType + ":" + objBuffer[i].wordpressAttribute + ":" + objBuffer[i].description + ":" + objBuffer[i].viewInUserProfile + ":" + objBuffer[i].syncToAd + ":" + objBuffer[i].overwriteWithEmptyValue + ";";
            }

            return stringBuffer;
        };

        vm.parseAttributeStringToObjects = function (attributeString) {
            if (attributeString["option_value"] == "") {
                return;
            }

            var attributes = attributeString["option_value"].split(";");
            for (var i = 0; i < attributes.length; i++) {
                var attribute = attributes[i].split(":");
                if (attribute[0] && attribute[2]) {
                    //parsing string "true" or "false to bool true or false
                    attribute[4] = vm.parseStringToBool(attribute[4]);
                    attribute[5] = vm.parseStringToBool(attribute[5]);
                    attribute[6] = vm.parseStringToBool(attribute[6]);

                    //check for custom attributes and add them to the list
                    vm.addCustomItemToAttributes(attribute[0], $scope.optionsValues["ldapAttributes"]);
                    vm.addCustomItemToDataTypes(attribute[1], $scope.optionsValues["dataTypes"]);


                    $scope.option.additional_user_attributes["attributes"].push({
                        "adAttribute": attribute[0],
                        "dataType": attribute[1],
                        "wordpressAttribute": attribute[2],
                        "description": attribute[3],
                        "viewInUserProfile": attribute[4],
                        "syncToAd": attribute[5],
                        "overwriteWithEmptyValue": attribute[6]
                    });
                }
            }
        };

        vm.parseStringToBool = function (string) {
            return string === "true";
        };

        vm.resetAddAttributeFields = function () {
            $scope.newAdAttribute = "";
            $scope.newDataType = "";
            $scope.newWordpressAttribute = "next_ad_int_";
            $scope.newDescription = "";
            $scope.newViewInUserProfile = false;
            $scope.newSyncToAd = false;
            $scope.newOverwriteWithEmptyValue = false;
        };

        vm.parseLdapAttributes = function (ldapAttributes) {
            return jQuery.map(ldapAttributes, function (value, key) {
                return {
                    'display_name': key + ' (' + value + ')',
                    'value': key
                }
            });
        };

        vm.parseDataTypes = function (dataTypes) {
            return jQuery.map(dataTypes, function (value) {
                return {
                    'display_name': value
                }
            });
        };

        //Todo write Service for addingCustomItems
        //TODO Add check if array already contains the item to prevent future mistakes

        vm.addCustomItemToAttributes = function (itemKey) {
            $scope.ldapAttributes.push({
                "display_name": itemKey + ' ( Custom Attribute )',
                "value": itemKey
            });
        };

        vm.addCustomItemToDataTypes = function (itemKey) {
            $scope.dataTypes.push({
                "display_name": itemKey
            });
        };

        $scope.containsErrors = function () {
            return (!$arrayUtil.containsOnlyNullValues($scope.messages));
        };

        $scope.getPreparedOptions = function () {
            var data = DataService.cleanOptions($scope.option);
            data["additional_user_attributes"] = vm.createAttributeDbString(data["additional_user_attributes"].attributes); //TODO Verbessern, daten werden im Feld "attributes" übergeben und nicht im Feld "option_value"
            return data;
        };

        /**
         * Added by sfi
         * This step is required, to add the input field vlaue to the list.
         * This way the input value will be saved without having to press the plus icon. If this method is not present in a controller,
         * the parent controller will be used (default).
         */
        $scope.save = function() {
            if($scope.newAdAttribute && $scope.newDataType && $scope.newWordpressAttribute != 'next_ad_int_' && $scope.newWordpressAttribute != '') {
                $scope.addAttribute($scope.newAdAttribute, $scope.newDataType, $scope.newWordpressAttribute, $scope.newDescription, $scope.newViewInUserProfile, $scope.newSyncToAd, $scope.newOverwriteWithEmptyValue);
                console.log('added', $scope.newAdAttribute, $scope.newDataType, $scope.newWordpressAttribute, $scope.newDescription, $scope.newViewInUserProfile, $scope.newSyncToAd, $scope.newOverwriteWithEmptyValue);
                vm.resetAddAttributeFields();
            }
            // call parent save
            $scope.$parent.save();
        };

        /**
         * Due to the complexity this has been made a separate function.
         * This will check, if all required values have been set.
         *
         * @returns {string|boolean}
         */
        $scope.is_input_complete = function() {
            return $scope.newAdAttribute && $scope.newDataType && $scope.newWordpressAttribute != 'next_ad_int_' && $scope.newWordpressAttribute != '';
        };
    }
})();