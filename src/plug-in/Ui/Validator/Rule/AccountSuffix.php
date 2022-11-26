<?php

namespace Dreitier\Nadi\Ui\Validator\Rule;

use Dreitier\Util\Validator\Rule\HasSuffix;

/**
 * Validates that the given value is a suffix.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class AccountSuffix extends HasSuffix
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
	 * @param $entry
	 *
	 * @return bool
	 */
	private function isInvalid($entry)
	{
		return ($entry != "" && strpos($entry, '@') === false || $entry != "" && $entry[0] != '@');
	}
}
