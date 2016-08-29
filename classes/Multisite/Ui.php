<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Ui')) {
	return;
}

/**
 * NextADInt_Multisite_Ui contains shared information between UI classes
 *
 * @author  Christopher Klein <ckl@neos-it.de>
 * @access  public
 */
class NextADInt_Multisite_Ui
{
	const VERSION_PAGE_JS = '1.0';
	const VERSION_PROFILE_JS = '1.0';
	const VERSION_CSS = '1.0';
}