<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Ui_ProfileConfigurationPage')) {
	return;
}

/**
 * Multisite_Ui_ProfileConfigurationPage represents the BlogOption page in WordPress.
 *
 * Multisite_Ui_ProfileConfigurationPage holds the methods for interacting with WordPress, displaying the rendered template
 * and saving the data.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 *
 * @access public
 */
class Multisite_Ui_ProfileConfigurationPage extends Multisite_Ui_BlogConfigurationPage
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

	/** @var Multisite_Ui_ProfileConfigurationController */
	private $profileConfigurationController;

	/** @var Multisite_Ui_ProfileController */
	private $profileController;

	/** @var Multisite_Configuration_Service */
	private $configuration;

	/** @var array map the given subActions to the corresponding methods */
	private $actionMapping = array(
		self::SUB_ACTION_SAVE_PROFILE                  => self::SUB_ACTION_SAVE_PROFILE,
		self::SUB_ACTION_REMOVE_PROFILE                => self::SUB_ACTION_REMOVE_PROFILE,
		self::SUB_ACTION_GET_PROFILE_OPTION_VALUES     => self::SUB_ACTION_GET_PROFILE_OPTION_VALUES,
		self::SUB_ACTION_PERSIST_PROFILE_OPTION_VALUES => self::SUB_ACTION_PERSIST_PROFILE_OPTION_VALUES,
		self::SUB_ACTION_LOAD_PROFILES                 => self::SUB_ACTION_LOAD_PROFILES,
		self::SUB_ACTION_GENERATE_AUTHCODE             => self::SUB_ACTION_GENERATE_AUTHCODE,
	);

	/**
	 * @param Multisite_View_TwigContainer                $twigContainer
	 * @param Multisite_Ui_BlogConfigurationController    $blogConfigurationController
	 * @param Multisite_Ui_ProfileConfigurationController $profileConfigurationController
	 * @param Multisite_Ui_ProfileController              $profileController
	 * @param Multisite_Configuration_Service             $configurationService
	 */
	public function __construct(Multisite_View_TwigContainer $twigContainer,
		Multisite_Ui_BlogConfigurationController $blogConfigurationController,
		Multisite_Ui_ProfileConfigurationController $profileConfigurationController,
		Multisite_Ui_ProfileController $profileController,
		Multisite_Configuration_Service $configurationService
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
		return esc_html__('Profile options', ADI_I18N);
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
		return ADI_PREFIX . 'profile_options';
	}

	/**
	 * Render the page for an network admin.
	 */
	public function renderNetwork()
	{
		$relativeUrl = add_query_arg('page', Multisite_Ui_BlogProfileRelationshipPage::buildSlug());

		$this->display(self::TEMPLATE, array(
			'blog_profile_relationship_url' => $relativeUrl,
			'nonce'                         => wp_create_nonce(self::NONCE), //create nonce for security
			'blog_rel_nonce'                => wp_create_nonce(Multisite_Ui_BlogProfileRelationshipPage::NONCE),
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

		wp_enqueue_script('adi2_profile_options_service_persistence',
			ADI_URL . '/js/app/profile-options/services/persistence.service.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('adi2_profile_options_service_data',
			ADI_URL . '/js/app/profile-options/services/data.service.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);

		// add the controller js files
		wp_enqueue_script('adi2_profile_options_controller_profile', ADI_URL .
			'/js/app/profile-options/controllers/profile.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('adi2_profile_options_controller_delete', ADI_URL .
			'/js/app/profile-options/controllers/delete.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('adi2_profile_options_controller_ajax', ADI_URL .
			'/js/app/profile-options/controllers/ajax.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('adi2_profile_options_controller_general', ADI_URL .
			'/js/app/profile-options/controllers/general.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('adi2_profile_options_controller_environment', ADI_URL .
			'/js/app/profile-options/controllers/environment.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('adi2_profile_options_controller_user', ADI_URL .
			'/js/app/profile-options/controllers/user.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('adi2_profile_options_controller_password', ADI_URL .
			'/js/app/profile-options/controllers/password.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('adi2_profile_options_controller_permission', ADI_URL .
			'/js/app/profile-options/controllers/permission.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('adi2_profile_options_controller_security', ADI_URL .
			'/js/app/profile-options/controllers/security.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('adi2_profile_options_controller_attributes', ADI_URL .
			'/js/app/profile-options/controllers/attributes.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('adi2_profile_options_controller_sync_to_ad', ADI_URL .
			'/js/app/profile-options/controllers/sync-to-ad.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
		wp_enqueue_script('adi2_profile_options_controller_sync_to_wordpress', ADI_URL .
			'/js/app/profile-options/controllers/sync-to-wordpress.controller.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);

		wp_enqueue_script('adi2_blog_options_model', ADI_URL . '/js/app/profile-options/models/profile.model.js',
			array(), self::VERSION_PROFILE_CONFIGURATION_JS);
	}

	/**
	 * Get the data from our {@see $_POST} and send it to our {@see Multisite_Ui_ProfileController}.
	 *
	 * @param $postData
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
	 * Get the data from our {@see $_POST} and send it to our {@see Multisite_Ui_ProfileController}.
	 *
	 * @param $postData
	 *
	 * @return array|bool
	 */
	protected function removeProfile($postData)
	{
		$id = $postData['id'];

		return $this->profileController->deleteProfile($id);
	}

	/**
	 * Get the data from our {@see $_POST} and send it to our {@see Multisite_Configuration_Service}.
	 *
	 * @param $postData
	 *
	 * @return array|mixed
	 */
	protected function getProfileOptionsValues($postData)
	{
		$profileId = $postData['profileId'];

		return $this->configuration->getAllProfileOptionsValues($profileId);
	}

	/**
	 * Get the data from our {@see $_POST} and send it to our {@see Multisite_Ui_ProfileConfigurationController}.
	 *
	 * @param $postData
	 *
	 * @return array
	 */
	protected function persistProfileOptionsValues($postData)
	{
		$data = $postData['data'];
		$options = $data['options'];

		$this->validate($data);

		$id = $this->saveProfile($postData);

		$message = $this->profileConfigurationController->saveProfileOptions($options, $id);
		$message['additionalInformation'] = array(
			'profileId'   => $id,
			'profileName' => $options['profile_name']['option_value'],
		);

		return $message;
	}

	/**
	 * Get the profile id from the given data.
	 *
	 * @param $data
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
	 * Load all necessary data for our initial page call.
	 *
	 * @return array
	 */
	protected function loadProfiles()
	{
		return array(
			'profiles'           => $this->profileController->findAll(),
			'associatedProfiles' => $this->profileController->findAllProfileAssociations(),
			'defaultProfileData' => $this->configuration->getAllProfileOptionsValues(-1),
			'ldapAttributes'     => Ldap_Attribute_Description::findAll(),
			'dataTypes'          => Ldap_Attribute_Repository::findAllAttributeTypes(),
			'permissionItems'	 => $this->getPermission(),
			'wpRoles'        => Adi_Role_Manager::getRoles(),
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
				"value" => "0",
				"description" => __("Input field is invisible.", ADI_I18N),
			),
			1 => array(
				"value" => "1",
				"description" => __("Deactivated and option value not shown.", ADI_I18N),
			),
			2 => array(
				"value" => "2",
				"description" => __("Deactivated and option value shown.", ADI_I18N),
			),
			3 => array(
				"value" => "3",
				"description" => __("Blog admin sets the option value.", ADI_I18N),
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
		parent::validate($data['options']);
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
}