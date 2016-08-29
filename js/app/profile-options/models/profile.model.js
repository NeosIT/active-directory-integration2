var next_ad_int = next_ad_int || {};
next_ad_int.model = next_ad_int.model || {};

next_ad_int.model.Profile = (function () {
    function Profile(id, name) {
        this.profileId = id;
        this.profileName = name;
    }

    return Profile;    
})();