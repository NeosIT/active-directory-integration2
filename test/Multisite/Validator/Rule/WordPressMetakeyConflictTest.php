<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Ut_NextADInt_Multisite_Validator_Rule_WordPressMetakeyConflictTest')) {
	return;
}

/**
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Ut_NextADInt_Multisite_Validator_Rule_WordPressMetakeyConflictTest extends Ut_BasicTest
{
	const VALIDATION_MESSAGE = 'You cannot use the same WordPress Attribute multiple times.';

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
	 * @return NextADInt_Multisite_Validator_Rule_WordPressMetakeyConflict|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Multisite_Validator_Rule_WordPressMetakeyConflict')
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
	public function validate_withConflict_returnString()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(
			"testAdAttribute1:string:testWordpressAttribute1:testDescription:0:0:0;testAdAttribute2:string:testWordpressAttribute1:testDescription:0:0:0",
			null
		);

		$this->assertEquals(array(NextADInt_Core_Message_Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function validate_withoutConflict_returnTrue()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(
			"testAdAttribute1:string:testWordpressAttribute1:testDescription:0:0:0;testAdAttribute2:string:testWordpressAttribute2:testDescription:0:0:0",
			null
		);

		$this->assertTrue($actual);
	}
}