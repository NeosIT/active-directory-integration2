<?php
if (!class_exists('NextADInt_Adi_Init')) {
	class NextADInt_Adi_Init
	{

	}
}

if (!class_exists('NextADInt_Core_Logger')) {
	class NextADInt_Core_Logger extends \Dreitier\Nadi\Log\NadiLog {
	}
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