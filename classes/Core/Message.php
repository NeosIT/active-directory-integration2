<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_Message')) {
	return;
}

/**
 * NextADInt_Core_Message represents a message that can be shown in the frontend.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class NextADInt_Core_Message
{
	/**
	 * @var string $message
	 */
	private $message;
	/**
	 * @var string $type
	 */
	private $type;
	/**
	 * @var array
	 */
	private $additionalInformation = array();

	private function __construct($message, $type, $additionalInformation)
	{
		$this->message = $message;
		$this->type = $type;
		$this->additionalInformation = $additionalInformation;
	}

	private function __clone()
	{
	}

	/**
	 * Create a new success message.
	 *
	 * @param       $message
	 * @param array $additionalInformation
	 *
	 * @return NextADInt_Core_Message
	 */
	public static function success($message, $additionalInformation = array())
	{
		return new self($message, NextADInt_Core_Message_Type::SUCCESS, $additionalInformation);
	}

	/**
	 * Create a new error message.
	 *
	 * @param       $message
	 * @param array $additionalInformation
	 *
	 * @return NextADInt_Core_Message
	 */
	public static function error($message, $additionalInformation = array())
	{
		return new self($message, NextADInt_Core_Message_Type::ERROR, $additionalInformation);
	}

	/**
	 * Add an additional information to our message.
	 *
	 * @param $name
	 * @param $value
	 */
	public function addAdditionalInformation($name, $value)
	{
		$this->additionalInformation[$name] = $value;
	}

	/**
	 * Convert the current message to an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return array(
			'message' => $this->message,
			'type' => $this->type,
			'additionalInformation' => $this->additionalInformation,
			'isMessage' => true,
		);
	}
}