<?php

namespace Dreitier\Util\Validator\Rule;

use Dreitier\Test\BasicTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ConditionalTest extends BasicTestCase
{
	public function setUp(): void
	{
		parent::setUp();
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @param       $methods
	 * @param array $rules
	 *
	 * @return Conditional|MockObject
	 */
	public function sut(array $methods = [], array $rules = [])
	{
		return $this->getMockBuilder(Conditional::class)
			->setConstructorArgs(array(
				$rules, array(
					'sync_to_wordpress_enabled' => true,
				),
			))
			->onlyMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function validate_withConditionsTrue_executesAllRules()
	{
		$value = '';
		$data = [];

		$ruleOne = $this->createMock(RuleAdapter::class);
		$ruleTwo = $this->createMock(RuleAdapter::class);

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
		$data = [];

		$ruleOne = $this->createMock(RuleAdapter::class);
		$ruleTwo = $this->createMock(RuleAdapter::class);

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

		$result = $sut->validate('', []);

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