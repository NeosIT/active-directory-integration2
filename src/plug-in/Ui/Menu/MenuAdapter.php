<?php

namespace Dreitier\Nadi\Ui\Menu;

use Dreitier\Util\ArrayUtil;
use Dreitier\Util\StringUtil;
use Dreitier\WordPress\Multisite\Option\Attribute;
use Dreitier\WordPress\Multisite\Option\Provider;
use Dreitier\WordPress\Multisite\Ui\Actions;
use Dreitier\WordPress\Multisite\View\Page\Page;

/**
 * MenuAdapter provides basic functionality for menu entries.
 *
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access public
 */
abstract class MenuAdapter
{
	/**
	 * @var Provider
	 */
	private $optionProvider;

	/**
	 * @param Provider $optionProvider
	 */
	public function __construct(Provider $optionProvider)
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
	 * @param Page $page
	 * @return bool|true|void
	 */
	function addAjaxListener($page)
	{
		if (!$page instanceof Page) {
			return false;
		}

		$wordPressAjaxListener = 'wpAjaxListener';

		return add_action(Actions::ADI_MENU_WP_AJAX_PREFIX . $page->wpAjaxSlug(), array(
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
			if (!isset($option[Attribute::DETAIL])) {
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
	function generateHelpTabEntry($option, $optionName)
	{
		$title = ArrayUtil::get(Attribute::TITLE, $option, '');
		$detail = ArrayUtil::get(Attribute::DETAIL, $option, '');
		$content = '<p>' . StringUtil::concat($detail, '<br />') . '</p>';

		return array(
			'id' => $optionName,
			'title' => $title,
			'content' => $content,
		);
	}

	/**
	 * Adds a sub menu to the given main menu.
	 *
	 * @access protected
	 * @param string $mainMenuSlug
	 * @param mixed $permission
	 * @param Page $page
	 * @param string $callbackMethodName
	 *
	 * @return bool|false|string
	 */
	function addSubMenu($mainMenuSlug, $permission, $page, $callbackMethodName)
	{
		if (!$page instanceof Page) {
			return false;
		}

		$pageTitle = $page->getTitle();
		$pageSlug = $page->getSlug();

		return add_submenu_page($mainMenuSlug, $pageTitle, $pageTitle, $permission, $pageSlug, array($page, $callbackMethodName));
	}
}
