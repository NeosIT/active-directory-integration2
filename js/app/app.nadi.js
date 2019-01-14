// ADI-634 We had to rename this file since some firewalls block files with names containing strings like 'config' or 'password'

var $valueHelper = next_ad_int.util.ValueHelper;
var $arrayUtil = next_ad_int.util.ArrayUtil;

// inject $lazy into services and chain the AJAX response with .then($result) or use filters
app.value("$result", function (response) {
    return response.data;
});