<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Ut_NextADInt_Multisite_Validator_Rule_DisallowInvalidWordPressRoles')) {
	return;
}

/**
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Ut_NextADInt_Multisite_Validator_Rule_DisallowInvalidWordPressRoles extends Ut_BasicTest
{
	const VALIDATION_MESSAGE = 'Validation failed!';
    const VALIDATION_MESSAGE_2 = 'Validation 2 failed!';

	public function setUp() : void
	{
		parent::setUp();
	}

	public function tearDown() : void
	{
		parent::tearDown();
	}

	/**
	 * @param null $methods
	 *
	 * @return NextADInt_Multisite_Validator_Rule_DisallowInvalidWordPressRoles|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Multisite_Validator_Rule_DisallowInvalidWordPressRoles')
			->setConstructorArgs(
				array(
				    array(self::VALIDATION_MESSAGE, self::VALIDATION_MESSAGE_2)
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function validate_onNetworkDashboard_returnsTrue()
	{
		$sut = $this->sut(array('isOnNetworkDashboard'));

		$sut->expects($this->once())
			->method('isOnNetworkDashboard')
			->willReturn(true);

		$actual = $sut->validate('', array());

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function validate_withoutSuperAdminRole_returnsTrue()
	{
		$sut = $this->sut(array('isOnNetworkDashboard', 'getWpRoles'));

		$sut->expects($this->once())
			->method('isOnNetworkDashboard')
			->willReturn(false);

		$sut->expects($this->once())
			->method('getWpRoles')
			->willReturn(array());

		$actual = $sut->validate('', array());

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function validate_withSuperAdminRoleInBlogConfig_returnsMessage()
	{
		$sut = $this->sut(array('isOnNetworkDashboard', 'getWpRoles'));

		$sut->expects($this->once())
			->method('isOnNetworkDashboard')
			->willReturn(false);

		$sut->expects($this->once())
			->method('getWpRoles')
			->willReturn(array(NextADInt_Adi_Role_Manager::ROLE_SUPER_ADMIN));

		$actual = $sut->validate('', array());

		$this->assertEquals(array(NextADInt_Core_Message_Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function validate_withInvalidString_returnsMessage()
	{
		$sut = $this->sut(array('isOnNetworkDashboard'));

		$sut->expects($this->once())
			->method('isOnNetworkDashboard')
			->willReturn(false);

		$actual = $sut->validate('role=super admin;', array());

		$this->assertEquals(array(NextADInt_Core_Message_Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function getWpRoles_withEmptyString_returnsExpectedResult()
	{
		$sut = $this->sut();

		$actual = $this->invokeMethod($sut, 'getWpRoles', array(''));

		$this->assertCount(0, $actual);
	}

	/**
	 * @test
	 */
	public function getWpRoles_withOneRoleMapping_returnsExpectedResult()
	{
		$sut = $this->sut();

		$expected = array('WpRole');
		$actual = $this->invokeMethod($sut, 'getWpRoles', array('AdRole=WpRole;'));

		$this->assertCount(1, $actual);
		$this->assertEquals($expected, $actual);
	}
}