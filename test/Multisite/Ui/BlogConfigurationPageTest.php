<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Multisite_Ui_BlogConfigurationPageTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_View_TwigContainer|PHPUnit_Framework_MockObject_MockObject */
	private $twigContainer;

	/* @var NextADInt_Multisite_Ui_BlogConfigurationController|PHPUnit_Framework_MockObject_MockObject */
	private $blogConfigurationController;

	public function setUp()
	{
		parent::setUp();

		$this->twigContainer = $this->createMock('NextADInt_Multisite_View_TwigContainer');
		$this->blogConfigurationController = $this->createMock('NextADInt_Multisite_Ui_BlogConfigurationController');
	}

	public function tearDown()
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
	 * @return NextADInt_Multisite_Ui_BlogConfigurationPage| PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Multisite_Ui_BlogConfigurationPage')
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

		$expectedReturn = NEXT_AD_INT_PREFIX . 'blog_options';
		$returnedValue = $sut->getSlug();

		$this->assertEquals($expectedReturn, $returnedValue);
	}

	/**
	 * @test
	 */
	public function wpAjaxSlug()
	{
		$sut = $this->sut(null);

		$expectedReturn = NEXT_AD_INT_PREFIX . 'blog_options';
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
            'title' => 'Active Directory Integration Blog Configuration',
            'regenerateAuthCode' => 'Regenerate Auth Code',
            'securityGroup' => 'Security group',
            'wordpressRole' => 'WordPress role',
            'selectRole' => 'Please select a role',
            'verify' => 'Verify',
            'adAttributes' => 'AD Attributes',
            'dataType' => 'Data Type',
            'wordpressAttribute' => 'Wordpress Attribute',
            'description' => 'Description',
            'viewInUserProfile' => 'View in User Profile',
            'syncToAd' => 'Sync to Ad',
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
            'haveToVerifyDomainConnection' => 'You have to verify the connection to the AD before saving.'
        );

        WP_Mock::wpFunction('wp_create_nonce', array(
            'args' => NextADInt_Multisite_Ui_BlogConfigurationPage::NONCE,
            'times' => 1,
            'return' => $nonce,)
		);

        $sut->expects($this->once())
			->method('display')
			->with(NextADInt_Multisite_Ui_BlogConfigurationPage::TEMPLATE, array('nonce' => $nonce, 'i18n' => $i18n));

		$sut->renderAdmin();
	}

	/**
	 * @test
	 */
	public function loadAdminScriptsAndStyle()
	{
		$sut = $this->sut(null);
		$hook = NEXT_AD_INT_PREFIX . 'blog_options';

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
				'args'  => array(
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
					NEXT_AD_INT_URL . '/js/app/app.config.js',
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
					'next_ad_int_blog_options_service_persistence',
					NEXT_AD_INT_URL . '/js/app/blog-options/services/persistence.service.js',
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
					'next_ad_int_blog_options_service_data',
					NEXT_AD_INT_URL . '/js/app/blog-options/services/data.service.js',
					array(),
					NextADInt_Multisite_Ui_BlogConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);

		// add the controller js files
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_blog',
					NEXT_AD_INT_URL . '/js/app/blog-options/controllers/blog.controller.js',
					array(),
					NextADInt_Multisite_Ui_BlogConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_ajax',
					NEXT_AD_INT_URL . '/js/app/blog-options/controllers/ajax.controller.js',
					array(),
					NextADInt_Multisite_Ui_BlogConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_general',
					NEXT_AD_INT_URL . '/js/app/blog-options/controllers/general.controller.js',
					array(),
					NextADInt_Multisite_Ui_BlogConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_environment',
					NEXT_AD_INT_URL . '/js/app/blog-options/controllers/environment.controller.js',
					array(),
					NextADInt_Multisite_Ui_BlogConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_user',
					NEXT_AD_INT_URL . '/js/app/blog-options/controllers/user.controller.js',
					array(),
					NextADInt_Multisite_Ui_BlogConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_password',
					NEXT_AD_INT_URL . '/js/app/blog-options/controllers/password.controller.js',
					array(),
					NextADInt_Multisite_Ui_BlogConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_permission',
					NEXT_AD_INT_URL . '/js/app/blog-options/controllers/permission.controller.js',
					array(),
					NextADInt_Multisite_Ui_BlogConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_security',
					NEXT_AD_INT_URL . '/js/app/blog-options/controllers/security.controller.js',
					array(),
					NextADInt_Multisite_Ui_BlogConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_attributes',
					NEXT_AD_INT_URL . '/js/app/blog-options/controllers/attributes.controller.js',
					array(),
					NextADInt_Multisite_Ui_BlogConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_sync_to_ad',
					NEXT_AD_INT_URL . '/js/app/blog-options/controllers/sync-to-ad.controller.js',
					array(),
					NextADInt_Multisite_Ui_BlogConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_sync_to_wordpress',
					NEXT_AD_INT_URL . '/js/app/blog-options/controllers/sync-to-wordpress.controller.js',
					array(),
					NextADInt_Multisite_Ui_BlogConfigurationPage::VERSION_BLOG_OPTIONS_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_controller_logging',
					NEXT_AD_INT_URL . '/js/app/blog-options/controllers/logging.controller.js',
					array(),
					NextADInt_Multisite_Ui_BlogConfigurationPage::VERSION_BLOG_OPTIONS_JS,
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
				'args' => array('next_ad_int', NEXT_AD_INT_URL . '/css/next_ad_int.css', array(), NextADInt_Multisite_Ui::VERSION_CSS),
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


		$sut->loadAdminScriptsAndStyle($hook);
	}

    /**
     * @test
     */
    public function wpAjaxListener_withEscapedCharacter_unescapeTheseCharacter() {
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

        WP_Mock::wpFunction('check_ajax_referer', array(
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

		WP_Mock::wpFunction('check_ajax_referer', array(
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

		WP_Mock::wpFunction(
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

		WP_Mock::wpFunction(
			'check_ajax_referer', array(
				'args' => array('Active Directory Integration Configuration Nonce', 'security', true),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
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
		$sut = $this->sut(array(NextADInt_Multisite_Ui_BlogConfigurationPage::SUB_ACTION_GENERATE_AUTHCODE));

		$sut->expects($this->once())
			->method(NextADInt_Multisite_Ui_BlogConfigurationPage::SUB_ACTION_GENERATE_AUTHCODE)
			->with(array());

		$subAction = NextADInt_Multisite_Ui_BlogConfigurationPage::SUB_ACTION_GENERATE_AUTHCODE;

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
				'option_permission' => NextADInt_Multisite_Configuration_Service::EDITABLE
			),
			'port' => array(
				'option_value' => 'test',
				'option_permission' => NextADInt_Multisite_Configuration_Service::REPLACE_OPTION_WITH_DEFAULT_TEXT
			),
		);

		$expected = array(
			'domain_controllers' => array(
				'option_value' => 'test',
				'option_permission' => NextADInt_Multisite_Configuration_Service::EDITABLE
			),
			'port' => array(
				'option_value' => '',
				'option_permission' => NextADInt_Multisite_Configuration_Service::REPLACE_OPTION_WITH_DEFAULT_TEXT
			),
		);

		$this->twigContainer->expects($this->once())
			->method('getAllOptionsValues')
			->willReturn($data);

		$result = $this->invokeMethod($sut, 'getAllOptionsValues');

		$this->assertEquals(array(
			'options' => $expected,
			'ldapAttributes' => NextADInt_Ldap_Attribute_Description::findAll(),
			'dataTypes' => NextADInt_Ldap_Attribute_Repository::findAllAttributeTypes(),
			'wpRoles'        => NextADInt_Adi_Role_Manager::getRoles(),
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

		$this->twigContainer->expects($this->once())
			->method('getAllOptionsValues')
			->willReturn(array('test' => array('option_permission' => 3)));

		$this->blogConfigurationController->expects($this->once())
			->method('saveBlogOptions')
			->willReturn(true);

		$actual = $this->invokeMethod($sut, 'persistOptionsValues', array(array('data' => array('test' => 'test'))));

		$this->assertTrue($actual);
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
			->willReturn(true);

		$this->invokeMethod($sut, 'persistOptionsValues', array(array('data' => array('test' => 'test'))));
	}

	/**
	 * @test
	 */
	public function validate_withoutValidationErrors_rendersErrors()
	{
		$sut = $this->sut(array('getValidator', 'renderJson'));

		$result = array('test' => 'error');

		$validationResult = $this->createMock('NextADInt_Core_Validator_Result');
		$validationResult->expects($this->once())
			->method('isValid')
			->willReturn(true);

		$validationResult->expects($this->never())
			->method('getResult')
			->willReturn($result);

		$validator = $this->createMock('NextADInt_Core_Validator');
		$validator->expects($this->once())
			->method('validate')
			->willReturn($validationResult);

		$sut->expects($this->once())
			->method('getValidator')
			->willReturn($validator);

		$sut->expects($this->never())
			->method('renderJson')
			->with($result);

		$this->invokeMethod($sut, 'validate', array(array('options' => array())));
	}

	/**
	 * @test
	 */
	public function getValidator_hasRequiredValidations()
	{
		$sut = $this->sut();

		$validator = $sut->getValidator();
		$rules = $validator->getValidationRules();

		$this->assertCount(17, $rules);
		$this->assertInstanceOf('NextADInt_Multisite_Validator_Rule_Conditional', $rules[NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_USER][0]);
		$this->assertInstanceOf('NextADInt_Multisite_Validator_Rule_Conditional', $rules[NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_USER][0]);
		$this->assertInstanceOf('NextADInt_Multisite_Validator_Rule_AccountSuffix', $rules[NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX][0]);
		$this->assertInstanceOf('NextADInt_Multisite_Validator_Rule_NoDefaultAttributeName', $rules[NextADInt_Adi_Configuration_Options::ADDITIONAL_USER_ATTRIBUTES][0]);
		$this->assertInstanceOf('NextADInt_Multisite_Validator_Rule_AttributeMappingNull', $rules[NextADInt_Adi_Configuration_Options::ADDITIONAL_USER_ATTRIBUTES][1]);
		$this->assertInstanceOf('NextADInt_Multisite_Validator_Rule_WordPressMetakeyConflict', $rules[NextADInt_Adi_Configuration_Options::ADDITIONAL_USER_ATTRIBUTES][2]);
		$this->assertInstanceOf('NextADInt_Multisite_Validator_Rule_AdAttributeConflict', $rules[NextADInt_Adi_Configuration_Options::ADDITIONAL_USER_ATTRIBUTES][3]);
		$this->assertInstanceOf('NextADInt_Multisite_Validator_Rule_DefaultEmailDomain', $rules[NextADInt_Adi_Configuration_Options::DEFAULT_EMAIL_DOMAIN][0]);
		$this->assertInstanceOf('NextADInt_Multisite_Validator_Rule_AdminEmail', $rules[NextADInt_Adi_Configuration_Options::ADMIN_EMAIL][0]);
		$this->assertInstanceOf('NextADInt_Multisite_Validator_Rule_FromEmailAdress', $rules[NextADInt_Adi_Configuration_Options::FROM_EMAIL][0]);
		$this->assertInstanceOf('NextADInt_Multisite_Validator_Rule_Port', $rules[NextADInt_Adi_Configuration_Options::PORT][0]);
		$this->assertInstanceOf('NextADInt_Multisite_Validator_Rule_PositiveNumericOrZero', $rules[NextADInt_Adi_Configuration_Options::NETWORK_TIMEOUT][0]);
		$this->assertInstanceOf('NextADInt_Multisite_Validator_Rule_PositiveNumericOrZero', $rules[NextADInt_Adi_Configuration_Options::MAX_LOGIN_ATTEMPTS][0]);
		$this->assertInstanceOf('NextADInt_Multisite_Validator_Rule_PositiveNumericOrZero', $rules[NextADInt_Adi_Configuration_Options::BLOCK_TIME][0]);
		$this->assertInstanceOf('NextADInt_Multisite_Validator_Rule_NotEmptyOrWhitespace', $rules[NextADInt_Adi_Configuration_Options::PROFILE_NAME][0]);
		$this->assertInstanceOf('NextADInt_Multisite_Validator_Rule_DisallowInvalidWordPressRoles', $rules[NextADInt_Adi_Configuration_Options::ROLE_EQUIVALENT_GROUPS][0]);
		$this->assertInstanceOf('NextADInt_Multisite_Validator_Rule_SelectValueValid', $rules[NextADInt_Adi_Configuration_Options::ENCRYPTION][0]);
		$this->assertInstanceOf('NextADInt_Multisite_Validator_Rule_SelectValueValid', $rules[NextADInt_Adi_Configuration_Options::SSO_ENVIRONMENT_VARIABLE][0]);
		$this->assertInstanceOf('NextADInt_Multisite_Validator_Rule_Conditional', $rules[NextADInt_Adi_Configuration_Options::SSO_USER][0]);
		$this->assertInstanceOf('NextADInt_Multisite_Validator_Rule_Conditional', $rules[NextADInt_Adi_Configuration_Options::SSO_PASSWORD][0]);
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
}