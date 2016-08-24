describe('DataService', function () {
    var sut, $httpBackend, $q, $rootScope;

    beforeEach(angular.mock.module('next_ad_int-module'));

    beforeEach(inject(function (_$httpBackend_, _$q_, _DataService_, _$rootScope_) {
        sut = _DataService_;
        $httpBackend = _$httpBackend_;
        $q = _$q_;
        $rootScope = _$rootScope_;
    }));

    it('#loadProfile should call $http.post() with specific url and parameters and return the object', function () {
        var obj = {id: 1, text: 'test'};
        var result = null;

        // define promise
        $httpBackend.when('POST', '../admin-ajax.php').respond(obj);

        // invoke method
        sut.loadProfile(1).then(function (response) {
            result = response;
        });

        // expect post call with parameters
        $httpBackend.expectPOST('../admin-ajax.php', {
            action: 'next_ad_int_profile_options',
            security: document.next_ad_int.security,
            profileId: 1,
            subAction: 'getProfileOptionsValues'
        });
        $httpBackend.flush();

        expect(result).toEqual(obj);
    });

    it('#initialize should call $http.post() with specific url and parameters and return the object', function () {
        var obj = {id: 1, text: 'test'};
        var result = null;

        // define promise
        $httpBackend.when('POST', '../admin-ajax.php').respond(obj);

        // invoke method
        sut.initialize().then(function (response) {
            result = response;
        });

        // expect post call with parameters
        $httpBackend.expectPOST('../admin-ajax.php', {
            action: 'next_ad_int_profile_options',
            security: document.next_ad_int.security,
            init: 1,
            subAction: 'getProfileOptionsValues'
        });
        $httpBackend.flush();

        expect(result).toEqual(obj);
    });

    it('#mergeScopeOptions should return result form multiple child scopes', function () {
        var expected = {
            options: {
                domain_controllers: {
                    option_value: 'abc;def',
                    option_permission: 3
                },
                port: {
                    option_value: 386,
                    option_permission: 3
                }
            }
        };

        var $parentScope = $rootScope.$new();
        var $domainControllerChildScope = $parentScope.$new();
        $domainControllerChildScope.option = {
            domain_controllers: expected.options.domain_controllers.option_value
        };
        $domainControllerChildScope.permission = {
            domain_controllers: expected.options.domain_controllers.option_permission
        };

        var $portChildScope = $parentScope.$new();
        $portChildScope.option = {
            port: expected.options.port.option_value
        };
        $portChildScope.permission = {
            port: expected.options.port.option_permission
        };

        var actual = sut.mergeScopeOptions($parentScope);

        expect(actual).toEqual(expected);
    });

    it('#mergeScopeOptions should use getPreparedOptions if provided by scope', function () {
        var expected = {
            options: {
                domain_controllers: {
                    option_value: 'abc;def',
                    option_permission: 3
                },
                port: {
                    option_value: 123,
                    option_permission: 3
                }
            }
        };

        var $parentScope = $rootScope.$new();
        var $domainControllerChildScope = $parentScope.$new();
        $domainControllerChildScope.option = {
            domain_controllers: expected.options.domain_controllers.option_value
        };
        $domainControllerChildScope.permission = {
            domain_controllers: expected.options.domain_controllers.option_permission
        };

        var $portChildScope = $parentScope.$new();
        $portChildScope.option = {
            port: expected.options.port.option_value,
            getPreparedOptions: function () {
                return {
                    port: 123
                }
            }
        };
        $portChildScope.permission = {
            port: expected.options.port.option_permission
        };

        var actual = sut.mergeScopeOptions($parentScope);

        expect(actual).toEqual(expected);
    });
});