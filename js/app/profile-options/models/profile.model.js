var adi2 = adi2 || {};
adi2.model = adi2.model || {};

adi2.model.Profile = (function () {
    function Profile(id, name) {
        this.profileId = id;
        this.profileName = name;
    }

    return Profile;    
})();