<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_User_Profile_Ui_TriggerActiveDirectorySynchronization')) {
	return;
}

/**
 * NextADInt_Adi_User_Profile_Ui_TriggerActiveDirectorySynchronization Provides the possibility to sync the current user back to the Ad.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class NextADInt_Adi_User_Profile_Ui_TriggerActiveDirectorySynchronization
{
	/* @var NextADInt_Multisite_Configuration_Service */
	private $configuration;

	/* @var NextADInt_Adi_Synchronization_ActiveDirectory */
	private $syncToActiveDirectory;

	/* @var NextADInt_Ldap_Attribute_Repository */
	private $attributeRepository;

	/* @var Logger */
	private $logger;

	/**
	 * @var array
	 */
	private $errors;

	const FORM_PASSWORD = 'active_directory_password';

	/**
	 * @param NextADInt_Multisite_Configuration_Service $configuration
	 * @param NextADInt_Adi_Synchronization_ActiveDirectory $syncToActiveDirectory
	 * @param NextADInt_Ldap_Attribute_Repository  $attributeRepository
	 * @param array $errors array containing errors for unit testing
	 */
	public function __construct(NextADInt_Multisite_Configuration_Service $configuration,
								NextADInt_Adi_Synchronization_ActiveDirectory $syncToActiveDirectory,
								NextADInt_Ldap_Attribute_Repository $attributeRepository,
								$errors = array()) {
		$this->configuration = $configuration;
		$this->syncToActiveDirectory = $syncToActiveDirectory;
		$this->attributeRepository = $attributeRepository;
		$this->errors = $errors;

		$this->logger = NextADInt_Core_Logger::getLogger();
	}

	/**
	 * Register WordPress callbacks for 'personal_options_update' and 'edit_user_profile_update'
	 */
	public function register()
	{
		//add this save listeners
		add_action('personal_options_update', array($this, 'updateOwnProfile'));
		add_action('edit_user_profile_update', array($this, 'updateForeignProfile'));
	}

	/**
	 * Delegate to updateProfile($userId, false)
	 * @param int $userId
	 * @return bool
	 */
	public function updateForeignProfile($userId) {
		return $this->updateProfile($userId, false);
	}

	/**
	 * Delegate to updateProfile($userId, true)
	 * @param int $userId
	 * @return bool
	 */
	public function updateOwnProfile($userId) {
		return $this->updateProfile($userId, true);
	}


	/**
	 * Update user meta from profile page
	 * Here we can write user meta information back to AD. User disable status is set in profile_update_disable_user().
	 *
	 * @param int $userId
	 * @param bool $isOwnProfile Should the own user profile edited
	 * @return bool
	 */
	public function updateProfile($userId, $isOwnProfile)
	{
		// add an action, so we can show errors on profile page
		add_action('user_profile_update_errors', array($this, 'generateError'), 10, 3);

        // ADI-357 unescape already escaped $_POST
        $post = stripslashes_deep($_POST);

		// update user meta information
		$this->updateWordPressProfile($userId, $post);

		if ($this->syncToActiveDirectory->isEditable($userId, $isOwnProfile)) {
			return $this->triggerSyncToActiveDirectory($userId, $post);
		}

		return true;
	}

	/**
	 * Update the user meta information
	 *
	 * @param int $userId
	 * @param array $data
	 */
	public function updateWordPressProfile($userId, $data)
	{
		/* @var $attributes NextADInt_Ldap_Attribute */
		$attributes = $this->attributeRepository->filterWhitelistedAttributes(true);

		foreach ($attributes as $attributeName => $attribute) {
			// get the value for the user meta key from $_POST
			$metaKey = $attribute->getMetakey();
			
			if (!$metaKey) {
				continue;
			}

			// key is not present in request
			if (!isset($data[$metaKey])) {
				continue;
			}

			// get the corresponding user meta key for the Active Directory attribute name of the current user attribute
			$value = $data[$metaKey];

			// $data[$metakey] contains the raw value - use this string instead of an processed array
			update_user_meta($userId, $metaKey, $value);
		}
	}

	/**
	 * Update the user attribute values in the Active Directory of the corresponding user $userId
	 *
	 * @param int $userId
	 * @param array $data
	 * @return bool
	 * @throws Exception
	 */
	public function triggerSyncToActiveDirectory($userId, $data)
	{
		$useGlobalSyncUser = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SYNC_TO_AD_USE_GLOBAL_USER);
		if ($useGlobalSyncUser) {
			$globalSyncUserName = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_USER);
			$globalSyncUserPassword =$this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD);
			if ($globalSyncUserName === "" || $globalSyncUserPassword === "") {
				$this->errors[] = array(
					'active_directory_integration_missing_service_account_credentials',
					__('Error on writing additional attributes back to Active Directory. Service Account is not setup properly. Please contact your WordPress administrator.', 'next-active-directory-integration'),
				);
				return false;
			}
		}

		$this->logger->debug("Synchronizing user's profile back to Active Directory");

		// Get User Data
		$userInfo = get_userdata($userId);
		$passwordKey = NEXT_AD_INT_PREFIX . self::FORM_PASSWORD;
		$password  = isset($data[$passwordKey]) ? $data[$passwordKey] : null;


		// if a password has been provided, the user's account is used for the LDAP authentication
		$ldapConnectionDetails = $this->createLdapConnectionDetails($userInfo, $password);

		if (!$ldapConnectionDetails) {
			$this->errors[] = array(
				'syncToAd_no_password',
				__('No password given, so additional attributes have not been written back to Active Directory', 'next-active-directory-integration'),
			);

			return false;
		}

		$status = false;

		try {
			$status = $this->syncToActiveDirectory->synchronize($userInfo->ID, $ldapConnectionDetails->username, $ldapConnectionDetails->password);
		}
		catch (Exception $e) {
		}

		if (!$status) {
			$this->errors[] = array(
				'active_directory_integration_wrong_password',
				__('Error on writing additional attributes back to Active Directory. Please contact your WordPress administrator?', 'next-active-directory-integration'),
			);

			return false;
		}

		return true;
	}

	/**
	 * Create the details for the synchronization connection
	 *
	 * @param WP_User $userInfo
	 * @param string|null $customPassword if null the service account is used
	 * @return null|stdClass null if no service account is used and password is missing, else stdClass{username, password}
	 */
	function createLdapConnectionDetails($userInfo, $customPassword = null) {
		$r = new stdClass();

		$username = null;
		$password = null;

		$useServiceAccount = $this->syncToActiveDirectory->isServiceAccountEnabled();

		if ($useServiceAccount) {
			$username = $this->syncToActiveDirectory->getServiceAccountUsername();
			$password = $this->syncToActiveDirectory->getServiceAccountPassword();
		} else {
			if (empty($customPassword)) {
				return null;
			}

			$username = get_user_meta($userInfo->ID, NEXT_AD_INT_PREFIX . 'userprincipalname', true);
			$password = stripslashes($customPassword);
		}


		$r->username = $username;
		$r->password = $password;

		return $r;
	}

	/**
	 * Add errors to the global error object
	 *
	 * @param $wpError WP_Error
	 * @param $update
	 * @param $user
	 */
	function generateError($wpError, $update, $user)
	{
		// $this->errors is an array
		foreach ($this->errors as $error) {
			switch (sizeof($error)) {
				case 2:
					$wpError->add($error[0], $error[1]);
					break;
				case 3:
					$wpError->add($error[0], $error[1], $error[2]);
					break;
			}
		}
	}
}