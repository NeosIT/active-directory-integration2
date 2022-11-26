<?php

namespace Dreitier\Nadi\Authentication;

use Dreitier\Test\BasicTest;

/**
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access private
 */
class CredentialsTest extends BasicTest
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
	public function __construct_itSetsLoginAndPassword()
	{
		$sut = new Credentials('LOGIN', 'password');

		$this->assertEquals('LOGIN', $sut->getLogin());
		$this->assertEquals('password', $sut->getPassword());
	}

	/**
	 * @test
	 */
	public function setLogin_itUpdatesUserPrincipalName()
	{
		$sut = new Credentials('login', 'password');

		$sut->setUpnUsername('me');
		$sut->setUpnSuffix('@test.ad');

		$this->assertEquals('me', $sut->getUpnUsername());
		$this->assertEquals('test.ad', $sut->getUpnSuffix());
	}

	/**
	 * @test
	 * @issue ADI-389
	 */
	public function setNetbiosName_itUpdatesNetbiosName()
	{
		$sut = new Credentials('upn', 'password');
		$this->assertEquals(null, $sut->getNetbiosName());

		$sut->setNetbiosName('NETBIOS');

		$this->assertEquals('NETBIOS', $sut->getNetbiosName());
	}

	/**
	 * @test
	 */
	public function setUserPrincipalName_itUpdatesUpnUsernameAndUpnSuffix()
	{
		$sut = new Credentials('upn', 'password');

		$sut->setUserPrincipalName('upn@upnsuffix');

		$this->assertEquals('upn', $sut->getUpnUsername());
		$this->assertEquals('upnsuffix', $sut->getUpnSuffix());
	}
}