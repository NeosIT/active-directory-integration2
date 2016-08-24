<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Authentication_Ui_ShowBlockedMessage')) {
	return;
}

/**
 * NextADInt_Adi_Authentication_Ui_ShowBlockedMessage provides the functionality to display SSO related data in the frontend.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class NextADInt_Adi_Authentication_Ui_ShowBlockedMessage
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
		$message = __('Log in with SSO', NEXT_AD_INT_I18N);
		$url = esc_url(add_query_arg('reauth', 'sso'));
		echo '<p><a href="' . $url . '">' . $message . '</a></p>';
	}

}