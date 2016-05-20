<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Validator_Rule_DefaultEmailDomain')) {
	return;
}

/**
 * Multisite_Validator_Rule_DefaultEmailDomain prevents saving DefaultEmailDomain in the wrong style.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Multisite_Validator_Rule_DefaultEmailDomain extends Core_Validator_Rule_Abstract
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
		$conflict = $value != "" && strpos($value, '@') !== false;

		if ($conflict) {
			return $this->getMsg();
		}

		return true;
	}
}