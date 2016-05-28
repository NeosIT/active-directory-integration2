<?php

/**
 * @author Danny Meissner <dme@neos-it.de>
 * @access private
 */
class Ut_Adi_Authentication_VerificationServiceTest extends Ut_BasicTest
{
	/* @var Ldap_Connection|PHPUnit_Framework_MockObject_MockObject $ldapConnection */
	private $ldapConnection;

	/* @var Ldap_Attribute_Repository|PHPUnit_Framework_MockObject_MockObject $attributeRepository */
	private $attributeRepository;


	public function setUp()
	{
		parent::setUp();

		$this->ldapConnection = $this->createMock('Ldap_Connection');
		$this->attributeRepository = $this->createMock('Ldap_Attribute_Repository');
	}


	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @return Adi_Authentication_VerificationService|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null, $simulated = false)
	{
		return $this->getMockBuilder('Adi_Authentication_VerificationService')
			->setConstructorArgs(
				array(
					$this->ldapConnection,
					$this->attributeRepository
				)
			)
			->setMethods($methods)
			->getMock();
	}


	/**
	 * @test
	 */
	public function verifyActiveDirectoryDomain_whenConnected_itReturnsObjectSid()
	{
		$this->markTestIncomplete('Not yet');
	}
}