<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Validator_Rule_Suffix')) {
	return;
}

/**
 * Multisite_Validator_Rule_Suffix provides validation for a specific suffix.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Multisite_Validator_Rule_Suffix extends Core_Validator_Rule_Abstract
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
		if ($this->isEmailList($value)) {
			
			$emails = explode(';', $value);
			
			foreach($emails as $email) {
				
				if ($email != "" && strpos($email, $this->suffix) === false || $email[0] != '@') {
					return $this->getMsg();
				}
				
				continue;
			}
			return true;
		}
		
		if ($value != "" && strpos($value, $this->suffix) == false) {
			return $this->getMsg();
		}

		return true;
	}
	
	public function isEmailList($value) {
		if (strpos($value, ";") !== false) {
			return true;
		}
		
		return false;
	}
}