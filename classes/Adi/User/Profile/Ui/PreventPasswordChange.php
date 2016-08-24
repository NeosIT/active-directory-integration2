<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_User_Profile_Ui_PreventPasswordChange')) {
	return;
}

/**
 * Depending upon the blog configuration this class disables the password field to prevent users from changing there password.
 * This hook is only executed for Active Directory members.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Christopher Klein <ckl@neos-it.de>
 * @access public
 */
class NextADInt_Adi_User_Profile_Ui_PreventPasswordChange
{
	/** @var $configuration NextADInt_Multisite_Configuration_Service */
	private $configuration;

	/** @var $userManager NextADInt_Adi_User_Manager */
	private $userManager;

	/**
	 * @param NextADInt_Multisite_Configuration_Service $configuration
	 * @param NextADInt_Adi_User_Manager $userManager
	 */
	public function __construct(NextADInt_Multisite_Configuration_Service $configuration,
								NextADInt_Adi_User_Manager $userManager)
	{
		$this->configuration = $configuration;
		$this->userManager = $userManager;
	}

	/**
	 * Disable password fields for a user.
	 */
	public function register()
	{
		// the callback functions have to decide whether to execute thm or not b/c they require the current user context

		// Is local password change disallowed?
		// disable password fields
		add_filter('show_password_fields', array($this, 'showPasswordFields'), 10, 2);
	}

	/**
	 * Return the value of th setting "Allow local password changes"
	 * @return bool
	 */
	public function isPasswordChangeEnabled() {
		return $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::ENABLE_PASSWORD_CHANGE);
	}

	/**
	 * WordPress callback for showing/hiding the password fields
	 *
	 * @param bool $show setting from prior called plug-ins
	 * @param WP_User $wpUser
	 * @return mixed bool
	 */
	public function showPasswordFields($show, $wpUser) {
		// if user is no AD member or a local user (admin etc.), the parent value has priority
		if (!$this->userManager->hasActiveDirectoryAccount($wpUser)) {
			return $show;
		}

		// user is AD member so decide by ADI setting
		return $this->isPasswordChangeEnabled();
	}
}