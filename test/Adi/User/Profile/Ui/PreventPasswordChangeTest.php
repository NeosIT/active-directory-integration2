<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_User_Profile_Ui_PreventPasswordChangeTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_Configuration_Service | PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/* @var NextADInt_Adi_User_Manager | PHPUnit_Framework_MockObject_MockObject */
	private $userManager;

	/**
	 * @return NextADInt_Adi_User_Profile_Ui_PreventPasswordChange|PHPUnit_Framework_MockObject_MockObject
	 */
	public function setUp() : void
	{
		$this->configuration = $this->createMock('NextADInt_Multisite_Configuration_Service');
		$this->userManager = $this->createMock('NextADInt_Adi_User_Manager');

		WP_Mock::setUp();
	}

	public function tearDown() : void
	{
		WP_Mock::tearDown();
	}

	/* @return NextADInt_Adi_User_Profile_Ui_PreventPasswordChange| PHPUnit_Framework_MockObject_MockObject */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Adi_User_Profile_Ui_PreventPasswordChange')
			->setConstructorArgs(
				array(
					$this->configuration,
					$this->userManager
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function register_localPasswordChangeNotAllowed()
	{
		$sut = $this->sut(null);

		\WP_Mock::expectFilterAdded('show_password_fields', array($sut, 'showPasswordFields'), 10, 2);

		$sut->register();
	}

	/**
	 * @test
	 */
	public function isPasswordChangeEnabled_delegatesToconfiguration()
	{
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::ENABLE_PASSWORD_CHANGE)
			->willReturn(true);

		$this->assertTrue($sut->isPasswordChangeEnabled());
	}

	/**
	 * @test
	 */
	public function showPasswordFields_usesParentSetting_ifNoActiveDirectoryAccountIsGiven()
	{
		$sut = $this->sut(null);

		$wpUser = (object)array('ID' => 666);

		$this->userManager->expects($this->once())
			->method('hasActiveDirectoryAccount')
			->with($wpUser)
			->willReturn(false);

		$this->assertTrue($sut->showPasswordFields(true, $wpUser));
	}

	/**
	 * @test
	 */
	public function showPasswordFields_itReturnsAdiSetting_ifActiveDirectoryAccountIsGiven()
	{
		$sut = $this->sut(array('isPasswordChangeEnabled'));

		$wpUser = (object)array('ID' => 666);

		$this->userManager->expects($this->once())
			->method('hasActiveDirectoryAccount')
			->with($wpUser)
			->willReturn(true);

		$sut->expects($this->once())
			->method('isPasswordChangeEnabled')
			->willReturn(true);

		$this->assertTrue($sut->showPasswordFields(false, $wpUser));
	}
}

