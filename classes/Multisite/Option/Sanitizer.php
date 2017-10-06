<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Option_Sanitizer')) {
	return;
}

/**
 * This class sanitize option values.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class NextADInt_Multisite_Option_Sanitizer
{
	/**
	 * This method sanitize a value.
	 * The first element of $array must contain the method name like 'boolean', 'integer', 'string', 'email' etc.
	 * The other element are parameters for the function (for example 'integerRange' needs a $min and a $max)
	 * Sometime the last element can be an array with a sanitizer function.
	 *
	 * @param mixed $value not sanitized value
	 * @param array $array array('methodForSanitizing', parameters for the method...)
	 * @param mixed $optionElement option metadata form NextADInt_Adi_Configuration_Options
	 * @param bool $saveOption will this value be saved or be requested
	 *
	 * @return bool|mixed
	 * @throws Exception
	 */
	public function sanitize($value, $array, $optionElement, $saveOption = false)
	{
		if (sizeof($array) < 1) {
			return false;
		}

		// the first value from $array is the method name
		$methodName = array_shift($array);
		// all other values from $array are params
		$userParams = $array;

		return call_user_func_array(array($this, $methodName), array($value, $userParams, $optionElement, $saveOption));
	}

	/**
	 * $value can be 'false' 'true' '0' '1' 0 1 false true '0.0' '1.0' 0.0 1.0 and will be converted to false or true. If it fails, the default value will used.
	 * This method does not use extra parameters.
	 * You can call it by sanitize($value, array('boolean'), $optionElement)
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 * @throws Exception
	 */
	function boolean($value)
	{
		//TODO bessere Lösung überlegen
		if (is_array($value)) {
			$value = $value["option_value"];
		}

		if (NextADInt_Core_Util_StringUtil::toLowerCase($value) === 'false') {
			return false;
		}

		return (bool)$value;
	}

	/**
	 * Do not call this method directly. Use the method sanitize instead.
	 * sanitize($valueToSanitize, array('email'), $optionMetaData)
	 *
	 * @param mixed $value
	 * @param mixed $userParams @deprecated
	 * @param mixed $optionData
	 *
	 * @return null|string
	 */
	function email($value, $userParams, $optionData)
	{
		$value = sanitize_email($value);
		if (is_email($value)) {
			return $value;
		}

		return $this->getDefaultValue($optionData);
	}

	/**
	 * Do not call this method directly. Use the method sanitize instead.
	 * sanitize($valueToSanitize, array('integerRange', $optionalMin, $optionalMax), NextADInt_Adi_Configuration_Options::get(...))
	 *
	 * This method prevent too low or too high numbers. In these cases, the default value will be returned.
	 *
	 * @param string $value
	 * @param array $userParams
	 * @param array $optionData
	 *
	 * @return int|null
	 */
	function integerRange($value, $userParams, $optionData)
	{
        $min = NextADInt_Core_Util_ArrayUtil::get(0, $userParams, '');
        $max = NextADInt_Core_Util_ArrayUtil::get(1, $userParams, '');

        $value = $this->integer($value, null, null);

        // $value must be an integer
        if (!is_integer($value)) {
            return $this->getDefaultValue($optionData);
        }

        // $value is too low
        if (is_integer($min) && $value < $min) {
            return $this->getDefaultValue($optionData);
        }

        // $value is too high
        if (is_integer($max) && $value > $max) {
            return $this->getDefaultValue($optionData);
        }

        return $value;
	}

	/**
	 * Do not call this method directly. Use the method sanitize instead.
	 * sanitize($valueToSanitize, array('integer'), NextADInt_Adi_Configuration_Options::get(...))
	 *
	 * This method tries to convert $value to a number. If it fails, the default value will used.
	 *
	 * @param string $value
	 * @param mixed $userParams @deprecated
	 * @param mixed $optionData
	 *
	 * @return int|null
	 */
	function integer($value, $userParams, $optionData)
	{
		// prevent converting true to "1"
		if (is_string($value)) {
			$value = trim($value);
		}

		if (is_numeric($value)) {
			return intval($value);
		}

		return $this->getDefaultValue($optionData);
	}

	/**
	 * Do not call this method directly. Use the method sanitize instead.
	 * This method splits $value into pieces and call a sanitizer for sanitizing each the piece.
	 * sanitize($value, array('accumulation', ';', array('string', true, true)), $optionElement)
	 *
	 * @param mixed $value
	 * @param array $userParams
	 * @param array $optionData
	 *
	 * @return null|string
	 * @throws Exception
	 */
	function accumulation($value, $userParams, $optionData)
	{
		$separator = NextADInt_Core_Util_ArrayUtil::get(0, $userParams, ';');
		$subMethod = NextADInt_Core_Util_ArrayUtil::get(1, $userParams);

		$parts = explode($separator, $value);
		$results = array();
		foreach ($parts as $part) {
			$value = $this->sanitize($part, $subMethod, null);
			if ($value !== null) {
				$results[] = $value;
			}
		}

		$result = implode($separator, $results);
		if ($result) {
			return $result;
		}

		return $this->getDefaultValue($optionData);
	}

	/**
	 * Do not call this method directly. Use the method sanitize instead.
	 * This method sanitize a string with a value assignment.
	 * sanitize($value, array('valueAssignment', '=', true, false), $optionElement)
	 *
	 * @access private
	 * @param mixed $value
	 * @param mixed $userParams
	 * @param mixed $optionData
	 * @return null|string
	 */
	function valueAssignment($value, $userParams, $optionData)
	{
		$separator = NextADInt_Core_Util_ArrayUtil::get(0,$userParams, '=');
		$leftLowercase = NextADInt_Core_Util_ArrayUtil::get(1, $userParams, false);
		$rightLowercase = NextADInt_Core_Util_ArrayUtil::get(2, $userParams, true);

		$parts = explode($separator, $value);
		if (2 !== sizeof($parts)) {
			return $this->getDefaultValue($optionData);
		}

		$left = $this->string($parts[0], array($leftLowercase, true), null);
		$right = $this->string($parts[1], array($rightLowercase, true), null);
		if (!$left || !$right) {
			return $this->getDefaultValue($optionData);
		}

		return $left . '=' . $right;
	}

	/**
	 * Do not call this method directly. Use the method sanitize instead.
	 * This method sanitize a string (for example remove leading spaces etc.). If it fails, the default value will be used.
	 * sanitize($value, array('string', $optionalLowercase, $optionTrim, $optionNonEmpty), $optionElement)
	 *
	 * @param mixed $value
	 * @param mixed $userParams
	 * @param mixed $optionData
	 *
	 * @return null|string
	 */
	function string($value, $userParams, $optionData)
	{
		$lowercase = NextADInt_Core_Util_ArrayUtil::get(0, $userParams, false);
		$trim = NextADInt_Core_Util_ArrayUtil::get(1, $userParams, true);
		$nonEmpty = NextADInt_Core_Util_ArrayUtil::get(2, $userParams, false);

		if (!is_string($value) && !is_numeric($value) && !is_bool($value)) {
			return $this->getDefaultValue($optionData);
		}

		$value = (string)$value;
		if ($trim) {
			$value = trim($value);
		}

		if ($lowercase) {
			$value = NextADInt_Core_Util_StringUtil::toLowerCase($value);
		}

		if ($nonEmpty && 0 === strlen($value)) {
			$value = $this->getDefaultValue($optionData);
		}

		return $value;
	}

	/**
	 * This method replace $value if the default value if $value does not exists in elements (in option meta data).
	 * This method do not use extra parameters.
	 *
	 * @param mixed $value
	 * @param mixed $userParams
	 * @param mixed $optionData
	 *
	 * @return string
	 * @throws Exception
	 */
	function selection($value, $userParams, $optionData)
	{
		$validStrings = NextADInt_Core_Util_ArrayUtil::get(
			NextADInt_Multisite_Option_Attribute::ELEMENTS, $optionData, array()
		);

		$value = trim($value);
		if (in_array($value, $validStrings)) {
			return $value;
		}

		return $this->getDefaultValue($optionData);
	}

	function custom($value, $userParams, $optionData)
	{
			return $value;
			}

	function custom2($value, $userParams, $optionData)
	{
		$lineSeparator = NextADInt_Core_Util_ArrayUtil::get(0, $userParams, "\n");
		$unitSeparator = NextADInt_Core_Util_ArrayUtil::get(1, $userParams, ':');

		$units = NextADInt_Core_Util_ArrayUtil::get(
			NextADInt_Multisite_Option_Attribute::TYPE_STRUCTURE, $optionData, array()
		);

		$sanitizedValue = '';
		$lines = NextADInt_Core_Util_StringUtil::split($value, "\n");

		foreach ($lines as $line) {
			$sanitizedLine = $this->sanitizeLine();
			if ($sanitizedLine) {
				$sanitizedValue .= "\n";
			}
			$sanitizedValue .= $sanitizedLine;
		}
	}

	private function sanitizeLine() {
		return null;
	}


	function authcode($value, $userParams, $optionData, $saveOption)
	{
		// only generate a new authcode when saving the option
		if (!$saveOption) {
			return $value;
		}

		if (!is_string($value) || strlen($value) < 20) {
			$value = wp_generate_password(20, false, false);
		}

		return $value;
	}

	public function getDefaultValue($optionData)
	{
		return NextADInt_Core_Util_ArrayUtil::get(NextADInt_Multisite_Option_Attribute::DEFAULT_VALUE, $optionData);
	}
}