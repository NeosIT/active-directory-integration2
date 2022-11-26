<?php

namespace Dreitier\Nadi\Ui;

use Dreitier\Ldap\Attribute\Description;
use Dreitier\Ldap\Attribute\Repository;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Role\Manager;
use Dreitier\Nadi\Ui\Validator\Rule\AccountSuffix;
use Dreitier\Nadi\Ui\Validator\Rule\AdAttributeConflict;
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
use Dreitier\Util\Validator\Rule\Conditional;
use Dreitier\Util\Validator\Rule\NotEmptyOrWhitespace;
use Dreitier\Util\Validator\Rule\PositiveNumericOrZero;
use Dreitier\WordPress\Multisite\Configuration\Service;
use Dreitier\WordPress\Multisite\Ui;
use Dreitier\WordPress\Multisite\Ui\BlogConfigurationController;
use Dreitier\WordPress\Multisite\Ui\ProfileConfigurationController;
use Dreitier\WordPress\Multisite\Ui\ProfileController;
use Dreitier\WordPress\Multisite\View\TwigContainer;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class NadiMultisiteConfigurationPageTest extends BasicTest
{
	/* @var TwigContainer |MockObject */
	private $twigContainer;

	/* @var ProfileConfigurationController|MockObject */
	private $profileConfigurationController;

	/* @var ProfileController|MockObject */
	private $profileController;

	/* @var Service|MockObject */
	private $configuration;

	/* @var BlogConfigurationController|MockObject */
	private $blogConfigurationController;

	public function setUp(): void
	{
		parent::setUp();

		$this->twigContainer = $this->createMock(TwigContainer::class);
		$this->blogConfigurationController = $this->createMock(BlogConfigurationController::class);
		$this->profileConfigurationController = $this->createMock(ProfileConfigurationController::class);
		$this->profileController = $this->createMock(ProfileController::class);
		$this->configuration = $this->createMock(Service::class);
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

		$expectedTitle = 'Profile options';

		$returnedTitle = $sut->getTitle();
		$this->assertEquals($expectedTitle, $returnedTitle);
	}

	/**
	 * @param null $methods
	 *
	 * @return NadiMultisiteConfigurationPage|MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder(NadiMultisiteConfigurationPage::class)
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

		$expectedReturn = NEXT_AD_INT_PREFIX . 'profile_options';
		$returnedValue = $sut->getSlug();

		$this->assertEquals($expectedReturn, $returnedValue);
	}

	/**
	 * @test
	 */
	public function wpAjaxSlug()
	{
		$sut = $this->sut(null);

		$expectedReturn = NEXT_AD_INT_PREFIX . 'profile_options';
		$returnedValue = $sut->wpAjaxSlug();

		$this->assertEquals($expectedReturn, $returnedValue);
	}

	/**
	 * @test
	 */
	public function renderNetwork()
	{
		$sut = $this->sut(array('display'));
		$this->mockFunction__();

		$nonce = 'some_nonce';

		\WP_Mock::wpFunction('add_query_arg', array(
				'args' => array('page', Ui\BlogProfileRelationshipPage::buildSlug()),
				'times' => 1,
				'return' => 'url')
		);

		\WP_Mock::wpFunction('wp_create_nonce', array(
				'args' => NadiMultisiteConfigurationPage::NONCE,
				'times' => 1,
				'return' => $nonce)
		);

		\WP_Mock::wpFunction('wp_create_nonce', array(
				'args' => Ui\BlogProfileRelationshipPage::NONCE,
				'times' => 1,
				'return' => $nonce)
		);

		$sut->expects($this->once())
			->method('display')
			->with(NadiMultisiteConfigurationPage::TEMPLATE, array(
					'blog_profile_relationship_url' => 'url',
					'nonce' => $nonce,
					'blog_rel_nonce' => $nonce,
					'i18n' => array(
						'warningDiscardChanges' => 'The current profile contains unsaved changes. Are you sure you want to continue?',
						'deleteProfileAssociatedSites' => 'The current profile is associated with the following sites:',
						'deleteProfileAssociated' => 'The current profile is associated with {{ associations.length }} sites. Are you sure you want to delete this profile?',
						'assignNewProfile' => 'Assign to profile:',
						'newProfile' => 'New Profile',
						'none' => 'None',
						'configureSettingsForProfile' => 'Configure Settings for Profile : ',
						'createNewProfile' => 'Create new profile',
						'deleteProfile' => 'Delete profile',
						'viewAssociatedProfiles' => 'View associated profiles',
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
						'savingSuccessful' => 'The configuration has been saved successfully.')
				)
			);

		$sut->renderNetwork();
	}

	/**
	 * @test
	 */
	public function loadJavaScriptAdmin()
	{
		$sut = $this->sut(null);
		$hook = NEXT_AD_INT_PREFIX . 'profile_options';

		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'jquery'
				),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_page', NEXT_AD_INT_URL . '/js/page.js',
					array('jquery'),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'angular.min',
					NEXT_AD_INT_URL . '/js/libraries/angular.min.js',
					array(),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'ng-alertify',
					NEXT_AD_INT_URL . '/js/libraries/ng-alertify.js',
					array('angular.min'),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'ng-notify',
					NEXT_AD_INT_URL . '/js/libraries/ng-notify.min.js',
					array('angular.min'),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'ng-busy',
					NEXT_AD_INT_URL . '/js/libraries/angular-busy.min.js',
					array('angular.min'),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_shared_util_array',
					NEXT_AD_INT_URL . '/js/app/shared/utils/array.util.js',
					array(),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_shared_util_value',
					NEXT_AD_INT_URL . '/js/app/shared/utils/value.util.js',
					array(),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_app_module',
					NEXT_AD_INT_URL . '/js/app/app.module.js',
					array(),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_app_config',
					NEXT_AD_INT_URL . '/js/app/app.nadi.js',
					array(),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_shared_service_browser',
					NEXT_AD_INT_URL . '/js/app/shared/services/browser.service.js',
					array(),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_shared_service_template',
					NEXT_AD_INT_URL . '/js/app/shared/services/template.service.js',
					array(),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_shared_service_notification',
					NEXT_AD_INT_URL . '/js/app/shared/services/notification.service.js',
					array(),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);
		
		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_profile_options_service_persistence',
					NEXT_AD_INT_URL . '/js/app/profile-options/services/persistence.service.js',
					array(),
					NadiMultisiteConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		
		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_shared_service_list',
					NEXT_AD_INT_URL . '/js/app/shared/services/list.service.js',
					array(),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_profile_options_service_data',
					NEXT_AD_INT_URL . '/js/app/profile-options/services/data.service.js',
					array(),
					NadiMultisiteConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);

		// add the controller js files
		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_profile_options_controller_profile',
					NEXT_AD_INT_URL . '/js/app/profile-options/controllers/profile.controller.js',
					array(),
					NadiMultisiteConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_profile_options_controller_delete',
					NEXT_AD_INT_URL . '/js/app/profile-options/controllers/delete.controller.js',
					array(),
					NadiMultisiteConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_profile_options_controller_ajax',
					NEXT_AD_INT_URL . '/js/app/profile-options/controllers/ajax.controller.js',
					array(),
					NadiMultisiteConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		
		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_profile_options_controller_general',
					NEXT_AD_INT_URL . '/js/app/profile-options/controllers/general.controller.js',
					array(),
					NadiMultisiteConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_profile_options_controller_environment',
					NEXT_AD_INT_URL . '/js/app/profile-options/controllers/environment.controller.js',
					array(),
					NadiMultisiteConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_profile_options_controller_user',
					NEXT_AD_INT_URL . '/js/app/profile-options/controllers/user.controller.js',
					array(),
					NadiMultisiteConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_profile_options_controller_password',
					NEXT_AD_INT_URL . '/js/app/profile-options/controllers/credential.controller.js',
					array(),
					NadiMultisiteConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_profile_options_controller_permission',
					NEXT_AD_INT_URL . '/js/app/profile-options/controllers/permission.controller.js',
					array(),
					NadiMultisiteConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_profile_options_controller_security',
					NEXT_AD_INT_URL . '/js/app/profile-options/controllers/security.controller.js',
					array(),
					NadiMultisiteConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_profile_options_controller_sso',
					NEXT_AD_INT_URL . '/js/app/profile-options/controllers/sso.controller.js',
					array(),
					NadiMultisiteConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_profile_options_controller_attributes',
					NEXT_AD_INT_URL . '/js/app/profile-options/controllers/attributes.controller.js',
					array(),
					NadiMultisiteConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_profile_options_controller_sync_to_ad',
					NEXT_AD_INT_URL . '/js/app/profile-options/controllers/sync-to-ad.controller.js',
					array(),
					NadiMultisiteConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_profile_options_controller_sync_to_wordpress',
					NEXT_AD_INT_URL . '/js/app/profile-options/controllers/sync-to-wordpress.controller.js',
					array(),
					NadiMultisiteConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);
		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_profile_options_controller_logging',
					NEXT_AD_INT_URL . '/js/app/profile-options/controllers/logging.controller.js',
					array(),
					NadiMultisiteConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_options_model',
					NEXT_AD_INT_URL . '/js/app/profile-options/models/profile.model.js',
					array(),
					NadiMultisiteConfigurationPage::VERSION_PROFILE_CONFIGURATION_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'selectizejs',
					NEXT_AD_INT_URL . '/js/libraries/selectize.min.js',
					array('jquery'),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);


		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'selectizeFix',
					NEXT_AD_INT_URL . '/js/libraries/fixed-angular-selectize-3.0.1.js',
					array('selectizejs', 'angular.min'),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
			'wp_enqueue_style', array(
				'args' => array(
					'next_ad_int',
					NEXT_AD_INT_URL . '/css/next_ad_int.css',
					array(),
					Ui::VERSION_CSS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
			'wp_enqueue_style', array(
				'args' => array(
					'ng-notify',
					NEXT_AD_INT_URL . '/css/ng-notify.min.css',
					array(),
					Ui::VERSION_CSS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
			'wp_enqueue_style', array(
				'args' => array(
					'selectizecss',
					NEXT_AD_INT_URL . '/css/selectize.css',
					array(),
					Ui::VERSION_CSS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
			'wp_enqueue_style', array(
				'args' => array(
					'alertify.min',
					NEXT_AD_INT_URL . '/css/alertify.min.css',
					array(),
					Ui::VERSION_CSS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_bootstrap_min_js',
					NEXT_AD_INT_URL . '/js/libraries/bootstrap.min.js',
					array(),
					Ui::VERSION_PAGE_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
			'wp_enqueue_style', array(
				'args' => array(
					'next_ad_int_bootstrap_min_css',
					NEXT_AD_INT_URL . '/css/bootstrap.min.css',
					array(),
					Ui::VERSION_CSS,
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

		\WP_Mock::wpFunction(
			'check_ajax_referer', array(
				'args' => array('Active Directory Integration Profile Option Nonce', 'security', true),
				'times' => 1,
			)
		);

		\WP_Mock::wpFunction(
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
			->method('getProfileOptionsValues')
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
			'message' => 'The configuration was saved successfully.',
			'type' => 'success',
			'isMessage' => true,
			'additionalInformation' => array(),
		);

		$expected = array(
			'message' => 'The configuration was saved successfully.',
			'type' => 'success',
			'isMessage' => true,
			'additionalInformation' => array(
				'profileId' => 1,
				'profileName' => 'test',
			),
		);

		$fakeValidator = $this->createAnonymousMock(array('containsErrors', 'getValidationResult'));

		$sut->expects($this->once())
			->method('validate')
			->willReturn($fakeValidator);

		$sut->expects($this->once())
			->method('saveProfile')
			->willReturn(1);

		$this->profileConfigurationController->expects($this->once())
			->method('saveProfileOptions')
			->with($data['data']['options'], $data['data']['profile'])
			->willReturn($message);

		$fakeValidator->expects($this->once())
			->method('containsErrors')
			->willReturn(false);

		$fakeValidator->expects($this->once())
			->method('getValidationResult')
			->willReturn(array());

		$result = $this->invokeMethod($sut, 'persistProfileOptionsValues', array($data));

		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 */
	public function persistProfileOptionsValues_delegatesCallGetErrorMessage()
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

		$expected = array(
			'message' => 'An error occurred while saving the configuration.',
			'type' => 'error',
			'isMessage' => true,
			'additionalInformation' => array(
				'profileId' => 1,
				'profileName' => 'test',
			),
			0 => 'Error'
		);

		$fakeValidator = $this->createAnonymousMock(array('containsErrors', 'getValidationResult'));

		$this->mockFunction__();

		$sut->expects($this->once())
			->method('validate')
			->willReturn($fakeValidator);

		$fakeValidator->expects($this->once())
			->method('containsErrors')
			->willReturn(true);

		$fakeValidator->expects($this->once())
			->method('getValidationResult')
			->willReturn(array('Error'));

		$result = $this->invokeMethod($sut, 'persistProfileOptionsValues', array($data));

		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 */
	public function loadProfiles_delegatesCallToNecessaryControllers()
	{
		$sut = $this->sut(array('getPermission'));

		$this->mockFunction__();
		$this->mockWordpressFunction('is_multisite');

		$permissionItems = array(
			0 => array(
				"value" => "0",
				"description" => __("Input field is invisible.", 'next-active-directory-integration'),
			),
			1 => array(
				"value" => "1",
				"description" => __("Deactivated and option value not shown.", 'next-active-directory-integration'),
			),
			2 => array(
				"value" => "2",
				"description" => __("Deactivated and option value shown.", 'next-active-directory-integration'),
			),
			3 => array(
				"value" => "3",
				"description" => __("Blog admin sets the option value.", 'next-active-directory-integration'),
			),
		);

		$expected = array(
			'profiles' => 'profileArray',
			'associatedProfiles' => 'associatedProfiles',
			'defaultProfileData' => 'defaultProfileData',
			'ldapAttributes' => Description::findAll(),
			'dataTypes' => Repository::findAllAttributeTypes(),
			'permissionItems' => $permissionItems,
			'wpRoles' => Manager::getRoles(),
		);

		$this->profileController->expects($this->once())
			->method('findAll')
			->willReturn($expected['profiles']);

		$this->profileController->expects($this->once())
			->method('findAllProfileAssociations')
			->willReturn($expected['associatedProfiles']);

		$this->configuration->expects($this->once())
			->method('getProfileOptionsValues')
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
	public function persistDomainSidForProfile_itSavesBlogOptions()
	{
		$sut = $this->sut();
		$data = array();

		$this->profileConfigurationController->expects($this->once())
			->method('saveProfileOptions')
			->with($data)
			->willReturn(true);

		$actual = $sut->persistDomainSid($data, 1);
		$this->assertTrue($actual);
	}
}