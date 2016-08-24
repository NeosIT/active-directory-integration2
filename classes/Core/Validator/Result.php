<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_Validator_Result')) {
	return;
}

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 */
class NextADInt_Core_Validator_Result
{
	/**
	 * @var array
	 */
	private $result = array();

	/**
	 * @param string $name
	 * @param string $msg
	 */
	public function addValidationResult($name, $msg)
	{
		if (!isset($this->result[$name])) {
			$this->result[$name] = array();
		}

		$this->result[$name] = $msg;
	}

	/**
	 * @return bool
	 */
	public function isValid()
	{
		return (sizeof($this->result) == 0);
	}

	/**
	 * @return array
	 */
	public function getResult()
	{
		return $this->result;
	}
}