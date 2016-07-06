<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_Authentication_Exception')) {
	return;
}

/**
 * Adi_Authentication_Exception is used for raising exceptions during the authentication.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class Adi_Authentication_Exception extends Core_Exception
{

}