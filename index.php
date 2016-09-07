<?php
/*
Plugin Name: Next Active Directory Integration
Plugin URI: https://www.active-directory-wp.com
Description: This is the successor of the Active Directory Integration plug-in which allows you to authenticate, authorize, create and update users through Active Directory.
Version: 2.0.1
Author: NeosIT GmbH
Author URI: http://www.neos-it.de/
License: GPLv3

The work is derived from version 1.0.5 of the plugin Active Directory Authentication:
OriginalPlugin URI: http://soc.qc.edu/jonathan/wordpress-ad-auth
OriginalDescription: Allows WordPress to authenticate users through Active Directory
OriginalAuthor: Jonathan Marc Bearak
OriginalAuthor URI: http://soc.qc.edu/jonathan
*/

if (!defined('ABSPATH')) {
	die('Access denied.');
}

require_once 'constants.php';
require_once 'Autoloader.php';
$autoLoader = new NextADInt_Autoloader();
$autoLoader->register();

require_once 'functions.php';
if (!class_exists('Logger')) {
    require_once 'vendor/apache/log4php/src/main/php/Logger.php';
}

$requirements = new NextADInt_Adi_Requirements();
if (!$requirements->check()) {
	return;
}

// start plugin
$adiPlugin = new NextADInt_Adi_Init();

// register basic hooks
register_activation_hook(__FILE__, array($adiPlugin, 'activation'));
register_uninstall_hook(__FILE__, array('NextADInt_Adi_Init' /* static */, 'uninstall'));

add_action('plugins_loaded', 'next_ad_int_angular_ajax_params_to_post');

// register any hooks after the plug-in has been activated e.g. to display notices for a migration of options
add_action('admin_init', array($adiPlugin, 'postActivation'));

// --- Normal Blog / Single Site ---
// execute the plugin and their hooks after the 'plugins_loaded' hook has been called
// so we can use WordPress functions for lazy-loading
add_action('plugins_loaded', array($adiPlugin, 'run'));

// --- Active Multisite dashboard ---
// we need to register a second hook to decide if the network dashboard is shown.
// another possible solution would be using the hook 'redirect_network_admin_request' from network/admin.php but
// the loading of the menu happens to early
add_action('plugins_loaded', array($adiPlugin, 'runMultisite'));
