<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Validator_Rule_Port')) {
	return;
}

/**
 * NextADInt_Multisite_Validator_Rule_Port validates if the value is numeric and in port range.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class NextADInt_Multisite_Validator_Rule_Port extends NextADInt_Multisite_Validator_Rule_Numeric
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
		if (!is_numeric($value) || !$this->isInPortRange($value)) {
			return $this->getMsg();
		}

		return true;
	}

	/**
	 * Check if the given number is inside the port range.
	 *
	 * @param $value
	 *
	 * @return bool
	 */
	public function isInPortRange($value)
	{
		if ($value >= 0 && $value <= 65535) {
			return true;
		}

		return false;
	}
}