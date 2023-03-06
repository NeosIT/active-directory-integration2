<?php
/**
 * Create stubs for classes which are required by older NADI PEs.
 * @return void
 */
function next_ad_int_create_class_stubs()
{
	if (!class_exists('NextADInt_Adi_Init')) {
		class NextADInt_Adi_Init
		{

		}
	}

	if (!class_exists('NextADInt_Core_Logger')) {
		class NextADInt_Core_Logger extends \Dreitier\Nadi\Log\NadiLog
		{
		}
	}

	if (!class_exists('Logger')) {
		require_once __DIR__ . '/logger.php';
	}

	if (!class_exists('\Monolog\Registry')) {
		require_once __DIR__ . '/monolog_registry.php';
	}

	// BEGIN: compat nadiext-buddypress-simpleattributes
	if (!defined('NEXT_AD_INT_PREFIX')) {
		define('NEXT_AD_INT_PREFIX', NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX);
	}
	// END

	// BEGIN: nadiext-wp-cli
	if (!class_exists('\Monolog\Handler\NullHandler')) {
		require_once __DIR__ . '/monolog_handler_nullhandler.php';
	}

	if (!class_exists('\Monolog\Logger')) {
		require_once __DIR__ . '/monolog_logger.php';
	}
	// END
}

// when running in WordPress, we postpone the stub creation so that every other plug-in has the chance to call its autoloader.
// this prevents issues like #181: the-events-calendar can initialize its non-repackaged Monolog instance and we can use it.
if (function_exists('add_action')) {
	add_action('plugins_loaded', function () {
		next_ad_int_create_class_stubs();
	});
}
// when running unit tests, initialize stubs directly
else {
	next_ad_int_create_class_stubs();
}
