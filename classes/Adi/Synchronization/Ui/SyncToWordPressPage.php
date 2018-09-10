<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage')) {
	return;
}

/**
 * Controller for manual synchronizing of Active Directory users into the current WordPress instance
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 *
 * @access public
 */
class NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage extends NextADInt_Multisite_View_Page_Abstract
{
	const SLUG = 'sync_to_wordpress';
	const AJAX_SLUG = null;
	const CAPABILITY = 'manage_options';
	const TEMPLATE = 'sync-to-wordpress.twig';
	const NONCE = 'Active Directory Integration Sync to WordPress Nonce';

	/* @var NextADInt_Adi_Synchronization_WordPress $syncToWordPress */
	private $syncToWordPress;

	/** @var NextADInt_Multisite_Configuration_Service $configuration */
	private $configuration;

	private $result;
	private $log;

	/**
	 * @param NextADInt_Multisite_View_TwigContainer $twigContainer
	 * @param NextADInt_Adi_Synchronization_WordPress $syncToWordPress
	 * @param NextADInt_Multisite_Configuration_Service $configuration
	 */
	public function __construct(NextADInt_Multisite_View_TwigContainer $twigContainer,
								NextADInt_Adi_Synchronization_WordPress $syncToWordPress,
								NextADInt_Multisite_Configuration_Service $configuration)
	{
		parent::__construct($twigContainer);

		$this->syncToWordPress = $syncToWordPress;
		$this->configuration = $configuration;
	}

	/**
	 * Get the page title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return esc_html__('Sync to WordPress', 'next-active-directory-integration');
	}

	/**
	 * Render the page for an admin.
	 */
	public function renderAdmin()
	{
		$this->checkCapability();

		// dont unescape $_POST because only base64 values will be accessed
		$params = $this->processData($_POST);
		// add nonce for security
		$params['nonce'] = wp_create_nonce(self::NONCE);
		$params['authCode'] = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_AUTHCODE);
		$params['blogUrl'] = get_site_url(get_current_blog_id());
		$params['message'] = $this->result;
		$params['log'] = $this->log;
		$params['domainSidSet'] = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::DOMAIN_SID) ? 1 : 0;
		$params['syncEnabled'] = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_ENABLED) ? 1 : 0;
		$params['syncUserSet'] = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_USER) ? 1 : 0;
		$params['syncPassSet'] = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_PASSWORD) ? 1 : 0;

		$i18n = array(
			'title' => __('Sync To WordPress', 'next-active-directory-integration'),
			'descriptionLine1' => __('If you want to trigger Sync to WordPress, you must know the URL to the index.php of your blog:', 'next-active-directory-integration'),
			'descriptionLine2' => __('Settings like auth-code etc. depends on the current blog. So be careful which blog you are using. Here are some examples:', 'next-active-directory-integration'),
			'repeatAction' => __('Repeat AD to WordPress synchronization', 'next-active-directory-integration'),
			'startAction' => __('Start AD to WordPress synchronization', 'next-active-directory-integration'),
			'syncDisabled' => __('Check that a connection to a domain controller is established and \'Enable sync to WordPress\' is checked. Also, a service account has to be provided.', 'next-active-directory-integration'),
            'showLogOutput' => __('Show log output', 'next-active-directory-integration')
        );
		$params['i18n'] = NextADInt_Core_Util_EscapeUtil::escapeHarmfulHtml($i18n);

		$this->display(self::TEMPLATE, $params);
	}

	/**
	 * This method reads the $_POST array and triggers Sync to Wordpress (if the authentication code from $_POST is correct)
	 *
	 * @param array $post
	 * @return array
	 */
	public function processData($post)
	{
		if (!isset($post['syncToWordpress'])) {    // TODO bulkImport darf nicht in POST stehen
			return array();
		}

		$security = NextADInt_Core_Util_ArrayUtil::get('security', $post, '');
		if (!wp_verify_nonce($security, self::NONCE)) {
			$message = esc_html__('You do not have sufficient permissions to access this page.', 'next-active-directory-integration');
			wp_die($message);
		}

		NextADInt_Core_Logger::enableFrontendHandler();
		$status = $this->syncToWordPress->synchronize();
		$this->log = NextADInt_Core_Logger::getBufferedLog();
		NextADInt_Core_Logger::disableFrontendHandler();

		if ($status) {
			$this->result = esc_html__('Sync to WordPress succeeded.', 'next-active-directory-integration');
		} else {
			$this->result = esc_html__('Sync to WordPress failed.', 'next-active-directory-integration');
		}

		return array(
			'status' => $status
		);
	}

	/**
	 * Include JavaScript und CSS Files into WordPress.
	 *
	 * @param string $hook
	 */
	public function loadAdminScriptsAndStyle($hook)
	{
		if (strpos($hook, self::getSlug()) === false) {
			return;
		}

		$this->loadSharedAdminScriptsAndStyle();

		wp_enqueue_script(
			'next_ad_int_blog_options_controller_sync_action', NEXT_AD_INT_URL .
			'/js/app/blog-options/controllers/sync-action.controller.js', array(),
			NextADInt_Multisite_Ui_BlogConfigurationPage::VERSION_BLOG_OPTIONS_JS
		);

		wp_enqueue_style('next_ad_int_bootstrap_min_css', NEXT_AD_INT_URL . '/css/bootstrap.min.css', array(),
			NextADInt_Multisite_Ui::VERSION_CSS);

		wp_enqueue_script('next_ad_int_bootstrap_min_js', NEXT_AD_INT_URL . '/js/libraries/bootstrap.min.js', array(),
			NextADInt_Multisite_Ui::VERSION_PAGE_JS);
	}

	/**
	 * Include shared JavaScript und CSS Files into WordPress.
	 */
	protected function loadSharedAdminScriptsAndStyle()
	{
		wp_enqueue_script("jquery");

		wp_enqueue_script('next_ad_int_page', NEXT_AD_INT_URL . '/js/page.js', array('jquery'),
			NextADInt_Multisite_Ui::VERSION_PAGE_JS);

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
			'next_ad_int_shared_util_array', NEXT_AD_INT_URL . '/js/app/shared/utils/array.util.js',
			array(), NextADInt_Multisite_Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script(
			'next_ad_int_shared_util_value', NEXT_AD_INT_URL . '/js/app/shared/utils/value.util.js',
			array(), NextADInt_Multisite_Ui::VERSION_PAGE_JS
		);

		wp_enqueue_script('next_ad_int_app_module', NEXT_AD_INT_URL . '/js/app/app.module.js', array(),
			NextADInt_Multisite_Ui::VERSION_PAGE_JS);
		wp_enqueue_script('next_ad_int_app_config', NEXT_AD_INT_URL . '/js/app/app.nadi.js', array(),
			NextADInt_Multisite_Ui::VERSION_PAGE_JS);

		// add the service js files
		wp_enqueue_script(
			'next_ad_int_shared_service_browser',
			NEXT_AD_INT_URL . '/js/app/shared/services/browser.service.js', array(),
			NextADInt_Multisite_Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script(
			'next_ad_int_shared_service_template',
			NEXT_AD_INT_URL . '/js/app/shared/services/template.service.js', array(),
			NextADInt_Multisite_Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script(
			'next_ad_int_shared_service_notification',
			NEXT_AD_INT_URL . '/js/app/shared/services/notification.service.js', array(),
			NextADInt_Multisite_Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script(
			'next_ad_int_shared_service_list',
			NEXT_AD_INT_URL . '/js/app/shared/services/list.service.js', array(),
			NextADInt_Multisite_Ui::VERSION_PAGE_JS
		);

		wp_enqueue_script(
			'selectizejs', NEXT_AD_INT_URL . '/js/libraries/selectize.min.js',
			array('jquery'), NextADInt_Multisite_Ui::VERSION_PAGE_JS
		);
		wp_enqueue_script(
			'selectizeFix', NEXT_AD_INT_URL . '/js/libraries/fixed-angular-selectize-3.0.1.js',
			array('selectizejs', 'angular.min'), NextADInt_Multisite_Ui::VERSION_PAGE_JS
		);

		wp_enqueue_style('next_ad_int', NEXT_AD_INT_URL . '/css/next_ad_int.css', array(),
			NextADInt_Multisite_Ui::VERSION_CSS);
		wp_enqueue_style('ng-notify', NEXT_AD_INT_URL . '/css/ng-notify.min.css', array(),
			NextADInt_Multisite_Ui::VERSION_CSS);
		wp_enqueue_style('selectizecss', NEXT_AD_INT_URL . '/css/selectize.css', array(),
			NextADInt_Multisite_Ui::VERSION_CSS);
		wp_enqueue_style('alertify.min', NEXT_AD_INT_URL . '/css/alertify.min.css', array(),
			NextADInt_Multisite_Ui::VERSION_CSS);
	}

	/**
	 * Get the menu slug of the page.
	 *
	 * @return string
	 */
	public function getSlug()
	{
		return NEXT_AD_INT_PREFIX . self::SLUG;
	}

	/**
	 * Get the slug for post requests.
	 *
	 * @return mixed
	 */
	public function wpAjaxSlug()
	{
		return self::AJAX_SLUG;
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
}
