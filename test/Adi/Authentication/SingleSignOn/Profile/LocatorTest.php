<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Ut_NextADInt_Adi_Authentication_SingleSignOn_LocatorTest')) {
	return;
}

class Ut_NextADInt_Adi_Authentication_SingleSignOn_LocatorTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_Configuration_Service|PHPUnit_Framework_MockObject_MockObject $configuration */
	private $configuration;

	public function setUp(): void
	{
		parent::setUp();

		$this->configuration = $this->createMock('NextADInt_Multisite_Configuration_Service');

		// mock away our internal php calls
		$this->native = $this->createMockedNative();
		NextADInt_Core_Util::native($this->native);
	}

	public function tearDown(): void
	{
		parent::tearDown();
		NextADInt_Core_Util::native(null);
	}

	/**
	 * @param null $methods
	 *
	 * @return NextADInt_Adi_Authentication_SingleSignOn_Profile_Locator|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Adi_Authentication_SingleSignOn_Profile_Locator')
			->setConstructorArgs(
				array(
					$this->configuration
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @since 2.2.0
	 * @test
	 */
	public function locate_delegatesToNetbios_ifNotEmpty()
	{
		$sut = $this->sut(array('findBestConfigurationMatchForProfile'));
		$credentials = new NextADInt_Adi_Authentication_Credentials('NB\username');
		$credentials->setNetbiosName('NB');

		$profileMatch = array('profile_opts');

		$sut->expects($this->once())
			->method('findBestConfigurationMatchForProfile')
			->with(NextADInt_Adi_Configuration_Options::NETBIOS_NAME, $credentials->getNetbiosName())
			->willReturn($profileMatch);

		$actual = $sut->locate($credentials);

		$this->assertEquals($actual->getType(), NextADInt_Adi_Authentication_SingleSignOn_Profile_Match::NETBIOS);
		$this->assertEquals($actual->getProfile(), $profileMatch);
	}

	/**
	 * @since 2.2.0
	 * @test
	 */
	public function locate_delegatesToKerberos_ifSet()
	{
		$sut = $this->sut(array('findBestConfigurationMatchForProfile'));
		$credentials = new NextADInt_Adi_Authentication_Credentials('NB\username');
		$credentials->setUpnSuffix('suffix');

		$profileMatch = array('profile_opts');

		$sut->expects($this->once())
			->method('findBestConfigurationMatchForProfile')
			->with(NextADInt_Adi_Configuration_Options::KERBEROS_REALM_MAPPINGS, $credentials->getUpnSuffix(), false)
			->willReturn($profileMatch);

		$actual = $sut->locate($credentials);

		$this->assertEquals($actual->getType(), NextADInt_Adi_Authentication_SingleSignOn_Profile_Match::KERBEROS_REALM);
		$this->assertEquals($actual->getProfile(), $profileMatch);
	}

	/**
	 * @since 2.2.0
	 * @test
	 */
	public function locate_delegatesToUserPrincipalName_ifKerberosNotFound()
	{
		$sut = $this->sut(array('findBestConfigurationMatchForProfile'));
		$credentials = new NextADInt_Adi_Authentication_Credentials('NB\username');
		$credentials->setUpnSuffix('suffix');

		$profileMatch = array('profile_opts');

		$sut->expects($this->exactly(2))
			->method('findBestConfigurationMatchForProfile')
			->withConsecutive(
				[NextADInt_Adi_Configuration_Options::KERBEROS_REALM_MAPPINGS, $credentials->getUpnSuffix(), false],
				[NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX, '@' . $credentials->getUpnSuffix(), true]
			)
			->willReturnOnConsecutiveCalls(
				null,
				$profileMatch
			);

		$actual = $sut->locate($credentials);

		$this->assertEquals($actual->getType(), NextADInt_Adi_Authentication_SingleSignOn_Profile_Match::UPN_SUFFIX);
		$this->assertEquals($actual->getProfile(), $profileMatch);
	}

	/**
	 * @test
	 */
	public function findBestConfigurationMatchForProfile_withoutProfile_itReturnsNull()
	{
		$sut = $this->sut(array('findSsoEnabledProfiles'));
		$suffix = '@test';

		$this->behave($sut, 'findSsoEnabledProfiles', array());

		$actual = $sut->findBestConfigurationMatchForProfile(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX, $suffix);

		$this->assertNull($actual);
	}

	/**
	 * @test
	 */
	public function findBestConfigurationMatchForProfile_withoutCorrespondingProfileForSuffix_itReturnsProfileWithoutSuffixSet()
	{
		$sut = $this->sut(array('findSsoEnabledProfiles'));
		$suffix = '@test';

		$profiles = array(
			array(
				NextADInt_Adi_Configuration_Options::SSO_ENABLED => true,
				NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX => '@abc',
			),
			array(
				NextADInt_Adi_Configuration_Options::SSO_ENABLED => true,
				NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX => '',
			),
		);

		$this->behave($sut, 'findSsoEnabledProfiles', $profiles);

		$expected = $profiles[1];
		$actual = $sut->findBestConfigurationMatchForProfile(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX, $suffix);

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findBestConfigurationMatchForProfile_withCorrespondingProfileForSuffix_itReturnsCorrectProfile()
	{
		$sut = $this->sut(array('findSsoEnabledProfiles'));
		$suffix = '@test';

		$profiles = array(
			array(
				NextADInt_Adi_Configuration_Options::SSO_ENABLED => true,
				NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX => $suffix,
			),
			array(
				NextADInt_Adi_Configuration_Options::SSO_ENABLED => true,
				NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX => '',
			),
		);

		$this->behave($sut, 'findSsoEnabledProfiles', $profiles);

		$expected = $profiles[0];
		$actual = $sut->findBestConfigurationMatchForProfile(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX, $suffix);

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function normalizeSuffix_withoutSuffix_returnsExpectedResult()
	{
		$sut = $this->sut();

		$value = 'test';
		$expected = '@' . $value;
		$actual = $this->invokeMethod($sut, 'normalizeSuffix', array($value));

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function normalizeSuffix_withExistingSuffix_returnsExpectedResult()
	{
		$sut = $this->sut();

		$expected = '@test';
		$actual = $this->invokeMethod($sut, 'normalizeSuffix', array($expected));

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getProfilesWithOptionValue_returnsExpectedResult()
	{
		$sut = $this->sut();
		$suffix = '@test';

		$profiles = array(
			array(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX => $suffix),
			array(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX => ''),
		);

		$expected = array($profiles[0]);
		$actual = $this->invokeMethod($sut, 'getProfilesWithOptionValue', array(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX, $suffix, $profiles));
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getProfilesWithoutOptionValue_returnsExpectedResult()
	{
		$sut = $this->sut();

		$profiles = array(
			array(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX => '@test'),
			array(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX => ''),
		);

		$expected = array($profiles[1]);
		$actual = $this->invokeMethod($sut, 'getProfilesWithoutOptionValue', array(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX, $profiles));
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findSsoEnabledProfiles_returnsProfilesWithSsoEnabled()
	{
		$sut = $this->sut();

		$config = array(
			NextADInt_Adi_Configuration_Options::SSO_ENABLED => array(
				'option_value' => false,
				'option_permission' => 3,
			),
		);

		$profiles = array(
			array(
				NextADInt_Adi_Configuration_Options::SSO_ENABLED => array(
					'option_value' => true,
					'option_permission' => 3,
				),
			),
			array(
				NextADInt_Adi_Configuration_Options::SSO_ENABLED => array(
					'option_value' => false,
					'option_permission' => 3,
				),
			),
		);

		$this->configuration->expects($this->once())
			->method('findAllProfiles')
			->willReturn($profiles);

		$actual = $this->invokeMethod($sut, 'findSsoEnabledProfiles');
		$this->assertCount(1, $actual);
	}

	/**
	 * @test
	 */
	public function findSsoEnabledProfiles_noProfilesFound_returnsEmpty()
	{
		$sut = $this->sut();

		$config = array(
			NextADInt_Adi_Configuration_Options::SSO_ENABLED => array(
				'option_value' => false,
				'option_permission' => 3,
			),
		);

		$profiles = array();

		$this->configuration->expects($this->once())
			->method('findAllProfiles')
			->willReturn($profiles);

		$actual = $this->invokeMethod($sut, 'findSsoEnabledProfiles');
		$this->assertEmpty($actual);
	}

}