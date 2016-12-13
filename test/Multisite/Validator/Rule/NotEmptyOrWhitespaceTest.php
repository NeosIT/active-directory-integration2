<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Ut_NextADInt_Multisite_Validator_Rule_NotEmptyOrWhitespaceTest')) {
	return;
}

/**
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Ut_NextADInt_Multisite_Validator_Rule_NotEmptyOrWhitespaceTest extends Ut_BasicTest
{
	const VALIDATION_MESSAGE = 'Validation failed.';

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
	 * @param $msg string
	 *
	 * @return NextADInt_Multisite_Validator_Rule_Numeric|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Multisite_Validator_Rule_NotEmptyOrWhitespace')
			->setConstructorArgs(
				array(
					self::VALIDATION_MESSAGE,
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function validate_withEmptyString_returnString()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(
			"",
			null
		);

		$this->assertEquals(array(NextADInt_Core_Message_Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function validate_withWhitespaceOnly_returnString()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(
			"   ",
			null
		);

		$this->assertEquals(array(NextADInt_Core_Message_Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function validate_withWhitespacesAndLetters_returnTrue()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(
			" test ",
			null
		);

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function validate_withLettersOnly_returnTrue()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(
			"test",
			null
		);

		$this->assertTrue($actual);
	}
}