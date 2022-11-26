<?php

namespace Dreitier\Nadi\Ui\Validator\Rule;


use Dreitier\Util\Validator\Rule\Numeric;

/**
 * Validates if the value is numeric and in port range.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Port extends Numeric
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
		if (!is_numeric($value) || !$this->isInPortRange($value)) {
			return $this->getMsg();
		}

		return true;
	}

	/**
	 * Check if the given number is inside the port range.
	 *
	 * @param $value
	 *
	 * @return bool
	 */
	public function isInPortRange($value)
	{
		if ($value >= 0 && $value <= 65535) {
			return true;
		}

		return false;
	}
}