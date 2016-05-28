<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_Multisite_Ui_ProfileConfigurationPageTest extends Ut_BasicTest
{
	/* @var Multisite_View_TwigContainer | PHPUnit_Framework_MockObject_MockObject */
	private $twigContainer;

	/* @var Multisite_Ui_ProfileConfigurationController|PHPUnit_Framework_MockObject_MockObject */
	private $profileConfigurationController;

	/* @var Multisite_Ui_ProfileController|PHPUnit_Framework_MockObject_MockObject */
	private $profileController;

	/* @var Multisite_Configuration_Service|PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/* @var Multisite_Ui_BlogConfigurationController|PHPUnit_Framework_MockObject_MockObject */
	private $blogConfigurationController;

	public function setUp()
	{
		parent::setUp();

		$this->twigContainer = $this->createMock('Multisite_View_TwigContainer');
		$this->blogConfigurationController = $this->createMock('Multisite_Ui_BlogConfigurationController');
		$this->profileConfigurationController = $this->createMock('Multisite_Ui_ProfileConfigurationController');
		$this->profileController = $this->createMock('Multisite_Ui_ProfileController');
		$this->configuration = $this->createMock('Multisite_Configuration_Service');
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

		$expectedTitle = 'Profile options';
		$returnedTitle = $sut->getTitle();

		$this->assertEquals($expectedTitle, $returnedTitle);
	}

	/**
	 * @param null $methods
	 *
	 * @return Multisite_Ui_ProfileConfigurationPage|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('Multisite_Ui_ProfileConfigurationPage')
			->setConstructorArgs(
				array(
					$this->twigContainer,
					$this->blogConfigurationController,
					$this->profileConfigurationController,
					$this->profileController,
					$this->configuration,
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

		$expectedReturn = ADI_PREFIX . 'profile_options';
		$returnedValue = $sut->getSlug();

		$this->assertEquals($expectedReturn, $returnedValue);
	}

	/**
	 * @test
	 */
	public function wpAjaxSlug()
	{
		$sut = $this->sut(null);

		$expectedReturn = ADI_PREFIX . 'profile_options';
		$returnedValue = $sut->wpAjaxSlug();

		$this->assertEquals($expectedReturn, $returnedValue);
	}

	/**
	 * @test
	 */
	public function renderNetwork()
	{
		$sut = $this->sut(array('display'));

		$nonce = 'some_nonce';

		WP_Mock::wpFunction(
			'add_query_arg', array(
				'args' => array('page', Multisite_Ui_BlogProfileRelationshipPage::buildSlug()),
				'times' => 1,
				'return' => 'url',
			)
		);

		WP_Mock::wpFunction(
			'wp_create_nonce', array(
				'args' => Multisite_Ui_ProfileConfigurationPage::NONCE,
				'times' => 1,
				'return' => $nonce,
			)
		);

		WP_Mock::wpFunction(
			'wp_create_nonce', array(
				'args' => Multisite_Ui_BlogProfileRelationshipPage::NONCE,
				'times' => 1,
				'return' => $nonce,
			)
		);

		$sut->expects($this->once())
			->method('display')
			->with(Multisite_Ui_ProfileConfigurationPage::TEMPLATE, array(
				'blog_profile_relationship_url' => 'url',
				'nonce' => $nonce,
				'blog_rel_nonce' => $nonce,
			));

		$sut->renderNetwork();
	}

	/**
	 * @test
	 */
	public function loadJavaScriptAdmin()
	{
		$sut = $this->sut(null);
		$hook = ADI_PREFIX . 'profile_options';

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_page', ADI_URL . '/js/page.js',
					array('jquery'),
					Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'jquery-1.12.2.min',
					ADI_URL . '/js/libraries/jquery-1.12.2.min.js',
					array(),
					Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'angular.min',
					ADI_URL . '/js/libraries/angular.min.js',
					array(),
					Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'ng-alertify',
					ADI_URL . '/js/libraries/ng-alertify.js',
					array('angular.min'),
					Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'ng-notify',
					ADI_URL . '/js/libraries/ng-notify.min.js',
					array('angular.min'),
					Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_shared_util_array',
					ADI_URL . '/js/app/shared/utils/array.util.js',
					array(),
					Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_shared_util_value',
					ADI_URL . '/js/app/shared/utils/value.util.js',
					array(),
					Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_app_module',
					ADI_URL . '/js/app/app.module.js',
					array(),
					Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_app_config',
					ADI_URL . '/js/app/app.config.js',
					array(),
					Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_shared_service_browser',
					ADI_URL . '/js/app/shared/services/browser.service.js',
					array(),
					Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_shared_service_template',
					ADI_URL . '/js/app/shared/services/template.service.js',
					array(),
					Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_shared_service_notification',
					ADI_URL . '/js/app/shared/services/notification.service.js',
					array(),
					Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_profile_options_service_persistence',
					ADI_URL . '/js/app/profile-options/services/persistence.service.js',
					array(),
					Multisite_Ui_ProfileConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_shared_service_list',
					ADI_URL . '/js/app/shared/services/list.service.js',
					array(),
					Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_profile_options_service_data',
					ADI_URL . '/js/app/profile-options/services/data.service.js',
					array(),
					Multisite_Ui_ProfileConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);

		// add the controller js files
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_profile_options_controller_profile',
					ADI_URL . '/js/app/profile-options/controllers/profile.controller.js',
					array(),
					Multisite_Ui_ProfileConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_profile_options_controller_delete',
					ADI_URL . '/js/app/profile-options/controllers/delete.controller.js',
					array(),
					Multisite_Ui_ProfileConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_profile_options_controller_ajax',
					ADI_URL . '/js/app/profile-options/controllers/ajax.controller.js',
					array(),
					Multisite_Ui_ProfileConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_profile_options_controller_general',
					ADI_URL . '/js/app/profile-options/controllers/general.controller.js',
					array(),
					Multisite_Ui_ProfileConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_profile_options_controller_environment',
					ADI_URL . '/js/app/profile-options/controllers/environment.controller.js',
					array(),
					Multisite_Ui_ProfileConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_profile_options_controller_user',
					ADI_URL . '/js/app/profile-options/controllers/user.controller.js',
					array(),
					Multisite_Ui_ProfileConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_profile_options_controller_password',
					ADI_URL . '/js/app/profile-options/controllers/password.controller.js',
					array(),
					Multisite_Ui_ProfileConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_profile_options_controller_permission',
					ADI_URL . '/js/app/profile-options/controllers/permission.controller.js',
					array(),
					Multisite_Ui_ProfileConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_profile_options_controller_security',
					ADI_URL . '/js/app/profile-options/controllers/security.controller.js',
					array(),
					Multisite_Ui_ProfileConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_profile_options_controller_attributes',
					ADI_URL . '/js/app/profile-options/controllers/attributes.controller.js',
					array(),
					Multisite_Ui_ProfileConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_profile_options_controller_sync_to_ad',
					ADI_URL . '/js/app/profile-options/controllers/sync-to-ad.controller.js',
					array(),
					Multisite_Ui_ProfileConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_profile_options_controller_sync_to_wordpress',
					ADI_URL . '/js/app/profile-options/controllers/sync-to-wordpress.controller.js',
					array(),
					Multisite_Ui_ProfileConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'adi2_blog_options_model',
					ADI_URL . '/js/app/profile-options/models/profile.model.js',
					array(),
					Multisite_Ui_ProfileConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'selectizejs',
					ADI_URL . '/js/libraries/selectize.min.js',
					array('jquery'),
					Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);


		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'selectizeFix',
					ADI_URL . '/js/libraries/fixed-angular-selectize-3.0.1.js',
					array('selectizejs', 'angular.min'),
					Multisite_Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_style', array(
				'args' => array(
					'adi2',
					ADI_URL . '/css/adi2.css',
					array(),
					Multisite_Ui::VERSION_CSS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_style', array(
				'args' => array(
					'ng-notify',
					ADI_URL . '/css/ng-notify.min.css',
					array(),
					Multisite_Ui::VERSION_CSS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_style', array(
				'args' => array(
					'selectizecss',
					ADI_URL . '/css/selectize.css',
					array(),
					Multisite_Ui::VERSION_CSS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_style', array(
				'args' => array(
					'alertify.min',
					ADI_URL . '/css/alertify.min.css',
					array(),
					Multisite_Ui::VERSION_CSS,
				),
				'times' => 1,
			)
		);

		$sut->loadNetworkScriptsAndStyle($hook);
	}

	/**
	 * @test
	 */
	public function wpAjaxListener_delegatesCallToRouteRequestMethod()
	{
		$sut = $this->sut(array('renderJson', 'routeRequest'));

		$_POST['subAction'] = 'subAction';
		$_POST['data']['options'] = array(
			'someOption' => 'a',
		);
		$_POST['data']['profile'] = 'someProfile';

		WP_Mock::wpFunction(
			'check_ajax_referer', array(
				'args' => array('Active Directory Integration Profile Option Nonce', 'security', true),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'current_user_can', array(
				'args' => array('manage_network'),
				'times' => '1',
				'return' => true,
			)
		);

		$sut->expects($this->once())
			->method('routeRequest')
			->with($_POST['subAction'], $_POST)
			->willReturn(false);

		$sut->wpAjaxListener();
	}

	/**
	 * @test
	 */
	public function routeRequest_withoutExistingSubAction_returnsFalse()
	{
		$sut = $this->sut();

		$result = $this->invokeMethod($sut, 'routeRequest', array('does-not-exist', array()));

		$this->assertFalse($result);
	}

	/**
	 * @test
	 */
	public function routeRequest_withExistingSubAction_delegatesCallToCorrectMethod()
	{
		$sut = $this->sut(array('saveProfile'));
		$data = array('data' => 'test');

		$sut->expects($this->once())
			->method('saveProfile')
			->with($data)
			->willReturn('test');

		$result = $this->invokeMethod($sut, 'routeRequest', array('saveProfile', $data));

		$this->assertEquals('test', $result);
	}

	/**
	 * @test
	 */
	public function saveProfile_validatesDataAndDelegatesCallToProfileController()
	{
		$sut = $this->sut(array('validate'));

		$data = array(
			'data' => array('test', 'profile' => 1),
		);

		$sut->expects($this->once())
			->method('validate');

		$this->profileController->expects($this->once())
			->method('saveProfile')
			->with($data['data'], 1)
			->willReturn('test');

		$result = $this->invokeMethod($sut, 'saveProfile', array($data));

		$this->assertEquals('test', $result);
	}

	/**
	 * @test
	 */
	public function removeProfile_delegatesCallToProfileController()
	{
		$sut = $this->sut();

		$data = array('id' => 1);

		$this->profileController->expects($this->once())
			->method('deleteProfile')
			->with(1)
			->willReturn('test');

		$result = $this->invokeMethod($sut, 'removeProfile', array($data));

		$this->assertEquals('test', $result);
	}

	/**
	 * @test
	 */
	public function getProfileOptionsValues_delegatesCallToConfigurationService()
	{
		$sut = $this->sut();

		$data = array('profileId' => 1);

		$this->configuration->expects($this->once())
			->method('getAllProfileOptionsValues')
			->with(1)
			->willReturn('test');

		$result = $this->invokeMethod($sut, 'getProfileOptionsValues', array($data));

		$this->assertEquals('test', $result);
	}

	/**
	 * @test
	 */
	public function persistProfileOptionsValues_delegatesCallToProfileConfigurationController()
	{
		$sut = $this->sut(array('validate', 'saveProfile'));

		$data = array(
			'data' => array(
				'options' => array(
					'test',
					'profile_name' => array(
						'option_value' => 'test',
					),
				),
				'profile' => 1,
			),
		);

		$message = array(
			'message' => 'The profile was deleted successfully.',
			'type' => 'success',
			'isMessage' => true,
			'additionalInformation' => array(),
		);

		$expected = array(
			'message' => 'The profile was deleted successfully.',
			'type' => 'success',
			'isMessage' => true,
			'additionalInformation' => array(
				'profileId' => 1,
				'profileName' => 'test',
			),
		);

		$sut->expects($this->once())
			->method('validate');

		$sut->expects($this->once())
			->method('saveProfile')
			->willReturn(1);

		$this->profileConfigurationController->expects($this->once())
			->method('saveProfileOptions')
			->with($data['data']['options'], $data['data']['profile'])
			->willReturn($message);

		$result = $this->invokeMethod($sut, 'persistProfileOptionsValues', array($data));

		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 */
	public function loadProfiles_delegatesCallToNecessaryControllers()
	{
		$sut = $this->sut(array('getPermission'));

		$permissionItems = array(
			0 => array(
				"value" => "0",
				"description" => __("Input field is invisible.", ADI_I18N),
			),
			1 => array(
				"value" => "1",
				"description" => __("Deactivated and option value not shown.", ADI_I18N),
			),
			2 => array(
				"value" => "2",
				"description" => __("Deactivated and option value shown.", ADI_I18N),
			),
			3 => array(
				"value" => "3",
				"description" => __("Blog admin sets the option value.", ADI_I18N),
			),
		);

		$expected = array(
			'profiles' => 'profileArray',
			'associatedProfiles' => 'associatedProfiles',
			'defaultProfileData' => 'defaultProfileData',
			'ldapAttributes' => Ldap_Attribute_Description::findAll(),
			'dataTypes' => Ldap_Attribute_Repository::findAllAttributeTypes(),
			'permissionItems' => $permissionItems,
		);

		$this->profileController->expects($this->once())
			->method('findAll')
			->willReturn($expected['profiles']);

		$this->profileController->expects($this->once())
			->method('findAllProfileAssociations')
			->willReturn($expected['associatedProfiles']);

		$this->configuration->expects($this->once())
			->method('getAllProfileOptionsValues')
			->with(-1)
			->willReturn($expected['defaultProfileData']);

		$sut->expects($this->once())
			->method('getPermission')
			->willReturn($permissionItems);

		$result = $this->invokeMethod($sut, 'loadProfiles');

		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 */
	public function validate_withValidationErrors_rendersErrors()
	{
		$sut = $this->sut(array('getValidator', 'renderJson'));

		$result = array('test' => 'error');

		$validationResult = $this->createMock('Core_Validator_Result');
		$validationResult->expects($this->once())
			->method('isValid')
			->willReturn(false);

		$validationResult->expects($this->once())
			->method('getResult')
			->willReturn($result);

		$validator = $this->createMock('Core_Validator');
		$validator->expects($this->once())
			->method('validate')
			->willReturn($validationResult);

		$sut->expects($this->once())
			->method('getValidator')
			->willReturn($validator);

		$sut->expects($this->once())
			->method('renderJson')
			->with($result);

		$this->invokeMethod($sut, 'validate', array(array('options' => array())));
	}

	/**
	 * @test
	 */
	public function validate_withoutValidationErrors_rendersErrors()
	{
		$sut = $this->sut(array('getValidator', 'renderJson'));

		$result = array('test' => 'error');

		$validationResult = $this->createMock('Core_Validator_Result');
		$validationResult->expects($this->once())
			->method('isValid')
			->willReturn(true);

		$validationResult->expects($this->never())
			->method('getResult')
			->willReturn($result);

		$validator = $this->createMock('Core_Validator');
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

		$this->assertCount(11, $rules);
		$this->assertInstanceOf('Multisite_Validator_Rule_ConditionalSuffix', $rules[Adi_Configuration_Options::SYNC_TO_WORDPRESS_USER][0]);
		$this->assertInstanceOf('Multisite_Validator_Rule_ConditionalSuffix', $rules[Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_USER][0]);
		$this->assertInstanceOf('Multisite_Validator_Rule_AccountSuffix', $rules[Adi_Configuration_Options::ACCOUNT_SUFFIX][0]);
		$this->assertInstanceOf('Multisite_Validator_Rule_NoDefaultAttributeName', $rules[Adi_Configuration_Options::ADDITIONAL_USER_ATTRIBUTES][0]);
		$this->assertInstanceOf('Multisite_Validator_Rule_AttributeMappingNull', $rules[Adi_Configuration_Options::ADDITIONAL_USER_ATTRIBUTES][1]);
		$this->assertInstanceOf('Multisite_Validator_Rule_WordPressMetakeyConflict', $rules[Adi_Configuration_Options::ADDITIONAL_USER_ATTRIBUTES][2]);
		$this->assertInstanceOf('Multisite_Validator_Rule_AdAttributeConflict', $rules[Adi_Configuration_Options::ADDITIONAL_USER_ATTRIBUTES][3]);
		$this->assertInstanceOf('Multisite_Validator_Rule_DefaultEmailDomain', $rules[Adi_Configuration_Options::DEFAULT_EMAIL_DOMAIN][0]);
		$this->assertInstanceOf('Multisite_Validator_Rule_AdminEmail', $rules[Adi_Configuration_Options::ADMIN_EMAIL][0]);
		$this->assertInstanceOf('Multisite_Validator_Rule_Port', $rules[Adi_Configuration_Options::PORT][0]);
		$this->assertInstanceOf('Multisite_Validator_Rule_PositiveNumericOrZero', $rules[Adi_Configuration_Options::NETWORK_TIMEOUT][0]);
		$this->assertInstanceOf('Multisite_Validator_Rule_PositiveNumericOrZero', $rules[Adi_Configuration_Options::MAX_LOGIN_ATTEMPTS][0]);
		$this->assertInstanceOf('Multisite_Validator_Rule_PositiveNumericOrZero', $rules[Adi_Configuration_Options::BLOCK_TIME][0]);
		$this->assertInstanceOf('Multisite_Validator_Rule_NotEmptyOrWhitespace', $rules[Adi_Configuration_Options::PROFILE_NAME][0]);
	}


	/**
	 * @test
	 */
	public function persistDomainSidForProfile_itSavesBlogOptions()
	{
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}
}