<?php

namespace Dreitier\Util\Session;

use Dreitier\Util\StringUtil;
use Dreitier\Util\Util;

/**
 * SessionHandler provides access to the PHP session.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class SessionHandler
{
	/**
	 * @var SessionHandler
	 */
	private static $instance = null;

	private function __construct()
	{
	}

	private function __clone()
	{
	}

	/**
	 * Get the singleton instance of {@link SessionHandler}.
	 *
	 * @return SessionHandler
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
			self::$instance = new SessionHandler();
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
		if (StringUtil::startsWith(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX, $key)) {
			return $key;
		}

		return NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . $key;
	}

	/**
	 * Check if the session is not started and start it.
	 */
	protected function startSession()
	{
		$native = Util::native();

		if ('' === $native->getSessionId()) {
			$native->startSession();
		}
	}
}