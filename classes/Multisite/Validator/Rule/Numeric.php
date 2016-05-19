<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Validator_Rule_Numeric')) {
	return;
}

/**
 * Multisite_Validator_Rule_Numeric validates if the value is numeric.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Multisite_Validator_Rule_Numeric extends Core_Validator_Rule_Abstract
{
	/**
	 * Validate the given data.
	 *
	 * @param string $value
	 * @param array  $data
	 *
	 * @return mixed
	 */
	public function validate($value, $data)
	{
		if (!is_numeric($value)) {
			return $this->getMsg();
		}

		return true;
	}

	/**
	 * Check if the given value is a negative number.
	 *
	 * @param $value
	 *
	 * @return bool
	 */
	public function isNegative($value)
	{
		if ($value < 0) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the given value is a positive number.
	 *
	 * @param $value
	 *
	 * @return bool
	 */
	public function isPositive($value)
	{
		if ($value > 0) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the given value is a float.
	 *
	 * @param $value
	 *
	 * @return bool
	 */
	public function isFloat($value)
	{
		if (is_float($value)) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the given value is a zero.
	 *
	 * @param $value
	 *
	 * @return bool
	 */
	public function isZero($value)
	{
		if ($value === 0) {
			return true;
		}

		return false;
	}
}