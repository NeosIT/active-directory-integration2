<?php

namespace Dreitier\ActiveDirectory;

use Dreitier\Test\BasicTestCase;

/**
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access private
 */
class ContextTest extends BasicTestCase
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
	public function __construct_throwsException_ifSidsAreEmpty()
	{
		$this->expectExceptionThrown(\Exception::class);
		$sut = new Context([]);
	}

	/**
	 * @test
	 */
	public function getPrimaryDomainSid_returnsFirst_ofAll()
	{
		$sut = new Context(['first', 'second']);
		$this->assertEquals('FIRST', $sut->getPrimaryDomainSid());
	}

	/**
	 * @test
	 */
	public function GEW_4086_sid_does_not_match()
	{
		$given = explode(",", "S-1-5-21-721228255-4152530800-340480405,S-1-5-21-1039548834-2980765846-2411786586,S-1-5-21-3280973328-3585321222-604173836,S-1-5-21-4266179657-1558324212-930312181,S-1-5-21-3446879627-3705196528-1814536650,S-1-5-21-1849792053-1545908873-1227565995,S-1-5-21-2396029296-1825183443-1932635969,S-1-5-21-2010830043-1564980032-3945708717,S-1-5-21-200721460-3957465712-881549826");
		$objectId = Sid::of("S-1-5-21-721228255-4152530800-340480405-4622");

		$sut = new Context($given);
		$this->assertTrue($sut->isMember($objectId));
	}

	/**
	 * @test
	 */
	public function getForestSidsreturnsAll()
	{
		$sids = ['first', 'second'];

		$sut = new Context($sids);
		$this->assertEquals(['FIRST', 'SECOND'], $sut->getForestSids());
	}

	/**
	 * @test
	 */
	public function isMember_returnsTrue_ifIsMember()
	{
		$domainSids = ['S-1-5-21-2127521184-1604012920-55555', 'S-1-5-21-2127521184-1604012920-6666'];
		$member = Sid::of('S-1-5-21-2127521184-1604012920-6666-1234');
		$sut = new Context($domainSids);

		$this->assertTrue($sut->isMember($member));
	}

	/**
	 * @test
	 */
	public function isMember_returnsFalse_ifNotIsMember_inPrimaryDomain()
	{
		$domainSids = ['S-1-5-21-2127521184-1604012920-55555', 'S-1-5-21-2127521184-1604012920-6666'];
		$member = Sid::of('S-1-5-21-2127521184-1604012920-6666-1234');
		$sut = new Context($domainSids);

		$this->assertFalse($sut->isMember($member, true));
	}
}
