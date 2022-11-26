<?php

namespace Dreitier\Nadi\Authentication;

use Dreitier\Ldap\Attributes;
use Dreitier\Ldap\Connection;
use Dreitier\Ldap\ConnectionDetails;
use Dreitier\Ldap\UserQuery;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\LoginState;
use Dreitier\Nadi\Role\Mapping;
use Dreitier\Nadi\User\LoginSucceededService;
use Dreitier\Nadi\User\Manager;
use Dreitier\Test\BasicTest;
use Dreitier\WordPress\Multisite\Configuration\Service;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class LoginServiceTest extends BasicTest
{
	/* @var Service|MockObject $configuration */
	private $configuration;

	/* @var Connection|MockObject $ldapConnection */
	private $ldapConnection;

	/* @var Manager|MockObject $userManager */
	private $userManager;

	/* @var \Dreitier\Ldap\Attribute\Service|MockObject $attributeService */
	private $attributeService;

	/** @var LoginState */
	private $loginState;

	/** @var LoginSucceededService */
	private $loginSucceededService;

	public function setUp(): void
	{
		parent::setUp();

		$this->configuration = $this->createMock(Service::class);
		$this->ldapConnection = $this->createMock(Connection::class);
		$this->userManager = $this->createMock(Manager::class);
		$this->attributeService = $this->createMock(\Dreitier\Ldap\Attribute\Service::class);
		$this->loginSucceededService = $this->createMock(LoginSucceededService::class);
		$this->loginState = new LoginState();
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @return LoginService|MockObject
	 */
	public function sut($methods = null, $simulated = false)
	{
		return $this->getMockBuilder(LoginService::class)
			->setConstructorArgs(
				array(
					$this->configuration,
					$this->ldapConnection,
					$this->userManager,
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
			->with($this->isInstanceOf(Credentials::class), $suffixes)
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
		$credentials = new Credentials('username');
		$attributes = [
			'samaccountname' => 'sam',
			'userprincipalname' => 'sam@test.ad',
			'objectguid' => 666
		];

		$ldapAttributes = new Attributes($attributes, $attributes);

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
			->with(Options::EXCLUDE_USERNAMES_FROM_AUTHENTICATION)
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
			->with(Options::EXCLUDE_USERNAMES_FROM_AUTHENTICATION)
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
			->with(Options::EXCLUDE_USERNAMES_FROM_AUTHENTICATION)
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
			->with(Options::ACCOUNT_SUFFIX)
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
			->with(Options::ACCOUNT_SUFFIX)
			->willReturn('');

		$actual = $sut->detectAuthenticatableSuffixes('@test.ad');

		$this->assertEquals(array('@test.ad'), $actual);
	}

	/**
	 * @test
	 * @issue ADI-716
	 */
	public function ADI_716()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::ACCOUNT_SUFFIX)
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
			->with(Options::ACCOUNT_SUFFIX)
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
			->method('connect')
			->with(new ConnectionDetails())
			->willReturn(true);

		$this->ldapConnection->expects($this->once())
			->method('authenticate')
			->with($username, $suffix, $password)
			->willReturn(false);

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

		$roleMapping = new Mapping("username");
		$attributes = new Attributes(array(), array('objectguid' => $userGuid));

		$this->ldapConnection->expects($this->once())
			->method('connect')
			->with(new ConnectionDetails())
			->willReturn(true);

		$this->ldapConnection->expects($this->once())
			->method('authenticate')
			->with($username, $suffix, $password)
			->willReturn(true);

		$actual = $sut->authenticateAtActiveDirectory($username, $suffix, $password);
		$this->assertTrue($actual);
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
			->with(Options::ALLOW_XMLRPC_LOGIN)
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
			->with(Options::ALLOW_XMLRPC_LOGIN)
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

		\WP_Mock::wpFunction('username_exists',
			array(
				'args' => array($login),
				'times' => 1,
				'return' => $expectedWpUserId
			)
		);

		$actual = $sut->getWordPressUser($login);

		$this->assertTrue($actual instanceof \WP_User);
	}

	/**
	 * @test
	 * @issue ADI-673
	 */
	public function getWordPressUser_withInvalidLogin_returnsFalse()
	{
		$login = 'john.doe';
		$sut = $this->sut();

		\WP_Mock::wpFunction('username_exists',
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
		$credentials = new Credentials($samaccountName, 'secret');
		$filteredAttributes = array('samaccountname' => $samaccountName, 'objectguid' => $objectguid);
		$expectedLdapAttributes = new Attributes(array(), $filteredAttributes);

		$sut = $this->sut(array('updateCredentials'));

		$this->attributeService->expects($this->once())
			->method('resolveLdapAttributes')
			->with($this->callback(function (UserQuery $userQuery) use ($credentials) {
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
		$credentials = new Credentials($samaccountName, 'secret');
		$expectedLdapAttributes = new Attributes(false, false);

		$sut = $this->sut();

		$this->attributeService->expects($this->once())
			->method('resolveLdapAttributes')
			->with($this->callback(function (UserQuery $userQuery) use ($credentials) {
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
			->with(Options::ENABLE_LOST_PASSWORD_RECOVERY)
			->willReturn(true);

		\WP_Mock::expectFilterAdded('authenticate', array($sut, 'authenticate'), 10, 3);
		\WP_Mock::expectFilterNotAdded('allow_password_reset', '__return_false');

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
			->with(Options::ENABLE_LOST_PASSWORD_RECOVERY)
			->willReturn(false);

		\WP_Mock::expectFilterAdded('authenticate', array($sut, 'authenticate'), 10, 3);
		\WP_Mock::expectFilterAdded('allow_password_reset', '__return_false');

		$sut->register();
	}

	/**
	 * @test
	 * @issue #142
	 */
	public function register_adds_filter_next_ad_int_auth_form_login_requires_ad_authentication()
	{
		$sut = $this->sut();

		\WP_Mock::expectFilterAdded('next_ad_int_auth_form_login_requires_ad_authentication', array($sut, 'requiresActiveDirectoryAuthentication'), 10, 1);
		$sut->register();
	}

}