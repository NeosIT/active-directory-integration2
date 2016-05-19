<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Validator_Rule_NetworkTimeout')) {
	return;
}

/**
 * Multisite_Validator_Rule_NetworkTimeout validates if the value is numeric.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Multisite_Validator_Rule_NetworkTimeout extends Multisite_Validator_Rule_Numeric
{
	public function validate($value, $data)
	{
		$condition = parent::validate($value, $data) === true && !$this->isNegative($value);
		
		if($condition) {
			return true;
		}
		
		return $this->getMsg();
	}
}