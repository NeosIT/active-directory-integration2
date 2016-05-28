<?php

/**
 * Adi_Synchronization_Stub is a stub for Adi_Synchronization_Abstract
 */
class Adi_Synchronization_Stub extends Adi_Synchronization_Abstract
{
	public function __construct(Multisite_Configuration_Service $configuration, Ldap_Connection $connection, Ldap_Attribute_Service $attributeService)
	{
		parent::__construct($configuration, $connection, $attributeService);
	}
}

/**
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_Synchronization_ActiveDirectoryTest extends Ut_BasicTest
{
	/* @var Multisite_Configuration_Service | PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/* @var Ldap_Connection | PHPUnit_Framework_MockObject_MockObject */
	private $ldapConnection;

	/* @var Ldap_Attribute_Service | PHPUnit_Framework_MockObject_MockObject */
	private $attributeService;

	public function setUp()
	{
		parent::setUp();

		$this->configuration = $this->createMock('Multisite_Configuration_Service');
		$this->ldapConnection = $this->createMock('Ldap_Connection');
		$this->attributeService = $this->createMock('Ldap_Attribute_Service');
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @param null $methods
	 *
	 * @return Adi_Synchronization_Stub|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('Adi_Synchronization_Stub')
			->setConstructorArgs(
				array(
					$this->attributeService,
					$this->configuration,
					$this->ldapConnection
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function increaseExecutionTime_whenSettingIsInsufficient_itSetsMaxExecutionTime()
	{
		$sut = $this->sut();
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @test
	 */
	public function connectToLdap_itReturnsConnectionAfterCheck()
	{
		$sut = $this->sut();
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @test
	 */
	public function findActiveDirectoryUsernames_itIgnoresNonDomainMember()
	{
		$sut = $this->sut('isVerifiedDomainMember');
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @test
	 */
	public function findActiveDirectoryUsernames_itReturnsDomainMember()
	{
		$sut = $this->sut('isVerifiedDomainMember');
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @test
	 */
	public function findActiveDirectoryUsers_itOnlyReturnsDomainMembers()
	{
		$sut = $this->sut();
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);

	}

	/**
	 * @test
	 */
	public function isVerifiedDomainMember_itChecksWithDomainSidOfConnection()
	{
		$sut = $this->sut();
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @test
	 */
	public function isUsernameInDomain_itReturnsTrue_whenUserIsVerifiedDomainMember()
	{
		$sut = $this->sut();
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @test
	 */
	public function isUsernameInDomain_itReturnsFalse_whenUserIsNotInDomain()
	{
		$sut = $this->sut();
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

}