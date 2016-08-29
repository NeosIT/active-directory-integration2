<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Ut_NextADInt_Multisite_Validator_Rule_PortTest')) {
	return;
}

/**
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Ut_NextADInt_Multisite_Validator_Rule_PortTest extends Ut_BasicTest
{
	const VALIDATION_MESSAGE = 'Port has to be numeric and in the range from 0 - 65535.';

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
	 * @return NextADInt_Multisite_Validator_Rule_Port|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Multisite_Validator_Rule_Port')
			->setConstructorArgs(
				array(
					self::VALIDATION_MESSAGE
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function validate_withWrongRange_returnString()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(
			123456789,
			null
		);

		$this->assertEquals(self::VALIDATION_MESSAGE, $actual);
	}
}