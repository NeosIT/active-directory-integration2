<?php

namespace Dreitier\Nadi\Ui\Validator\Rule;

use Dreitier\Util\Validator\Rule\RuleAdapter;

/**
 * DefaultEmailDomain prevents saving DefaultEmailDomain in the wrong style.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class DefaultEmailDomain extends RuleAdapter
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
		$conflict = $value != "" && strpos($value, '@') !== false;

		if ($conflict) {
			return $this->getMsg();
		}

		return true;
	}
}