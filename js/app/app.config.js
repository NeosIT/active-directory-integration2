var $valueHelper = adi2.util.ValueHelper;
var $arrayUtil = adi2.util.ArrayUtil;

// inject $lazy into services and chain the AJAX response with .then($result) or use filters
app.value("$result", function (response) {
    return response.data;
});