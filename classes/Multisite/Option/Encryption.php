<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Option_Encryption')) {
	return;
}

/**
 * Multisite_Option_Encryption holds all values for our encryption type.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Multisite_Option_Encryption
{
	const NONE = 'none',
		STARTTLS = 'starttls',
		LDAPS = 'ldaps';
}