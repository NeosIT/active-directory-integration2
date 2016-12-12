<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Validator_Rule_BaseDnWarn')) {
	return;
}

/**
 * NextADInt_Multisite_Validator_Rule_BaseDn validates if the given base DN is valid.
 *
 * @author Stefan Fiedler <sfi@neos-it.de>
 *
 * @access
 */
class NextADInt_Multisite_Validator_Rule_BaseDnWarn extends NextADInt_Core_Validator_Rule_Abstract
{

	/**
	 * Validate the given base DN.
	 *
	 * @param string $value
	 * @param array  $data
	 *
	 * @return bool|mixed
	 */
	public function validate($value, $data)
	{
		$dns= ldap_explode_dn($value, 0);

		// check if given base DN starts with dc=
		$occurrences = NextADInt_Core_Util_ArrayUtil::countOccurencesStartsWith($dns, 'dc=');

		if($occurrences == 1) {
			return $this->getMsg();
		}

		return true;
	}

}
