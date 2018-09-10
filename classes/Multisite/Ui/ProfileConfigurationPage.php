<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Ui_ProfileConfigurationPage')) {
	return;
}

/**
 * NextADInt_Multisite_Ui_ProfileConfigurationPage represents the BlogOption page in WordPress.
 *
 * NextADInt_Multisite_Ui_ProfileConfigurationPage holds the methods for interacting with WordPress, displaying the rendered template
 * and saving the data.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 *
 * @access public
 */
class NextADInt_Multisite_Ui_ProfileConfigurationPage extends NextADInt_Multisite_Ui_BlogConfigurationPage
{
	const SUB_ACTION_GENERATE_AUTHCODE = 'generateNewAuthCode';
	const SUB_ACTION_SAVE_PROFILE = 'saveProfile';
	const SUB_ACTION_REMOVE_PROFILE = 'removeProfile';
	const SUB_ACTION_GET_PROFILE_OPTION_VALUES = 'getProfileOptionsValues';
	const SUB_ACTION_PERSIST_PROFILE_OPTION_VALUES = 'persistProfileOptionsValues';
	const SUB_ACTION_LOAD_PROFILES = 'loadProfiles';

	const VERSION_PROFILE_CONFIGURATION_JS = '1.0';
	const CAPABILITY = 'manage_network';
	const TEMPLATE = 'profile-rights-management.twig';
	const NONCE = 'Active Directory Integration Profile Option Nonce';

	/** @var NextADInt_Multisite_Ui_ProfileConfigurationController */
	private $profileConfigurationController;

	/** @var NextADInt_Multisite_Ui_ProfileController */
	private $profileController;

	/** @var NextADInt_Multisite_Configuration_Service */
	private $configuration;

	/** @var array map the given subActions to the corresponding methods */
	private $actionMapping = array(
		self::SUB_ACTION_SAVE_PROFILE                  => self::SUB_ACTION_SAVE_PROFILE,
		self::SUB_ACTION_REMOVE_PROFILE                => self::SUB_ACTION_REMOVE_PROFILE,
		self::SUB_ACTION_GET_PROFILE_OPTION_VALUES     => self::SUB_ACTION_GET_PROFILE_OPTION_VALUES,
		self::SUB_ACTION_PERSIST_PROFILE_OPTION_VALUES => self::SUB_ACTION_PERSIST_PROFILE_OPTION_VALUES,
		self::SUB_ACTION_LOAD_PROFILES                 => self::SUB_ACTION_LOAD_PROFILES,
		self::SUB_ACTION_GENERATE_AUTHCODE             => self::SUB_ACTION_GENERATE_AUTHCODE,
		parent::SUB_ACTION_VERIFY_AD_CONNECTION        => parent::SUB_ACTION_VERIFY_AD_CONNECTION,
	);

	/**
	 * @param NextADInt_Multisite_View_TwigContainer                $twigContainer
	 * @param NextADInt_Multisite_Ui_BlogConfigurationController    $blogConfigurationController
	 * @param NextADInt_Multisite_Ui_ProfileConfigurationController $profileConfigurationController
	 * @param NextADInt_Multisite_Ui_ProfileController              $profileController
	 * @param NextADInt_Multisite_Configuration_Service             $configurationService
	 */
	public function __construct(NextADInt_Multisite_View_TwigContainer $twigContainer,
								NextADInt_Multisite_Ui_BlogConfigurationController $blogConfigurationController,
								NextADInt_Multisite_Ui_ProfileConfigurationController $profileConfigurationController,
								NextADInt_Multisite_Ui_ProfileController $profileController,
								NextADInt_Multisite_Configuration_Service $configurationService
	) {
		parent::__construct($twigContainer, $blogConfigurationController);

		$this->profileConfigurationController = $profileConfigurationController;
		$this->profileController = $profileController;
		$this->configuration = $configurationService;
	}

	/**
	 * Get the page title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return esc_html__('Profile options', 'next-active-directory-integration');
	}

	/**
	 * Get the slug for post requests.
	 *
	 * @return string
	 */
	public function wpAjaxSlug()
	{
		return $this->getSlug();
	}

	/**
	 * Get the menu slug of the page.
	 *
	 * @return string
	 */
	public function getSlug()
	{
		return NEXT_AD_INT_PREFIX . 'profile_options';
	}

	/**
	 * Render the page for an network admin.
	 */
	public function renderNetwork()
	{
		$relativeUrl = add_query_arg('page', NextADInt_Multisite_Ui_BlogProfileRelationshipPage::buildSlug());

        // translate twig text
        $i18n = array(
            'warningDiscardChanges' => __('The current profile contains unsaved changes. Are you sure you want to continue?', 'next-active-directory-integration'),
            'deleteProfileAssociatedSites' => __('The current profile is associated with the following sites:', 'next-active-directory-integration'),
            'deleteProfileAssociated' => __('The current profile is associated with {{ associations.length }} sites. Are you sure you want to delete this profile?', 'next-active-directory-integration'),
            'assignNewProfile' => __('Assign to profile:', 'next-active-directory-integration'),
            'newProfile' => __('New Profile', 'next-active-directory-integration'),
            'none' => __('None', 'next-active-directory-integration'),
            'configureSettingsForProfile' => __('Configure Settings for Profile : ', 'next-active-directory-integration'),
            'createNewProfile' => __('Create new profile', 'next-active-directory-integration'),
            'deleteProfile' => __('Delete profile', 'next-active-directory-integration'),
            'viewAssociatedProfiles' => __('View associated profiles', 'next-active-directory-integration'),
            'regenerateAuthCode' => __('Regenerate Auth Code', 'next-active-directory-integration'),
            'securityGroup' => __('Security group', 'next-active-directory-integration'),
            'wordpressRole' => __('WordPress role', 'next-active-directory-integration'),
            'selectRole' => __('Please select a role', 'next-active-directory-integration'),
            'verify' => __('Verify', 'next-active-directory-integration'),
            'adAttributes' => __('AD Attributes', 'next-active-directory-integration'),
            'dataType' => __('Data Type', 'next-active-directory-integration'),
            'wordpressAttribute' => __('Wordpress Attribute', 'next-active-directory-integration'),
            'description' => __('Description', 'next-active-directory-integration'),
            'viewInUserProfile' => __('View in User Profile', 'next-active-directory-integration'),
            'syncToAd' => __('Sync to Ad', 'next-active-directory-integration'),
            'overwriteWithEmptyValue' => __('Overwrite with empty value', 'next-active-directory-integration'),
            'wantToRegenerateAuthCode' => __('Do you really want to regenerate a new AuthCode?', 'next-active-directory-integration'),
            'wordPressIsConnectedToDomain' => __('WordPress Site is currently connected to Domain: ', 'next-active-directory-integration'),
            'domainConnectionVerificationSuccessful' => __('Verification successful! WordPress site is now connected to Domain: ', 'next-active-directory-integration'),
            'verificationSuccessful' => __('Verification successful!', 'next-active-directory-integration'),
            'domainConnectionVerificationFailed' => __('Verification failed! Please check your logfile for further information.', 'next-active-directory-integration'),
            'managePermissions' => __('Manage Permissions', 'next-active-directory-integration'),
            'noOptionsExists' => __('No options exists', 'next-active-directory-integration'),
            'pleaseWait' => __('Please wait...', 'next-active-directory-integration'),
            'save' => __('Save', 'next-active-directory-integration'),
            'haveToVerifyDomainConnection' => __('You have to verify the connection to the AD before saving.', 'next-active-directory-integration'),
            'errorWhileSaving' => __('An error occurred while saving the configuration.', 'next-active-directory-integration'),
            'savingSuccessful' => __('The configuration has been saved successfully.', 'next-active-directory-integration')
        );

		$i18n = NextADInt_Core_Util_EscapeUtil::escapeHarmfulHtml($i18n);

		$this->display(self::TEMPLATE, array(
			'blog_profile_relationship_url' => $relativeUrl,
			'nonce'                         => wp_create_nonce(self::NONCE), //create nonce for security
			'blog_rel_nonce'                => wp_create_nonce(NextADInt_Multisite_Ui_BlogProfileRelationshipPage::NONCE),
            'i18n' => $i18n
		));
	}

	/**
	 * Include JavaScript und CSS Files into WordPress.
	 *
	 * @param $hook
	 */
	public function loadNetworkScriptsAndStyle($hook)
	{
		if (strpos($hook, self::getSlug()) === false) {
			return;
		}

		parent::loadSharedAdminScriptsAndStyle();

		wp_enqueue_script('next_ad_int_profile_options_service_persistence',
			NEXT_AD_INT_URL . '/js/app/profile-options/services/persistence.service.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('next_ad_int_profile_options_service_data',
			NEXT_AD_INT_URL . '/js/app/profile-options/services/data.service.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);

		// add the controller js files
		wp_enqueue_script('next_ad_int_profile_options_controller_profile', NEXT_AD_INT_URL .
			'/js/app/profile-options/controllers/profile.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('next_ad_int_profile_options_controller_delete', NEXT_AD_INT_URL .
			'/js/app/profile-options/controllers/delete.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('next_ad_int_profile_options_controller_ajax', NEXT_AD_INT_URL .
			'/js/app/profile-options/controllers/ajax.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('next_ad_int_profile_options_controller_general', NEXT_AD_INT_URL .
			'/js/app/profile-options/controllers/general.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('next_ad_int_profile_options_controller_environment', NEXT_AD_INT_URL .
			'/js/app/profile-options/controllers/environment.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('next_ad_int_profile_options_controller_user', NEXT_AD_INT_URL .
			'/js/app/profile-options/controllers/user.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('next_ad_int_profile_options_controller_password', NEXT_AD_INT_URL .
			'/js/app/profile-options/controllers/credential.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('next_ad_int_profile_options_controller_permission', NEXT_AD_INT_URL .
			'/js/app/profile-options/controllers/permission.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('next_ad_int_profile_options_controller_security', NEXT_AD_INT_URL .
			'/js/app/profile-options/controllers/security.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('next_ad_int_profile_options_controller_attributes', NEXT_AD_INT_URL .
			'/js/app/profile-options/controllers/attributes.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('next_ad_int_profile_options_controller_sync_to_ad', NEXT_AD_INT_URL .
			'/js/app/profile-options/controllers/sync-to-ad.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('next_ad_int_profile_options_controller_sync_to_wordpress', NEXT_AD_INT_URL .
			'/js/app/profile-options/controllers/sync-to-wordpress.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('next_ad_int_profile_options_controller_logging', NEXT_AD_INT_URL .
			'/js/app/profile-options/controllers/logging.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);

		wp_enqueue_script('next_ad_int_blog_options_model', NEXT_AD_INT_URL . '/js/app/profile-options/models/profile.model.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
	}

	/**
	 * Get the data from our {@see $_POST} and send it to our {@see NextADInt_Multisite_Ui_ProfileController}.
	 *
	 * @param array $postData
	 *
	 * @return bool|false|int
	 */
	protected function saveProfile($postData)
	{
		$data = $postData['data'];
		$id = $this->getProfileId($data);

		$this->validate($data);

		return $this->profileController->saveProfile($data, $id);
	}

	/**
	 * Get the data from our {@see $_POST} and send it to our {@see NextADInt_Multisite_Ui_ProfileController}.
	 *
	 * @param array $postData
	 *
	 * @return array|bool
	 */
	protected function removeProfile($postData)
	{
		$id = $postData['id'];

		return $this->profileController->deleteProfile($id);
	}

	/**
	 * Get the data from our {@see $_POST} and send it to our {@see NextADInt_Multisite_Configuration_Service}.
	 *
	 * @param array $postData
	 *
	 * @return array|mixed
	 */
	protected function getProfileOptionsValues($postData)
	{
		$profileId = $postData['profileId'];

		return $this->configuration->getProfileOptionsValues($profileId);
	}

	/**
	 * Verify connection to AD to recieve domainSid.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected function verifyAdConnection($data)
	{
		$data = $data["data"];
		$profileId = $data["profile"];
		unset($data["profile"]);

		$validation = parent::validateVerification($data);
		$connection = $this->verifyInternal($data, $profileId);

		$response = array_merge($validation->getValidationResult(), $connection);

		return $response;
	}

	/**
	 * Prepare an array for persistence.
	 *
	 * @param string $domainSid
	 *
	 * @return array
	 */
	protected function getDomainSidForPersistence($domainSid)
	{
		return array(
			"domain_sid" => array(
				"option_value"      => $domainSid,
				"option_permission" => 3, //TODO revisit (default value to prevent saving errors)
			),
		);
	}

	/**
	 * Delegate call to {@link NextADInt_Multisite_Ui_ProfileConfigurationController#saveProfileOptions}.
	 *
	 * @param array $data
	 * @param int|null $profileId
	 *
	 * @return array
	 */
	public function persistDomainSid($data, $profileId = null)
	{
		return $this->profileConfigurationController->saveProfileOptions($data, $profileId);
	}

	/**
	 * Get the data from our {@see $_POST} and send it to our {@see NextADInt_Multisite_Ui_ProfileConfigurationController}.
	 *
	 * @param array $postData
	 *
	 * @return array
	 */
	protected function persistProfileOptionsValues($postData)
	{
		$data = $postData['data'];
		$options = $data['options'];

		$validation = $this->validate($data);
		$resultContainsErrors = $validation->containsErrors();
		$validationResult = $validation->getValidationResult();
		$id = $this->getProfileId($data);
		$profileName = $this->getProfileName($options);

		// if pre validation failed return errorMessage, if it contains only warning, continue
		if (sizeof($validationResult) > 0 && $resultContainsErrors) {
			return $this->getErrorMessage($id, $profileName, $validationResult);
		}

		//TODO Check if this methode call is nessessary
		$id = $this->saveProfile($postData);

		$persistStatusMessage = $this->profileConfigurationController->saveProfileOptions($options, $id);

		// Add additional information to the response ( profileid, profileName and validationResult)
		$persistStatusMessage = $this->getPersistStatusMessage($id, $profileName, $persistStatusMessage, $validationResult);

		return $persistStatusMessage;
	}

	/**
	 * Get the profile id from the given data.
	 *
	 * @param array $data
	 *
	 * @return null
	 */
	protected function getProfileId($data)
	{
		// never save a profile with a negative id
		if (isset($data['profile']) && '' === $data['profile']) {
			return null;
		}

		return $data['profile'];
	}

	/**
	 * Get the profile name from the given data.
	 *
	 * @param array $data
	 *
	 * @return null
	 */
	protected function getProfileName($options)
	{
		// never save a profile without a name
		if (isset($options['profile_name']['option_value']) && '' === $options['profile_name']['option_value']) {
			return null;
		}

		return $options['profile_name']['option_value'];
	}

	/**
	 * Load all necessary data for our initial page call.
	 *
	 * @return array
	 */
	protected function loadProfiles()
	{
		return array(
			'profiles'           => $this->profileController->findAll(),
			'associatedProfiles' => $this->profileController->findAllProfileAssociations(),
			'defaultProfileData' => $this->configuration->getProfileOptionsValues(-1),
			'ldapAttributes'     => NextADInt_Ldap_Attribute_Description::findAll(),
			'dataTypes'          => NextADInt_Ldap_Attribute_Repository::findAllAttributeTypes(),
			'permissionItems'    => $this->getPermission(),
			'wpRoles'        => NextADInt_Adi_Role_Manager::getRoles(),
		);
	}

	/**
	 * Return permission items for permission selectbox
	 *
	 * @return array
	 */

	protected function getPermission()
	{
		$permissionItems = array(
			0 => array(
				"value"       => "0",
				"description" => __("Input field is invisible.", 'next-active-directory-integration'),
			),
			1 => array(
				"value"       => "1",
				"description" => __("Deactivated and option value not shown.", 'next-active-directory-integration'),
			),
			2 => array(
				"value"       => "2",
				"description" => __("Deactivated and option value shown.", 'next-active-directory-integration'),
			),
			3 => array(
				"value"       => "3",
				"description" => __("Blog admin sets the option value.", 'next-active-directory-integration'),
			),
		);

		return $permissionItems;
	}

	/**
	 * Validate the given data.
	 *
	 * @param $data
	 */
	protected function validate($data)
	{
		return parent::validate($data['options']);
	}

	/**
	 * Get the current capability to check if the user has permission to view this page.
	 *
	 * @return string
	 */
	protected function getCapability()
	{
		return self::CAPABILITY;
	}

	/**
	 * @return array
	 */
	protected function getActionMapping()
	{
		return $this->actionMapping;
	}

	/**
	 * Get the current nonce value.
	 *
	 * @return string
	 */
	protected function getNonce()
	{
		return self::NONCE;
	}

	private function getErrorMessage($profileId, $profileName, $result) {
		$errorMessage = NextADInt_Core_Message::error(__('An error occurred while saving the configuration.', 'next-active-directory-integration'))->toArray();
		$errorMessage['additionalInformation'] = array(
			'profileId'   => $profileId,
			'profileName' => $profileName,
		);

		$errorMessage = array_merge($errorMessage, $result);

		return $errorMessage;
	}

	private function getPersistStatusMessage($profileId, $profileName, $persistStatusMessage, $validationResult) {
		$persistStatusMessage['additionalInformation'] = array(
			'profileId'   => $profileId,
			'profileName' => $profileName,
		);

		$persistStatusMessage = array_merge($persistStatusMessage, $validationResult);

		return $persistStatusMessage;
	}
}