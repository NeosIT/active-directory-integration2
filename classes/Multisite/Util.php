<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Util')) {
	return;
}

/**
 * NextADInt_Multisite_Util TODO long description
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class NextADInt_Multisite_Util
{
	private function __construct()
	{

	}

	private function __clone()
	{

	}

	/**
	 * Check if the user is currently on the network Dashboard.
	 *
	 * @return bool
	 */
	public static function isOnNetworkDashboard()
	{
		// network admin + ajax requests
		// see: https://core.trac.wordpress.org/ticket/22589
		if (defined('DOING_AJAX') && DOING_AJAX && is_multisite()
			&& preg_match('#^' . network_admin_url() . '#i', $_SERVER['HTTP_REFERER'])
		) {
			return true;
		}

		// it is a multisite installation, the user is network administrator and the current page is the network dashboard
		return (is_multisite() && is_super_admin() && is_network_admin());
	}
}