<?php

namespace Dreitier\Nadi\User;

use Dreitier\Ldap\Attribute\Service;
use Dreitier\Ldap\Attributes;
use Dreitier\Ldap\Connection;
use Dreitier\Ldap\UserQuery;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\LoginState;
use Dreitier\Nadi\Role\Mapping;
use Dreitier\Test\BasicTest;
use Dreitier\Nadi\Authentication\Credentials;
use PHPUnit\Framework\MockObject\MockObject;

class LoginSucceededServiceTest extends BasicTest
{
	/** @var LoginState|MockObject $loginState */
	private $loginState = null;

	/** @var Service|MockObject $attributeService */
	private $attributeService;

	/** @var Manager|MockObject $userManager */
	private $userManager;

	/** @var Connection|MockObject $ldapConnection */
	private $ldapConnection;

	/** @var \Dreitier\WordPress\Multisite\Configuration\Service|MockObject $configuration */
	private $configuration;

	public function setUp(): void
	{
		parent::setUp();

		$this->loginState = $this->createMock(LoginState::class);
		$this->attributeService = $this->createMock(Service::class);
		$this->userManager = $this->createMock(Manager::class);
		$this->ldapConnection = $this->createMock(Connection::class);
		$this->configuration = $this->createMock(\Dreitier\WordPress\Multisite\Configuration\Service::class);
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @param null $methods
	 * @param bool $simulated
	 *
	 * @return LoginSucceededService|MockObject
	 */
	public function sut($methods = null, $simulated = false)
	{
		return $this->getMockBuilder(LoginSucceededService::class)
			->setConstructorArgs(
				array(
					$this->loginState,
					$this->attributeService,
					$this->ldapConnection,
					$this->configuration,
					$simulated ? null : $this->userManager,
				)
			)->setMethods($methods)
			->getMock();

	}

	/**
	 * @test
	 */
	public function register_willRegisterExpectedFilter()
	{
		$sut = $this->sut();

		\WP_Mock::expectFilterAdded('authenticate', array($sut, 'updateOrCreateAfterSuccessfulLogin'), 19, 3);

		\WP_Mock::expectFilterAdded(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'login_succeeded', array($sut, 'updateOrCreateUser'), 10, 1);

		\WP_Mock::expectFilterAdded(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'auth_before_create_or_update_user',
			array($sut, 'beforeCreateOrUpdateUser'),
			10, 2);

		\WP_Mock::expectFilterAdded(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'auth_after_create_or_update_user',
			array($sut, 'afterCreateOrUpdateUser'), 10,
			3);

		$sut->register();
	}

	/**
	 * @test
	 */
	public function updateOrCreateAfterSuccessfulLogin_willApplyExpectedFilter()
	{
		$sut = $this->sut();
		$credentials = new Credentials('john.doe@test.ad');

		\WP_Mock::onFilter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'login_succeeded')
			->with($credentials)
			->reply(true); // used to verify the filter call with the expected params

		$actual = $sut->updateOrCreateAfterSuccessfulLogin($credentials, null);
		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function updateOrCreateUser_withUnauthenticatedState_returnsFalse()
	{
		$wpUser = new Credentials();
		$sut = $this->sut();

		$this->loginState->expects($this->once())
			->method('isAuthenticated')
			->willReturn(false);

		$this->loginState->expects($this->once())
			->method('isAuthenticated')
			->willReturn(true);

		$actual = $sut->updateOrCreateUser($wpUser);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function updateOrCreateUser_withFailedAuthorizationState_returnsFalse()
	{
		$wpUser = new Credentials();
		$sut = $this->sut();

		$this->loginState->expects($this->once())
			->method('isAuthenticated')
			->willReturn(true);

		$this->loginState->expects($this->once())
			->method('isAuthorized')
			->willReturn(false);

		$actual = $sut->updateOrCreateUser($wpUser);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function updateOrCreateUser_withExistingUser_returnsUser()
	{
		$wpUser = new \WP_User();
		$sut = $this->sut();

		$this->loginState->expects($this->once())
			->method('isAuthenticated')
			->willReturn(true);

		$this->loginState->expects($this->once())
			->method('isAuthorized')
			->willReturn(true);

		$actual = $sut->updateOrCreateUser($wpUser);

		$this->assertEquals($wpUser, $actual);
	}

	/**
	 * @test
	 */
	public function updateOrCreateUser_withValidCredentials_noLdapAttributes_returnsFalse()
	{
		$credentials = new Credentials();
		$sut = $this->sut();

		$this->loginState->expects($this->once())
			->method('isAuthenticated')
			->willReturn(true);

		$this->loginState->expects($this->once())
			->method('isAuthorized')
			->willReturn(true);

		$this->attributeService->expects($this->once())
			->method('resolveLdapAttributes')
			->with($this->callback(function (UserQuery $userQuery) use ($credentials) {
				return $userQuery->getPrincipal() == $credentials->getLogin();
			}))
			->willReturn(new Attributes(false, false));

		$actual = $sut->updateOrCreateUser($credentials);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function updateOrCreateUser_withValidCredentials_preCreateStatusFalse_returnsFalse()
	{
		$credentials = new Credentials();
		$filteredAttrs = array('samaccountname' => 'john.doe');
		$expectedLdapAttrs = new Attributes(array(), $filteredAttrs);
		$sut = $this->sut();

		$this->loginState->expects($this->once())
			->method('isAuthenticated')
			->willReturn(true);

		$this->loginState->expects($this->once())
			->method('isAuthorized')
			->willReturn(true);

		$this->attributeService->expects($this->once())
			->method('resolveLdapAttributes')
			->with($this->callback(function (UserQuery $userQuery) use ($credentials) {
				return $userQuery->getPrincipal() == $credentials->getLogin();
			}))
			->willReturn($expectedLdapAttrs);

		\WP_Mock::onFilter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'auth_before_create_or_update_user')
			->with($credentials, $expectedLdapAttrs)
			->reply(false);

		$actual = $sut->updateOrCreateUser($credentials);

		$this->assertEquals($credentials->getSAMAccountName(), $filteredAttrs['samaccountname']);
		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function updateOrCreateUser_withValidCredentials_errorUpdatingWpUser_returnsWpError()
	{
		$userSid = 'S-1-5-21-7623811015-3361044348-030300820-1013';
		$expectedDomainSid = 'S-1-5-21-7623811015-3361044348-030300820';

		$credentials = new Credentials();
		$filteredAttrs = array('samaccountname' => 'john.doe', 'objectsid' => $userSid);
		$expectedLdapAttrs = new Attributes(array(), $filteredAttrs);

		$adiUserCreds = new Credentials('john.doe@test.ad');
		$adiUserCreds->setUpnUsername('jdo');
		$adiUserCreds->setSAMAccountName('jdo');
		$expectedAdiUser = new User($adiUserCreds, $expectedLdapAttrs);
		$expectedAdiUser->setId(123);

		$expectedErrorMessage = 'Error during update.';
		$expectedError = new \WP_Error($expectedErrorMessage);
		$sut = $this->sut(array('updateUser'));

		$this->loginState->expects($this->once())
			->method('isAuthenticated')
			->willReturn(true);

		$this->loginState->expects($this->once())
			->method('isAuthorized')
			->willReturn(true);

		$this->attributeService->expects($this->once())
			->method('resolveLdapAttributes')
			->with($this->callback(function (UserQuery $userQuery) use ($credentials) {
				return $userQuery->getPrincipal() == $credentials->getLogin();
			}))
			->willReturn($expectedLdapAttrs);

		\WP_Mock::onFilter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'auth_before_create_or_update_user')
			->with($credentials, $expectedLdapAttrs)
			->reply(true);

		$this->userManager->expects($this->once())
			->method('createAdiUser')
			->with($credentials, $expectedLdapAttrs)
			->willReturn($expectedAdiUser);

		$sut->expects($this->once())
			->method('updateUser')
			->with($expectedAdiUser)
			->willReturn($expectedError);

		\WP_Mock::userFunction('is_wp_error', array(
			'args' => array($expectedError),
			'times' => 1,
			'return' => true,
		));

		$actual = $sut->updateOrCreateUser($credentials);

		$this->assertEquals($credentials->getSAMAccountName(), $filteredAttrs['samaccountname']);
		$this->assertEquals($expectedError, $actual);
	}

	/**
	 * @test
	 */
	public function updateOrCreateUser_withValidCredentials_errorCreatingWpUser_returnsWpError()
	{
		$userSid = 'S-1-5-21-7623811015-3361044348-030300820-1013';
		$expectedDomainSid = 'S-1-5-21-7623811015-3361044348-030300820';

		$credentials = new Credentials();
		$filteredAttrs = array('samaccountname' => 'john.doe', 'objectsid' => $userSid);

		$expectedLdapAttrs = new Attributes(array(), $filteredAttrs);

		$adiUserCreds = new Credentials('john.doe@test.ad');
		$adiUserCreds->setUpnUsername('jdo');
		$adiUserCreds->setSAMAccountName('jdo');
		$expectedAdiUser = new User($adiUserCreds, $expectedLdapAttrs);
		$expectedAdiUser->setId(null);

		$expectedErrorMessage = 'Error during update.';
		$expectedError = new \WP_Error($expectedErrorMessage);
		$sut = $this->sut(array('createUser'));

		$this->loginState->expects($this->once())
			->method('isAuthenticated')
			->willReturn(true);

		$this->loginState->expects($this->once())
			->method('isAuthorized')
			->willReturn(true);

		$this->attributeService->expects($this->once())
			->method('resolveLdapAttributes')
			->with($this->callback(function (UserQuery $userQuery) use ($credentials) {
				return $userQuery->getPrincipal() == $credentials->getLogin();
			}))
			->willReturn($expectedLdapAttrs);

		\WP_Mock::onFilter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'auth_before_create_or_update_user')
			->with($credentials, $expectedLdapAttrs)
			->reply(true);

		$this->userManager->expects($this->once())
			->method('createAdiUser')
			->with($credentials, $expectedLdapAttrs)
			->willReturn($expectedAdiUser);

		$sut->expects($this->once())
			->method('createUser')
			->with($expectedAdiUser)
			->willReturn($expectedError);

		\WP_Mock::userFunction('is_wp_error', array(
			'args' => array($expectedError),
			'times' => 1,
			'return' => true,
		));

		$actual = $sut->updateOrCreateUser($credentials);

		$this->assertEquals($credentials->getSAMAccountName(), $filteredAttrs['samaccountname']);
		$this->assertEquals($expectedError, $actual);
	}

	/**
	 * @test
	 */
	public function updateOrCreateUser_withValidCredentials_returnsExpected()
	{
		$userSid = 'S-1-5-21-7623811015-3361044348-030300820-1013';
		$expectedDomainSid = 'S-1-5-21-7623811015-3361044348-030300820';

		$credentials = new Credentials();
		$filteredAttrs = array('samaccountname' => 'john.doe', 'objectsid' => $userSid);
		$expectedLdapAttrs = new Attributes(array(), $filteredAttrs);

		$adiUserCreds = new Credentials('john.doe@test.ad');
		$adiUserCreds->setUpnUsername('jdo');
		$adiUserCreds->setSAMAccountName('jdo');
		$expectedAdiUser = new User($adiUserCreds, $expectedLdapAttrs);
		$expectedAdiUser->setId(null);
		$expectedWpUser = new \WP_User();

		$sut = $this->sut(array('createUser'));

		$this->loginState->expects($this->once())
			->method('isAuthenticated')
			->willReturn(true);

		$this->loginState->expects($this->once())
			->method('isAuthorized')
			->willReturn(true);

		$this->attributeService->expects($this->once())
			->method('resolveLdapAttributes')
			->with($this->callback(function (UserQuery $userQuery) use ($credentials) {
				return $userQuery->getPrincipal() == $credentials->getLogin();
			}))
			->willReturn($expectedLdapAttrs);

		\WP_Mock::onFilter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'auth_before_create_or_update_user')
			->with($credentials, $expectedLdapAttrs)
			->reply(true);

		$this->userManager->expects($this->once())
			->method('createAdiUser')
			->with($credentials, $expectedLdapAttrs)
			->willReturn($expectedAdiUser);

		$sut->expects($this->once())
			->method('createUser')
			->with($expectedAdiUser)
			->willReturn($expectedWpUser);

		\WP_Mock::userFunction('is_wp_error', array(
			'args' => array($expectedWpUser),
			'times' => 1,
			'return' => false,
		));

		\WP_Mock::onFilter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'auth_after_create_or_update_user')
			->with($credentials, $expectedLdapAttrs, $expectedWpUser)
			->reply($expectedWpUser);

		$actual = $sut->updateOrCreateUser($credentials);

		$actualDomainSid = $expectedLdapAttrs->getFilteredValue('domainsid');
		$this->assertEquals($expectedDomainSid, $actualDomainSid);
		$this->assertEquals($filteredAttrs['samaccountname'], $credentials->getSAMAccountName());
		$this->assertEquals(666, $credentials->getWordPressUserId());
		$this->assertEquals($expectedWpUser, $actual);
	}

	/**
	 * @test
	 */
	public function createUser_withValidAdiUser_withSimulatedLogin_returnsFalse()
	{
		/** @var User|MockObject $adiUser */
		$adiUser = $this->createMockWithMethods(User::class, array('getUsername'));

		$adiUser->expects($this->once())
			->method('getUsername')
			->willReturn('jdo');

		$sut = $this->sut(null, true);

		$actual = $sut->createUser($adiUser);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function createUser_withValidAdiUser_returnsWpUser()
	{
		/** @var User|MockObject $adiUser */
		$adiUser = $this->createMockWithMethods(User::class, array('getUsername'));

		$this->userManager->expects($this->once())
			->method('create')
			->with($adiUser)
			->willReturn(new \WP_User);

		$sut = $this->sut();

		$actual = $sut->createUser($adiUser);

		$this->assertEquals(new \WP_User(), $actual);
	}

	/**
	 * @test
	 */
	public function updateUser_withAutoUpdatePassword_withAutoUpdateUser_returnsExpectedWpUser()
	{
		$credentials = new Credentials();
		$credentials->setUserPrincipalName('john.doe@test.ad');
		$credentials->setSAMAccountName('jdo');

		$attributes = new Attributes();

		$adiUser = new User($credentials, $attributes);
		$adiUser->setId(123);

		$expectedWpUser = new \WP_User();

		$this->configuration->expects($this->exactly(2))
			->method('getOptionValue')
			->withConsecutive(
				[Options::AUTO_UPDATE_USER],
				[Options::AUTO_UPDATE_PASSWORD]
			)
			->willReturnOnConsecutiveCalls(
				true,
				true
			);

		$this->userManager->expects($this->once())
			->method('updatePassword')
			->with($adiUser);

		$this->userManager->expects($this->once())
			->method('update')
			->with($adiUser)
			->willReturn($expectedWpUser);

		$sut = $this->sut();

		$actual = $sut->updateUser($adiUser);

		$this->assertEquals($expectedWpUser, $actual);
	}

	/**
	 * @test
	 */
	public function updateUser_withoutAutoUpdatePassword_withoutAutoUpdateUser_returnsExpectedWpUser()
	{
		$credentials = new Credentials();
		$credentials->setUserPrincipalName('john.doe@test.ad');
		$credentials->setSAMAccountName('jdo');

		$attributes = new Attributes();

		$adiUser = new User($credentials, $attributes);
		$adiUser->setId(123);

		$roleMapping = new Mapping('8fa6191f-1473-4189-bac7-950a469ebd7a');
		$adiUser->setRoleMapping($roleMapping);

		$expectedWpUser = new \WP_User();

		$this->configuration->expects($this->exactly(2))
			->method('getOptionValue')
			->withConsecutive(
				[Options::AUTO_UPDATE_USER],
				[Options::AUTO_UPDATE_PASSWORD]
			)
			->willReturnOnConsecutiveCalls(
				false,
				false
			);

		$this->userManager->expects($this->never())
			->method('updatePassword')
			->with($adiUser);

		$this->userManager->expects($this->never())
			->method('update')
			->with($adiUser);

		$this->userManager->expects($this->once())
			->method('updateSAMAccountName')
			->with(123, 'jdo');

		$this->userManager->expects($this->once())
			->method('maybeUpdateObjectGuidIfMissing')
			->with(123, $attributes);

		$this->userManager->expects($this->once())
			->method('updateUserRoles')
			->with(123, $roleMapping);

		$this->userManager->expects($this->once())
			->method('findById')
			->with(123)
			->willReturn($expectedWpUser);

		$sut = $this->sut();

		$actual = $sut->updateUser($adiUser);

		$this->assertEquals($expectedWpUser, $actual);
	}

	/**
	 * @test
	 */
	public function checkUserEnabled_withWpError_returnsWpError()
	{
		$wpError = $this->createMock(\WP_Error::class);
		$sut = $this->sut();

		$actual = $sut->checkUserEnabled($wpError);

		$this->assertEquals($wpError, $actual);
	}

	/**
	 * @test
	 */
	public function checkUserEnabled_withDisabledUser_returnsWpError()
	{
		$wpUserId = 332;
		$wpUser = new \WP_User();
		$wpUser->setID($wpUserId);
		$expectedReason = 'my custom reason.';

		$sut = $this->sut();

		$this->userManager->expects($this->once())
			->method('isDisabled')
			->with($wpUserId)
			->willReturn(true);

		\WP_Mock::userFunction('get_user_meta', array(
			'times' => 1,
			'args' => array($wpUserId,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_disabled_reason', true),
			'return' => $expectedReason
		));

		\WP_Mock::userFunction('remove_filter', array(
			'times' => 2
		));

		$actual = $sut->checkUserEnabled($wpUser);

		$this->assertTrue($actual instanceof \WP_Error);
		$this->assertEquals('user_disabled', $actual->get_error_message());
	}

	/**
	 * @test
	 */
	public function checkUserEnabled_withValidUser_returnsWpUser()
	{
		$wpUserId = 332;
		$wpUser = new \WP_User();
		$wpUser->setID($wpUserId);

		$sut = $this->sut();

		$this->userManager->expects($this->once())
			->method('isDisabled')
			->with($wpUserId)
			->willReturn(false);

		$actual = $sut->checkUserEnabled($wpUser);

		$this->assertEquals($wpUser, $actual);
	}

	/**
	 * @test
	 */
	public function beforeCreateOrUpdateUser_returnsTrue()
	{
		$credentials = new Credentials();
		$attributes = new Attributes();

		$this->assertTrue($this->sut()->beforeCreateOrUpdateUser($credentials, $attributes));
	}

	/**
	 * @test
	 */
	public function afterCreateOrUpdateUser_returnsWpUser()
	{
		$credentials = $this->createMock(Credentials::class);
		$attributes = $this->createMock(User::class);
		$wpUser = new \WP_User();

		$this->assertEquals($wpUser, $this->sut()->afterCreateOrUpdateUser($credentials, $attributes, $wpUser));
	}
}