<?php

namespace Dreitier\Util\Validator\Rule;

/**
 * Rule provides the validate method for our rules.
 */
interface Rule
{
	/**
	 * Validate the given data.
	 *
	 * @param string $value
	 * @param array $data
	 *
	 * @return mixed
	 */
	public function validate($value, $data);
}