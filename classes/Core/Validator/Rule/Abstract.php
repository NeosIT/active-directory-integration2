<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_Validator_Rule_Abstract')) {
	return;
}

/**
 * NextADInt_Core_Validator_Rule_Abstract provides the base functionality for our rules.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access public
 */
abstract class NextADInt_Core_Validator_Rule_Abstract implements NextADInt_Core_Validator_Rule
{
	/**
	 * The message that will be returned, if the validation failed.
	 *
	 * @var string
	 */
	private $msg;

	/**
	 * NextADInt_Core_Validator_Rule_Abstract constructor.
	 * @param $msg
	 * @param string $type
	 */
	public function __construct($msg, $type = NextADInt_Core_Message_Type::ERROR)
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