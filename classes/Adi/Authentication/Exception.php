<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Authentication_Exception')) {
	return;
}

/**
 * NextADInt_Adi_Authentication_Exception is used for raising exceptions during the authentication.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class NextADInt_Adi_Authentication_Exception extends NextADInt_Core_Exception
{

}