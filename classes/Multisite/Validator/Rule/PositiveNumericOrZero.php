<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Validator_Rule_PositiveNumericOrZero')) {
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
class Multisite_Validator_Rule_PositiveNumericOrZero extends Multisite_Validator_Rule_Numeric
{
	public function validate($value, $data)
	{
		$condition = parent::validate($value, $data) === true && !$this->isNegative($value);

		if ($condition) {
			return true;
		}

		return $this->getMsg();
	}
}