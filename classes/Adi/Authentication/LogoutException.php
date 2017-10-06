<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Authentication_LogoutException')) {
	return;
}

/**
 * NextADInt_Adi_Authentication_LogoutException is used to mark a current logout process. It is a marker exception and not critical.
 *
 * @author  Christopher Klein <ckl@neos-it.de>
 *
 * @access
 */
class NextADInt_Adi_Authentication_LogoutException extends NextADInt_Core_Exception
{

}