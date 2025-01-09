<?php

namespace Dreitier\Nadi\Ui\Validator\Rule;


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
class PortTest extends BasicTestCase
{
	const VALIDATION_MESSAGE = 'Port has to be numeric and in the range from 0 - 65535.';

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
	 * @return Port|MockObject
	 */
	public function sut(array $methods = [])
	{
		return $this->getMockBuilder(Port::class)
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
	public function validate_withWrongRange_returnString()
	{
		$sut = $this->sut();

		$actual = $sut->validate(
			123456789,
			null
		);

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}
}