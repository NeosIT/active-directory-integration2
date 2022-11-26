<?php

namespace Dreitier\Nadi\Multisite\Ui;

use Dreitier\Nadi\Ui\Menu\MenuAdapter;
use Dreitier\Nadi\Ui\NadiMultisiteConfigurationPage;
use Dreitier\WordPress\Multisite\Option\Provider;
use Dreitier\WordPress\Multisite\Ui\Actions;
use Dreitier\WordPress\Multisite\Ui\BlogProfileRelationshipPage;
use Dreitier\WordPress\Multisite\View\Page\Page;

/**
 * Registers the multisite menu
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 *
 * @access public
 */
class MultisiteMenu extends MenuAdapter
{
	/** @var BlogProfileRelationshipPage | Page */
	private $blogProfileRelationshipPage;

	/** @var NadiMultisiteConfigurationPage | Page */
	private $profileConfigurationPage;

	/**
	 * @param Provider $optionProvider
	 * @param BlogProfileRelationshipPage $blogProfileRelationshipPage
	 * @param NadiMultisiteConfigurationPage $nadiMultisiteConfigurationPage
	 */
	public function __construct(Provider                       $optionProvider,
								BlogProfileRelationshipPage    $blogProfileRelationshipPage,
								NadiMultisiteConfigurationPage $nadiMultisiteConfigurationPage
	)
	{
		parent::__construct($optionProvider);

		$this->blogProfileRelationshipPage = $blogProfileRelationshipPage;
		$this->profileConfigurationPage = $nadiMultisiteConfigurationPage;
	}

	/**
	 * Register all menu pages.
	 */
	public function register()
	{
		add_action(Actions::ADI_MENU_NETWORK_ADMIN_MENU, array($this, 'registerMenu'));

		$this->addAjaxListener($this->blogProfileRelationshipPage);
		$this->addAjaxListener($this->profileConfigurationPage);
	}

	/**
	 * Register all pages for the network admin menu
	 */
	public function registerMenu()
	{
		$permission = 'manage_network';
		$renderMethodName = 'renderNetwork';
		$networkMenuTitle = esc_html__('Active Directory Integration', 'next-active-directory-integration');
		$networkMenuSlug = $this->blogProfileRelationshipPage->getSlug(); // the header of the group must have the slug of the first item

		// add menu header
		add_menu_page($networkMenuTitle, $networkMenuTitle, $permission, $networkMenuSlug);

		// add sub menus
		$this->addSubMenu($networkMenuSlug, $permission, $this->blogProfileRelationshipPage, $renderMethodName);
		$profileConfigurationPage = $this->addSubMenu($networkMenuSlug, $permission, $this->profileConfigurationPage, $renderMethodName);

		add_action('admin_enqueue_scripts', array($this, 'loadScriptsAndStyle'));
		add_action('load-' . $profileConfigurationPage, array($this, 'addHelpTab'));
	}

	/**
	 * Add scripts/css to the network admin menu.
	 *
	 * @param $hook
	 */
	public function loadScriptsAndStyle($hook)
	{
		$this->blogProfileRelationshipPage->loadNetworkScriptsAndStyle($hook);
		$this->profileConfigurationPage->loadNetworkScriptsAndStyle($hook);
	}
}
