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
class WordPressMetakeyConflictTest extends BasicTestCase
{
	const VALIDATION_MESSAGE = 'You cannot use the same WordPress Attribute multiple times.';

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
	 * @return WordPressMetakeyConflict|MockObject
	 */
	public function sut(array $methods = [])
	{
		return $this->getMockBuilder(WordPressMetakeyConflict::class)
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
	public function validate_withConflict_returnString()
	{
		$sut = $this->sut();

		$actual = $sut->validate(
			"testAdAttribute1:string:testWordpressAttribute1:testDescription:0:0:0;testAdAttribute2:string:testWordpressAttribute1:testDescription:0:0:0",
			null
		);

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function validate_withoutConflict_returnTrue()
	{
		$sut = $this->sut();

		$actual = $sut->validate(
			"testAdAttribute1:string:testWordpressAttribute1:testDescription:0:0:0;testAdAttribute2:string:testWordpressAttribute2:testDescription:0:0:0",
			null
		);

		$this->assertTrue($actual);
	}
}