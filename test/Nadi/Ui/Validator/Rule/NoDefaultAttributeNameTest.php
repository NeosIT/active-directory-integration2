<?php

namespace Dreitier\Nadi\Ui\Validator\Rule;

use Dreitier\Test\BasicTestCase;
use Dreitier\Util\Message\Type;
use PHPUnit\Framework\MockObject\MockObject;

class NoDefaultAttributeNameTest extends BasicTestCase
{
	/** @var string */
	private $invalidTestString = 'givenname:string:next_ad_int_samaccountname:first name:true:true:true';
	/** @var string */
	private $validTestString = 'givenname:string:next_ad_int_first_name:first name:true:true:true';

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
	 * @return NoDefaultAttributeName|MockObject
	 */
	public function sut(array $methods = [])
	{
		return $this->getMockBuilder(NoDefaultAttributeName::class)
			->setConstructorArgs(array('test'))
			->onlyMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function validate_withoutDefaultAttributes_returnsTrue()
	{
		$sut = $this->sut();

		$actual = $sut->validate($this->validTestString, null);

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function validate_withDefaultAttributes_returnsMessage()
	{
		$sut = $this->sut();

		$actual = $sut->validate($this->invalidTestString, null);

		$this->assertEquals(array(Type::ERROR => 'test'), $actual);
	}
}