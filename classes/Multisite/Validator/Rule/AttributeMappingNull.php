<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Validator_Rule_AttributeMappingNull')) {
	return;
}

/**
 * Multisite_Validator_Rule_AttributeMappingNull prevents saving uncomplete attribute mappings.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Multisite_Validator_Rule_AttributeMappingNull extends Core_Validator_Rule_Abstract
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
				|| $attribute["wordpress_attribute"] === "adi2_";

			if ($isEmpty) {
				return $this->getMsg();
			}
		}

		return true;
	}

	/**
	 * Simple delegation to {@see Ldap_Attribute_Repository::convertAttributeMapping}.
	 *
	 * @param $attributeString
	 *
	 * @return array
	 */
	protected function convertAttributeMapping($attributeString)
	{
		return Ldap_Attribute_Repository::convertAttributeMapping($attributeString);
	}

}