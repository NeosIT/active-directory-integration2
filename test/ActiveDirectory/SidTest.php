<?php

namespace Dreitier\ActiveDirectory;

use Dreitier\Test\BasicTest;

/**
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access private
 */
class SidTest extends BasicTest
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
	 * @test
	 */
	public function of_returnsNull_withInvalidSid()
	{
		$sut = Sid::of('some-string');
		$this->assertNull($sut);
	}

	/**
	 * @test
	 */
	public function aSidString_canBeConverted()
	{
		$sid = 'S-1-5-21-2127521184-1604012920-1887927527-72713';
		$sut = Sid::of($sid);

		$this->assertEquals($sid, $sut->getFormatted());
		$this->assertEquals('010500000000000515000000A065CF7E784B9B5FE77C8770091C0100', $sut->getHex());
	}

	/**
	 * @test
	 */
	public function aSidHex_canBeConverted()
	{
		$sid = '010500000000000515000000A065CF7E784B9B5FE77C8770091C0100';
		$sut = Sid::of($sid);

		$this->assertEquals($sid, $sut->getHex());
		$this->assertEquals('S-1-5-21-2127521184-1604012920-1887927527-72713', $sut->getFormatted());
		$this->assertEquals(hex2bin($sid), $sut->getBinary());
	}

	/**
	 * @test
	 */
	public function aSidBinary_canBeConverted()
	{
		$hex = '010500000000000515000000A065CF7E784B9B5FE77C8770091C0100';
		$sid = hex2bin($hex);
		$sut = Sid::of($sid);

		$this->assertEquals($sid, $sut->getBinary());
		$this->assertEquals('S-1-5-21-2127521184-1604012920-1887927527-72713', $sut->getFormatted());
		$this->assertEquals($sid, $sut->getBinary());
	}

	/**
	 * @test
	 */
	public function getDomainPart_returnsPartOnly()
	{
		$sid = 'S-1-5-21-2127521184-1604012920-1887927527-72713';
		$sut = Sid::of($sid);

		$this->assertEquals('S-1-5-21-2127521184-1604012920-1887927527', $sut->getDomainPartAsSid()->getFormatted());
	}
}
