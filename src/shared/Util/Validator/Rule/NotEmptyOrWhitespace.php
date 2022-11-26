<?php

namespace Dreitier\Util\Validator\Rule;


/**
 * NotEmptyOrWhitespace provides a validation to prevent that a user enters an empty value.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class NotEmptyOrWhitespace extends RuleAdapter
{
	/**
	 * Validate the given data.
	 *
	 * @param string $value
	 * @param array $data
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