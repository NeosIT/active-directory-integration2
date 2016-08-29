<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Ut_NextADInt_Multisite_Validator_Rule_SuffixTest')) {
	return;
}

class Ut_NextADInt_Multisite_Validator_Rule_SelectValueValidTest extends Ut_BasicTest
{
	const VALIDATION_MESSAGE = 'The given value is not valid.';

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
	 * @return NextADInt_Multisite_Validator_Rule_Suffix|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Multisite_Validator_Rule_Suffix')
			->setConstructorArgs(
				array(
					self::VALIDATION_MESSAGE, array(1, 2),
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function validate_withValidValue_returnTrue()
	{
		$sut = $this->sut();

		$actual = $sut->validate(2, array());

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function validate_withInvalidValue_returnMessage()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(3, array());

		$this->assertEquals(self::VALIDATION_MESSAGE, $actual);
	}

	/**
	 * @test
	 */
	public function getMsg()
	{
		$sut = $this->sut(null);

		$actual = $sut->getMsg();

		$this->assertEquals(self::VALIDATION_MESSAGE, $actual);
	}
}