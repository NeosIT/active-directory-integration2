<?php

namespace Dreitier\WordPress\Multisite\Ui;

/**
 * Actions holds a list of constants for WordPress actions.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 *
 * @access public
 */
class Actions
{
	public const ADI_REQUIREMENTS_ALL_ADMIN_NOTICES = 'all_admin_notices';

	public const ADI_MENU_ADMIN_MENU = 'admin_menu';
	public const ADI_MENU_NETWORK_ADMIN_MENU = 'network_admin_menu';
	public const ADI_MENU_WP_AJAX_PREFIX = 'wp_ajax_';

	public static function la() {
		die("TEST");
	}
	private function __construct()
	{
	}

	private function __clone()
	{
	}
}