<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Core_Util_ExceptionUtil')) {
	return;
}

/**
 * Core_Util_ExceptionUtil allows handling {@see WP_Error} classes as a {@see Core_Exception_WordPressErrorException}.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class Core_Util_ExceptionUtil
{
	/** @var Logger */
	private static $logger;

	/**
	 * Throw a new {@see Core_Exception_WordPressErrorException} using the given $error. If the given value
	 * is not an instance of {@see WP_Error}, false will be returned.
	 *
	 * @param WP_Error|mixed $error
	 *
	 * @return bool
	 *
	 * @throws Core_Exception_WordPressErrorException
	 */
	public static function handleWordPressErrorAsException($error)
	{
		if (!is_wp_error($error)) {
			return false;
		}

		Core_Util_LoggerUtil::error(self::getLogger(), $error->get_error_messages());

		throw new Core_Exception_WordPressErrorException($error);
	}

	/**
	 * Return a new or existing {@see Logger}.
	 *
	 * @return Logger
	 */
	private static function getLogger()
	{
		if (null === self::$logger) {
			self::$logger = Logger::getLogger(__CLASS__);
		}

		return self::$logger;
	}
}