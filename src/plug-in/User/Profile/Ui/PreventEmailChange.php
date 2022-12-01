<?php
namespace Dreitier\Nadi\User\Profile\Ui;

use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Log\NadiLog;
use Dreitier\Nadi\Vendor\Monolog\Logger;
use Dreitier\WordPress\Multisite\Configuration\Service;

/**
 * PreventEmailChange prevents user from changing their email address.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class PreventEmailChange
{
	/* @var Service $multisiteConfigurationService */
	private $multisiteConfigurationService;

	/* @var Logger $logger */
	private $logger;

	/**
	 * @param Service $configuration
	 */
	public function __construct(Service $configuration)
	{
		$this->multisiteConfigurationService = $configuration;

		$this->logger = NadiLog::getInstance();
	}

	/**
	 * This method prevents that the user can change his email.
	 */
	public function register()
	{
		//if PREVENT_EMAIL_CHANGE is forbidden, then leave.
		$preventEmailChange = $this->multisiteConfigurationService->getOptionValue(Options::PREVENT_EMAIL_CHANGE);
		if (!$preventEmailChange) {
			return;
		}

		//deactivate the email field and prevent an email address change
		add_action('show_user_profile', array($this, 'disableEmailField'));
		add_action('personal_options_update', array($this, 'addMissingEmailAddressToPOST'));
		add_action('user_profile_update_errors', array($this, 'preventEmailChange'), 0, 3);
	}

	/**
	 * Disable email field in user profile if needed (actions edit_user_profile and show_user_profile)
	 * This is not safe and only for cosmetic reasons, but we also have the method prevent_email_change() (see below)
	 *
	 * @param \WP_User $user
	 */
	public function disableEmailField($user)
	{
		$samAccountName = get_user_meta($user->ID,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'samaccountname', true);
		$admin = current_user_can('manage_options');

		// disable email field if needed (dirty hack)
		if ($samAccountName && !$admin) {
			echo "<script type=\"text/javascript\">
                var email = document.getElementById('email');
                if (email) {
                    email.setAttribute('disabled', 'disabled');
                }
            </script>";
		}
	}

	/**
	 * Prevent ADI users from changing their email (action user_profile_update_errors)
	 *
	 * @param object $errors
	 * @param bool $update
	 * @param \WP_User $user
	 */
	public function preventEmailChange(&$errors, $update, &$user)
	{
        // ADI-670: Compatibility fix with other plug-ins
        if (!is_object($user) || !property_exists($user, 'ID')) {
            $this->logger->warning("Some malicious plug-ins have changed the expected \$user parameter so we can't prevent email changes for user");
            return;
        }

        // prevent emails
		add_filter('send_password_change_email', '__return_false');
		add_filter('send_email_change_email', '__return_false');

		$samAccountName = get_user_meta($user->ID,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'samaccountname', true);
		$admin = current_user_can('manage_options');

		if ($samAccountName && !$admin) {
			// get all possible email value
			$oldEmail = get_user_by('id', $user->ID)->user_email;

			// if email address was changed, then throw an error and prevent the insert of option values
			if (isset($_POST[NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'email_change'])) {
				$this->logger->debug( "Prevent email change on profile update for user '$user->user_login' ($user->ID).");
				$errors = new \WP_Error(
					'prevent email change', __(
						"You must not change your email address. The plugin 'Next Active Directory Integration' prevents it.",
						'next-active-directory-integration'
					)
				);
			}

			$_POST['email'] = $oldEmail;
			$_REQUEST['email'] = $oldEmail;
			$user->user_email = $oldEmail;
			delete_option($user->ID . '_new_email');
		}
	}

	/**
	 * WordPress needs the $_POST value for 'email' but the form profile.php ignores deactivated elements.
	 * That's why i have to insert the email address for the user $userId from the database into $_POST.
	 *
	 * @param int $userId
	 */
	public function addMissingEmailAddressToPOST($userId)
	{
		// only add the value if user has a samaccountname and not an admin
		$samAccountName = get_user_meta($userId,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'samaccountname', true);
		$admin = current_user_can('manage_options');

		// leave if user of this profile has no samAccountName or the current user is an admin
		if (!$samAccountName || $admin) {
			return;
		}

		if (isset($_POST['email'])) {
			$_POST[NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'email_change'] = true;
		}

		// set old email values
		$old = get_user_by('id', $userId);
		$_POST['email'] = $old->user_email;
		$_REQUEST['email'] = $old->user_email;
	}
}