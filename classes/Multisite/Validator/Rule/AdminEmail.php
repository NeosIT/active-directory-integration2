<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Validator_Rule_AdminEmail')) {
	return;
}

/**
 * Multisite_Validator_Rule_AdminEmail validates if the given value is an email address.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Multisite_Validator_Rule_AdminEmail extends Multisite_Validator_Rule_Suffix
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

				$isConflict = $email != "" && strpos($email, '@') === false || $email != "" && $email[0] == '@'
					|| $email != "" && $email[strlen($email) - 1] == '@';

				if ($isConflict) {
					return $this->getMsg();
				}

				continue;
			}
			return true;
		}

		$isConflict = $value != "" && strpos($value, '@') === false || $value != "" && $value[0] == '@'
			|| $value != ""
			&& $value[strlen($value) - 1] == '@';

		if ($isConflict) {
			return $this->getMsg();
		}

		return true;
	}
}
