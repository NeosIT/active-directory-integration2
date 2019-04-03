<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Authentication_Ui_SingleSignOn')) {
	return;
}

/**
 * NextADInt_Adi_Authentication_Ui_SingleSignOn provides the functionality to display SSO related data in the frontend.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class NextADInt_Adi_Authentication_Ui_SingleSignOn
{
    /**
     * @var bool
     */
    private $isRegistered = false;

	/**
	 * Register our action to add the SSO link to the login page.
	 */
	public function register()
	{
        // don't allow multiple registrations of the same LoginService instance
        if ($this->isRegistered) {
            return;
        }

		add_action('login_form', array($this, 'generateLoginFooter'), 1);
	}

	/**
	 * Render the link to re-authenticate using SSO for the login page.
	 */
	public function generateLoginFooter()
	{
		$message = __('Log in using SSO', 'next-active-directory-integration');
		$url = esc_url(add_query_arg('reauth', 'sso'));
		echo '<p><a href="' . $url . '">' . $message . '</a></p>';
	}
}