<?php

namespace Dreitier\Nadi\Authentication;

use Dreitier\Test\BasicTestCase;

/**
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access private
 * @since ADI-620
 */
class PrincipalResolverTest extends BasicTestCase
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
	public function detectNetbiosName_returnsNetbiosPart()
	{
		$r = PrincipalResolver::detectNetbiosName('domain\sAMAccountName');

		$this->assertEquals('DOMAIN', $r);
	}

	/**
	 * @test
	 */
	public function detectNetbiosName_whenDomainPartIsMissing_itReturnsNull()
	{
		$r = PrincipalResolver::detectNetbiosName('sAMAccountName');

		$this->assertEquals(null, $r);
	}

	/**
	 * @test
	 */
	public function detectUserPrincipalParts_returnsUpnNameAndSuffix()
	{
		$r = PrincipalResolver::detectUserPrincipalParts('upn@upnsuffix');

		$this->assertEquals('upn', $r[0]);
		$this->assertEquals('upnsuffix', $r[1]);
	}

	/**
	 * @test
	 */
	public function detectUserPrincipalParts_whenInvalidUpnFormat_itReturnsNull()
	{
		$r = PrincipalResolver::detectUserPrincipalParts('upn_invalid_upnsuffix');

		$this->assertEquals(null, $r);
	}

	/**
	 * @test
	 */
	public function suggestSamaccountName_whenUpnFormat_itReturnsUserPrincipalName()
	{
		$r = PrincipalResolver::suggestSamaccountName('upn@upn_suffix');

		$this->assertEquals('upn', $r);
	}

	/**
	 * @test
	 */
	public function suggestSamaccountName_whenNetbiosFormat_itReturnsUsername()
	{
		$r = PrincipalResolver::suggestSamaccountName('domain\user');

		$this->assertEquals('user', $r);
	}

	/**
	 * @test
	 */
	public function forNewPrincipalResolvers_withUpnFormat_upnHasPrecdencesOverSamAccountName()
	{
		$r = new PrincipalResolver('upn@upnsuffix');

		$this->assertEquals(null, $r->getNetbiosName());
		$this->assertEquals('upn', $r->getUpnUsername());
		$this->assertEquals('upnsuffix', $r->getUpnSuffix());
		$this->assertEquals('upn', $r->getSAMAccountName());
	}

	/**
	 * @test
	 */
	public function forNewPrincipalResolvers_withNetbiosFormat_userPrincipalNameIsIdenticalToUpn()
	{
		$r = new PrincipalResolver('domain\user');

		$this->assertEquals('DOMAIN', $r->getNetbiosName());
		$this->assertEquals('user', $r->getUpnUsername());
		$this->assertEquals(null, $r->getUpnSuffix());
		$this->assertEquals('user', $r->getSAMAccountName());
	}

	/**
	 * @test
	 */
	public function forNewPrincipalResolvers_withPlainFormat_userPrincipalNameIsIdenticalToUpn()
	{
		$r = new PrincipalResolver('any_principal');

		$this->assertEquals(null, $r->getNetbiosName());
		$this->assertEquals('any_principal', $r->getUpnUsername());
		$this->assertEquals(null, $r->getUpnSuffix());
		$this->assertEquals('any_principal', $r->getSAMAccountName());
	}

	/**
	 * @test
	 */
	public function createCredentials_hasExpectedValues()
	{
		$r = PrincipalResolver::createCredentials('upn@upnsuffix', 'password');

		$this->assertEquals('password', $r->getPassword());
		$this->assertEquals('upn', $r->getUpnUsername());
		$this->assertEquals('upnsuffix', $r->getUpnSuffix());
		$this->assertEquals('upn', $r->getSAMAccountName());
		$this->assertEquals(null, $r->getNetbiosName());
	}
}