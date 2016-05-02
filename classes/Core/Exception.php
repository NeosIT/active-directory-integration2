<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Core_Exception')) {
	return;
}

/**
 * Core_Exception is a base class for own execptions
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class Core_Exception extends Exception
{
}