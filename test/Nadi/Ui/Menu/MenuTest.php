<?php

namespace Dreitier\Nadi\Ui\Menu;

use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Synchronization\Ui\SyncToActiveDirectoryPage;
use Dreitier\Nadi\Synchronization\Ui\SyncToWordPressPage;
use Dreitier\Nadi\Ui\ConnectivityTestPage;
use Dreitier\Nadi\Ui\NadiSingleSiteConfigurationPage;
use Dreitier\Test\BasicTestCase;
use Dreitier\WordPress\Multisite\Configuration\Service;
use Dreitier\WordPress\Multisite\Option\Provider;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class MenuTest extends BasicTestCase
{
	/* @var Service $configuration */
	private $configuration;

	/* @var NadiSingleSiteConfigurationPage | MockObject */
	private $nadiSingleSiteConfigurationPage;

	/* @var SyncToWordPressPage | MockObject */
	private $syncToWordPressPage;

	/* @var SyncToActiveDirectoryPage | MockObject */
	private $syncToActiveDirectoryPage;

	/* @var ConnectivityTestPage | MockObject */
	private $connectivityTestPage;

	public function setUp(): void
	{
		parent::setUp();

		$this->configuration = $this->createMock(Service::class);
		$this->nadiSingleSiteConfigurationPage = $this->createMock(NadiSingleSiteConfigurationPage::class);
		$this->connectivityTestPage = $this->createMock(ConnectivityTestPage::class);
		$this->syncToActiveDirectoryPage = $this->createMock(SyncToActiveDirectoryPage::class);
		$this->syncToWordPressPage = $this->createMock(SyncToWordPressPage::class);
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 *
	 * @param null $methods
	 *
	 * @return Menu|MockObject
	 */
	private function sut(array $methods = [])
	{
		return $this->getMockBuilder(Menu::class)
			->setConstructorArgs(
				array(
					$this->createMock(Provider::class),
					$this->configuration,
					$this->nadiSingleSiteConfigurationPage,
					$this->connectivityTestPage,
					$this->syncToWordPressPage,
					$this->syncToActiveDirectoryPage
				)
			)
			->onlyMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function register_itAddsTheMenus()
	{
		$sut = $this->sut(array('addAjaxListener'));

		\WP_Mock::expectActionAdded('admin_menu', array($sut, 'registerMenu'));

		$sut->register();
	}

	/**
	 * @test
	 */
	public function register_itAddsTheAjaxListeners()
	{
		$sut = $this->sut(array('addAjaxListener'));

		$sut->expects($this->exactly(1))
			->method('addAjaxListener')
			->with(...self::withConsecutive(
				[$this->nadiSingleSiteConfigurationPage]
			));

		$sut->register();
	}

	/**
	 * @test
	 */
	public function registerMenu_addsMenusToWordPress()
	{
		$sut = $this->sut(array('addSubMenu'));
		$this->mockFunctionEsc_html__();

		$this->nadiSingleSiteConfigurationPage->expects($this->once())
			->method('getSlug')
			->willReturn('next_ad_int_slug');

		\WP_Mock::userFunction('add_menu_page', array(
			'args' => array('Active Directory Integration', 'Active Directory Integration', 'manage_options', 'next_ad_int_slug'),
			'times' => 1
		));

		$sut->expects($this->once())
			->method('addSubMenu')
			->with('next_ad_int_slug', 'manage_options', $this->nadiSingleSiteConfigurationPage, 'renderAdmin')
			->willReturn('next_ad_int_blog_page', '', '', '');

		// check methods
		\WP_Mock::expectActionAdded('admin_enqueue_scripts', array($sut, 'loadScriptsAndStyle'));
		// WP_Mock::expectActionAdded('load-adi2_blog_page', array($sut, 'addHelpTAb'));

		$sut->registerMenu();
	}

	/**
	 * @test
	 */
	public function registerMenu_whenShowTestAuthentication_itEnablesTestAuthentication()
	{
		$sut = $this->sut(array('addSubMenu'));
		$this->mockFunction__();
		$this->mockFunctionEsc_html__();

		$this->configuration->expects($this->exactly(3))
			->method('getOptionValue')
			->with(...self::withConsecutive(
				[Options::SHOW_MENU_TEST_AUTHENTICATION],
				[Options::SHOW_MENU_SYNC_TO_AD],
				[Options::SHOW_MENU_SYNC_TO_WORDPRESS]
			))
			->willReturn(
				true,
				false,
				false);

		$this->nadiSingleSiteConfigurationPage->expects($this->once())
			->method('getSlug')
			->willReturn('next_ad_int_slug');

		\WP_Mock::userFunction('add_menu_page', array(
			'args' => array('Active Directory Integration', 'Active Directory Integration', 'manage_options', 'next_ad_int_slug'),
			'times' => 1
		));

		$sut->expects($this->exactly(2))
			->method('addSubMenu')
			->with(...self::withConsecutive(
				array('next_ad_int_slug', 'manage_options', $this->nadiSingleSiteConfigurationPage, 'renderAdmin'),
				array('next_ad_int_slug', 'manage_options', $this->connectivityTestPage, 'renderAdmin')
			));

		$sut->registerMenu();
	}


	/**
	 * @test
	 */
	public function registerMenu_whenShowSyncToAD_itEnablesSyncToAD()
	{
		$sut = $this->sut(array('addSubMenu'));
		$this->mockFunctionEsc_html__();

		$this->configuration->expects($this->exactly(3))
			->method('getOptionValue')
			->with(...self::withConsecutive(
				[Options::SHOW_MENU_TEST_AUTHENTICATION],
				[Options::SHOW_MENU_SYNC_TO_AD],
				[Options::SHOW_MENU_SYNC_TO_WORDPRESS]
			))
			->willReturn(
				false,
				true,
				false);

		$this->nadiSingleSiteConfigurationPage->expects($this->once())
			->method('getSlug')
			->willReturn('next_ad_int_slug');

		\WP_Mock::userFunction('add_menu_page', array(
			'args' => array('Active Directory Integration', 'Active Directory Integration', 'manage_options', 'next_ad_int_slug'),
			'times' => 1
		));

		$sut->expects($this->exactly(2))
			->method('addSubMenu')
			->with(...self::withConsecutive(
				array('next_ad_int_slug', 'manage_options', $this->nadiSingleSiteConfigurationPage, 'renderAdmin'),
				array('next_ad_int_slug', 'manage_options', $this->syncToActiveDirectoryPage, 'renderAdmin')
			));

		$sut->registerMenu();
	}


	/**
	 * @test
	 */
	public function registerMenu_whenShowSyncToWordPress_itEnablesSyncToWordPress()
	{
		$sut = $this->sut(array('addSubMenu'));
		$this->mockFunctionEsc_html__();

		$this->configuration->expects($this->exactly(3))
			->method('getOptionValue')
			->with(...self::withConsecutive(
				[Options::SHOW_MENU_TEST_AUTHENTICATION],
				[Options::SHOW_MENU_SYNC_TO_AD],
				[Options::SHOW_MENU_SYNC_TO_WORDPRESS]
			))
			->willReturn(
				false,
				false,
				true);

		$this->nadiSingleSiteConfigurationPage->expects($this->once())
			->method('getSlug')
			->willReturn('next_ad_int_slug');

		\WP_Mock::userFunction('add_menu_page', array(
			'args' => array('Active Directory Integration', 'Active Directory Integration', 'manage_options', 'next_ad_int_slug'),
			'times' => 1
		));

		$sut->expects($this->exactly(2))
			->method('addSubMenu')
			->with(...self::withConsecutive(
				array('next_ad_int_slug', 'manage_options', $this->nadiSingleSiteConfigurationPage, 'renderAdmin'),
				array('next_ad_int_slug', 'manage_options', $this->syncToWordPressPage, 'renderAdmin')
			));

		$sut->registerMenu();
	}

	/**
	 * @test
	 */
	public function loadScriptsAndStyle_loadsAllJavaScriptAndCssFilesFromNecessaryPages()
	{
		$sut = $this->sut();
		$hook = 'testHook';
		$objectMethod = 'loadAdminScriptsAndStyle';

		$this->nadiSingleSiteConfigurationPage->expects($this->once())
			->method($objectMethod)
			->with($hook);

		$this->connectivityTestPage->expects($this->once())
			->method($objectMethod)
			->with($hook);

		$this->syncToActiveDirectoryPage->expects($this->once())
			->method($objectMethod)
			->with($hook);

		$this->syncToWordPressPage->expects($this->once())
			->method($objectMethod)
			->with($hook);

		$sut->loadScriptsAndStyle($hook);
	}
}