<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_Synchronization_WordPressTest extends Ut_BasicTest
{
	/* @var NextADInt_Ldap_Connection | PHPUnit_Framework_MockObject_MockObject */
	private $ldapConnection;

	/* @var NextADInt_Multisite_Configuration_Service | PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/* @var NextADInt_Ldap_Attribute_Service | PHPUnit_Framework_MockObject_MockObject */
	private $attributeService;

	/* @var NextADInt_Adi_User_Manager | PHPUnit_Framework_MockObject_MockObject */
	private $userManager;

	/* @var NextADInt_Adi_User_Helper | PHPUnit_Framework_MockObject_MockObject */
	private $userHelper;

	/* @var NextADInt_Adi_Role_Manager | PHPUnit_Framework_MockObject_MockObject */
	private $roleManager;


	public function setUp(): void
	{
		parent::setUp();

		$this->userManager = $this->createMock('NextADInt_Adi_User_Manager');
		$this->userHelper = $this->createMock('NextADInt_Adi_User_Helper');
		$this->configuration = $this->createMock('NextADInt_Multisite_Configuration_Service');
		$this->ldapConnection = $this->createMock('NextADInt_Ldap_Connection');
		$this->attributeService = $this->createMock('NextADInt_Ldap_Attribute_Service');
		$this->roleManager = $this->createMock('NextADInt_Adi_Role_Manager');

		ob_start();
	}

	public function tearDown(): void
	{
		parent::tearDown();
		ob_end_clean();
	}

	/**
	 *
	 * @return NextADInt_Adi_Synchronization_WordPress| PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Adi_Synchronization_WordPress')
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
			->will($this->returnCallback(function (NextADInt_Adi_Authentication_Credentials $credentials) use (&$usernames, &$call
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
	public function synchronize_catchesException_whenErrorsOccured()
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

		$users = array('a');

		$sut->expects($this->once())
			->method('prepareForSync')
			->willReturn(true);

		$sut->expects($this->once())
			->method('findSynchronizableUsers')
			->willReturn($users);

		$sut->expects($this->once())
			->method('logNumberOfUsers')
			->with($users);

		$sut->expects($this->once())
			->method('synchronizeUser')
			->will($this->throwException(new Exception('bla')));

		$sut->expects($this->once())
			->method('finishSynchronization')
			->with(0, 0, 1);

		$actual = $sut->synchronize();
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function ADI_145_synchronize_itCallsFilter_nextadi_sync_ad2wp_filter_synchronizable_users()
	{
		$sut = $this->sut(
			array(
				'prepareForSync',
				'findSynchronizableUsers',
				'logNumberOfUsers',
			)
		);

		$users = array('a', 'b', 'c');
		$modifiedUsers = array();

		$sut->expects($this->once())
			->method('prepareForSync')
			->willReturn(true);

		$sut->expects($this->once())
			->method('findSynchronizableUsers')
			->willReturn($users);

		\WP_Mock::onFilter(NEXT_AD_INT_PREFIX . 'sync_ad2wp_filter_synchronizable_users')
			->with($users)
			->reply($modifiedUsers);

		$actual = $sut->synchronize();

		// with an empty array (modified by the given filter) the method returns false
		$this->assertEquals(false, $actual);
	}


	/**
	 * @test
	 */
	public function prepareForSync_syncIsDisabled_returnFalse()
	{
		$sut = $this->sut();


		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_ENABLED)
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
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_ENABLED),
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_USER),
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_PASSWORD)
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
		$sut = $this->sut(array('startTimer', 'connectToAdLdap', 'increaseExecutionTime', 'isUsernameInDomain'));

		$this->configuration->expects($this->exactly(3))
			->method('getOptionValue')
			->withConsecutive(
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_ENABLED),
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_USER),
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_PASSWORD)
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

		$sut->expects($this->once())
			->method('isUsernameInDomain')
			->willReturn(true);

		$actual = $this->invokeMethod($sut, 'prepareForSync', array());
		$this->assertEquals(true, $actual);
	}

	/**
	 * @issue ADI-235
	 * @test
	 */
	public function prepareForSync_whenUsernameIsNotInDomain_itReturnsFalse()
	{
		$sut = $this->sut(array('startTimer', 'connectToAdLdap', 'increaseExecutionTime', 'isUsernameInDomain'));

		$this->configuration->expects($this->exactly(3))
			->method('getOptionValue')
			->withConsecutive(
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_ENABLED),
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_USER),
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_PASSWORD)
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
			->method('isUsernameInDomain')
			->willReturn(false);

		$actual = $this->invokeMethod($sut, 'prepareForSync', array());
		$this->assertEquals(false, $actual);
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

		$ldapAttributes = $this->createMock('NextADInt_Ldap_Attributes');
		$ldapAttributes->expects($this->exactly(2))
			->method('getFilteredValue')
			->with(NextADInt_Adi_User_Persistence_Repository::META_KEY_OBJECT_GUID)
			->willReturnOnConsecutiveCalls('guid1', 'guid2');

		$this->attributeService->expects($this->exactly(2))
			->method('findLdapAttributesOfUser')
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

		$actual = $sut->isNormalAccount(NextADInt_Adi_Synchronization_WordPress::UF_NORMAL_ACCOUNT);

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function isNormalAccount_returnsFalse_ifInterdomainTrustAccFlagSet()
	{
		$sut = $this->sut();

		$actual = $sut->isNormalAccount(NextADInt_Adi_Synchronization_WordPress::UF_INTERDOMAIN_TRUST_ACCOUNT);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function isNormalAccount_returnsFalse_ifWorkstationTrustAccFlagSet()
	{
		$sut = $this->sut();

		$actual = $sut->isNormalAccount(NextADInt_Adi_Synchronization_WordPress::UF_WORKSTATION_TRUST_ACCOUNT);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function isNormalAccount_returnsFalse_ifServerTrustAccFlagSet()
	{
		$sut = $this->sut();

		$actual = $sut->isNormalAccount(NextADInt_Adi_Synchronization_WordPress::UF_SERVER_TRUST_ACCOUNT);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function isNormalAccount_returnsFalse_ifMnsLogonAccountFlagSet()
	{
		$sut = $this->sut();

		$actual = $sut->isNormalAccount(NextADInt_Adi_Synchronization_WordPress::UF_MNS_LOGON_ACCOUNT);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function isNormalAccount_returnsFalse_ifUacContainsMultipleForbiddenFlags()
	{
		$sut = $this->sut();

		$actual = $sut->isNormalAccount(NextADInt_Adi_Synchronization_WordPress::NO_UF_NORMAL_ACCOUNT);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function isNormalAccount_returnsFalse_ifPartialSecretsAccountFlagSet()
	{
		$sut = $this->sut();

		$actual = $sut->isNormalAccount(NextADInt_Adi_Synchronization_WordPress::UF_PARTIAL_SECRETS_ACCOUNT);

		$this->assertFalse($actual);
	}


	/**
	 * @test
	 */
	public function isSmartCardRequired_returnsTrue_ifSet()
	{
		$sut = $this->sut();

		$this->assertTrue($sut->isSmartCardRequired(NextADInt_Adi_Synchronization_WordPress::UF_SMARTCARD_REQUIRED));
	}

	/**
	 * @test
	 */
	public function isAccountDisabled_returnsTrue_ifSet()
	{
		$sut = $this->sut();

		$this->assertTrue($sut->isAccountDisabled(NextADInt_Adi_Synchronization_WordPress::UF_ACCOUNT_DISABLE));
	}

	/**
	 * @test
	 */
	public function GH_151_isAccountDisabled_throwsTypeError_withPHP81()
	{
		$sut = $this->sut();
		$this->expectError();
		// regex is required because error messages changed from PHP 7.4 ("must be of THE type int") to PHP >=8.0 ("must be of type int")
		$this->expectErrorMessageMatches('/must be of( the)? type int, string given/');
		$this->assertFalse($sut->isAccountDisabled(""));
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
		$this->mockFunction__();

		$adiUser = new NextADInt_Adi_User(NextADInt_Adi_Authentication_PrincipalResolver::createCredentials("username"), new NextADInt_Ldap_Attributes());
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
		$this->mockFunction__();

		$adiUser = new NextADInt_Adi_User(NextADInt_Adi_Authentication_PrincipalResolver::createCredentials("username"),
			new NextADInt_Ldap_Attributes(array('key' => 'value')));
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
		$this->mockFunction__();

		$adiUser = new NextADInt_Adi_User(NextADInt_Adi_Authentication_PrincipalResolver::createCredentials("username"),
			new NextADInt_Ldap_Attributes(array('key' => 'value')));
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
		$this->mockFunction__();

		$adiUser = new NextADInt_Adi_User(NextADInt_Adi_Authentication_PrincipalResolver::createCredentials("username"),
			new NextADInt_Ldap_Attributes(array('key' => 'value')));
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
			'resolveLdapAttributes'));

		$this->attributeService->expects($this->once())
			->method('resolveLdapAttributes')
			->willReturn(new NextADInt_Ldap_Attributes(array(),
				array(
					'userprincipalname' => 'username@test.ad',
					'objectsid' => 'S-1-5-21-3623811015-3361044348-30300820-1013'
				)));

		$this->behave($this->userManager, "createAdiUser", $this->createMock('NextADInt_Adi_User'));

		$sut->synchronizeUser(NextADInt_Adi_Authentication_PrincipalResolver::createCredentials("username"), 'guid');
	}

	/**
	 * @test
	 */
	public function synchronizeUser_itUpdatesUserPrincipalName()
	{
		$sut = $this->sut(array('checkAccountRestrictions', 'createOrUpdateUser', 'synchronizeAccountStatus',
			'resolveLdapAttributes'));

		$credentials = NextADInt_Adi_Authentication_PrincipalResolver::createCredentials("username");
		$this->attributeService->expects($this->once())
			->method('resolveLdapAttributes')
			->willReturn(new NextADInt_Ldap_Attributes(array(),
				array(
					'userprincipalname' => 'username@test.ad',
					'objectsid' => 'S-1-5-21-3623811015-3361044348-30300820-1013'
				)));

		$this->behave($this->userManager, "createAdiUser", $this->createMock('NextADInt_Adi_User'));

		$sut->synchronizeUser($credentials, 'guid');
		$this->assertEquals("username@test.ad", $credentials->getUserPrincipalName());
	}

	/**
	 * @test
	 */
	public function synchronizeUser_withSynchronizeDisabledAccounts_theAccountRestrictionsAreChecked()
	{
		$sut = $this->sut(array('checkAccountRestrictions', 'resolveLdapAttributes', 'createOrUpdateUser'));

		$credentials = NextADInt_Adi_Authentication_PrincipalResolver::createCredentials("username");
		$adiUser = new NextADInt_Adi_User($credentials, new NextADInt_Ldap_Attributes());

		$this->attributeService->expects($this->once())
			->method('resolveLdapAttributes')
			->willReturn(new NextADInt_Ldap_Attributes(array(), array(
				'userprincipalname' => 'username@test.ad',
				'objectsid' => 'S-1-5-21-3623811015-3361044348-30300820-1013'
			)));

		$this->behave($this->userManager, 'createAdiUser', $adiUser);

		$this->configuration->expects($this->exactly(2))
			->method('getOptionValue')
			->withConsecutive(
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_DISABLE_USERS),
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_IMPORT_DISABLED_USERS)
			)
			->willReturnOnConsecutiveCalls(
				true,
				false
			);

		$sut->expects($this->once())
			->method('checkAccountRestrictions')
			->with($adiUser)
			->willReturn(false);

		$actual = $sut->synchronizeUser($credentials, 'guid');
		$this->assertEquals(1, $actual);
	}

	/**
	 * @test
	 */
	public function synchronizeUser_withoutSynchronizeDisabledAccounts_theUserIsCreated()
	{
		$sut = $this->sut(array('checkAccountRestrictions', 'createOrUpdateUser', 'synchronizeAccountStatus',
			'resolveLdapAttributes'));

		$credentials = NextADInt_Adi_Authentication_PrincipalResolver::createCredentials("username");
		$adiUser = new NextADInt_Adi_User($credentials, new NextADInt_Ldap_Attributes());

		$this->attributeService->expects($this->once())
			->method('resolveLdapAttributes')
			->willReturn(new NextADInt_Ldap_Attributes(array(),
				array(
					'userprincipalname' => 'username@test.ad',
					'objectguid' => '123',
					'objectsid' => 'S-1-5-21-3623811015-3361044348-30300820-1013'
				)));

		$this->behave($this->userManager, 'createAdiUser', $adiUser);

		$this->configuration->expects($this->exactly(2))
			->method('getOptionValue')
			->withConsecutive(
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_DISABLE_USERS),
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_IMPORT_DISABLED_USERS)
			)
			->willReturnOnConsecutiveCalls(
				true,
				false
			);

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
			'resolveLdapAttributes'));

		$credentials = NextADInt_Adi_Authentication_PrincipalResolver::createCredentials("username");
		$adiUser = new NextADInt_Adi_User($credentials, new NextADInt_Ldap_Attributes());

		$this->attributeService->expects($this->once())
			->method('resolveLdapAttributes')
			->willReturn(new NextADInt_Ldap_Attributes(array(), array(
				'userprincipalname' => 'username@test.ad',
				'objectguid' => '123',
				'objectsid' => 'S-1-5-21-3623811015-3361044348-30300820-1013'
			)));

		$this->behave($this->userManager, 'createAdiUser', $adiUser);

		$this->configuration->expects($this->exactly(2))
			->method('getOptionValue')
			->withConsecutive(
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_DISABLE_USERS),
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_IMPORT_DISABLED_USERS)
			)
			->willReturnOnConsecutiveCalls(
				true,
				false
			);

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
			'resolveLdapAttributes'));

		$credentials = NextADInt_Adi_Authentication_PrincipalResolver::createCredentials("username");
		$adiUser = new NextADInt_Adi_User($credentials, new NextADInt_Ldap_Attributes());

		$this->attributeService->expects($this->once())
			->method('resolveLdapAttributes')
			->willReturn(new NextADInt_Ldap_Attributes(array(),
				array(
					'userprincipalname' => 'username@test.ad',
					'objectsid' => 'S-1-5-21-3623811015-3361044348-30300820-1013',
					'objectguid' => 'any-guid'
				)));

		$this->behave($this->userManager, 'createAdiUser', $adiUser);

		$this->configuration->expects($this->exactly(2))
			->method('getOptionValue')
			->withConsecutive(
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_DISABLE_USERS),
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_IMPORT_DISABLED_USERS)
			)
			->willReturnOnConsecutiveCalls(
				true,
				false
			);

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
	 * @issue ADI-145
	 * @test
	 */
	public function ADI_145_synchronizeUser_itCallsFilter_nextadi_sync_ad2wp_filter_user_before_synchronize()
	{
		$sut = $this->sut(array('disableUserWithoutValidGuid', 'checkAccountRestrictions', 'createOrUpdateUser', 'synchronizeAccountStatus',
			'resolveLdapAttributes'));

		$credentials = NextADInt_Adi_Authentication_PrincipalResolver::createCredentials("username");
		$adiUser =  $this->createMock('NextADInt_Adi_User');
		$ldapAttributes = new NextADInt_Ldap_Attributes(array(), array('objectsid' => 'S-1-5-21-3623811015-3361044348-30300820-1013'));
		$modifiedAdiUser = $this->createMock('NextADInt_Adi_User');

		$this->behave($this->userManager, "createAdiUser", $adiUser);

		$this->attributeService->expects($this->once())
			->method('resolveLdapAttributes')
			->willReturn($ldapAttributes);

		$sut->expects($this->once())
			->method('createOrUpdateUser')
			->with($modifiedAdiUser)
			->willReturn(666);

		\WP_Mock::onFilter(NEXT_AD_INT_PREFIX . 'sync_ad2wp_filter_user_before_synchronize')
			->with($adiUser, $credentials, $ldapAttributes)
			->reply($modifiedAdiUser);

		$actual = $sut->synchronizeUser($credentials, 'guid');
		$this->assertEquals(666, $actual);
	}

	/**
	 * @issue ADI-145
	 * @test
	 */
	public function ADI_145_synchronizeUser_itCallsAction_nextadi_sync_wp2ad_after_user_synchronize()
	{
		$sut = $this->sut(array('disableUserWithoutValidGuid', 'checkAccountRestrictions', 'createOrUpdateUser', 'synchronizeAccountStatus',
			'resolveLdapAttributes'));

		$credentials = NextADInt_Adi_Authentication_PrincipalResolver::createCredentials("username");
		$adiUser =  $this->createMock('NextADInt_Adi_User');
		$ldapAttributes = new NextADInt_Ldap_Attributes(array(),
			array(
				'objectsid' => 'S-1-5-21-3623811015-3361044348-30300820-1013'
			));

		$this->behave($this->userManager, "createAdiUser", $adiUser);

		$this->attributeService->expects($this->once())
			->method('resolveLdapAttributes')
			->willReturn($ldapAttributes);

		$sut->expects($this->once())
			->method('createOrUpdateUser')
			->with($adiUser)
			->willReturn(666);

		\WP_Mock::expectAction(NEXT_AD_INT_PREFIX . 'ad2wp_after_user_synchronize', 666, $adiUser, $credentials, $ldapAttributes);

		$actual = $sut->synchronizeUser($credentials, 'guid');
		$this->assertEquals(666, $actual);
	}

	/**
	 * @issue ADI-223
	 * @test
	 */
	public function ADI_223_synchronizeUser_withDoNotSynchronizeDisabledAccounts_skipDisabledUsers()
	{
		$sut = $this->sut(array('checkAccountRestrictions', 'findLdapAttributesOfUser', 'createOrUpdateUser', 'isAccountDisabled'));

		$credentials = NextADInt_Adi_Authentication_PrincipalResolver::createCredentials("username");
		$adiUser = new NextADInt_Adi_User($credentials, new NextADInt_Ldap_Attributes());

		$this->attributeService->expects($this->once())
			->method('resolveLdapAttributes')
			->willReturn(new NextADInt_Ldap_Attributes(array(),
				array(
					'userprincipalname' => 'username@test.ad',
					'objectsid' => 'S-1-5-21-3623811015-3361044348-30300820-1013'
				)));

		$this->behave($this->userManager, 'createAdiUser', $adiUser);

		$this->configuration->expects($this->exactly(2))
			->method('getOptionValue')
			->withConsecutive(
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_DISABLE_USERS),
				array(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_IMPORT_DISABLED_USERS)
			)
			->willReturnOnConsecutiveCalls(
				true,
				false
			);

		$sut->expects($this->once())
			->method('isAccountDisabled')
			->willReturn(true);

		$actual = $sut->synchronizeUser($credentials, 'guid');
		$this->assertEquals(-1, $actual);
	}

	/**
	 * @see #141
	 * @test
	 */
	public function synchronizeUser_itSetsDomainSidOnlyIfUserIsPresent_gh141()
	{
		$sut = $this->sut(array('checkAccountRestrictions', 'createOrUpdateUser', 'synchronizeAccountStatus',
			'resolveLdapAttributes'));

		$credentials = NextADInt_Adi_Authentication_PrincipalResolver::createCredentials("username");
		$this->attributeService->expects($this->once())
			->method('resolveLdapAttributes')
			->willReturn(new NextADInt_Ldap_Attributes(array(),
				array(
					'userprincipalname' => '',
					'objectsid' => ''
				)));

		$this->behave($this->userManager, "createAdiUser", $this->createMock('NextADInt_Adi_User'));

		$sut->synchronizeUser($credentials, 'guid');
		$this->assertEquals("username", $credentials->getUserPrincipalName());
	}
	/**
	 * @test
	 */
	public function synchronizeAccountStatus_ifAccountIsDisabled_theUserIsDisabled()
	{
		$sut = $this->sut(array('userAccountControl', 'isAccountDisabled'));
		$this->mockFunction__();

		$ldapAttributes = array('cn' => array('value'));

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$this->behave($adiUser, 'getLdapAttributes', new NextADInt_Ldap_Attributes($ldapAttributes, array()));
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

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$this->behave($adiUser, 'getLdapAttributes', new NextADInt_Ldap_Attributes($ldapAttributes, array()));
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

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$this->behave($adiUser, 'getId', 0);

		$this->userManager->expects($this->once())
			->method('create')
			->with($adiUser, true)
			->willReturn($adiUser);

		\WP_Mock::wpFunction('is_wp_error', array(
			'args' => array($adiUser),
			'times' => 1,
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

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$this->behave($adiUser, 'getId', 666);

		$this->userManager->expects($this->once())
			->method('update')
			->with($adiUser, true)
			->willReturn($adiUser);

		\WP_Mock::wpFunction('is_wp_error', array(
			'args' => array($adiUser),
			'times' => 1,
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

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$this->behave($adiUser, 'getId', 666);

		$this->userManager->expects($this->once())
			->method('update')
			->with($adiUser, true)
			->willReturn(array('error' => true));

		\WP_Mock::wpFunction('is_wp_error', array(
			'times' => 1,
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

	/**
	 * @test
	 */
	public function disableUserWithoutValidGuid_withNullGuid()
	{

		$sut = $this->sut(array('createOrUpdateUser'));

		$ldapAttributes = $this->createMock('NextADInt_Ldap_Attributes');

		$ldapAttributes->expects($this->once())
			->method('getFilteredValue')
			->with('objectguid')
			->willReturn(null);

		$credentials = NextADInt_Adi_Authentication_PrincipalResolver::createCredentials('username', 'password');
		$adiUser = new NextADInt_Adi_User($credentials, $ldapAttributes);
		$adiUser->setId(123);

		$ldapAttributes->expects($this->once())
			->method('setDomainSid')
			->with('empty');

		$this->userManager->expects($this->once())
			->method('createAdiUser')
			->with($credentials, $ldapAttributes)
			->willReturn($adiUser);

		$sut->expects($this->once())
			->method('createOrUpdateUser')
			->with($adiUser)
			->willReturn(1);

		$this->userManager->expects($this->once())
			->method('disable')
			->with($adiUser->getId(), 'User no longer exists in Active Directory.');

		$actual = $sut->disableUserWithoutValidGuid($ldapAttributes, $credentials);

		$this->assertEquals(1, $actual);
	}

	/**
	 * @test
	 */
	public function disableUserWithoutValidGuid_withValidGuid()
	{
		$ldapAttributes = new NextADInt_Ldap_Attributes(array(), array('objectguid' => '123'));
		$credentials = NextADInt_Adi_Authentication_PrincipalResolver::createCredentials('username', 'password');

		$sut = $this->sut(null);

		$sut->disableUserWithoutValidGuid($ldapAttributes, $credentials);
	}

	/**
	 * GitHub issue #132
	 * @see https://github.com/NeosIT/active-directory-integration2/issues/132
	 * @test
	 */
	public function GH_132_TypeError_withPHP8_0() {
		$sut = $this->sut();
		$this->mockFunction__();

		$attributes = new NextADInt_Ldap_Attributes(array('useraccountcontrol' => ['512']));

		$actual = $sut->userAccountControl($attributes->getRaw());
		$this->assertIsInt($actual);
		$this->assertEquals(512, $actual);
	}
}