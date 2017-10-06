<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Validator_Rule_PositiveNumericOrZero')) {
	return;
}

/**
 * Multisite_Validator_Rule_PositiveNumberOrZero validates if the value is positive numeric or zero.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class NextADInt_Multisite_Validator_Rule_PositiveNumericOrZero extends NextADInt_Multisite_Validator_Rule_Numeric
{
	/**
	 * Validate the given data and check if it is zero or a position number.
	 *
	 * @param string $value
	 * @param array  $data
	 *
	 * @return bool|mixed
	 */
	public function validate($value, $data)
	{
		$condition = parent::validate($value, $data) === true && !$this->isNegative($value);

		if ($condition) {
			return true;
		}

		return $this->getMsg();
	}
}