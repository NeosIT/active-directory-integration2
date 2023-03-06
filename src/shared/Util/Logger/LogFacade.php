<?php

namespace Dreitier\Util\Logger;

/**
 * Allows logging multiple messages by iterating through the given array of messages.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
abstract class LogFacade
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
	 * @param array $messages
	 */
	public static function error(array $messages)
	{
		foreach ($messages as $message) {
			next_ad_int_logger()->error($message);
		}
	}

	/**
	 * Iterate through all the given $messages and log them as debug.
	 *
	 * @param array $messages
	 */
	public static function debug(array $messages)
	{
		foreach ($messages as $message) {
			next_ad_int_logger()->debug($message);
		}
	}
}