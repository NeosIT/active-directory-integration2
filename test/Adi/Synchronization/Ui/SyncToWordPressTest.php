<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_Adi_Synchronization_Ui_SyncToWordPressTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_View_TwigContainer|PHPUnit_Framework_MockObject_MockObject */
	private $twigContainer;

	/* @var NextADInt_Adi_Synchronization_WordPress |PHPUnit_Framework_MockObject_MockObject */
	private $syncToWordPress;

	/* @var NextADInt_Multisite_Configuration_Service | PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	public function setUp(): void
	{
		parent::setUp();

		$this->twigContainer = $this->createMock('NextADInt_Multisite_View_TwigContainer');
		$this->configuration = $this->createMock('NextADInt_Multisite_Configuration_Service');
		$this->syncToWordPress = $this->createMock('NextADInt_Adi_Synchronization_WordPress');
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 *
	 * @return NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage| PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage')
			->setConstructorArgs(
				array(
					$this->twigContainer,
					$this->syncToWordPress,
					$this->configuration
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function getTitle()
	{
		$sut = $this->sut(null);
		$this->mockFunctionEsc_html__();

		$returnedTitle = $sut->getTitle();
		$this->assertEquals('Sync to WordPress', $returnedTitle);
	}

	/**
	 * @test
	 */
	public function getSlug()
	{
		$sut = $this->sut(null);

		$returnedValue = $sut->getSlug();
		$this->assertEquals(NEXT_AD_INT_PREFIX . NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage::SLUG, $returnedValue);
	}


	/**
	 * @test
	 */
	public function renderAdmin_validCapability_delegateToMethod()
	{
		$sut = $this->sut(array('checkCapability', 'processData', 'display'));
		$this->mockFunction__();
		$this->mockFunctionEsc_html__();

		$authCode = 'auth_code';
		$domainSid = 'domain_sid';
		$syncEnabled = 1;
		$syncUser = 'sync_user';
		$syncPass = 'syncPass';

		$paramsFilled = array(
			'nonce' => NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage::NONCE, //add nonce for security
			'authCode' => 'auth_code',
			'blogUrl' => 'www.testsite.it',
			'message' => null,
			'log' => null,
			'i18n' => array(
				'title' => 'Sync To WordPress',
				'descriptionLine1' => 'If you want to trigger Sync to WordPress, you must know the URL to the index.php of your blog:',
				'descriptionLine2' => 'Settings like auth-code etc. depends on the current blog. So be careful which blog you are using. Here are some examples:',
				'repeatAction' => 'Repeat AD to WordPress synchronization',
				'startAction' => 'Start AD to WordPress synchronization',
				'syncDisabled' => 'Check that a connection to a domain controller is established and \'Enable sync to WordPress\' is checked. Also, a service account has to be provided.',
				'showLogOutput' => __('Show log output', 'next-active-directory-integration')
			),
			'domainSidSet' => 1,
			'syncEnabled' => 1,
			'syncUserSet' => 1,
			'syncPassSet' => 1
		);

		$sut->expects($this->once())
			->method('processData')
			->willReturn(array());

		WP_Mock::wpFunction('wp_create_nonce', array(
				'args' => NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage::NONCE,
				'times' => 1,
				'return' => NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage::NONCE)
		);


		$this->configuration->expects($this->exactly(5))
			->method('getOptionValue')
			->withConsecutive(
				[NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_AUTHCODE],
				[NextADInt_Adi_Configuration_Options::DOMAIN_SID],
				[NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_ENABLED],
				[NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_USER],
				[NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_PASSWORD]
			)
			->willReturnOnConsecutiveCalls($authCode, $domainSid, $syncEnabled, $syncUser, $syncPass);


		WP_Mock::wpFunction('get_current_blog_id', array(
				'times' => 1,
				'return' => 1,)
		);

		WP_Mock::wpFunction('get_site_url', array(
				'args' => 1,
				'times' => 1,
				'return' => 'www.testsite.it',)
		);

		$sut->expects($this->once())
			->method('display')
			->with(NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage::TEMPLATE, $paramsFilled);

		$sut->renderAdmin();
	}

	/**
	 * @test
	 */
	public function loadJavaScriptAdmin_validHook_enqueueScript()
	{
		$sut = $this->sut(null);
		$hook = NEXT_AD_INT_PREFIX . NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage::SLUG;

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
		$hook = NEXT_AD_INT_PREFIX . 'some_stuff';

		WP_Mock::wpFunction('wp_enqueue_style', array(
				'times' => 0)
		);

		$sut->loadAdminScriptsAndStyle($hook);
	}

	/**
	 * @test
	 */
	public function processData_invalidPost_returnEmptyArray()
	{
		$sut = $this->sut(null);

		$post = array();

		$actual = $sut->processData($post);
		$this->assertEquals(array(), $actual);
	}

	/**
	 * @test
	 */
	public function processData_invalidNonce_callWpDie()
	{
		$sut = $this->sut(null);

		$post = array(
			'syncToWordpress' => '',
			'security' => 'invalid'
		);

		WP_Mock::wpFunction('wp_verify_nonce', array(
				'args' => array($post['security'], NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage::NONCE),
				'times' => 1,
				'return' => false)
		);

		WP_Mock::wpFunction('wp_die', array(
				'times' => 1)
		);

		$sut->processData($post);
	}

	/**
	 * @test
	 */
	public function processData_validNonce_returnResult()
	{
		$sut = $this->sut(null);

		$post = array(
			'syncToWordpress' => '',
			'security' => 'valid'
		);

		WP_Mock::wpFunction('wp_verify_nonce', array(
				'args' => array($post['security'], NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage::NONCE),
				'times' => 1,
				'return' => true)
		);

		WP_Mock::wpFunction('wp_die', array(
				'times' => 0,)
		);

		$this->syncToWordPress->expects($this->once())
			->method('synchronize')
			->willReturn('bulkData');

		$expected = array(
			'status' => 'bulkData'
		);

		$actual = $sut->processData($post);
		$this->assertTrue(is_array($actual));
		$this->assertEquals($expected, $actual);

	}

	/**
	 * @test
	 */
	public function wpAjaxSlug_getAjaxSlug_returnAjaxSlug()
	{
		$sut = $this->sut(null);

		$returnedTitle = $sut->wpAjaxSlug();
		$this->assertEquals(NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage::AJAX_SLUG, $returnedTitle);
	}
}