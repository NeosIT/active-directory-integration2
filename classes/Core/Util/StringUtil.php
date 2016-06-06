<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Core_Util_StringUtil')) {
	return;
}

/**
 * Core_Util_StringUtil provides helper functions for interacting with strings.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 *
 * @access public
 */
class Core_Util_StringUtil
{
	private function __construct()
	{
	}

	private function __clone()
	{
	}

	/**
	 * Split $string at $separator. If $string is an array then return this array.
	 *
	 * @param string $string
	 * @param char   $separator
	 *
	 * @return array
	 */
	public static function split($string, $separator)
	{
		if (is_array($string)) {
			return $string;
		}

		if ("\n" === $separator) {
			$string = str_replace("\r", '', $string);
		}

		return explode($separator, $string);
	}

	/**
	 * Split string at $separator and return only the items which are not empty and have at least one character.
	 *
	 * @param $string
	 * @param $separator
	 * @return array
	 */
	public static function splitNonEmpty($string, $separator) {
		$items = self::split($string, $separator);
		$r = array();

		foreach ($items as $item) {
			$trimmedItem = trim($item);

			if (!empty($trimmedItem)) {
				$r[] = $trimmedItem;
			}
		}

		return $r;
	}

	/**
	 * Split a text with \r\n and only returns non-empty lines
	 *
	 * @param string $value
	 *
	 * @return array
	 */
	public static function splitText($value)
	{
		$string = str_replace("\r", '', $value);
		$lines = explode("\n", $string);

		$array = array();
		foreach ($lines as $line) {
			$line = trim($line);
			if ($line) {
				$array[] = $line;
			}
		}

		return $array;
	}

	/**
	 * Concat array with the given separator
	 *
	 * @param array $array
	 * @param char  $separator
	 *
	 * @return string
	 */
	public static function concat($array, $separator)
	{
		if (is_string($array)) {
			return $array;
		}

		return implode($separator, $array);
	}

	/**
	 * Compare both strings case-insensitive
	 *
	 * @param string $string1
	 * @param string $string2
	 *
	 * @return bool
	 */
	public static function compareLowercase($string1, $string2)
	{
		$string1 = strtolower($string1);
		$string2 = strtolower($string2);

		return $string1 === $string2;
	}

	/**
	 * Explode the given string and trim every element. If the line is empty it will not be added.
	 *
	 * @param string $trim
	 * @param string $string
	 *
	 * @return array
	 */
	public static function trimmedExplode($trim, $string)
	{
		$parts = explode($trim, $string);
		$r = array();

		foreach ($parts as $part) {
			$part = trim($part);

			if (strlen($part) > 0) {
				$r[] = $part;
			}
		}

		return $r;
	}

	/**
	 * Convert the given binary string into a real string.
	 *
	 * @param $binaryString
	 *
	 * @return string
	 */
	public static function binaryToGuid($binaryString)
	{
		$hexString = bin2hex($binaryString);
		$result = '';

		for ($k = 1; $k <= 4; ++$k) {
			$result .= substr($hexString, 8 - 2 * $k, 2);
		}

		$result .= '-';

		for ($k = 1; $k <= 2; ++$k) {
			$result .= substr($hexString, 12 - 2 * $k, 2);
		}

		$result .= '-';

		for ($k = 1; $k <= 2; ++$k) {
			$result .= substr($hexString, 16 - 2 * $k, 2);
		}

		$result .= '-' . substr($hexString, 16, 4);
		$result .= '-' . substr($hexString, 20);

		return strtolower($result);
	}
	/**
	 * Convert object SID to domain SID
	 * 
	 * @param string $objectSid
	 * @return string
	 */
	public static function objectSidToDomainSid($objectSid) {
		$stringBuffer = "";

		if (is_string($objectSid) && !empty($objectSid))
		{
			$position = 0;
			$reversedString = strrev($objectSid);

			for ($i = 0; $i < strlen($reversedString); $i++) {
				if ($reversedString[$i] === "-") {
					$position = $i + 1;
					break;
				}
			}

			$stringBuffer = substr($reversedString, $position);
			$stringBuffer = strrev($stringBuffer);
		}

		return $stringBuffer;
	}
}