<?php

namespace Dreitier\Nadi\Ui\Validator\Rule;

use Dreitier\Util\Validator\Rule\HasSuffix;

/**
 * Validates if the given value is an email address.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class AdminEmail extends HasSuffix
{

	/**
	 * Validate the given data.
	 *
	 * @param string $value
	 * @param array $data
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
