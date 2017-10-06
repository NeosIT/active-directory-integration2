<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Validator_Rule_FromEmailAdress')) {
	return;
}

/**
 * NextADInt_Multisite_Validator_Rule_FromEmailAdress prevents saving FromEmailAdress in the wrong style.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class NextADInt_Multisite_Validator_Rule_FromEmailAdress extends NextADInt_Core_Validator_Rule_Abstract
{
	/**
	 * Validate the given data.
	 *
	 * @param string $value
	 * @param array  $data
	 *
	 * @return string
	 */
	public function validate($value, $data)
	{
		$conflict = (strpos($value, '@') === false && !empty($value));

		if ($conflict) {
			return $this->getMsg();
		}

		return true;
	}
}