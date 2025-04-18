<?php

namespace Dreitier\Nadi\Synchronization\Ui;

use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Synchronization\WordPressSynchronizationService;
use Dreitier\Nadi\Ui\NadiSingleSiteConfigurationPage;
use Dreitier\Test\BasicTestCase;
use Dreitier\WordPress\Multisite\Configuration\Service;
use Dreitier\WordPress\Multisite\Ui;
use Dreitier\WordPress\Multisite\View\TwigContainer;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class SyncToWordPressPageTest extends BasicTestCase
{
	/* @var TwigContainer|MockObject */
	private $twigContainer;

	/* @var WordPressSynchronizationService |MockObject */
	private $syncToWordPress;

	/* @var Service | MockObject */
	private $configuration;

	public function setUp(): void
	{
		parent::setUp();

		$this->twigContainer = $this->createMock(TwigContainer::class);
		$this->configuration = $this->createMock(Service::class);
		$this->syncToWordPress = $this->createMock(WordPressSynchronizationService::class);
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 *
	 * @return SyncToWordPressPage| MockObject
	 */
	public function sut(array $methods = [])
	{
		return $this->getMockBuilder(SyncToWordPressPage::class)
			->setConstructorArgs(
				array(
					$this->twigContainer,
					$this->syncToWordPress,
					$this->configuration
				)
			)
			->onlyMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function getTitle()
	{
		$sut = $this->sut();
		$this->mockFunctionEsc_html__();

		$returnedTitle = $sut->getTitle();
		$this->assertEquals('Sync to WordPress', $returnedTitle);
	}

	/**
	 * @test
	 */
	public function getSlug()
	{
		$sut = $this->sut();

		$returnedValue = $sut->getSlug();
		$this->assertEquals(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . SyncToWordPressPage::SLUG, $returnedValue);
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
			'nonce' => SyncToWordPressPage::NONCE, //add nonce for security
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
			->willReturn([]);

		\WP_Mock::userFunction('wp_create_nonce', array(
				'args' => SyncToWordPressPage::NONCE,
				'times' => 1,
				'return' => SyncToWordPressPage::NONCE)
		);


		$this->configuration->expects($this->exactly(5))
			->method('getOptionValue')
			->with(...self::withConsecutive(
				[Options::SYNC_TO_WORDPRESS_AUTHCODE],
				[Options::DOMAIN_SID],
				[Options::SYNC_TO_WORDPRESS_ENABLED],
				[Options::SYNC_TO_WORDPRESS_USER],
				[Options::SYNC_TO_WORDPRESS_PASSWORD]
			))
			->willReturnOnConsecutiveCalls($authCode, $domainSid, $syncEnabled, $syncUser, $syncPass);


		\WP_Mock::userFunction('get_current_blog_id', array(
				'times' => 1,
				'return' => 1,)
		);

		\WP_Mock::userFunction('get_site_url', array(
				'args' => 1,
				'times' => 1,
				'return' => 'www.testsite.it',)
		);

		$sut->expects($this->once())
			->method('display')
			->with(SyncToWordPressPage::TEMPLATE, $paramsFilled);

		$sut->renderAdmin();
	}

	/**
	 * @test
	 */
	public function loadJavaScriptAdmin_validHook_enqueueScript()
	{
		$sut = $this->sut();
		$hook =NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . SyncToWordPressPage::SLUG;

		\WP_Mock::userFunction(
			'wp_enqueue_style', array(
				'args' => array('next_ad_int',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/css/next_ad_int.css', [], Ui::VERSION_CSS),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_style', array(
				'args' => array('next_ad_int_bootstrap_min_css',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/css/bootstrap.min.css', [], Ui::VERSION_CSS),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array('next_ad_int_bootstrap_min_js',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/libraries/bootstrap.min.js', [], Ui::VERSION_PAGE_JS),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'jquery'
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_page',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/page.js',
					array('jquery'),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'angular.min',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/libraries/angular.min.js',
					[],
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'ng-alertify',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/libraries/ng-alertify.js',
					array('angular.min'),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'ng-notify',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/libraries/ng-notify.min.js',
					array('angular.min'),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'ng-busy',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/libraries/angular-busy.min.js',
					array('angular.min'),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_shared_util_array',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/shared/utils/array.util.js',
					[],
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_shared_util_value',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/shared/utils/value.util.js',
					[],
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_app_module',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/app.module.js',
					[],
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_app_config',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/app.nadi.js',
					[],
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_shared_service_browser',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/shared/services/browser.service.js',
					[],
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_shared_service_template',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/shared/services/template.service.js',
					[],
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_shared_service_notification',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/shared/services/notification.service.js',
					[],
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_shared_service_list',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/shared/services/list.service.js',
					[],
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'selectizejs',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/libraries/selectize.min.js',
					array('jquery'),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);


		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'selectizeFix',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/libraries/fixed-angular-selectize-3.0.1.js',
					array('selectizejs', 'angular.min'),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_style', array(
				'args' => array(
					'ng-notify',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/css/ng-notify.min.css',
					[],
					Ui::VERSION_CSS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_style', array(
				'args' => array(
					'selectizecss',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/css/selectize.css',
					[],
					Ui::VERSION_CSS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_style', array(
				'args' => array(
					'alertify.min',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/css/alertify.min.css',
					[],
					Ui::VERSION_CSS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_sync_action',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/blog-options/controllers/sync-action.controller.js',
					[],
					NadiSingleSiteConfigurationPage::VERSION_BLOG_OPTIONS_JS,
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
		$sut = $this->sut();
		$hook =NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'some_stuff';

		\WP_Mock::userFunction('wp_enqueue_style', array(
				'times' => 0)
		);

		$sut->loadAdminScriptsAndStyle($hook);
	}

	/**
	 * @test
	 */
	public function processData_invalidPost_returnEmptyArray()
	{
		$sut = $this->sut();

		$post = [];

		$actual = $sut->processData($post);
		$this->assertEquals([], $actual);
	}

	/**
	 * @test
	 */
	public function processData_invalidNonce_callWpDie()
	{
		$sut = $this->sut();

		$post = array(
			'syncToWordpress' => '',
			'security' => 'invalid'
		);

		\WP_Mock::userFunction('wp_verify_nonce', array(
				'args' => array($post['security'], SyncToWordPressPage::NONCE),
				'times' => 1,
				'return' => false)
		);

		\WP_Mock::userFunction('wp_die', array(
				'times' => 1)
		);

		$sut->processData($post);
	}

	/**
	 * @test
	 */
	public function processData_validNonce_returnResult()
	{
		$sut = $this->sut();

		$post = array(
			'syncToWordpress' => '',
			'security' => 'valid'
		);

		\WP_Mock::userFunction('wp_verify_nonce', array(
				'args' => array($post['security'], SyncToWordPressPage::NONCE),
				'times' => 1,
				'return' => true)
		);

		\WP_Mock::userFunction('wp_die', array(
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
		$sut = $this->sut();

		$returnedTitle = $sut->wpAjaxSlug();
		$this->assertEquals(SyncToWordPressPage::AJAX_SLUG, $returnedTitle);
	}
}