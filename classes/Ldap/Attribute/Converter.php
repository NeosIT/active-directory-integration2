<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Ldap_Attribute_Converter')) {
	return;
}

/**
 * Adi_Option_ValueConverter can converter some special Active Directory attribute values to their WordPress counterparts.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 * @access  private
 */
class NextADInt_Ldap_Attribute_Converter
{
	private function __construct()
	{
	}

	private function __clone()
	{
	}

	/**
	 * Converts values of user attributes.
	 *
	 * @param string $type one of string, integer, bool, time, timestamp, octet or cn
	 * @param mixed $value
	 *
	 * @return bool|int|string
	 */
	public static function formatAttributeValue($type, $value)
	{
		switch ($type) {
			case 'string':
				return (string)$value;
			case 'integer':
				return (int)$value;
			case 'bool':
				return (bool)$value;
			case 'time':
				return self::formatTime($value);
			case 'timestamp':
				return self::formatTimestamp($value);
			case 'octet':
				return base64_encode($value);
			case 'cn':
				return self::getCnFromDistinguishedName($value);
		}

		return $value;
	}

	/**
	 * Parse ASN.1 GeneralizedTime to string.
	 * @param string $value
	 * @return string
	 */
	public static function formatTime($value) {
		$year = substr($value, 0, 4);
		$month = substr($value, 4, 2);
		$date = substr($value, 6, 2);
		$hour = substr($value, 8, 2);
		$minute = substr($value, 10, 2);
		$second = substr($value, 12, 2);
		$offset = substr($value, -1);

		date_default_timezone_set('UTC');
		$timestamp = mktime($hour, $minute, $second, $month, $date, $year);

		if ('Z' === $offset) {
			$offset = get_option('gmt_offset', 0) * 3600;
		} else {
			$offset = 0;
		}

		$dateFormat = get_option('date_format', 'Y-m-d');
		$timeFormat = get_option('time_format', 'H:i:s');
		$format = $dateFormat . ' / ' . $timeFormat;

		return date_i18n($format, $timestamp + $offset, true);
	}

	/**
	 * Parse a Windows timestamp to string.
	 * @param int $value
	 * @return string
	 */
	public static function formatTimestamp($value) {
		$gmtOffset = get_option('gmt_offset', 0);
		$dateFormat = get_option('date_format', 'Y-m-d');
		$timeFormat = get_option('time_format', 'H:i:s');

		$timestamp = ($value / 10000000) - 11644473600;
		$timestamp = $timestamp + $gmtOffset * 3600;
		$format = $dateFormat . ' / ' . $timeFormat;

		return date_i18n($format, $timestamp, true);
	}

	/**
	 * Get the cn from distinguished name
	 * @param string $value
	 * @return string
	 */
	public static function getCnFromDistinguishedName($value) {
		$pos = stripos($value, 'cn=');
		if ($pos === false) {
			return '';
		}

		$start = $pos + 3;
		$valueLength = strlen($value);
		for ($i = $start; $i < $valueLength; $i++) {
			// search for unescaped commas
			if (',' === $value[$i] && '\\' !== $value[$i - 1]) {
				break;
			}
		}

		return stripslashes(substr($value, $start, $i - $start));
	}
}