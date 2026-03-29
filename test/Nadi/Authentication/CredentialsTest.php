<?php

namespace Dreitier\Nadi\Authentication;

use Dreitier\Test\BasicTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access private
 */
class CredentialsTest extends BasicTestCase
{

	public function setUp(): void
	{
		parent::setUp();
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	#[Test]
	public function __construct_itSetsLoginAndPassword()
	{
		$sut = new Credentials('LOGIN', 'password');

		$this->assertEquals('LOGIN', $sut->getLogin());
		$this->assertEquals('password', $sut->getPassword());
	}

	#[Test]
	public function setLogin_itUpdatesUserPrincipalName()
	{
		$sut = new Credentials('login', 'password');

		$sut->setUpnUsername('me');
		$sut->setUpnSuffix('@test.ad');

		$this->assertEquals('me', $sut->getUpnUsername());
		$this->assertEquals('test.ad', $sut->getUpnSuffix());
	}

	/**
	 * @issue ADI-389
	 */
	#[Test]
	public function setNetbiosName_itUpdatesNetbiosName()
	{
		$sut = new Credentials('upn', 'password');
		$this->assertEquals(null, $sut->getNetbiosName());

		$sut->setNetbiosName('NETBIOS');

		$this->assertEquals('NETBIOS', $sut->getNetbiosName());
	}

	#[Test]
	public function setUserPrincipalName_itUpdatesUpnUsernameAndUpnSuffix()
	{
		$sut = new Credentials('upn', 'password');

		$sut->setUserPrincipalName('upn@upnsuffix');

		$this->assertEquals('upn', $sut->getUpnUsername());
		$this->assertEquals('upnsuffix', $sut->getUpnSuffix());
	}
}