<?php

namespace Dreitier\Nadi\Ui\Validator\Rule;


use Dreitier\Util\Validator\Rule\RuleAdapter;

/**
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class SelectValueValid extends RuleAdapter
{
	/**
	 * @var array
	 */
	private $validValues = array();

	/**
	 * @param string $msg
	 * @param array $validValues
	 */
	public function __construct($msg, array $validValues)
	{
		parent::__construct($msg);

		$this->validValues = $validValues;
	}

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
		if (!in_array($value, $this->validValues)) {
			return $this->getMsg();
		}

		return true;
	}
}