<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Ui_Menu')) {
	return;
}

/**
 * NextADInt_Adi_Ui_Menu registers menu entries for a single site installation.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 *
 * @access public
 */
class NextADInt_Adi_Ui_Menu extends NextADInt_Adi_Ui_Menu_Abstract
{
	/* @var NextADInt_Multisite_Configuration_Service $configuration */
	private $configuration;

	/** @var NextADInt_Multisite_Ui_BlogConfigurationPage | NextADInt_Multisite_View_Page */
	private $blogConfigurationPage;

	/** @param NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage | Adi_Page_PageInterface */
	private $syncToWordPressPage;

	/** @param NextADInt_Adi_Synchronization_Ui_SyncToActiveDirectoryPage | Adi_Page_PageInterface */
	private $syncToActiveDirectoryPage;

	/** @var NextADInt_Adi_Ui_ConnectivityTestPage | NextADInt_Multisite_View_Page */
	private $connectivityTestPage;

	/**
	 * NextADInt_Adi_Ui_Menu constructor.
	 *
	 * @param NextADInt_Multisite_Option_Provider $optionProvider
	 * @param NextADInt_Multisite_Configuration_Service $configuration
	 * @param NextADInt_Multisite_Ui_BlogConfigurationPage $blogConfigurationPage
	 * @param NextADInt_Adi_Ui_ConnectivityTestPage $connectivityTestPage
	 * @param NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage $syncToWordPressPage
	 * @param NextADInt_Adi_Synchronization_Ui_SyncToActiveDirectoryPage $syncToActiveDirectoryPage
	 */
	public function __construct(NextADInt_Multisite_Option_Provider $optionProvider,
								NextADInt_Multisite_Configuration_Service $configuration,
								NextADInt_Multisite_Ui_BlogConfigurationPage $blogConfigurationPage,
								NextADInt_Adi_Ui_ConnectivityTestPage $connectivityTestPage,
								NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage $syncToWordPressPage,
								NextADInt_Adi_Synchronization_Ui_SyncToActiveDirectoryPage $syncToActiveDirectoryPage) {
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
		add_action(NextADInt_Adi_Ui_Actions::ADI_MENU_ADMIN_MENU, array($this, 'registerMenu'));

		$this->addAjaxListener($this->blogConfigurationPage);
	}

	/**
	 * Register all pages for the admin menu for a single blog
	 */
	public function registerMenu()
	{
		$permission = 'manage_options';
		$renderMethodName = 'renderAdmin';
		$mainMenuTitle = esc_html__('Active Directory Integration', 'next-active-directory-integration');
		$mainMenuSlug = $this->blogConfigurationPage->getSlug();

		// add menu
		add_menu_page($mainMenuTitle, $mainMenuTitle, $permission, $mainMenuSlug);

		// add sub menus
		$blogOptionPage = $this->addSubMenu($mainMenuSlug, $permission, $this->blogConfigurationPage, $renderMethodName);

		if ($this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SHOW_MENU_TEST_AUTHENTICATION)) {
			$this->addSubMenu($mainMenuSlug, $permission, $this->connectivityTestPage, $renderMethodName);
		}

		if ($this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SHOW_MENU_SYNC_TO_AD)) {
			$this->addSubMenu($mainMenuSlug, $permission, $this->syncToActiveDirectoryPage, $renderMethodName);
		}

		if ($this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SHOW_MENU_SYNC_TO_WORDPRESS)) {
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
