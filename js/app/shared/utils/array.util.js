var next_ad_int = next_ad_int || {};
next_ad_int.util = next_ad_int.util || {};

/**
 * @type {{findItemReference, findNextToItem, findFirst, findLast, findNextToLast, remove, findByKey, containsOnlyNullValues}}
 */
next_ad_int.util.ArrayUtil = (function () {
    return {
        findItemReference: findItemReference,
        findNextToItem: findNextToItem,
        findFirst: findFirst,
        findLast: findLast,
        findNextToLast: findNextToLast,
        remove: remove,
        findByKey: findByKey,
        containsOnlyNullValues: containsOnlyNullValues
    };

    /**
     *
     * @param item
     * @param array
     *
     * @returns {*}
     */
    function findItemReference(item, array) {
        if (0 == array.length) {
            return null;
        }

        return array[array.indexOf(item)];
    }

    /**
     * Find the next item to the given element from the array.
     *
     * @param item
     * @param array
     *
     * @returns {*}
     */
    function findNextToItem(item, array) {
        if (1 >= array.length) {
            return null;
        }

        var idx = array.indexOf(item);
        var nextIdx = (idx == 0) ? idx + 1 : idx - 1;

        if (0 > nextIdx) {
            return null;
        }

        return array[nextIdx];
    }

    /**
     * Return the first entry from an array.
     *
     * @param array
     *
     * @returns {*}
     */
    function findFirst(array) {
        if (0 == array.length) {
            return null;
        }

        return array[0];
    }

    /**
     * Return the last entry from an array.
     *
     * @param array
     *
     * @returns {*}
     */
    function findLast(array) {
        var lastIdx = array.length - 1;

        if (0 > lastIdx) {
            return null;
        }

        return array[lastIdx];
    }

    /**
     * Find the element next to the last element from the array.
     *
     * @param array
     *
     * @returns {*}
     */
    function findNextToLast(array) {
        if (1 >= array.length) {
            return null;
        }

        var idx = array.length - 2;
        return array[idx];
    }

    /**
     * Removes the given {@code item} from the given {@code array} and recalculate the array.
     *
     * @param item
     * @param array
     *
     * @returns {*}
     */
    function remove(item, array) {
        var idx = array.indexOf(item);

        if (-1 == idx) {
            return array;
        }

        array.splice(idx, 1);
        return array;
    }

    /**
     * Find an item from the given {@code objectOrArray} using the given {@code key}.
     *
     * @param key
     * @param objectOrArray
     *
     * @returns {*}
     */
    function findByKey(key, objectOrArray) {
        for (var idx in objectOrArray) {
            if (key == idx) {
                return objectOrArray[idx];
            }
        }

        return null;
    }

    /**
     * Check if the given {@code array} contains only null values.
     *
     * @param objectOrArray
     *
     * @returns {boolean}
     */
    function containsOnlyNullValues(objectOrArray) {
        if (typeof objectOrArray == 'undefined' || 0 == objectOrArray.length) {
            return true;
        }

        for (var idx in objectOrArray) {
            if (null != objectOrArray[idx]) {
                return false;
            }
        }

        return true;
    }
})();