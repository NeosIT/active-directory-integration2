<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Validator_Rule_NoDefaultAttributeName')) {
	return;
}

/**
 * Multisite_Validator_Rule_NoDefaultAttributeName provides a validation to prevent that a user overrides the default
 * attributes.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Multisite_Validator_Rule_NoDefaultAttributeName extends Core_Validator_Rule_Abstract
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
		$attributeMappingMetaKeys = array_map(function($mapping) {
			return $mapping['wordpress_attribute'];
		}, $attributeMapping);

		$intersect = array_intersect($attributeMappingMetaKeys, $this->getForbiddenAttributeNames());

		if (sizeof($intersect) > 0) {
			return $this->getMsg();
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

	/**
	 * Get an array with all default attribute meta keys to prevent that the user overrides any of this.
	 *
	 * @return array
	 */
	protected function getForbiddenAttributeNames()
	{
		return Ldap_Attribute_Repository::getDefaultAttributeMetaKeys();
	}
}