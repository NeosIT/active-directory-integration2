<?php
namespace Dreitier\AdLdap;

use Dreitier\Test\PHPUnitHelper;
use PHPUnit\Framework\TestCase;

/**
 * Custom test adapter so that we do not call `connect()` right on object creation
 */
class AdLdapTestAdapter extends AdLdap {
	public function __construct(array $options = []) {
	}
}

class AdLdapTest extends TestCase
{
	use \phpmock\phpunit\PHPMock;
	use PHPUnitHelper;

	private function sut($methods = [], $options = [])
	{
		$r = $this->getMockBuilder(AdLdapTestAdapter::class)
			->onlyMethods($methods)
			->getMock();

		if (!empty($options)) {
			$r->configureOptions($options);
		}

		return $r;
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
			->with(...self::withConsecutive(
				// first call is on deepest level
				[adLDAP::PARTITIONS_PREFIX . $baseDn, adLDAP::NETBIOS_MATCHER, []],
				// second call is on top level
				[adLDAP::PARTITIONS_PREFIX . $topDn, adLDAP::NETBIOS_MATCHER, []]
			))
			->willReturnOnConsecutiveCalls(
				// on deepest level, we don't find anything
				FALSE,
				// on DC=test,DC=ad we'll find the partition
				TRUE
			);

		$sut->expects($this->once())
			->method('_ldap_get_entries')
			->with(...self::withConsecutive(
				// with the first call, we don't do any further search as we simulate error code 32
				[true]
			))
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
			->with(...self::withConsecutive(
				[adLDAP::PARTITIONS_PREFIX . $baseDn, adLDAP::NETBIOS_MATCHER, []],
			))
			->willReturnOnConsecutiveCalls(
				// on deepest level, we don't find anything
				TRUE,
			);

		$sut->expects($this->once())
			->method('_ldap_get_entries')
			->with(...self::withConsecutive(
				// find something on top level
				[TRUE]
			))
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

	/**
	 * @test
	 * @issue #198
	 */
	public function buildConnectionUrl_returnsDefault389_whenNoPortIsSet() {
		$sut = $this->sut();

		$this->assertEquals("ldap://host:389", $sut->buildConnectionUrl('host'));
	}

	/**
	 * @test
	 * @issue #198
	 */
	public function buildConnectionUrl_returnsPort555_ifSpecified() {
		$sut = $this->sut(options: ['ad_port' => 555]);

		$this->assertEquals("ldap://host:555", $sut->buildConnectionUrl('host'));
	}

	/**
	 * @test
	 * @issue #198
	 */
	public function buildConnectionUrl_returnsLapsWithDefault636_ifSpecified() {
		$sut = $this->sut(options: ['use_ssl' => true]);

		$this->assertEquals("ldaps://host:636", $sut->buildConnectionUrl('host'));
	}

	/**
	 * @test
	 * @issue #198
	 */
	public function buildConnectionUrl_returnsLapsWithCustomPort_ifSpecified() {
		$sut = $this->sut(options: ['use_ssl' => true, 'ad_port' => 555]);

		$this->assertEquals("ldaps://host:555", $sut->buildConnectionUrl('host'));
	}

	/**
	 * @test
	 * @issue #198
	 */
	public function GH_198_deprecated_ldap_connect_method_signature_isNoLongerUsed() {
		$sut = new AdLdapTestAdapter();
		$sut->set_domain_controllers(['host']);

		$_ldap_connect = $this->getFunctionMock(__NAMESPACE__, "ldap_connect");

		$_ldap_connect->expects($this->once())->with("ldap://host:389")->willReturn(null);

		$sut->connect();
	}
}