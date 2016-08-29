<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Validator_Rule_AdAttributeConflict')) {
	return;
}

/**
 * NextADInt_Multisite_Validator_Rule_AdAttributeConflict prevents using the same Ad Attribute multiple times.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class NextADInt_Multisite_Validator_Rule_AdAttributeConflict extends NextADInt_Core_Validator_Rule_Abstract
{
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
		$conflict = $this->checkAttributeNamesForConflict($value);

		if ($conflict) {
			return $this->getMsg();
		}

		return true;
	}

	/**
	 * Simple delegation to {@see NextADInt_Ldap_Attribute_Repository::checkAttributeMapping}.
	 *
	 * @param $attributeString
	 *
	 * @return bool
	 */
	protected function checkAttributeNamesForConflict($attributeString)
	{
		return NextADInt_Ldap_Attribute_Repository::checkAttributeNamesForConflict($attributeString);
	}
}