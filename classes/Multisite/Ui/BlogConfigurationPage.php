<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Ui_BlogConfigurationPage')) {
	return;
}

/**
 * Multisite_Ui_BlogConfigurationPage represents the BlogOption page in WordPress.
 *
 * Multisite_Ui_BlogConfigurationPage holds the methods for interacting with WordPress, displaying the rendered template and saving
 * the data.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 *
 * @access public
 */
class Multisite_Ui_BlogConfigurationPage extends Multisite_View_Page_Abstract
{
	const SUB_ACTION_GENERATE_AUTHCODE = 'generateNewAuthCode';
	const SUB_ACTION_GET_ALL_OPTION_VALUES = 'getAllOptionsValues';
	const SUB_ACTION_PERSIST_OPTION_VALUES = 'persistOptionsValues';

	const VERSION_BLOG_OPTIONS_JS = '1.0';

	const CAPABILITY = 'manage_options';
	const TEMPLATE = 'blog-options-page.twig';
	const NONCE = 'Active Directory Integration Configuration Nonce';

	/** @var Multisite_Ui_BlogConfigurationController */
	private $blogConfigurationController;

	/* @var Core_Validator */
	private $validator;

	/** @var array map the given subActions to the corresponding methods */
	private $actionMapping = array(
		self::SUB_ACTION_GENERATE_AUTHCODE     => self::SUB_ACTION_GENERATE_AUTHCODE,
		self::SUB_ACTION_GET_ALL_OPTION_VALUES => self::SUB_ACTION_GET_ALL_OPTION_VALUES,
		self::SUB_ACTION_PERSIST_OPTION_VALUES => self::SUB_ACTION_PERSIST_OPTION_VALUES,
	);

	/**
	 * @param Multisite_View_TwigContainer             $twigContainer
	 * @param Multisite_Ui_BlogConfigurationController $blogConfigurationConfigurationControllerController
	 */
	public function __construct(Multisite_View_TwigContainer $twigContainer,
		Multisite_Ui_BlogConfigurationController $blogConfigurationConfigurationControllerController
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
		return esc_html__('Configuration', ADI_I18N);
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
		return ADI_PREFIX . 'blog_options';
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

		wp_enqueue_script('adi2_blog_options_service_persistence', ADI_URL .
			'/js/app/blog-options/services/persistence.service.js', array(), self::VERSION_BLOG_OPTIONS_JS);
		wp_enqueue_script('adi2_blog_options_service_data',
			ADI_URL . '/js/app/blog-options/services/data.service.js', array(), self::VERSION_BLOG_OPTIONS_JS);

		// add the controller js files
		wp_enqueue_script('adi2_blog_options_controller_blog', ADI_URL .
			'/js/app/blog-options/controllers/blog.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS);
		wp_enqueue_script('adi2_blog_options_controller_ajax', ADI_URL .
			'/js/app/blog-options/controllers/ajax.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS);
		wp_enqueue_script('adi2_blog_options_controller_general', ADI_URL .
			'/js/app/blog-options/controllers/general.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS);
		wp_enqueue_script('adi2_blog_options_controller_environment', ADI_URL .
			'/js/app/blog-options/controllers/environment.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS);
		wp_enqueue_script('adi2_blog_options_controller_user', ADI_URL .
			'/js/app/blog-options/controllers/user.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS);
		wp_enqueue_script('adi2_blog_options_controller_password', ADI_URL .
			'/js/app/blog-options/controllers/password.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS);
		wp_enqueue_script('adi2_blog_options_controller_permission', ADI_URL .
			'/js/app/blog-options/controllers/permission.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS);
		wp_enqueue_script('adi2_blog_options_controller_security', ADI_URL .
			'/js/app/blog-options/controllers/security.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS);
		wp_enqueue_script('adi2_blog_options_controller_attributes', ADI_URL .
			'/js/app/blog-options/controllers/attributes.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS);
		wp_enqueue_script('adi2_blog_options_controller_sync_to_ad', ADI_URL .
			'/js/app/blog-options/controllers/sync-to-ad.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS);
		wp_enqueue_script('adi2_blog_options_controller_sync_to_wordpress', ADI_URL .
			'/js/app/blog-options/controllers/sync-to-wordpress.controller.js', array(), self::VERSION_BLOG_OPTIONS_JS);
	}

	/**
	 * Include shared JavaScript und CSS Files into WordPress.
	 */
	protected function loadSharedAdminScriptsAndStyle()
	{
		wp_enqueue_script('jquery-1.12.2.min', ADI_URL . '/js/libraries/jquery-1.12.2.min.js',
			array(), Multisite_Ui::VERSION_PAGE_JS);

		wp_enqueue_script('adi2_page', ADI_URL . '/js/page.js', array('jquery'), Multisite_Ui::VERSION_PAGE_JS);

		wp_enqueue_script('angular.min', ADI_URL . '/js/libraries/angular.min.js',
			array(), Multisite_Ui::VERSION_PAGE_JS);
		wp_enqueue_script('ng-alertify', ADI_URL . '/js/libraries/ng-alertify.js',
			array('angular.min'), Multisite_Ui::VERSION_PAGE_JS);
		wp_enqueue_script('ng-notify', ADI_URL . '/js/libraries/ng-notify.min.js',
			array('angular.min'), Multisite_Ui::VERSION_PAGE_JS);

		wp_enqueue_script('adi2_shared_util_array', ADI_URL . '/js/app/shared/utils/array.util.js',
			array(), Multisite_Ui::VERSION_PAGE_JS);
		wp_enqueue_script('adi2_shared_util_value', ADI_URL . '/js/app/shared/utils/value.util.js',
			array(), Multisite_Ui::VERSION_PAGE_JS);

		wp_enqueue_script('adi2_app_module', ADI_URL . '/js/app/app.module.js', array(), Multisite_Ui::VERSION_PAGE_JS);
		wp_enqueue_script('adi2_app_config', ADI_URL . '/js/app/app.config.js', array(), Multisite_Ui::VERSION_PAGE_JS);

		// add the service js files
		wp_enqueue_script('adi2_shared_service_browser',
			ADI_URL . '/js/app/shared/services/browser.service.js', array(), Multisite_Ui::VERSION_PAGE_JS);
		wp_enqueue_script('adi2_shared_service_template',
			ADI_URL . '/js/app/shared/services/template.service.js', array(), Multisite_Ui::VERSION_PAGE_JS);
		wp_enqueue_script('adi2_shared_service_notification',
			ADI_URL . '/js/app/shared/services/notification.service.js', array(), Multisite_Ui::VERSION_PAGE_JS);
		wp_enqueue_script('adi2_shared_service_list',
			ADI_URL . '/js/app/shared/services/list.service.js', array(), Multisite_Ui::VERSION_PAGE_JS);

		wp_enqueue_script('selectizejs', ADI_URL . '/js/libraries/selectize.min.js',
			array('jquery'), Multisite_Ui::VERSION_PAGE_JS);
		wp_enqueue_script('selectizeFix', ADI_URL . '/js/libraries/fixed-angular-selectize-3.0.1.js',
			array('selectizejs', 'angular.min'), Multisite_Ui::VERSION_PAGE_JS);

		wp_enqueue_style('adi2', ADI_URL . '/css/adi2.css', array(), Multisite_Ui::VERSION_CSS);
		wp_enqueue_style('ng-notify', ADI_URL . '/css/ng-notify.min.css', array(), Multisite_Ui::VERSION_CSS);
		wp_enqueue_style('selectizecss', ADI_URL . '/css/selectize.css', array(), Multisite_Ui::VERSION_CSS);
		wp_enqueue_style('alertify.min', ADI_URL . '/css/alertify.min.css', array(), Multisite_Ui::VERSION_CSS);
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
	 * @return Core_Message|mixed
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

			if (Multisite_Configuration_Service::DISABLED_FOR_BLOG_ADMIN > $permission) {
				$data[$optionName]["option_value"] = "";
			}
		}

		return array(
			'options'        => $data,
			'ldapAttributes' => Ldap_Attribute_Description::findAll(),
			'dataTypes'      => Ldap_Attribute_Repository::findAllAttributeTypes(),
		);
	}

	/**
	 * Generate a new auth code and return it.
	 *
	 * @return array
	 */
	protected function generateNewAuthCode()
	{
		$sanitizer = new Multisite_Option_Sanitizer();
		$newAuthCode = $sanitizer->authcode('newCode', null, null, true);

		return array('newAuthCode' => $newAuthCode);
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

		$this->validate($data);

		//check if the permission of the option is high enough for the option to be saved
		$databaseOptionData = $this->twigContainer->getAllOptionsValues();

		foreach ($data as $optionName => $optionValue) {
			$databaseOptionPermission = $databaseOptionData[$optionName]["option_permission"];

			if (Multisite_Configuration_Service::EDITABLE != $databaseOptionPermission) {
				unset($data[$optionName]);
			}
		}

		return $this->blogConfigurationController->saveBlogOptions($data);
	}

	/**
	 * Validate the given data.
	 *
	 * @param $data
	 */
	protected function validate($data)
	{
		$validationResult = $this->getValidator()->validate($data);

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
	 * Create or get our current validator object..
	 *
	 * @return Core_Validator
	 */
	public function getValidator()
	{
		if (null === $this->validator) {
			$validator = new Core_Validator();

			$message = __('Username has to contain a suffix.', ADI_I18N);

			// conditional rule for our sync_to_wordpress_user value
			$syncToWordPressSuffixRule = new Multisite_Validator_Rule_ConditionalSuffix($message, '@', array(
				'sync_to_wordpress_enabled' => true,
			));
			$validator->addRule('sync_to_wordpress_user', $syncToWordPressSuffixRule);

			// conditional rule for our sync_to_ad_global_user value
			$syncToActiveDirectorySuffixRule = new Multisite_Validator_Rule_ConditionalSuffix($message, '@', array(
				'sync_to_ad_use_global_user' => true,
			));
			$validator->addRule('sync_to_ad_global_user', $syncToActiveDirectorySuffixRule);

			$noDefaultAttributeNameMessage = __('Cannot use default attribute names for custom attribute mapping.',
				ADI_I18N);
			$noDefaultAttributeNameRule = new Multisite_Validator_Rule_NoDefaultAttributeName(
				$noDefaultAttributeNameMessage);
			$validator->addRule('additional_user_attributes', $noDefaultAttributeNameRule);

			$this->validator = $validator;
		}

		return $this->validator;
	}
}