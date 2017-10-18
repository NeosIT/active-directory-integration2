<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Authentication_PasswordValidationService')) {
	return;
}

/**
 * This class adds a hook for WordPress' "check_password" filter to override it with ADIs password check against the Active Directory.
 * 
 * @author  Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class NextADInt_Adi_Authentication_PasswordValidationService
{
	/* @var NextADInt_Adi_Authentication_LoginService $loginService */
	private $loginService;

	/* @var NextADInt_Multisite_Configuration_Service $configuration */
	private $configuration;

	/* @var Logger $logger */
	private $logger;

	/**
	 * @param NextADInt_Adi_Authentication_LoginService $loginService
	 * @param NextADInt_Multisite_Configuration_Service  $configuration
	 */
	public function __construct(NextADInt_Adi_Authentication_LoginService $loginService,
								NextADInt_Multisite_Configuration_Service $configuration)
	{
		$this->loginService = $loginService;
		$this->configuration = $configuration;

		$this->logger = NextADInt_Core_Logger::getLogger();
	}

	/**
	 * Override WordPress password check (for using Active Directory passwords)
	 */
	public function register()
	{
		add_filter('check_password', array($this, 'overridePasswordCheck'), 10, 4);
	}

	/**
	 * The local WordPress password check will be used for user ID 1.
	 * The password for authenticated user is always ok.
	 * The password for disabled user is never ok.
	 * The WordPress password check will be used, if an user is not authenticated, has a samAccountName and FALLBACK_TO_LOCAL_PASSWORD is activated.
	 *
	 * @param bool $check
	 * @param string $password
	 * @param string $hash
	 * @param int $userId
	 *
	 * @return bool
	 * This method will check the users credentials if he can not be authenticated by LoginService.php
	 * If FALLBACK_TO_LOCAL_PASSWORD is disabled, the credentials of this user (created by this plugin) will be denied.
	 * If the user is disabled by this plugin, the credentials of this user will be denied.
	 */
	public function overridePasswordCheck($check, $password, $hash, $userId)
	{
		// always use local password handling for user_id 1 (admin)
		if ($userId == 1) {
			$this->logger->debug('UserID 1: using local (WordPress) password check.');

			return $check;
		}

		// return true for users authenticated by ADI (should never happen, but who knows?)
		if ($this->loginService->isCurrentUserAuthenticated()) {
			$this->logger->debug('User successfully authenticated by the "Active Directory Integration" plugin: override local (WordPress) password check.');

			return true;
		}

		// return false if user is disabled
		if (get_user_meta($userId, NEXT_AD_INT_PREFIX . 'user_disabled', true)) {
			$reason = get_user_meta($userId, NEXT_AD_INT_PREFIX . 'user_disabled_reason', true);
			$this->logger->debug("User is disabled. Reason: $reason");

			return false;
		}

		// only check for local password if this is not an AD user and if fallback to local password is active
		$userCheck = get_user_meta($userId, NEXT_AD_INT_PREFIX . 'samaccountname', true);

		if (!$userCheck) {
			// use local password check in all other cases
			$this->logger->debug('Using local (WordPress) password check.');

			return $check;
		}
		
		$fallbackToLocalPassword = $this->configuration->getOptionValue(
			NextADInt_Adi_Configuration_Options::FALLBACK_TO_LOCAL_PASSWORD
		);
			
		if ($fallbackToLocalPassword) {
			$this->logger->debug('User from AD. Falling back to local (WordPress) password check.');
			
			return $check;
		}
		
		$this->logger->debug('User from AD and fallback to local (WordPress) password deactivated. Authentication failed.');
		
		return false;
	}
}