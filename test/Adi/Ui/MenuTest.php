<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_Adi_Ui_MenuTest extends Ut_BasicTest
{
	/* @var Multisite_Configuration_Service $configuration */
	private $configuration;

	/* @var Multisite_Ui_BlogConfigurationPage | PHPUnit_Framework_MockObject_MockObject */
	private $blogConfigurationPage;

	/* @var Adi_Synchronization_Ui_SyncToWordPressPage | PHPUnit_Framework_MockObject_MockObject */
	private $syncToWordPressPage;

	/* @var Adi_Synchronization_Ui_SyncToActiveDirectoryPage | PHPUnit_Framework_MockObject_MockObject */
	private $syncToActiveDirectoryPage;

	/* @var Adi_Ui_ConnectivityTestPage | PHPUnit_Framework_MockObject_MockObject */
	private $connectivityTestPage;

	public function setUp()
	{
		parent::setUp();

		$this->configuration = $this->createMock('Multisite_Configuration_Service');
		$this->blogConfigurationPage = $this->createMock('Multisite_Ui_BlogConfigurationPage');
		$this->connectivityTestPage = $this->createMock('Adi_Ui_ConnectivityTestPage');
		$this->syncToActiveDirectoryPage = $this->createMock('Adi_Synchronization_Ui_SyncToActiveDirectoryPage');
		$this->syncToWordPressPage = $this->createMock('Adi_Synchronization_Ui_SyncToWordPressPage');
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 *
	 * @param null $methods
	 *
	 * @return Adi_Ui_Menu|PHPUnit_Framework_MockObject_MockObject
	 */
	private function sut($methods = null)
	{
		return $this->getMockBuilder('Adi_Ui_Menu')
			->setConstructorArgs(
				array(
					$this->createMock('Multisite_Option_Provider'),
					$this->configuration,
					$this->blogConfigurationPage,
					$this->connectivityTestPage,
					$this->syncToWordPressPage,
					$this->syncToActiveDirectoryPage
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
			->withConsecutive(
				$this->blogConfigurationPage
			);

		$sut->register();
	}

	/**
	 * @test
	 */
	public function registerMenu_addsMenusToWordPress()
	{
		$sut = $this->sut(array('addSubMenu', 'blogOption'));

		$this->blogConfigurationPage->expects($this->once())
			->method('getSlug')
			->willReturn('adi2_slug');

		WP_Mock::wpFunction('add_menu_page', array(
			'args' => array('Active Directory Integration', 'Active Directory Integration', 'manage_options', 'adi2_slug'),
			'times' => 1
		));

		$sut->expects($this->once())
			->method('addSubMenu')
			->with('adi2_slug', 'manage_options', $this->blogConfigurationPage, 'renderAdmin')
			->willReturn('adi2_blog_page', '', '', '');

		// check methods
		WP_Mock::expectActionAdded('admin_enqueue_scripts', array($sut, 'loadScriptsAndStyle'));
		// WP_Mock::expectActionAdded('load-adi2_blog_page', array($sut, 'addHelpTAb'));

		$sut->registerMenu();
	}

	/**
	 * @test
	 */
	public function registerMenu_whenShowTestAuthentication_itEnablesTestAuthentication() {
		$sut = $this->sut(array('addSubMenu', 'blogOption'));

		$this->configuration->expects($this->at(0))
			->method('getOptionValue')
			->with(Adi_Configuration_Options::SHOW_MENU_TEST_AUTHENTICATION)
			->willReturn(true);

		$this->blogConfigurationPage->expects($this->once())
			->method('getSlug')
			->willReturn('adi2_slug');

		WP_Mock::wpFunction('add_menu_page', array(
			'args' => array('Active Directory Integration', 'Active Directory Integration', 'manage_options', 'adi2_slug'),
			'times' => 1
		));

		$sut->expects($this->exactly(2))
			->method('addSubMenu')
			->withConsecutive(
				array('adi2_slug', 'manage_options', $this->blogConfigurationPage, 'renderAdmin'),
				array('adi2_slug', 'manage_options', $this->connectivityTestPage, 'renderAdmin')
			);

		$sut->registerMenu();
	}


	/**
	 * @test
	 */
	public function registerMenu_whenShowSyncToAD_itEnablesSyncToAD() {
		$sut = $this->sut(array('addSubMenu', 'blogOption'));

		$this->configuration->expects($this->at(1))
			->method('getOptionValue')
			->with(Adi_Configuration_Options::SHOW_MENU_SYNC_TO_AD)
			->willReturn(true);

		$this->blogConfigurationPage->expects($this->once())
			->method('getSlug')
			->willReturn('adi2_slug');

		WP_Mock::wpFunction('add_menu_page', array(
			'args' => array('Active Directory Integration', 'Active Directory Integration', 'manage_options', 'adi2_slug'),
			'times' => 1
		));

		$sut->expects($this->exactly(2))
			->method('addSubMenu')
			->withConsecutive(
				array('adi2_slug', 'manage_options', $this->blogConfigurationPage, 'renderAdmin'),
				array('adi2_slug', 'manage_options', $this->syncToActiveDirectoryPage, 'renderAdmin')
			);

		$sut->registerMenu();
	}


	/**
	 * @test
	 */
	public function registerMenu_whenShowSyncToWordPress_itEnablesSyncToWordPress() {
		$sut = $this->sut(array('addSubMenu', 'blogOption'));

		$this->configuration->expects($this->at(2))
			->method('getOptionValue')
			->with(Adi_Configuration_Options::SHOW_MENU_SYNC_TO_WORDPRESS)
			->willReturn(true);

		$this->blogConfigurationPage->expects($this->once())
			->method('getSlug')
			->willReturn('adi2_slug');

		WP_Mock::wpFunction('add_menu_page', array(
			'args' => array('Active Directory Integration', 'Active Directory Integration', 'manage_options', 'adi2_slug'),
			'times' => 1
		));

		$sut->expects($this->exactly(2))
			->method('addSubMenu')
			->withConsecutive(
				array('adi2_slug', 'manage_options', $this->blogConfigurationPage, 'renderAdmin'),
				array('adi2_slug', 'manage_options', $this->syncToWordPressPage, 'renderAdmin')
			);

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

		$this->blogConfigurationPage->expects($this->once())
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