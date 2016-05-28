<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_Synchronization_WordPressTest extends Ut_BasicTest
{
	/* @var Ldap_Connection | PHPUnit_Framework_MockObject_MockObject */
	private $ldapConnection;

	/* @var Multisite_Configuration_Service | PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/* @var Ldap_Attribute_Service | PHPUnit_Framework_MockObject_MockObject */
	private $attributeService;

	/* @var Adi_User_Manager | PHPUnit_Framework_MockObject_MockObject */
	private $userManager;

	/* @var Adi_User_Helper | PHPUnit_Framework_MockObject_MockObject */
	private $userHelper;

	/* @var Adi_Role_Manager | PHPUnit_Framework_MockObject_MockObject */
	private $roleManager;


	public function setUp()
	{
		parent::setUp();

		$this->userManager = $this->createMock('Adi_User_Manager');
		$this->userHelper = $this->createMock('Adi_User_Helper');
		$this->configuration = $this->createMock('Multisite_Configuration_Service');
		$this->ldapConnection = $this->createMock('Ldap_Connection');
		$this->attributeService = $this->createMock('Ldap_Attribute_Service');
		$this->roleManager = $this->createMock('Adi_Role_Manager');
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 *
	 * @return Adi_Synchronization_WordPress| PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('Adi_Synchronization_WordPress')
			->setConstructorArgs(
				array(
					$this->userManager,
					$this->userHelper,
					$this->configuration,
					$this->ldapConnection,
					$this->attributeService,
					$this->roleManager,
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function synchronize_itReturnsFalse_whenErrorsOccured()
	{
		$sut = $this->sut(array('prepareForSync'));

		$sut->expects($this->once())
			->method('prepareForSync')
			->willReturn(false);

		$actual = $sut->synchronize();
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function synchronize_itReturnsTrue_whenMultipleUsersAreSynchronized()
	{
		$sut = $this->sut(
			array(
				'prepareForSync',
				'findSynchronizableUsers',
				'logNumberOfUsers',
				'synchronizeUser',
				'finishSynchronization',
			)
		);

		$users = array('a', 'b', 'c');

		$sut->expects($this->once())
			->method('prepareForSync')
			->willReturn(true);

		$sut->expects($this->once())
			->method('findSynchronizableUsers')
			->willReturn($users);

		$sut->expects($this->once())
			->method('logNumberOfUsers')
			->with($users);

		$usernames = array();
		$call = -1;

		$sut->expects($this->exactly(3))
			->method('synchronizeUser')
			->will($this->returnCallback(function(Adi_Authentication_Credentials $credentials) use (&$usernames, &$call
			) {
				$usernames[] = $credentials->getSAMAccountName();

				return $call++;
			}));

		$sut->expects($this->once())
			->method('finishSynchronization')
			->with(1, 1, 1);

		$actual = $sut->synchronize();
		$this->assertEquals(true, $actual);
		$this->assertEquals($users, $usernames);
	}

	/**
	 * @test
	 */
	public function prepareForSync_syncIsDisabled_returnFalse()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::SYNC_TO_WORDPRESS_ENABLED)
			->willReturn(false);

		$actual = $this->invokeMethod($sut, 'prepareForSync', array());
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function prepareForSync_connectionNotEstablished_returnFalse()
	{
		$sut = $this->sut(array('startTimer', 'connectToAdLdap', 'increaseExecutionTime'));

		$this->configuration->expects($this->exactly(3))
			->method('getOptionValue')
			->withConsecutive(
				array(Adi_Configuration_Options::SYNC_TO_WORDPRESS_ENABLED),
				array(Adi_Configuration_Options::SYNC_TO_WORDPRESS_USER),
				array(Adi_Configuration_Options::SYNC_TO_WORDPRESS_PASSWORD)
			)
			->will($this->onConsecutiveCalls(
				true,
				'user',
				'password'
			));

		$sut->expects($this->once())
			->method('startTimer');

		$sut->expects($this->once())
			->method('connectToAdLdap')
			->with('user', 'password')
			->willReturn(false);

		$actual = $this->invokeMethod($sut, 'prepareForSync', array());
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function prepareForSync_syncIsEnabled_returnTrue()
	{
		$sut = $this->sut(array('startTimer', 'connectToAdLdap', 'increaseExecutionTime'));

		$this->configuration->expects($this->exactly(3))
			->method('getOptionValue')
			->withConsecutive(
				array(Adi_Configuration_Options::SYNC_TO_WORDPRESS_ENABLED),
				array(Adi_Configuration_Options::SYNC_TO_WORDPRESS_USER),
				array(Adi_Configuration_Options::SYNC_TO_WORDPRESS_PASSWORD)
			)
			->will($this->onConsecutiveCalls(
				true,
				'user',
				'password'
			));

		$sut->expects($this->once())
			->method('startTimer');

		$sut->expects($this->once())
			->method('connectToAdLdap')
			->with('user', 'password')
			->willReturn(true);

		$sut->expects($this->once())
			->method('increaseExecutionTime');

		$actual = $this->invokeMethod($sut, 'prepareForSync', array());
		$this->assertEquals(true, $actual);
	}

	/**
	 * @issue ADI-235
	 * @test
	 */
	public function prepareForSync_whenUsernameIsNotInDomain_itReturnsFalse()
	{
		$sut = $this->sut();

		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @test
	 */
	public function findSynchronizableUsers_iReturnsMergedActiveDirectoryAndWordPressUsers()
	{
		$sut = $this->sut(array('findActiveDirectoryUsernames', 'convertActiveDirectoryUsers'));

		$this->ldapConnection->expects($this->once())
			->method('findAllMembersOfGroups')
			->willReturn(array('ad1', 'ad2'));

		$this->behave($sut, 'convertActiveDirectoryUsers',
			array(
				'k2' => 'ad1',
				'k3' => 'ad2',
			)
		);

		$sut->expects($this->once())
			->method('findActiveDirectoryUsernames')
			->willReturn(array(
				'k2' => 'wp1',
				'k1' => 'wp2',
			));

		$expected = array(
			'k1' => 'wp2',
			'k2' => 'ad1',
			'k3' => 'ad2',
		);
		$actual = $this->invokeMethod($sut, 'findSynchronizableUsers', array());
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function convertActiveDirectoryUsers_returnsExpectedFormat()
	{
		$sut = $this->sut();

		$ldapAttributes = $this->createMock('Ldap_Attributes');
		$ldapAttributes->expects($this->exactly(2))
			->method('getFilteredValue')
			->with(Adi_User_Persistence_Repository::META_KEY_OBJECT_GUID)
			->willReturnOnConsecutiveCalls('guid1', 'guid2');

		$this->attributeService->expects($this->exactly(2))
			->method('findLdapAttributesOfUsername')
			->willReturn($ldapAttributes);

		$users = array('test', 'test-user');

		$expected = array('guid1' => 'test', 'guid2' => 'test-user');

		$actual = $this->invokeMethod($sut, 'convertActiveDirectoryUsers', array($users));

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function userAccountControl_withNullArgument_itReturnsZero()
	{
		$sut = $this->sut();

		$this->assertEquals(0, $sut->userAccountControl(null));
	}

	/**
	 * @test
	 */
	public function userAccountControl_withEmptyAttributes_itReturnsZero()
	{
		$sut = $this->sut();

		$this->assertEquals(0, $sut->userAccountControl(array()));
	}

	/**
	 * @test
	 */
	public function userAccountControl_withMissingKey_itReturnsZero()
	{
		$sut = $this->sut();

		$this->assertEquals(0, $sut->userAccountControl(array('k' => 'v')));
	}

	/**
	 * @test
	 */
	public function userAccountControl_withNonArrayOfUseraccountcontrolAttribute_itReturnsZero()
	{
		$sut = $this->sut();

		$this->assertEquals(0, $sut->userAccountControl(array('useraccountcontrol' => 'v')));
	}

	/**
	 * @test
	 */
	public function userAccountControl_withUseraccountcontrolSet_itReturnsValue()
	{
		$sut = $this->sut();

		$this->assertEquals(512, $sut->userAccountControl(array('useraccountcontrol' => array(512))));
	}

	/**
	 * @test
	 */
	public function isNormalAccount_returnsTrue_ifSet()
	{
		$sut = $this->sut();

		$this->assertTrue($sut->isNormalAccount(Adi_Synchronization_WordPress::UF_NORMAL_ACCOUNT));
	}

	/**
	 * @test
	 */
	public function isSmartCardRequired_returnsTrue_ifSet()
	{
		$sut = $this->sut();

		$this->assertTrue($sut->isSmartCardRequired(Adi_Synchronization_WordPress::UF_SMARTCARD_REQUIRED));
	}

	/**
	 * @test
	 */
	public function isAccountDisabled_returnsTrue_ifSet()
	{
		$sut = $this->sut();

		$this->assertTrue($sut->isAccountDisabled(Adi_Synchronization_WordPress::UF_ACCOUNT_DISABLE));
	}

	/**
	 * @test
	 */
	public function logNumberOfUsers_getElapsedTime_logMessages()
	{
		$sut = $this->sut(array('getElapsedTime'));

		$sut->expects($this->once())
			->method('getElapsedTime');

		$this->invokeMethod($sut, 'logNumberOfUsers', array(array()));
	}

	/**
	 * @test
	 */
	public function checkAccountRestrictions_itDisablesTheWordPressAccount_whenActiveDirectoryUserDoesNotExist()
	{
		$sut = $this->sut(null);

		$adiUser = new Adi_User(new Adi_Authentication_Credentials("username"), new Ldap_Attributes());
		$adiUser->setId(666);

		$this->userManager->expects($this->once())
			->method('disable')
			->with(666, $this->stringContains("no longer found"));

		$actual = $sut->checkAccountRestrictions($adiUser);
		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function checkAccountRestrictions_itDisablesTheWordPressAccount_whenActiveDirectoryAccountIsNotNormal()
	{
		$sut = $this->sut(array('isNormalAccount'));

		$adiUser = new Adi_User(new Adi_Authentication_Credentials("username"),
			new Ldap_Attributes(array('key' => 'value')));
		$adiUser->setId(666);

		$this->behave($sut, 'isNormalAccount', false);
		$this->userManager->expects($this->once())
			->method('disable')
			->with(666, $this->stringContains("has no normal"));

		$actual = $sut->checkAccountRestrictions($adiUser);
		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function checkAccountRestrictions_itDisablesTheWordPressAccount_whenActiveDirectoryAccountRequiresSmartCard()
	{
		$sut = $this->sut(array('isNormalAccount', 'isSmartCardRequired'));

		$adiUser = new Adi_User(new Adi_Authentication_Credentials("username"),
			new Ldap_Attributes(array('key' => 'value')));
		$adiUser->setId(666);

		$this->behave($sut, 'isNormalAccount', true);
		$this->behave($sut, 'isSmartCardRequired', true);

		$this->userManager->expects($this->once())
			->method('disable')
			->with(666, $this->stringContains("requires a smart card"));

		$actual = $sut->checkAccountRestrictions($adiUser);
		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function checkAccountRestrictions_itPasses_whenRestrictionsAreFulfilled()
	{
		$sut = $this->sut(array('isNormalAccount', 'isSmartCardRequired'));

		$adiUser = new Adi_User(new Adi_Authentication_Credentials("username"),
			new Ldap_Attributes(array('key' => 'value')));
		$adiUser->setId(666);

		$this->behave($sut, 'isNormalAccount', true);
		$this->behave($sut, 'isSmartCardRequired', false);

		$actual = $sut->checkAccountRestrictions($adiUser);
		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function synchronizeUser_itFindsLdapAttributesOfSAMAccountName()
	{
		$sut = $this->sut(array('checkAccountRestrictions', 'createOrUpdateUser', 'synchronizeAccountStatus',
			'findLdapAttributesOfUser'));

		$this->attributeService->expects($this->once())
			->method('findLdapAttributesOfUser')
			->willReturn(new Ldap_Attributes(array(), array('userprincipalname' => 'username@test.ad')));

		$this->behave($this->userManager, "createAdiUser", $this->createMock('Adi_User'));

		$sut->synchronizeUser(new Adi_Authentication_Credentials("username"), 'guid');
	}

	/**
	 * @test
	 */
	public function synchronizeUser_itUpdatesUserPrincipalName()
	{

		$sut = $this->sut(array('checkAccountRestrictions', 'createOrUpdateUser', 'synchronizeAccountStatus',
			'findLdapAttributesOfUser'));

		$credentials = new Adi_Authentication_Credentials("username");
		$this->attributeService->expects($this->once())
			->method('findLdapAttributesOfUser')
			->willReturn(new Ldap_Attributes(array(), array('userprincipalname' => 'username@test.ad')));

		$this->behave($this->userManager, "createAdiUser", $this->createMock('Adi_User'));

		$sut->synchronizeUser($credentials, 'guid');
		$this->assertEquals("username@test.ad", $credentials->getUserPrincipalName());
	}

	/**
	 * @test
	 */
	public function synchronizeUser_withSynchronizeDisabledAccounts_theAccountRestrictionsAreChecked()
	{
		$sut = $this->sut(array('checkAccountRestrictions', 'findLdapAttributesOfUser'));

		$rawLdapAttributes = array('cn' => 'value');

		$credentials = new Adi_Authentication_Credentials("username");
		$adiUser = new Adi_User($credentials, new Ldap_Attributes());

		$this->attributeService->expects($this->once())
			->method('findLdapAttributesOfUser')
			->willReturn(new Ldap_Attributes(array(), array('userprincipalname' => 'username@test.ad')));

		$this->behave($this->userManager, 'createAdiUser', $adiUser);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::SYNC_TO_WORDPRESS_DISABLE_USERS)
			->willReturn(true);

		$sut->expects($this->once())
			->method('checkAccountRestrictions')
			->with($adiUser)
			->willReturn(false);

		$actual = $sut->synchronizeUser($credentials, 'guid');
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function synchronizeUser_withoutSynchronizeDisabledAccounts_theUserIsCreated()
	{
		$sut = $this->sut(array('checkAccountRestrictions', 'createOrUpdateUser', 'synchronizeAccountStatus',
			'findLdapAttributesOfUser'));

		$credentials = new Adi_Authentication_Credentials("username");
		$adiUser = new Adi_User($credentials, new Ldap_Attributes());

		$this->attributeService->expects($this->once())
			->method('findLdapAttributesOfUser')
			->willReturn(new Ldap_Attributes(array(), array('userprincipalname' => 'username@test.ad')));

		$this->behave($this->userManager, 'createAdiUser', $adiUser);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::SYNC_TO_WORDPRESS_DISABLE_USERS)
			->willReturn(true);

		$sut->expects($this->once())
			->method('checkAccountRestrictions')
			->with($adiUser)
			->willReturn(true);

		$sut->expects($this->once())
			->method('createOrUpdateUser')
			->with($adiUser)
			->willReturn(-1);

		$sut->expects($this->never())
			->method('synchronizeAccountStatus');

		$actual = $sut->synchronizeUser($credentials, 'guid');
		$this->assertEquals(-1, $actual);
	}

	/**
	 * @test
	 */
	public function synchronizeUser_afterCreateOrUpdate_theAccountStatusIsSynchronized()
	{
		$sut = $this->sut(array('checkAccountRestrictions', 'createOrUpdateUser', 'synchronizeAccountStatus',
			'findLdapAttributesOfUser'));

		$credentials = new Adi_Authentication_Credentials("username");
		$adiUser = new Adi_User($credentials, new Ldap_Attributes());

		$this->attributeService->expects($this->once())
			->method('findLdapAttributesOfUser')
			->willReturn(new Ldap_Attributes(array(), array('userprincipalname' => 'username@test.ad')));

		$this->behave($this->userManager, 'createAdiUser', $adiUser);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::SYNC_TO_WORDPRESS_DISABLE_USERS)
			->willReturn(true);

		$sut->expects($this->once())
			->method('checkAccountRestrictions')
			->with($adiUser)
			->willReturn(true);

		$sut->expects($this->once())
			->method('createOrUpdateUser')
			->with($adiUser)
			->willReturn(666);

		$sut->expects($this->once())
			->method('synchronizeAccountStatus')
			->with($adiUser, true);


		$actual = $sut->synchronizeUser($credentials, 'guid');
		$this->assertEquals(666, $actual);
	}


	/**
	 * @issue ADI-235
	 * @test
	 */
	public function synchronizeUser_itAddsDomainSid()
	{
		$sut = $this->sut(array('checkAccountRestrictions', 'createOrUpdateUser', 'synchronizeAccountStatus',
			'findLdapAttributesOfUser'));

		$credentials = new Adi_Authentication_Credentials("username");
		$adiUser = new Adi_User($credentials, new Ldap_Attributes());

		$this->attributeService->expects($this->once())
			->method('findLdapAttributesOfUser')
			->willReturn(new Ldap_Attributes(array(), array('userprincipalname' => 'username@test.ad')));

		$this->behave($this->userManager, 'createAdiUser', $adiUser);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::SYNC_TO_WORDPRESS_DISABLE_USERS)
			->willReturn(true);

		$sut->expects($this->once())
			->method('checkAccountRestrictions')
			->with($adiUser)
			->willReturn(true);

		$sut->expects($this->once())
			->method('createOrUpdateUser')
			->with($adiUser)
			->willReturn(666);

		$sut->expects($this->once())
			->method('synchronizeAccountStatus')
			->with($adiUser, true);

		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);

		$actual = $sut->synchronizeUser($credentials, 'guid');
		$this->assertEquals(666, $actual);
	}

	/**
	 * @test
	 */
	public function synchronizeAccountStatus_ifAccountIsDisabled_theUserIsDisabled()
	{
		$sut = $this->sut(array('userAccountControl', 'isAccountDisabled'));

		$ldapAttributes = array('cn' => array('value'));

		$adiUser = $this->createMock('Adi_User');
		$this->behave($adiUser, 'getLdapAttributes', new Ldap_Attributes($ldapAttributes, array()));
		$this->behave($adiUser, 'getId', 666);
		$this->behave($adiUser, 'getUserLogin', 'username@test.ad');

		$sut->expects($this->once())
			->method('userAccountControl')
			->with($ldapAttributes)
			->willReturn(666);

		$sut->expects($this->once())
			->method('isAccountDisabled')
			->with(666)
			->willReturn(true);

		$this->userManager->expects($this->once())
			->method('disable')
			->with(666, $this->stringContains('is disabled in'));


		$actual = $sut->synchronizeAccountStatus($adiUser, true/* sync disabled accounts */);
		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function synchronizeAccountStatus_ifAccountIsEnabled_theUserIsEnabled()
	{
		$sut = $this->sut(array('userAccountControl', 'isAccountDisabled'));

		$ldapAttributes = array('cn' => array('value'));

		$adiUser = $this->createMock('Adi_User');
		$this->behave($adiUser, 'getLdapAttributes', new Ldap_Attributes($ldapAttributes, array()));
		$this->behave($adiUser, 'getId', 666);
		$this->behave($adiUser, 'getUserLogin', 'username@test.ad');

		$sut->expects($this->once())
			->method('userAccountControl')
			->with($ldapAttributes)
			->willReturn(666);

		$sut->expects($this->once())
			->method('isAccountDisabled')
			->with(666)
			->willReturn(false);

		$this->userManager->expects($this->never())
			->method('disable');

		$this->userManager->expects($this->once())
			->method('enable')
			->with(666);


		$actual = $sut->synchronizeAccountStatus($adiUser, true /* sync disabled accounts */);
		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function createOrUpdateUser_itDelegatesToCreate_whenUserDoesNotExist()
	{
		$sut = $this->sut(null);

		$adiUser = $this->createMock('Adi_User');
		$this->behave($adiUser, 'getId', 0);

		$this->userManager->expects($this->once())
			->method('create')
			->with($adiUser, true)
			->willReturn($adiUser);

		\WP_Mock::wpFunction('is_wp_error', array(
			'args'   => array($adiUser),
			'times'  => 1,
			'return' => false,
		));

		$actual = $this->invokeMethod($sut, 'createOrUpdateUser', array($adiUser));
		$this->assertEquals(0 /* create */, $actual);
	}

	/**
	 * @test
	 */
	public function createOrUpdateUser_itDelegatesToUpdate_whenUserDoesNotExist()
	{
		$sut = $this->sut(null);

		$adiUser = $this->createMock('Adi_User');
		$this->behave($adiUser, 'getId', 666);

		$this->userManager->expects($this->once())
			->method('update')
			->with($adiUser, true)
			->willReturn($adiUser);

		\WP_Mock::wpFunction('is_wp_error', array(
			'args'   => array($adiUser),
			'times'  => 1,
			'return' => false,
		));

		$actual = $this->invokeMethod($sut, 'createOrUpdateUser', array($adiUser));
		$this->assertEquals(1 /* update */, $actual);
	}

	/**
	 * @test
	 */
	public function createOrUpdateUser_itReturnsMinusOne_whenDelegateFailed()
	{
		$sut = $this->sut(null);

		$adiUser = $this->createMock('Adi_User');
		$this->behave($adiUser, 'getId', 666);

		$this->userManager->expects($this->once())
			->method('update')
			->with($adiUser, true)
			->willReturn(array('error' => true));

		\WP_Mock::wpFunction('is_wp_error', array(
			'times'  => 1,
			'return' => true,
		));

		$actual = $this->invokeMethod($sut, 'createOrUpdateUser', array($adiUser));
		$this->assertEquals(-1 /* error */, $actual);
	}

	/**
	 * @test
	 */
	public function finishSynchronization_getElapsedTime_logMessages()
	{
		$sut = $this->sut(array('getElapsedTime'));

		$sut->expects($this->once())
			->method('getElapsedTime');

		$this->invokeMethod($sut, 'finishSynchronization', array(3, 1, 6));
	}
}