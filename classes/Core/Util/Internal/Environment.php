<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_Util_Internal_Environment')) {
	return;
}

/**
 * NextADInt_Core_Util_Internal_Environment checks the environment variables for specific values.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access public
 */
class NextADInt_Core_Util_Internal_Environment
{
	const ENV_PRODUCTIVE = 'productive';

	private function __construct()
	{
	}

	private function __clone()
	{
	}

	/**
	 * Check the environment variables for the current machine. If production is set to false, it means this is a
	 * development environment.
	 * If no environment variable was found, it means it is a productive machine.
	 *
	 * @return string
	 */
	public static function isProductive()
	{
		return (self::getEnvironmentVariable(self::ENV_PRODUCTIVE, true));
	}

	/**
	 * Reads environment vars and returns them
	 *
	 * @param string $key Name of environment var. Will lookup for $key, then for 'env.' . $key. If none is found $default is used
	 * @param string $default Value if no environment variable is found
	 *
	 * @return string
	 */
	public static function getEnvironmentVariable($key, $default)
	{
		if (false !== ($val = getenv('env.' . $key))) {
			return $val;
		}

		if (false !== ($val = getenv($key))) {
			return $val;
		}

		// none defined
		return $default;
	}
}