<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_Util_LoggerUtil')) {
	return;
}

/**
 * NextADInt_Core_Util_LoggerUtil allows logging multiple messages by iterating through the given array of messages.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
abstract class NextADInt_Core_Util_LoggerUtil
{

	private function __construct()
	{
	}

	private function __clone()
	{
	}

	/**
	 * Iterate through all the given $messages and log them as error.
	 *
	 * @param array  $messages
	 */
	public static function error(array $messages)
	{
		foreach ($messages as $message) {
			NextADInt_Core_Logger::getLogger()->error($message);
		}
	}

	/**
	 * Iterate through all the given $messages and log them as debug.
	 *
	 * @param array  $messages
	 */
	public static function debug(array $messages)
	{
		foreach ($messages as $message) {
			NextADInt_Core_Logger::getLogger()->debug($message);
		}
	}
}