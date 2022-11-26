<?php

namespace Dreitier\Nadi\Multisite\Ui;

use Dreitier\Nadi\Ui\NadiMultisiteConfigurationPage;
use Dreitier\Test\BasicTest;
use Dreitier\WordPress\Multisite\Option\Provider;
use Dreitier\WordPress\Multisite\Ui\BlogProfileRelationshipPage;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access private
 */
class MultisiteMenuTest extends BasicTest
{
	/* @var BlogProfileRelationshipPage | MockObject */
	private $blogProfileRelationshipPage;

	/* @var NadiMultisiteConfigurationPage | MockObject */
	private $nadiMultisiteConfigurationPage;

	public function setUp(): void
	{
		parent::setUp();

		$this->blogProfileRelationshipPage = $this->createMock(BlogProfileRelationshipPage::class);
		$this->nadiMultisiteConfigurationPage = $this->createMock(NadiMultisiteConfigurationPage::class);
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 *
	 * @param null $methods
	 *
	 * @return MultisiteMenu|MockObject
	 */
	private function sut($methods = null)
	{
		return $this->getMockBuilder(MultisiteMenu::class)
			->setConstructorArgs(
				array(
					$this->createMock(Provider::class),
					$this->blogProfileRelationshipPage,
					$this->nadiMultisiteConfigurationPage,
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function register_itAddsTheMenus()
	{
		$sut = $this->sut(array('addAjaxListeners'));

		\WP_Mock::expectActionAdded('network_admin_menu', array($sut, 'registerMenu'));

		$sut->register();
	}

	/**
	 * @test
	 */
	public function register_itAddsTheAjaxListeners()
	{
		$sut = $this->sut(array('addAjaxListener'));

		$sut->expects($this->exactly(2))
			->method('addAjaxListener')
			->withConsecutive(
				[$this->blogProfileRelationshipPage],
				[$this->nadiMultisiteConfigurationPage]
			);

		$sut->register();
	}

	/**
	 * @test
	 */
	public function registerMenu_addsMenusToWordPress()
	{
		$sut = $this->sut(array('addSubMenu'));
		$this->mockFunctionEsc_html__();

		$permission = 'manage_network';
		$networkMenu = 'Active Directory Integration';

		\WP_Mock::wpFunction('add_menu_page', array(
			'args' => array($networkMenu, $networkMenu, $permission, 'next_ad_int_slug'),
			'times' => '1',
		));

		$this->blogProfileRelationshipPage->expects($this->once())
			->method('getSlug')
			->willReturn('next_ad_int_slug');

		$sut->expects($this->exactly(2))
			->method('addSubMenu')
			->withConsecutive(
				array('next_ad_int_slug', 'manage_network', $this->blogProfileRelationshipPage, 'renderNetwork'),
				array('next_ad_int_slug', 'manage_network', $this->nadiMultisiteConfigurationPage, 'renderNetwork')
			)
			->willReturn('next_ad_int_page', '', '', '');

		\WP_Mock::expectActionAdded('admin_enqueue_scripts', array($sut, 'loadScriptsAndStyle'));

		$sut->registerMenu();
	}

	/**
	 * @test
	 */
	public function loadScriptsAndStyle_itloadsAllJavaScriptAndCssFilesFromNecessaryPages()
	{
		$sut = $this->sut();
		$hook = 'testHook';
		$objectMethod = 'loadNetworkScriptsAndStyle';

		$this->blogProfileRelationshipPage->expects($this->once())
			->method($objectMethod)
			->with($hook);

		$this->nadiMultisiteConfigurationPage->expects($this->once())
			->method($objectMethod)
			->with($hook);

		$sut->loadScriptsAndStyle($hook);
	}
}