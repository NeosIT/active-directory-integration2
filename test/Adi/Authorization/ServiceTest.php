<?php

/**
 * @author Christopher Klein <ckl@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_Authorization_ServiceTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_Configuration_Service| PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/* @var NextADInt_Adi_User_Manager| PHPUnit_Framework_MockObject_MockObject */
	private $userManager;

	/* @var NextADInt_Adi_Role_Manager| PHPUnit_Framework_MockObject_MockObject */
	private $roleManager;

	/** @var NextADInt_Adi_LoginState */
	private $loginState;

	public function setUp()
	{
		parent::setUp();

		$this->configuration = $this->createMock('NextADInt_Multisite_Configuration_Service');
		$this->userManager   = $this->createMock('NextADInt_Adi_User_Manager');
		$this->roleManager   = $this->createMock('NextADInt_Adi_Role_Manager');
		$this->loginState    = new NextADInt_Adi_LoginState();
	}

	/**
	 * @param null $methods
	 * @param bool $simulated
	 *
	 * @return NextADInt_Adi_Authorization_Service|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null, $simulated = false)
	{
		return $this->getMockBuilder('NextADInt_Adi_Authorization_Service')
		            ->setConstructorArgs(
			            array(
				            $this->configuration,
				            $this->userManager,
				            $this->roleManager,
				            $this->loginState
			            )
		            )->setMethods($methods)
		            ->getMock();

	}

	public function tearDown()
	{
		parent::tearDown();
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
		$wpAdmin = new WP_User();
		$wpAdmin->setId(1);

		$r = $this->sut()->checkAuthorizationRequired($wpAdmin);

		$this->assertFalse($r);
	}

	/**
	 * @test
	 */
	public function checkAuthorizationRequired_withValidCredentials_returnsTrue()
	{
		$this->assertTrue($this->sut()->checkAuthorizationRequired(new NextADInt_Adi_Authentication_Credentials()));
	}

	/**
	 * @test
	 * @issue ADI-673
	 */
	public function register_willRegisterExpectedFilter()
	{
		$sut = $this->sut();

		WP_Mock::expectFilterAdded('authenticate', array($sut, 'authorizeAfterAuthentication'), 15, 3);
		WP_Mock::expectFilterAdded('authorize', array($sut, 'isUserInAuthorizationGroup'), 10, 1);

		$sut->register();
	}

	/**
	 * @test
	 * @issue ADI-673
	 */
	public function authorizeAfterAuthentication_willApplyExpectedFilter()
	{
		$credentials    = new NextADInt_Adi_Authentication_Credentials();
		$expectedResult = new WP_User();
		$sut            = $this->sut();

		WP_Mock::onFilter('authorize')->with($credentials)->reply($expectedResult);

		$actual = $sut->authorizeAfterAuthentication($credentials, 'john.doe');
		$this->assertEquals($expectedResult, $actual);
	}

	/**
	 * @test
	 * @issue ADI-673
	 */
	public function isUserInAuthorizationGroup()
	{
		$credentials    = new NextADInt_Adi_Authentication_Credentials();
		$expectedResult = new WP_User();
		$sut            = $this->sut();

		WP_Mock::onFilter('authorize')->with($credentials)->reply($expectedResult);

		$actual = $sut->authorizeAfterAuthentication($credentials, 'john.doe');
		$this->assertEquals($expectedResult, $actual);
	}

	/**
	 * @test
	 */
	public function isUserInAuthorizationGroup_withValidCredentials_authorizeByGroupsDisabled_returnsExpected()
	{
		$credentials = new NextADInt_Adi_Authentication_Credentials();
		$sut         = $this->sut();

		$this->configuration->expects($this->once())
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
		$credentials = new WP_Error();
		$sut         = $this->sut();

		$this->configuration->expects($this->never())->method('getOptionValue');

		$actual = $sut->isUserInAuthorizationGroup($credentials);

		$this->assertEquals($credentials, $actual);
	}

	/**
	 * @test
	 */
	public function isUserInAuthorizationGroup_withValidCredentials_missingGuid_returnsExpected()
	{
		$credentials = new NextADInt_Adi_Authentication_Credentials();
		$sut         = $this->sut();
		$this->loginState->setAuthenticationSucceeded();

		$this->configuration->expects($this->once())
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
		$credentials = new NextADInt_Adi_Authentication_Credentials();
		$credentials->setObjectGuid('f764c1fc-8f7c-4b43-97b6-782002ade47c');
		$sut = $this->sut();

		$this->configuration->expects($this->once())
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
		$credentials = new NextADInt_Adi_Authentication_Credentials();
		$guid        = 'f764c1fc-8f7c-4b43-97b6-782002ade47c';
		$credentials->setObjectGuid($guid);
		$roleMapping = new NextADInt_Adi_Role_Mapping($guid);

		$this->loginState->setAuthenticationSucceeded();
		$sut = $this->sut();

		$this->configuration->expects($this->once())
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
		$this->assertTrue($actual instanceof WP_Error);
	}

	/**
	 * @test
	 */
	public function isUserInAuthorizationGroup_withValidCredentials_userInAuthGroup_returnsExpected()
	{
		$credentials = new NextADInt_Adi_Authentication_Credentials();
		$guid        = 'f764c1fc-8f7c-4b43-97b6-782002ade47c';
		$credentials->setObjectGuid($guid);
		$roleMapping = new NextADInt_Adi_Role_Mapping($guid);

		$this->loginState->setAuthenticationSucceeded();
		$sut = $this->sut();

		$this->configuration->expects($this->once())
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