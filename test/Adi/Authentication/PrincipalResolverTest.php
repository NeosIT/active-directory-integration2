<?php

/**
 * @author Christopher Klein <ckl@neos-it.de>
 * @access private
 * @since ADI-620
 */
class Ut_NextADInt_Adi_Authentication_PrincipalResolverTest extends Ut_BasicTest
{

	public function setUp()
	{
		parent::setUp();
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function detectNetbiosName_returnsNetbiosPart()
	{
		$r = NextADInt_Adi_Authentication_PrincipalResolver::detectNetbiosName('domain\sAMAccountName');

		$this->assertEquals('DOMAIN', $r);
	}

	/**
	 * @test
	 */
	public function detectNetbiosName_whenDomainPartIsMissing_itReturnsNull()
	{
		$r = NextADInt_Adi_Authentication_PrincipalResolver::detectNetbiosName('sAMAccountName');

		$this->assertEquals(null, $r);
	}

	/**
	 * @test
	 */
	public function detectUserPrincipalParts_returnsUpnNameAndSuffix() {
		$r = NextADInt_Adi_Authentication_PrincipalResolver::detectUserPrincipalParts('upn@upnsuffix');

		$this->assertEquals('upn', $r[0]);
		$this->assertEquals('upnsuffix', $r[1]);
	}

	/**
	 * @test
	 */
	public function detectUserPrincipalParts_whenInvalidUpnFormat_itReturnsNull() {
		$r = NextADInt_Adi_Authentication_PrincipalResolver::detectUserPrincipalParts('upn_invalid_upnsuffix');

		$this->assertEquals(null, $r);
	}

	/**
	 * @test
	 */
	public function suggestSamaccountName_whenUpnFormat_itReturnsUserPrincipalName() {
		$r = NextADInt_Adi_Authentication_PrincipalResolver::suggestSamaccountName('upn@upn_suffix');

		$this->assertEquals('upn', $r);
	}

	/**
	 * @test
	 */
	public function suggestSamaccountName_whenNetbiosFormat_itReturnsUsername() {
		$r = NextADInt_Adi_Authentication_PrincipalResolver::suggestSamaccountName('domain\user');

		$this->assertEquals('user', $r);
	}

	/**
	 * @test
	 */
	public function forNewPrincipalResolvers_withUpnFormat_upnHasPrecdencesOverSamAccountName() {
		$r = new NextADInt_Adi_Authentication_PrincipalResolver('upn@upnsuffix');

		$this->assertEquals(null, $r->getNetbiosName());
		$this->assertEquals('upn', $r->getUpnUsername());
		$this->assertEquals('upnsuffix', $r->getUpnSuffix());
		$this->assertEquals('upn', $r->getSAMAccountName());
	}

	/**
	 * @test
	 */
	public function forNewPrincipalResolvers_withNetbiosFormat_userPrincipalNameIsIdenticalToUpn() {
		$r = new NextADInt_Adi_Authentication_PrincipalResolver('domain\user');

		$this->assertEquals('DOMAIN', $r->getNetbiosName());
		$this->assertEquals('user', $r->getUpnUsername());
		$this->assertEquals(null, $r->getUpnSuffix());
		$this->assertEquals('user', $r->getSAMAccountName());
	}

	/**
	 * @test
	 */
	public function forNewPrincipalResolvers_withPlainFormat_userPrincipalNameIsIdenticalToUpn() {
		$r = new NextADInt_Adi_Authentication_PrincipalResolver('any_principal');

		$this->assertEquals(null, $r->getNetbiosName());
		$this->assertEquals('any_principal', $r->getUpnUsername());
		$this->assertEquals(null, $r->getUpnSuffix());
		$this->assertEquals('any_principal', $r->getSAMAccountName());
	}

	/**
	 * @test
	 */
	public function createCredentials_hasExpectedValues() {
		$r = NextADInt_Adi_Authentication_PrincipalResolver::createCredentials('upn@upnsuffix', 'password');

		$this->assertEquals('password', $r->getPassword());
		$this->assertEquals('upn', $r->getUpnUsername());
		$this->assertEquals('upnsuffix', $r->getUpnSuffix());
		$this->assertEquals('upn', $r->getSAMAccountName());
		$this->assertEquals(null, $r->getNetbiosName());
	}
}