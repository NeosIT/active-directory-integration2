<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Validator_Rule_SelectValueValid')) {
	return;
}

/**
 * Multisite_Validator_Rule_SelectValueValid TODO short description
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Multisite_Validator_Rule_SelectValueValid extends Core_Validator_Rule_Abstract
{
	/**
	 * @var array
	 */
	private $validValues = array();

	/**
	 * Multisite_Validator_Rule_SelectValueValid constructor.
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