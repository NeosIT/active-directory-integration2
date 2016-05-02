document['adi2'] = document['adi2'] || {};
document['adi2']['model'] = document['adi2']['model'] || {};
document['adi2']['model']['configuration'] = document['adi2']['model']['configuration'] || {};

document['adi2']['model']['configuration']['SinglePropertyValue'] = (function () {
    function SinglePropertyValue() {
        this.option_value = '';
        this.option_permission = '';
    }
    
    return SinglePropertyValue;
})();

document['adi2']['model']['configuration']['General'] = (function (SinglePropertyValue) {
    function General() {
        this.profile_name = new SinglePropertyValue();
        this.profile_is_active = new SinglePropertyValue();
        this.is_active = new SinglePropertyValue();
        this.show_menu_test_authentication = new SinglePropertyValue();
        this.show_menu_sync_to_ad = new SinglePropertyValue();
        this.show_menu_sync_to_wordpress = new SinglePropertyValue();
    }
    
    return General;
})(document['adi2']['model']['configuration']['SinglePropertyValue']);