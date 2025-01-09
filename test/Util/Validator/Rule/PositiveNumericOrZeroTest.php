<?php

namespace Dreitier\Util\Validator\Rule;


use Dreitier\Test\BasicTestCase;
use Dreitier\Util\Message\Type;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class PositiveNumericOrZeroTest extends BasicTestCase
{
	const VALIDATION_MESSAGE = 'Validation failed!';


	public function setUp(): void
	{
		parent::setUp();
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @param $methods
	 * @param $msg string
	 *
	 * @return PositiveNumericOrZero|MockObject
	 */
	public function sut(array $methods = [])
	{
		return $this->getMockBuilder(PositiveNumericOrZero::class)
			->setConstructorArgs(
				array(
					self::VALIDATION_MESSAGE
				)
			)
			->onlyMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function validate_withPositiveNumeric_returnTrue()
	{
		$sut = $this->sut();

		$actual = $sut->validate(
			2,
			null
		);

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function validate_withZero_returnTrue()
	{
		$sut = $this->sut();

		$actual = $sut->validate(
			0,
			null
		);

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function validate_withNegativeNumeric_returnString()
	{
		$sut = $this->sut();

		$actual = $sut->validate(
			-123456789,
			null
		);

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}
}