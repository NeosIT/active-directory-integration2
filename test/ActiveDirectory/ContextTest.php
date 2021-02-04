
<?php

/**
 * @author Christopher Klein <me[at]schakko[dot]de>
 * @access private
 */
class Ut_NextADInt_ActiveDirectory_ContextTest extends Ut_BasicTest
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
	 * @test
	 */
	public function __construct_throwsException_ifSidsAreEmpty() {
		$this->expectExceptionThrown(Exception::class);
		$sut = new NextADInt_ActiveDirectory_Context([]);
	}

	/**
	 * @test
	 */
	public function getPrimaryDomainSid_returnsFirst_ofAll() {
		$sut = new NextADInt_ActiveDirectory_Context(['first', 'second']);
		$this->assertEquals('FIRST', $sut->getPrimaryDomainSid());
	}

	/**
	 * @test
	 */
	public function getForestSidsreturnsAll() {
		$sids = ['first', 'second'];

		$sut = new NextADInt_ActiveDirectory_Context($sids);
		$this->assertEquals(['FIRST', 'SECOND'], $sut->getForestSids());
	}

	/**
	 * @test
	 */
	public function isMember_returnsTrue_ifIsMember() {
		$domainSids = ['S-1-5-21-2127521184-1604012920-55555', 'S-1-5-21-2127521184-1604012920-6666'];
		$member = NextADInt_ActiveDirectory_Sid::of('S-1-5-21-2127521184-1604012920-6666-1234');
		$sut = new NextADInt_ActiveDirectory_Context($domainSids);

		$this->assertTrue($sut->isMember($member));
	}

	/**
	 * @test
	 */
	public function isMember_returnsFalse_ifNotIsMember_inPrimaryDomain() {
		$domainSids = ['S-1-5-21-2127521184-1604012920-55555', 'S-1-5-21-2127521184-1604012920-6666'];
		$member = NextADInt_ActiveDirectory_Sid::of('S-1-5-21-2127521184-1604012920-6666-1234');
		$sut = new NextADInt_ActiveDirectory_Context($domainSids);

		$this->assertFalse($sut->isMember($member, true));
	}
}
