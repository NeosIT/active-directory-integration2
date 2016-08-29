<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Validator_Rule_SelectValueValid')) {
	return;
}

/**
 * NextADInt_Multisite_Validator_Rule_SelectValueValid TODO short description
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class NextADInt_Multisite_Validator_Rule_SelectValueValid extends NextADInt_Core_Validator_Rule_Abstract
{
	/**
	 * @var array
	 */
	private $validValues = array();

	/**
	 * NextADInt_Multisite_Validator_Rule_SelectValueValid constructor.
	 *
	 * @param string $msg
	 * @param array  $validValues
	 */
	public function __construct($msg, array $validValues)
	{
		parent::__construct($msg);

		$this->validValues = $validValues;
	}

	/**
	 * Validate the given data.
	 *
	 * @param string $value
	 * @param array  $data
	 *
	 * @return mixed
	 */
	public function validate($value, $data)
	{
		if (!in_array($value, $this->validValues)) {
			return $this->getMsg();
		}

		return true;
	}
}