<?php
/*
Plugin Name: Next Active Directory Integration
Plugin URI: https://www.active-directory-wp.com
Description: Enterprise-ready solution to authenticate, authorize and synchronize your Active Directory users to WordPress. Next Active Directory Authentication supports NTLM and Kerberos for Single Sign On.
Version: REPLACE_BY_JENKINS_SCRIPT
Author: active-directory-wp.com
Author URI: https://active-directory-wp.com
Text Domain: next-active-directory-integration
Domain Path: /languages
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

define('NEXT_ACTIVE_DIRECTORY_INTEGRATION_PLUGIN_PATH', plugin_dir_path( __FILE__ ));
require_once(dirname(__FILE__)."/constants.php");
require_once(dirname(__FILE__)."/Autoloader.php");
require_once(dirname(__FILE__)."/functions.php");

// init dummy logger in order to prevent fatal errors for outdated premium extensions
require_once(dirname(__FILE__) . "/classes/Core/DummyLogger/DummyLogger.php");

$autoLoader = new NextADInt_Autoloader();
$autoLoader->register();

// load plugin dependencies with composer autoloader
require_once(dirname(__FILE__)."/vendor/autoload.php");

// NADI-692: We have to skip the requirements check if wp-cli is used.
// Otherwise the requirements will/might fail if any of the required PHP modules is not enabled for php-cli and NADI will disable it on its own.
if (!defined('WP_CLI')) {
    $requirements = new NextADInt_Adi_Requirements();

    if (!$requirements->check()) {
        return;
    }
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
add_action('set_current_user', array($adiPlugin, 'run'));

// --- Active Multisite dashboard ---
// we need to register a second hook to decide if the network dashboard is shown.
// another possible solution would be using the hook 'redirect_network_admin_request' from network/admin.php but
// the loading of the menu happens to early
add_action('set_current_user', array($adiPlugin, 'runMultisite'));

/**
 * Global accessor for Next ADI dependencies.
 * You can call this function in your own extensions to gain access to the internals of NADI.
 *
 * @return NextADInt_Adi_Dependencies
 */
function next_ad_int() {
    return NextADInt_Adi_Dependencies::getInstance();
}
