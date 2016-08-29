<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Validator_Rule_Conditional')) {
	return;
}

/**
 * NextADInt_Multisite_Validator_Rule_Conditional runs the given rule only under certain conditions.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class NextADInt_Multisite_Validator_Rule_Conditional extends NextADInt_Core_Validator_Rule_Abstract
{
	/**
	 * @var NextADInt_Core_Validator_Rule[]
	 */
	private $rules = array();

	/**
	 * Conditions to match our data against, before we can run our validation.
	 *
	 * @var array
	 */
	private $propertyCondition;

	/**
	 * NextADInt_Multisite_Validator_Rule_Conditional constructor.
	 *
	 * @param NextADInt_Core_Validator_Rule[] $rules
	 * @param array                 $propertyCondition
	 */
	public function __construct($rules, array $propertyCondition)
	{
		parent::__construct('');
		$this->rules = $rules;
		$this->propertyCondition = $propertyCondition;
	}

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
		if (!$this->areConditionsTrue($data)) {
			return true;
		}

		foreach ($this->rules as $rule) {
			$result = $rule->validate($value, $data);

			if (true !== $result) {
				return $result;
			}
		}

		return true;
	}

	/**
	 * Check the given necessary conditions against our current data set.
	 *
	 * @param $data
	 *
	 * @return bool
	 */
	protected function areConditionsTrue($data)
	{
		foreach ($this->propertyCondition as $key => $value) {
			$dataValue = $data[$key];

			if (isset($dataValue['option_value'])) {
				$dataValue = $dataValue['option_value'];
			}

			if ($dataValue != $value) {
				return false;
			}
		}

		return true;
	}
}