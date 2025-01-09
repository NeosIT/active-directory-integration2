<?php

namespace Dreitier\Nadi\Authorization;

use Dreitier\Nadi\Authentication\Credentials;
use Dreitier\Nadi\LoginState;
use Dreitier\Nadi\Role\Mapping;
use Dreitier\Nadi\User\Manager;
use Dreitier\Test\BasicTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access private
 */
class ServiceTest extends BasicTestCase
{
	/* @var \Dreitier\WordPress\Multisite\Configuration\Service| MockObject */
	private $multisiteConfigurationService;

	/* @var Manager| MockObject */
	private $userManager;

	/* @var \Dreitier\Nadi\Role\Manager| MockObject */
	private $roleManager;

	/** @var LoginState */
	private $loginState;

	public function setUp(): void
	{
		parent::setUp();

		$this->multisiteConfigurationService = $this->createMock(\Dreitier\WordPress\Multisite\Configuration\Service::class);
		$this->userManager = $this->createMock(Manager::class);
		$this->roleManager = $this->createMock(\Dreitier\Nadi\Role\Manager::class);
		$this->loginState = new LoginState();
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @param null $methods
	 * @param bool $simulated
	 *
	 * @return Service|MockObject
	 */
	public function sut(array $methods = [], bool $simulated = false)
	{
		return $this->getMockBuilder(Service::class)
			->setConstructorArgs(
				array(
					$this->multisiteConfigurationService,
					$this->userManager,
					$this->roleManager,
					$this->loginState
				)
			)->onlyMethods($methods)
			->getMock();

	}

	/**
	 * @test
	 */
	public function authorizationIsNever_ifUserHasNotBeenPreviouslyAuthenticated()
	{
		$r = $this->sut()->checkAuthorizationRequired(null);

		$this->assertFalse($r);
	}

	/**
	 * @test
	 */
	public function authorizationIsNeverRequiredForAdmins()
	{
		$wpAdmin = new \WP_User();
		$wpAdmin->setId(1);

		$r = $this->sut()->checkAuthorizationRequired($wpAdmin);

		$this->assertFalse($r);
	}

	/**
	 * @test
	 */
	public function checkAuthorizationRequired_withValidCredentials_returnsTrue()
	{
		$this->assertTrue($this->sut()->checkAuthorizationRequired(new Credentials()));
	}

	/**
	 * @test
	 * @issue ADI-673
	 */
	public function register_willRegisterExpectedFilter()
	{
		$sut = $this->sut();

		\WP_Mock::expectFilterAdded('authenticate', array($sut, 'authorizeAfterAuthentication'), 15, 3);
		\WP_Mock::expectFilterAdded('authorize', array($sut, 'isUserInAuthorizationGroup'), 10, 1);

		$sut->register();
	}

	/**
	 * @test
	 * @issue ADI-673
	 */
	public function authorizeAfterAuthentication_willApplyExpectedFilter()
	{
		$credentials = new Credentials();
		$expectedResult = new \WP_User();
		$sut = $this->sut();

		\WP_Mock::onFilter('authorize')->with($credentials)->reply($expectedResult);

		$actual = $sut->authorizeAfterAuthentication($credentials, 'john.doe');
		$this->assertEquals($expectedResult, $actual);
	}

	/**
	 * @test
	 * @issue ADI-673
	 */
	public function isUserInAuthorizationGroup()
	{
		$credentials = new Credentials();
		$expectedResult = new \WP_User();
		$sut = $this->sut();

		\WP_Mock::onFilter('authorize')->with($credentials)->reply($expectedResult);

		$actual = $sut->authorizeAfterAuthentication($credentials, 'john.doe');
		$this->assertEquals($expectedResult, $actual);
	}

	/**
	 * @test
	 */
	public function isUserInAuthorizationGroup_withValidCredentials_authorizeByGroupsDisabled_returnsExpected()
	{
		$credentials = new Credentials();
		$sut = $this->sut();

		$this->multisiteConfigurationService->expects($this->once())
			->method('getOptionValue')
			->willReturn(false);

		$actual = $sut->isUserInAuthorizationGroup($credentials);

		$this->assertEquals($credentials, $actual);
	}

	/**
	 * @test
	 */
	public function isUserInAuthorizationGroup_withInvalidCredentials_returnsExpected()
	{
		$credentials = new \WP_Error();
		$sut = $this->sut();

		$this->multisiteConfigurationService->expects($this->never())->method('getOptionValue');

		$actual = $sut->isUserInAuthorizationGroup($credentials);

		$this->assertEquals($credentials, $actual);
	}

	/**
	 * @test
	 */
	public function isUserInAuthorizationGroup_withValidCredentials_missingGuid_returnsExpected()
	{
		$credentials = new Credentials();
		$sut = $this->sut();
		$this->loginState->setAuthenticationSucceeded();

		$this->multisiteConfigurationService->expects($this->once())
			->method('getOptionValue')
			->willReturn(true);

		$this->roleManager->expects($this->never())->method('createRoleMapping');

		$actual = $sut->isUserInAuthorizationGroup($credentials);

		$this->assertEquals($credentials, $actual);
	}

	/**
	 * @test
	 */
	public function isUserInAuthorizationGroup_withValidCredentials_notAuthenticated_returnsExpected()
	{
		$credentials = new Credentials();
		$credentials->setObjectGuid('f764c1fc-8f7c-4b43-97b6-782002ade47c');
		$sut = $this->sut();

		$this->multisiteConfigurationService->expects($this->once())
			->method('getOptionValue')
			->willReturn(true);

		$this->roleManager->expects($this->never())->method('createRoleMapping');

		$actual = $sut->isUserInAuthorizationGroup($credentials);

		$this->assertEquals($credentials, $actual);
	}

	/**
	 * @test
	 */
	public function isUserInAuthorizationGroup_withValidCredentials_userNotInAuthGroup_returnsExpected()
	{
		$credentials = new Credentials();
		$guid = 'f764c1fc-8f7c-4b43-97b6-782002ade47c';
		$credentials->setObjectGuid($guid);
		$roleMapping = new Mapping($guid);

		$this->loginState->setAuthenticationSucceeded();
		$sut = $this->sut();

		$this->multisiteConfigurationService->expects($this->once())
			->method('getOptionValue')
			->willReturn(true);

		$this->roleManager->expects($this->once())
			->method('createRoleMapping')
			->with($guid)->willReturn($roleMapping);

		$this->roleManager->expects($this->once())
			->method('isInAuthorizationGroup')
			->with($roleMapping)->willReturn(false);

		$actual = $sut->isUserInAuthorizationGroup($credentials);

		$this->assertFalse($this->loginState->isAuthorized());
		$this->assertTrue($actual instanceof \WP_Error);
	}

	/**
	 * @test
	 */
	public function isUserInAuthorizationGroup_withValidCredentials_userInAuthGroup_returnsExpected()
	{
		$credentials = new Credentials();
		$guid = 'f764c1fc-8f7c-4b43-97b6-782002ade47c';
		$credentials->setObjectGuid($guid);
		$roleMapping = new Mapping($guid);

		$this->loginState->setAuthenticationSucceeded();
		$sut = $this->sut();

		$this->multisiteConfigurationService->expects($this->once())
			->method('getOptionValue')
			->willReturn(true);

		$this->roleManager->expects($this->once())
			->method('createRoleMapping')
			->with($guid)->willReturn($roleMapping);

		$this->roleManager->expects($this->once())
			->method('isInAuthorizationGroup')
			->with($roleMapping)->willReturn(true);

		$actual = $sut->isUserInAuthorizationGroup($credentials);

		$this->assertEquals($credentials, $actual);
	}
}