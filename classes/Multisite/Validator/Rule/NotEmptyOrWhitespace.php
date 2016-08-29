<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Validator_Rule_NotEmptyOrWhitespace')) {
	return;
}

/**
 * Multisite_Validator_Rule_NotEmpty provides a validation to prevent that a user enters an empty value.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class NextADInt_Multisite_Validator_Rule_NotEmptyOrWhitespace extends NextADInt_Core_Validator_Rule_Abstract
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
		$value = trim($value);

		if (empty($value)) {
			return $this->getMsg();
		}

		return true;
	}
}