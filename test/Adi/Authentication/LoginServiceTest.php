<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_Authentication_LoginServiceTest extends Ut_BasicTest
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

	/** @var NextADInt_Adi_LoginState */
	private $loginState;

	/** @var NextADInt_Adi_User_LoginSucceededService */
	private $loginSucceededService;

	public function setUp(): void
	{
		parent::setUp();

		$this->failedLoginRepository = $this->createMock('NextADInt_Adi_Authentication_Persistence_FailedLoginRepository');
		$this->configuration = $this->createMock('NextADInt_Multisite_Configuration_Service');
		$this->ldapConnection = $this->createMock('NextADInt_Ldap_Connection');
		$this->userManager = $this->createMock('NextADInt_Adi_User_Manager');
		$this->mailNotification = $this->createMock('NextADInt_Adi_Mail_Notification');
		$this->userBlockedMessage = $this->createMock('NextADInt_Adi_Authentication_Ui_ShowBlockedMessage');
		$this->attributeService = $this->createMock('NextADInt_Ldap_Attribute_Service');
		$this->loginSucceededService = $this->createMock('NextADInt_Adi_User_LoginSucceededService');
		$this->loginState = new NextADInt_Adi_LoginState();
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @return NextADInt_Adi_Authentication_LoginService|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null, $simulated = false)
	{
		return $this->getMockBuilder('NextADInt_Adi_Authentication_LoginService')
			->setConstructorArgs(
				array(
					$this->failedLoginRepository,
					$this->configuration,
					$this->ldapConnection,
					$this->userManager,
					$this->mailNotification,
					$this->userBlockedMessage,
					$this->attributeService,
					$this->loginState,
					$this->loginSucceededService
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function authenticate_itSkips_ifNoActiveDirectoryAuthenticationIsRequired()
	{
		$sut = $this->sut(
			array(
				'detectAuthenticatableSuffixes'
			)
		);

		$login = "testuser";
		$password = "1234";

		\WP_Mock::onFilter(NEXT_AD_INT_PREFIX . 'auth_form_login_requires_ad_authentication')
			->with($login)
			->reply(false);

		$sut->expects($this->never())
			->method('detectAuthenticatableSuffixes');

		$actual = $sut->authenticate(null, $login, $password);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function authenticate_itTriesAuthenticatableSuffixes_ifAuthenticationIsRequired()
	{
		$sut = $this->sut(
			array(
				'detectAuthenticatableSuffixes',
				'tryAuthenticatableSuffixes'
			)
		);

		$login = "testuser@test.ad";
		$password = "1234";
		$suffixes = array('test.ad');

		\WP_Mock::onFilter(NEXT_AD_INT_PREFIX . 'auth_form_login_requires_ad_authentication')
			->with($login)
			->reply(true);

		$sut->expects($this->once())
			->method('detectAuthenticatableSuffixes')
			->with('test.ad')
			->willReturn($suffixes);

		$sut->expects($this->once())
			->method('tryAuthenticatableSuffixes')
			->with($this->isInstanceOf('NextADInt_Adi_Authentication_Credentials'), $suffixes)
			->willReturn(false);

		$actual = $sut->authenticate(null, $login, $password);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function tryAuthenticatableSuffixes_itReturnsFalse_ifNoSuffixIsGiven()
	{
		$sut = $this->sut(array('authenticateAtActiveDirectory'));
		$credentials = $sut->buildCredentials('username', 'password');

		$sut->expects($this->never())
			->method('authenticateAtActiveDirectory');

		$actual = $sut->tryAuthenticatableSuffixes($credentials, array());
		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function tryAuthenticatableSuffixes_itExecutesPostAuthentication_ifAuthenticationSucceeds()
	{
		$sut = $this->sut(array(
			'authenticateAtActiveDirectory',
			'postAuthentication'
		));

		$credentials = $sut->buildCredentials('username', 'password');

		$sut->expects($this->once())
			->method('authenticateAtActiveDirectory')
			->with('username', 'test.ad', 'password')
			->willReturn(true);

		$sut->expects($this->once())
			->method('postAuthentication')
			->with($credentials)
			->willReturn(true);

		$actual = $sut->tryAuthenticatableSuffixes($credentials, array('test.ad'));
		$this->assertTrue($actual);
	}

	/**
	 * @since 2.2.0
	 * @test
	 */
	public function updateCredentials_setsRelevantPrincipals()
	{
		$sut = $this->sut();
		$credentials = new NextADInt_Adi_Authentication_Credentials('username');
		$attributes = [
			'samaccountname' => 'sam',
			'userprincipalname' => 'sam@test.ad',
			'objectguid' => 666
		];

		$ldapAttributes = new NextADInt_Ldap_Attributes($attributes, $attributes);

		$sut->updateCredentials($credentials, $ldapAttributes);

		$this->assertEquals($attributes['samaccountname'], $credentials->getSAMAccountName());
		$this->assertEquals($attributes['userprincipalname'], $credentials->getUserPrincipalName());
		$this->assertEquals($attributes['objectguid'], $credentials->getObjectGuid());
	}

	/**
	 * @test
	 */
	public function createAuthentication_itCreatesANewInstance()
	{
		$sut = $this->sut(null);

		$credentials = $sut->buildCredentials('username', 'password');

		$this->assertNotNull($credentials);
	}

	/**
	 * @test
	 */
	public function requiresActiveDirectoryAuthentication_returnsFalse_ifEmpty()
	{
		$sut = $this->sut(null);

		$returnedValue = $sut->requiresActiveDirectoryAuthentication('');
		$this->assertFalse($returnedValue);
	}

	/**
	 * @test
	 */
	public function requiresActiveDirectoryAuthentication_returnsFalse_ifAdmin()
	{
		$sut = $this->sut(array('getWordPressUser'));

		$username = "testAdmin";
		$user = (object)array(
			'ID' => 1,
		);

		$sut->expects($this->once())
			->method('getWordPressUser')
			->with($username)
			->willReturn($user);

		$returnedValue = $sut->requiresActiveDirectoryAuthentication($username);
		$this->assertFalse($returnedValue);
	}

	/**
	 * @test
	 */
	public function requiresActiveDirectoryAuthentication_itReturnsFalse_whenUsernameIsExcluded()
	{
		$sut = $this->sut(array('getWordPressUser', 'isUsernameExcludedFromAuthentication'));
		$username = "testAdmin";
		$user = (object)array(
			'ID' => 2,
		);

		$sut->expects($this->once())
			->method('isUsernameExcludedFromAuthentication')
			->with($username)
			->willReturn(true);

		$returnedValue = $sut->requiresActiveDirectoryAuthentication($username);
		$this->assertFalse($returnedValue);
	}

	/**
	 * @test
	 */
	public function requiresActiveDirectoryAuthentication_returnsTrue_ifNormalUser()
	{
		$sut = $this->sut(array('getWordPressUser', 'isUsernameExcludedFromAuthentication'));
		$username = "testAdmin";

		$sut->expects($this->once())
			->method('isUsernameExcludedFromAuthentication')
			->with($username)
			->willReturn(false);


		$returnedValue = $sut->requiresActiveDirectoryAuthentication($username);
		$this->assertTrue($returnedValue);
	}

	/**
	 * @test
	 */
	public function isUsernameExcludedFromAuthentication_itReturnsTrue_whenUserIsExcluded()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::EXCLUDE_USERNAMES_FROM_AUTHENTICATION)
			->willReturn('userA;userB');

		// Match original name
		$this->assertTrue($sut->isUsernameExcludedFromAuthentication('userA'));
	}

	/**
	 * @issue ADI-304
	 * @test
	 */
	public function ADI_304_isUsernameExcludedFromAuthentication_itReturnsTrue_whenUserIsExcludedCaseInsensitive()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::EXCLUDE_USERNAMES_FROM_AUTHENTICATION)
			->willReturn('userA;userB');

		// ADI-304: exclude usernames must be case-insensitive
		$this->assertTrue($sut->isUsernameExcludedFromAuthentication('userb'));
	}

	/**
	 * @test
	 */
	public function isUsernameExcludedFromAuthentication_itReturnsFalse_whenUserIsNotExcluded()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::EXCLUDE_USERNAMES_FROM_AUTHENTICATION)
			->willReturn('userA;userB');

		$this->assertFalse($sut->isUsernameExcludedFromAuthentication('userC'));
	}

	/**
	 * @test
	 */
	public function detectAuthenticatableSuffixes_itReturnsDefaultSuffixes_whenNoSuffixIsGiven()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX)
			->willReturn('@home.de;@test.ad');

		$actual = $sut->detectAuthenticatableSuffixes('');


		$this->assertEquals(array('@home.de', '@test.ad'), $actual);
	}

	/**
	 * @test
	 */
	public function detectAuthenticatableSuffixes_itReturnsGivenSuffix_ifNoDefaultSuffixesAreGiven()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX)
			->willReturn('');

		$actual = $sut->detectAuthenticatableSuffixes('@test.ad');

		$this->assertEquals(array('@test.ad'), $actual);
	}

	/**
	 * @test
	 * @see ADI-716
	 */
	public function ADI_716()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX)
			->willReturn('@test.ad;@domain.tld');

		$actual = $sut->detectAuthenticatableSuffixes('domain.tld');

		$this->assertEquals(array('@domain.tld'), $actual);
	}

	/**
	 * @test
	 */
	public function detectAuthenticatableSuffixes_itReturnsDefaultSuffixes_whenSuffixIsNotRegistered()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX)
			->willReturn('@test.ad;@domain.tld');

		$actual = $sut->detectAuthenticatableSuffixes('unknown.tld');


		$this->assertEquals(array('@test.ad', '@domain.tld'), $actual);
	}

	/**
	 * @test
	 */
	public function authenticateAtActiveDirectory_itReturnsFalse_whenAuthenticationFails()
	{
		$sut = $this->sut(array('bruteForceProtection', 'refreshBruteForceProtectionStatusForUser'));

		$username = 'testUser';
		$suffix = "@company.it";
		$password = "1234";

		$this->ldapConnection->expects($this->exactly(1))
			->method('checkPorts')
			->willReturn(true);

		$this->ldapConnection->expects($this->once())
			->method('connect')
			->with(new NextADInt_Ldap_ConnectionDetails())
			->willReturn(true);

		$sut->expects($this->exactly(2))
			->method('bruteForceProtection')
			->with($username);

		$returnedValue = $sut->authenticateAtActiveDirectory($username, $suffix, $password);
		$this->assertEquals(false, $returnedValue);
	}

	/**
	 * @test
	 */
	public function authenticateAtActiveDirectory_itReturnsTrue_whenAuthenticationSucceeds()
	{
		$sut = $this->sut(array('bruteForceProtection', 'refreshBruteForceProtectionStatusForUser'));

		$username = 'testUser';
		$suffix = "@company.it";
		$password = "1234";
		$userGuid = 'e16d5d9c-xxxx-xxxx-9b8b-969fdf4b2702';

		$roleMapping = new NextADInt_Adi_Role_Mapping("username");
		$attributes = new NextADInt_Ldap_Attributes(array(), array('objectguid' => $userGuid));

		$this->ldapConnection->expects($this->exactly(1))
			->method('checkPorts')
			->willReturn(true);

		$this->ldapConnection->expects($this->once())
			->method('connect')
			->with(new NextADInt_Ldap_ConnectionDetails())
			->willReturn(true);

		$sut->expects($this->exactly(2))
			->method('bruteForceProtection')
			->with($username);

		$this->ldapConnection->expects($this->once())
			->method('authenticate')
			->with($username, $suffix, $password)
			->willReturn(true);

		$actual = $sut->authenticateAtActiveDirectory($username, $suffix, $password);
		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function bruteForceProtection_checkSimulationDetection()
	{
		$temp = $this->userBlockedMessage;
		$this->userBlockedMessage = null;
		$sut = $this->sut();
		$this->userBlockedMessage = $temp;

		$sut->bruteForceProtection('test', '@test.test');

		$temp = $this->failedLoginRepository;
		$this->failedLoginRepository = null;
		$sut = $this->sut();
		$this->failedLoginRepository = $temp;

		$sut->bruteForceProtection('test', '@test.test');
	}

	/**
	 * @test
	 */
	public function bruteForceProtection_userIsNotBlocked()
	{
		$sut = $this->sut(null);

		$this->failedLoginRepository->expects($this->once())
			->method('isUserBlocked')
			->with('test@test.test')
			->willReturn(false);

		$this->mailNotification->expects($this->never())
			->method('sendNotifications');

		$sut->bruteForceProtection('test', '@test.test');
	}

	/**
	 * @test
	 */
	public function bruteForceProtection_userIsBlocked()
	{
		$sut = $this->sut();

		$wpUser = $this->createWpUserMock();

		$this->userManager->expects($this->once())
			->method('findByActiveDirectoryUsername')
			->with('hugo', 'hugo@test.test')
			->willReturn($wpUser);

		$this->failedLoginRepository->expects($this->once())
			->method('isUserBlocked')
			->with('hugo@test.test')
			->willReturn(true);

		$this->userManager->expects($this->once())
			->method("findByActiveDirectoryUsername")
			->with('hugo', 'hugo@test.test')
			->willReturn($wpUser);

		$this->mailNotification->expects($this->once())
			->method('sendNotifications')
			->with($wpUser, true);

		$this->userBlockedMessage->expects($this->once())
			->method('blockCurrentUser');

		$sut->bruteForceProtection('hugo', '@test.test');
	}

	/**
	 * @test
	 */
	public function refreshBruteForceProtectionStatusForUser_simulation()
	{
		$this->failedLoginRepository = null;

		$sut = $this->sut();

		$sut->refreshBruteForceProtectionStatusForUser('test', '@test.ad', false);
	}

	/**
	 * @test
	 */
	public function refreshBruteForceProtectionStatusForUser_successfulLogin()
	{
		$sut = $this->sut();

		$this->failedLoginRepository->expects($this->once())
			->method('deleteLoginAttempts')
			->with('test');

		$sut->refreshBruteForceProtectionStatusForUser('test', '', true);
	}

	/**
	 * @test
	 */
	public function refreshBruteForceProtectionStatusForUser_unsuccessfulLogin_withLessLoginAttempts()
	{
		$sut = $this->sut();

		$wpUser = $this->createWpUserMock();

		$this->userManager->expects($this->once())
			->method('findByActiveDirectoryUsername')
			->with('test', 'test@test.test')
			->willReturn($wpUser);

		$this->userManager->expects($this->once())
			->method('isNAdiUser')
			->with($wpUser)
			->willReturn(true);

		$this->failedLoginRepository->expects($this->once())
			->method('increaseLoginAttempts')
			->with('test@test.test');

		$this->failedLoginRepository->expects($this->once())
			->method('findLoginAttempts')
			->with('test@test.test')
			->willReturn(1);

		$this->failedLoginRepository->expects($this->once())
			->method('findLoginAttempts')
			->with('test@test.test')
			->willReturn(4);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::MAX_LOGIN_ATTEMPTS)
			->willReturn(3);

		$this->failedLoginRepository->expects($this->never())
			->method('blockUser');

		$sut->refreshBruteForceProtectionStatusForUser('test', '@test.test', false);
	}

	/**
	 * @test
	 */
	public function refreshBruteForceProtectionStatusForUser_unsuccessfulLogin_withTooManyLoginAttempts()
	{
		$sut = $this->sut();

		$wpUser = $this->createWpUserMock();

		$this->userManager->expects($this->once())
			->method('findByActiveDirectoryUsername')
			->with('test', 'test@test.test')
			->willReturn($wpUser);

		$this->userManager->expects($this->once())
			->method('isNAdiUser')
			->with($wpUser)
			->willReturn(true);

		$this->failedLoginRepository->expects($this->once())
			->method('increaseLoginAttempts')
			->with('test@test.test');

		$this->failedLoginRepository->expects($this->once())
			->method('findLoginAttempts')
			->with('test@test.test')
			->willReturn(4);

		$this->configuration->expects($this->exactly(2))
			->method('getOptionValue')
			->withConsecutive(
				array(NextADInt_Adi_Configuration_Options::MAX_LOGIN_ATTEMPTS),
				array(NextADInt_Adi_Configuration_Options::BLOCK_TIME)
			)
			->will(
				$this->onConsecutiveCalls(
					3,
					30
				)
			);

		$this->failedLoginRepository->expects($this->once())
			->method('blockUser')
			->with('test@test.test', 30);

		$sut->refreshBruteForceProtectionStatusForUser('test', '@test.test', false);
	}

	/**
	 * @issue ADI-367
	 * @test
	 */
	public function ADI_367_xmlrpcMustBeSecured_whenAllowXmlRpcLoginIsDisabled()
	{
		$sut = $this->sut();
		$this->mockFunction__();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::ALLOW_XMLRPC_LOGIN)
			->willReturn(false);

		$_SERVER['PHP_SELF'] = 'xmlrpc.php';

		\WP_Mock::wpFunction('wp_die',
			array(
				'args' => array("Next ADI prevents XML RPC authentication!"),
				'times' => 1,
			)
		);

		$sut->checkXmlRpcAccess();
	}

	/**
	 * @issue ADI-367
	 * @test
	 */
	public function ADI_367_xmlrpcIsAllowed_whenOptionIsConfigured()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::ALLOW_XMLRPC_LOGIN)
			->willReturn(true);

		$_SERVER['PHP_SELF'] = 'xmlrpc.php';

		\WP_Mock::wpFunction('wp_die',
			array(
				'args' => array("Next ADI prevents XML RPC authentication!"),
				'times' => 0,
			)
		);

		$sut->checkXmlRpcAccess();
	}

	/**
	 * @issue ADI-367
	 * @test
	 */
	public function ADI_367_authenticate_checksXmlRpcAccess()
	{
		$sut = $this->sut(
			array(
				'checkXmlRpcAccess'
			)
		);

		$login = "testuser";
		$password = "1234";

		$sut->expects($this->once())
			->method('checkXmlRpcAccess');

		$actual = $sut->authenticate(null, $login, $password);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 * @issue ADI-673
	 */
	public function getWordPressUser_withValidLogin_returnsExpectedWpUser()
	{
		$login = 'john.doe';
		$sut = $this->sut();
		$expectedWpUserId = 422;

		WP_Mock::wpFunction('username_exists',
			array(
				'args' => array($login),
				'times' => 1,
				'return' => $expectedWpUserId
			)
		);

		$actual = $sut->getWordPressUser($login);

		$this->assertTrue($actual instanceof WP_User);
	}

	/**
	 * @test
	 * @issue ADI-673
	 */
	public function getWordPressUser_withInvalidLogin_returnsFalse()
	{
		$login = 'john.doe';
		$sut = $this->sut();

		WP_Mock::wpFunction('username_exists',
			array(
				'args' => array($login),
				'times' => 1,
				'return' => false
			)
		);

		$actual = $sut->getWordPressUser($login);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 * @issue ADI-673
	 */
	public function postAuthentication_withValidCredentials_authenticationSuccess_returnsCredentials()
	{
		$samaccountName = 'john.doe';
		$objectguid = 'cc07cacc-5d9d-fa40-a9fb-3a4d50a172b0';
		$credentials = new NextADInt_Adi_Authentication_Credentials($samaccountName, 'secret');
		$filteredAttributes = array('samaccountname' => $samaccountName, 'objectguid' => $objectguid);
		$expectedLdapAttributes = new NextADInt_Ldap_Attributes(array(), $filteredAttributes);

		$sut = $this->sut(array('updateCredentials'));

		$this->attributeService->expects($this->once())
			->method('resolveLdapAttributes')
			->with($this->callback(function (NextADInt_Ldap_UserQuery $userQuery) use ($credentials) {
				return $userQuery->getPrincipal() == $credentials->getLogin();
			}))
			->willReturn($expectedLdapAttributes);

		$sut->expects($this->once())
			->method('updateCredentials')
			->with($credentials, $expectedLdapAttributes);

		$actual = $sut->postAuthentication($credentials);

		$this->assertTrue($this->loginState->isAuthenticated());
	}

	/**
	 * @test
	 * @issue ADI-673
	 */
	public function postAuthentication_withInvalidCredentials_returnsFalse()
	{
		$samaccountName = 'john.doe';
		$credentials = new NextADInt_Adi_Authentication_Credentials($samaccountName, 'secret');
		$expectedLdapAttributes = new NextADInt_Ldap_Attributes(false, false);

		$sut = $this->sut();

		$this->attributeService->expects($this->once())
			->method('resolveLdapAttributes')
			->with($this->callback(function (NextADInt_Ldap_UserQuery $userQuery) use ($credentials) {
				return $userQuery->getPrincipal() == $credentials->getLogin();
			}))
			->willReturn($expectedLdapAttributes);


		$actual = $sut->postAuthentication($credentials);

		$this->assertFalse($actual);
		$this->assertFalse($this->loginState->isAuthenticated());
	}

	/**
	 * @test
	 * @issue ADI-673
	 */
	public function register_withoutLostPasswordRecovery_willAddExpectedFilter()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::ENABLE_LOST_PASSWORD_RECOVERY)
			->willReturn(true);

		WP_Mock::expectFilterAdded('authenticate', array($sut, 'authenticate'), 10, 3);
		WP_Mock::expectFilterNotAdded('allow_password_reset', '__return_false');

		$sut->register();
	}

	/**
	 * @test
	 * @issue ADI-673
	 */
	public function register_withLostPasswordRecovery_willAddExpectedFilters()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::ENABLE_LOST_PASSWORD_RECOVERY)
			->willReturn(false);

		WP_Mock::expectFilterAdded('authenticate', array($sut, 'authenticate'), 10, 3);
		WP_Mock::expectFilterAdded('allow_password_reset', '__return_false');

		$sut->register();
	}

	/**
	 * @test
	 * @issue #142
	 */
	public function register_adds_filter_next_ad_int_auth_form_login_requires_ad_authentication() {
		$sut = $this->sut();

		WP_Mock::expectFilterAdded('next_ad_int_auth_form_login_requires_ad_authentication', array($sut, 'requiresActiveDirectoryAuthentication'), 10, 1);
		$sut->register();
	}

}