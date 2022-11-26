<?php

namespace Dreitier\Nadi\Ui\Validator\Rule;

use Dreitier\Nadi\Role\Manager;
use Dreitier\Test\BasicTest;
use Dreitier\Util\Message\Type;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class DisallowInvalidWordPressRolesTest extends BasicTest
{
	const VALIDATION_MESSAGE = 'Validation failed!';
	const VALIDATION_MESSAGE_2 = 'Validation 2 failed!';

	public function setUp(): void
	{
		parent::setUp();
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @param null $methods
	 *
	 * @return DisallowInvalidWordPressRoles|MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder(DisallowInvalidWordPressRoles::class)
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
			->willReturn(array(Manager::ROLE_SUPER_ADMIN));

		$actual = $sut->validate('', array());

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
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

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
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