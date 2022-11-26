<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (!defined('WP_UNINSTALL_PLUGIN')) {
	wp_die('Plugin uninstalling is not authorized.');
}

require_once 'constants.php';

// include any packages required during testing like WP_Mock
require_once NEXT_AD_INT_PATH . "/vendor/autoload.php";
// include vendored packages
require_once NEXT_AD_INT_PATH . "/vendor-repackaged/autoload.php";

$uninstaller = new \Dreitier\Util\Uninstaller();
$uninstaller->removePluginSettings();