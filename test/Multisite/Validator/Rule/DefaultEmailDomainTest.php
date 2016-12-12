<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Ut_NextADInt_Multisite_Validator_Rule_DefaultEmailDomainTest')) {
	return;
}

class Ut_NextADInt_Multisite_Validator_Rule_DefaultEmailDomainTest extends Ut_BasicTest
{
	const VALIDATION_MESSAGE = 'Please remove the "@", it will be added automatically.';
	const EXPECTED_ERROR= array(NextADInt_Core_Message_Type::ERROR => self::VALIDATION_MESSAGE);

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
	 * @return NextADInt_Multisite_Validator_Rule_DefaultEmailDomain|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Multisite_Validator_Rule_DefaultEmailDomain')
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
	public function validate_withEmailConflict_returnString()
	{
		$sut = $this->sut();

		$actual = $sut->validate('@test.ad', array());

		$this->assertEquals(self::EXPECTED_ERROR, $actual);
	}

	/**
	 * @test
	 */
	public function validate_withoutConflict_returnTrue()
	{
		// TODO Code anpassen damit als default email domain normales suffix angegeben werden kann (z.b. "@test.ad")
		$sut = $this->sut();

		$actual = $sut->validate('test.ad', array());

		$this->assertTrue($actual);
	}
}