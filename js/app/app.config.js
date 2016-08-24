var $valueHelper = next_ad_int.util.ValueHelper;
var $arrayUtil = next_ad_int.util.ArrayUtil;

// inject $lazy into services and chain the AJAX response with .then($result) or use filters
app.value("$result", function (response) {
    return response.data;
});