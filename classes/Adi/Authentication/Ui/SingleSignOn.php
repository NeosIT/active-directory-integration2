<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('SingleSignOnPage')) {
	return;
}

/**
 * Adi_Authentication_Ui_SingleSignOn provides the functionality to display SSO related data in the frontend.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class Adi_Authentication_Ui_SingleSignOn
{
	/**
	 * Register our action to add the SSO link to the login page.
	 */
	public function register()
	{
		add_action('login_form', array($this, 'generateLoginFooter'), 1);
	}

	/**
	 * Render the link to re-authenticate using SSO for the login page.
	 */
	public function generateLoginFooter()
	{
		$message = __('Log in with SSO', ADI_I18N);
		$url = esc_url(add_query_arg('reauth', 'sso'));
		echo '<p><a href="' . $url . '">' . $message . '</a></p>';
	}

}