document['next_ad_int'] = document['next_ad_int'] || {};
document['next_ad_int']['model'] = document['next_ad_int']['model'] || {};
document['next_ad_int']['model']['configuration'] = document['next_ad_int']['model']['configuration'] || {};

document['next_ad_int']['model']['configuration']['SinglePropertyValue'] = (function () {
    function SinglePropertyValue() {
        this.option_value = '';
        this.option_permission = '';
    }
    
    return SinglePropertyValue;
})();

document['next_ad_int']['model']['configuration']['General'] = (function (SinglePropertyValue) {
    function General() {
        this.profile_name = new SinglePropertyValue();
        this.profile_is_active = new SinglePropertyValue();
        this.is_active = new SinglePropertyValue();
        this.show_menu_test_authentication = new SinglePropertyValue();
        this.show_menu_sync_to_ad = new SinglePropertyValue();
        this.show_menu_sync_to_wordpress = new SinglePropertyValue();
    }
    
    return General;
})(document['next_ad_int']['model']['configuration']['SinglePropertyValue']);