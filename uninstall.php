<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (!defined('WP_UNINSTALL_PLUGIN')) {
	wp_die('Plugin uninstalling is not authorized.');
}

require_once __DIR__ . "/autoload.php";
require_once __DIR__ . "/constants.php";

$uninstaller = new \Dreitier\Util\Uninstaller();
$uninstaller->removePluginSettings();