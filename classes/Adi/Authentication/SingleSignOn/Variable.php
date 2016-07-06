<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_Authentication_SingleSignOn_Variable')) {
	return;
}

/**
 * Adi_Authentication_SingleSignOn_Variable contains all environment identifiers which could point to SSO user principle.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class Adi_Authentication_SingleSignOn_Variable
{
	const REMOTE_USER = 'REMOTE_USER';

	const X_REMOTE_USER = 'X-REMOTE-USER';

	public static function getValues()
	{
		return array(
			self::REMOTE_USER,
			self::X_REMOTE_USER,
		);
	}
}