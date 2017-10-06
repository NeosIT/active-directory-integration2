<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Validator_Rule_AdminEmail')) {
	return;
}

/**
 * NextADInt_Multisite_Validator_Rule_AdminEmail validates if the given value is an email address.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class NextADInt_Multisite_Validator_Rule_AdminEmail extends NextADInt_Multisite_Validator_Rule_Suffix
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
		if ($this->isList($value)) {
			$emails = explode(';', $value);

			foreach ($emails as $email) {
				if ($this->isInvalid($email)) {
					return $this->getMsg();
				}

				continue;
			}

			return true;
		}

		if ($this->isInvalid($value)) {
			return $this->getMsg();
		}

		return true;
	}

	/**
	 * Check if the given value is invalid.
	 *
	 * @param $email
	 *
	 * @return bool
	 */
	private function isInvalid($email)
	{
		return ($email != "" && strpos($email, '@') === false)
		|| ($email != "" && $email[0] == '@')
		|| ($email != "" && $email[strlen($email) - 1] == '@');
	}
}
