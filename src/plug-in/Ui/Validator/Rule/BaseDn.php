<?php

namespace Dreitier\Nadi\Ui\Validator\Rule;


use Dreitier\Util\ArrayUtil;
use Dreitier\Util\Validator\Rule\RuleAdapter;

/**
 * BaseDn validates if the given base DN is valid.
 *
 * @author Stefan Fiedler <sfi@neos-it.de>
 *
 * @access
 */
class BaseDn extends RuleAdapter
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

		// DME: ADI-563 | Github Issue #49 If baseDN is empty there is no need to continue format validation. We return true in order to allow an empty BaseDN
		if ($value == '') {
			return true;
		}

		// check if baseDN starts with a special character
		$re = '/[\W]+/';
		preg_match_all($re, $value[0], $matches);

		if ($matches[0]) {
			return $this->getMsg();
		}

		// general format is incorrect
		$dns = ldap_explode_dn($value, 0);

		if (!$dns) {
			return $this->getMsg();
		}

		// last part of DN must be a domain controller object
		$lastObject = end($dns);

		// make lowercase for further comparison
		$lastObject = strtolower($lastObject);

		// check if first 3 characters are equal to 'dc='
		if (substr($lastObject, 0, 3) !== "dc=") {
			return $this->getMsg();
		}

		// check if at least one domain controller is present
		$occurrences = ArrayUtil::countOccurencesStartsWith($dns, 'dc=');

		if ($occurrences == 0) {
			return $this->getMsg();
		}

		return true;
	}

}
