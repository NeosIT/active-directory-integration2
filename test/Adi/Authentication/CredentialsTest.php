
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

		$this->assertEquals('login', $sut->getLogin());
		$this->assertEquals('password', $sut->getPassword());
	}

	/**
	 * @test
	 */
	public function setUserPrincipalName_itSplitsUpn() {
		$sut = new NextADInt_Adi_Authentication_Credentials('login', 'password');

		$sut->setUserPrincipalName('me@test.ad');

		$this->assertEquals('me', $sut->getUpnUsername());
		$this->assertEquals('test.ad', $sut->getUpnSuffix());
		$this->assertEquals('me@test.ad', $sut->getUserPrincipalName());
	}

	/**
	 * @test
	 */
	public function setLogin_itUpdatesUserPrincipalName() {
		$sut = new NextADInt_Adi_Authentication_Credentials('login', 'password');

		$sut->setLogin('me@test.ad');

		$this->assertEquals('me', $sut->getUpnUsername());
		$this->assertEquals('test.ad', $sut->getUpnSuffix());
		$this->assertEquals('me', $sut->getSAMAccountName());
	}

	/**
	 * @test
	 * @issue ADI-389
	 */
	public function setNetbiosName_itUpdatesNetbiosName() {
		$sut = new NextADInt_Adi_Authentication_Credentials('upn', 'password');
		$this->assertEquals(null, $sut->getNetbiosName());

		$sut->setLogin('netbios\samaccountname');

		$this->assertEquals('NETBIOS', $sut->getNetbiosName());
		$this->assertEquals('netbios\samaccountname', $sut->getLogin());
	}

}