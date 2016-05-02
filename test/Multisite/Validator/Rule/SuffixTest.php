<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Ut_Multisite_Validator_Rule_SuffixTest')) {
	return;
}

class Ut_Multisite_Validator_Rule_SuffixTest extends Ut_BasicTest
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
	 * @return Multisite_Validator_Rule_Suffix|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('Multisite_Validator_Rule_Suffix')
			->setConstructorArgs(array(
				self::VALIDATION_MESSAGE, '@',
			))
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function validate_withEmptyMessage_returnTrue()
	{
		$sut = $this->sut();

		$actual = $sut->validate('', array());

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function validate_returnMessage()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate('Administrator', array());

		$this->assertEquals(self::VALIDATION_MESSAGE, $actual);
	}

	/**
	 * @test
	 */
	public function validate_returnTrue()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate('Administrator@test.ad', array());

		$this->assertTrue($actual);
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