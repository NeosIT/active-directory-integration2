<?php

namespace Dreitier\Nadi\Ui\Menu;

use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Synchronization\Ui\SyncToActiveDirectoryPage;
use Dreitier\Nadi\Synchronization\Ui\SyncToWordPressPage;
use Dreitier\Nadi\Ui\ConnectivityTestPage;
use Dreitier\Nadi\Ui\NadiSingleSiteConfigurationPage;
use Dreitier\WordPress\Multisite\Configuration\Service;
use Dreitier\WordPress\Multisite\Option\Provider;
use Dreitier\WordPress\Multisite\Ui\Actions;
use Dreitier\WordPress\Multisite\View\Page\Page;

/**
 * Menu registers menu entries for a single site installation.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 *
 * @access public
 */
class Menu extends MenuAdapter
{
	/* @var Service $multisiteConfigurationService */
	private $multisiteConfigurationService;

	/** @var NadiSingleSiteConfigurationPage | Page */
	private $blogConfigurationPage;

	/** @param SyncToWordPressPage | Page */
	private $syncToWordPressPage;

	/** @param SyncToActiveDirectoryPage | Page */
	private $syncToActiveDirectoryPage;

	/** @var ConnectivityTestPage | Page */
	private $connectivityTestPage;

	/**
	 * @param Provider $optionProvider
	 * @param Service $configuration
	 * @param NadiSingleSiteConfigurationPage $blogConfigurationPage
	 * @param ConnectivityTestPage $connectivityTestPage
	 * @param SyncToWordPressPage $syncToWordPressPage
	 * @param SyncToActiveDirectoryPage $syncToActiveDirectoryPage
	 */
	public function __construct(Provider                        $optionProvider,
								Service                         $configuration,
								NadiSingleSiteConfigurationPage $blogConfigurationPage,
								ConnectivityTestPage            $connectivityTestPage,
								SyncToWordPressPage             $syncToWordPressPage,
								SyncToActiveDirectoryPage       $syncToActiveDirectoryPage)
	{
		parent::__construct($optionProvider);

		$this->multisiteConfigurationService = $configuration;
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
		add_action(Actions::ADI_MENU_ADMIN_MENU, array($this, 'registerMenu'));

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

		if ($this->multisiteConfigurationService->getOptionValue(Options::SHOW_MENU_TEST_AUTHENTICATION)) {
			$this->addSubMenu($mainMenuSlug, $permission, $this->connectivityTestPage, $renderMethodName);
		}

		if ($this->multisiteConfigurationService->getOptionValue(Options::SHOW_MENU_SYNC_TO_AD)) {
			$this->addSubMenu($mainMenuSlug, $permission, $this->syncToActiveDirectoryPage, $renderMethodName);
		}

		if ($this->multisiteConfigurationService->getOptionValue(Options::SHOW_MENU_SYNC_TO_WORDPRESS)) {
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
