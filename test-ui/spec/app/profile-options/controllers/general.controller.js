describe('GeneralController', function () {
    var sut, $rootScope, $scope, PersistenceService;

    beforeEach(angular.mock.module('next_ad_int-module'));

    beforeEach(inject(function (_$rootScope_, $controller, _PersistService_) {
        $rootScope = _$rootScope_;
        $scope = _$rootScope_.$new();

        sut = $controller('GeneralController', {
            $scope: $scope
        });

        PersistenceService = _PersistService_;
    }));

    it('should watch $scope.profile_name and emit the value to the parent scopes', function () {
        var name = null;

        $rootScope.$on('change-profile-name', function (event, value) {
            name = value;
        });

        $scope.option['profile_name'] = 'name';
        $scope.$apply();

        expect(name).toBe('name');
    });
});