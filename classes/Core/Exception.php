<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_Exception')) {
	return;
}

/**
 * Core_Exception is a base class for own execptions
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class NextADInt_Core_Exception extends Exception
{
}