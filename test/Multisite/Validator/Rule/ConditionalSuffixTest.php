<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Ut_Multisite_Validator_Rule_ConditionalSuffixTest')) {
	return;
}

/**
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Ut_Multisite_Validator_Rule_ConditionalSuffixTest extends Ut_BasicTest
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
		return $this->getMockBuilder('Multisite_Validator_Rule_ConditionalSuffix')
			->setConstructorArgs(
				array(
					self::VALIDATION_MESSAGE, '@', array(
					'sync_to_wordpress_enabled' => true,
				),
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function validate_areConditionsTrueEqualsFalse_returnTrue()
	{
		$sut = $this->sut(array('areConditionsTrue'));

		$sut->expects($this->once())
			->method('areConditionsTrue')
			->willReturn(false);

		$actual = $sut->validate('test', array());

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function validate_areConditionsTrueEqualsTrueAndWithoutSuffix_returnsMessage()
	{
		$sut = $this->sut(array('areConditionsTrue'));

		$sut->expects($this->once())
			->method('areConditionsTrue')
			->willReturn(true);

		$actual = $sut->validate('test', array());

		$this->assertEquals(self::VALIDATION_MESSAGE, $actual);
	}

	/**
	 * @test
	 */
	public function validate_areConditionsTrueEqualsTrueAndWithSuffix_returnsTrue()
	{
		$sut = $this->sut(array('areConditionsTrue'));

		$sut->expects($this->once())
			->method('areConditionsTrue')
			->willReturn(true);

		$actual = $sut->validate('test@test', array());

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function isAnyConditionFalse_withoutFalseCondition_returnTrue()
	{
		$sut = $this->sut();

		$actual = $this->invokeMethod(
			$sut, 'areConditionsTrue', array(
			array(
				'sync_to_wordpress_enabled' => array(
					'option_value' => 1,
				),
			),
		)
		);

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function isAnyConditionFalse_withFalseCondition_returnFalse()
	{
		$sut = $this->sut();

		$actual = $this->invokeMethod(
			$sut, 'areConditionsTrue', array(
			array(
				'sync_to_wordpress_enabled' => array(
					'option_value' => 0,
				),
			),
		)
		);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function areConditionsTrue_withBlogConfigurationData_returnTrue()
	{
		$sut = $this->sut();

		$actual = $this->invokeMethod(
			$sut, 'areConditionsTrue', array(
			array(
				'sync_to_wordpress_enabled' => 1
			),
		)
		);

		$this->assertTrue($actual);
	}
}