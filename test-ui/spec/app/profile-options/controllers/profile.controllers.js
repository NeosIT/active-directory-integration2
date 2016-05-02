describe('ProfileController', function () {
    var sut, $scope, $timeout, $window, alertify, TemplateService, DataService, PersistService, BrowserService, ArrayUtil, deferred;

    beforeEach(angular.mock.module('adi2-module'));

    beforeEach(inject(function ($rootScope, $controller, _$q_, _$timeout_, _$window_, _alertify_, _DataService_, _PersistService_, formDirective, _TemplateService_, _BrowserService_) {
        $window = _$window_;

        DataService = _DataService_;
        PersistService = _PersistService_;
        TemplateService = _TemplateService_;
        BrowserService = _BrowserService_;
        alertify = _alertify_;

        $scope = $rootScope.$new();
        sut = $controller('ProfileController', {
            $scope: $scope
        });

        $scope.form = $controller(formDirective[0].controller, {
            $scope: $rootScope.$new(),
            $element: angular.element('<form></form>'),
            $attrs: {}
        });

        ArrayUtil = adi2.util.ArrayUtil;
        deferred = _$q_.defer();
    }));

    it('#$scope.create adds new profile to $scope', function () {
        var expectedProfile = new adi2.model.Profile(null, 'new-profile');

        spyOn($scope, '$broadcast');
        spyOn(angular, 'copy').and.returnValue(expectedProfile);

        expect($scope.profiles.length).toBe(0);
        expect($scope.activeProfile).toBeNull();
        expect(sut.lastActiveProfile).toBeNull();

        $scope.create();

        expect($scope.profiles.length).toBe(1);
        expect($scope.activeProfile).toEqual(expectedProfile);
        expect(sut.lastActiveProfile).toEqual(expectedProfile);
        expect($scope.$broadcast).toHaveBeenCalledTimes(2);
    });

    it('#removeProfileFromSelect triggers expected methods', function () {
        var oldProfile = {profileName: 'profile1'};
        var newProfile = {profileName: 'profile2'};

        $scope.profiles = [oldProfile, newProfile];

        spyOn(ArrayUtil, 'findNextToItem').and.returnValue(newProfile);
        spyOn(ArrayUtil, 'remove').and.returnValue($scope.profiles);
        spyOn($scope, 'changeProfile');

        sut.removeProfileFromSelect(oldProfile);

        expect(ArrayUtil.findNextToItem).toHaveBeenCalledWith(oldProfile, $scope.profiles);
        expect(ArrayUtil.remove).toHaveBeenCalledWith(oldProfile, $scope.profiles);
        expect($scope.changeProfile).toHaveBeenCalledWith(newProfile, false);
    });

    it('#removeProfile with new profile should not trigger service', function () {
        var profile = {profileId: null, profileName: 'test'};
        $scope.activeProfile = profile;

        spyOn(sut, 'removeProfileFromSelect');
        spyOn(PersistService, 'removeProfile');

        sut.removeProfile(profile);
        $scope.$apply();

        expect(sut.removeProfileFromSelect).toHaveBeenCalledWith(profile);
        expect(PersistService.removeProfile).not.toHaveBeenCalled();
    });

    it('#removeProfile with existing profile should trigger service', function () {
        var profile = {profileId: 1, profileName: 'test'};
        $scope.activeProfile = profile;

        var promise = deferred.promise;
        deferred.resolve(null);

        spyOn(PersistService, 'assignProfile').and.returnValue(promise);
        spyOn(PersistService, 'removeProfile').and.returnValue(promise);
        spyOn(BrowserService, 'reload');

        sut.removeProfile(profile);
        $scope.$apply();

        expect(PersistService.assignProfile).toHaveBeenCalled();
        expect(PersistService.removeProfile).toHaveBeenCalledWith(profile.profileId);
        expect(BrowserService.reload).toHaveBeenCalled();
    });

    it('#changeProfileInternal with new profile should not trigger service', function () {
        var profile = {profileId: null, profileName: 'test'};
        sut.defaultProfileData = {profileId: null, profileName: 'new'};

        expect(sut.originalProfileData).toBeNull();

        spyOn($scope, '$broadcast');

        sut.changeProfileInternal(profile);

        expect($scope.$broadcast).toHaveBeenCalledWith('options', sut.defaultProfileData);
        expect(sut.originalProfileData).toEqual(sut.defaultProfileData);
    });

    it('#changeProfileInternal with existing profile should trigger service', function () {
        var profile = {profileId: 1, profileName: 'test'};
        var data = {'domain_controllers': 'test'};
        sut.defaultProfileData = {profileId: null, profileName: 'new'};

        var promise = deferred.promise;
        deferred.resolve(data);

        expect(sut.originalProfileData).toBeNull();

        spyOn($scope, '$broadcast');
        spyOn(DataService, 'loadProfile').and.returnValue(promise);

        sut.changeProfileInternal(profile);
        $scope.$apply();

        expect($scope.$broadcast).toHaveBeenCalledWith('profileId', 1);
        expect($scope.$broadcast).toHaveBeenCalledWith('options', data);
        expect(sut.originalProfileData).toEqual(data);
    });
});