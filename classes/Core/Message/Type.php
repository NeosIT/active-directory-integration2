<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Core_Message_Type')) {
	return;
}

/**
 * Core_Message_Type defines the m3essage type in the frontend.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Core_Message_Type
{
	const SUCCESS = 'success';
	const ERROR = 'error';
}