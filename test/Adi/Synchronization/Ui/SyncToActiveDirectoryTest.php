<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_Adi_Synchronization_Ui_SyncToActiveDirectoryTest extends Ut_BasicTest
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
		$domainSid = 'domain_sid';
		$syncEnabled = 1;
		$blogUrl = 'blog_url';
		$syncUser = 'sync_user';
		$syncPass = 'syncPass';

		$sut->expects($this->once())
			->method('currentUserHasCapability')
			->willReturn(true);

		WP_Mock::wpFunction('wp_create_nonce', array(
				'args' => NextADInt_Adi_Synchronization_Ui_SyncToActiveDirectoryPage::NONCE,
				'times' => 1,
				'return' => $nonce)
		);

		$this->configuration->expects($this->exactly(5))
			->method('getOptionValue')
			->withConsecutive(
				[NextADInt_Adi_Configuration_Options::SYNC_TO_AD_AUTHCODE],
				[NextADInt_Adi_Configuration_Options::DOMAIN_SID],
				[NextADInt_Adi_Configuration_Options::SYNC_TO_AD_ENABLED],
				[NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_USER],
				[NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD]
			)
			->willReturnOnConsecutiveCalls($authCode, $domainSid, $syncEnabled, $syncUser, $syncPass);


		WP_Mock::wpFunction('get_site_url', array(
				'args' => 1,
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
					'startAction' => 'Start WordPress to Active Directory synchronization',
					'syncDisabled' => __('Check that a connection to a domain controller is established and \'Enable sync to AD\' is checked. Also, a service account has to be provided.', 'next-active-directory-integration'),
					'showLogOutput' => __('Show log output', 'next-active-directory-integration')
				),
				'domainSidSet' => 1,
				'syncEnabled' => 1,
				'syncUserSet' => 1,
				'syncPassSet' => 1
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
				'args' => array('next_ad_int', NEXT_AD_INT_URL . '/css/next_ad_int.css', array(), NextADInt_Multisite_Ui::VERSION_CSS),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_style', array(
				'args' => array('next_ad_int_bootstrap_min_css', NEXT_AD_INT_URL . '/css/bootstrap.min.css', array(), NextADInt_Multisite_Ui::VERSION_CSS),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array('next_ad_int_bootstrap_min_js', NEXT_AD_INT_URL . '/js/libraries/bootstrap.min.js', array(), NextADInt_Multisite_Ui::VERSION_PAGE_JS),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'jquery'
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_page', NEXT_AD_INT_URL . '/js/page.js',
					array('jquery'),
					NextADInt_Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'angular.min',
					NEXT_AD_INT_URL . '/js/libraries/angular.min.js',
					array(),
					NextADInt_Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'ng-alertify',
					NEXT_AD_INT_URL . '/js/libraries/ng-alertify.js',
					array('angular.min'),
					NextADInt_Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'ng-notify',
					NEXT_AD_INT_URL . '/js/libraries/ng-notify.min.js',
					array('angular.min'),
					NextADInt_Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'ng-busy',
					NEXT_AD_INT_URL . '/js/libraries/angular-busy.min.js',
					array('angular.min'),
					NextADInt_Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_shared_util_array',
					NEXT_AD_INT_URL . '/js/app/shared/utils/array.util.js',
					array(),
					NextADInt_Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_shared_util_value',
					NEXT_AD_INT_URL . '/js/app/shared/utils/value.util.js',
					array(),
					NextADInt_Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_app_module',
					NEXT_AD_INT_URL . '/js/app/app.module.js',
					array(),
					NextADInt_Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_app_config',
					NEXT_AD_INT_URL . '/js/app/app.nadi.js',
					array(),
					NextADInt_Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_shared_service_browser',
					NEXT_AD_INT_URL . '/js/app/shared/services/browser.service.js',
					array(),
					NextADInt_Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_shared_service_template',
					NEXT_AD_INT_URL . '/js/app/shared/services/template.service.js',
					array(),
					NextADInt_Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_shared_service_notification',
					NEXT_AD_INT_URL . '/js/app/shared/services/notification.service.js',
					array(),
					NextADInt_Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_shared_service_list',
					NEXT_AD_INT_URL . '/js/app/shared/services/list.service.js',
					array(),
					NextADInt_Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'selectizejs',
					NEXT_AD_INT_URL . '/js/libraries/selectize.min.js',
					array('jquery'),
					NextADInt_Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);


		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'selectizeFix',
					NEXT_AD_INT_URL . '/js/libraries/fixed-angular-selectize-3.0.1.js',
					array('selectizejs', 'angular.min'),
					NextADInt_Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_style', array(
				'args' => array(
					'ng-notify',
					NEXT_AD_INT_URL . '/css/ng-notify.min.css',
					array(),
					NextADInt_Multisite_Ui::VERSION_CSS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_style', array(
				'args' => array(
					'selectizecss',
					NEXT_AD_INT_URL . '/css/selectize.css',
					array(),
					NextADInt_Multisite_Ui::VERSION_CSS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_style', array(
				'args' => array(
					'alertify.min',
					NEXT_AD_INT_URL . '/css/alertify.min.css',
					array(),
					NextADInt_Multisite_Ui::VERSION_CSS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_sync_action',
					NEXT_AD_INT_URL . '/js/app/blog-options/controllers/sync-action.controller.js',
					array(),
					NextADInt_Multisite_Ui_BlogConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
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
				'args' => array('invalid', NextADInt_Adi_Synchronization_Ui_SyncToActiveDirectoryPage::NONCE),
				'times' => '1',
				'return' => false)
		);

		WP_Mock::wpFunction('wp_die', array(
				'times' => '1')
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
				'args' => array($post['security'], 'Active Directory Integration Sync to AD Nonce'),
				'times' => '1',
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