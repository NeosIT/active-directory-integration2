<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_Adi_Synchronization_Ui_SyncToActiveDirectoryTest  extends Ut_BasicTest
{
	/* @var Multisite_View_TwigContainer | PHPUnit_Framework_MockObject_MockObject */
	private $twigContainer;

	/* @var Multisite_Configuration_Service | PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/* @var Adi_Synchronization_ActiveDirectory | PHPUnit_Framework_MockObject_MockObject */
	private $syncToActiveDirectory;

	public function setUp()
	{
		parent::setUp();

		$this->configuration = $this->createMock('Multisite_Configuration_Service');
		$this->twigContainer = $this->createMock('Multisite_View_TwigContainer');
		$this->syncToActiveDirectory = $this->createMock('Adi_Synchronization_ActiveDirectory');
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 *
	 * @return Adi_Synchronization_Ui_SyncToActiveDirectoryPage | PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('Adi_Synchronization_Ui_SyncToActiveDirectoryPage')
			->setConstructorArgs(
				array(
					$this->twigContainer,
					$this->syncToActiveDirectory,
					$this->configuration
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function getTitle_escapeTitle_returnTitle()
	{
		$sut = $this->sut(null);

		$returnedTitle = $sut->getTitle();
		$this->assertEquals(Adi_Synchronization_Ui_SyncToActiveDirectoryPage::TITLE, $returnedTitle);
	}

	/**
	 * @test
	 */
	public function getSlug_concat_returnSlug()
	{
		$sut = $this->sut(null);

		$returnedValue = $sut->getSlug();
		$this->assertEquals(ADI_PREFIX . Adi_Synchronization_Ui_SyncToActiveDirectoryPage::SLUG, $returnedValue);
	}

	/**
	 * @test
	 */
	public function wpAjaxSlug_findSlug_returnNull()
	{
		$sut = $this->sut(null);

		$returnedValue = $sut->wpAjaxSlug();
		$this->assertEquals(Adi_Synchronization_Ui_SyncToActiveDirectoryPage::AJAX_SLUG, $returnedValue);
	}

	/**
	 * @test
	 */
	public function getCapability_getValueFromConstant_returnCapability()
	{
		$sut = $this->sut(null);

		$returnedValue = $this->invokeMethod($sut, 'getCapability', array());
		$this->assertEquals(Adi_Synchronization_Ui_SyncToActiveDirectoryPage::CAPABILITY, $returnedValue);
	}

	/**
	 * @test
	 */
	public function renderAdmin_withCorrectCapability_delegateToMethod()
	{
		$sut = $this->sut(array('display', 'currentUserHasCapability'));

		$nonce = 'some_nonce';
		$authCode = 'auth_code';
		$blogUrl = 'blog_url';

		$sut->expects($this->once())
			->method('currentUserHasCapability')
			->willReturn(true);

		WP_Mock::wpFunction(
			'wp_create_nonce', array(
				'args'  => Adi_Synchronization_Ui_SyncToActiveDirectoryPage::NONCE,
				'times' => 1,
				'return' => $nonce
			)
		);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::SYNC_TO_AD_AUTHCODE)
			->willReturn($authCode);

		WP_Mock::wpFunction(
			'get_site_url', array(
				'args'  => 1,
				'times' => 1,
				'return' => $blogUrl
			)
		);

		WP_Mock::wpFunction(
			'get_current_blog_id', array(
				'times' => 1,
				'return' => 1
			)
		);

		$sut->expects($this->once())
			->method('display')
			->with(Adi_Synchronization_Ui_SyncToActiveDirectoryPage::TEMPLATE, array(
				'nonce' => $nonce, 
				'authCode' => $authCode, 
				'blogUrl' => $blogUrl,
				'message' => null,
				'log' => null,
			));

		$sut->renderAdmin();
	}

	/**
	 * @test
	 */
	public function loadJavaScriptAdmin_validHook_enqeueScript()
	{
		$sut = $this->sut(null);
		$hook = ADI_PREFIX . 'sync_to_ad';

		WP_Mock::wpFunction(
			'wp_enqueue_style', array(
				'args'  => array('adi2', ADI_URL . '/css/adi2.css', array(), Multisite_Ui::VERSION_CSS),
				'times' => 1,
			)
		);

		$sut->loadAdminScriptsAndStyle($hook);
	}

	/**
	 * @test
	 */
	public function loadJavaScriptAdmin_invalidHook_doNothing()
	{
		$sut = $this->sut(null);
		$hook = ADI_PREFIX . 'some_other_stuff';

		WP_Mock::wpFunction('wp_enqueue_style', array(
			'times' => 0)
		);

		$sut->loadAdminScriptsAndStyle($hook);
	}

	/**
	 * @test
	 */
	public function processData_withInvalidPost_returnEmptyArray()
	{
		$sut = $this->sut(null);

		$actual = $sut->processData(array());
		$this->assertEquals(array(), $actual);
	}

	/**
	 * @test
	 */
	public function processData_invalidNonce_callWpDie()
	{
		$sut = $this->sut(null);

		$post = array(
			'syncToAd' => '',
			'security' => 'invalid'
		);

		WP_Mock::wpFunction('wp_verify_nonce', array(
			'args'   => array('invalid', Adi_Synchronization_Ui_SyncToActiveDirectoryPage::NONCE),
			'times'  => '1',
			'return' => false)
		);

		WP_Mock::wpFunction('wp_die', array(
			'times'  => '1')
		);

		$sut->processData($post);
	}

	/**
	 * @test
	 */
	public function processData_validNonce_callSyncToAd()
	{
		$sut = $this->sut(null);

		$post = array(
			'syncToAd' => 'someData',
			'security' => "",
			'userid' => ""
		);

		WP_Mock::wpFunction(
			'wp_verify_nonce', array(
				'args'   => array($post['security'], 'Active Directory Integration Sync to AD Nonce'),
				'times'  => '1',
				'return' => true,
			)
		);

		$this->syncToActiveDirectory->expects($this->once())
			->method('synchronize')
			->with($post['userid'])
			->willReturn('someStatus');

		$expectedReturn = array(
			'status' => 'someStatus'
		);

		$returnedValue = $sut->processData($post);

		$this->assertTrue(is_array($returnedValue));
		$this->assertEquals($expectedReturn, $returnedValue);
	}
}