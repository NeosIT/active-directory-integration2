<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_Authentication_Sso')) {
	return;
}

/**
 * Adi_Authentication_Sso provides an auto login feature (Not implemented yet).
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access public
 */
class Adi_Authentication_Sso
{
	private $configuration;

	/**
	 * @param Multisite_Configuration_Service $configuration
	 */
	public function __construct(Multisite_Configuration_Service $configuration)
	{
		$this->configuration = $configuration;
	}


	function autoLogin()
	{
		if (!is_user_logged_in()) {
			$this->loginForm();
		}
	}

	function loginForm()
	{
		if (!empty($_SERVER["REMOTE_USER"])) {


			$username = $_SERVER['REMOTE_USER'];

			$user = get_user_by('login', $username);
			var_dump($username);
			if (!is_a($user, 'WP_User')) {
				//Todo Was ist das für eine Abfrage, was bewirkt diese ?
			}

			if ($user && $username == $user->user_login) {
				// Clean buffers
				ob_clean();
				// Feed WordPress a double-MD5 hash (MD5 of value generated in check_passwords)
				$password = $user->user_pass;

				// User is now authorized; force WordPress to use the generated password
				$using_cookie = true;
				wp_set_auth_cookie($user->ID, true);


				// Redirect and stop execution
				$redirectUrl = home_url();
				if (isset($_GET['redirect_to'])) {
					$redirectUrl = $_GET['redirect_to'];
				}
				wp_redirect($redirectUrl);
				exit();
			}
		}
	}


	function register()
	{
		if ($this->configuration->getOptionValue(Adi_Configuration_Options::AUTO_LOGIN)) {
			add_action('init', array($this, 'autoLogin'));
			add_action('login_form', array($this, 'loginForm'));
		}
	}

}