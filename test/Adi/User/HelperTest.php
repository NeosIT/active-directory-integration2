<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_User_HelperTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_Configuration_Service| PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	public function setUp() : void
	{
		$this->configuration = $this->getMockBuilder('NextADInt_Multisite_Configuration_Service')
			->disableOriginalConstructor()
			->setMethods(array('getOptionValue'))
			->getMock();

		WP_Mock::setUp();
	}

	public function tearDown() : void
	{
		WP_Mock::tearDown();
	}

	/**
	 * @test
	 */
	public function getEnrichedUserData_withAutoUpdateDescriptionFalseAndDisplayNameEmpty_returnsExceptedResult()
	{
		$sut = $this->sut(array('getDisplayName'));

		$ldapAttributes = array(
			'givenname' => 'givenname',
			'sn'        => 'surname',
		);

		$adiUser = $this->createMock('NextADInt_Adi_User');
		$this->behave($adiUser, 'getLdapAttributes', new NextADInt_Ldap_Attributes(array(), $ldapAttributes));
		$this->behave($adiUser, 'getId', 1);
		$this->behave($adiUser, 'getCredentials', NextADInt_Adi_Authentication_PrincipalResolver::createCredentials('username'));

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::AUTO_UPDATE_DESCRIPTION)
			->willReturn(false);

		$sut->expects($this->once())
			->method('getDisplayName')
			->with('username', $ldapAttributes)
			->willReturn(null);

		$expected = array(
			'ID'         => 1,
			'first_name' => 'givenname',
			'last_name'  => 'surname',
		);

		$actual = $sut->getEnrichedUserData($adiUser);

		$this->assertEquals($expected, $actual);
	}


	/**
	 * @test
	 */
	public function getAccountSuffix_returnSuffix()
	{
		$sut = $this->sut(null);

		$userAttributeValues = array('userprincipalname' => 'test@company.it');
		$expectedReturn = "@company.it";
		$returnedValue = $sut->getAccountSuffix($userAttributeValues);

		$this->assertEquals($expectedReturn, $returnedValue);
	}

	/**
	 * @test
	 */
	public function getUserAccountSuffix_returnEmptyString()
	{
		$sut = $this->sut(null);

		$userAttributeValues = array('userprincipalname' => 'testcompany.it');
		$expectedReturn = "";
		$returnedValue = $sut->getAccountSuffix($userAttributeValues);

		$this->assertEquals($expectedReturn, $returnedValue);
	}

	/**
	 * @test
	 */
	public function createPassword_handlesDataCorrectly()
	{
		$sut = $this->sut(array('isRandomGeneratePassword', 'getRandomPassword'));

		$sut->expects($this->once())
			->method('isRandomGeneratePassword')
			->with(false)
			->willReturn(true);

		$sut->expects($this->once())
			->method('getRandomPassword')
			->with(true, 'password')
			->willReturn('new-password');

		$actual = $sut->getPassword('password', false);
		$this->assertEquals('new-password', $actual);
	}

	/**
	 * @test
	 */
	public function isRandomGeneratePassword_withSyncToWordPressFalseAndNoRandomPasswordFalse_returnsExpectedResult()
	{
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::NO_RANDOM_PASSWORD)
			->willReturn(false);

		$actual = $this->invokeMethod($sut, 'isRandomGeneratePassword', array(false));
		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function isRandomGeneratePassword_withSyncToWordPressFalseAndNoRandomPasswordTrue_returnsExpectedResult()
	{
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::NO_RANDOM_PASSWORD)
			->willReturn(true);

		$actual = $this->invokeMethod($sut, 'isRandomGeneratePassword', array(false));
		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function isRandomGeneratePassword_withSyncToWordPressTrueAndNoRandomPasswordFalse_returnsExpectedResult()
	{
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::NO_RANDOM_PASSWORD)
			->willReturn(false);

		$actual = $this->invokeMethod($sut, 'isRandomGeneratePassword', array(true));
		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function isRandomGeneratePassword_withSyncToWordPressTrueAndNoRandomPasswordTrue_returnsExpectedResult()
	{
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::NO_RANDOM_PASSWORD)
			->willReturn(true);

		$actual = $this->invokeMethod($sut, 'isRandomGeneratePassword', array(true));
		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function getPassword_withGenerateRandomPasswordFalse_returnsDefaultPassword()
	{
		$sut = $this->sut(null);

		$expected = 'password';
		$actual = $this->invokeMethod($sut, 'getRandomPassword', array(false, $expected));

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getDisplayNameFromUserAttributeValues_returnUsernameIfEmptyOrSamaAccountName()
	{
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with('name_pattern')
			->willReturn('samaccountname');

		$expectedUsername = "testUser";

		$actualUsername = $sut->getDisplayName($expectedUsername, null);
		$this->assertTrue(is_string($actualUsername));
		$this->assertTrue(!empty($actualUsername));
		$this->assertEquals($expectedUsername, $actualUsername);
	}

	/**
	 * @test
	 */
	public function getDisplayNameFromUserAttributeValues_generateWantedDisplayName()
	{
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with('name_pattern')
			->willReturn('givenname sn');

		$userAttributeValues = array(
			'givenname' => 'testName',
			'sn'        => '123'
		);

		$expectedUsername = 'testName 123';

		$actualUsername = $sut->getDisplayName(null, $userAttributeValues);
		$this->assertTrue(is_string($actualUsername));
		$this->assertTrue(!empty($actualUsername));
		$this->assertEquals($expectedUsername, $actualUsername);
	}

	/**
	 * @test
	 */
	public function getDisplayNameFromUserAttributeValues_returnUsernameIfDisplayNameEmpty()
	{
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with('name_pattern')
			->willReturn('someValue');

		$expectedUsername = 'testName';

		$actualUsername = $sut->getDisplayName($expectedUsername, null);
		$this->assertTrue(is_string($actualUsername));
		$this->assertTrue(!empty($actualUsername));
		$this->assertEquals($expectedUsername, $actualUsername);
	}

	/**
	 * @test
	 */
	public function createUniqueEmailAddress()
	{
		$sut = $this->sut(array('checkIfEmailExists'));

		\WP_Mock::wpFunction(
			'email_exists', array(
				'args'   => 'test@company.de',
				'times'  => 1,
			'return' => false
			)
		);

		$expectedEmail = "test@company.de";

		$email = $sut->createUniqueEmailAddress($expectedEmail);
		$this->assertTrue(is_string($email));
		$this->assertTrue(!empty($email));
		$this->assertEquals($expectedEmail, $email);
	}

	/**
	 * @test
	 */
	public function createUniqueEmailAddress_withExistingEmail()
	{
		$sut = $this->sut(null);

		\WP_Mock::wpFunction(
			'email_exists', array(
				'args'   => 'test@company.de',
				'times'  => 1,
			'return' => true
			)
		);

		\WP_Mock::wpFunction(
			'email_exists', array(
				'args'   => 'test0@company.de',
				'times'  => 1,
			'return' => false
			)
		);

		$email = $sut->createUniqueEmailAddress("test@company.de");
		$this->assertTrue(is_string($email));
		$this->assertTrue(!empty($email));
		$this->assertEquals("test0@company.de", $email);
	}

	/**
	 * @param $methods array methods to mock
	 *
	 * @return NextADInt_Adi_User_Helper|PHPUnit_Framework_MockObject_MockObject
	 */
	private function sut($methods)
	{
		return $this->getMockBuilder('NextADInt_Adi_User_Helper')
			->setConstructorArgs(
				array(
					$this->configuration,
				)
			)
			->setMethods($methods)
			->getMock();
	}
}


