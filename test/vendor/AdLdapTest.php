<?php

use PHPUnit\Framework\TestCase;

class AdLdapTest extends TestCase
{
	private function sut($methods = null, $adLdapOptions = array())
	{
		return $this->getMockBuilder('adLdap')
			->setConstructorArgs(array($adLdapOptions))
			->setMethods($methods)
			->getMock();
	}

	public function setUp(): void
	{
		parent::setUp();
	}

	/**
	 * @test
	 * @issue #153
	 */
	public function GH_153_inADForestTheUpperDNsAreSearched_whenResolvingTheConfiguration()
	{
		// - if base DN is set to DC=sub,DC=test,DC=ad
		// - BUT the partitions are below CN=Partitions,CN=Configuration,DC=test,DC=ad
		// - THEN we probably have an AD forest and have to match the mCName attribute
		$topDn = "DC=test,DC=ad";
		$baseDn = 'DC=sub,' . $topDn;
		$someOtherDn = 'DC=forest-a,' . $topDn;
		$sut = $this->sut(array('_ldap_get_entries', '_ldap_search'), array('base_dn' => $baseDn));

		$sut->expects($this->atLeast(2))
			->method('_ldap_search')
			->withConsecutive(
				// first call is on deepest level
				[adLDAP::PARTITIONS_PREFIX . $baseDn, adLDAP::NETBIOS_MATCHER, []],
				// second call is on top level
				[adLDAP::PARTITIONS_PREFIX . $topDn, adLDAP::NETBIOS_MATCHER, []]
			)
			->willReturnOnConsecutiveCalls(
				// on deepest level, we don't find anything
				FALSE,
				// on DC=test,DC=ad we'll find the partition
				TRUE
			);

		$sut->expects($this->once())
			->method('_ldap_get_entries')
			->withConsecutive(
				// with the first call, we don't do any further search as we simulate error code 32
				[TRUE]
			)
			->willReturnOnConsecutiveCalls(
				// on DC=test,DC=ad we'll find the partition
				[
					'count' => 2,
					[
						'netbiosname' => ['SUBNETBIOS', 'count' => 1],
						adLDAP::NCNAME_ATTRIBUTE => [$someOtherDn, 'count' => 1]
					],
					[
						'netbiosname' => ['CORRECTNETBIOS', 'count' => 1],
						adLDAP::NCNAME_ATTRIBUTE => [$baseDn, 'count' => 1],
					],
				]
			);

		$r = $sut->get_configuration("netbiosname");
		$this->assertEquals('CORRECTNETBIOS', $r);
	}

	/**
	 * @test
	 * @issue #153
	 */
	public function GH_153_inSingleDomain_theNetbiosConfigurationIsReturned()
	{
		$baseDn = 'DC=test,DC=ad';
		$sut = $this->sut(array('_ldap_get_entries', '_ldap_search'), array('base_dn' => $baseDn));

		$sut->expects($this->once())
			->method('_ldap_search')
			->withConsecutive(
				[adLDAP::PARTITIONS_PREFIX . $baseDn, adLDAP::NETBIOS_MATCHER, []],
			)
			->willReturnOnConsecutiveCalls(
				// on deepest level, we don't find anything
				TRUE,
			);

		$sut->expects($this->once())
			->method('_ldap_get_entries')
			->withConsecutive(
				// find something on top level
				[TRUE]
			)
			->willReturnOnConsecutiveCalls(
				// on DC=test,DC=ad we'll find the partition
				[
					'count' => 1,
					[
						'netbiosname' => ['CORRECTNETBIOS', 'count' => 1],
						adLDAP::NCNAME_ATTRIBUTE => [$baseDn, 'count' => 1],
					],
				]
			);

		$r = $sut->get_configuration("netbiosname");
		$this->assertEquals('CORRECTNETBIOS', $r);
	}

	/**
	 * @test
	 * @issue #153
	 */
	public function sanitizeDistinguishedName() {
		$sut = $this->sut();
		$this->assertEquals("dc=test,dc=ad", $sut->sanitizeDistinguishedName("DC=test,DC=ad "));
	}
}