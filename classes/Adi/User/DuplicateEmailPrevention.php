<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_User_DuplicateEmailPrevention')) {
	return;
}

/**
 * Adi_User_DuplicateEmailPrevention contains the values for the meta data
 * {@see Adi_Configuration_Options::DUPLICATE_EMAIL_PREVENTION}.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
abstract class Adi_User_DuplicateEmailPrevention
{
	const PREVENT = 'prevent',
		CREATE = 'create',
		ALLOW = 'allow';

	private function __construct()
	{

	}

	private function __clone()
	{

	}
}