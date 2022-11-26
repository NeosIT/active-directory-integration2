<?php

namespace Dreitier\Nadi\Ui\Validator\Rule;


use Dreitier\Ldap\Attribute\Repository;
use Dreitier\Util\Validator\Rule\RuleAdapter;

/**
 * NoDefaultAttributeName provides a validation to prevent that a user overrides the default
 * attributes.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class NoDefaultAttributeName extends RuleAdapter
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
		$attributeMappingMetaKeys = array_map(function ($mapping) {
			return $mapping['wordpress_attribute'];
		}, $attributeMapping);

		$intersect = array_intersect($attributeMappingMetaKeys, $this->getForbiddenAttributeNames());

		if (sizeof($intersect) > 0) {
			return $this->getMsg();
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

	/**
	 * Get an array with all default attribute meta keys to prevent that the user overrides any of this.
	 *
	 * @return array
	 */
	protected function getForbiddenAttributeNames()
	{
		return Repository::getDefaultAttributeMetaKeys();
	}
}