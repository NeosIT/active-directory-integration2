<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Authentication_SingleSignOn_Variable')) {
	return;
}

/**
 * NextADInt_Adi_Authentication_SingleSignOn_Variable contains all environment identifiers which could point to SSO user principle.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Stefan Fiedler <sfi@neos-it.de>
 *
 * @access
 */
class NextADInt_Adi_Authentication_SingleSignOn_Variable
{
	const REMOTE_USER = 'REMOTE_USER';

	const X_REMOTE_USER = 'X-REMOTE-USER';
    // ADI-389 see github issue#29
    const HTTP_X_REMOTE_USER = 'HTTP_X_REMOTE_USER';

	public static function getValues()
	{
		return array(
			self::REMOTE_USER,
			self::X_REMOTE_USER,
            self::HTTP_X_REMOTE_USER
		);
	}
}