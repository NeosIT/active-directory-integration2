<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Validator_Rule_AccountSuffix')) {
	return;
}

/**
 * Multisite_Validator_Rule_AccountSuffix validates that the given value is a suffix.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Multisite_Validator_Rule_AccountSuffix extends Multisite_Validator_Rule_Suffix
{

	/**
	 * Validate the given data.
	 *
	 * @param string $value
	 * @param array  $data
	 *
	 * @return bool|mixed
	 */
	public function validate($value, $data)
	{
		if ($this->isEmailList($value)) {
			$emails = explode(';', $value);

			foreach ($emails as $email) {

				if ($email != "" && strpos($email, '@') === false || $email != "" && $email[0] != '@') {
					return $this->getMsg();
				}

				continue;
			}
			return true;
		}

		if ($value != "" && strpos($value, '@') === false || $value != "" && $value[0] != '@') {
			return $this->getMsg();
		}

		return true;
	}
}
