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

define('NEXT_ACTIVE_DIRECTORY_INTEGRATION_PLUGIN_PATH', plugin_dir_path(__FILE__));
require_once(dirname(__FILE__) . "/constants.php");
require_once(dirname(__FILE__) . "/Autoloader.php");
require_once(dirname(__FILE__) . "/functions.php");

// init dummy logger in order to prevent fatal errors for outdated premium extensions
require_once(dirname(__FILE__) . "/classes/Core/DummyLogger/DummyLogger.php");

$autoLoader = new NextADInt_Autoloader();
$autoLoader->register();

// load plugin dependencies with composer autoloader
require_once(dirname(__FILE__) . "/vendor/autoload.php");

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

function prefix_plugin_update_message($plugin_data, $response)
{
	/*
	if (version_compare('3.0.0', $plugin_data['new_version'], '>') || version_compare('3.0.0', $plugin_data['Version'], '<=')) {
		return;
	}*/

	$update_notice = '</p><div class="wc_plugin_upgrade_notice">';

	$summary = 'https://active-directory-wp.com/2022/12/02/important-breaking-changes-with-nadi-3-0-0/';
	$milestone = 'https://github.com/NeosIT/active-directory-integration2/milestone/11';

	$update_notice .= sprintf(__('<strong>Warning!</strong> Upcoming version 3.0.0 of Next Active Directory Integration requires PHP 8.0 to work. <br />Please read the <a href="%s">major version\'s summary</a> and the full <a href="%s">milestone description</a> carefully.'), $summary, $milestone);
	$affectedPremiumExtensions = array();

	foreach (get_plugins() as $path => $plugin) {
		if (strpos($path, 'nadiext') !== FALSE || (isset($plugin['Name']) && (strpos($plugin['Name'], 'Next Active Directory Integration:') !== FALSE))) {
			$affectedPremiumExtensions[] = $plugin['Name'];
		}
	}

	if (sizeof($affectedPremiumExtensions) > 0) {
		$update_notice .= sprintf(__('<br /><br />Furthermore, the following NADI Premium Extensions require a mandatory upgrade to be usable with NADI 3.0.0 and later: <ul><li>%s</li></ul> '), implode("</li><li>", $affectedPremiumExtensions));
	}

	$update_notice .= '</div>';

	echo wp_kses_post($update_notice);
}

add_action('in_plugin_update_message-next-active-directory-integration/index.php', 'prefix_plugin_update_message', 10, 2);

/**
 * Global accessor for Next ADI dependencies.
 * You can call this function in your own extensions to gain access to the internals of NADI.
 *
 * @return NextADInt_Adi_Dependencies
 */
function next_ad_int()
{
	return NextADInt_Adi_Dependencies::getInstance();
}

function next_ad_int_logger()
{
	return NextADInt_Core_Logger::getLogger();
}