<?php

namespace Dreitier\Nadi\Ui;


use Dreitier\Ldap\Attribute\Description;
use Dreitier\Ldap\Attribute\Repository;
use Dreitier\Nadi\Authentication\SingleSignOn\Variable;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Role\Manager;
use Dreitier\Nadi\Ui\Validator\Rule\AccountSuffix;
use Dreitier\Nadi\Ui\Validator\Rule\AdAttributeConflict;
use Dreitier\Nadi\Ui\Validator\Rule\AdminEmail;
use Dreitier\Nadi\Ui\Validator\Rule\AttributeMappingNull;
use Dreitier\Nadi\Ui\Validator\Rule\BaseDn;
use Dreitier\Nadi\Ui\Validator\Rule\BaseDnWarn;
use Dreitier\Nadi\Ui\Validator\Rule\DefaultEmailDomain;
use Dreitier\Nadi\Ui\Validator\Rule\DisallowInvalidWordPressRoles;
use Dreitier\Nadi\Ui\Validator\Rule\NoDefaultAttributeName;
use Dreitier\Nadi\Ui\Validator\Rule\NoDefaultAttributeNameTest;
use Dreitier\Nadi\Ui\Validator\Rule\Port;
use Dreitier\Nadi\Ui\Validator\Rule\SelectValueValid;
use Dreitier\Nadi\Ui\Validator\Rule\WordPressMetakeyConflict;
use Dreitier\Util\EscapeUtil;
use Dreitier\Util\Message\Message;
use Dreitier\Util\Message\Type;
use Dreitier\Util\Validator\Rule\Conditional;
use Dreitier\Util\Validator\Rule\HasSuffix;
use Dreitier\Util\Validator\Rule\NotEmptyOrWhitespace;
use Dreitier\Util\Validator\Rule\PositiveNumericOrZero;
use Dreitier\Util\Validator\Validator;
use Dreitier\WordPress\Multisite\Configuration\Service;
use Dreitier\WordPress\Multisite\Option\Encryption;
use Dreitier\WordPress\Multisite\Option\Sanitizer;
use Dreitier\WordPress\Multisite\Ui;
use Dreitier\WordPress\Multisite\Ui\BlogConfigurationController;
use Dreitier\WordPress\Multisite\View\Page\PageAdapter;
use Dreitier\WordPress\Multisite\View\TwigContainer;

/**
 * NadiSingleSiteConfigurationPage represents the BlogOption page in WordPress.
 *
 * NadiSingleSiteConfigurationPage holds the methods for interacting with WordPress, displaying the rendered template and saving
 * the data.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 *
 * @access public
 */
class NadiSingleSiteConfigurationPage extends PageAdapter
{
	const SUB_ACTION_GENERATE_AUTHCODE = 'generateNewAuthCode';
	const SUB_ACTION_GET_ALL_OPTION_VALUES = 'getAllOptionsValues';
	const SUB_ACTION_PERSIST_OPTION_VALUES = 'persistOptionsValues';
	const SUB_ACTION_VERIFY_AD_CONNECTION = 'verifyAdConnection';

	const VERSION_BLOG_OPTIONS_JS = '1.0';

	const CAPABILITY = 'manage_options';
	const TEMPLATE = 'blog-options-page.twig';
	const NONCE = 'Active Directory Integration Configuration Nonce';

	/** @var Ui\BlogConfigurationController */
	private $blogConfigurationController;

	/** @var Validator */
	private $validator;

	/** @var Validator */
	private $verificationValidator;

	/** @var array map the given subActions to the corresponding methods */
	private $actionMapping
		= array(
			self::SUB_ACTION_GENERATE_AUTHCODE => self::SUB_ACTION_GENERATE_AUTHCODE,
			self::SUB_ACTION_GET_ALL_OPTION_VALUES => self::SUB_ACTION_GET_ALL_OPTION_VALUES,
			self::SUB_ACTION_PERSIST_OPTION_VALUES => self::SUB_ACTION_PERSIST_OPTION_VALUES,
			self::SUB_ACTION_VERIFY_AD_CONNECTION => self::SUB_ACTION_VERIFY_AD_CONNECTION,
		);

	/**
	 * @param TwigContainer $twigContainer
	 * @param BlogConfigurationController $blogMultisiteConfigurationServiceController
	 */
	public function __construct(TwigContainer               $twigContainer,
								BlogConfigurationController $blogMultisiteConfigurationServiceController
	)
	{
		parent::__construct($twigContainer);

		$this->blogConfigurationController = $blogMultisiteConfigurationServiceController;
	}

	/**
	 * Get the page title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return esc_html__('Configuration', 'next-active-directory-integration');
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
		return NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'blog_options';
	}

	/**
	 * Render the page for an admin.
	 */
	public function renderAdmin()
	{
		// translate twig text
		$i18n = array(
			'title' => __('Next Active Directory Integration Blog Configuration', 'next-active-directory-integration'),
			'regenerateAuthCode' => __('Regenerate Auth Code', 'next-active-directory-integration'),
			'securityGroup' => __('Security group', 'next-active-directory-integration'),
			'wordpressRole' => __('WordPress role', 'next-active-directory-integration'),
			'selectRole' => __('Please select a role', 'next-active-directory-integration'),
			'verify' => __('Verify', 'next-active-directory-integration'),
			'adAttributes' => __('AD Attributes', 'next-active-directory-integration'),
			'dataType' => __('Data Type', 'next-active-directory-integration'),
			'wordpressAttribute' => __('WordPress Attribute', 'next-active-directory-integration'),
			'description' => __('Description', 'next-active-directory-integration'),
			'viewInUserProfile' => __('View in User Profile', 'next-active-directory-integration'),
			'syncToAd' => __('Sync to AD', 'next-active-directory-integration'),
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
		$i18n = EscapeUtil::escapeHarmfulHtml($i18n);

		$this->display(
			self::TEMPLATE, array(
				'nonce' => wp_create_nonce(self::NONCE),// create nonce for security
				'i18n' => $i18n
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
			'next_ad_int_blog_options_service_persistence',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL .
			'/js/app/blog-options/services/persistence.service.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'next_ad_int_blog_options_service_data',
			NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/blog-options/services/data.service.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);

		// add the controller js files
		wp_enqueue_script(
			'next_ad_int_blog_options_controller_blog',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL .
			'/js/app/blog-options/controllers/blog.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'next_ad_int_blog_options_controller_ajax',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL .
			'/js/app/blog-options/controllers/ajax.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'next_ad_int_blog_options_controller_general',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL .
			'/js/app/blog-options/controllers/general.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'next_ad_int_blog_options_controller_environment',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL .
			'/js/app/blog-options/controllers/environment.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'next_ad_int_blog_options_controller_user',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL .
			'/js/app/blog-options/controllers/user.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'next_ad_int_blog_options_controller_password',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL .
			'/js/app/blog-options/controllers/credential.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'next_ad_int_blog_options_controller_permission',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL .
			'/js/app/blog-options/controllers/permission.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'next_ad_int_blog_options_controller_security',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL .
			'/js/app/blog-options/controllers/security.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'next_ad_int_blog_options_controller_sso',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL .
			'/js/app/blog-options/controllers/sso.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'next_ad_int_blog_options_controller_attributes',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL .
			'/js/app/blog-options/controllers/attributes.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'next_ad_int_blog_options_controller_sync_to_ad',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL .
			'/js/app/blog-options/controllers/sync-to-ad.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'next_ad_int_blog_options_controller_sync_to_wordpress',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL .
			'/js/app/blog-options/controllers/sync-to-wordpress.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
		wp_enqueue_script(
			'next_ad_int_blog_options_controller_logging',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL .
			'/js/app/blog-options/controllers/logging.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS
		);
	}

	/**
	 * Include shared JavaScript und CSS Files into WordPress.
	 */
	protected function loadSharedAdminScriptsAndStyle()
	{
		wp_enqueue_script("jquery");

		wp_enqueue_script('next_ad_int_page',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/page.js', array('jquery'), Ui::VERSION_PAGE_JS);

		wp_enqueue_script(
			'angular.min',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/libraries/angular.min.js',
			array(), Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script(
			'ng-alertify',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/libraries/ng-alertify.js',
			array('angular.min'), Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script(
			'ng-notify',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/libraries/ng-notify.min.js',
			array('angular.min'), Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script('ng-busy',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/libraries/angular-busy.min.js',
			array('angular.min'), Ui::VERSION_PAGE_JS);

		wp_enqueue_script(
			'next_ad_int_shared_util_array',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/shared/utils/array.util.js',
			array(), Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script(
			'next_ad_int_shared_util_value',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/shared/utils/value.util.js',
			array(), Ui::VERSION_PAGE_JS
		);

		wp_enqueue_script('next_ad_int_app_module',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/app.module.js', array(), Ui::VERSION_PAGE_JS);
		wp_enqueue_script('next_ad_int_app_config',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/app.nadi.js', array(), Ui::VERSION_PAGE_JS);

		// add the service js files
		wp_enqueue_script(
			'next_ad_int_shared_service_browser',
			NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/shared/services/browser.service.js', array(), Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script(
			'next_ad_int_shared_service_template',
			NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/shared/services/template.service.js', array(), Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script(
			'next_ad_int_shared_service_notification',
			NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/shared/services/notification.service.js', array(), Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script(
			'next_ad_int_shared_service_list',
			NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/shared/services/list.service.js', array(), Ui::VERSION_PAGE_JS
		);

		wp_enqueue_script(
			'selectizejs',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/libraries/selectize.min.js',
			array('jquery'), Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script(
			'selectizeFix',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/libraries/fixed-angular-selectize-3.0.1.js',
			array('selectizejs', 'angular.min'), Ui::VERSION_PAGE_JS
		);

		wp_enqueue_script('next_ad_int_bootstrap_min_js',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/libraries/bootstrap.min.js', array(), Ui::VERSION_PAGE_JS);

		wp_enqueue_style('ng-notify',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/css/ng-notify.min.css', array(), Ui::VERSION_CSS);
		wp_enqueue_style('selectizecss',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/css/selectize.css', array(), Ui::VERSION_CSS);
		wp_enqueue_style('alertify.min',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/css/alertify.min.css', array(), Ui::VERSION_CSS);
		wp_enqueue_style('next_ad_int_bootstrap_min_css',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/css/bootstrap.min.css', array(), Ui::VERSION_CSS);
		wp_enqueue_style('next_ad_int',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/css/next_ad_int.css', array(), Ui::VERSION_CSS);
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

		// ADI-357 unescape already escaped $_POST
		$post = stripslashes_deep($_POST);

		$subAction = (!empty($post['subAction'])) ? $post['subAction'] : '';

		$result = $this->routeRequest($subAction, $post);

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
	 * @return Message|mixed
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

			if (Service::DISABLED_FOR_BLOG_ADMIN > $permission) {
				$data[$optionName]["option_value"] = "";
			}
		}

		return array(
			'options' => $data,
			'ldapAttributes' => Description::findAll(),
			'dataTypes' => Repository::findAllAttributeTypes(),
			'wpRoles' => Manager::getRoles(),
		);
	}

	/**
	 * Generate a new auth code and return it.
	 *
	 * @return array
	 */
	protected function generateNewAuthCode()
	{
		$sanitizer = new Sanitizer();
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
		// retrieve all verification results
		$validation = $this->validateVerification($data);
		$connection = array();

		// only call verifyInternal if no validation errors were found (warnings are excluded)
		if (!$validation->containsErrors()) {
			$connection = $this->verifyInternal($data);
		}

		// return the validation result and the connection details
		// it can consist of a successful connection and validation warnings
		$response = array_merge($validation->getValidationResult(), $connection);

		return $response;
	}

	/**
	 * Verify the connection by the given $data array
	 *
	 * @param array $data
	 * @param null $profileId
	 *
	 * @return array
	 */
	protected function verifyInternal($data, $profileId = null)
	{
		$failedMessage = array(
			"verification_error" => "Verification failed! Please check your logfile for further information.",
		);

		$objectSid = $this->twigContainer->findActiveDirectoryDomainSid($data);

		if (false === $objectSid) {
			return $failedMessage;
		}

		$domainSid = $objectSid->getDomainPartAsSid()->getFormatted();
		$domainSidData = $this->prepareDomainSid($domainSid);

		if (false === $domainSid) {
			return $failedMessage;
		}

		$netBiosName = $this->twigContainer->findActiveDirectoryNetBiosName($data);

		$netBiosData = array();
		if ($netBiosName) {
			$netBiosData = $this->prepareNetBiosName($netBiosName);
			$this->persistNetBiosName($netBiosData, $profileId);
		}

		$this->persistDomainSid($domainSidData, $profileId);

		return array("verification_successful_sid" => $domainSid, "verification_successful_netbios" => $netBiosData['netbios_name']);
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

	protected function prepareNetBiosName($netBiosName)
	{
		if (is_string($netBiosName) && $netBiosName !== '') {
			return $this->getNetBiosNameForPersistence($netBiosName);
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

	protected function getNetBiosNameForPersistence($netBiosName)
	{
		return array("netbios_name" => $netBiosName);
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

		$connection = array('status_success' => false);
		$data = $postData['data'];

		//check if the permission of the option is high enough for the option to be saved
		$databaseOptionData = $this->twigContainer->getAllOptionsValues();

		foreach ($data as $optionName => $optionValue) {
			$databaseOptionPermission = $databaseOptionData[$optionName]["option_permission"];

			if (Service::EDITABLE != $databaseOptionPermission) {
				unset($data[$optionName]);
			}
		}

		// aggregate all validation results
		$validation = $this->validate($data);
		// only call saveBlogOptions if no validation errors are present (warnings are excluded)
		if (!$validation->containsErrors()) {
			$connection = $this->blogConfigurationController->saveBlogOptions($data);
		}

		// merge the validation errors and the status
		$response = array_merge($validation->getValidationResult(), $connection);

		return $response;

	}

	/**
	 * Delegate call to {@link Ui_BlogConfigurationController#saveProfileOptions}.
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

	public function persistNetBiosName($data, $profileId = null)
	{
		return $this->blogConfigurationController->saveBlogOptions($data);
	}

	/**
	 * Validate the given data using the validator from {@code Ui_BlogConfigurationPage#getValidator()}.
	 *
	 * @param $data
	 */
	protected function validate($data)
	{
		return $this->validateWithValidator($this->getValidator(), $data);
	}

	/**
	 * Validate the given data using the validator from
	 * {@code Ui_BlogConfigurationPage#getVerificationValidator()}.
	 *
	 * @param $data
	 */
	protected function validateVerification($data)
	{
		return $this->validateWithValidator($this->getVerificationValidator(), $data);
	}

	/**
	 * Validate the data using the given {@code $validator}.
	 *
	 * @param Validator $validator
	 * @param                $data
	 */
	protected function validateWithValidator(Validator $validator, $data)
	{
		$response = $validator->validate($data);

		return $response;

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
	 * @return Validator
	 */
	public function getValidator()
	{
		if (null === $this->validator) {
			$validator = $this->getSharedValidator();

			$message = __('Username has to contain a suffix.', 'next-active-directory-integration');
			$invalidValueMessage = __('The given value is invalid.', 'next-active-directory-integration');

			// PROFILE
			$notEmptyMessage = __('This value must not be empty.', 'next-active-directory-integration');
			$notEmptyRule = new NotEmptyOrWhitespace($notEmptyMessage);
			$validator->addRule(Options::PROFILE_NAME, $notEmptyRule);

			// ENVIRONMENT
			$invalidSelectValueRule = new SelectValueValid($invalidValueMessage,
				Encryption::getValues());
			$validator->addRule(Options::ENCRYPTION, $invalidSelectValueRule);

			// USER
			$accountSuffixMessage = __(
				'Account Suffix does not match the required style. (e.g. "@company.local")',
				'next-active-directory-integration'
			);
			$accountSuffixRule = new AccountSuffix($accountSuffixMessage, '@');
			$validator->addRule(Options::ACCOUNT_SUFFIX, $accountSuffixRule);

			$defaultEmailDomainMessage = __('Please remove the "@", it will be added automatically.', 'next-active-directory-integration');
			$defaultEmailDomainRule = new DefaultEmailDomain($defaultEmailDomainMessage);
			$validator->addRule(Options::DEFAULT_EMAIL_DOMAIN, $defaultEmailDomainRule);

			// SSO username
			$ssoServiceAccountUserSuffixRule = new HasSuffix($message, '@');

			$ssoServiceAccountUserNotEmptyMessage = __('Username must not be empty.', 'next-active-directory-integration');
			$ssoServiceAccountUserNotEmptyRule = new NotEmptyOrWhitespace(
				$ssoServiceAccountUserNotEmptyMessage
			);

			$ssoServiceAccountUsernameConditionalRules = new Conditional(
				array($ssoServiceAccountUserSuffixRule, $ssoServiceAccountUserNotEmptyRule),
				array(Options::SSO_ENABLED => true)
			);
			$validator->addRule(Options::SSO_USER, $ssoServiceAccountUsernameConditionalRules);

			// SSO password
			$ssoServiceAccountPasswordNotEmptyMessage = __('Password must not be empty.', 'next-active-directory-integration');
			$ssoServiceAccountPasswordNotEmptyRule = new NotEmptyOrWhitespace(
				$ssoServiceAccountPasswordNotEmptyMessage
			);
			$ssoServiceAccountPasswordConditionalRules = new Conditional(
				array($ssoServiceAccountPasswordNotEmptyRule),
				array(Options::SSO_ENABLED => true)
			);
			$validator->addRule(Options::SSO_PASSWORD, $ssoServiceAccountPasswordConditionalRules);

			$ssoEnvironmentVariableRule = new SelectValueValid(
				$invalidSelectValueRule, Variable::getValues()
			);
			$validator->addRule(Options::SSO_ENVIRONMENT_VARIABLE, $ssoEnvironmentVariableRule);

			// PERMISSIONS
			$disallowedRoleMessage = __('The role super admin can only be set inside a profile.', 'next-active-directory-integration');
			$invalidRoleMessage = __('At least one security group is associated with a non existing WordPress role. Please select an existing role for the group.', 'next-active-directory-integration');
			$disallowedRoleRule = new DisallowInvalidWordPressRoles(array($disallowedRoleMessage, $invalidRoleMessage));
			$validator->addRule(Options::ROLE_EQUIVALENT_GROUPS, $disallowedRoleRule);

			// ATTRIBUTES
			$noDefaultAttributeNameMessage = __(
				'Cannot use default attribute names for custom attribute mapping.',
				'next-active-directory-integration'
			);
			$noDefaultAttributeNameRule = new NoDefaultAttributeName(
				$noDefaultAttributeNameMessage
			);
			$validator->addRule(Options::ADDITIONAL_USER_ATTRIBUTES, $noDefaultAttributeNameRule);

			$attributeMappingNullMessage = __(
				'Ad Attribute / Data Type / WordPress Attribute cannot be empty!',
				'next-active-directory-integration'
			);
			$attributeMappingNullRule = new AttributeMappingNull($attributeMappingNullMessage);
			$validator->addRule(Options::ADDITIONAL_USER_ATTRIBUTES, $attributeMappingNullRule);

			$metakeyConflictMessage = __('You cannot use the same WordPress Attribute multiple times.', 'next-active-directory-integration');
			$metakeyConflictRule = new WordPressMetakeyConflict($metakeyConflictMessage);
			$validator->addRule(Options::ADDITIONAL_USER_ATTRIBUTES, $metakeyConflictRule);

			$adAttributeConflictMessage = __('You cannot use the same Ad Attribute multiple times.', 'next-active-directory-integration');
			$adAttributeConflictRule = new AdAttributeConflict($adAttributeConflictMessage);
			$validator->addRule(Options::ADDITIONAL_USER_ATTRIBUTES, $adAttributeConflictRule);

			// SYNC TO AD
			// conditional rule for our sync_to_ad_global_user value
			$syncToActiveDirectorySuffixRule = new HasSuffix($message, '@');
			$syncToWordPressConditionalRules = new Conditional(
				array($syncToActiveDirectorySuffixRule),
				array(Options::SYNC_TO_AD_USE_GLOBAL_USER => true)
			);
			$validator->addRule(Options::SYNC_TO_AD_GLOBAL_USER, $syncToWordPressConditionalRules);

			// SYNC TO WORDPRESS
			// conditional rule for our sync_to_wordpress_user value
			$syncToWordPressSuffixRule = new HasSuffix($message, '@');
			$syncToWordPressConditionalRules = new Conditional(
				array($syncToWordPressSuffixRule),
				array(Options::SYNC_TO_WORDPRESS_ENABLED => true)
			);
			$validator->addRule(Options::SYNC_TO_WORDPRESS_USER, $syncToWordPressConditionalRules);

			$this->addBaseDnValidators($validator);

			$this->validator = $validator;
		}

		return $this->validator;
	}

	/**
	 * Get the validator with all necessary rules for the verification.
	 *
	 * @return Validator
	 */
	public function getVerificationValidator()
	{
		if (null == $this->verificationValidator) {
			$validator = $this->getSharedValidator();
			$this->addBaseDnValidators($validator);

			$verifyUsernameMessage = __(
				'Verification Username does not match the required style. (e.g. "Administrator@test.ad")', 'next-active-directory-integration'
			);
			$verifyUsernameRule = new AdminEmail($verifyUsernameMessage, '@');
			$validator->addRule(Options::VERIFICATION_USERNAME, $verifyUsernameRule);

			$verifyUsernameEmptyMessage = __(
				'Verification Username does not match the required style. (e.g. "Administrator@test.ad")', 'next-active-directory-integration'
			);
			$verifyUsernameEmptyRule = new NotEmptyOrWhitespace($verifyUsernameEmptyMessage);
			$validator->addRule(Options::VERIFICATION_USERNAME, $verifyUsernameEmptyRule);

			$verifyPasswordMessage = __('Verification Password cannot be empty.', 'next-active-directory-integration');
			$verifyPasswordRule = new NotEmptyOrWhitespace($verifyPasswordMessage);
			$validator->addRule(Options::VERIFICATION_PASSWORD, $verifyPasswordRule);

			$this->verificationValidator = $validator;
		}

		return $this->verificationValidator;
	}

	/**
	 * Return a validator with the shared rules.
	 */
	protected function getSharedValidator()
	{
		$validator = new Validator();

		// ENVIRONMENT
		$portMessage = __('Port has to be numeric and in the range from 0 - 65535.', 'next-active-directory-integration');
		$portRule = new Port($portMessage);
		$validator->addRule(Options::PORT, $portRule);

		$networkTimeoutMessage = __('Network timeout has to be numeric and cannot be negative.', 'next-active-directory-integration');
		$networkTimeoutRule = new PositiveNumericOrZero($networkTimeoutMessage);
		$validator->addRule(Options::NETWORK_TIMEOUT, $networkTimeoutRule);

		$domainControllerMessage = __('Domain Controller cannot be empty.', 'next-active-directory-integration');
		$domainControllerRule = new NotEmptyOrWhitespace($domainControllerMessage);
		$validator->addRule(Options::DOMAIN_CONTROLLERS, $domainControllerRule);

		return $validator;
	}

	/**
	 * Add validators for the Base DN to an existing validator.
	 *
	 * @param Validator $validator
	 */
	protected function addBaseDnValidators(Validator $validator)
	{

		$verifyBaseDnMessage = __(
			'Base DN does not match the required style. (e.g. "DC=test,DC=ad")', 'next-active-directory-integration'
		);
		$verifyBaseDnRule = new BaseDn($verifyBaseDnMessage);
		$validator->addRule(Options::BASE_DN, $verifyBaseDnRule);

		$verifyBaseDnWarning = __(
			'Base DN consists of only one DC. (e.g. "DC=test,DC=ad")', 'next-active-directory-integration'
		);
		$verifyBaseDnWarningRule = new BaseDnWarn($verifyBaseDnWarning, Type::WARNING);
		$validator->addRule(Options::BASE_DN, $verifyBaseDnWarningRule);

	}

}