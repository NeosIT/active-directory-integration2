<?php

namespace Dreitier\Util\Validator;


use Dreitier\Util\Message\Type;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 */
class Result
{
	/**
	 * @var array
	 */
	private $validationResult = array();

	/**
	 * @param string $name
	 * @param string $msg
	 */
	public function addValidationResult($name, $msg)
	{
		if (!isset($this->validationResult[$name])) {
			$this->validationResult[$name] = array();
		}

		$this->validationResult[$name] = $msg;
	}

	/**
	 * @return bool
	 */
	public function isValid()
	{
		return (sizeof($this->validationResult) == 0);
	}

	/**
	 * This method will check the current validationResult object and find the first occurrence of an error.
	 * If a validation error is found, return true;
	 *
	 * @return bool
	 */
	public function containsErrors()
	{
		foreach ($this->validationResult as $result) {
			if (array_key_exists(Type::ERROR, $result)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function getValidationResult()
	{
		return $this->validationResult;
	}
}
