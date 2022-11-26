<?php

namespace Dreitier\Util\Validator\Rule;

use Dreitier\Util\Message\Type;

/**
 * Provides the base functionality for our rules.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access public
 */
abstract class RuleAdapter implements Rule
{
	/**
	 * The message that will be returned, if the validation failed.
	 *
	 * @var string
	 */
	private $msg;

	/**
	 * @param $msg
	 * @param string $type
	 */
	public function __construct($msg, $type = Type::ERROR)
	{
		$this->msg = array($type => $msg);
	}

	/**
	 * @return mixed
	 */
	public function getMsg()
	{
		return $this->msg;
	}
}