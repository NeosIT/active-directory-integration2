<?php

namespace Dreitier\Nadi\Ui\Validator\Rule;


use Dreitier\Ldap\Attribute\Repository;
use Dreitier\Util\Validator\Rule\RuleAdapter;

/**
 * AttributeMappingNull prevents saving incomplete attribute mappings.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class AttributeMappingNull extends RuleAdapter
{
	/**
	 * Validate the given data.
	 *
	 * @param string $value
	 * @param array $data
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
	 * Simple delegation to {@see Repository::convertAttributeMapping}.
	 *
	 * @param $attributeString
	 *
	 * @return array
	 */
	protected function convertAttributeMapping($attributeString)
	{
		return Repository::convertAttributeMapping($attributeString);
	}
}