describe('ValueHelper', function () {
    var sut;

    beforeEach(function () {
        sut = angular.copy(adi2.util.ValueHelper);
    });

    it('#findValue returns null if item is null', function () {
        spyOn(adi2.util.ArrayUtil, 'findByKey').and.returnValue(null);

        var actual = sut.findValue('key', []);

        expect(actual).toBeNull();
    });

    it('#findValue returns option_value from found item', function () {
        spyOn(adi2.util.ArrayUtil, 'findByKey').and.returnValue({
            option_value: 'value'
        });

        var actual = sut.findValue('key', []);

        expect(actual).toBe('value');
    });

    it('#findPermission returns null if item is null', function () {
        spyOn(adi2.util.ArrayUtil, 'findByKey').and.returnValue(null);

        var actual = sut.findPermission('key', []);

        expect(actual).toBeNull();
    });

    it('#findPermission returns option_permission from found item', function () {
        spyOn(adi2.util.ArrayUtil, 'findByKey').and.returnValue({
            option_permission: 3
        });

        var actual = sut.findPermission('key', []);

        expect(actual).toBe(3);
    });

    it('#findMessage returns null if item is null', function () {
        spyOn(adi2.util.ArrayUtil, 'findByKey').and.returnValue(null);

        var actual = sut.findMessage('key', []);

        expect(actual).toBeNull();
    });

    it('#findMessage returns option_permission from found item', function () {
        spyOn(adi2.util.ArrayUtil, 'findByKey').and.returnValue('message');

        var actual = sut.findMessage('key', []);

        expect(actual).toBe('message');
    });
});