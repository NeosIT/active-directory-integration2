<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_Multisite_Ui_Menu')) {
	return;
}

/**
 * Adi_Ui_Menu registers the multisite menu
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 *
 * @access public
 */
class Adi_Multisite_Ui_Menu extends Adi_Ui_Menu_Abstract
{
	/** @var Multisite_Ui_BlogProfileRelationshipPage | Multisite_View_Page */
	private $blogProfileRelationshipPage;

	/** @var Multisite_Ui_ProfileConfigurationPage | Multisite_View_Page */
	private $profileConfigurationPage;

	/**
	 * Adi_Multisite_Ui_Menu constructor.
	 *
	 * @param Multisite_Option_Provider                $optionProvider
	 * @param Multisite_Ui_BlogProfileRelationshipPage $blogProfileRelationshipPage
	 * @param Multisite_Ui_ProfileConfigurationPage    $profileConfigurationPage
	 */
	public function __construct(Multisite_Option_Provider $optionProvider,
		Multisite_Ui_BlogProfileRelationshipPage $blogProfileRelationshipPage,
		Multisite_Ui_ProfileConfigurationPage $profileConfigurationPage
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
		add_action(Adi_Ui_Actions::ADI_MENU_NETWORK_ADMIN_MENU, array($this, 'registerMenu'));

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
		$networkMenuTitle = esc_html__('Active Directory Integration', NEXT_AD_INT_I18N);
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
