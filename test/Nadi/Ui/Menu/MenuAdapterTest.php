<?php

namespace Dreitier\Nadi\Ui\Menu;

use Dreitier\Test\BasicTest;
use Dreitier\WordPress\Multisite\Option\Provider;
use Dreitier\WordPress\Multisite\View\Page\Page;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class MenuAdapterStub extends MenuAdapter
{
	public function register()
	{
	}
}

class MenuAdapterTest extends BasicTest
{
	/**
	 * @var Provider
	 */
	private $optionProvider;

	public function setUp(): void
	{
		parent::setUp();

		$this->optionProvider = $this->createMock(Provider::class);
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 *
	 * @param null $methods
	 *
	 * @return MenuAdapter|MockObject
	 */
	private function sut($methods = null)
	{
		return $this->getMockBuilder(MenuAdapterStub::class)
			->setConstructorArgs(
				array(
					$this->optionProvider
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function addHelpTab_addsCorrectHelpTabToScreen()
	{
		$sut = $this->sut(null);

		$this->optionProvider->expects($this->once())
			->method('getAll')
			->willReturn(array('domain_controllers' => array('detail' => 'detail', 'title' => 'title')));

		$screen = $this->createMockWithMethods('BlueprintClass', array('add_help_tab'));

		\WP_Mock::wpFunction('get_current_screen', array(
			'return' => $screen,
		));

		$screen->expects($this->once())
			->method('add_help_tab')
			->withConsecutive(
				array(
					array(
						'id' => 'domain_controllers',
						'title' => 'title',
						'content' => '<p>' . 'detail' . '</p>',
					),
				)
			);

		$sut->addHelpTab();
	}

	/**
	 * @test
	 */
	public function addSubMenu_returnsFalse_ifPageIsNoInstanceOfPageInterface()
	{
		$sut = $this->sut();

		$result = $this->invokeMethod($sut, 'addSubMenu', array(
			null, null, null, null,
		));

		$this->assertFalse($result);
	}

	/**
	 * @test
	 */
	public function addSubMenu_addsSubMenuPage_ifPageIsInstanceOfPageInterface()
	{
		$sut = $this->sut();

		$title = 'title';
		$slug = 'slug';

		$menuSlug = 'menuSlug';
		$permission = null;
		$callbackMethodName = 'renderAdmin';

		$page = $this->getMockBuilder(Page::class)->setMethods(array(
			'getTitle', 'getSlug', 'wpAjaxSlug',
		))->getMock();

		$page->expects($this->once())
			->method('getTitle')
			->willReturn($title);

		$page->expects($this->once())
			->method('getSlug')
			->willReturn($slug);

		\WP_Mock::wpFunction('add_submenu_page', array(
			'args' => array($menuSlug, $title, $title, $permission, $slug, array($page, $callbackMethodName)),
			'times' => 1,
		));

		$this->invokeMethod($sut, 'addSubMenu', array($menuSlug, $permission, $page, $callbackMethodName));
	}
}