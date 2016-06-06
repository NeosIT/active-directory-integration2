(function () {
    app.service('ListService', ListService);

    ListService.$inject = [];

    function ListService() {
        var vm = this;

        vm.removeListItem = function (index, listArray) {
            listArray.splice(index, 1);
            return listArray;
        };

        vm.addListItem = function (newItem, listArray) {
            if (newItem) {
                listArray.push(newItem);
                return listArray;
            }

            return listArray;
        };

        vm.parseListArrayToString = function (listArray) {
            var stringBuffer = "";
            var arrayLength = listArray.length;

            for (var i = 0; i < arrayLength; i++) {
                var value = listArray[i];

                if (value != '') {
                    if (i + 1 < arrayLength) {
                        stringBuffer += value + ";";
                        continue;
                    }

                    stringBuffer += value;
                }
            }

            return stringBuffer;
        };

        return vm;
    }
})();