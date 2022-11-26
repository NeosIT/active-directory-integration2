<?php

namespace Dreitier\Nadi\Ui\Validator\Rule;

use Dreitier\Ldap\Attribute\Repository;
use Dreitier\Util\Validator\Rule\RuleAdapter;

/**
 * prevents using the same Ad Attribute multiple times.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class AdAttributeConflict extends RuleAdapter
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
		$conflict = $this->checkAttributeNamesForConflict($value);

		if ($conflict) {
			return $this->getMsg();
		}

		return true;
	}

	/**
	 * Simple delegation to {@see Repository::checkAttributeMapping}.
	 *
	 * @param $attributeString
	 *
	 * @return bool
	 */
	protected function checkAttributeNamesForConflict($attributeString)
	{
		return Repository::checkAttributeNamesForConflict($attributeString);
	}
}