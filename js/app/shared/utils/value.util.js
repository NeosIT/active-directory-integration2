var next_ad_int = next_ad_int || {};
next_ad_int.util = next_ad_int.util || {};

/**
 * @type {{findValue, findPermission}}
 */
next_ad_int.util.ValueHelper = (function (ArrayUtil) {
    return {
        findValue: findValue,
        findPermission: findPermission,
        findMessage: findMessage
    };

    /**
     * Find the item from the given {@code dataArray} using the given {@code name} and return the
     * option_value property.
     *
     * @param name
     * @param dataArray
     * @param defaultValue
     *
     * @returns {*}
     */
    function findValue(name, dataArray, defaultValue) {
        var item = ArrayUtil.findByKey(name, dataArray);

        if (null == item) {
            return defaultValue || null;
        }

        return item['option_value'];
    }

    /**
     * Find the item from the given {@code dataArray} using the given {@code name} and return the
     * option_permission property.
     *
     * @param name
     * @param dataArray
     * @returns {*}
     */
    function findPermission(name, dataArray) {
        var item = ArrayUtil.findByKey(name, dataArray);

        if (null == item) {
            return null;
        }

        return item['option_permission'];
    }

    /**
     * Find the message from the given {@code dataArray} using the given {@code name} and return it.
     *
     * @param name
     * @param dataArray
     *
     * @returns {*}
     */
    function findMessage(name, dataArray) {
        return ArrayUtil.findByKey(name, dataArray);
    }
})(next_ad_int.util.ArrayUtil);
