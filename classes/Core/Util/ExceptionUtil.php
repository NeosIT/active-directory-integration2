<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_Util_ExceptionUtil')) {
	return;
}

/**
 * NextADInt_Core_Util_ExceptionUtil allows handling {@see WP_Error} classes as a {@see NextADInt_Core_Exception_WordPressErrorException}.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class NextADInt_Core_Util_ExceptionUtil
{
	/**
	 * Throw a new {@see NextADInt_Core_Exception_WordPressErrorException} using the given $error. If the given value
	 * is not an instance of {@see WP_Error}, false will be returned.
	 *
	 * @param WP_Error|mixed $error
	 *
	 * @return bool
	 *
	 * @throws NextADInt_Core_Exception_WordPressErrorException
	 */
	public static function handleWordPressErrorAsException($error)
	{
		if (!is_wp_error($error)) {
			return false;
		}

		NextADInt_Core_Util_LoggerUtil::error($error->get_error_messages());

		throw new NextADInt_Core_Exception_WordPressErrorException($error);
	}
}