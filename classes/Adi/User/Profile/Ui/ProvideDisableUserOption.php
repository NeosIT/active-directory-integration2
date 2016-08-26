<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_User_Profile_Ui_ProvideDisableUserOption')) {
	return;
}

/**
 * Adi_User_Profile_Ui_ProvideDisableUserOption Extend user profiles with the possibility of disabling the corresponding user.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class Adi_User_Profile_Ui_ProvideDisableUserOption
{
	const CAPABILITY = 'manage_options';
	const TEMPLATE_NAME = 'user-profile-option.twig';

	/** @var Multisite_View_TwigContainer  */
	private $twigContainer;

	/** @var Adi_User_Manager */
	private $userManager;

	/**
	 * @param Multisite_View_TwigContainer $twigContainer
	 * @param Adi_User_Manager $userManager
	 */
	public function __construct(Multisite_View_TwigContainer $twigContainer,
								Adi_User_Manager $userManager)
	{
		$this->twigContainer = $twigContainer;
		$this->userManager = $userManager;
	}

	/**
	 * Extend user profiles with the possibility of disabling the corresponding user.
	 */
	public function register()
	{
		add_action('edit_user_profile', array($this, 'addOption'));

		//save the 'disable user' option for your own user profile
		//add_action('personal_options_update', array(&$this, 'profile_update')); TODO der User soll sich doch nicht selbst aussperren können oder?
		//save the 'disable user' option to all other user profiles
		add_action('edit_user_profile_update', array(&$this, 'saveOption'), 100, 1);
	}

	/**
	 * Add an 'disable-user' checkbox to foreign user profiles.
	 *
	 * @param WP_User $user
	 */
	public function addOption($user)
	{
		// User disabled only visible for admins
		if (!current_user_can(self::CAPABILITY)) {
			return;
		}

		//and not for user with ID 1 (admin) and not for ourselves
		if ($user->ID == 1) {
			return;
		}

		echo $this->twigContainer->getTwig()->render(
			self::TEMPLATE_NAME, array(
				'userDisabled'   => $this->userManager->isDisabled($user->ID),
				'disabledReason' => get_user_meta($user->ID, ADI_PREFIX . 'user_disabled_reason', true),
			)
		);
	}

	/**
	 * Disable or enable the user if the status of the 'disable-user' checkbox has been changed.
	 *
	 * @param int $userId
	 */
	public function saveOption($userId)
	{
		//$value 0 => user should be unblocked
		//$value 1 => user should be blocked
		$value = $_POST[ADI_PREFIX . 'user_disabled'];
		$disabled = $this->userManager->isDisabled($userId);

		//user is not blocked and he should be blocked
		if ($value === '1' && !$disabled) {
			//disable user
			$username = get_userdata($userId);
			$message = sprintf(
				__('User manually disabled by "%s" with the ID %s.', NEXT_AD_INT_I18N), $username->user_login, $userId
			);
			$this->userManager->disable($userId, $message);

			//use the new user email address
			$user = get_user_by('id', $userId);
			$_POST['email'] = $user->user_email;

		} else if (!$value && $disabled) {
			//enable user
			$this->userManager->enable($userId);

			//use the new user email address
			$user = get_user_by('id', $userId);
			$_POST['email'] = $user->user_email;
		}
	}
}