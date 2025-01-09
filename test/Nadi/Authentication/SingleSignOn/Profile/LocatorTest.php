<?php

namespace Dreitier\Nadi\Authentication\SingleSignOn\Profile;

use Dreitier\Nadi\Authentication\Credentials;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Test\BasicTestCase;
use Dreitier\Util\Internal\Native;
use Dreitier\Util\Util;
use Dreitier\WordPress\Multisite\Configuration\Service;
use PHPUnit\Framework\MockObject\MockObject;

class LocatorTest extends BasicTestCase
{
	/* @var Service|MockObject $configuration */
	private $configuration;

	/** @var \Mockery\MockInterface|Native  */
	private $native;

	public function setUp(): void
	{
		parent::setUp();

		$this->configuration = $this->createMock(Service::class);

		// mock away our internal php calls
		$this->native = $this->createMockedNative();
		Util::native($this->native);
	}

	public function tearDown(): void
	{
		parent::tearDown();
		Util::native(null);
	}

	/**
	 * @param null $methods
	 *
	 * @return Locator|MockObject
	 */
	public function sut(array $methods = [])
	{
		return $this->getMockBuilder(Locator::class)
			->setConstructorArgs(
				array(
					$this->configuration
				)
			)
			->onlyMethods($methods)
			->getMock();
	}

	/**
	 * @since 2.2.0
	 * @test
	 */
	public function locate_delegatesToNetbios_ifNotEmpty()
	{
		$sut = $this->sut(array('findBestConfigurationMatchForProfile'));
		$credentials = new Credentials('NB\username');
		$credentials->setNetbiosName('NB');

		$profileMatch = array('profile_opts');

		$sut->expects($this->once())
			->method('findBestConfigurationMatchForProfile')
			->with(Options::NETBIOS_NAME, $credentials->getNetbiosName())
			->willReturn($profileMatch);

		$actual = $sut->locate($credentials);

		$this->assertEquals($actual->getType(), Matcher::NETBIOS);
		$this->assertEquals($actual->getProfile(), $profileMatch);
	}

	/**
	 * @since 2.2.0
	 * @test
	 */
	public function locate_delegatesToKerberos_ifSet()
	{
		$sut = $this->sut(array('findBestConfigurationMatchForProfile'));
		$credentials = new Credentials('NB\username');
		$credentials->setUpnSuffix('suffix');

		$profileMatch = array('profile_opts');

		$sut->expects($this->once())
			->method('findBestConfigurationMatchForProfile')
			->with(Options::KERBEROS_REALM_MAPPINGS, $credentials->getUpnSuffix(), false)
			->willReturn($profileMatch);

		$actual = $sut->locate($credentials);

		$this->assertEquals($actual->getType(), Matcher::KERBEROS_REALM);
		$this->assertEquals($actual->getProfile(), $profileMatch);
	}

	/**
	 * @since 2.2.0
	 * @test
	 */
	public function locate_delegatesToUserPrincipalName_ifKerberosNotFound()
	{
		$sut = $this->sut(array('findBestConfigurationMatchForProfile'));
		$credentials = new Credentials('NB\username');
		$credentials->setUpnSuffix('suffix');

		$profileMatch = array('profile_opts');

		$sut->expects($this->exactly(2))
			->method('findBestConfigurationMatchForProfile')
			->with(...self::withConsecutive(
				[Options::KERBEROS_REALM_MAPPINGS, $credentials->getUpnSuffix(), false],
				[Options::ACCOUNT_SUFFIX, '@' . $credentials->getUpnSuffix(), true]
			))
			->willReturnOnConsecutiveCalls(
				null,
				$profileMatch
			);

		$actual = $sut->locate($credentials);

		$this->assertEquals($actual->getType(), Matcher::UPN_SUFFIX);
		$this->assertEquals($actual->getProfile(), $profileMatch);
	}

	/**
	 * @test
	 */
	public function findBestConfigurationMatchForProfile_withoutProfile_itReturnsNull()
	{
		$sut = $this->sut(array('findSsoEnabledProfiles'));
		$suffix = '@test';

		$this->behave($sut, 'findSsoEnabledProfiles', []);

		$actual = $sut->findBestConfigurationMatchForProfile(Options::ACCOUNT_SUFFIX, $suffix);

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
				Options::SSO_ENABLED => true,
				Options::ACCOUNT_SUFFIX => '@abc',
			),
			array(
				Options::SSO_ENABLED => true,
				Options::ACCOUNT_SUFFIX => '',
			),
		);

		$this->behave($sut, 'findSsoEnabledProfiles', $profiles);

		$expected = $profiles[1];
		$actual = $sut->findBestConfigurationMatchForProfile(Options::ACCOUNT_SUFFIX, $suffix);

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
				Options::SSO_ENABLED => true,
				Options::ACCOUNT_SUFFIX => $suffix,
			),
			array(
				Options::SSO_ENABLED => true,
				Options::ACCOUNT_SUFFIX => '',
			),
		);

		$this->behave($sut, 'findSsoEnabledProfiles', $profiles);

		$expected = $profiles[0];
		$actual = $sut->findBestConfigurationMatchForProfile(Options::ACCOUNT_SUFFIX, $suffix);

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
			array(Options::ACCOUNT_SUFFIX => $suffix),
			array(Options::ACCOUNT_SUFFIX => ''),
		);

		$expected = array($profiles[0]);
		$actual = $this->invokeMethod($sut, 'getProfilesWithOptionValue', array(Options::ACCOUNT_SUFFIX, $suffix, $profiles));
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getProfilesWithoutOptionValue_returnsExpectedResult()
	{
		$sut = $this->sut();

		$profiles = array(
			array(Options::ACCOUNT_SUFFIX => '@test'),
			array(Options::ACCOUNT_SUFFIX => ''),
		);

		$expected = array($profiles[1]);
		$actual = $this->invokeMethod($sut, 'getProfilesWithoutOptionValue', array(Options::ACCOUNT_SUFFIX, $profiles));
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findSsoEnabledProfiles_returnsProfilesWithSsoEnabled()
	{
		$sut = $this->sut();

		$config = array(
			Options::SSO_ENABLED => array(
				'option_value' => false,
				'option_permission' => 3,
			),
		);

		$profiles = array(
			array(
				Options::SSO_ENABLED => array(
					'option_value' => true,
					'option_permission' => 3,
				),
			),
			array(
				Options::SSO_ENABLED => array(
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
			Options::SSO_ENABLED => array(
				'option_value' => false,
				'option_permission' => 3,
			),
		);

		$profiles = [];

		$this->configuration->expects($this->once())
			->method('findAllProfiles')
			->willReturn($profiles);

		$actual = $this->invokeMethod($sut, 'findSsoEnabledProfiles');
		$this->assertEmpty($actual);
	}

}