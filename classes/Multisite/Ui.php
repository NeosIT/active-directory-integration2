<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Ui')) {
	return;
}

/**
 * Multisite_Ui contains shared information between UI classes
 *
 * @author  Christopher Klein <ckl@neos-it.de>
 * @access  public
 */
class Multisite_Ui
{
	const VERSION_PAGE_JS = '1.0';
	const VERSION_PROFILE_JS = '1.0';
	const VERSION_CSS = '1.0';
}