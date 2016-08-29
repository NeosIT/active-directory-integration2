<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_View_Page')) {
	return;
}

/**
 * Provide generic methods for WordPress administration views
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 *
 * @access public
 */
interface NextADInt_Multisite_View_Page
{
	/**
	 * Get the page title.
	 *
	 * @return mixed
	 */
	public function getTitle();

	/**
	 * Get the menu slug for the page.
	 *
	 * @return mixed
	 */
	public function getSlug();

	/**
	 * Get the slug for post requests.
	 *
	 * @return mixed
	 */
	public function wpAjaxSlug();
}