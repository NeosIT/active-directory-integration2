<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Multisite_Ui_Menu')) {
	return;
}

/**
 * NextADInt_Adi_Ui_Menu registers the multisite menu
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 *
 * @access public
 */
class NextADInt_Adi_Multisite_Ui_Menu extends NextADInt_Adi_Ui_Menu_Abstract
{
	/** @var NextADInt_Multisite_Ui_BlogProfileRelationshipPage | NextADInt_Multisite_View_Page */
	private $blogProfileRelationshipPage;

	/** @var NextADInt_Multisite_Ui_ProfileConfigurationPage | NextADInt_Multisite_View_Page */
	private $profileConfigurationPage;

	/**
	 * NextADInt_Adi_Multisite_Ui_Menu constructor.
	 *
	 * @param NextADInt_Multisite_Option_Provider                $optionProvider
	 * @param NextADInt_Multisite_Ui_BlogProfileRelationshipPage $blogProfileRelationshipPage
	 * @param NextADInt_Multisite_Ui_ProfileConfigurationPage    $profileConfigurationPage
	 */
	public function __construct(NextADInt_Multisite_Option_Provider $optionProvider,
		NextADInt_Multisite_Ui_BlogProfileRelationshipPage $blogProfileRelationshipPage,
		NextADInt_Multisite_Ui_ProfileConfigurationPage $profileConfigurationPage
	) {
		parent::__construct($optionProvider);

		$this->blogProfileRelationshipPage = $blogProfileRelationshipPage;
		$this->profileConfigurationPage = $profileConfigurationPage;
	}

	/**
	 * Register all menu pages.
	 */
	public function register()
	{
		add_action(NextADInt_Adi_Ui_Actions::ADI_MENU_NETWORK_ADMIN_MENU, array($this, 'registerMenu'));

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
