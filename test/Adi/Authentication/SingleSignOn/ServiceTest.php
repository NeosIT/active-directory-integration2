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

    /** @var NextADInt_Adi_LoginState|PHPUnit_Framework_MockObject_MockObject $loginState */
	private $loginState;

	/** @var NextADInt_Adi_User_LoginSucceededService */
	private $loginSucceededService;

    /**
     * @var NextADInt_Adi_Authentication_SingleSignOn_Profile_Locator|PHPUnit_Framework_MockObject_MockObject
     */
	private $ssoProfileLocator;

	public function setUp() : void
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
		$this->loginSucceededService = $this->createMock('NextADInt_Adi_User_LoginSucceededService');
		$this->loginState = new NextADInt_Adi_LoginState();
        $this->ssoProfileLocator = $this->createMock('NextADInt_Adi_Authentication_SingleSignOn_Profile_Locator');

		// mock away our internal php calls
		$this->native = $this->createMockedNative();
		NextADInt_Core_Util::native($this->native);
	}

	public function tearDown() : void
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
					$this->ssoValidation,
                    $this->loginState,
					$this->loginSucceededService,
                    $this->ssoProfileLocator
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
			'times' => 1,
			'return' => false,
		));

		$actual = $sut->authenticate();

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function authenticate_userLoggedIn_returnFalse()
	{
		$sut = $this->sut();

		WP_Mock::wpFunction('is_user_logged_in', array(
			'times' => 1,
			'return' => true,
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
				array(NextADInt_Adi_Authentication_SingleSignOn_Service::FAILED_SSO_PRINCIPAL),
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
				array(NextADInt_Adi_Authentication_SingleSignOn_Service::FAILED_SSO_PRINCIPAL),
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
	 * Check authenticateAtActiveDirectory overwrite comment
	 */
	public function tryAuthenticatableSuffixes_delegatesToParent()
	{
		$sut = $this->sut(array('postAuthentication'));
        $credentials = new NextADInt_Adi_Authentication_Credentials('username');

		$sut->expects($this->once())
            ->method('postAuthentication')
            ->willReturn($credentials);

		$actual = $sut->tryAuthenticatableSuffixes($credentials, array(/* can be null for SSO */));

		$this->assertEquals($credentials, $actual);
	}

	/**
	 * @test
	 */
	public function createConnectionDetailsFromProfile_returnsExpectedResult()
	{
		$sut = $this->sut();

		$profile = array(
			NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS => '127.0.0.1',
			NextADInt_Adi_Configuration_Options::PORT => '368',
			NextADInt_Adi_Configuration_Options::ENCRYPTION => 'none',
			NextADInt_Adi_Configuration_Options::NETWORK_TIMEOUT => '3',
			NextADInt_Adi_Configuration_Options::BASE_DN => 'test',
			NextADInt_Adi_Configuration_Options::SSO_USER => 'user',
			NextADInt_Adi_Configuration_Options::SSO_PASSWORD => 'password',
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
	public function detectAuthenticatableSuffixes_validSuffixes_returnsSuffixITself()
	{
		$suffix = 'test.ad';

		$sut = $this->sut(null);

		$actual = $sut->detectAuthenticatableSuffixes($suffix);

		$this->assertEquals(array($suffix), $actual);
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
		// @issue #142
		WP_Mock::expectFilterAdded('next_ad_int_auth_sso_login_requires_ad_authentication', array($sut, 'requiresActiveDirectoryAuthentication'), 10, 1);
		// @issue #160
		WP_Mock::expectActionAdded('next_ad_int_login_succeeded_do_redirect', array($sut, 'doRedirect'));
		WP_Mock::expectFilterAdded('next_ad_int_login_succeeded_create_redirect_uri', array($sut, 'createRedirectUri'), 10, 1);

		$sut->register();
	}

	/**
	 * @test
	 */
	public function loginUser_doesTriggerWordPressFunctions()
	{
		$user = $this->createWpUserMock();
		$sut = $this->sut();

		WP_Mock::expectAction('wp_login', $user->user_login, $user);

		WP_Mock::wpFunction(
			'is_ssl', array(
				'times' => 1,
				'return' => true
			)
		);

		WP_Mock::wpFunction(
			'wp_set_current_user', array(
				'times' => 1,
				'args'  => array($user->ID, $user->user_login),
			)
		);

		WP_Mock::wpFunction(
			'wp_set_auth_cookie', array(
				'times' => 1,
				'args'  => array($user->ID, true, true /* SSL */),
			)
		);

		$this->invokeMethod($sut, 'loginUser', array($user, false));
	}

	/**
	 * @test
	 * @issue #160
	 */
	public function GH_160_loginUser_callsAction_loginSucceededDoRedirect()
	{
		$user = $this->createWpUserMock();
		$sut = $this->sut();

		WP_Mock::expectAction(NEXT_AD_INT_PREFIX . 'login_succeeded_do_redirect', $user, false);

		$this->invokeMethod($sut, 'loginUser', array($user, false));
	}

	/**
	 * @issue #160
	 * @test
	 */
	public function doRedirect_sendsSafeRedirect() {
		$user = $this->createWpUserMock();
		$sut = $this->sut();

		\WP_Mock::onFilter( NEXT_AD_INT_PREFIX . 'login_succeeded_create_redirect_uri' )
			->with('')
			->reply('/');

		WP_Mock::wpFunction(
			'wp_safe_redirect', array(
				'times' => 1,
				'args' => '/',
			)
		);

		$this->invokeMethod($sut, 'doRedirect', array($user, false));
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
	public function authenticate_userNotAuthenticated_authenticationFails_itReturnsFalse()
	{
		$sut              = $this->sut(array('findUsername', 'getSessionHandler', 'clearAuthenticationState', 'delegateAuth', 'parentAuthenticate'));
		$expectedUsername = 'john.doe@test.ad';
		$credentials = NextADInt_Adi_Authentication_PrincipalResolver::createCredentials($expectedUsername);

		WP_Mock::wpFunction(
			'is_user_logged_in', array(
				'times'  => 1,
				'return' => false
			)
		);

		$sut->expects($this->once())
		    ->method('findUsername')
		    ->willReturn($expectedUsername);

		\WP_Mock::onFilter(NEXT_AD_INT_PREFIX . 'auth_sso_login_requires_ad_authentication')
			->with($expectedUsername)
			->reply(true);

		$sut->expects($this->once())
		    ->method('getSessionHandler')
		    ->willReturn($this->sessionHandler);

		$sut->expects($this->once())
		    ->method('clearAuthenticationState');

		$this->ssoValidation->expects($this->once())
		                    ->method('validateUrl');

		$this->ssoValidation->expects($this->once())
		                    ->method('validateLogoutState');

		$this->ssoValidation->expects($this->once())
		                    ->method('validateAuthenticationState')
		                    ->with($credentials);

		$sut->expects($this->once())
		    ->method('delegateAuth')
		    ->with($credentials, $this->ssoValidation)
		    ->willReturn($credentials);

		$sut->expects($this->once())
		    ->method('parentAuthenticate')
		    ->willReturn(null);

		$this->sessionHandler->expects($this->once())
			->method('setValue')
			->with($sut::FAILED_SSO_PRINCIPAL, $credentials->getUserPrincipalName());

		$actual = $sut->authenticate(null, '', '');

		$this->assertFalse($actual);
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
     * @since 2.2.0
     * @test
     */
    public function delegateAuth_firesHook() {
	    $sut = $this->sut(array('openLdapConnection', 'updateCredentials'));
	    $profile = array('test');
	    $type = NextADInt_Adi_Authentication_SingleSignOn_Profile_Match::KERBEROS_REALM;
	    $credentials = new NextADInt_Adi_Authentication_Credentials("username");

	    $profileMatch = new NextADInt_Adi_Authentication_SingleSignOn_Profile_Match($profile, $type);
	    $ldapAttributes = new NextADInt_Ldap_Attributes(array('attr'));

	    $this->ssoProfileLocator->expects($this->once())
            ->method('locate')
            ->with($credentials)
            ->willReturn($profileMatch);

	    $this->attributeService->expects($this->once())
            ->method('resolveLdapAttributes')
            ->with($credentials->toUserQuery())
            ->willReturn($ldapAttributes);

	    $sut->expects($this->once())
            ->method('updateCredentials')
            ->with($credentials, $ldapAttributes);

	    \WP_Mock::expectAction(NEXT_AD_INT_PREFIX . 'sso_profile_located',
            $credentials, $profileMatch);

	    $sut->delegateAuth($credentials, $this->ssoValidation);
    }

	/**
	 * @since 2.3.2
	 * @issue #152
	 * @test
	 */
	public function GH_152_delegateAuthThrowsException_ifProfileCannotBeFound()
	{
		$sut = $this->sut(array('openLdapConnection', 'updateCredentials'));
		$credentials = new NextADInt_Adi_Authentication_Credentials("username");

		$noProfileMatch = NextADInt_Adi_Authentication_SingleSignOn_Profile_Match::noMatch();
		$ldapAttributes = new NextADInt_Ldap_Attributes(array('attr'));

		$this->ssoProfileLocator->expects($this->once())
			->method('locate')
			->with($credentials)
			->willReturn($noProfileMatch);

		$this->attributeService->expects($this->never())
			->method('resolveLdapAttributes')
			->with($credentials->toUserQuery())
			->willReturn($ldapAttributes);

		$sut->expects($this->never())
			->method('updateCredentials')
			->with($credentials, $ldapAttributes);

		$this->expectException(NextADInt_Adi_Authentication_Exception::class);
		$this->expectExceptionMessageMatches('/Unable to locate a matching profile/');

		$sut->delegateAuth($credentials, $this->ssoValidation);
	}

	/**
	 * @test
	 */
	public function authenticate_withExceptionDuringLogout_itReturnFalse()
	{
		$username = 'username@company.local';

		WP_Mock::wpFunction('is_user_logged_in', array(
			'times' => 1,
			'return' => false,
		));

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
			->willThrowException(new NextADInt_Adi_Authentication_LogoutException("error"));

		$this->sessionHandler->expects($this->never())
			->method('setValue')
			->with('failedSsoPrincipal', $username);

		$actual = $this->invokeMethod($sut, 'authenticate', array(null, '', ''));

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function authenticate_withExceptionDuringAuthentication_itReturnFalse()
	{
		$username = 'username@company.local';

		WP_Mock::wpFunction('is_user_logged_in', array(
			'times' => 1,
			'return' => false,
		));

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
			->method('validateAuthenticationState')
			->willThrowException(new NextADInt_Adi_Authentication_Exception("error"));

		$this->sessionHandler->expects($this->once())
			->method('setValue')
			->with('failedSsoPrincipal', $username);

		$actual = $this->invokeMethod($sut, 'authenticate', array(null, '', ''));

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 * @issue ADI-418
	 */
	public function ADI_418_createRedirectUri_itUsesEnvironmentVar_REDIRECT_URL_asDefault()
	{
		$sut = $this->sut();

		$_SERVER['REQUEST_URI'] = '/my-redirect-url';
		$sut = $this->sut();

		$r = $this->invokeMethod($sut, 'createRedirectUri', array());
		$this->assertEquals($r, $_SERVER['REQUEST_URI']);
	}

	/**
	 * @test
	 * @issue ADI-418
	 */
	public function ADI_418_createRedirectUri_itUsesWordPressVar_redirect_to_over_REDIRECT_URL()
	{
		$user = $this->createWpUserMock();
		$sut = $this->sut();

		$_SERVER['REDIRECT_URL'] = '/wrong-url';
		$_REQUEST['redirect_to'] = '/expected-url';
		$sut = $this->sut();

		$r = $this->invokeMethod($sut, 'createRedirectUri', array());
		$this->assertEquals($_REQUEST['redirect_to'], $r);
	}

	/**
	 * @test
	 * @issue #142
	 */
	public function GH_142_authenticate_skips_if_auth_form_login_requires_ad_authentication_returns_false() {
		$sut              = $this->sut(array('findUsername', 'getSessionHandler', 'clearAuthenticationState', 'delegateAuth', 'parentAuthenticate'));
		$expectedUsername = 'john.doe@test.ad';
		$credentials = NextADInt_Adi_Authentication_PrincipalResolver::createCredentials($expectedUsername);

		WP_Mock::wpFunction(
			'is_user_logged_in', array(
				'times'  => 1,
				'return' => false
			)
		);

		$sut->expects($this->once())
			->method('findUsername')
			->willReturn($expectedUsername);

		\WP_Mock::onFilter(NEXT_AD_INT_PREFIX . 'auth_sso_login_requires_ad_authentication')
			->with($expectedUsername)
			->reply(false);

		$sut->expects($this->never())
			->method('getSessionHandler')
			->willReturn($this->sessionHandler);

		$actual = $sut->authenticate(null, $expectedUsername, '');
		$this->assertFalse($actual);
	}
}