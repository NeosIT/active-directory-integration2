<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Ui_Actions')) {
	return;
}

/**
 * NextADInt_Adi_Ui_Actions holds a list of constants for WordPress actions.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 *
 * @access public
 */
class NextADInt_Adi_Ui_Actions
{
	const ADI_REQUIREMENTS_ALL_ADMIN_NOTICES = 'all_admin_notices';

	const ADI_MENU_ADMIN_MENU = 'admin_menu';
	const ADI_MENU_NETWORK_ADMIN_MENU = 'network_admin_menu';
	const ADI_MENU_WP_AJAX_PREFIX = 'wp_ajax_';

	private function __construct()
	{
	}

	private function __clone()
	{
	}
}