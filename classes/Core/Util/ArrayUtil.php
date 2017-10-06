<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_Util_ArrayUtil')) {
	return;
}

/**
 * NextADInt_Core_Util_ArrayUtil provides helper functions for interacting with arrays.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 *
 * @access public
 */
class NextADInt_Core_Util_ArrayUtil
{
	private function __construct()
	{
	}

	private function __clone()
	{
	}

	/**
	 * Get the value $key from the $array. If the value does not exist, then return $fallback.
	 *
	 * @param string|int $key
	 * @param array      $array
	 * @param mixed      $fallback null
	 *
	 * @return mixed
	 */
	public static function get($key, $array, $fallback = null)
	{
		if (isset($array[$key])) {
			return $array[$key];
		}

		return $fallback;
	}

	/**
	 * Check if the given $needle is in the given $haystack.
	 *
	 * @param $needle
	 * @param $haystack
	 *
	 * @return bool
	 */
	public static function containsIgnoreCase($needle, $haystack)
	{
		$lowerHaystack = array_map(array('NextADInt_Core_Util_StringUtil', 'toLowerCase'), $haystack);
		$lowerNeedle = NextADInt_Core_Util_StringUtil::toLowerCase($needle);

		return in_array($lowerNeedle, $lowerHaystack);
	}

	/**
	 * Check if the array value behind the $key is equal to $compareValue.
	 *
	 * @param string $key
	 * @param string $compareValue
	 * @param array  $array
	 *
	 * @return bool
	 */
	public static function compareKey($key, $compareValue, $array)
	{
		return (isset($array[$key]) && $array[$key] === $compareValue);
	}

	/**
	 * Map the data from the given {@code $array} using the {@code $callback}.
	 * 
	 * @param       $callback
	 * @param array $array
	 *
	 * @return array
	 */
	public static function map($callback, array $array)
	{
		$result = array();

		foreach ($array as $key => $value) {
			$value = $callback($value, $key);
			$result[$key] = $value;
		}

		return $result;
	}

	/**
	 * Filter the given $array by using the $callback.
	 *
	 * @param \Closure $callback
	 * @param array    $array
	 * @param bool     $preserveKeys define if the key should be preserved or not.
	 *
	 * @return array
	 */
	public static function filter($callback, array $array, $preserveKeys = false)
	{
		$result = array();

		foreach ($array as $key => $value) {
			$bool = $callback($value, $key);

			if (!$bool) {
				continue;
			}

			if ($preserveKeys) {
				$result[$key] = $value;
				continue;
			}

			$result[] = $value;
		}

		return $result;
	}

	/**
	 * Find the first element from an array.
	 *
	 * @param      $array
	 * @param null $default
	 *
	 * @return mixed if no element was found return the $default value
	 */
	public static function findFirstOrDefault($array, $default = null)
	{
		if (!is_array($array) || 0 == sizeof($array)) {
			return $default;
		}

		return array_shift($array);
	}

	/**
	 * This function will return the amount of occurrences of array elements that start with a specific string.
	 *
	 * @param $haystack
	 * @param $needle
	 * @return int
	 */
	public static function countOccurencesStartsWith($haystack, $needle)
	{
		$occurrences = 0;

		foreach ($haystack as $part) {
			$part = strtolower($part);

			// check if first x characters are equal to given needle
			if(substr($part, 0, strlen($needle)) === $needle) {
				$occurrences ++;
			}

		}

		return $occurrences;
	}
}