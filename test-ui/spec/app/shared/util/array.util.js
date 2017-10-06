describe('ArrayUtil', function () {
    var sut;

    beforeEach(function () {
        sut = angular.copy(next_ad_int.util.ArrayUtil);
    });

    it('#findNextToItem with no items should return null', function () {
        var actual = sut.findNextToItem('test', []);

        expect(actual).toBeNull();
    });

    it('#findNextToItem with one other item should return null', function () {
        var actual = sut.findNextToItem('test', ['abc']);

        expect(actual).toBeNull();
    });

    it('#findNextToItem with only the searched item should return null', function () {
        var actual = sut.findNextToItem('test', ['test']);

        expect(actual).toBeNull();
    });

    it('#findNextToItem with item before the searched item should return the item before the searched value', function () {
        var actual = sut.findNextToItem('test', ['hello', 'test']);

        expect(actual).toBe('hello');
    });

    it('#findNextToItem with item after the searched item should return the item after the searched value', function () {
        var actual = sut.findNextToItem('test', ['test', 'world']);

        expect(actual).toBe('world');
    });

    it('#findNextToItem with multiple items should return the item before the searched value', function () {
        var actual = sut.findNextToItem('test', ['hello', 'test', 'world']);

        expect(actual).toBe('hello');
    });

    it('#findFirst with no items should return null', function () {
        var actual = sut.findFirst([]);

        expect(actual).toBeNull();
    });

    it('#findFirst with one item should the item', function () {
        var actual = sut.findFirst(['test']);

        expect(actual).toBe('test');
    });

    it('#findFirst with multiple items should the first item', function () {
        var actual = sut.findFirst(['hello', 'world']);

        expect(actual).toBe('hello');
    });

    it('#findLast with no items should return null', function () {
        var actual = sut.findLast([]);

        expect(actual).toBeNull();
    });

    it('#findLast with one item should return the correct item', function () {
        var actual = sut.findLast(['test']);

        expect(actual).toBe('test');
    });

    it('#findLast with multiple items should return the correct item', function () {
        var actual = sut.findLast(['hello', 'world']);

        expect(actual).toBe('world');
    });

    it('#findNextToLast should return null if array has no values', function () {
        var actual = sut.findNextToLast([]);

        expect(actual).toBeNull();
    });

    it('#findNextToLast should return null if array has one value', function () {
        var actual = sut.findNextToLast(['hello']);

        expect(actual).toBeNull();
    });

    it('#findNextToLast should return the correct value', function () {
        var actual = sut.findNextToLast(['hello', 'world']);

        expect(actual).toBe('hello');
    });

    it('#remove should remove the element from array', function () {
        var actual = sut.remove('test', ['test']);

        expect(actual.length).toBe(0);
    });

    it('#remove should recalculate array index', function () {
        var actual = sut.remove('b', ['a', 'b', 'c']);

        expect(actual[0]).toBe('a');
        expect(actual[1]).toBe('c');
        expect(actual[2]).toBeUndefined();
    });

    it('#findByKey without an existing value should return null', function () {
        var actual = sut.findByKey('', []);

        expect(actual).toBeNull();
    });

    it('#findByKey with an existing value should return the item', function () {
        var actual = sut.findByKey('key', {'key': 'test'});

        expect(actual).toBe('test');
    });

    it('#containsOnlyNullValues should return true on empty array', function () {
        var actual = sut.containsOnlyNullValues([]);

        expect(actual).toBeTruthy();
    });

    it('#containsOnlyNullValues should return true on array with only null values', function () {
        var actual = sut.containsOnlyNullValues([null, null]);

        expect(actual).toBeTruthy();
    });

    it('#containsOnlyNullValues should return false on array with non null value', function () {
        var actual = sut.containsOnlyNullValues([null, 'test']);

        expect(actual).toBeFalsy();
    });

    it('#containsOnlyNullValues can handle objects', function () {
        var actual = sut.containsOnlyNullValues({
            propA: null,
            propB: 'test'
        });

        expect(actual).toBeFalsy();
    });
});