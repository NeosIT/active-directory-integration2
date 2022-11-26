<?php

namespace Dreitier\Nadi\Ui\Validator\Rule;

use Dreitier\Test\BasicTest;
use Dreitier\Util\Message\Type;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class AttributeMappingNullTest extends BasicTest
{

	const VALIDATION_MESSAGE = 'Ad Attribute / Data Type / WordPress Attribute cannot be empty!';

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
	 * @return  AttributeMappingNull|MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder( AttributeMappingNull::class)
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
	public function validate_withAdAttributeUndefined_returnString()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(
			"undefined:string:testWordpressAttribute1:testDescription:0:0:0;testAdAttribute1:string:testWordpressAttribute1:testDescription:0:0:0",
			null
		);

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function validate_withWordPressAttributeUndefined_returnString()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(
			"testAdAttribute1:string:undefined:testDescription:0:0:0;testAdAttribute2:string:testWordpressAttribute1:testDescription:0:0:0",
			null
		);

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function validate_withWordPressAttributeEmpty_returnString()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(
			"testAdAttribute1:string::testDescription:0:0:0;testAdAttribute2:string:testWordpressAttribute1:testDescription:0:0:0",
			null
		);

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function validate_withWordPressAttributeDefaultValue_returnString()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(
			"testAdAttribute1:string:next_ad_int_:testDescription:0:0:0;testAdAttribute2:string:testWordpressAttribute1:testDescription:0:0:0",
			null
		);

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function validate_withTypeUndefined_returnString()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(
			"testAdAttribute1:undefined:WordPressAttribut1:testDescription:0:0:0;testAdAttribute2:string:testWordpressAttribute1:testDescription:0:0:0",
			null
		);

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function validate_withTypeEmpty_returnString()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(
			"testAdAttribute1::WordPressAttribut1:testDescription:0:0:0;testAdAttribute2:string:testWordpressAttribute1:testDescription:0:0:0",
			null
		);

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}


	/**
	 * @test
	 */
	public function validate_withoutConflict_returnTrue()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(
			"testAdAttribute1:string:testWordpressAttribute1:testDescription:0:0:0;testAdAttribute2:string:testWordpressAttribute2:testDescription:0:0:0",
			null
		);

		$this->assertTrue($actual);
	}
}