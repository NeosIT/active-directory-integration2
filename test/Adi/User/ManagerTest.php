<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_User_ManagerTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_Configuration_Service|PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/* @var NextADInt_Ldap_Attribute_Service|PHPUnit_Framework_MockObject_MockObject */
	private $attributeService;

	/* @var NextADInt_Adi_User_Helper| PHPUnit_Framework_MockObject_MockObject */
	private $userHelper;

	/* @var NextADInt_Ldap_Attribute_Repository|PHPUnit_Framework_MockObject_MockObject */
	private $attributeRepository;

	/* @var NextADInt_Adi_Role_Manager|PHPUnit_Framework_MockObject_MockObject */
	private $roleManager;

	/** @var NextADInt_Adi_User_Meta_Persistence_Repository|PHPUnit_Framework_MockObject_MockObject */
	private $metaRepository;

	/** @var NextADInt_Adi_User_Persistence_Repository|PHPUnit_Framework_MockObject_MockObject */
	private $userRepository;

	/** @var NextADInt_Core_Util_ExceptionUtil|\Mockery\MockInterface */
	private $exceptionUtil;

	private $userData = array(
		'user_email' => 'test@company.it',
	);
	private $wpUser;
	private $userId = 1;

	/**
	 * @return NextADInt_Adi_User_Manager|PHPUnit_Framework_MockObject_MockObject
	 */
	public function setUp()
	{
		parent::setUp();

		$this->wpUser = $this->createMock('WP_User');

		$this->configuration = $this->createMock('NextADInt_Multisite_Configuration_Service');
		$this->attributeService = $this->createMock('NextADInt_Ldap_Attribute_Service');
		$this->userHelper = $this->createMock('NextADInt_Adi_User_Helper');
		$this->attributeRepository = $this->createMock('NextADInt_Ldap_Attribute_Repository');
		$this->roleManager = $this->createMock('NextADInt_Adi_Role_Manager');
		$this->metaRepository = $this->createMock('NextADInt_Adi_User_Meta_Persistence_Repository');
		$this->userRepository = $this->createMock('NextADInt_Adi_User_Persistence_Repository');

		$this->exceptionUtil = $this->createUtilClassMock('NextADInt_Core_Util_ExceptionUtil');
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @param null $methods
	 *
	 * @return NextADInt_Adi_User_Manager|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		$r = $this->getMockBuilder('NextADInt_Adi_User_Manager')
			->setConstructorArgs(
				array(
					$this->configuration,
					$this->attributeService,
					$this->userHelper,
					$this->attributeRepository,
					$this->roleManager,
					$this->metaRepository,
					$this->userRepository,
				)
			)
			->setMethods($methods)
			->getMock();

		// inject logger
		$r->logger = $this->createMock('Logger');

		return $r;
	}

	/**
	 * @test
	 */
	public function findById_itDelegatesToRepository()
	{
		$sut = $this->sut();

		$this->userRepository->expects($this->once())
			->method('findById')
			->with(1)
			->willReturn($this->wpUser);

		$user = $sut->findById(1);

		$this->assertEquals($this->wpUser, $user);
	}

	/**
	 * @test
	 */
	public function findByUsername_itPrefersSuffix()
	{
		$sut = $this->sut();
		$wpUser = (object)(array('ID' => 1));

		$this->userRepository->expects($this->once())
			->method('findByUsername')
			->with("username@test.ad")
			->willReturn($wpUser);

		$actual = $sut->findByActiveDirectoryUsername("username", "username@test.ad");
		$this->assertEquals($wpUser, $actual);
	}

	/**
	 * @test
	 */
	public function findByActiveDirectoryUsername_itPriroitizesSAMAccountName()
	{
		$sut = $this->sut();
		$wpUser = (object)(array('ID' => 1));

		$this->userRepository->expects($this->once())
			->method('findBySAMAccountName')
			->with("sAMAccountName")
			->willReturn($wpUser);

		$actual = $sut->findByActiveDirectoryUsername("sAMAccountName", "userPrincipalName");
		$this->assertEquals($wpUser, $actual);
	}

	/**
	 * @test
	 */
	public function findByActiveDirectoryUsername_itFallsbackToUserPrincipalName()
	{
		$sut = $this->sut(array('findBySAMAccountName'));
		$wpUser = (object)(array('ID' => 1));

		$this->behave($sut, 'findBySAMAccountName', false);

		$this->userRepository->expects($this->once())
			->method('findByUsername')
			->with("userPrincipalName")
			->willReturn($wpUser);

		$actual = $sut->findByActiveDirectoryUsername("sAMAccountName", "userPrincipalName");
		$this->assertEquals($wpUser, $actual);
	}

	/**
	 * @test
	 */
	public function findByActiveDirectoryUsername_itUsesSAMAccountNameForUserLogin_whenEverythingFails()
	{
		$sut = $this->sut(array('findBySAMAccountName'));
		$wpUser = (object)(array('ID' => 1));

		$this->behave($sut, 'findBySAMAccountName', false);

		$this->userRepository->expects($this->at(1))
			->method('findByUsername')
			->with("userPrincipalName")
			->willReturn(false);

		$this->userRepository->expects($this->at(2))
			->method('findByUsername')
			->with("sAMAccountName")
			->willReturn($wpUser);

		$actual = $sut->findByActiveDirectoryUsername("sAMAccountName", "userPrincipalName");
		$this->assertEquals($wpUser, $actual);
	}


	/**
	 * @test
	 */
	public function isDisabled_itDelegatesToRepository()
	{
		$sut = $this->sut();

		$this->metaRepository->expects($this->once())
			->method('isUserDisabled')
			->willReturn(1)
			->willReturn(true);

		$actual = $sut->isDisabled(1);

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function createAdiUser_itMapsRoles()
	{
		$credentials = new NextADInt_Adi_Authentication_Credentials("username@test.ad", "password");
		$sut = $this->sut();

		$this->roleManager->expects($this->once())
			->method('createRoleMapping')
			->with('username');

		$sut->createAdiUser($credentials, new NextADInt_Ldap_Attributes());
	}

	/**
	 * @test
	 */
	public function createAdiUser_itFindsTheWordPressUser()
	{
		$credentials = new NextADInt_Adi_Authentication_Credentials("username@test.ad", "password");
		$sut = $this->sut(array('findByActiveDirectoryUsername'));

		$wpUser = (object)array('ID' => 1, 'user_login' => 'username');

		$sut->expects($this->once())
			->method('findByActiveDirectoryUsername')
			->with('username', 'username@test.ad')
			->willReturn($wpUser);

		$actual = $sut->createAdiUser($credentials, new NextADInt_Ldap_Attributes());
		$this->assertEquals(1, $actual->getId());
		$this->assertEquals('username', $actual->getUserLogin());
	}

	/**
	 * @test
	 */
	public function createAdiUser_itCopiesCredentialValues()
	{
		$credentials = new NextADInt_Adi_Authentication_Credentials("username@test.ad", "password");
		$sut = $this->sut(array('findByUsername'));
		$ldapAttributes = new NextADInt_Ldap_Attributes();

		$actual = $sut->createAdiUser($credentials, $ldapAttributes);

		$this->assertEquals($credentials, $actual->getCredentials());
		$this->assertEquals($ldapAttributes, $actual->getLdapAttributes());
		$this->assertEquals(null, $actual->getId());

		try {
			$actual->getUserLogin();
			$this->assertTrue(false);
		} catch (Exception $e/** expected: WordPress user has not been loaded */) {
			$this->assertTrue(true);

		}
	}

	/**
	 * @test
	 */
	public function createAdiUser_itFindsUserByObjectGuid()
	{
		$sut = $this->sut(array('findByActiveDirectoryUsername'));

		$wpUser = $this->createMock('WP_User');
		$wpUser->ID = 1;
		$wpUser->user_login = 'username';

		$ldapAttributes = new NextADInt_Ldap_Attributes(array(), array('objectguid' => 'guid1'));
		$credentials = new NextADInt_Adi_Authentication_Credentials("username@test.ad", "password");

		$this->userRepository->expects($this->once())
			->method('findByObjectGuid')
			->with('guid1')
			->willReturn($wpUser);

		$sut->expects($this->never())
			->method('findByActiveDirectoryUsername')
			->with('username', 'username@test.ad');

		$sut->createAdiUser($credentials, $ldapAttributes);
	}

	/**
	 * @test
	 */
	public function createAdiUser_withoutUserFoundByGuid_itFindsUserByActiveDirectoryUsernameAsFallback()
	{
		$sut = $this->sut(array('findByActiveDirectoryUsername'));

		$wpUser = $this->createMock('WP_User');
		$wpUser->ID = 1;
		$wpUser->user_login = 'username';

		$ldapAttributes = new NextADInt_Ldap_Attributes(array(), array('objectguid' => 'guid1'));
		$credentials = new NextADInt_Adi_Authentication_Credentials("username@test.ad", "password");

		$this->userRepository->expects($this->once())
			->method('findByObjectGuid')
			->with('guid1')
			->willReturn(false);

		$sut->expects($this->once())
			->method('findByActiveDirectoryUsername')
			->with('username', 'username@test.ad')
			->willReturn($wpUser);

		$sut->createAdiUser($credentials, $ldapAttributes);
	}

	/**
	 * @test
	 */
	public function create_itUpdatesThePassword()
	{
		$sut = $this->sut(array('checkDuplicateEmail', 'update'));

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$credentials = $this->createMock('NextADInt_Adi_Authentication_Credentials');

		$this->behave($adiUser, 'getLdapAttributes', new NextADInt_Ldap_Attributes());
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($credentials, 'getPassword', 'password');

		$credentials->expects($this->once())
			->method('setPassword')
			->with('new-password');

		$this->userHelper->expects($this->once())
			->method('getPassword')
			->with('password', false)
			->willReturn('new-password');

		$this->userRepository->expects($this->once())
			->method('create')
			->willReturn(100);

		$this->exceptionUtil->shouldReceive('handleWordPressErrorAsException')
			->with(100)
			->once();

		$sut->create($adiUser);
	}

	/**
	 * @test
	 */
	public function create_itUsesUserPrincipalNameAsUserLogin_whenAppendSuffixToNewUserIsEnabled()
	{
		$sut = $this->sut(array('checkDuplicateEmail', 'update', 'appendSuffixToNewUser'));

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$credentials = $this->createMock('NextADInt_Adi_Authentication_Credentials');

		$this->behave($adiUser, 'getLdapAttributes', new NextADInt_Ldap_Attributes());
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($credentials, 'getPassword', 'password');
		$this->behave($credentials, 'getUserPrincipalName', 'userPrincipalName');

		$this->behave($sut, 'appendSuffixToNewUser', true);
		$this->behave($adiUser, 'getLdapAttributes', new NextADInt_Ldap_Attributes());
		$this->behave($this->userRepository, 'create', 100);

		$adiUser->expects($this->once())
			->method('setUserLogin')
			->with('userPrincipalName');

		$this->exceptionUtil->shouldReceive('handleWordPressErrorAsException')
			->with(100)
			->once();

		$sut->create($adiUser);
	}

	/**
	 * @test
	 */
	public function create_itChecksForDuplicateMail()
	{
		$sut = $this->sut(array('checkDuplicateEmail', 'update', 'appendSuffixToNewUser'));

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$credentials = $this->createMock('NextADInt_Adi_Authentication_Credentials');
		$ldapAttributes = array('email' => 'email@test.ad');

		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($credentials, 'getPassword', 'password');
		$this->behave($adiUser, 'getLdapAttributes', new NextADInt_Ldap_Attributes(array(), $ldapAttributes));
		$this->behave($credentials, 'getUserPrincipalName', 'userprincipalname');
		$this->behave($this->userRepository, 'create', 100);

		$this->userHelper->expects($this->once())
			->method('getEmailAddress')
			->with('userprincipalname', $ldapAttributes)
			->willReturn('email@test.ad');

		$sut->expects($this->once())
			->method('checkDuplicateEmail')
			->with('userprincipalname', 'email@test.ad');

		$this->exceptionUtil->shouldReceive('handleWordPressErrorAsException')
			->with(100)
			->once();

		$sut->create($adiUser);
	}

	/**
	 * @test
	 */
	public function create_itUpdatesTheAdiUsersId()
	{
		$sut = $this->sut(array('checkDuplicateEmail', 'update', 'appendSuffixToNewUser'));

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$credentials = $this->createMock('NextADInt_Adi_Authentication_Credentials');
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($credentials, 'getPassword', 'password');

		$this->behave($adiUser, 'getLdapAttributes', new NextADInt_Ldap_Attributes());
		$this->behave($this->userRepository, 'create', 100);

		$adiUser->expects($this->once())
			->method('setId')
			->with(100);

		$this->exceptionUtil->shouldReceive('handleWordPressErrorAsException')
			->with(100)
			->once();

		$sut->create($adiUser);
	}

	/**
	 * @test
	 */
	public function create_itUpdatesTheDataAfterCreation()
	{
		$sut = $this->sut(array('update'));

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$credentials = $this->createMock('NextADInt_Adi_Authentication_Credentials');
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($credentials, 'getPassword', 'password');

		$this->behave($adiUser, 'getLdapAttributes', new NextADInt_Ldap_Attributes());
		$this->behave($this->userRepository, 'create', 100);

		$sut->expects($this->once())
			->method('update')
			->with($adiUser, false, true);

		$this->exceptionUtil->shouldReceive('handleWordPressErrorAsException')
			->with(100)
			->once();

		$sut->create($adiUser, false, true);
	}

	/**
	 * @test
	 */
	public function appendSuffixToNewUser_itReturnsOptionValueAsBoolean()
	{
		$sut = $this->sut();
		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::USE_SAMACCOUNTNAME_FOR_NEW_USERS)
			->willReturn('1');

		$this->assertTrue($sut->useSamAccountNameForNewUsers());
	}

	/**
	 * @test
	 */
	public function checkDuplicateEmail_withExistingEmail_throwsException()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::DUPLICATE_EMAIL_PREVENTION)
			->willReturn(NextADInt_Adi_User_DuplicateEmailPrevention::PREVENT);

		$this->userRepository->expects($this->once())
			->method('isEmailExisting')
			->with('test@test.com')
			->willReturn(true);

		$this->exceptionUtil->shouldReceive('handleWordPressErrorAsException')
			->once();

		$this->invokeMethod($sut, 'checkDuplicateEmail', array('username', 'test@test.com'));
	}

	/**
	 * @test
	 */
	public function checkDuplicateEmail_withPreventionAndWithoutExistingEmail_doesNotThrowException()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::DUPLICATE_EMAIL_PREVENTION)
			->willReturn(NextADInt_Adi_User_DuplicateEmailPrevention::PREVENT);

		$this->userRepository->expects($this->once())
			->method('isEmailExisting')
			->with('test@test.com')
			->willReturn(false);

		$this->exceptionUtil->shouldReceive('handleWordPressErrorAsException')
			->never();

		$this->invokeMethod($sut, 'checkDuplicateEmail', array('username', 'test@test.com'));
	}

	/**
	 * @test
	 */
	public function checkDuplicateEmail_withoutPreventionAndWithExistingEmail_doesNotThrowException()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::DUPLICATE_EMAIL_PREVENTION)
			->willReturn(NextADInt_Adi_User_DuplicateEmailPrevention::ALLOW);

		$this->userRepository->expects($this->once())
			->method('isEmailExisting')
			->with('test@test.com')
			->willReturn(true);

		$this->exceptionUtil->shouldReceive('handleWordPressErrorAsException')
			->never();

		$this->invokeMethod($sut, 'checkDuplicateEmail', array('username', 'test@test.com'));
	}

	/**
	 * @test
	 */
	public function update_itDisablesEmailNotification()
	{
		$sut = $this->sut(array(
			'disableEmailNotification',
			'assertUserExisting',
			'updateWordPressAccount',
			'updateUserMetaDataFromActiveDirectory',
			'updateEmail',
			'updatePassword',
			'findById',
		));

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$credentials = $this->createMock('NextADInt_Adi_Authentication_Credentials');
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($adiUser, 'getLdapAttributes', new NextADInt_Ldap_Attributes());

		$sut->expects($this->once())
			->method('disableEmailNotification');

		$sut->update($adiUser);
	}

	/**
	 * @test
	 */
	public function update_itRequiresAnExistingUser()
	{
		$sut = $this->sut(array(
			'disableEmailNotification',
			'assertUserExisting',
			'updateWordPressAccount',
			'updateUserMetaDataFromActiveDirectory',
			'updateEmail',
			'updatePassword',
			'findById',
		));

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$credentials = $this->createMock('NextADInt_Adi_Authentication_Credentials');
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($adiUser, 'getLdapAttributes', new NextADInt_Ldap_Attributes());

		$sut->expects($this->once())
			->method('assertUserExisting')
			->with($adiUser);

		$sut->update($adiUser);
	}

	/**
	 * @test
	 */
	public function update_itUpdatesTheWordPressAccount()
	{
		$sut = $this->sut(array(
			'disableEmailNotification',
			'assertUserExisting',
			'updateWordPressAccount',
			'updateUserMetaDataFromActiveDirectory',
			'updateEmail',
			'updatePassword',
			'findById',
		));

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$credentials = $this->createMock('NextADInt_Adi_Authentication_Credentials');
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($adiUser, 'getLdapAttributes', new NextADInt_Ldap_Attributes());

		$sut->expects($this->once())
			->method('updateWordPressAccount')
			->with($adiUser);

		$sut->update($adiUser);
	}

	/**
	 * @test
	 */
	public function update_itWritesTheUserMetaData()
	{
		$sut = $this->sut(array(
			'disableEmailNotification',
			'assertUserExisting',
			'updateWordPressAccount',
			'updateUserMetaDataFromActiveDirectory',
			'updateEmail',
			'updatePassword',
			'findById',
		));

		$rawLdapAttributes = array('cn' => array('common_name'));
		$ldapAttributes = new NextADInt_Ldap_Attributes(array(), $rawLdapAttributes);

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$credentials = $this->createMock('NextADInt_Adi_Authentication_Credentials');
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($adiUser, 'getLdapAttributes', $ldapAttributes);
		$this->behave($adiUser, 'getId', 666);

		$sut->expects($this->once())
			->method('updateUserMetaDataFromActiveDirectory')
			->with(666, $rawLdapAttributes);

		$sut->update($adiUser, false, true /* writeUserMeta */);
	}

	/**
	 * @test
	 */
	public function update_itUpdatesTheAccountSuffix()
	{
		$sut = $this->sut(array(
			'disableEmailNotification',
			'assertUserExisting',
			'updateWordPressAccount',
			'updateUserMetaDataFromActiveDirectory',
			'updateEmail',
			'updatePassword',
			'findById',
		));

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$credentials = $this->createMock('NextADInt_Adi_Authentication_Credentials');
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($adiUser, 'getLdapAttributes', new NextADInt_Ldap_Attributes());
		$this->behave($adiUser, 'getId', 666);
		$this->behave($credentials, 'getUpnSuffix', 'test.ad');

		$this->metaRepository->expects($this->once())
			->method('update')
			->with(666, NEXT_AD_INT_PREFIX . 'account_suffix', '@test.ad');

		$sut->update($adiUser);
	}

	/**
	 * @test
	 */
	public function update_itUpdatesTheEmailAddress()
	{
		$sut = $this->sut(array(
			'disableEmailNotification',
			'assertUserExisting',
			'updateWordPressAccount',
			'updateUserMetaDataFromActiveDirectory',
			'updateEmail',
			'updatePassword',
			'findById',
		));

		$ldapAttributes = new NextADInt_Ldap_Attributes();

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$credentials = $this->createMock('NextADInt_Adi_Authentication_Credentials');
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($adiUser, 'getLdapAttributes', $ldapAttributes);
		$this->behave($credentials, 'getSAMAccountName', 'samaccountname');

		$this->userHelper->expects($this->once())
			->method('getEmailAddress')
			->with('samaccountname', $ldapAttributes->getFiltered())
			->willReturn('mail@test.ad');

		$sut->expects($this->once())
			->method('updateEmail')
			->with($adiUser, 'mail@test.ad');

		$sut->update($adiUser);
	}

	/**
	 * @test
	 */
	public function update_itUpdatesThePassword()
	{
		$sut = $this->sut(array(
			'disableEmailNotification',
			'assertUserExisting',
			'updateWordPressAccount',
			'updateUserMetaDataFromActiveDirectory',
			'updateEmail',
			'updatePassword',
			'findById',
		));

		$ldapAttributes = new NextADInt_Ldap_Attributes();

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$credentials = $this->createMock('NextADInt_Adi_Authentication_Credentials');
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($adiUser, 'getLdapAttributes', $ldapAttributes);
		$this->behave($adiUser, 'getId', 666);
		$this->behave($credentials, 'getPassword', 'password');

		$sut->expects($this->once())
			->method('updatePassword')
			->with(666, 'password', true);

		$sut->update($adiUser, true);
	}

	/**
	 * @test
	 */
	public function update_itReturnsTheUpdatedUser()
	{
		$sut = $this->sut(array(
			'disableEmailNotification',
			'assertUserExisting',
			'updateWordPressAccount',
			'updateUserMetaDataFromActiveDirectory',
			'updateEmail',
			'updatePassword',
			'findById',
		));

		$wpUser = (object)array('ID' => 666);
		$adiUser = $this->createMock('NextADInt_Adi_User');
		$credentials = $this->createMock('NextADInt_Adi_Authentication_Credentials');
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($adiUser, 'getLdapAttributes', new NextADInt_Ldap_Attributes());
		$this->behave($adiUser, 'getId', 666);

		$sut->expects($this->once())
			->method('findById')
			->with(666)
			->willReturn($wpUser);

		$actual = $sut->update($adiUser);
		$this->assertEquals($wpUser, $actual);
	}

	/**
	 * @test
	 */
	public function assertUserExisting_itThrowsException_whenUserIdIsNull()
	{
		$sut = $this->sut();

		$adiUser = $this->createMock('NextADInt_Adi_User');

		$this->exceptionUtil->shouldReceive('handleWordPressErrorAsException')
			->once();

		$this->invokeMethod($sut, 'assertUserExisting', array($adiUser));
	}

	/**
	 * @test
	 */
	public function assertUserExisting_withUserIdSet_shouldNotThrowException()
	{
		$sut = $this->sut();

		$adiUser = $this->createMockWithMethods('NextADInt_Adi_User', array('getId', 'getUsername'));
		$adiUser->expects($this->once())
			->method('getId')
			->willReturn(1);

		$adiUser->expects($this->never())
			->method('getUsername')
			->willReturn('hugo');

		$this->exceptionUtil->shouldReceive('handleWordPressErrorAsException')
			->never();

		$this->invokeMethod($sut, 'assertUserExisting', array($adiUser));
	}

	/**
	 * @test
	 */
	public function updatePassword_withAutoUpdatePasswordTrueAndSyncToWordPressFalse_updatesPassword()
	{
		$sut = $this->sut();

		$userId = 666;
		$password = "password";

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::AUTO_UPDATE_PASSWORD)
			->willReturn(true);

		$this->userRepository->expects($this->once())
			->method('updatePassword')
			->with($userId, $password);

		$this->invokeMethod($sut, 'updatePassword', array($userId, $password, false));
	}

	/**
	 * @test
	 */
	public function disableEmailNotification_addsCorrectWordPressFilter()
	{
		$sut = $this->sut();

		WP_Mock::expectFilterAdded('send_password_change_email', '__return_false');
		WP_Mock::expectFilterAdded('send_email_change_email', '__return_false');

		$this->invokeMethod($sut, 'disableEmailNotification');
	}

	/**
	 * @test
	 */
	public function updateWordPressAccount_iiUpdatesAccountInformation()
	{
		$sut = $this->sut(array('updateUserRoles', 'updateSAMAccountName'));

		$userId = 66;
		$roleMapping = new NextADInt_Adi_Role_Mapping("username");

		$attributes = array(
			'objectGUID' => 'guid',
		);

		$credentials = new NextADInt_Adi_Authentication_Credentials('username');
		$adiUser = new NextADInt_Adi_User($credentials, new NextADInt_Ldap_Attributes(array(), $attributes));
		$adiUser->setRoleMapping($roleMapping);
		$adiUser->setId($userId);

		$data = array(
			'ID'           => 66,
			'first_name'   => 'fn1',
			'last_name'    => 'sn2',
			'description'  => 'd3',
			'display_name' => 'dn4',
			'objectGUID'   => 'guid',
		);

		$this->behave($this->userHelper, 'getEnrichedUserData', $data);

		$this->userRepository->expects($this->once())
			->method('update')
			->with($adiUser, $data);

		$sut->expects($this->once())
			->method('updateSAMAccountName')
			->with($userId, "username");

		$sut->expects($this->once())
			->method('updateUserRoles')
			->with($userId, $roleMapping);


		$this->invokeMethod($sut, 'updateWordPressAccount', array($adiUser));
	}

	/**
	 * @test
	 */
	public function updateUserRoles_itDelegatesToRoleManager()
	{
		$sut = $this->sut();

		$roleMapping = $this->createMock('NextADInt_Adi_Role_Mapping');

		$this->userRepository->expects($this->once())
			->method('findById')
			->with(1)
			->willReturn($this->wpUser);

		$this->roleManager->expects($this->once())
			->method('synchronizeRoles')
			->with($this->wpUser, $roleMapping);

		$sut->updateUserRoles(1, $roleMapping);
	}

	/**
	 * @test
	 */
	public function updateUserMetaDataFromActiveDirectory_filtersValues()
	{
		$sut = $this->sut(array('filterDisallowedAttributes', 'filterEmptyAttributes'));

		$telephonenumber = new NextADInt_Ldap_Attribute();
		$telephonenumber->setType('string');
		$telephonenumber->setMetakey('t_n');

		$attributes = array(
			'telephonenumber' => $telephonenumber,
		);

		$ldapAttributes = array(
			'telephonenumber' => '0123456',
		);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::USERMETA_EMPTY_OVERWRITE)
			->willReturn(true);

		$this->behave($this->attributeRepository, 'getWhitelistedAttributes', $attributes);

		$sut->expects($this->once())
			->method('filterDisallowedAttributes')
			->with($ldapAttributes, $attributes)
			->willReturn($ldapAttributes);

		$sut->expects($this->once())
			->method('filterEmptyAttributes')
			->with($ldapAttributes, $attributes, true)
			->willReturn($ldapAttributes);

		$this->metaRepository->expects($this->once())
			->method('update')
			->with(1, 't_n', '0123456');

		$this->invokeMethod($sut, 'updateUserMetaDataFromActiveDirectory', array(1, $ldapAttributes));
	}

	/**
	 * @test
	 */
	public function filterDisallowedAttributes_itFiltersAttributes_whenNotPresentInWhiteList()
	{
		$sut = $this->sut();

		$telephonenumber = new NextADInt_Ldap_Attribute();
		$telephonenumber->setType('string');
		$telephonenumber->setMetakey('t_n');

		$whitelist = array(
			'telephonenumber' => $telephonenumber,
		);

		$ldapAttributes = array(
			'telephonenumber' => '0123456',
			'samAccountName'  => 'newSamAccountName',
		);

		$actual = $this->invokeMethod($sut, 'filterDisallowedAttributes', array($ldapAttributes,
			$whitelist));

		$this->assertEquals(1, count($actual));
		$this->assertEquals($ldapAttributes['telephonenumber'], $actual['telephonenumber']);
	}

	/**
	 * @test
	 */
	public function filterEmptyAttributes_withUserMetaEmptyOverwriteFalse_filtersAttributesWithEmptyValues()
	{
		$sut = $this->sut(null);

		$telephonenumber = new NextADInt_Ldap_Attribute();
		$telephonenumber->setType('string');
		$telephonenumber->setMetakey('t_n');

		$whitelist = array(
			'telephonenumber' => $telephonenumber,
		);

		$ldapAttributes = array(
			'telephonenumber' => '',
		);

		$actual = $this->invokeMethod($sut, 'filterEmptyAttributes', array($ldapAttributes,
			$whitelist, false));

		$this->assertEquals(0, count($actual));
	}

	/**
	 * @test
	 */
	public function filterEmptyAttributes_withUserMetaEmptyOverwriteTrue_doesNotFiltersAttributesWithEmptyValues()
	{
		$sut = $this->sut(null);

		$telephonenumber = new NextADInt_Ldap_Attribute();
		$telephonenumber->setType('string');
		$telephonenumber->setMetakey('t_n');

		$whitelist = array(
			'telephonenumber' => $telephonenumber,
		);

		$ldapAttributes = array(
			'telephonenumber' => '',
		);

		$actual = $this->invokeMethod($sut, 'filterEmptyAttributes', array($ldapAttributes, $whitelist, true));

		$this->assertEquals(1, count($actual));
	}

	/**
	 * @test
	 */
	public function updateEmail_withInvalidEmail_doesNotTriggerAnyRepositoryMethods()
	{
		$sut = $this->sut(array('getEmailForUpdate'));

		$adiUser = $this->createMock('NextADInt_Adi_User');

		WP_Mock::wpFunction('is_email', array(
			'args'   => array('test@test.com'),
			'times'  => 1,
			'return' => false,
		));

		$this->userRepository->expects($this->never())
			->method('findById');

		$this->userRepository->expects($this->never())
			->method('updateEmail');

		$this->invokeMethod($sut, 'updateEmail', array($adiUser, 'test@test.com'));
	}

	/**
	 * @test
	 */
	public function updateEmail_withValidEmailAndDuplicateEmailPrevention_doesNotTriggerUpdateRepositoryMethods()
	{
		$sut = $this->sut(array('getEmailForUpdate'));

		$email = 'test@test.com';
		$this->wpUser->user_email = $email;

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$adiUser->expects($this->once())
			->method('getId')
			->willReturn(1);

		WP_Mock::wpFunction('is_email', array(
			'args'   => array($email),
			'times'  => 1,
			'return' => true,
		));

		$this->userRepository->expects($this->once())
			->method('findById')
			->with(1)
			->willReturn($this->wpUser);

		$sut->expects($this->once())
			->method('getEmailForUpdate')
			->with($this->wpUser, $email)
			->willReturn(false);

		$this->userRepository->expects($this->once())
			->method('findById')
			->with(1);

		$this->userRepository->expects($this->never())
			->method('updateEmail');

		$this->invokeMethod($sut, 'updateEmail', array($adiUser, $email));
	}

	/**
	 * @test
	 */
	public function updateEmail_withValidEmailAndDuplicateEmailAllowed_doesTriggerUpdateRepositoryMethods()
	{
		$sut = $this->sut(array('getEmailForUpdate'));

		$email = 'test@test.com';
		$this->wpUser->user_email = $email;

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$adiUser->expects($this->once())
			->method('getId')
			->willReturn(1);

		WP_Mock::wpFunction('is_email', array(
			'args'   => array($email),
			'times'  => 1,
			'return' => true,
		));

		$this->userRepository->expects($this->once())
			->method('findById')
			->with(1)
			->willReturn($this->wpUser);

		$sut->expects($this->once())
			->method('getEmailForUpdate')
			->with($this->wpUser, $email)
			->willReturn($email);

		$this->userRepository->expects($this->once())
			->method('findById')
			->with(1);

		$this->userRepository->expects($this->once())
			->method('updateEmail')
			->with(1, $email);

		$this->invokeMethod($sut, 'updateEmail', array($adiUser, $email));
	}

	/**
	 * @test
	 */
	public function getEmailForUpdate_withDuplicateEmailPreventionAllow_returnsEmailAndSetsWordPressConstant()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::DUPLICATE_EMAIL_PREVENTION)
			->willReturn(NextADInt_Adi_User_DuplicateEmailPrevention::ALLOW);

		$this->userRepository->expects($this->never())
			->method('isEmailExisting')
			->with('test@test.com')
			->willReturn(false);

		$this->userHelper->expects($this->never())
			->method('createUniqueEmailAddress')
			->with('test@test.com')
			->willReturn('unique@test.com');

		$this->assertFalse(defined('WP_IMPORTING'));

		$actual = $this->invokeMethod($sut, 'getEmailForUpdate', array($this->wpUser, 'test@test.com'));

		$this->assertTrue(defined('WP_IMPORTING'));
		$this->assertTrue(WP_IMPORTING);
		$this->assertEquals('test@test.com', $actual);
	}

	/**
	 * @test
	 */
	public function getEmailForUpdate_withDuplicateEmailPreventionPreventAndNotExistingEmail_returnsEmail()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::DUPLICATE_EMAIL_PREVENTION)
			->willReturn(NextADInt_Adi_User_DuplicateEmailPrevention::PREVENT);

		$this->userRepository->expects($this->once())
			->method('isEmailExisting')
			->with('test@test.com')
			->willReturn(false);

		$this->userHelper->expects($this->never())
			->method('createUniqueEmailAddress')
			->with('test@test.com')
			->willReturn('unique@test.com');

		$actual = $this->invokeMethod($sut, 'getEmailForUpdate', array($this->wpUser, 'test@test.com'));

		$this->assertEquals('test@test.com', $actual);
	}

	/**
	 * @test
	 */
	public function getEmailForUpdate_withDuplicateEmailPreventionCreateAndNotExistingUserEmail_returnsUniqueEmail()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::DUPLICATE_EMAIL_PREVENTION)
			->willReturn(NextADInt_Adi_User_DuplicateEmailPrevention::CREATE);

		$this->userRepository->expects($this->once())
			->method('isEmailExisting')
			->with('test@test.com')
			->willReturn(true);

		$this->userHelper->expects($this->once())
			->method('createUniqueEmailAddress')
			->with('test@test.com')
			->willReturn('unique@test.com');

		$this->wpUser->user_email = null;

		$actual = $this->invokeMethod($sut, 'getEmailForUpdate', array($this->wpUser, 'test@test.com'));

		$this->assertEquals('unique@test.com', $actual);
	}

	/**
	 * @test
	 */
	public function getEmailForUpdate_withDuplicateEmailPreventionCreateAndExistingUserEmail_returnsFalse()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::DUPLICATE_EMAIL_PREVENTION)
			->willReturn(NextADInt_Adi_User_DuplicateEmailPrevention::CREATE);

		$this->userRepository->expects($this->once())
			->method('isEmailExisting')
			->with('test@test.com')
			->willReturn(true);

		$this->userHelper->expects($this->never())
			->method('createUniqueEmailAddress')
			->with('test@test.com')
			->willReturn('unique@test.com');

		$this->wpUser->user_email = 'test@test.com';

		$actual = $this->invokeMethod($sut, 'getEmailForUpdate', array($this->wpUser, 'test@test.com'));

		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function getEmailForUpdate_withAllOptionsFalse_returnsFalse()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::DUPLICATE_EMAIL_PREVENTION)
			->willReturn(NextADInt_Adi_User_DuplicateEmailPrevention::PREVENT);

		$this->userRepository->expects($this->once())
			->method('isEmailExisting')
			->with('test@test.com')
			->willReturn(true);

		$this->userHelper->expects($this->never())
			->method('createUniqueEmailAddress')
			->with('test@test.com')
			->willReturn('unique@test.com');

		$this->wpUser->user_email = 'test@test.com';

		$actual = $this->invokeMethod($sut, 'getEmailForUpdate', array($this->wpUser, 'test@test.com'));

		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function hasActiveDirectoryAccount_acceptsUsername()
	{
		$sut = $this->sut();

		$this->wpUser->ID = 666;

		$this->userRepository->expects($this->once())
			->method('findByUsername')
			->with('username')
			->willReturn($this->wpUser);

		$this->metaRepository->expects($this->once())
			->method('find')
			->with(666, NEXT_AD_INT_PREFIX . 'samaccountname', true)
			->willReturn('usr');

		$this->assertTrue($sut->hasActiveDirectoryAccount('username'));
	}


	/**
	 * @test
	 */
	public function hasActiveDirectoryAccount_acceptsWpUserObject()
	{
		$sut = $this->sut();

		$this->wpUser->ID = 666;

		$this->userRepository->expects($this->never())
			->method('findByUsername')
			->with('username')
			->willReturn($this->wpUser);

		$this->metaRepository->expects($this->once())
			->method('find')
			->with(666, NEXT_AD_INT_PREFIX . 'samaccountname', true)
			->willReturn('usr');

		$this->assertTrue($sut->hasActiveDirectoryAccount($this->wpUser));
	}

	/**
	 * @test
	 */
	public function hasActiveDirectoryAccount_acceptsWpUserId()
	{
		$sut = $this->sut();

		$this->wpUser->ID = 666;

		$this->userRepository->expects($this->never())
			->method('findByUsername')
			->with('username')
			->willReturn($this->wpUser);

		$this->metaRepository->expects($this->once())
			->method('find')
			->with(666, NEXT_AD_INT_PREFIX . 'samaccountname', true)
			->willReturn('usr');

		// co-check empty
		$this->assertTrue($sut->hasActiveDirectoryAccount(666));
	}

	/**
	 * @test
	 */
	public function enable_withEmptyUserEmailAndStoredEmail_restoreEmail()
	{
		$sut = $this->sut();

		$userId = 1;

		$this->wpUser->user_login = 'user';
		$this->wpUser->user_email = '';

		$this->metaRepository->expects($this->once())
			->method('find')
			->with($userId, NEXT_AD_INT_PREFIX . 'user_disabled_email', true)
			->willReturn('test@test.com');

		$this->userRepository->expects($this->once())
			->method('findById')
			->with($userId)
			->willReturn($this->wpUser);

		$this->metaRepository->expects($this->once())
			->method('enableUser')
			->with($this->wpUser);

		$this->userRepository->expects($this->once())
			->method('updateEmail')
			->with($userId, 'test@test.com');

		$sut->enable($userId);
	}

	/**
	 * @test
	 */
	public function enable_withUserEmailNotEmpty_doNotRestoreEmail()
	{
		$sut = $this->sut();

		$userId = 1;

		$this->wpUser->user_login = 'user';
		$this->wpUser->user_email = 'test@test.com';

		$this->metaRepository->expects($this->once())
			->method('find')
			->with($userId, NEXT_AD_INT_PREFIX . 'user_disabled_email', true)
			->willReturn('test@test.com');

		$this->userRepository->expects($this->once())
			->method('findById')
			->with($userId)
			->willReturn($this->wpUser);

		$this->metaRepository->expects($this->once())
			->method('enableUser')
			->with($this->wpUser);

		$this->userRepository->expects($this->never())
			->method('updateEmail')
			->with($userId, 'test@test.com');

		$sut->enable($userId);
	}

	/**
	 * @test
	 */
	public function disable_disablesUserAndRemovesEmail()
	{
		$sut = $this->sut();

		$userId = 1;
		$reason = "Spam";

		$this->userRepository->expects($this->once())
			->method('findById')
			->with($userId)
			->willReturn($this->wpUser);

		$this->metaRepository->expects($this->once())
			->method('disableUser')
			->with($this->wpUser, $reason);

		$this->userRepository->expects($this->once())
			->method('updateEmail')
			->with($userId, '');

		$sut->disable($userId, $reason);
	}

	/**
	 * @test
	 */
	public function migratePreviousVersion_itUpdatesOldSamAccountNames() {
		$sut = $this->sut();
		$wpUsers = array((object)array('ID' => 666));

		$this->userRepository->expects($this->once())
			->method('findByMetaKey')
			->with('adi_samaccountname')
			->willReturn($wpUsers);

		$this->userRepository->expects($this->once())
			->method('findUserMeta')
			->with(666)
			->willReturn(array('adi_samaccountname' => array('username')));

		$this->userRepository->expects($this->once())
			->method('updateSAMAccountName')
			->with(666, 'username');

		$actual = $sut->migratePreviousVersion();

		$this->assertEquals(1, $actual);
	}

	/**
	 * @test
	 */
	public function migratePreviousVersion_itIgnoresSamAccountName_whenAlreadyMigrated() {
		$sut = $this->sut();
		$wpUsers = array((object)array('ID' => 666));

		$this->userRepository->expects($this->once())
			->method('findByMetaKey')
			->with('adi_samaccountname')
			->willReturn($wpUsers);

		$this->userRepository->expects($this->once())
			->method('findUserMeta')
			->with(666)
			->willReturn(array('adi_samaccountname' => array('username'), 'next_ad_int_samaccountname' => array('new_username')));

		$this->userRepository->expects($this->never())
			->method('updateSAMAccountName');

		$actual = $sut->migratePreviousVersion();

		$this->assertEquals(0, $actual);
	}
}