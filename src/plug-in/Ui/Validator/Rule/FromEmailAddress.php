<?php
namespace Dreitier\Nadi\Ui\Validator\Rule;


use Dreitier\Util\Validator\Rule\RuleAdapter;

/**
 * FromEmailAddress prevents saving FromEmailAddress in the wrong style.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class FromEmailAddress extends RuleAdapter
{
	/**
	 * Validate the given data.
	 *
	 * @param string $value
	 * @param array  $data
	 *
	 * @return string
	 */
	public function validate($value, $data)
	{
		$conflict = (strpos($value, '@') === false && !empty($value));

		if ($conflict) {
			return $this->getMsg();
		}

		return true;
	}
}