<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_Ui_Menu_Abstract')) {
	return;
}

/**
 * Adi_Ui_Menu_Abstract provides basic functionality for menu entries.
 *
 * @author Christopher Klein <ckl@neos-it.de>
 *
 * @access public
 */
abstract class Adi_Ui_Menu_Abstract
{
	/**
	 * @var Multisite_Option_Provider
	 */
	private $optionProvider;

	/**
	 * Adi_Ui_Menu_Abstract constructor.
	 * @param Multisite_Option_Provider $optionProvider
	 */
	public function __construct(Multisite_Option_Provider $optionProvider)
	{
		$this->optionProvider = $optionProvider;
	}

	/**
	 * Register all menu pages.
	 */
	abstract public function register();

	/**
	 * Adds the given ajax listener for the given page.
	 *
	 * @access protected
	 * @param Multisite_View_Page $page
	 * @return bool|true|void
	 */
	function addAjaxListener($page)
	{
		if (!$page instanceof Multisite_View_Page) {
			return false;
		}

		$wordPressAjaxListener = 'wpAjaxListener';

		return add_action(Adi_Ui_Actions::ADI_MENU_WP_AJAX_PREFIX . $page->wpAjaxSlug(), array(
			$page, $wordPressAjaxListener,
		));
	}

	/**
	 * Add help tab.
	 */
	public function addHelpTab()
	{
		// gets all the OptionMetaData
		$options = $this->optionProvider->getAll();
		$screen = get_current_screen();

		foreach ($options as $optionName => $option) {
			if (!isset($option[Multisite_Option_Attribute::DETAIL])) {
				continue;
			}

			$data = $this->generateHelpTabEntry($option, $optionName);
			$screen->add_help_tab($data);
		}
	}

	/**
	 * Return help tab entry.
	 *
	 * @access protected
	 * @param $screen
	 * @param array $option
	 * @param string $optionName
	 *
	 * @return array
	 */
	function generateHelpTabEntry($option, $optionName) {
		$title = Core_Util_ArrayUtil::get(Multisite_Option_Attribute::TITLE, $option, '');
		$detail = Core_Util_ArrayUtil::get(Multisite_Option_Attribute::DETAIL, $option, '');
		$content = '<p>' . Core_Util_StringUtil::concat($detail, '<br />') . '</p>';

		return array(
			'id'      => $optionName,
			'title'   => $title,
			'content' => $content,
		);
	}

	/**
	 * Adds a sub menu to the given main menu.
	 *
	 * @access protected
	 * @param string $mainMenuSlug
	 * @param mixed $permission
	 * @param Multisite_View_Page $page
	 * @param string $callbackMethodName
	 *
	 * @return bool|false|string
	 */
	function addSubMenu($mainMenuSlug, $permission, $page, $callbackMethodName)
	{
		if (!$page instanceof Multisite_View_Page) {
			return false;
		}

		$pageTitle = $page->getTitle();
		$pageSlug = $page->getSlug();

		return add_submenu_page($mainMenuSlug, $pageTitle, $pageTitle, $permission, $pageSlug, array($page, $callbackMethodName));
	}
}
