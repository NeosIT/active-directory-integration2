
<?php

/**
 * @author Christopher Klein <ckl@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_Authentication_CredentialsTest extends Ut_BasicTest
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
	public function __construct_itSetsLoginAndPassword() {
		$sut = new NextADInt_Adi_Authentication_Credentials('LOGIN', 'password');

		$this->assertEquals('LOGIN', $sut->getLogin());
		$this->assertEquals('password', $sut->getPassword());
	}

	/**
	 * @test
	 */
	public function setLogin_itUpdatesUserPrincipalName() {
		$sut = new NextADInt_Adi_Authentication_Credentials('login', 'password');

		$sut->setUpnUsername('me');
		$sut->setUpnSuffix('@test.ad');

		$this->assertEquals('me', $sut->getUpnUsername());
		$this->assertEquals('test.ad', $sut->getUpnSuffix());
	}

	/**
	 * @test
	 * @issue ADI-389
	 */
	public function setNetbiosName_itUpdatesNetbiosName() {
		$sut = new NextADInt_Adi_Authentication_Credentials('upn', 'password');
		$this->assertEquals(null, $sut->getNetbiosName());

		$sut->setNetbiosName('NETBIOS');

		$this->assertEquals('NETBIOS', $sut->getNetbiosName());
	}

	/**
	 * @test
	 */
	public function setUserPrincipalName_itUpdatesUpnUsernameAndUpnSuffix() {
		$sut = new NextADInt_Adi_Authentication_Credentials('upn', 'password');

		$sut->setUserPrincipalName('upn@upnsuffix');

		$this->assertEquals('upn', $sut->getUpnUsername());
		$this->assertEquals('upnsuffix', $sut->getUpnSuffix());
	}
}