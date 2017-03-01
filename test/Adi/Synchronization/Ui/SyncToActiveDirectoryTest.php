<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_Adi_Synchronization_Ui_SyncToActiveDirectoryTest  extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_View_TwigContainer | PHPUnit_Framework_MockObject_MockObject */
	private $twigContainer;

	/* @var NextADInt_Multisite_Configuration_Service | PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/* @var NextADInt_Adi_Synchronization_ActiveDirectory | PHPUnit_Framework_MockObject_MockObject */
	private $syncToActiveDirectory;

	public function setUp()
	{
		parent::setUp();

		$this->configuration = $this->createMock('NextADInt_Multisite_Configuration_Service');
		$this->twigContainer = $this->createMock('NextADInt_Multisite_View_TwigContainer');
		$this->syncToActiveDirectory = $this->createMock('NextADInt_Adi_Synchronization_ActiveDirectory');
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 *
	 * @return NextADInt_Adi_Synchronization_Ui_SyncToActiveDirectoryPage | PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Adi_Synchronization_Ui_SyncToActiveDirectoryPage')
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
		$this->mockFunctionEsc_html__();

		$returnedTitle = $sut->getTitle();
		$this->assertEquals('Sync to AD', $returnedTitle);
	}

	/**
	 * @test
	 */
	public function getSlug_concat_returnSlug()
	{
		$sut = $this->sut(null);

		$returnedValue = $sut->getSlug();
		$this->assertEquals(NEXT_AD_INT_PREFIX . NextADInt_Adi_Synchronization_Ui_SyncToActiveDirectoryPage::SLUG, $returnedValue);
	}

	/**
	 * @test
	 */
	public function wpAjaxSlug_findSlug_returnNull()
	{
		$sut = $this->sut(null);

		$returnedValue = $sut->wpAjaxSlug();
		$this->assertEquals(NextADInt_Adi_Synchronization_Ui_SyncToActiveDirectoryPage::AJAX_SLUG, $returnedValue);
	}

	/**
	 * @test
	 */
	public function getCapability_getValueFromConstant_returnCapability()
	{
		$sut = $this->sut(null);

		$returnedValue = $this->invokeMethod($sut, 'getCapability', array());
		$this->assertEquals(NextADInt_Adi_Synchronization_Ui_SyncToActiveDirectoryPage::CAPABILITY, $returnedValue);
	}

	/**
	 * @test
	 */
	public function renderAdmin_withCorrectCapability_delegateToMethod()
	{
		$sut = $this->sut(array('display', 'currentUserHasCapability'));
		$this->mockFunction__();

		$nonce = 'some_nonce';
		$authCode = 'auth_code';
		$blogUrl = 'blog_url';

		$sut->expects($this->once())
			->method('currentUserHasCapability')
			->willReturn(true);

		WP_Mock::wpFunction('wp_create_nonce', array(
            'args'  => NextADInt_Adi_Synchronization_Ui_SyncToActiveDirectoryPage::NONCE,
            'times' => 1,
            'return' => $nonce)
		);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::SYNC_TO_AD_AUTHCODE)
			->willReturn($authCode);

		WP_Mock::wpFunction('get_site_url', array(
            'args'  => 1,
            'times' => 1,
            'return' => $blogUrl)
		);

		WP_Mock::wpFunction('get_current_blog_id', array(
            'times' => 1,
            'return' => 1)
		);

		$sut->expects($this->once())
			->method('display')
			->with(NextADInt_Adi_Synchronization_Ui_SyncToActiveDirectoryPage::TEMPLATE, array(
				'nonce' => $nonce, 
				'authCode' => $authCode, 
				'blogUrl' => $blogUrl,
				'message' => null,
				'log' => null,
                'i18n' => array(
                    'title' => 'Sync To Active Directory',
                    'descriptionLine1' => 'If you want to trigger Sync to Active Directory, you must know the URL to the index.php of your blog:',
                    'descriptionLine2' => 'Settings like auth-code etc. depends on the current blog. So be careful which blog you are using. Here are some examples:',
                    'userId' => 'User-ID: (optional)',
                    'repeatAction' => 'Repeat WordPress to Active Directory synchronization',
                    'startAction' => 'Start WordPress to Active Directory synchronization'
                )
			));

		$sut->renderAdmin();
	}

	/**
	 * @test
	 */
	public function loadJavaScriptAdmin_validHook_enqeueScript()
	{
		$sut = $this->sut(null);
		$hook = NEXT_AD_INT_PREFIX . 'sync_to_ad';

		WP_Mock::wpFunction(
			'wp_enqueue_style', array(
				'args'  => array('next_ad_int', NEXT_AD_INT_URL . '/css/next_ad_int.css', array(), NextADInt_Multisite_Ui::VERSION_CSS),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_style', array(
				'args'  => array('next_ad_int_bootstrap_min_css', NEXT_AD_INT_URL . '/css/bootstrap.min.css', array(), NextADInt_Multisite_Ui::VERSION_CSS),
				'times' => 1,
			)
		);

        WP_Mock::wpFunction(
            'wp_enqueue_script', array(
                'args'  => array('next_ad_int_bootstrap_min_js', NEXT_AD_INT_URL . '/js/libraries/bootstrap.min.js', array(), NextADInt_Multisite_Ui::VERSION_PAGE_JS),
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
		$hook = NEXT_AD_INT_PREFIX . 'some_other_stuff';

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

		$this->mockFunction__();


		WP_Mock::wpFunction('wp_verify_nonce', array(
			'args'   => array('invalid', NextADInt_Adi_Synchronization_Ui_SyncToActiveDirectoryPage::NONCE),
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