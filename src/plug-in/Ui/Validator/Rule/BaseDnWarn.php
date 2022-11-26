<?php

namespace Dreitier\Nadi\Ui\Validator\Rule;


use Dreitier\Util\ArrayUtil;
use Dreitier\Util\Validator\Rule\RuleAdapter;

/**
 * BaseDnWarn validates if the given base DN is valid.
 *
 * @author Stefan Fiedler <sfi@neos-it.de>
 *
 * @access
 */
class BaseDnWarn extends RuleAdapter
{

	/**
	 * Validate the given base DN.
	 *
	 * @param string $value
	 * @param array $data
	 *
	 * @return bool|mixed
	 */
	public function validate($value, $data)
	{
		$dns = ldap_explode_dn($value, 0);

		// check if given base DN starts with dc=
		$occurrences = ArrayUtil::countOccurencesStartsWith($dns, 'dc=');

		if ($occurrences == 1) {
			return $this->getMsg();
		}

		return true;
	}

}
