<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_User_Profile_Ui_ProvideDisableUserOption')) {
	return;
}

/**
 * NextADInt_Adi_User_Profile_Ui_ProvideDisableUserOption Extend user profiles with the possibility of disabling the corresponding user.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class NextADInt_Adi_User_Profile_Ui_ProvideDisableUserOption
{
	const CAPABILITY = 'manage_options';
	const TEMPLATE_NAME = 'user-profile-option.twig';

	/** @var NextADInt_Multisite_View_TwigContainer  */
	private $twigContainer;

	/** @var NextADInt_Adi_User_Manager */
	private $userManager;

	/**
	 * @param NextADInt_Multisite_View_TwigContainer $twigContainer
	 * @param NextADInt_Adi_User_Manager $userManager
	 */
	public function __construct(NextADInt_Multisite_View_TwigContainer $twigContainer,
								NextADInt_Adi_User_Manager $userManager)
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

		// translate twig text
        $i18n = array(
            'userDisabled' => __('User Disabled', 'next-active-directory-integration'),
            'emailWillBeChanged' => __('If selected, the user can not log in and his e-mail address will be changed for security reasons. The e-mail address is restored if the user is reenabled.', 'next-active-directory-integration'),
            'informationOnLastDisabling' => __('Information on last disabling: ', 'next-active-directory-integration'),
            'warning' => __('Attention: This flag is automatically set (or unset) by Sync to WordPresss and its state may change on next run of synchronization.', 'next-active-directory-integration')
        );
		$i18n = NextADInt_Core_Util_EscapeUtil::escapeHarmfulHtml($i18n);

		echo $this->twigContainer->getTwig()->render(
			self::TEMPLATE_NAME, array(
				'userDisabled'   => $this->userManager->isDisabled($user->ID),
				'disabledReason' => get_user_meta($user->ID, NEXT_AD_INT_PREFIX . 'user_disabled_reason', true),
                'i18n' => $i18n
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
		// $value 0 => user should be unblocked
		// $value 1 => user should be blocked
        // dont unescape $_POST because only numbers will be accessed
		$value = $_POST[NEXT_AD_INT_PREFIX . 'user_disabled'];
		$disabled = $this->userManager->isDisabled($userId);

		// user is not blocked and he should be blocked
		if ($value === '1' && !$disabled) {

			// Get data of the user to be disabled and of the current user
			$disabledUser = get_userdata($userId);
			$disabledBy = wp_get_current_user();

			$message = sprintf(
				__('User "%s" with ID "%s" manually disabled by "%s" with the ID "%s".', 'next-active-directory-integration'), $disabledUser->user_login, $userId, $disabledBy->user_login, $disabledBy->ID
			);
			$this->userManager->disable($userId, $message);

			// use the new user email address
			$user = get_user_by('id', $userId);
			$_POST['email'] = $user->user_email;

		} else if (!$value && $disabled) {
			// enable user
			$this->userManager->enable($userId);

			// use the new user email address
			$user = get_user_by('id', $userId);
			$_POST['email'] = $user->user_email;
		}
	}
}