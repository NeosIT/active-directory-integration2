<?php

namespace Dreitier\Util\Validator;

use Dreitier\Util\Validator\Rule\Rule;

/**
 * Uses custom validation rules to allow validation.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 *
 * @access public
 */
class Validator
{
	/**
	 * An array of all validation rules registered.
	 *
	 * @var array
	 */
	private $validationRules = array();

	/**
	 * Validate the given data and return a new {@see Result}.
	 *
	 * @param array $data
	 *
	 * @return Result
	 */
	public function validate($data)
	{
		$result = new Result();

		foreach ($this->validationRules as $name => $rules) {
			if (!isset($data[$name])) {
				continue; //TODO Revisit
			}

			$value = $data[$name];

			//TODO Find a better option
			if (is_array($value) && isset($value['option_value'])) {
				$value = $value['option_value'];
			}

			foreach ($rules as $rule) {
				/** @var Rule $rule */
				$validationResult = $rule->validate($value, $data);

				if (true !== $validationResult) {
					$result->addValidationResult($name, $validationResult);
				}
			}
		}

		return $result;
	}

	/**
	 * Add a new rule to our registered rules.
	 *
	 * @param string $name
	 * @param Rule $rule
	 */
	public function addRule($name, Rule $rule)
	{
		if (!isset($this->validationRules[$name])) {
			$this->validationRules[$name] = array();
		}

		$this->validationRules[$name][] = $rule;
	}

	/**
	 * Return the current validation rules.
	 *
	 * @return array
	 */
	public function getValidationRules()
	{
		return $this->validationRules;
	}
}