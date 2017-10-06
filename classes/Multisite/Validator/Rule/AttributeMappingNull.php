<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Validator_Rule_AttributeMappingNull')) {
	return;
}

/**
 * NextADInt_Multisite_Validator_Rule_AttributeMappingNull prevents saving uncomplete attribute mappings.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class NextADInt_Multisite_Validator_Rule_AttributeMappingNull extends NextADInt_Core_Validator_Rule_Abstract
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
		$attributeMapping = $this->convertAttributeMapping($value);

		$isAdAttributeUndefinedOrEmpty = isset($attributeMapping[""]) || isset($attributeMapping["undefined"]);

		if ($isAdAttributeUndefinedOrEmpty) {
			return $this->getMsg();
		}

		foreach ($attributeMapping as $attribute) {
			$isEmpty = $attribute["type"] === ""
				|| $attribute["type"] === "undefined"
				|| $attribute["wordpress_attribute"] === ""
				|| $attribute["wordpress_attribute"] === "undefined"
				|| $attribute["wordpress_attribute"] === "next_ad_int_";

			if ($isEmpty) {
				return $this->getMsg();
			}
		}

		return true;
	}

	/**
	 * Simple delegation to {@see NextADInt_Ldap_Attribute_Repository::convertAttributeMapping}.
	 *
	 * @param $attributeString
	 *
	 * @return array
	 */
	protected function convertAttributeMapping($attributeString)
	{
		return NextADInt_Ldap_Attribute_Repository::convertAttributeMapping($attributeString);
	}
}