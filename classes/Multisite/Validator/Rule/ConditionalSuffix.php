<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Validator_Rule_ConditionalSuffix')) {
	return;
}

/**
 * Multisite_Validator_Rule_SyncToWordPressSuffix provides validation for a specific suffix. Before the validation,
 * other conditions will be checked.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Multisite_Validator_Rule_ConditionalSuffix extends Multisite_Validator_Rule_Suffix
{
	/**
	 * Conditions to match our data against, before we can run our validation.
	 *
	 * @var array
	 */
	private $propertyCondition;

	/**
	 * Multisite_Validator_Rule_ConditionalSuffix constructor.
	 *
	 * @param string $msg
	 * @param string $suffix
	 * @param array  $propertyCondition
	 */
	public function __construct($msg, $suffix, array $propertyCondition)
	{
		parent::__construct($msg, $suffix);

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
		if ($this->areConditionsTrue($data)) {
			return parent::validate($value, $data);
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
		foreach ($this->propertyCondition AS $key => $value) {
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