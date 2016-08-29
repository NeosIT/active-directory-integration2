<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Validator_Rule_Suffix')) {
	return;
}

/**
 * NextADInt_Multisite_Validator_Rule_Suffix provides validation for a specific suffix.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class NextADInt_Multisite_Validator_Rule_Suffix extends NextADInt_Core_Validator_Rule_Abstract
{
	/**
	 * The suffix to check for.
	 *
	 * @var string
	 */
	private $suffix;

	/**
	 * Adi_Validation_Rule_SuffixRule constructor.
	 *
	 * @param string $msg
	 * @param string $suffix
	 */
	public function __construct($msg, $suffix)
	{
		parent::__construct($msg);
		$this->suffix = $suffix;
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
		if ($value != "" && strpos($value, $this->suffix) == false) {
			return $this->getMsg();
		}

		return true;
	}

	/**
	 * Check if the given value is a frontend list.
	 *
	 * @param $value
	 *
	 * @return bool
	 */
	public function isList($value)
	{
		if (strpos($value, ";") !== false) {
			return true;
		}

		return false;
	}
}