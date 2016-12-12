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

	/* @var NextADInt_Adi_Role_Manager|PHPUnit_Framework_MockObject_MockObject $roleManager */
	private $roleManager;

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
	}


	public function tearDown()
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
					$this->roleManager,
				)
			)
			->setMethods($methods)
			->getMock();
	}

	private function createAuthentication($login, $password)
	{
		return NextADInt_Adi_Authentication_LoginService::createCredentials($login, $password);
	}

	/**
	 * @test
	 */
	public function authenticate_itSkips_ifNoActiveDirectoryAuthenticationIsRequired()
	{
		$sut = $this->sut(
			array(
				'requiresActiveDirectoryAuthentication',
				'detectAuthenticatableSuffixes'
			)
		);

		$login = "testuser";
		$password = "1234";

		$sut->expects($this->once())
			->method('requiresActiveDirectoryAuthentication')
			->with($login)
			->willReturn(false);

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
				'requiresActiveDirectoryAuthentication',
				'detectAuthenticatableSuffixes',
				'tryAuthenticatableSuffixes'
			)
		);

		$login = "testuser@test.ad";
		$password = "1234";
		$suffixes = array('test.ad');

		$sut->expects($this->once())
			->method('requiresActiveDirectoryAuthentication')
			->with($login)
			->willReturn(true);

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
		$credentials = NextADInt_Adi_Authentication_LoginService::createCredentials('username', 'password');

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
		$credentials = NextADInt_Adi_Authentication_LoginService::createCredentials('username', 'password');

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
	 * @test
	 */
	public function createAuthentication_itCreatesANewInstance()
	{
		$credentials = NextADInt_Adi_Authentication_LoginService::createCredentials('username', 'password');

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
	 */
	public function detectAuthenticatableSuffixes_itMovesProvidedSuffixToFirstPosition_whenSuffixIsRegistered()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX)
			->willReturn('@test.ad;@domain.tld');

		$actual = $sut->detectAuthenticatableSuffixes('domain.tld');


		$this->assertEquals(array('@domain.tld', '@test.ad'), $actual);
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

		$this->ldapConnection->expects($this->once())
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
	public function authenticateAtActiveDirectory_itReturnsFalse_whenUserIsNotInAuthorizationGroup()
	{
		$sut = $this->sut(array('bruteForceProtection', 'refreshBruteForceProtectionStatusForUser'));

		$username = 'testUser';
		$suffix = "@company.it";
		$password = "1234";

		$roleMapping = new NextADInt_Adi_Role_Mapping("username");

		$this->ldapConnection->expects($this->once())
			->method('checkPorts')
			->willReturn(true);

		$this->ldapConnection->expects($this->once())
			->method('connect')
			->with(new NextADInt_Ldap_ConnectionDetails())
			->willReturn(true);

		$this->ldapConnection->expects($this->once())
			->method('authenticate')
			->with($username, $suffix, $password)
			->willReturn(true);

		$sut->expects($this->exactly(2) /* before and after authentication */)
			->method('bruteForceProtection')
			->with($username);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::AUTHORIZE_BY_GROUP)
			->willReturn(true);

		$this->roleManager->expects($this->exactly(2))
			->method('createRoleMapping')
			->withConsecutive(
				array($username),
				array($username . $suffix)
			)
			->willReturn($roleMapping);

		$this->roleManager->expects($this->once())
			->method('isInAuthorizationGroup')
			->with($roleMapping)
			->willReturn(false);

		$returnedValue = $sut->authenticateAtActiveDirectory($username, $suffix, $password);
		$this->assertFalse($returnedValue);
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

		$roleMapping = new NextADInt_Adi_Role_Mapping("username");

		$this->ldapConnection->expects($this->once())
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

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::AUTHORIZE_BY_GROUP)
			->willReturn(true);

		$this->roleManager->expects($this->exactly(2))
			->method('createRoleMapping')
			->withConsecutive(
				array($username),
				array($username . $suffix)
			)
			->willReturn($roleMapping);

		$this->roleManager->expects($this->once())
			->method('isInAuthorizationGroup')
			->with($roleMapping)
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

		$sut->bruteForceProtection('test');

		$temp = $this->failedLoginRepository;
		$this->failedLoginRepository = null;
		$sut = $this->sut();
		$this->failedLoginRepository = $temp;

		$sut->bruteForceProtection('test');
	}

	/**
	 * @test
	 */
	public function bruteForceProtection_userIsNotBlocked()
	{
		$sut = $this->sut(null);

		$this->failedLoginRepository->expects($this->once())
			->method('isUserBlocked')
			->with('test')
			->willReturn(false);

		$this->mailNotification->expects($this->never())
			->method('sendNotifications');

		$sut->bruteForceProtection('test');
	}

	/**
	 * @test
	 */
	public function bruteForceProtection_userIsBlocked()
	{
		$sut = $this->sut();

		$this->failedLoginRepository->expects($this->once())
			->method('isUserBlocked')
			->with('hugo@test.local')
			->willReturn(true);

		$this->mailNotification->expects($this->once())
			->method('sendNotifications')
			->with('hugo@test.local', true);

		$this->userBlockedMessage->expects($this->once())
			->method('blockCurrentUser');

		$sut->bruteForceProtection('hugo@test.local');
	}

	/**
	 * @test
	 */
	public function refreshBruteForceProtectionStatusForUser_simulation()
	{
		$this->failedLoginRepository = null;

		$sut = $this->sut();

		$sut->refreshBruteForceProtectionStatusForUser('test', false);
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

		$sut->refreshBruteForceProtectionStatusForUser('test', true);
	}

	/**
	 * @test
	 */
	public function refreshBruteForceProtectionStatusForUser_unsuccessfulLogin_withLessLoginAttempts()
	{
		$sut = $this->sut();

		$this->failedLoginRepository->expects($this->once())
			->method('increaseLoginAttempts')
			->with('test');

		$this->failedLoginRepository->expects($this->once())
			->method('findLoginAttempts')
			->with('test')
			->willReturn(1);

        $this->configuration->expects($this->once())
            ->method('getOptionValue')
            ->with(NextADInt_Adi_Configuration_Options::MAX_LOGIN_ATTEMPTS)
            ->willReturn(3);

		$this->failedLoginRepository->expects($this->never())
			->method('blockUser');

		$sut->refreshBruteForceProtectionStatusForUser('test', false);
	}

	/**
	 * @test
	 */
	public function refreshBruteForceProtectionStatusForUser_unsuccessfulLogin_withTooManyLoginAttempts()
	{
		$sut = $this->sut();

		$this->failedLoginRepository->expects($this->once())
			->method('increaseLoginAttempts')
			->with('test');

		$this->failedLoginRepository->expects($this->once())
			->method('findLoginAttempts')
			->with('test')
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
			->with('test', 30);

		$sut->refreshBruteForceProtectionStatusForUser('test', false);
	}

	/**
	 * @issue ADI-256
	 * @test
	 */
	public function ADI256_postAuthentication_whenUserNotCreated_itDoesNotCheckDisabled()
	{
		$sut = $this->sut(array('createOrUpdateUser'));

		$credentials = NextADInt_Adi_Authentication_LoginService::createCredentials('username', 'password');

		$wpUser = (object)(array('ID' => 0 /* invalid id - user has not been created */));

		$sut->expects($this->once())
			->method('createOrUpdateUser')
			->with($credentials)
			->willReturn($wpUser);

		\WP_Mock::wpFunction('is_wp_error', array(
			'times' => 1,
			'return' => false
		));

		$this->userManager->expects($this->never())
			->method('isDisabled');

		$this->assertEquals($wpUser, $sut->postAuthentication($credentials));
	}

	/**
	 * @issue ADI-300
	 * @test
	 */
	public function ADI300_postAuthentication_whenWordPressError_itDoesNotCheckDisabled()
	{
		$sut = $this->sut(array('createOrUpdateUser'));

		$credentials = NextADInt_Adi_Authentication_LoginService::createCredentials('username', 'password');

		$wpUser = (object)(array('ID' => 1));

		$sut->expects($this->once())
			->method('createOrUpdateUser')
			->with($credentials)
			->willReturn($wpUser);

		\WP_Mock::wpFunction('is_wp_error', array(
			'args'   => array($wpUser),
			'times'  => 1,
			'return' => true,
		));

		$this->userManager->expects($this->never())
			->method('isDisabled');

		$this->assertEquals($wpUser, $sut->postAuthentication($credentials));
	}

	/**
	 * @test
	 */
	public function postAuthentication_itReturnsFalse_whenUserIsDisabled()
	{
		$sut = $this->sut(array('createOrUpdateUser'));

		$credentials = NextADInt_Adi_Authentication_LoginService::createCredentials('username', 'password');

		$wpUser = (object)(array('ID' => 666));
		$sut->expects($this->once())
			->method('createOrUpdateUser')
			->with($credentials)
			->willReturn($wpUser);

		$this->userManager->expects($this->once())
			->method('isDisabled')
			->with(666)
			->willReturn(true);

		$this->assertEquals(false, $sut->postAuthentication($credentials));
	}

	/**
	 * @test
	 */
	public function postAuthentication_itReturnsWpUser_whenCreateOrUpdateSucceeds()
	{
		$sut = $this->sut(array('createOrUpdateUser'));

		$credentials = NextADInt_Adi_Authentication_LoginService::createCredentials('username', 'password');

		$wpUser = (object)(array('ID' => 666));
		$sut->expects($this->once())
			->method('createOrUpdateUser')
			->with($credentials)
			->willReturn($wpUser);

		$this->userManager->expects($this->once())
			->method('isDisabled')
			->with(666)
			->willReturn(false);

		$this->assertEquals($wpUser, $sut->postAuthentication($credentials));
	}

	/**
	 * @test
	 */
	public function createOrUpdateUser_itUsesUserPrincipalNameToFindLdapAttributes()
	{
		$sut = $this->sut(array('createUser', 'updateUser'));
		$credentials = NextADInt_Adi_Authentication_LoginService::createCredentials('username@test.ad', 'password');

		\WP_Mock::wpFunction('is_wp_error', array('returns' => false));

		$ldapAttributes = new NextADInt_Ldap_Attributes();

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$this->behave($this->userManager, 'createAdiUser', $adiUser);
		$this->behave($adiUser, 'getId', 666);
		$this->behave($adiUser, 'getLdapAttributes', new NextADInt_Ldap_Attributes());

		$this->attributeService->expects($this->once())
			->method('findLdapAttributesOfUser')
			->with($credentials)
			->willReturn($ldapAttributes);

		$sut->createOrUpdateUser($credentials);
	}

	/**
	 * @test
	 * @issue ADI-395
	 */
	public function ADI_395_createOrUpdateUser_itReturnsFalse_whenLdapAttributesCouldNotBeLoaded() {
		$sut = $this->sut(array('createAdiUser'));
		$credentials = NextADInt_Adi_Authentication_LoginService::createCredentials('username@test.ad', 'password');
		$ldapAttributes = new NextADInt_Ldap_Attributes(false /* failed */, array());

		$this->attributeService->expects($this->once())
			->method('findLdapAttributesOfUser')
			->with($credentials)
			->willReturn($ldapAttributes);

		// createAdiUser must not be reached
		$this->userManager->expects($this->never())
			->method('createAdiUser');

		$actual = $sut->createOrUpdateUser($credentials);
		
		// return value is false
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function createOrUpdateUser_itUpdatesTheSAMAccountName()
	{
		$sut = $this->sut(array('createUser', 'updateUser'));
		$credentials = NextADInt_Adi_Authentication_LoginService::createCredentials('username@test.ad', 'password');

		\WP_Mock::wpFunction('is_wp_error', array('returns' => false));

		$ldapAttributes = new NextADInt_Ldap_Attributes(array(), array('samaccountname' => 'new_sammaccountname'));

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$this->behave($this->attributeService, 'findLdapAttributesOfUser', $ldapAttributes);
		$this->behave($this->userManager, 'createAdiUser', $adiUser);
		$this->behave($adiUser, 'getId', 666);
		$this->behave($adiUser, 'getLdapAttributes', new NextADInt_Ldap_Attributes());

		$sut->createOrUpdateUser($credentials);

		$this->assertEquals('new_sammaccountname', $credentials->getSAMAccountName());
	}

	/**
	 * @test
	 */
	public function createOrUpdateUser_ifUserExists_itCallsUpdateUser()
	{
		$sut = $this->sut(array('createUser', 'updateUser'));
		$credentials = NextADInt_Adi_Authentication_LoginService::createCredentials('username', 'password');

		\WP_Mock::wpFunction('is_wp_error',
			array(
				'args' => 555,
				'returns' => false
			)
		);

		$ldapAttributes = new NextADInt_Ldap_Attributes();

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$this->behave($this->attributeService, 'findLdapAttributesOfUser', $ldapAttributes);
		$this->behave($adiUser, 'getId', 666);
		$this->behave($adiUser, 'getLdapAttributes', new NextADInt_Ldap_Attributes());

		$this->userManager->expects($this->once())
			->method('createAdiUser')
			->with($credentials, $ldapAttributes)
			->willReturn($adiUser);

		$sut->expects($this->once())
			->method('updateUser')
			->willReturn(555);

		$actual = $sut->createOrUpdateUser($credentials);

		$this->assertEquals(555, $actual);
	}

	/**
	 * @test
	 */
	public function createOrUpdateUser_ifUserDoesNotExists_itCallsCreateUser()
	{
		$sut = $this->sut(array('createUser', 'updateUser'));
		$credentials = NextADInt_Adi_Authentication_LoginService::createCredentials('username', 'password');

		\WP_Mock::wpFunction('is_wp_error',
			array(
				'args' => null,
				'returns' => false
			)
		);

		$ldapAttributes = new NextADInt_Ldap_Attributes();

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$this->behave($this->attributeService, 'findLdapAttributesOfUser', $ldapAttributes);
		$this->behave($adiUser, 'getId', 0);
		$this->behave($adiUser, 'getLdapAttributes', new NextADInt_Ldap_Attributes());

		$this->userManager->expects($this->once())
			->method('createAdiUser')
			->with($credentials, $ldapAttributes)
			->willReturn($adiUser);

		$sut->expects($this->once())
			->method('createUser')
			->willReturn(555);

		$actual = $sut->createOrUpdateUser($credentials);

		$this->assertEquals(555, $actual);
	}

	/**
	 * @test
	 */
	public function createOrUpdateUser_updateDomainSID() {
		$domainSID = 'S-1-5-21-1372432699-1244323441-1038535101';
		$credentials = NextADInt_Adi_Authentication_LoginService::createCredentials('username', 'password');

		$this->ldapConnection->expects($this->exactly(1))
			->method('getDomainSid')
			->willReturn($domainSID);

		$sut = $this->sut(array('createUser', 'updateUser'));
		$this->behave($this->attributeService, 'findLdapAttributesOfUser', new NextADInt_Ldap_Attributes());

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$this->behave($this->userManager, 'createAdiUser', $adiUser);

		$ldapAttributes = $this->createMock('NextADInt_Ldap_Attributes');
		$this->behave($adiUser, 'getLdapAttributes', $ldapAttributes);

		$ldapAttributes->expects($this->once())
			->method('setDomainSid')
			->with($domainSID);

		$sut->createOrUpdateUser($credentials);
	}

	/**
	 * @test
	 */
	public function createUser_returnsFalse_ifSimulated()
	{
		$sut = $this->sut();

		$credentials = new NextADInt_Adi_Authentication_Credentials("username@test.ad");
		$adiUser = new NextADInt_Adi_User($credentials, new NextADInt_Ldap_Attributes());

		$this->configuration->expects($this->exactly(1))
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::AUTO_CREATE_USER)
			->willReturn(true);

		$actual = $sut->createUser($adiUser);

		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function createUser_returnsWpError_ifAutoCreateIsDisabled()
	{
		$sut = $this->sut();

		$credentials = new NextADInt_Adi_Authentication_Credentials("username@test.ad");
		$adiUser = new NextADInt_Adi_User($credentials, new NextADInt_Ldap_Attributes());

		$this->behave($this->configuration, 'getOptionValue', false);

		$this->userManager->expects($this->never())
			->method('create');

		$actual = $sut->createUser($adiUser);

		$this->assertTrue($actual instanceof WP_Error);
		$this->assertEquals('invalid_username', $actual->getErrorKey());
	}

	/**
	 * @test
	 */
	public function createUser_ifAutoCreateUserIsEnabled_itCreatesTheUser()
	{
		$sut = $this->sut();

		$username = "USERNAME";

		$roleMapping = new NextADInt_Adi_Role_Mapping($username);
		$credentials = new NextADInt_Adi_Authentication_Credentials("username@test.ad");
		$adiUser = new NextADInt_Adi_User($credentials, new NextADInt_Ldap_Attributes());
		$adiUser->setRoleMapping($roleMapping);

		$this->behave($this->configuration, 'getOptionValue', true);

		$this->userManager->expects($this->once())
			->method('create')
			->with($adiUser)
			->willReturn('666' /* ID */);

		$actual = $sut->createUser($adiUser);

		$this->assertEquals(666/** marker */, $actual);
	}

	/**
	 * @test
	 */
	public function updateUser_ifAutoUpdateIsDisabled_itReturnsFalse()
	{
		$sut = $this->sut();

		$credentials = new NextADInt_Adi_Authentication_Credentials("username@test.ad");
		$adiUser = new NextADInt_Adi_User($credentials, new NextADInt_Ldap_Attributes());

		$this->behave($this->configuration, 'getOptionValue', false);

		$this->userManager->expects($this->never())
			->method('update');

		$actual = $sut->updateUser($adiUser);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function updateUser_ifAutoUpdateIsDisabledButRoleIsAvailable_itStillUpdatesTheUsersRole()
	{
		$sut = $this->sut();

		$username = "USERNAME";
		$suffix = "SUFFIX";

		$roleMapping = new NextADInt_Adi_Role_Mapping($username);
		$credentials = new NextADInt_Adi_Authentication_Credentials("username@test.ad");
		$adiUser = new NextADInt_Adi_User($credentials, new NextADInt_Ldap_Attributes());
		$adiUser->setId(666);
		$wpUser = new WP_User();

		$adiUser->setRoleMapping($roleMapping);

		$this->behave($this->configuration, 'getOptionValue', false);

		$this->userManager->expects($this->never())
			->method('update');

		$this->userManager->expects($this->once())
			->method('findById')
			->with($adiUser->getId())
			->willReturn($wpUser);

		$actual = $sut->updateUser($adiUser);

		$this->assertEquals($wpUser, $actual);
	}

	/**
	 * @test
	 */
	public function updateUser_ifAutoUpdateUserIsEnabled_itUpdatesTheUser()
	{
		$sut = $this->sut();

		$username = "USERNAME";
		$suffix = "SUFFIX";

		$roleMapping = new NextADInt_Adi_Role_Mapping($username);
		$credentials = new NextADInt_Adi_Authentication_Credentials("username@test.ad");
		$adiUser = new NextADInt_Adi_User($credentials, new NextADInt_Ldap_Attributes());

		$adiUser->setRoleMapping($roleMapping);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::AUTO_UPDATE_USER)
			->willReturn(true);

		$this->userManager->expects($this->once())
			->method('update')
			->with($adiUser)
			->willReturn(666 /* ID */);

		$actual = $sut->updateUser($adiUser);

		$this->assertEquals(666/** ID */, $actual);
	}

	/**
	 * @issue ADI-367
	 * @test
	 */
	public function ADI_367_xmlrpcMustBeSecured_whenAllowXmlRpcLoginIsDisabled() {
		$sut = $this->sut();

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
	public function ADI_367_xmlrpcIsAllowed_whenOptionIsConfigured() {
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
				'requiresActiveDirectoryAuthentication',
				'checkXmlRpcAccess'
			)
		);

		$login = "testuser";
		$password = "1234";

		$sut->expects($this->once())
			->method('checkXmlRpcAccess');

		$sut->expects($this->once())
			->method('requiresActiveDirectoryAuthentication')
			->with($login)
			->willReturn(false);

		$actual = $sut->authenticate(null, $login, $password);

		$this->assertFalse($actual);
	}
}