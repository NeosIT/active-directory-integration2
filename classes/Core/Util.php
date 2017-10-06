<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_Util')) {
	return;
}

/**
 * Core_Util provides access to utility methods.
 * 
 * This class has been introduced to abstract the access to PHP version-dependent methods and make them mockable during the test phase.
 *
 * @author Christopher Klein <ckl@neos-it.de>
 *
 * @access public
 */
class NextADInt_Core_Util
{
	private function __construct() {
	}

	/**
	 * @var NextADInt_Core_Util_Internal_Native
	 */
	private static $native = null;
	
	/**
	 * Gain access to PHP's native functions which could be disabled in certain environments
	 *
	 * @param none|null|Adi_Util_Internal_Native {0} 
	 *		if no parameter is provided the current Adi_Util_Internal_Native instance is returned.
	 *		if the parameter is explicitly null, the current instance is resetted and replaced with a fresh instance
	 *		if the parameter is not null it is set as $native object. This must be used inside the test environment
	 *
	 * @return NextADInt_Core_Util_Internal_Native not null
	 */
	public static function native() {
		$args = func_get_args();
		$instance = self::$native;
		
		// (re)set native PHP functions?
		if (sizeof($args) > 0) {
			$instance = $args[0];
		}
		
		if ($instance == null) {
			// create new instance
			self::$native = new NextADInt_Core_Util_Internal_Native();
		} 
		else {
			// get instance from parameter
			self::$native = $instance;
		}
		
		return self::$native;
	}
}