<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Ut_NextADInt_Multisite_Validator_Rule_ConditionalTest')) {
	return;
}

class Ut_NextADInt_Multisite_Validator_Rule_ConditionalTest extends Ut_BasicTest
{
	public function setUp()
	{
		parent::setUp();
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @param       $methods
	 * @param array $rules
	 *
	 * @return NextADInt_Multisite_Validator_Rule_Conditional|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null, $rules = array())
	{
		return $this->getMockBuilder('NextADInt_Multisite_Validator_Rule_Conditional')
			->setConstructorArgs(array(
				$rules, array(
					'sync_to_wordpress_enabled' => true,
				),
			))
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function validate_withConditionsTrue_executesAllRules()
	{
		$value = '';
		$data = array();

		$ruleOne = $this->createMock('NextADInt_Core_Validator_Rule');
		$ruleTwo = $this->createMock('NextADInt_Core_Validator_Rule');

		$ruleOne->expects($this->once())
			->method('validate')
			->with($value, $data)
			->willReturn(true);

		$ruleTwo->expects($this->once())
			->method('validate')
			->with($value, $data)
			->willReturn(true);

		$sut = $this->sut(array('areConditionsTrue'), array($ruleOne, $ruleTwo));
		$this->behave($sut, 'areConditionsTrue', true);

		$result = $sut->validate($value, $data);

		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function validate_withFirstRuleInvalid_doesNotExecuteOtherRules()
	{
		$value = '';
		$data = array();

		$ruleOne = $this->createMock('NextADInt_Core_Validator_Rule');
		$ruleTwo = $this->createMock('NextADInt_Core_Validator_Rule');

		$ruleOne->expects($this->once())
			->method('validate')
			->with($value, $data)
			->willReturn('Test');

		$ruleTwo->expects($this->never())
			->method('validate')
			->with($value, $data)
			->willReturn(true);

		$sut = $this->sut(array('areConditionsTrue'), array($ruleOne, $ruleTwo));
		$this->behave($sut, 'areConditionsTrue', true);

		$result = $sut->validate($value, $data);

		$this->assertEquals('Test', $result);
	}

	/**
	 * @test
	 */
	public function validate_withConditionsFalse_returnsTrue()
	{
		$sut = $this->sut(array('areConditionsTrue'));

		$this->behave($sut, 'areConditionsTrue', false);

		$result = $sut->validate('', array());

		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function areConditionsTrue_withoutFalseCondition_returnTrue()
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
	public function areConditionsTrue_withFalseCondition_returnFalse()
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
					'sync_to_wordpress_enabled' => 1,
				),
			)
		);

		$this->assertTrue($actual);
	}
}