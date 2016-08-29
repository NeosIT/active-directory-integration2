<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Ui_BlogConfigurationPage')) {
	return;
}

/**
 * NextADInt_Multisite_Ui_BlogConfigurationPage represents the BlogOption page in WordPress.
 *
 * NextADInt_Multisite_Ui_BlogConfigurationPage holds the methods for interacting with WordPress, displaying the rendered template and saving
 * the data.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 *
 * @access public
 */
class NextADInt_Multisite_Ui_BlogConfigurationPage extends NextADInt_Multisite_View_Page_Abstract
{
	const SUB_ACTION_GENERATE_AUTHCODE = 'generateNewAuthCode';
	const SUB_ACTION_GET_ALL_OPTION_VALUES = 'getAllOptionsValues';
	const SUB_ACTION_PERSIST_OPTION_VALUES = 'persistOptionsValues';
	const SUB_ACTION_VERIFY_AD_CONNECTION = 'verifyAdConnection';

	const VERSION_BLOG_OPTIONS_JS = '1.0';

	const CAPABILITY = 'manage_options';
	const TEMPLATE = 'blog-options-page.twig';
	const NONCE = 'Active Directory Integration Configuration Nonce';

	/** @var NextADInt_Multisite_Ui_BlogConfigurationController */
	private $blogConfigurationController;

	/** @var NextADInt_Core_Validator */
	private $validator;

	/** @var NextADInt_Core_Validator */
	private $verificationValidator;

	/** @var array map the given subActions to the corresponding methods */
	private $actionMapping
		= array(
			self::SUB_ACTION_GENERATE_AUTHCODE     => self::SUB_ACTION_GENERATE_AUTHCODE,
			self::SUB_ACTION_GET_ALL_OPTION_VALUES => self::SUB_ACTION_GET_ALL_OPTION_VALUES,
			self::SUB_ACTION_PERSIST_OPTION_VALUES => self::SUB_ACTION_PERSIST_OPTION_VALUES,
			self::SUB_ACTION_VERIFY_AD_CONNECTION  => self::SUB_ACTION_VERIFY_AD_CONNECTION,
		);

	/**
	 * @param NextADInt_Multisite_View_TwigContainer             $twigContainer
	 * @param NextADInt_Multisite_Ui_BlogConfigurationController $blogConfigurationConfigurationControllerController
	 */
	public function __construct(NextADInt_Multisite_View_TwigContainer $twigContainer,
								NextADInt_Multisite_Ui_BlogConfigurationController $blogConfigurationConfigurationControllerController
	) {
		parent::__construct($twigContainer);

		$this->blogConfigurationController = $blogConfigurationConfigurationControllerController;
	}

	/**
	 * Get the page title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return esc_html__('Configuration', NEXT_AD_INT_I18N);
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
		return NEXT_AD_INT_PREFIX . 'blog_options';
	}

	/**
	 * Render the page for an admin.
	 */
	public function renderAdmin()
	{
		$this->display(
			self::TEMPLATE, array(
				'nonce' => wp_create_nonce(self::NONCE),// create nonce for security
			)
		);
	}

	/**
	 * Include JavaScript und CSS Files into WordPress.
	 *
	 * @param $hook
	 */
	public function loadAdminScriptsAndStyle($hook)
	{
		if (strpos($hook, self::getSlug()) === false) {
			return;
		}

		$this->loadSharedAdminScriptsAndStyle();

		wp_enqueue_script(
			'adi2_blog_options_service_persistence', NEXT_AD_INT_URL .
			'/js/app/blog-options/services/persistence.service.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'adi2_blog_options_service_data',
			NEXT_AD_INT_URL . '/js/app/blog-options/services/data.service.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);

		// add the controller js files
		wp_enqueue_script(
			'adi2_blog_options_controller_blog', NEXT_AD_INT_URL .
			'/js/app/blog-options/controllers/blog.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'adi2_blog_options_controller_ajax', NEXT_AD_INT_URL .
			'/js/app/blog-options/controllers/ajax.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'adi2_blog_options_controller_general', NEXT_AD_INT_URL .
			'/js/app/blog-options/controllers/general.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'adi2_blog_options_controller_environment', NEXT_AD_INT_URL .
			'/js/app/blog-options/controllers/environment.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'adi2_blog_options_controller_user', NEXT_AD_INT_URL .
			'/js/app/blog-options/controllers/user.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'adi2_blog_options_controller_password', NEXT_AD_INT_URL .
			'/js/app/blog-options/controllers/password.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'adi2_blog_options_controller_permission', NEXT_AD_INT_URL .
			'/js/app/blog-options/controllers/permission.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'adi2_blog_options_controller_security', NEXT_AD_INT_URL .
			'/js/app/blog-options/controllers/security.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'adi2_blog_options_controller_attributes', NEXT_AD_INT_URL .
			'/js/app/blog-options/controllers/attributes.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'adi2_blog_options_controller_sync_to_ad', NEXT_AD_INT_URL .
			'/js/app/blog-options/controllers/sync-to-ad.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'adi2_blog_options_controller_sync_to_wordpress', NEXT_AD_INT_URL .
			'/js/app/blog-options/controllers/sync-to-wordpress.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
	}

	/**
	 * Include shared JavaScript und CSS Files into WordPress.
	 */
	protected function loadSharedAdminScriptsAndStyle()
	{
		wp_enqueue_script("jquery");

		wp_enqueue_script('adi2_page', NEXT_AD_INT_URL . '/js/page.js', array('jquery'), NextADInt_Multisite_Ui::VERSION_PAGE_JS);

		wp_enqueue_script(
			'angular.min', NEXT_AD_INT_URL . '/js/libraries/angular.min.js',
			array(), NextADInt_Multisite_Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script(
			'ng-alertify', NEXT_AD_INT_URL . '/js/libraries/ng-alertify.js',
			array('angular.min'), NextADInt_Multisite_Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script(
			'ng-notify', NEXT_AD_INT_URL . '/js/libraries/ng-notify.min.js',
			array('angular.min'), NextADInt_Multisite_Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script('ng-busy', NEXT_AD_INT_URL . '/js/libraries/angular-busy.min.js',
			array('angular.min'), NextADInt_Multisite_Ui::VERSION_PAGE_JS);

		wp_enqueue_script(
			'adi2_shared_util_array', NEXT_AD_INT_URL . '/js/app/shared/utils/array.util.js',
			array(), NextADInt_Multisite_Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script(
			'adi2_shared_util_value', NEXT_AD_INT_URL . '/js/app/shared/utils/value.util.js',
			array(), NextADInt_Multisite_Ui::VERSION_PAGE_JS
		);

		wp_enqueue_script('adi2_app_module', NEXT_AD_INT_URL . '/js/app/app.module.js', array(), NextADInt_Multisite_Ui::VERSION_PAGE_JS);
		wp_enqueue_script('adi2_app_config', NEXT_AD_INT_URL . '/js/app/app.config.js', array(), NextADInt_Multisite_Ui::VERSION_PAGE_JS);

		// add the service js files
		wp_enqueue_script(
			'adi2_shared_service_browser',
			NEXT_AD_INT_URL . '/js/app/shared/services/browser.service.js', array(), NextADInt_Multisite_Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script(
			'adi2_shared_service_template',
			NEXT_AD_INT_URL . '/js/app/shared/services/template.service.js', array(), NextADInt_Multisite_Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script(
			'adi2_shared_service_notification',
			NEXT_AD_INT_URL . '/js/app/shared/services/notification.service.js', array(), NextADInt_Multisite_Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script(
			'adi2_shared_service_list',
			NEXT_AD_INT_URL . '/js/app/shared/services/list.service.js', array(), NextADInt_Multisite_Ui::VERSION_PAGE_JS
		);

		wp_enqueue_script(
			'selectizejs', NEXT_AD_INT_URL . '/js/libraries/selectize.min.js',
			array('jquery'), NextADInt_Multisite_Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script(
			'selectizeFix', NEXT_AD_INT_URL . '/js/libraries/fixed-angular-selectize-3.0.1.js',
			array('selectizejs', 'angular.min'), NextADInt_Multisite_Ui::VERSION_PAGE_JS
		);

		wp_enqueue_style('adi2', NEXT_AD_INT_URL . '/css/adi2.css', array(), NextADInt_Multisite_Ui::VERSION_CSS);
		wp_enqueue_style('ng-notify', NEXT_AD_INT_URL . '/css/ng-notify.min.css', array(), NextADInt_Multisite_Ui::VERSION_CSS);
		wp_enqueue_style('selectizecss', NEXT_AD_INT_URL . '/css/selectize.css', array(), NextADInt_Multisite_Ui::VERSION_CSS);
		wp_enqueue_style('alertify.min', NEXT_AD_INT_URL . '/css/alertify.min.css', array(), NextADInt_Multisite_Ui::VERSION_CSS);
	}

	/**
	 * This method listens to post request via wp_ajax_xxx hook.
	 */
	public function wpAjaxListener()
	{
		// die if nonce is not valid
		$this->checkNonce();

		// if user has got insufficient permission, then leave
		if (!$this->currentUserHasCapability()) {
			return;
		}

		$subAction = (!empty($_POST['subAction'])) ? $_POST['subAction'] : '';

		$result = $this->routeRequest($subAction, $_POST);

		if (false !== $result) {
			$this->renderJson($result);
		}
	}

	/**
	 * Check the current request for a sub-action and delegate it to a corresponding method.
	 *
	 * @param $subAction
	 * @param $data
	 *
	 * @return NextADInt_Core_Message|mixed
	 */
	protected function routeRequest($subAction, $data)
	{
		$mappings = $this->getActionMapping();

		if (empty($subAction) || !isset($mappings[$subAction])) {
			return false;
		}

		$targetMethod = $mappings[$subAction];

		return call_user_func(array(&$this, $targetMethod), $data);
	}

	/**
	 * Return the current action mapping for this page.
	 *
	 * @return array
	 */
	protected function getActionMapping()
	{
		return $this->actionMapping;
	}

	/**
	 * Create and return an array with all data used by the frontend.
	 *
	 * @return array
	 */
	protected function getAllOptionsValues()
	{
		$data = $this->twigContainer->getAllOptionsValues();

		foreach ($data as $optionName => $optionData) {
			$permission = $optionData["option_permission"];

			if (NextADInt_Multisite_Configuration_Service::DISABLED_FOR_BLOG_ADMIN > $permission) {
				$data[$optionName]["option_value"] = "";
			}
		}

		return array(
			'options'        => $data,
			'ldapAttributes' => NextADInt_Ldap_Attribute_Description::findAll(),
			'dataTypes'      => NextADInt_Ldap_Attribute_Repository::findAllAttributeTypes(),
			'wpRoles'        => NextADInt_Adi_Role_Manager::getRoles(),
		);
	}

	/**
	 * Generate a new auth code and return it.
	 *
	 * @return array
	 */
	protected function generateNewAuthCode()
	{
		$sanitizer = new NextADInt_Multisite_Option_Sanitizer();
		$newAuthCode = $sanitizer->authcode('newCode', null, null, true);

		return array('newAuthCode' => $newAuthCode);
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
		$this->validateVerification($data);

		return $this->verifyInternal($data);
	}

	/**
	 * Verify the connection by the given $data array
	 *
	 * @param array $data
	 * @param null  $profileId
	 *
	 * @return array
	 */
	protected function verifyInternal($data, $profileId = null)
	{
		$failedMessage = array(
			"verification_failed" => "Verification failed! Please check your logfile for further information.",
		);
		$objectSid = $this->twigContainer->findActiveDirectoryDomainSid($data);

		if (false === $objectSid) {
			return $failedMessage;
		}

		$domainSid = NextADInt_Core_Util_StringUtil::objectSidToDomainSid($objectSid);
		$domainSidData = $this->prepareDomainSid($domainSid);

		if (false === $domainSid) {
			return $failedMessage;
		}

		$this->persistDomainSid($domainSidData, $profileId);

		return array("verification_successful" => $domainSid);
	}

	/**
	 * Check if the given SID is valid and normalize it for persistence.
	 *
	 * @param      $domainSid
	 *
	 * @return array
	 */
	protected function prepareDomainSid($domainSid)
	{
		if (is_string($domainSid) && $domainSid !== '') {
			return $this->getDomainSidForPersistence($domainSid);
		}

		return false;
	}

	/**
	 * Prepare an array for persistence.
	 *
	 * @param $domainSid
	 *
	 * @return array
	 */
	protected function getDomainSidForPersistence($domainSid)
	{
		return array("domain_sid" => $domainSid);
	}

	/**
	 * Persist the given option values.
	 *
	 * @param $postData
	 *
	 * @return array|boolean
	 */
	protected function persistOptionsValues($postData)
	{
		// is $_POST does not contain data, then return
		if (empty($postData['data'])) {
			return false;
		}

		$data = $postData['data'];

		//check if the permission of the option is high enough for the option to be saved
		$databaseOptionData = $this->twigContainer->getAllOptionsValues();

		foreach ($data as $optionName => $optionValue) {
			$databaseOptionPermission = $databaseOptionData[$optionName]["option_permission"];

			if (NextADInt_Multisite_Configuration_Service::EDITABLE != $databaseOptionPermission) {
				unset($data[$optionName]);
			}
		}

		$this->validate($data);

		return $this->blogConfigurationController->saveBlogOptions($data);
	}

	/**
	 * Delegate call to {@link NextADInt_Multisite_Ui_BlogConfigurationController#saveProfileOptions}.
	 *
	 * @param $data
	 * @param $profileId
	 *
	 * @return array
	 */
	public function persistDomainSid($data, $profileId = null)
	{
		return $this->blogConfigurationController->saveBlogOptions($data);
	}

	/**
	 * Validate the given data using the validator from {@code NextADInt_Multisite_Ui_BlogConfigurationPage#getValidator()}.
	 *
	 * @param $data
	 */
	protected function validate($data)
	{
		$this->validateWithValidator($this->getValidator(), $data);
	}

	/**
	 * Validate the given data using the validator from
	 * {@code NextADInt_Multisite_Ui_BlogConfigurationPage#getVerificationValidator()}.
	 *
	 * @param $data
	 */
	protected function validateVerification($data)
	{
		$this->validateWithValidator($this->getVerificationValidator(), $data);
	}

	/**
	 * Validate the data using the given {@code $validator}.
	 *
	 * @param NextADInt_Core_Validator $validator
	 * @param                $data
	 */
	private function validateWithValidator(NextADInt_Core_Validator $validator, $data)
	{
		$validationResult = $validator->validate($data);

		if (!$validationResult->isValid()) {
			$this->renderJson($validationResult->getResult());
		}
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
	 * Get the current nonce value.
	 *
	 * @return mixed
	 */
	protected function getNonce()
	{
		return self::NONCE;
	}

	/**
	 * Get the validator for the default save action.
	 *
	 * @return NextADInt_Core_Validator
	 */
	public function getValidator()
	{
		if (null === $this->validator) {
			$validator = $this->getSharedValidator();

			$message = __('Username has to contain a suffix.', NEXT_AD_INT_I18N);
			$invalidValueMessage = __('The given value is invalid.', NEXT_AD_INT_I18N);

			// PROFILE
			$notEmptyMessage = __('This value must not be empty.', NEXT_AD_INT_I18N);
			$notEmptyRule = new NextADInt_Multisite_Validator_Rule_NotEmptyOrWhitespace($notEmptyMessage);
			$validator->addRule(NextADInt_Adi_Configuration_Options::PROFILE_NAME, $notEmptyRule);

			// ENVIRONMENT
			$invalidSelectValueRule = new NextADInt_Multisite_Validator_Rule_SelectValueValid($invalidValueMessage,
				NextADInt_Multisite_Option_Encryption::getValues());
			$validator->addRule(NextADInt_Adi_Configuration_Options::ENCRYPTION, $invalidSelectValueRule);

			// USER
			$accountSuffixMessage = __(
				'Account Suffix does not match the required style. (e.g. "@company.local")',
				NEXT_AD_INT_I18N
			);
			$accountSuffixRule = new NextADInt_Multisite_Validator_Rule_AccountSuffix($accountSuffixMessage, '@');
			$validator->addRule(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX, $accountSuffixRule);

			$defaultEmailDomainMessage = __('Please remove the "@", it will be added automatically.', NEXT_AD_INT_I18N);
			$defaultEmailDomainRule = new NextADInt_Multisite_Validator_Rule_DefaultEmailDomain($defaultEmailDomainMessage);
			$validator->addRule(NextADInt_Adi_Configuration_Options::DEFAULT_EMAIL_DOMAIN, $defaultEmailDomainRule);

			// SECURITY
			$maxLoginAttempts = __('Maximum login attempts has to be numeric and cannot be negative.', NEXT_AD_INT_I18N);
			$maxLoginAttemptsRule = new NextADInt_Multisite_Validator_Rule_PositiveNumericOrZero($maxLoginAttempts);
			$validator->addRule(NextADInt_Adi_Configuration_Options::MAX_LOGIN_ATTEMPTS, $maxLoginAttemptsRule);

			$blockTimeMessage = __('Blocking Time has to be numeric and cannot be negative.', NEXT_AD_INT_I18N);
			$blockTimeRule = new NextADInt_Multisite_Validator_Rule_PositiveNumericOrZero($blockTimeMessage);
			$validator->addRule(NextADInt_Adi_Configuration_Options::BLOCK_TIME, $blockTimeRule);

			$adminEmailMessage = __(
				'Admin email does not match the required style. (e.g. "admin@company.local")',
				NEXT_AD_INT_I18N
			);
			$adminEmailRule = new NextADInt_Multisite_Validator_Rule_AdminEmail($adminEmailMessage, '@');
			$validator->addRule(NextADInt_Adi_Configuration_Options::ADMIN_EMAIL, $adminEmailRule);

			// SSO username
			$ssoServiceAccountUserSuffixRule = new NextADInt_Multisite_Validator_Rule_Suffix($message, '@');

			$ssoServiceAccountUserNotEmptyMessage = __('Username must not be empty.', NEXT_AD_INT_I18N);
			$ssoServiceAccountUserNotEmptyRule = new NextADInt_Multisite_Validator_Rule_NotEmptyOrWhitespace(
				$ssoServiceAccountUserNotEmptyMessage
			);

			$ssoServiceAccountUsernameConditionalRules = new NextADInt_Multisite_Validator_Rule_Conditional(
				array($ssoServiceAccountUserSuffixRule, $ssoServiceAccountUserNotEmptyRule),
				array(NextADInt_Adi_Configuration_Options::SSO_ENABLED => true)
			);
			$validator->addRule(NextADInt_Adi_Configuration_Options::SSO_USER, $ssoServiceAccountUsernameConditionalRules);

			// SSO password
			$ssoServiceAccountPasswordNotEmptyMessage = __('Password must not be empty.', NEXT_AD_INT_I18N);
			$ssoServiceAccountPasswordNotEmptyRule = new NextADInt_Multisite_Validator_Rule_NotEmptyOrWhitespace(
				$ssoServiceAccountPasswordNotEmptyMessage
			);
			$ssoServiceAccountPasswordConditionalRules = new NextADInt_Multisite_Validator_Rule_Conditional(
				array($ssoServiceAccountPasswordNotEmptyRule),
				array(NextADInt_Adi_Configuration_Options::SSO_ENABLED => true)
			);
			$validator->addRule(NextADInt_Adi_Configuration_Options::SSO_PASSWORD, $ssoServiceAccountPasswordConditionalRules);

			$ssoEnvironmentVariableRule = new NextADInt_Multisite_Validator_Rule_SelectValueValid(
				$invalidSelectValueRule, NextADInt_Adi_Authentication_SingleSignOn_Variable::getValues()
			);
			$validator->addRule(NextADInt_Adi_Configuration_Options::SSO_ENVIRONMENT_VARIABLE, $ssoEnvironmentVariableRule);

			// PERMISSIONS
			$disallowedRoleMessage = __('The role super admin can only be set inside a profile.', NEXT_AD_INT_I18N);
			$disallowedRoleRule = new NextADInt_Multisite_Validator_Rule_DisallowSuperAdminInBlogConfig($disallowedRoleMessage);
			$validator->addRule(NextADInt_Adi_Configuration_Options::ROLE_EQUIVALENT_GROUPS, $disallowedRoleRule);

			// ATTRIBUTES
			$noDefaultAttributeNameMessage = __(
				'Cannot use default attribute names for custom attribute mapping.',
				NEXT_AD_INT_I18N
			);
			$noDefaultAttributeNameRule = new NextADInt_Multisite_Validator_Rule_NoDefaultAttributeName(
				$noDefaultAttributeNameMessage
			);
			$validator->addRule(NextADInt_Adi_Configuration_Options::ADDITIONAL_USER_ATTRIBUTES, $noDefaultAttributeNameRule);

			$attributeMappingNullMessage = __(
				'Ad Attribute / Data Type / WordPress Attribute cannot be empty!',
				NEXT_AD_INT_I18N
			);
			$attributeMappingNullRule = new NextADInt_Multisite_Validator_Rule_AttributeMappingNull($attributeMappingNullMessage);
			$validator->addRule(NextADInt_Adi_Configuration_Options::ADDITIONAL_USER_ATTRIBUTES, $attributeMappingNullRule);

			$metakeyConflictMessage = __('You cannot use the same WordPress Attribute multiple times.', NEXT_AD_INT_I18N);
			$metakeyConflictRule = new NextADInt_Multisite_Validator_Rule_WordPressMetakeyConflict($metakeyConflictMessage);
			$validator->addRule(NextADInt_Adi_Configuration_Options::ADDITIONAL_USER_ATTRIBUTES, $metakeyConflictRule);

			$adAttributeConflictMessage = __('You cannot use the same Ad Attribute multiple times.', NEXT_AD_INT_I18N);
			$adAttributeConflictRule = new NextADInt_Multisite_Validator_Rule_AdAttributeConflict($adAttributeConflictMessage);
			$validator->addRule(NextADInt_Adi_Configuration_Options::ADDITIONAL_USER_ATTRIBUTES, $adAttributeConflictRule);

			// SYNC TO AD
			// conditional rule for our sync_to_ad_global_user value
			$syncToActiveDirectorySuffixRule = new NextADInt_Multisite_Validator_Rule_Suffix($message, '@');
			$syncToWordPressConditionalRules = new NextADInt_Multisite_Validator_Rule_Conditional(
				array($syncToActiveDirectorySuffixRule),
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_AD_USE_GLOBAL_USER => true)
			);
			$validator->addRule(NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_USER, $syncToWordPressConditionalRules);

			// SYNC TO WORDPRESS
			// conditional rule for our sync_to_wordpress_user value
			$syncToWordPressSuffixRule = new NextADInt_Multisite_Validator_Rule_Suffix($message, '@');
			$syncToWordPressConditionalRules = new NextADInt_Multisite_Validator_Rule_Conditional(
				array($syncToWordPressSuffixRule),
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_ENABLED => true)
			);
			$validator->addRule(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_USER, $syncToWordPressConditionalRules);

			$this->validator = $validator;
		}

		return $this->validator;
	}

	/**
	 * Get the validator with all necessary rules for the verification.
	 *
	 * @return NextADInt_Core_Validator
	 */
	public function getVerificationValidator()
	{
		if (null == $this->verificationValidator) {
			$validator = $this->getSharedValidator();

			$verifyUsernameMessage = __(
				'Verification Username does not match the required style. (e.g. "Administrator@test.ad")', NEXT_AD_INT_I18N
			);
			$verifyUsernameRule = new NextADInt_Multisite_Validator_Rule_AdminEmail($verifyUsernameMessage, '@');
			$validator->addRule(NextADInt_Adi_Configuration_Options::VERIFICATION_USERNAME, $verifyUsernameRule);

			$verifyUsernameEmptyMessage = __(
				'Verification Username does not match the required style. (e.g. "Administrator@test.ad")', NEXT_AD_INT_I18N
			);
			$verifyUsernameEmptyRule = new NextADInt_Multisite_Validator_Rule_NotEmptyOrWhitespace($verifyUsernameEmptyMessage);
			$validator->addRule(NextADInt_Adi_Configuration_Options::VERIFICATION_USERNAME, $verifyUsernameEmptyRule);

			$verifyPasswordMessage = __('Verification Password cannot be empty.', NEXT_AD_INT_I18N);
			$verifyPasswordRule = new NextADInt_Multisite_Validator_Rule_NotEmptyOrWhitespace($verifyPasswordMessage);
			$validator->addRule(NextADInt_Adi_Configuration_Options::VERIFICATION_PASSWORD, $verifyPasswordRule);

			$this->verificationValidator = $validator;
		}

		return $this->verificationValidator;
	}

	/**
	 * Return a validator with the shared rules.
	 */
	protected function getSharedValidator()
	{
		$validator = new NextADInt_Core_Validator();

		// ENVIRONMENT
		$portMessage = __('Port has to be numeric and in the range from 0 - 65535.', NEXT_AD_INT_I18N);
		$portRule = new NextADInt_Multisite_Validator_Rule_Port($portMessage);
		$validator->addRule(NextADInt_Adi_Configuration_Options::PORT, $portRule);

		$networkTimeoutMessage = __('Network timeout has to be numeric and cannot be negative.', NEXT_AD_INT_I18N);
		$networkTimeoutRule = new NextADInt_Multisite_Validator_Rule_PositiveNumericOrZero($networkTimeoutMessage);
		$validator->addRule(NextADInt_Adi_Configuration_Options::NETWORK_TIMEOUT, $networkTimeoutRule);

		return $validator;
	}
}