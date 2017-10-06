<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Handler')) {
	return;
}

/**
 * NextADInt_Core_Session_Handler provides access to the PHP session.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class NextADInt_Core_Session_Handler
{
	/**
	 * @var NextADInt_Core_Session_Handler
	 */
	private static $instance = null;

	private function __construct()
	{
	}

	private function __clone()
	{
	}

	/**
	 * Get the singleton instance of {@link NextADInt_Core_Session_Handler}.
	 *
	 * @return NextADInt_Core_Session_Handler
	 */
	public static function getInstance()
	{
		$args = func_get_args();
		$instance = self::$instance;

		if (sizeof($args) > 0) {
			$instance = $args[0];
		}

		if ($instance == null) {
			// create new instance
			self::$instance = new NextADInt_Core_Session_Handler();
			self::$instance->startSession();
		} else {
			// get instance from parameter
			self::$instance = $instance;
		}

		return self::$instance;
	}

	/**
	 * Save the given value in the session using the next_ad_int_ prefix and {@code $key} as key.
	 *
	 * @param $key
	 * @param $value
	 */
	public function setValue($key, $value)
	{
		$sessionKey = $this->normalizeKey($key);
		$_SESSION[$sessionKey] = $value;
	}

	/**
	 * Retrieve the value from the session using the next_ad_int_ prefix and {@code $key} as key.
	 *
	 * @param      $key
	 * @param null $default
	 *
	 * @return null
	 */
	public function getValue($key, $default = null)
	{
		$sessionKey = $this->normalizeKey($key);

		if (!isset($_SESSION[$sessionKey])) {
			return $default;
		}

		return $_SESSION[$sessionKey];
	}

	/**
	 * Remove an entry from the session.
	 *
	 * @param $key
	 */
	public function clearValue($key)
	{
		$sessionKey = $this->normalizeKey($key);

		unset($_SESSION[$sessionKey]);
	}

	/**
	 * Return the normalized key.
	 *
	 * @param $key
	 *
	 * @return string
	 */
	protected function normalizeKey($key)
	{
		if (NextADInt_Core_Util_StringUtil::startsWith(NEXT_AD_INT_PREFIX, $key)) {
			return $key;
		}

		return NEXT_AD_INT_PREFIX . $key;
	}

	/**
	 * Check if the session is not started and start it.
	 */
	protected function startSession()
	{
		$native = NextADInt_Core_Util::native();

		if ('' === $native->getSessionId()) {
			$native->startSession();
		}
	}
}