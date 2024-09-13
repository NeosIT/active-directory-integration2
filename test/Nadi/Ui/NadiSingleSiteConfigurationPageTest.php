<?php

namespace Dreitier\Nadi\Ui;

use Dreitier\ActiveDirectory\Sid;
use Dreitier\Ldap\Attribute\Description;
use Dreitier\Ldap\Attribute\Repository;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Role\Manager;
use Dreitier\Nadi\Ui\Validator\Rule\AccountSuffix;
use Dreitier\Nadi\Ui\Validator\Rule\AdAttributeConflict;
use Dreitier\Nadi\Ui\Validator\Rule\AdminEmail;
use Dreitier\Nadi\Ui\Validator\Rule\AttributeMappingNull;
use Dreitier\Nadi\Ui\Validator\Rule\BaseDn;
use Dreitier\Nadi\Ui\Validator\Rule\BaseDnWarn;
use Dreitier\Nadi\Ui\Validator\Rule\DefaultEmailDomain;
use Dreitier\Nadi\Ui\Validator\Rule\DisallowInvalidWordPressRoles;
use Dreitier\Nadi\Ui\Validator\Rule\NoDefaultAttributeName;
use Dreitier\Nadi\Ui\Validator\Rule\Port;
use Dreitier\Nadi\Ui\Validator\Rule\SelectValueValid;
use Dreitier\Nadi\Ui\Validator\Rule\WordPressMetakeyConflict;
use Dreitier\Test\BasicTest;
use Dreitier\Util\Message\Type;
use Dreitier\Util\Validator\Result;
use Dreitier\Util\Validator\Rule\Conditional;
use Dreitier\Util\Validator\Rule\NotEmptyOrWhitespace;
use Dreitier\Util\Validator\Rule\PositiveNumericOrZero;
use Dreitier\Util\Validator\Validator;
use Dreitier\WordPress\Multisite\Configuration\Service;
use Dreitier\WordPress\Multisite\Ui;
use Dreitier\WordPress\Multisite\Ui\BlogConfigurationController;
use Dreitier\WordPress\Multisite\View\TwigContainer;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class NadiSingleSiteConfigurationPageTest extends BasicTest
{
	/* @var TwigContainer|MockObject */
	private $twigContainer;

	/* @var BlogConfigurationController|MockObject */
	private $blogConfigurationController;

	public function setUp(): void
	{
		parent::setUp();

		$this->twigContainer = $this->createMock(TwigContainer::class);
		$this->blogConfigurationController = $this->createMock(BlogConfigurationController::class);
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function getTitle()
	{
		$sut = $this->sut(null);
		$this->mockFunctionEsc_html__();

		$expectedTitle = 'Configuration';
		$returnedTitle = $sut->getTitle();
		$this->assertEquals($expectedTitle, $returnedTitle);
	}

	/**
	 *
	 * @return NadiSingleSiteConfigurationPage|MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder(NadiSingleSiteConfigurationPage::class)
			->setConstructorArgs(
				array(
					$this->twigContainer,
					$this->blogConfigurationController,
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function getSlug()
	{
		$sut = $this->sut(null);

		$expectedReturn =NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'blog_options';
		$returnedValue = $sut->getSlug();

		$this->assertEquals($expectedReturn, $returnedValue);
	}

	/**
	 * @test
	 */
	public function wpAjaxSlug()
	{
		$sut = $this->sut(null);

		$expectedReturn =NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'blog_options';
		$returnedValue = $sut->wpAjaxSlug();

		$this->assertEquals($expectedReturn, $returnedValue);
	}

	/**
	 * @test
	 */
	public function renderAdmin()
	{
		$sut = $this->sut(array('display'));
		$this->mockFunction__();

		$nonce = 'some_nonce';
		$i18n = array(
			'title' => 'Next Active Directory Integration Blog Configuration',
			'regenerateAuthCode' => 'Regenerate Auth Code',
			'securityGroup' => 'Security group',
			'wordpressRole' => 'WordPress role',
			'selectRole' => 'Please select a role',
			'verify' => 'Verify',
			'adAttributes' => 'AD Attributes',
			'dataType' => 'Data Type',
			'wordpressAttribute' => 'WordPress Attribute',
			'description' => 'Description',
			'viewInUserProfile' => 'View in User Profile',
			'syncToAd' => 'Sync to AD',
			'overwriteWithEmptyValue' => 'Overwrite with empty value',
			'wantToRegenerateAuthCode' => 'Do you really want to regenerate a new AuthCode?',
			'wordPressIsConnectedToDomain' => 'WordPress Site is currently connected to Domain: ',
			'domainConnectionVerificationSuccessful' => 'Verification successful! WordPress site is now connected to Domain: ',
			'verificationSuccessful' => 'Verification successful!',
			'domainConnectionVerificationFailed' => 'Verification failed! Please check your logfile for further information.',
			'managePermissions' => 'Manage Permissions',
			'noOptionsExists' => 'No options exists',
			'pleaseWait' => 'Please wait...',
			'save' => 'Save',
			'haveToVerifyDomainConnection' => 'You have to verify the connection to the AD before saving.',
			'errorWhileSaving' => 'An error occurred while saving the configuration.',
			'savingSuccessful' => 'The configuration has been saved successfully.'
		);

		\WP_Mock::userFunction('wp_create_nonce', array(
				'args' => NadiSingleSiteConfigurationPage::NONCE,
				'times' => 1,
				'return' => $nonce,)
		);

		$sut->expects($this->once())
			->method('display')
			->with(NadiSingleSiteConfigurationPage::TEMPLATE, array('nonce' => $nonce, 'i18n' => $i18n));

		$sut->renderAdmin();
	}

	/**
	 * @test
	 */
	public function loadAdminScriptsAndStyle()
	{
		$sut = $this->sut(null);
		$hook =NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'blog_options';

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
					array(),
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
					array(),
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
					array(),
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
					array(),
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
					array(),
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
					array(),
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
					array(),
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
					array(),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_service_persistence',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/blog-options/services/persistence.service.js',
					array(),
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
					array(),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_service_data',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/blog-options/services/data.service.js',
					array(),
					NadiSingleSiteConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);

		// add the controller js files
		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_blog',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/blog-options/controllers/blog.controller.js',
					array(),
					NadiSingleSiteConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_ajax',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/blog-options/controllers/ajax.controller.js',
					array(),
					NadiSingleSiteConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_general',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/blog-options/controllers/general.controller.js',
					array(),
					NadiSingleSiteConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_environment',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/blog-options/controllers/environment.controller.js',
					array(),
					NadiSingleSiteConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_user',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/blog-options/controllers/user.controller.js',
					array(),
					NadiSingleSiteConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_password',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/blog-options/controllers/credential.controller.js',
					array(),
					NadiSingleSiteConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_permission',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/blog-options/controllers/permission.controller.js',
					array(),
					NadiSingleSiteConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_security',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/blog-options/controllers/security.controller.js',
					array(),
					NadiSingleSiteConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_sso',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/blog-options/controllers/sso.controller.js',
					array(),
					NadiSingleSiteConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_attributes',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/blog-options/controllers/attributes.controller.js',
					array(),
					NadiSingleSiteConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_sync_to_ad',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/blog-options/controllers/sync-to-ad.controller.js',
					array(),
					NadiSingleSiteConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_sync_to_wordpress',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/blog-options/controllers/sync-to-wordpress.controller.js',
					array(),
					NadiSingleSiteConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_logging',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/app/blog-options/controllers/logging.controller.js',
					array(),
					NadiSingleSiteConfigurationPage::VERSION_BLOG_OPTIONS_JS,
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
				'args' => array('next_ad_int',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/css/next_ad_int.css', array(), Ui::VERSION_CSS),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_style', array(
				'args' => array(
					'ng-notify',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/css/ng-notify.min.css',
					array(),
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
					array(),
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
					array(),
					Ui::VERSION_CSS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_bootstrap_min_js',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/libraries/bootstrap.min.js',
					array(),
					Ui::VERSION_CSS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_style', array(
				'args' => array(
					'next_ad_int_bootstrap_min_css',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/css/bootstrap.min.css',
					array(),
					Ui::VERSION_CSS,
				),
				'times' => 1,
			)
		);


		$sut->loadAdminScriptsAndStyle($hook);
	}

	/**
	 * @test
	 */
	public function wpAjaxListener_withEscapedCharacter_unescapeTheseCharacter()
	{
		$sut = $this->sut(array('renderJson', 'routeRequest', 'currentUserHasCapability'));

		$_POST['data'] = array(
			"something" => array(
				"option_value" => "something\'s special",   // WordPress auto escape character like '
				"option_permission" => 3,
			),
		);

		$expected = array(
			"something" => array(
				"option_value" => "something's special",
				"option_permission" => 3,
			),
		);

		$sut->expects($this->once())
			->method('currentUserHasCapability')
			->willReturn(true);

		\WP_Mock::userFunction('check_ajax_referer', array(
			'args' => array('Active Directory Integration Configuration Nonce', 'security', true),
			'times' => 1,
		));

		$sut->expects($this->once())
			->method('routeRequest')
			->willReturn($expected);

		$sut->expects($this->once())
			->method('renderJson')
			->with($expected);

		$sut->wpAjaxListener();
	}

	/**
	 * @test
	 */
	public function wpAjaxListener()
	{
		$sut = $this->sut(array('renderJson', 'routeRequest', 'currentUserHasCapability'));

		$_POST['data'] = array(
			"something" => array(
				"option_value" => "something",
				"option_permission" => 3,
			),
		);

		$expected = array(
			"something" => array(
				"option_value" => "something",
				"option_permission" => 3,
			),
		);

		$sut->expects($this->once())
			->method('currentUserHasCapability')
			->willReturn(true);

		\WP_Mock::userFunction('check_ajax_referer', array(
			'args' => array('Active Directory Integration Configuration Nonce', 'security', true),
			'times' => 1,
		));

		$sut->expects($this->once())
			->method('routeRequest')
			->willReturn($expected);

		$sut->expects($this->once())
			->method('renderJson')
			->with($expected);

		$sut->wpAjaxListener();
	}

	/**
	 * @test
	 */
	public function wpAjaxListener_EmptyData()
	{
		$sut = $this->sut(null);
		$_POST['data'] = '';

		$this->mockWordpressFunction('current_user_can');

		\WP_Mock::userFunction(
			'check_ajax_referer', array(
				'args' => array('Active Directory Integration Configuration Nonce', 'security', true),
				'times' => 1,
			)
		);

		$sut->wpAjaxListener();
	}

	/**
	 * @test
	 */
	public function wpAjaxListener_NoPermission()
	{
		$sut = $this->sut(null);
		$_POST['data'] = 'something';

		\WP_Mock::userFunction(
			'check_ajax_referer', array(
				'args' => array('Active Directory Integration Configuration Nonce', 'security', true),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'current_user_can', array(
				'args' => 'manage_options',
				'times' => 1,
				'return' => false,
			)
		);

		$sut->wpAjaxListener();
	}

	/**
	 * @test
	 */
	public function routeRequest_withoutExistingMapping_returnsFalse()
	{
		$sut = $this->sut();

		$actual = $this->invokeMethod($sut, 'routeRequest', array('test', array()));

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function routeRequest_withExistingMapping_triggersMethod()
	{
		$sut = $this->sut(array(NadiSingleSiteConfigurationPage::SUB_ACTION_GENERATE_AUTHCODE));

		$sut->expects($this->once())
			->method(NadiSingleSiteConfigurationPage::SUB_ACTION_GENERATE_AUTHCODE)
			->with(array());

		$subAction = NadiSingleSiteConfigurationPage::SUB_ACTION_GENERATE_AUTHCODE;

		$this->invokeMethod($sut, 'routeRequest', array($subAction, array()));
	}

	/**
	 * @test
	 */
	public function getAllOptionValues_withPermissionLowerThanBlogAdmin_removesValuesFromResult()
	{
		$sut = $this->sut();

		$this->mockWordpressFunction('is_multisite');

		$data = array(
			'domain_controllers' => array(
				'option_value' => 'test',
				'option_permission' => Service::EDITABLE
			),
			'port' => array(
				'option_value' => 'test',
				'option_permission' => Service::REPLACE_OPTION_WITH_DEFAULT_TEXT
			),
		);

		$expected = array(
			'domain_controllers' => array(
				'option_value' => 'test',
				'option_permission' => Service::EDITABLE
			),
			'port' => array(
				'option_value' => '',
				'option_permission' => Service::REPLACE_OPTION_WITH_DEFAULT_TEXT
			),
		);

		$this->twigContainer->expects($this->once())
			->method('getAllOptionsValues')
			->willReturn($data);

		$result = $this->invokeMethod($sut, 'getAllOptionsValues');

		$this->assertEquals(array(
			'options' => $expected,
			'ldapAttributes' => Description::findAll(),
			'dataTypes' => Repository::findAllAttributeTypes(),
			'wpRoles' => Manager::getRoles(),
		), $result);
	}

	/**
	 * @test
	 */
	public function generateNewAuthCode_returnsNewAuthCode()
	{
		$sut = $this->sut();

		$this->mockWordpressFunction('wp_generate_password', array('times' => 1, 'return' => 'abc123'));

		$result = $this->invokeMethod($sut, 'generateNewAuthCode');

		$this->assertNotEmpty($result);
	}

	/**
	 * @test
	 */
	public function persistOptionsValues_validatesData()
	{
		$sut = $this->sut(array('validate'));
		$expected = array("status_success" => true);

		$this->twigContainer->expects($this->once())
			->method('getAllOptionsValues')
			->willReturn(array('test' => array('option_permission' => 3)));

		$this->blogConfigurationController->expects($this->once())
			->method('saveBlogOptions')
			->willReturn(array("status_success" => true));

		$sut->expects($this->once())
			->method('validate')
			->willReturn(new Result());

		$actual = $this->invokeMethod($sut, 'persistOptionsValues', array(array('data' => array('test' => 'test'))));

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function persistOptionsValues_withInsufficientPermission_removesDataBeforeSave()
	{
		$sut = $this->sut(array('validate'));

		$this->twigContainer->expects($this->once())
			->method('getAllOptionsValues')
			->willReturn(array('test' => array('option_permission' => 1)));

		$this->blogConfigurationController->expects($this->once())
			->method('saveBlogOptions')
			->with(array())
			->willReturn(array("status_success" => true));

		$sut->expects($this->once())
			->method('validate')
			->willReturn(new Result());

		$this->invokeMethod($sut, 'persistOptionsValues', array(array('data' => array('test' => 'test'))));
	}

	/**
	 * @test
	 */
	public function validate_withoutValidationErrors_rendersErrors()
	{
		$sut = $this->sut(array('getValidator', 'validateWithValidator'));

		$data = array(array('options' => array()));

		$validator = $this->createMock(Validator::class);
		$sut->expects($this->once())
			->method('getValidator')
			->willReturn($validator);

		$validationResult = $this->createMock(Result::class);
		$sut->expects($this->once())
			->method('validateWithValidator')
			->with($validator, $data)
			->willReturn($validationResult);

		$this->invokeMethod($sut, 'validate', array($data));
	}

	/**
	 * @test
	 */
	public function getValidator_hasRequiredValidations()
	{
		$sut = $this->sut();

		$validator = $sut->getValidator();
		$rules = $validator->getValidationRules();

		$this->assertCount(15, $rules);
		$this->assertInstanceOf(Conditional::class, $rules[Options::SYNC_TO_WORDPRESS_USER][0]);
		$this->assertInstanceOf(Conditional::class, $rules[Options::SYNC_TO_AD_GLOBAL_USER][0]);
		$this->assertInstanceOf(AccountSuffix::class, $rules[Options::ACCOUNT_SUFFIX][0]);
		$this->assertInstanceOf(NoDefaultAttributeName::class, $rules[Options::ADDITIONAL_USER_ATTRIBUTES][0]);
		$this->assertInstanceOf(AttributeMappingNull::class, $rules[Options::ADDITIONAL_USER_ATTRIBUTES][1]);
		$this->assertInstanceOf(WordPressMetakeyConflict::class, $rules[Options::ADDITIONAL_USER_ATTRIBUTES][2]);
		$this->assertInstanceOf(AdAttributeConflict::class, $rules[Options::ADDITIONAL_USER_ATTRIBUTES][3]);
		$this->assertInstanceOf(DefaultEmailDomain::class, $rules[Options::DEFAULT_EMAIL_DOMAIN][0]);
		$this->assertInstanceOf(Port::class, $rules[Options::PORT][0]);
		$this->assertInstanceOf(PositiveNumericOrZero::class, $rules[Options::NETWORK_TIMEOUT][0]);
		$this->assertInstanceOf(NotEmptyOrWhitespace::class, $rules[Options::PROFILE_NAME][0]);
		$this->assertInstanceOf(DisallowInvalidWordPressRoles::class, $rules[Options::ROLE_EQUIVALENT_GROUPS][0]);
		$this->assertInstanceOf(SelectValueValid::class, $rules[Options::ENCRYPTION][0]);
		$this->assertInstanceOf(Conditional::class, $rules[Options::SSO_USER][0]);
		$this->assertInstanceOf(Conditional::class, $rules[Options::SSO_PASSWORD][0]);
		$this->assertInstanceOf(BaseDn::class, $rules[Options::BASE_DN][0]);
		$this->assertInstanceOf(BaseDnWarn::class, $rules[Options::BASE_DN][1]);
	}

	/**
	 * @test
	 */
	public function persistDomainSid_itSavesBlogOptions()
	{
		$sut = $this->sut();
		$data = array();

		$this->blogConfigurationController->expects($this->once())
			->method('saveBlogOptions')
			->with($data)
			->willReturn(true);

		$actual = $sut->persistDomainSid($data);
		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function verifyAdConnection_withValidData_returnsSuccess()
	{
		$data = array('option' => 'someValue');
		$nestedData = array('data' => $data);

		$sut = $this->sut(array('validateVerification', 'verifyInternal'));

		$validation = $this->createMock(Result::class);
		$sut->expects($this->once())
			->method('validateVerification')
			->with($data)
			->willReturn($validation);

		$validation->expects($this->once())
			->method('containsErrors')
			->willReturn(false);

		$sut->expects($this->once())
			->method('verifyInternal')
			->with($data)
			->willReturn(array('status_success' => 1234));

		$validation->expects($this->once())
			->method('getValidationResult')
			->willReturn(array());

		$actual = $this->invokeMethod($sut, 'verifyAdConnection', array($nestedData));
		$this->assertEquals($actual, array('status_success' => 1234));
	}

	/**
	 * @test
	 */
	public function verifyAdConnection_withInvalidData_returnsErrors()
	{
		$data = array('option' => 'someValue');
		$nestedData = array('data' => $data);

		$sut = $this->sut(array('validateVerification', 'verifyInternal'));

		$validation = $this->createMock(Result::class);
		$sut->expects($this->once())
			->method('validateVerification')
			->with($data)
			->willReturn($validation);

		$validation->expects($this->once())
			->method('containsErrors')
			->willReturn(true);

		$sut->expects($this->never())
			->method('verifyInternal');

		$validation->expects($this->once())
			->method('getValidationResult')
			->willReturn(array('option' => array(Type::ERROR => 'Some error message')));

		$actual = $this->invokeMethod($sut, 'verifyAdConnection', array($nestedData));
		$this->assertEquals($actual, array('option' => array(Type::ERROR => 'Some error message')));
	}

	/**
	 * @test
	 */
	public function verifyInternal_withValidData_returnsSuccess()
	{
		$data = array('option' => 'someValue');
		$profileId = 1;

		$expectedObjectSid = Sid::of('S-1-5-21-3623811015-3361044348-30300820-1013');
		$expectedSid = 'S-1-5-21-3623811015-3361044348-30300820';
		$expectedNetBiosName = 'TEST';
		$expectedNetBiosData = array("netbios_name" => $expectedNetBiosName);

		$sut = $this->sut(array('prepareDomainSid', 'persistDomainSid', 'prepareNetBiosName', 'persistNetBiosName', 'findActiveDirectoryNetBiosName'));

		$this->twigContainer->expects($this->once())
			->method('findActiveDirectoryDomainSid')
			->with($data)
			->willReturn($expectedObjectSid);

		$this->twigContainer->expects($this->once())
			->method('findActiveDirectoryNetBiosName')
			->with($data)
			->willReturn($expectedNetBiosName);

		$sut->expects($this->once())
			->method('prepareNetBiosName')
			->with($expectedNetBiosName)
			->willReturn($expectedNetBiosData);

		$sut->expects($this->once())
			->method('persistNetBiosName')
			->with($expectedNetBiosData, $profileId);

		$sut->expects($this->once())
			->method('prepareDomainSid')
			->with($expectedSid)
			->willReturn(array('sid' => $expectedSid));

		$sut->expects($this->once())
			->method('persistDomainSid')
			->with(array('sid' => $expectedSid), $profileId);

		$actual = $this->invokeMethod($sut, 'verifyInternal', array($data, $profileId));
		$this->assertEquals($actual, array('verification_successful_sid' => $expectedSid, 'verification_successful_netbios' => $expectedNetBiosName));
	}

	/**
	 * @test
	 */
	public function verifyInternal_cantFindDomainSid_returnsError()
	{
		$data = array('option' => 'someValue');

		$sut = $this->sut();

		$this->twigContainer->expects($this->once())
			->method('findActiveDirectoryDomainSid')
			->with($data)
			->willReturn(false);

		$actual = $this->invokeMethod($sut, 'verifyInternal', array($data));
		$this->assertEquals($actual, array("verification_error" => "Verification failed! Please check your logfile for further information."));
	}

	/**
	 * @test
	 */
	public function prepareDomainSid_withValidDomainSid_returnsSuccess()
	{
		$domainSid = 'S-1-22-3-4-4567890-12345678';
		$sut = $this->sut(array('getDomainSidForPersistence'));

		$sut->expects($this->once())
			->method('getDomainSidForPersistence')
			->with($domainSid)
			->willReturn(true);

		$actual = $this->invokeMethod($sut, 'prepareDomainSid', array($domainSid));
		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function prepareDomainSid_withInvalidDomainSid_returnsError()
	{
		$domainSid = null;
		$sut = $this->sut(array('getDomainSidForPersistence'));

		$sut->expects($this->never())
			->method('getDomainSidForPersistence');

		$actual = $this->invokeMethod($sut, 'prepareDomainSid', array($domainSid));
		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function getDomainSidForPersistence_returnsValidArray()
	{
		$domainSid = 'S-1-23-4-567890-123456';

		$sut = $this->sut();

		$actual = $this->invokeMethod($sut, 'getDomainSidForPersistence', array($domainSid));
		$this->assertEquals($actual, array("domain_sid" => $domainSid));
	}

	/**
	 * @test
	 */
	public function getVerificationValidator_hasRequiredValidators()
	{
		$sut = $this->sut();

		$validator = $sut->getVerificationValidator();
		$rules = $validator->getValidationRules();

		$this->assertCount(6, $rules);
		$this->assertInstanceOf(Port::class, $rules[Options::PORT][0]);
		$this->assertInstanceOf(PositiveNumericOrZero::class, $rules[Options::NETWORK_TIMEOUT][0]);
		$this->assertInstanceOf(AdminEmail::class, $rules[Options::VERIFICATION_USERNAME][0]);
		$this->assertInstanceOf(NotEmptyOrWhitespace::class, $rules[Options::VERIFICATION_USERNAME][1]);
		$this->assertInstanceOf(NotEmptyOrWhitespace::class, $rules[Options::VERIFICATION_PASSWORD][0]);
		$this->assertInstanceOf(NotEmptyOrWhitespace::class, $rules[Options::DOMAIN_CONTROLLERS][0]);
	}

	/**
	 * @test
	 */
	public function addBaseDnValidators_hasRequiredValidators()
	{
		$sut = $this->sut();

		$validator = new Validator();
		$this->invokeMethod($sut, 'addBaseDnValidators', array($validator));
		$rules = $validator->getValidationRules();

		$this->assertCount(2, $rules['base_dn']);
		$this->assertInstanceOf(BaseDn::class, $rules[Options::BASE_DN][0]);
		$this->assertInstanceOf(BaseDnWarn::class, $rules[Options::BASE_DN][1]);
	}

	/**
	 * @test
	 */
	public function prepareNetBiosName_validName_returnsNetBiosName()
	{
		$sut = $this->sut(array('getNetBiosNameForPersistence'));

		$netBiosName = 'TEST';
		$expectedNetBiosData = array("netBIOS_name" => $netBiosName);

		$sut->expects($this->once())
			->method('getNetBiosNameForPersistence')
			->with($netBiosName)
			->willReturn($expectedNetBiosData);

		$actual = $this->invokeMethod($sut, 'prepareNetBiosName', array($netBiosName));

		$this->assertEquals($actual, $expectedNetBiosData);
	}

	/**
	 * @test
	 */
	public function prepareNetBiosName_noNetBiosName_returnsFalse()
	{
		$sut = $this->sut(array('getNetBiosNameForPersistence'));

		$netBiosName = '';

		$sut->expects($this->never())
			->method('getNetBiosNameForPersistence');

		$actual = $this->invokeMethod($sut, 'prepareNetBiosName', array($netBiosName));

		$this->assertEquals($actual, false);
	}

	/**
	 * @test
	 */
	public function getNetBiosNameForPersistence_returnsValidArray()
	{
		$netBiosName = 'TEST';

		$sut = $this->sut();

		$actual = $this->invokeMethod($sut, 'getNetBiosNameForPersistence', array($netBiosName));
		$this->assertEquals($actual, array("netbios_name" => $netBiosName));
	}

	/**
	 * @test
	 */
	public function persistNetBiosName_calls_saveBlogOptions()
	{
		$sut = $this->sut();
		$data = array();

		$this->blogConfigurationController->expects($this->once())
			->method('saveBlogOptions')
			->with($data)
			->willReturn(true);

		$actual = $this->invokeMethod($sut, 'persistNetBiosName', array($data));

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function validateVerification_calls_validateWithValidator()
	{
		$sut = $this->sut(array('getVerificationValidator', 'validateWithValidator'));

		$data = array();
		$validator = new Validator();
		$result = new Result();


		$sut->expects($this->once())
			->method('getVerificationValidator')
			->willReturn($validator);

		$sut->expects($this->once())
			->method('validateWithValidator')
			->with($validator, $data)
			->willReturn($result);

		$actual = $this->invokeMethod($sut, 'validateVerification', array($data));

		$this->assertEquals($actual, $result);
	}

	/**
	 * @test
	 */
	public function validateWithValidator_calls_givenValidator_validateMethod()
	{
		$sut = $this->sut(array('validate'));

		$validator = $this->createMock(Validator::class);

		$data = array();
		$result = new Result();

		$validator->expects($this->once())
			->method('validate')
			->with($data)
			->willReturn($result);

		$actual = $this->invokeMethod($sut, 'validateWithValidator', array($validator, $data));

		$this->assertEquals($actual, $result);
	}
}
