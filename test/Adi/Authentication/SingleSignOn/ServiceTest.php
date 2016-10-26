<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Ut_NextADInt_Adi_Authentication_SingleSignOn_ServiceTest')) {
	return;
}

class Ut_NextADInt_Adi_Authentication_SingleSignOn_ServiceTest extends Ut_BasicTest
{
	/* @var NextADInt_Adi_Authentication_Persistence_FailedLoginRepository|PHPUnit_Framework_MockObject_MockObject $failedLoginRepository */
	private $failedLoginRepository;

	/* @var NextADInt_Multisite_Configuration_Service|PHPUnit_Framework_MockObject_MockObject $configuration */
	private $configuration;

	/* @var NextADInt_Ldap_Connection|PHPUnit_Framework_MockObject_MockObject $ldapConnection */
	private $ldapConnection;

	/* @var NextADInt_Adi_User_Manager|PHPUnit_Framework_MockObject_MockObject $userManager */
	private $userManager;

	/* @var NextADInt_Adi_Mail_Notification|PHPUnit_Framework_MockObject_MockObject $mailNotification */
	private $mailNotification;

	/* @var NextADInt_Adi_Authentication_Ui_ShowBlockedMessage|PHPUnit_Framework_MockObject_MockObject $userBlockedMessage */
	private $userBlockedMessage;

	/* @var NextADInt_Ldap_Attribute_Service|PHPUnit_Framework_MockObject_MockObject $attributeService */
	private $attributeService;

	/* @var NextADInt_Adi_Role_Manager|PHPUnit_Framework_MockObject_MockObject $roleManager */
	private $roleManager;

	/* @var NextADInt_Core_Session_Handler|PHPUnit_Framework_MockObject_MockObject $sessionHandler */
	private $sessionHandler;

	/* @var NextADInt_Core_Util_Internal_Native|PHPUnit_Framework_MockObject_MockObject $sessionHandler */
	private $native;

	/** @var NextADInt_Adi_Authentication_SingleSignOn_Validator|PHPUnit_Framework_MockObject_MockObject $ssoValidation */
	private $ssoValidation;

	public function setUp()
	{
		parent::setUp();

		$this->failedLoginRepository = $this->createMock('NextADInt_Adi_Authentication_Persistence_FailedLoginRepository');
		$this->configuration = $this->createMock('NextADInt_Multisite_Configuration_Service');
		$this->ldapConnection = $this->createMock('NextADInt_Ldap_Connection');
		$this->userManager = $this->createMock('NextADInt_Adi_User_Manager');
		$this->mailNotification = $this->createMock('NextADInt_Adi_Mail_Notification');
		$this->userBlockedMessage = $this->createMock('NextADInt_Adi_Authentication_Ui_ShowBlockedMessage');
		$this->attributeService = $this->createMock('NextADInt_Ldap_Attribute_Service');
		$this->roleManager = $this->createMock('NextADInt_Adi_Role_Manager');
		$this->sessionHandler = $this->createMock('NextADInt_Core_Session_Handler');
		$this->ssoValidation = $this->createMock('NextADInt_Adi_Authentication_SingleSignOn_Validator');

		// mock away our internal php calls
		$this->native = $this->createMockedNative();
		NextADInt_Core_Util::native($this->native);
	}

	public function tearDown()
	{
		parent::tearDown();
		NextADInt_Core_Util::native(null);
	}

	/**
	 * @param null $methods
	 *
	 * @return NextADInt_Adi_Authentication_SingleSignOn_Service|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Adi_Authentication_SingleSignOn_Service')
			->setConstructorArgs(
				array(
					$this->failedLoginRepository,
					$this->configuration,
					$this->ldapConnection,
					$this->userManager,
					$this->mailNotification,
					$this->userBlockedMessage,
					$this->attributeService,
					$this->roleManager,
					$this->ssoValidation,
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function authenticate_withoutUsername_returnFalse()
	{
		$sut = $this->sut();

		WP_Mock::wpFunction('is_user_logged_in', array(
			'times'  => 1,
			'return' => false,
		));

		$actual = $sut->authenticate();

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function clearAuthenticationState_withGetParameter_doesClearSessionValues()
	{
		$_GET['reauth'] = 'sso';
		$sut = $this->sut(array('getSessionHandler'));
		$sessionHandler = $this->createMock('NextADInt_Core_Session_Handler');

		$sessionHandler->expects($this->exactly(2))
			->method('clearValue')
			->withConsecutive(
				array(NextADInt_Adi_Authentication_SingleSignOn_Service::FAILED_SSO_UPN),
				array(NextADInt_Adi_Authentication_SingleSignOn_Service::USER_LOGGED_OUT)
			);

		$sut->expects($this->exactly(2))
			->method('getSessionHandler')
			->willReturn($sessionHandler);

		$this->invokeMethod($sut, 'clearAuthenticationState');
	}

	/**
	 * @test
	 */
	public function clearAuthenticationState_withoutGetParameter_doesNotClearSessionValues()
	{
		$sut = $this->sut(array('getSessionHandler'));
		$sessionHandler = $this->createMock('NextADInt_Core_Session_Handler');

		$sessionHandler->expects($this->never())
			->method('clearValue')
			->withConsecutive(
				array(NextADInt_Adi_Authentication_SingleSignOn_Service::FAILED_SSO_UPN),
				array(NextADInt_Adi_Authentication_SingleSignOn_Service::USER_LOGGED_OUT)
			);

		$sut->expects($this->never())
			->method('getSessionHandler')
			->willReturn($sessionHandler);

		$this->invokeMethod($sut, 'clearAuthenticationState');
	}

	/**
	 * @test
	 */
	public function findUsername_returnsExpectedUsername()
	{
		$sut = $this->sut();
		$remoteVariable = 'REMOTE_USER';
		$expected = "admin@myad.local";
		$_SERVER[$remoteVariable] = $expected;

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::SSO_ENVIRONMENT_VARIABLE)
			->willReturn($remoteVariable);

		$actual = $this->invokeMethod($sut, 'findUsername');

		$this->assertEquals($expected, $actual);
	}

    /**
     * @test
     */
    public function findUsername_withDownLevelLogonName_unescapeEscapedUsername()
    {
        $sut = $this->sut();
        $remoteVariable = 'REMOTE_USER';
        $expected = 'TEST\klammer';
        $_SERVER[$remoteVariable] = addslashes($expected); // WordPress call addslashes for every entry in $_SERVEr

        $this->configuration->expects($this->once())
            ->method('getOptionValue')
            ->with(NextADInt_Adi_Configuration_Options::SSO_ENVIRONMENT_VARIABLE)
            ->willReturn($remoteVariable);

        $actual = $this->invokeMethod($sut, 'findUsername');

        $this->assertEquals($expected, $actual);
    }

	/**
	 * @test
	 */
	public function openLdapConnection_withValidConnection_doesNotThrowException()
	{
		$profile = array();
		$connectionDetails = $this->createMock('NextADInt_Ldap_ConnectionDetails');
		$sut = $this->sut(array('createConnectionDetailsFromProfile'));

		$this->behave($sut, 'createConnectionDetailsFromProfile', $connectionDetails);

		$this->expects($this->ldapConnection, $this->once(), 'connect', $connectionDetails, false);

		$this->behave($this->ldapConnection, 'isConnected', true);

		$this->invokeMethod($sut, 'openLdapConnection', array($profile));
	}

	/**
	 * @test
	 */
	public function openLdapConnection_withoutConnection_throwsException()
	{
		$profile = array();
		$connectionDetails = $this->createMock('NextADInt_Ldap_ConnectionDetails');
		$sut = $this->sut(array('createConnectionDetailsFromProfile'));

		$this->ssoValidation->expects($this->once())
			->method('validateLdapConnection')
			->willThrowException(new NextADInt_Adi_Authentication_Exception('Cannot connect to ldap. Check the connection.'));

		$this->expectExceptionThrown('NextADInt_Adi_Authentication_Exception', 'Cannot connect to ldap. Check the connection.');

		$this->behave($sut, 'createConnectionDetailsFromProfile', $connectionDetails);

		$this->expects($this->ldapConnection, $this->once(), 'connect', $connectionDetails, false);

		$this->behave($this->ldapConnection, 'isConnected', false);

		$this->invokeMethod($sut, 'openLdapConnection', array($profile));
	}

	/**
	 * @test
	 */
	public function findBestConfigurationMatchForProfile_withoutProfile_itReturnsNull()
	{
		$sut = $this->sut(array('findSsoEnabledProfiles'));
		$suffix = '@test';

		$this->behave($sut, 'findSsoEnabledProfiles', array());

		$actual = $sut->findBestConfigurationMatchForProfile(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX, $suffix);

		$this->assertNull($actual);
	}

	/**
	 * @test
	 */
	public function findBestConfigurationMatchForProfile_withoutCorrespondingProfileForSuffix_itReturnsProfileWithoutSuffixSet()
	{
		$sut = $this->sut(array('findSsoEnabledProfiles'));
		$suffix = '@test';

		$profiles = array(
			array(
				NextADInt_Adi_Configuration_Options::SSO_ENABLED    => true,
				NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX => '@abc',
			),
			array(
				NextADInt_Adi_Configuration_Options::SSO_ENABLED    => true,
				NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX => '',
			),
		);

		$this->behave($sut, 'findSsoEnabledProfiles', $profiles);

		$expected = $profiles[1];
		$actual = $sut->findBestConfigurationMatchForProfile(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX, $suffix);

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findBestConfigurationMatchForProfile_withCorrespondingProfileForSuffix_itReturnsCorrectProfile()
	{
		$sut = $this->sut(array('findSsoEnabledProfiles'));
		$suffix = '@test';

		$profiles = array(
			array(
				NextADInt_Adi_Configuration_Options::SSO_ENABLED    => true,
				NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX => $suffix,
			),
			array(
				NextADInt_Adi_Configuration_Options::SSO_ENABLED    => true,
				NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX => '',
			),
		);

		$this->behave($sut, 'findSsoEnabledProfiles', $profiles);

		$expected = $profiles[0];
		$actual = $sut->findBestConfigurationMatchForProfile(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX, $suffix);

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function authenticateAtActiveDirectory_delegatesCallToIsUserAuthorized()
	{
		$sut = $this->sut(array('isUserAuthorized'));

		$sut->expects($this->once())
			->method('isUserAuthorized')
			->with('test', '@test')
			->willReturn(true);

		$actual = $sut->authenticateAtActiveDirectory('test', '@test', '');

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function createConnectionDetailsFromProfile_returnsExpectedResult()
	{
		$sut = $this->sut();

		$profile = array(
			NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS => '127.0.0.1',
			NextADInt_Adi_Configuration_Options::PORT               => '368',
			NextADInt_Adi_Configuration_Options::ENCRYPTION         => 'none',
			NextADInt_Adi_Configuration_Options::NETWORK_TIMEOUT    => '3',
			NextADInt_Adi_Configuration_Options::BASE_DN            => 'test',
			NextADInt_Adi_Configuration_Options::SSO_USER           => 'user',
			NextADInt_Adi_Configuration_Options::SSO_PASSWORD       => 'password',
		);

		$expected = new NextADInt_Ldap_ConnectionDetails();
		$expected->setDomainControllers($profile[NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS]);
		$expected->setPort($profile[NextADInt_Adi_Configuration_Options::PORT]);
		$expected->setEncryption($profile[NextADInt_Adi_Configuration_Options::ENCRYPTION]);
		$expected->setNetworkTimeout($profile[NextADInt_Adi_Configuration_Options::NETWORK_TIMEOUT]);
		$expected->setBaseDn($profile[NextADInt_Adi_Configuration_Options::BASE_DN]);
		$expected->setUsername($profile[NextADInt_Adi_Configuration_Options::SSO_USER]);
		$expected->setPassword($profile[NextADInt_Adi_Configuration_Options::SSO_PASSWORD]);

		$actual = $this->invokeMethod($sut, 'createConnectionDetailsFromProfile', array($profile));

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function normalizeSuffix_withoutSuffix_returnsExpectedResult()
	{
		$sut = $this->sut();

		$value = 'test';
		$expected = '@' . $value;
		$actual = $this->invokeMethod($sut, 'normalizeSuffix', array($value));

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function normalizeSuffix_withExistingSuffix_returnsExpectedResult()
	{
		$sut = $this->sut();

		$expected = '@test';
		$actual = $this->invokeMethod($sut, 'normalizeSuffix', array($expected));

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getProfilesWithOptionValue_returnsExpectedResult()
	{
		$sut = $this->sut();
		$suffix = '@test';

		$profiles = array(
			array(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX => $suffix),
			array(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX => ''),
		);

		$expected = array($profiles[0]);
		$actual = $this->invokeMethod($sut, 'getProfilesWithOptionValue', array(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX, $suffix, $profiles));
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getProfilesWithoutOptionValue_returnsExpectedResult()
	{
		$sut = $this->sut();

		$profiles = array(
			array(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX => '@test'),
			array(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX => ''),
		);

		$expected = array($profiles[1]);
		$actual = $this->invokeMethod($sut, 'getProfilesWithoutOptionValue', array(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX, $profiles));
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findSsoEnabledProfiles_returnsProfilesWithSsoEnabled()
	{
		$sut = $this->sut();

		$config = array(
			NextADInt_Adi_Configuration_Options::SSO_ENABLED => array(
				'option_value'      => false,
				'option_permission' => 3,
			),
		);

		$profiles = array(
			array(
				NextADInt_Adi_Configuration_Options::SSO_ENABLED => array(
					'option_value'      => true,
					'option_permission' => 3,
				),
			),
			array(
				NextADInt_Adi_Configuration_Options::SSO_ENABLED => array(
					'option_value'      => false,
					'option_permission' => 3,
				),
			),
		);

		$this->configuration->expects($this->once())
			->method('getAllOptions')
			->willReturn($config);

		$this->configuration->expects($this->once())
			->method('findAllProfiles')
			->willReturn($profiles);

		$actual = $this->invokeMethod($sut, 'findSsoEnabledProfiles');
		$this->assertCount(1, $actual);
	}

	/**
	 * @test
	 */
	public function normalizeProfiles_returnsExpectedResult()
	{
		$sut = $this->sut();

		$expected = array(
			array(
				'domain_controllers' => '127.0.0.1',
				'port'               => '389',
			),
		);

		$actual = $this->invokeMethod(
			$sut, 'normalizeProfiles', array(
				array(
					array(
						'domain_controllers' => array('option_value' => '127.0.0.1', 'option_permission' => 3),
						'port'               => array('option_value' => '389', 'option_permission' => 3),
					),
				),
			)
		);

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function logout_setsFlagForManualLogout()
	{
		$sut = $this->sut(array('getSessionHandler'));

		$this->sessionHandler->expects($this->once())
			->method('setValue')
			->with(NextADInt_Adi_Authentication_SingleSignOn_Service::USER_LOGGED_OUT, true);

		$this->behave($sut, 'getSessionHandler', $this->sessionHandler);

		$sut->logout();
	}

	/**
	 * @test
	 */
	public function register_registersNecessaryHooks()
	{
		$sut = $this->sut();

		WP_Mock::expectActionAdded('wp_logout', array($sut, 'logout'));
		WP_Mock::expectActionAdded('init', array($sut, 'authenticate'));

		$sut->register();
	}

	/**
	 * @test
	 */
	public function loginUser_doesTriggerWordPressFunctions()
	{
		$user = $this->createWpUserMock();
		$sut = $this->sut();

		WP_Mock::wpFunction(
			'home_url', array(
				'times'  => 1,
				'args'   => '/',
				'return' => '/',
			)
		);

		WP_Mock::expectAction('wp_login', $user->user_login, $user);

		WP_Mock::wpFunction(
			'wp_set_current_user', array(
				'times' => 1,
				'args'  => $user->ID,
			)
		);

		WP_Mock::wpFunction(
			'wp_set_auth_cookie', array(
				'times' => 1,
				'args'  => $user->ID,
			)
		);

		WP_Mock::wpFunction(
			'wp_safe_redirect', array(
				'times' => 1,
				'args'  => '/',
			)
		);

		$this->invokeMethod($sut, 'loginUser', array($user, false));
	}

	/**
	 * @test
	 */
	public function getSessionHandler_returnsSessionHandlerInstance()
	{
		$sut = $this->sut();

		$sessionHandler = $this->invokeMethod($sut, 'getSessionHandler');

		$this->assertInstanceOf('NextADInt_Core_Session_Handler', $sessionHandler);
	}

	/**
	 * @test
	 */
	public function authenticate_whenAuthenticationWithUpn_itReturnsTrue()
	{
		$username = 'username@company.local';
		$credentials = new NextADInt_Adi_Authentication_Credentials($username, '');
		$profile = 1;
		$user = new WP_User(1, $username, 1);

		$sut = $this->sut(
			array('kerberosAuth', 'findUsername', 'openLdapConnection', 'getSessionHandler', 'findCorrespondingConfiguration',
				'loginUser', 'requiresActiveDirectoryAuthentication', 'detectAuthenticatableSuffixes',
				'tryAuthenticatableSuffixes')
		);

		$sut->expects($this->once())
			->method('findUsername')
			->willReturn($username);

		$sut->expects($this->once())
			->method('getSessionHandler')
			->willReturn($this->sessionHandler);

		$this->ssoValidation->expects($this->once())
			->method('validateUrl');

		$this->ssoValidation->expects($this->once())
			->method('validateAuthenticationState')
			->with($credentials);

		$this->ssoValidation->expects($this->once())
			->method('validateLogoutState');

		$sut->expects($this->once())
			->method('kerberosAuth')
			->with($credentials, $this->ssoValidation)
			->willReturn($credentials);

		$sut->expects($this->once())
			->method('requiresActiveDirectoryAuthentication')
			->with($credentials->getUserPrincipalName())
			->willReturn(true);

		$sut->expects($this->once())
			->method('detectAuthenticatableSuffixes')
			->with($credentials->getUpnSuffix())
			->willReturn($credentials->getUpnSuffix());

		$sut->expects($this->once())
			->method('tryAuthenticatableSuffixes')
			->with($credentials, $credentials->getUpnSuffix())
			->willReturn($user);

		$this->ssoValidation->expects($this->once())
			->method('validateUser')
			->with($user);

		$sut->expects($this->once())
			->method('loginUser')
			->with($user)
			->willReturn($username);

		$this->sessionHandler->expects($this->once())
			->method('clearValue')
			->with('failedSsoUpn');

		$actual = $this->invokeMethod($sut, 'authenticate', array(null, '', ''));

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function authenticate_whenAuthenticationWithNetbiosAndSamaccountName_itReturnsTrue()
	{
		$username = 'netbios\samaccountname';
		$credentials = new NextADInt_Adi_Authentication_Credentials($username, '');
		$profile = 1;
		$user = new WP_User(1, $username, 1);

		$sut = $this->sut(
			array('ntlmAuth', 'findUsername', 'openLdapConnection', 'getSessionHandler', 'findCorrespondingConfiguration',
				'loginUser', 'requiresActiveDirectoryAuthentication', 'detectAuthenticatableSuffixes',
				'tryAuthenticatableSuffixes')
		);

		$sut->expects($this->once())
			->method('findUsername')
			->willReturn($username);

		$sut->expects($this->once())
			->method('getSessionHandler')
			->willReturn($this->sessionHandler);

		$this->ssoValidation->expects($this->once())
			->method('validateUrl');

		$this->ssoValidation->expects($this->once())
			->method('validateAuthenticationState')
			->with($credentials);

		$this->ssoValidation->expects($this->once())
			->method('validateLogoutState');

		$sut->expects($this->once())
			->method('ntlmAuth')
			->with($credentials, $this->ssoValidation)
			->willReturn($credentials);

		$sut->expects($this->once())
			->method('requiresActiveDirectoryAuthentication')
			->with($credentials->getUserPrincipalName())
			->willReturn(true);

		$sut->expects($this->once())
			->method('detectAuthenticatableSuffixes')
			->with($credentials->getUpnSuffix())
			->willReturn($credentials->getUpnSuffix());

		$sut->expects($this->once())
			->method('tryAuthenticatableSuffixes')
			->with($credentials, $credentials->getUpnSuffix())
			->willReturn($user);

		$this->ssoValidation->expects($this->once())
			->method('validateUser')
			->with($user);

		$sut->expects($this->once())
			->method('loginUser')
			->with($user)
			->willReturn($username);

		$this->sessionHandler->expects($this->once())
			->method('clearValue')
			->with('failedSsoUpn');

		$actual = $this->invokeMethod($sut, 'authenticate', array(null, '', ''));

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function authenticate_withNoUsername_returnFalse()
	{
		$sut = $this->sut(array('findUsername'));

		$sut->expects($this->once())
			->method('findUsername')
			->willReturn('');

		$actual = $this->invokeMethod($sut, 'authenticate', array(null));

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function authenticate_withException_returnFalse()
	{
		$username = 'username@company.local';

		$sut = $this->sut(
			array('findUsername', 'openLdapConnection', 'getSessionHandler', 'findCorrespondingConfiguration',
				'loginUser', 'requiresActiveDirectoryAuthentication', 'detectAuthenticatableSuffixes',
				'tryAuthenticatableSuffixes')
		);

		$sut->expects($this->once())
			->method('findUsername')
			->willReturn($username);

		$sut->expects($this->once())
			->method('getSessionHandler')
			->willReturn($this->sessionHandler);

		$this->ssoValidation->expects($this->once())
			->method('validateUrl')
			->willThrowException(new NextADInt_Adi_Authentication_Exception("error"));

		$this->sessionHandler->expects($this->once())
			->method('setValue')
			->with('failedSsoUpn', $username);

		$actual = $this->invokeMethod($sut, 'authenticate', array(null, '', ''));

		$this->assertFalse($actual);
	}
}