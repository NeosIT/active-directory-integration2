<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Core_Validator_ResultTest extends Ut_BasicTest
{
	public function setUp() : void
	{
		parent::setUp();
	}

	public function tearDown() : void
	{
		parent::tearDown();
	}

	/**
	 * @return NextADInt_Core_Validator_Result| PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Core_Validator_Result')
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function isValid_returnFalse()
	{
		$sut = $this->sut(null);

		$sut->addValidationResult(
			'sync_to_ad_global_user', 'Username has to contain a suffix.'
		);

		$actual = $sut->isValid();

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function isValid_returnTrue()
	{
		$sut = $this->sut(null);

		$actual = $sut->isValid();

		$this->assertTrue($actual);
	}
}
