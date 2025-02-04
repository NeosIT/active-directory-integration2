<?php

namespace Dreitier\Nadi\Ui\Validator\Rule;


use Dreitier\Test\BasicTestCase;
use Dreitier\Util\Message\Type;
use PHPUnit\Framework\MockObject\MockObject;

class DefaultEmailDomainTest extends BasicTestCase
{
	const VALIDATION_MESSAGE = 'Please remove the "@", it will be added automatically.';

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
	 *
	 * @return DefaultEmailDomain|MockObject
	 */
	public function sut(array $methods = [])
	{
		return $this->getMockBuilder(DefaultEmailDomain::class)
			->setConstructorArgs(
				array(
					self::VALIDATION_MESSAGE,
				)
			)
			->onlyMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function validate_withEmailConflict_returnString()
	{
		$sut = $this->sut();

		$actual = $sut->validate('@test.ad', []);

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function validate_withoutConflict_returnTrue()
	{
		// TODO Code anpassen damit als default email domain normales suffix angegeben werden kann (z.b. "@test.ad")
		$sut = $this->sut();

		$actual = $sut->validate('test.ad', []);

		$this->assertTrue($actual);
	}
}