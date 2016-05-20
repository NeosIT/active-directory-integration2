<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Validator_Rule_WordPressMetakeyConflict')) {
	return;
}

/**
 * Multisite_Validator_Rule_WordPressMetakeyConflict prevents using the same WordPress Attribute multiple times.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Multisite_Validator_Rule_WordPressMetakeyConflict extends Core_Validator_Rule_Abstract
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

		$metakeyBuffer = array();

		foreach ($attributeMapping as $attribute) {

			if (sizeof($metakeyBuffer) <= 0) {
				$metakeyBuffer[$attribute["wordpress_attribute"]] = true;
				continue;
			}

			if (isset($metakeyBuffer[$attribute["wordpress_attribute"]])) {
				return $this->getMsg();
			}

			$metakeyBuffer[$attribute["wordpress_attribute"]] = true;
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