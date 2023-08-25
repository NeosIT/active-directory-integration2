<?php

namespace Dreitier\Nadi\User;

use Dreitier\Ldap\Attribute\Attribute;
use Dreitier\Ldap\Attribute\Repository;
use Dreitier\Ldap\Attributes;
use Dreitier\Nadi\Authentication\Credentials;
use Dreitier\Nadi\Authentication\PrincipalResolver;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Role\Mapping;
use Dreitier\Test\BasicTest;
use Dreitier\WordPress\Multisite\Configuration\Service;
use Dreitier\WordPress\WordPressErrorException;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class ManagerTest extends BasicTest
{
	/* @var Service|MockObject */
	private $configuration;

	/* @var \Dreitier\Ldap\Attribute\Service|MockObject */
	private $attributeService;

	/* @var Helper| MockObject */
	private $userHelper;

	/* @var Repository|MockObject */
	private $attributeRepository;

	/* @var \Dreitier\Nadi\Role\Manager|MockObject */
	private $roleManager;

	/** @var \Dreitier\Nadi\User\Meta\Persistence\Repository|MockObject */
	private $metaRepository;

	/** @var \Dreitier\Nadi\User\Persistence\Repository|MockObject */
	private $userRepository;

	/** @var WordPressErrorException|\Mockery\MockInterface */
	private $exceptionUtil;

	private $userData = array(
		'user_email' => 'test@company.it',
	);

	private $wpUser;
	private $userId = 1;

	public function setUp(): void
	{
		parent::setUp();

		$this->wpUser = new \WP_User();

		$this->configuration = $this->createMock(Service::class);
		$this->attributeService = $this->createMock(\Dreitier\Ldap\Attribute\Service::class);
		$this->userHelper = $this->createMock(Helper::class);
		$this->attributeRepository = $this->createMock(Repository::class);
		$this->roleManager = $this->createMock(\Dreitier\Nadi\Role\Manager::class);
		$this->metaRepository = $this->createMock(\Dreitier\Nadi\User\Meta\Persistence\Repository::class);
		$this->userRepository = $this->createMock(\Dreitier\Nadi\User\Persistence\Repository::class);

		$this->exceptionUtil = $this->createUtilClassMock(WordPressErrorException::class);
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @param null $methods
	 *
	 * @return Manager|MockObject
	 */
	public function sut($methods = null)
	{
		$r = $this->getMockBuilder(Manager::class)
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

		$this->userRepository->expects($this->exactly(2))
			->method('findByUsername')
			->withConsecutive(
				['userPrincipalName'],
				['sAMAccountName']
			)
			->willReturnOnConsecutiveCalls(
				false,
				$wpUser
			);

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
		$credentials = PrincipalResolver::createCredentials("username@test.ad", "password");

		$userGuid = 'e16d5d9c-xxxx-xxxx-9b8b-969fdf4b2702';

		$attributes = new Attributes(array(), array('objectguid' => $userGuid));
		$sut = $this->sut();

		$this->roleManager->expects($this->once())
			->method('createRoleMapping')
			->with($userGuid);

		$sut->createAdiUser($credentials, $attributes);
	}

	/**
	 * @test
	 */
	public function createAdiUser_itFindsTheWordPressUser()
	{
		$credentials = PrincipalResolver::createCredentials("username@test.ad", "password");
		$sut = $this->sut(array('findByActiveDirectoryUsername'));

		$wpUser = (object)array('ID' => 1, 'user_login' => 'username');

		$sut->expects($this->once())
			->method('findByActiveDirectoryUsername')
			->with('username', 'username@test.ad')
			->willReturn($wpUser);

		$actual = $sut->createAdiUser($credentials, new Attributes());
		$this->assertEquals(1, $actual->getId());
		$this->assertEquals('username', $actual->getUserLogin());
	}

	/**
	 * @test
	 */
	public function createAdiUser_itCopiesCredentialValues()
	{
		$credentials = PrincipalResolver::createCredentials("username@test.ad", "password");
		$sut = $this->sut(array('findByUsername'));
		$ldapAttributes = new Attributes();

		$actual = $sut->createAdiUser($credentials, $ldapAttributes);

		$this->assertEquals($credentials, $actual->getCredentials());
		$this->assertEquals($ldapAttributes, $actual->getLdapAttributes());
		$this->assertEquals(null, $actual->getId());

		try {
			$actual->getUserLogin();
			$this->assertTrue(false);
		} catch (\Exception $e/** expected: WordPress user has not been loaded */) {
			$this->assertTrue(true);

		}
	}

	/**
	 * @test
	 * @issue NADIS-98, ADI-688
	 * @since 2.1.9
	 */
	public function createAdiUser_itFindsUserByObjectGuid()
	{
		$sut = $this->sut(array('findByActiveDirectoryUsername'));

		$wpUser = $this->createMock(\WP_User::class);
		$wpUser->ID = 1;
		$wpUser->user_login = 'username';

		$ldapAttributes = new Attributes(array(), array('samAccountName' => 'username', 'objectguid' => '666-666'));
		$credentials = PrincipalResolver::createCredentials("username@test.ad", "password");

		$this->userRepository->expects($this->once())
			->method('findByObjectGuid')
			->with('666-666')
			->willReturn($wpUser);

		$this->userRepository->expects($this->never())
			->method('findBySAMAccountName')
			->with('username');

		$sut->expects($this->never())
			->method('findByActiveDirectoryUsername')
			->with('username', 'username@test.ad');

		$sut->createAdiUser($credentials, $ldapAttributes);
	}

	/**
	 * @test
	 */
	public function createAdiUser_itFindsUserBySamAccountName()
	{
		$sut = $this->sut(array('findByActiveDirectoryUsername'));

		$wpUser = new \WP_User();
		$wpUser->ID = 1;
		$wpUser->user_login = 'username';

		$ldapAttributes = new Attributes(array(), array('samAccountName' => 'username'));
		$credentials = PrincipalResolver::createCredentials("username@test.ad", "password");

		$sut->expects($this->once())
			->method('findByActiveDirectoryUsername')
			->with('username', 'username@test.ad');

		$sut->createAdiUser($credentials, $ldapAttributes);
	}

	/**
	 * @test
	 */
	public function createAdiUser_withoutUserFoundBySamAccountName_itFindsUserByActiveDirectoryUsernameAsFallback()
	{
		$sut = $this->sut(array('findByObjectGuid', 'findByActiveDirectoryUsername'));

		$wpUser = $this->createMock(\WP_User::class);
		$wpUser->ID = 1;
		$wpUser->user_login = 'username';

		$ldapAttributes = new Attributes(array(), array('samAccountName' => 'username'));
		$credentials = PrincipalResolver::createCredentials("username@test.ad", "password");

		$this->userRepository->expects($this->once())
			->method('findByObjectGuid')
			->with(null)
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

		$adiUser = $this->createMock(User::class);
		$credentials = $this->createMock(Credentials::class);

		$this->behave($adiUser, 'getLdapAttributes', new Attributes());
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

		$this->exceptionUtil->shouldReceive('processWordPressError')
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

		$adiUser = $this->createMock(User::class);
		$credentials = $this->createMock(Credentials::class);

		$this->behave($adiUser, 'getLdapAttributes', new Attributes());
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($credentials, 'getPassword', 'password');
		$this->behave($credentials, 'getUserPrincipalName', 'userPrincipalName');

		$this->behave($sut, 'appendSuffixToNewUser', true);
		$this->behave($adiUser, 'getLdapAttributes', new Attributes());
		$this->behave($this->userRepository, 'create', 100);

		$adiUser->expects($this->once())
			->method('setUserLogin')
			->with('userPrincipalName');

		$this->exceptionUtil->shouldReceive('processWordPressError')
			->with(100)
			->once();

		$sut->create($adiUser);
	}

	/**
	 * @test
	 */
	public function create_itChecksForDuplicateMail()
	{
		$sut = $this->sut(array('handleEmailAddressOfUser', 'update', 'appendSuffixToNewUser'));

		$adiUser = $this->createMock(User::class);
		$credentials = $this->createMock(Credentials::class);
		$ldapAttributes = array('email' => 'email@test.ad');

		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($credentials, 'getPassword', 'password');
		$this->behave($adiUser, 'getLdapAttributes', new Attributes(array(), $ldapAttributes));
		$this->behave($credentials, 'getUserPrincipalName', 'userprincipalname');
		$this->behave($this->userRepository, 'create', 100);

		$this->userHelper->expects($this->once())
			->method('getEmailAddress')
			->with('userprincipalname', $ldapAttributes)
			->willReturn('email@test.ad');

		$sut->expects($this->once())
			->method('handleEmailAddressOfUser')
			->with(null, 'email@test.ad');

		$this->exceptionUtil->shouldReceive('processWordPressError')
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

		$adiUser = $this->createMock(User::class);
		$credentials = $this->createMock(Credentials::class);
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($credentials, 'getPassword', 'password');

		$this->behave($adiUser, 'getLdapAttributes', new Attributes());
		$this->behave($this->userRepository, 'create', 100);

		$adiUser->expects($this->once())
			->method('setId')
			->with(100);

		$this->exceptionUtil->shouldReceive('processWordPressError')
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

		$adiUser = $this->createMock(User::class);
		$credentials = $this->createMock(Credentials::class);
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($credentials, 'getPassword', 'password');

		$this->behave($adiUser, 'getLdapAttributes', new Attributes());
		$this->behave($this->userRepository, 'create', 100);

		$sut->expects($this->once())
			->method('update')
			->with($adiUser, false, true);

		$this->exceptionUtil->shouldReceive('processWordPressError')
			->with(100)
			->once();

		$sut->create($adiUser, false, true);
	}

	/**
	 * @test
	 * @issue ADI-145
	 */
	public function create_itTriggersCustomHook_user_after_create()
	{
		$sut = $this->sut(array('update'));

		$adiUser = $this->createMock(User::class);
		$credentials = $this->createMock(Credentials::class);
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($credentials, 'getPassword', 'password');

		$this->behave($adiUser, 'getLdapAttributes', new Attributes());
		$this->behave($this->userRepository, 'create', 100);

		$this->exceptionUtil->shouldReceive('processWordPressError')
			->with(100)
			->once();

		\WP_Mock::expectAction(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_after_create', $adiUser, false, true);

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
			->with(Options::USE_SAMACCOUNTNAME_FOR_NEW_USERS)
			->willReturn('1');

		$this->assertTrue($sut->useSamAccountNameForNewUsers());
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

		$adiUser = $this->createMock(User::class);
		$credentials = $this->createMock(Credentials::class);
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($adiUser, 'getLdapAttributes', new Attributes());

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

		$adiUser = $this->createMock(User::class);
		$credentials = $this->createMock(Credentials::class);
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($adiUser, 'getLdapAttributes', new Attributes());

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

		$adiUser = $this->createMock(User::class);
		$credentials = $this->createMock(Credentials::class);
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($adiUser, 'getLdapAttributes', new Attributes());

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
		$ldapAttributes = new Attributes(array(), $rawLdapAttributes);

		$adiUser = $this->createMock(User::class);
		$credentials = $this->createMock(Credentials::class);
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

		$adiUser = $this->createMock(User::class);
		$credentials = $this->createMock(Credentials::class);
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($adiUser, 'getLdapAttributes', new Attributes());
		$this->behave($adiUser, 'getId', 666);
		$this->behave($credentials, 'getUpnSuffix', 'test.ad');

		$this->metaRepository->expects($this->once())
			->method('update')
			->with(666,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'account_suffix', '@test.ad');

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

		$ldapAttributes = new Attributes();

		$adiUser = $this->createMock(User::class);
		$credentials = $this->createMock(Credentials::class);
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
	public function update_itUpdatesThePassword_failed()
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

		$ldapAttributes = new Attributes();

		$adiUser = $this->createMock(User::class);
		$credentials = $this->createMock(Credentials::class);
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($adiUser, 'getLdapAttributes', $ldapAttributes);
		$this->behave($adiUser, 'getId', 666);
		$this->behave($credentials, 'getPassword', 'password');

		$sut->expects($this->never())
			->method('updatePassword');

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
		$adiUser = $this->createMock(User::class);
		$credentials = $this->createMock(Credentials::class);
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($adiUser, 'getLdapAttributes', new Attributes());
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
	 * @issue ADI-145
	 */
	public function update_itTriggersCustomHook_user_before_update()
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

		$adiUser = $this->createMock(User::class);
		$credentials = $this->createMock(Credentials::class);
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($adiUser, 'getLdapAttributes', new Attributes());

		\WP_Mock::expectAction(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_before_update', $adiUser, false, true);

		$sut->update($adiUser);
	}

	/**
	 * @test
	 * @issue ADI-145
	 */
	public function update_itTriggersCustomHook_user_after_update()
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
		$adiUser = $this->createMock(User::class);
		$credentials = $this->createMock(Credentials::class);
		$this->behave($adiUser, 'getCredentials', $credentials);
		$this->behave($adiUser, 'getLdapAttributes', new Attributes());
		$this->behave($adiUser, 'getId', 666);

		$sut->expects($this->once())
			->method('findById')
			->with(666)
			->willReturn($wpUser);

		\WP_Mock::expectAction(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_after_update', $adiUser, $wpUser, false, true);

		$sut->update($adiUser);
	}

	/**
	 * @test
	 */
	public function assertUserExisting_itThrowsException_whenUserIdIsNull()
	{
		$sut = $this->sut();

		$adiUser = $this->createMock(User::class);

		$this->exceptionUtil->shouldReceive('processWordPressError')
			->once();

		$this->invokeMethod($sut, 'assertUserExisting', array($adiUser));
	}

	/**
	 * @test
	 */
	public function assertUserExisting_withUserIdSet_shouldNotThrowException()
	{
		$sut = $this->sut();

		$adiUser = $this->createMockWithMethods(User::class, array('getId', 'getUsername'));
		$adiUser->expects($this->once())
			->method('getId')
			->willReturn(1);

		$adiUser->expects($this->never())
			->method('getUsername')
			->willReturn('hugo');

		$this->exceptionUtil->shouldReceive('processWordPressError')
			->never();

		$this->invokeMethod($sut, 'assertUserExisting', array($adiUser));
	}

	/**
	 * @test
	 */
	public function disableEmailNotification_addsCorrectWordPressFilter()
	{
		$sut = $this->sut();

		\WP_Mock::expectFilterAdded('send_password_change_email', '__return_false');
		\WP_Mock::expectFilterAdded('send_email_change_email', '__return_false');

		$this->invokeMethod($sut, 'disableEmailNotification');
	}

	/**
	 * @test
	 */
	public function updateWordPressAccount_iiUpdatesAccountInformation()
	{
		$sut = $this->sut(array('updateUserRoles', 'updateSAMAccountName'));

		$userId = 66;
		$roleMapping = new Mapping("username");

		$attributes = array(
			'objectGUID' => 'guid',
		);

		$credentials = PrincipalResolver::createCredentials('username');
		$adiUser = new User($credentials, new Attributes(array(), $attributes));
		$adiUser->setRoleMapping($roleMapping);
		$adiUser->setId($userId);

		$data = array(
			'ID' => 66,
			'first_name' => 'fn1',
			'last_name' => 'sn2',
			'description' => 'd3',
			'display_name' => 'dn4',
			'objectGUID' => 'guid',
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

		$roleMapping = $this->createMock(Mapping::class);

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

		$telephonenumber = new Attribute();
		$telephonenumber->setType('string');
		$telephonenumber->setMetakey('t_n');

		$attributes = array(
			'telephonenumber' => $telephonenumber,
		);

		$ldapAttributes = array(
			'telephonenumber' => '0123456',
		);

		$this->behave($this->attributeRepository, 'getWhitelistedAttributes', $attributes);

		$sut->expects($this->once())
			->method('filterDisallowedAttributes')
			->with($ldapAttributes, $attributes)
			->willReturn($ldapAttributes);

		$sut->expects($this->once())
			->method('filterEmptyAttributes')
			->with($ldapAttributes, $attributes)
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

		$telephonenumber = new Attribute();
		$telephonenumber->setType('string');
		$telephonenumber->setMetakey('t_n');

		$whitelist = array(
			'telephonenumber' => $telephonenumber,
		);

		$ldapAttributes = array(
			'telephonenumber' => '0123456',
			'samAccountName' => 'newSamAccountName',
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

		$telephonenumber = new Attribute();
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

		$telephonenumber = new Attribute();
		$telephonenumber->setType('string');
		$telephonenumber->setMetakey('t_n');
		$telephonenumber->setOverwriteWithEmpty('true');

		$whitelist = array(
			'telephonenumber' => $telephonenumber,
		);

		$ldapAttributes = array(
			'telephonenumber' => '',
		);

		$actual = $this->invokeMethod($sut, 'filterEmptyAttributes', array($ldapAttributes, $whitelist));

		$this->assertEquals(1, count($actual));
	}

	/**
	 * @test
	 */
	public function updateEmail_withInvalidEmail_doesNotTriggerAnyRepositoryMethods()
	{
		$sut = $this->sut(array('getEmailForUpdate'));

		$adiUser = $this->createMock(User::class);

		\WP_Mock::wpFunction('is_email', array(
			'args' => array('test@test.com'),
			'times' => 1,
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
	public function updateEmail_notUpdatesEmail_whenAlreadyAssignedToUser()
	{
		$sut = $this->sut(array('handleEmailAddressOfUser'));

		$email = 'test@test.com';
		$this->wpUser->user_email = $email;

		$adiUser = $this->createMock(User::class);
		$adiUser->expects($this->once())
			->method('getId')
			->willReturn(1);

		\WP_Mock::wpFunction('is_email', array(
			'args' => array($email),
			'times' => 1,
			'return' => true,
		));

		$this->userRepository->expects($this->once())
			->method('findById')
			->with(1)
			->willReturn($this->wpUser);

		$sut->expects($this->once())
			->method('handleEmailAddressOfUser')
			->with($this->wpUser, $email)
			->willReturn($email);

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
	public function updateEmail_doesTriggerUpdateRepositoryMethods_whenEmailMustBeSetToNewAddress()
	{
		$sut = $this->sut(array('handleEmailAddressOfUser'));

		$email = 'test@test.com';
		$newEmail = 'new@test.com';
		$this->wpUser->user_email = $email;

		$adiUser = $this->createMock(User::class);
		$adiUser->expects($this->once())
			->method('getId')
			->willReturn(1);

		\WP_Mock::wpFunction('is_email', array(
			'args' => array($email),
			'times' => 1,
			'return' => true,
		));

		$this->userRepository->expects($this->once())
			->method('findById')
			->with(1)
			->willReturn($this->wpUser);

		$sut->expects($this->once())
			->method('handleEmailAddressOfUser')
			->with($this->wpUser, $email)
			->willReturn($newEmail);

		$this->userRepository->expects($this->once())
			->method('findById')
			->with(1);

		$this->userRepository->expects($this->once())
			->method('updateEmail')
			->with(1, $newEmail);

		$this->invokeMethod($sut, 'updateEmail', array($adiUser, $email));
	}

	/**
	 * @test
	 */
	public function handleEmailAddressOfUser_returnsPreferredEmail_whenEmailDoesNotExist()
	{
		$sut = $this->sut();
		$preferredEmail = 'test@test.com';

		$this->userRepository->expects($this->once())
			->method('isEmailExisting')
			->with($preferredEmail)
			->willReturn(false);

		$this->userRepository->expects($this->never())
			->method('findByEmail')
			->with($preferredEmail);

		$actual = $this->invokeMethod($sut, 'handleEmailAddressOfUser', array($this->wpUser, $preferredEmail));
		$this->assertFalse(defined('WP_IMPORTING'));

		$this->assertEquals($preferredEmail, $actual);
	}

	/**
	 * @test
	 */
	public function handleEmailAddressOfUser_returnsPreferredEmail_whenEmailAlreadyBelongsToOwner()
	{
		$sut = $this->sut();
		$preferredEmail = 'test@test.com';

		$this->userRepository->expects($this->once())
			->method('isEmailExisting')
			->with($preferredEmail)
			->willReturn(true);

		$this->userRepository->expects($this->once())
			->method('findByEmail')
			->with($preferredEmail)
			->willReturn($this->wpUser);

		$actual = $this->invokeMethod($sut, 'handleEmailAddressOfUser', array($this->wpUser, $preferredEmail));
		$this->assertFalse(defined('WP_IMPORTING'));

		$this->assertEquals($preferredEmail, $actual);
	}

	/**
	 * @test
	 */
	public function handleEmailAddressOfUser_returnsPreferredEmail_evenIfItIsAlreadyInUse_whenDuplicateEmailPreventionIsAllow()
	{
		$sut = $this->sut();
		$preferredEmail = 'test@test.com';
		$ownerOfMail = $this->createMock(\WP_User::class);
		$ownerOfMail->ID = 555;

		$this->userRepository->expects($this->once())
			->method('isEmailExisting')
			->with($preferredEmail)
			->willReturn(true);

		$this->userRepository->expects($this->once())
			->method('findByEmail')
			->with($preferredEmail)
			->willReturn($ownerOfMail);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::DUPLICATE_EMAIL_PREVENTION)
			->willReturn(DuplicateEmailPrevention::ALLOW);

		$this->assertFalse(defined('WP_IMPORTING'));

		$actual = $this->invokeMethod($sut, 'handleEmailAddressOfUser', array($this->wpUser, 'test@test.com'));
		$this->assertTrue(defined('WP_IMPORTING'));


		$this->assertEquals($preferredEmail, $actual);
	}

	/**
	 * @test
	 */
	public function handleEmailAddressOfUser_throwsException_whenDuplicateEmailPreventionIsPrevent()
	{
		$sut = $this->sut();
		$preferredEmail = 'test@test.com';
		$ownerOfMail = $this->createMock(\WP_User::class);
		$ownerOfMail->ID = 555;

		$this->userRepository->expects($this->once())
			->method('isEmailExisting')
			->with($preferredEmail)
			->willReturn(true);

		$this->userRepository->expects($this->once())
			->method('findByEmail')
			->with($preferredEmail)
			->willReturn($ownerOfMail);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::DUPLICATE_EMAIL_PREVENTION)
			->willReturn(DuplicateEmailPrevention::PREVENT);

		$this->exceptionUtil->shouldReceive('processWordPressError')
			->once()
			->with(\Mockery::on(function (\WP_Error $wpError) {
				return 'duplicateEmailPrevention' == $wpError->getErrorKey();
			}));

		$actual = $this->invokeMethod($sut, 'handleEmailAddressOfUser', array($this->wpUser, 'test@test.com'));
	}

	/**
	 * @test
	 */
	public function handleEmailAddressOfUser_createsNewEmail_whenDuplicateEmailPreventionIsCreate()
	{
		$sut = $this->sut();
		$preferredEmail = 'test@test.com';
		$ownerOfMail = $this->createMock(\WP_User::class);
		$ownerOfMail->ID = 555;

		$this->userRepository->expects($this->once())
			->method('isEmailExisting')
			->with($preferredEmail)
			->willReturn(true);

		$this->userRepository->expects($this->once())
			->method('findByEmail')
			->with($preferredEmail)
			->willReturn($ownerOfMail);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::DUPLICATE_EMAIL_PREVENTION)
			->willReturn(DuplicateEmailPrevention::CREATE);

		$expectedResult = 'unique@test.com';

		\WP_Mock::onFilter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_create_email')->with($this->wpUser, $preferredEmail)->reply($expectedResult);

		$actual = $this->invokeMethod($sut, 'handleEmailAddressOfUser', array($this->wpUser, 'test@test.com'));
		$this->assertEquals($expectedResult, $actual);
	}

	/**
	 * @test
	 */
	public function handleEmailAddressOfUser_throwsGenericException_whenStateMachineStateIsUnknown()
	{
		$sut = $this->sut();
		$preferredEmail = 'test@test.com';
		$ownerOfMail = $this->createMock(\WP_User::class);
		$ownerOfMail->ID = 555;

		$this->userRepository->expects($this->once())
			->method('isEmailExisting')
			->with($preferredEmail)
			->willReturn(true);

		$this->userRepository->expects($this->once())
			->method('findByEmail')
			->with($preferredEmail)
			->willReturn($ownerOfMail);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::DUPLICATE_EMAIL_PREVENTION)
			->willReturn('invalid_state');

		$this->exceptionUtil->shouldReceive('processWordPressError')
			->once()
			->with(\Mockery::on(function (\WP_Error $wpError) {
				return 'invalidDuplicateEmailPreventionState' == $wpError->getErrorKey();
			}));

		$actual = $this->invokeMethod($sut, 'handleEmailAddressOfUser', array($this->wpUser, 'test@test.com'));
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function createNewEmailForExistingAddress_itCreatesEmail_whenUserHasBeenJustCreated()
	{
		$sut = $this->sut();

		$preferredEmail = 'test@test.com';
		$expectedResult = 'unique@test.com';

		$this->userHelper->expects($this->once())
			->method('createUniqueEmailAddress')
			->with($preferredEmail)
			->willReturn($expectedResult);

		$this->wpUser->user_email = null;

		$actual = $this->invokeMethod($sut, 'createNewEmailForExistingAddress', array(null, 'test@test.com'));

		$this->assertEquals($expectedResult, $actual);
	}

	/**
	 * @test
	 */
	public function createNewEmailForExistingAddress_itCreatesEmail_whenUserHasNoEmail()
	{
		$sut = $this->sut();

		$preferredEmail = 'test@test.com';
		$expectedResult = 'unique@test.com';

		$this->userHelper->expects($this->once())
			->method('createUniqueEmailAddress')
			->with($preferredEmail)
			->willReturn($expectedResult);

		$this->wpUser->user_email = null;

		$actual = $this->invokeMethod($sut, 'createNewEmailForExistingAddress', array($this->wpUser, 'test@test.com'));

		$this->assertEquals($expectedResult, $actual);
	}

	/**
	 * @test
	 */
	public function createNewEmailForExistingAddress_itUsesPreferredEmail_asFallback()
	{
		$sut = $this->sut();

		$preferredEmail = 'test@test.com';
		$randomEmail = 'unique@test.com';

		$this->userHelper->expects($this->never())
			->method('createUniqueEmailAddress')
			->with($preferredEmail);

		$this->wpUser->user_email = $randomEmail;

		$actual = $this->invokeMethod($sut, 'createNewEmailForExistingAddress', array($this->wpUser, $preferredEmail));

		$this->assertEquals($preferredEmail, $actual);
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
			->with(666,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'samaccountname', true)
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
			->with(666,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'samaccountname', true)
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
			->with(666,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'samaccountname', true)
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
		$this->wpUser->user_email = 'test@test.com-DISABLED';

		$this->metaRepository->expects($this->once())
			->method('find')
			->with($userId,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_disabled_email', true)
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
			->with($userId,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_disabled_email', true)
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
	 * @issue ADI-145
	 */
	public function ADI_145_enable_itCallsAction_next_ad_int_user_after_enable()
	{
		$sut = $this->sut();

		$userId = 1;

		$this->wpUser->user_login = 'user';
		$this->wpUser->user_email = 'test@test.com-DISABLED';

		$this->metaRepository->expects($this->once())
			->method('find')
			->with($userId,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_disabled_email', true)
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


		\WP_Mock::expectAction(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_after_enable', $this->wpUser, true);

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
		$this->wpUser->user_email = 'test@company.local';

		$this->userRepository->expects($this->once())
			->method('findById')
			->with($userId)
			->willReturn($this->wpUser);

		$this->metaRepository->expects($this->once())
			->method('disableUser')
			->with($this->wpUser, $reason);

		$this->userRepository->expects($this->once())
			->method('updateEmail')
			->with($userId, $this->wpUser->user_email . '-DISABLED');

		$sut->disable($userId, $reason);
	}

	/**
	 * @test
	 * @issue ADI-145
	 */
	public function ADI_145_disable_itCallsAction_next_ad_int_user_after_disable()
	{
		$sut = $this->sut();

		$userId = 1;
		$this->wpUser->user_email = 'test@company.local';
		$reason = "Spam";

		$this->userRepository->expects($this->once())
			->method('findById')
			->with($userId)
			->willReturn($this->wpUser);

		\WP_Mock::expectAction(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_after_disable', $this->wpUser);

		$sut->disable($userId, $reason);
	}

	/**
	 * @test
	 */
	public function migratePreviousVersion_itUpdatesOldSamAccountNames()
	{
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
	public function migratePreviousVersion_itIgnoresSamAccountName_whenAlreadyMigrated()
	{
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


	/**
	 * @test
	 */
	public function isNAdiUser_userIsNAdiUser_SamAndUpnSet_returnTrue()
	{
		$sut = $this->sut(null);

		$wpUser = (object)(array('ID' => 6));

		\WP_Mock::wpFunction('get_user_meta', array(
			'args' => array(6, 'next_ad_int_samaccountname', true),
			'times' => 1,
			'return' => true
		));

		\WP_Mock::wpFunction('get_user_meta', array(
			'args' => array(6, 'next_ad_int_userprincipalname', true),
			'times' => 1,
			'return' => true
		));

		$actual = $sut->isNadiUser($wpUser);
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function isNAdiUser_userIsNoNAdiUser_NoSamOrUpnSet_returnFalse()
	{
		$sut = $this->sut(null);

		$wpUser = (object)(array('ID' => 6));

		\WP_Mock::wpFunction('get_user_meta', array(
			'args' => array(6, 'next_ad_int_samaccountname', true),
			'times' => 1,
			'return' => false
		));

		\WP_Mock::wpFunction('get_user_meta', array(
			'args' => array(6, 'next_ad_int_userprincipalname', true),
			'times' => 1,
			'return' => false
		));

		$actual = $sut->isNadiUser($wpUser);
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function isNAdiUser_userIsNAdiUser_samAccountNameSet_returnTrue()
	{
		$sut = $this->sut(null);

		$wpUser = (object)(array('ID' => 6));

		\WP_Mock::wpFunction('get_user_meta', array(
			'args' => array(6, 'next_ad_int_samaccountname', true),
			'times' => 1,
			'return' => true
		));

		\WP_Mock::wpFunction('get_user_meta', array(
			'args' => array(6, 'next_ad_int_userprincipalname', true),
			'times' => 1,
			'return' => false
		));

		$actual = $sut->isNadiUser($wpUser);
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function isNAdiUser_userIsNAdiUser_userPrincipalNameSet_returnTrue()
	{
		$sut = $this->sut(null);

		$wpUser = (object)(array('ID' => 6));

		\WP_Mock::wpFunction('get_user_meta', array(
			'args' => array(6, 'next_ad_int_samaccountname', true),
			'times' => 1,
			'return' => false
		));

		\WP_Mock::wpFunction('get_user_meta', array(
			'args' => array(6, 'next_ad_int_userprincipalname', true),
			'times' => 1,
			'return' => true
		));

		$actual = $sut->isNadiUser($wpUser);
		$this->assertEquals(true, $actual);
	}
}