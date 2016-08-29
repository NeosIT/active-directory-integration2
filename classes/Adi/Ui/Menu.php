<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_Ui_Menu')) {
	return;
}

/**
 * Adi_Ui_Menu registers menu entries for a single site installation.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 *
 * @access public
 */
class Adi_Ui_Menu extends Adi_Ui_Menu_Abstract
{
	/* @var Multisite_Configuration_Service $configuration */
	private $configuration;

	/** @var Multisite_Ui_BlogConfigurationPage | Multisite_View_Page */
	private $blogConfigurationPage;

	/** @param Adi_Synchronization_Ui_SyncToWordPressPage | Adi_Page_PageInterface */
	private $syncToWordPressPage;

	/** @param Adi_Synchronization_Ui_SyncToActiveDirectoryPage | Adi_Page_PageInterface */
	private $syncToActiveDirectoryPage;

	/** @var Adi_Ui_ConnectivityTestPage | Multisite_View_Page */
	private $connectivityTestPage;

	/**
	 * Adi_Ui_Menu constructor.
	 *
	 * @param Multisite_Option_Provider $optionProvider
	 * @param Multisite_Configuration_Service $configuration
	 * @param Multisite_Ui_BlogConfigurationPage $blogConfigurationPage
	 * @param Adi_Ui_ConnectivityTestPage $connectivityTestPage
	 * @param Adi_Synchronization_Ui_SyncToWordPressPage $syncToWordPressPage
	 * @param Adi_Synchronization_Ui_SyncToActiveDirectoryPage $syncToActiveDirectoryPage
	 */
	public function __construct(Multisite_Option_Provider $optionProvider,
								Multisite_Configuration_Service $configuration,
								Multisite_Ui_BlogConfigurationPage $blogConfigurationPage,
								Adi_Ui_ConnectivityTestPage $connectivityTestPage,
								Adi_Synchronization_Ui_SyncToWordPressPage $syncToWordPressPage,
								Adi_Synchronization_Ui_SyncToActiveDirectoryPage $syncToActiveDirectoryPage) {
		parent::__construct($optionProvider);

		$this->configuration = $configuration;
		$this->blogConfigurationPage = $blogConfigurationPage;
		$this->connectivityTestPage = $connectivityTestPage;
		$this->syncToWordPressPage = $syncToWordPressPage;
		$this->syncToActiveDirectoryPage = $syncToActiveDirectoryPage;
	}

	/**
	 * Register all menu pages.
	 */
	public function register()
	{
		add_action(Adi_Ui_Actions::ADI_MENU_ADMIN_MENU, array($this, 'registerMenu'));

		$this->addAjaxListener($this->blogConfigurationPage);
	}

	/**
	 * Register all pages for the admin menu for a single blog
	 */
	public function registerMenu()
	{
		$permission = 'manage_options';
		$renderMethodName = 'renderAdmin';
		$mainMenuTitle = esc_html__('Active Directory Integration', NEXT_AD_INT_I18N);
		$mainMenuSlug = $this->blogConfigurationPage->getSlug();

		// add menu
		add_menu_page($mainMenuTitle, $mainMenuTitle, $permission, $mainMenuSlug);

		// add sub menus
		$blogOptionPage = $this->addSubMenu($mainMenuSlug, $permission, $this->blogConfigurationPage, $renderMethodName);

		if ($this->configuration->getOptionValue(Adi_Configuration_Options::SHOW_MENU_TEST_AUTHENTICATION)) {
			$this->addSubMenu($mainMenuSlug, $permission, $this->connectivityTestPage, $renderMethodName);
		}

		if ($this->configuration->getOptionValue(Adi_Configuration_Options::SHOW_MENU_SYNC_TO_AD)) {
			$this->addSubMenu($mainMenuSlug, $permission, $this->syncToActiveDirectoryPage, $renderMethodName);
		}

		if ($this->configuration->getOptionValue(Adi_Configuration_Options::SHOW_MENU_SYNC_TO_WORDPRESS)) {
			$this->addSubMenu($mainMenuSlug, $permission, $this->syncToWordPressPage, $renderMethodName);
		}

		$load = 'load-' . $blogOptionPage;

		add_action('admin_enqueue_scripts', array($this, 'loadScriptsAndStyle'));
		add_action($load, array($this, 'addHelpTab'));
	}

	/**
	 * Add scripts/css to the blog/site admin menu.
	 *
	 * @param $hook
	 */
	public function loadScriptsAndStyle($hook)
	{
		$this->blogConfigurationPage->loadAdminScriptsAndStyle($hook);
		$this->connectivityTestPage->loadAdminScriptsAndStyle($hook);
		$this->syncToActiveDirectoryPage->loadAdminScriptsAndStyle($hook);
		$this->syncToWordPressPage->loadAdminScriptsAndStyle($hook);
	}
}
