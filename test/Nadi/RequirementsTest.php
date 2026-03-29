<?php

namespace Dreitier\Nadi;

use Dreitier\Test\BasicTestCase;
use Dreitier\Util\Internal\Native;
use Dreitier\Util\Util;
use Dreitier\WordPress\Multisite\Ui\Actions;
use Dreitier\WordPress\WordPressSiteRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class RequirementsTest extends BasicTestCase
{
	/* @var Native|\Mockery\MockInterface */
	private $internalNative;

	public function setUp(): void
	{
		parent::setUp();

		// mock native functions
		$this->internalNative = $this->createMockedNative();
		Util::native($this->internalNative);
	}


	public function tearDown(): void
	{
		parent::tearDown();
		// release mocked native functions
		Util::native(null);
	}

	#[Test]
	public function check_itSucceeds()
	{
		$sut = $this->sut(array(
			'requireWordPressVersion',
			'requirePhpVersion',
			'requireLdap',
			'requireMbstring',
			'requireOpenSSL',
			'preventTooManySites',
			'preventSiteActivation',
			'deactivateDeprecatedVersion'
		));
		$showErrors = true;

		\WP_Mock::userFunction('is_multisite', array(
			'times' => 1,
			'return' => true,
		));

		$sut->expects($this->once())
			->method('requireWordPressVersion')
			->with($showErrors);

		$sut->expects($this->once())
			->method('requirePhpVersion')
			->with($showErrors);

		$sut->expects($this->once())
			->method('requireLdap')
			->with($showErrors);

		$sut->expects($this->once())
			->method('requireMbstring')
			->with($showErrors);

		$sut->expects($this->once())
			->method('requireOpenSSL')
			->with($showErrors);

		$sut->expects($this->once())
			->method('preventTooManySites')
			->with($showErrors);

		// site activation must only be executed during activation
		$sut->expects($this->never())
			->method('preventSiteActivation');

		$sut->expects($this->once())
			->method('deactivateDeprecatedVersion');

		$this->assertTrue($sut->check($showErrors, $includeActivationCheck = false));
	}

	#[Test]
	public function check_itPreventsSiteActivation_whenIncludeActivationCheckIsEnabled()
	{
		$sut = $this->sut(array('requireWordPressVersion', 'requireLdap', 'requireMbstring', 'requireOpenSSL', 'preventTooManySites', 'preventSiteActivation', 'deactivateDeprecatedVersion'));
		$showErrors = true;

		\WP_Mock::userFunction('is_multisite', array(
			'times' => 1,
			'return' => true,
		));

		// site activation must only be executed during activation
		$sut->expects($this->once())
			->method('preventSiteActivation');

		$this->assertTrue($sut->check($showErrors, $includeActivationCheck = true));
	}

	#[Test]
	public function check_itDeactivatesAdi_ifRequirementsNotMet()
	{
		$sut = $this->sut(array('requireWordPressVersion'));
		$showErrors = true;

		\WP_Mock::userFunction('deactivate_plugins', array(
			'times' => 1,
			'return' => NEXT_ACTIVE_DIRECTORY_INTEGRATION_PLUGIN_FILE,
		));

		// mock away static methods
		$this->internalNative->expects($this->once())
			->method('includeOnce');

		$sut->expects($this->once())
			->method('requireWordPressVersion')
			->with($showErrors)
			->will($this->throwException(new RequirementException()));

		$this->assertFalse($sut->check($showErrors));
	}

	#[Test]
	public function requireWordPressVersion_itSucceeds()
	{
		$sut = $this->sut();

		// mock away static methods
		$this->internalNative->expects($this->once())
			->method('compare')
			->willReturn(false);

		$sut->requireWordPressVersion(true);
	}

	#[Test]
	public function requireWordPressVersion_itFails_ifVersionIsTooOld()
	{
		$sut = $this->sut();
		$this->expectException(RequirementException::class);

		// mock away static methods
		$this->internalNative->expects($this->once())
			->method('compare')
			->willReturn(true);

		// verify calls
		\WP_Mock::expectActionAdded(Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
			$sut, 'wrongWordPressVersion',
		));

		$sut->requireWordPressVersion(true);
	}

	#[Test]
	public function requireLdap_itFails_ifExtensionIsNotLoaded()
	{
		$sut = $this->sut();
		$this->expectException(RequirementException::class);

		// mock away static methods
		$this->internalNative->expects($this->once())
			->method('isLoaded')
			->with(Requirements::MODULE_LDAP)
			->willReturn(false);

		\WP_Mock::expectActionAdded(Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
			$sut, 'missingLdapModule',
		));

		$sut->requireLdap(true);
	}


	#[Test]
	public function requireLdap_itSucceeds()
	{
		$sut = $this->sut();

		// mock away static methods
		$this->internalNative->expects($this->once())
			->method('isLoaded')
			->with(Requirements::MODULE_LDAP)
			->willReturn(true);

		$sut->requireLdap(true);
	}

	#[Test]
	public function requireMbstring_itFails_ifExtensionIsNotLoaded()
	{
		$sut = $this->sut();
		$this->expectException(RequirementException::class);

		// mock away static methods
		$this->internalNative->expects($this->once())
			->method('isLoaded')
			->with(Requirements::MODULE_MBSTRING)
			->willReturn(false);

		\WP_Mock::expectActionAdded(Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
			$sut, 'missingMbstring',
		));

		$sut->requireMbstring(true);
	}

	#[Test]
	public function requireMbstring_itSucceeds()
	{
		$sut = $this->sut();

		// mock away static methods
		$this->internalNative->expects($this->once())
			->method('isLoaded')
			->with(Requirements::MODULE_MBSTRING)
			->willReturn(true);

		$sut->requireMbstring(true);
	}

	#[Test]
	public function requireOpenSSL_itFails_ifExtensionIsNotLoaded()
	{
		$sut = $this->sut();
		$this->expectException(RequirementException::class);

		// mock away static methods
		$this->internalNative->expects($this->once())
			->method('isLoaded')
			->with(Requirements::MODULE_OPENSSL)
			->willReturn(false);

		\WP_Mock::expectActionAdded(Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
			$sut, 'missingOpenSSL',
		));

		$sut->requireOpenSSL(true);
	}

	#[Test]
	public function requireOpenSSL_itSucceeds()
	{
		$sut = $this->sut();

		// mock away static methods
		$this->internalNative->expects($this->once())
			->method('isLoaded')
			->with(Requirements::MODULE_OPENSSL)
			->willReturn(true);

		$sut->requireOpenSSL(true);
	}

	#[Test]
	public function preventTooManySites_itFails_ifIsLargeNetwork()
	{
		$sut = $this->sut();
		$this->expectException(RequirementException::class);

		\WP_Mock::userFunction('wp_is_large_network', array(
			'times' => 1,
			'return' => true,
		));

		\WP_Mock::expectActionAdded(Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
			$sut, 'tooManySites',
		));

		$sut->preventTooManySites(true);
	}

	#[Test]
	public function preventTooManySites_itSucceeds()
	{
		$sut = $this->sut();

		\WP_Mock::userFunction('wp_is_large_network', array(
			'times' => 1,
			'return' => false,
		));

		$sut->preventTooManySites(true);
	}

	#[Test]
	public function preventSiteActivation_itFails_ifActivationIsInSite()
	{
		$sut = $this->sut();
		$this->expectException(RequirementException::class);

		\WP_Mock::userFunction('is_network_admin', array(
			'times' => 1,
			'return' => false,
		));


		\WP_Mock::expectActionAdded(Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
			$sut, 'networkSiteActivationNotAllowed',
		));

		$sut->preventSiteActivation(true);
	}

	#[Test]
	public function preventSiteActivation_itSucceeds()
	{
		$sut = $this->sut();

		\WP_Mock::userFunction('is_network_admin', array(
			'times' => 1,
			'return' => true,
		));

		$sut->preventSiteActivation(true);
	}

	#[Test]
	public function registerPostActivation_showsDeprecationMessage()
	{
		$sut = $this->sut(array('isPluginInstalled'));

		$sut->expects($this->once())
			->method('isPluginInstalled')
			->with(Requirements::DEPRECATED_ADI_PLUGIN_NAME)
			->willReturn(true);

		\WP_Mock::expectActionAdded(Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
			$sut, 'deactivatedDeprecatedAdiVersionMessage',
		));

		$sut->registerPostActivation();
	}

	#[Test]
	public function deactivateDeprecatedVersion_itDeactivatesPreviousVersion_ifActive()
	{
		$sut = $this->sut();

		// mock away static methods

		$this->internalNative->expects($this->once())
			->method('includeOnce');

		\WP_Mock::userFunction('is_plugin_active', array(
			'times' => 1,
			'args' => Requirements::DEPRECATED_ADI_PLUGIN_NAME,
			'return' => true,
		));

		\WP_Mock::userFunction('deactivate_plugins', array(
			'times' => 1,
			'args' => Requirements::DEPRECATED_ADI_PLUGIN_NAME,
			'return' => true,
		));

		$actual = $sut->deactivateDeprecatedVersion();
		$this->assertTrue($actual);
	}


	/**
	 * @issue #179
	 */
	#[Test]
	public function GH_179_ifPhpVersionIsNotAvailable_pluginIsDeactivated()
	{
		$sut = $this->sut();
		$this->expectException(RequirementException::class);
		$newestUnusableVersion = '7.4';

		// mock away static methods
		$this->internalNative->expects($this->once())
			->method('phpversion')
			->willReturn($newestUnusableVersion);

		$this->internalNative->expects($this->once())
			->method('compare')
			->with($newestUnusableVersion, Requirements::PHP_VERSION_REQUIRED, '<')
			->willReturn(true);

		\WP_Mock::expectActionAdded(Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
			$sut, 'wrongPhpVersion',
		));

		$sut->requirePhpVersion(true);
	}

	/**
	 *
	 * @param null $methods
	 *
	 * @return Requirements|MockObject
	 */
	private function sut(array $methods = [])
	{
		return $this->getMockBuilder(Requirements::class)
			->setConstructorArgs(
				[]
			)
			->onlyMethods($methods)
			->getMock();
	}
}