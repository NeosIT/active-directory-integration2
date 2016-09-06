<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (!defined('WP_UNINSTALL_PLUGIN')) {
	wp_die('Plugin uninstalling is not authorized.');
}

require_once 'constants.php';
require_once NEXT_AD_INT_PATH . '/Autoloader.php';
$autoLoader = new NextADInt_Autoloader();
$autoLoader->register();

$uninstaller = new NextADInt_Core_Uninstaller();
$uninstaller->removePluginSettings();