<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Ut_NextADInt_Multisite_Validator_Rule_AdminEmailTest')) {
	return;
}

class Ut_NextADInt_Multisite_Validator_Rule_AdminEmailTest extends Ut_BasicTest
{
	const VALIDATION_MESSAGE = 'Username has to contain a suffix.';

	public function setUp()
	{
		parent::setUp();
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @param $methods
	 *
	 * @return NextADInt_Multisite_Validator_Rule_AdminEmail|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Multisite_Validator_Rule_AdminEmail')
			->setConstructorArgs(
				array(
					self::VALIDATION_MESSAGE, '@',
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function validate_withSingleEmailConflict_returnString()
	{
		$sut = $this->sut();

		$actual = $sut->validate('@test.ad', array());

		$this->assertEquals(self::VALIDATION_MESSAGE, $actual);
	}

	/**
	 * @test
	 */
	public function validate_withSingleEmail_returnTrue()
	{
		$sut = $this->sut();

		$actual = $sut->validate('test@test.ad', array());

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function validate_withEmailListConflict_returnString()
	{
		$sut = $this->sut();

		$actual = $sut->validate('test@test.ad;test2@;test3@test.ad', array());

		$this->assertEquals(self::VALIDATION_MESSAGE, $actual);
	}

	/**
	 * @test
	 */
	public function validate_withEmailList_returnTrue()
	{
		$sut = $this->sut();

		$actual = $sut->validate('test@test.ad;test2@test.ad;test3@test.ad', array());

		$this->assertTrue($actual);
	}
}