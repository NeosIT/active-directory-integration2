<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Core_Validator_Rule_Abstract')) {
	return;
}

/**
 * Core_Validator_Rule_Abstract provides the base functionality for our rules.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access public
 */
abstract class Core_Validator_Rule_Abstract implements Core_Validator_Rule
{
	/**
	 * The message that will be returned, if the validation failed.
	 *
	 * @var string
	 */
	private $msg;

	/**
	 * Core_Validator_Rule_Abstract constructor.
	 *
	 * @param string $msg
	 */
	public function __construct($msg)
	{
		$this->msg = $msg;
	}

	/**
	 * @return mixed
	 */
	public function getMsg()
	{
		return $this->msg;
	}
}