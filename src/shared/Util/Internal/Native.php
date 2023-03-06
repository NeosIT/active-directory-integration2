<?php

namespace Dreitier\Util\Internal;

/**
 * Native contains wrapper methods which delegates to internal PHP functions.
 * This is required to get the test infrastructure working.
 * In addition to that we can delegate to different methods depending upon the current used PHP version.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access public
 */
class Native
{
	private function __clone()
	{
	}

	/**
	 * Delegate the call to php internal version_compare function.
	 *
	 * @param string $version1
	 * @param string $version2
	 * @param null $operator
	 *
	 * @return mixed
	 * @see version_compare
	 *
	 */
	public function compare($version1, $version2, $operator = null)
	{
		return version_compare($version1, $version2, $operator);
	}

	/**
	 * Delegate the call to php internal fsockopen function.
	 *
	 * @param string $hostname
	 * @param int $port
	 * @param int $errno
	 * @param string $errstr
	 * @param int $timeout
	 *
	 * @return resource
	 */
	public function fsockopen($hostname, $port, &$errno, &$errstr, $timeout)
	{
		return fsockopen($hostname, $port, $errno, $errstr, $timeout);
	}

	/**
	 * Delegate the call to php internal fclose function.
	 *
	 * @param object $resource
	 *
	 * @return resource
	 */
	public function fclose($resource)
	{
		return fclose($resource);
	}

	/**
	 * Delegate the call to php internal extension_loaded function.
	 *
	 * @param string $name
	 *
	 * @return bool
	 * @see extension_loaded
	 *
	 */
	public function isLoaded($name)
	{
		return extension_loaded($name);
	}

	/**
	 * Delegate the call to php internal function_exists function.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function isFunctionAvailable($name)
	{
		return function_exists($name);
	}

	/**
	 * Delegate the call to php internal file_exists function.
	 *
	 * @param $filePath
	 *
	 * @return bool
	 */
	public function isFileAvailable($filePath)
	{
		return file_exists($filePath);
	}

	/**
	 * Delegate the call to php internal include_once function.
	 *
	 * @param $path
	 */
	public function includeOnce($path)
	{
		include_once($path);
	}

	/**
	 * Delegate the call to php internal ini_get function.
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function iniGet($key)
	{
		return ini_get($key);
	}

	/**
	 * Delegate the call to php internal ini_set function.
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return string
	 */
	public function iniSet($key, $value)
	{
		return ini_set($key, $value);
	}

	/**
	 * Returns the current session id from PHP.
	 *
	 * @return string
	 */
	public function getSessionId()
	{
		return session_id();
	}

	/**
	 * Start a new session.
	 */
	public function startSession()
	{
		session_start();
	}

	/**
	 * Delegate the call to php internal class_exists function.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function isClassAvailable($classname)
	{
		return class_exists($classname);
	}

	/**
	 * Delegate to <code>phpversion</code>
	 * @return false|string
	 */
	public function phpversion() {
		return phpversion();
	}
}