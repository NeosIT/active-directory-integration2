<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_RequirementsTest extends Ut_BasicTest
{
	/* @var NextADInt_Core_Util_Internal_Native|\Mockery\MockInterface */
	private $internalNative;
	
	/** @var \Mockery\MockInterface */
	private $wordPressActionHelper;
	
	/** @var \Mockery\MockInterface */
	private $wordPressSiteHelper;

	public function setUp() : void
	{
		parent::setUp();

		$this->wordPressActionHelper = $this->createMockedWordPressActionHelper();
		$this->wordPressSiteHelper = $this->createMockedWordPressSiteHelper();

		// mock native functions
		$this->internalNative = $this->createMockedNative();
		NextADInt_Core_Util::native($this->internalNative);
	}

	public function tearDown() : void
	{
		parent::tearDown();
		// release mocked native functions
		NextADInt_Core_Util::native(null);
	}

	/**
	 * @test
	 */
	public function check_itSucceeds() {
		$sut = $this->sut(array('requireWordPressVersion', 'requireLdap', 'requireMbstring', 'requireOpenSSL', 'preventTooManySites', 'preventSiteActivation', 'deactivateDeprecatedVersion'));
		$showErrors = true;

		WP_Mock::wpFunction('is_multisite', array(
			'times'  => 1,
			'return' => true,
		));

		$sut->expects($this->once())
			->method('requireWordPressVersion')
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

	/**
	 * @test
	 */
	public function check_itPreventsSiteActivation_whenIncludeActivationCheckIsEnabled() {
		$sut = $this->sut(array('requireWordPressVersion', 'requireLdap', 'requireMbstring', 'requireOpenSSL', 'preventTooManySites', 'preventSiteActivation', 'deactivateDeprecatedVersion'));
		$showErrors = true;

		WP_Mock::wpFunction('is_multisite', array(
			'times'  => 1,
			'return' => true,
		));

		// site activation must only be executed during activation
		$sut->expects($this->once())
			->method('preventSiteActivation');

		$this->assertTrue($sut->check($showErrors, $includeActivationCheck = true));
	}

	/**
	 * @test
	 */
	public function check_itDeactivatesAdi_ifRequirementsNotMet() {
		$sut = $this->sut(array('requireWordPressVersion'));
		$showErrors = true;

		WP_Mock::wpFunction('deactivate_plugins', array(
			'times'  => 1,
			'return' => NEXT_AD_INT_PLUGIN_FILE,
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

	/**
	 * @test
	 */
	public function requireWordPressVersion_itSucceeds()
	{
		$sut = $this->sut();

		// mock away static methods
		$this->internalNative->expects($this->once())
			->method('compare')
			->willReturn(false);

		$sut->requireWordPressVersion(true);
	}

	/**
	 * @test
	 */
	public function requireWordPressVersion_itFails_ifVersionIsTooOld()
	{
		$sut = $this->sut();
		$this->expectException(RequirementException::class);

		// mock away static methods
		$this->internalNative->expects($this->once())
			->method('compare')
			->willReturn(true);

		// verify calls
		WP_Mock::expectActionAdded(NextADInt_Adi_Ui_Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
			$sut, 'wrongWordPressVersion',
		));

		$sut->requireWordPressVersion(true);
	}

	/**
	 * @test
	 */
	public function requireLdap_itFails_ifExtensionIsNotLoaded()
	{
		$sut = $this->sut();
		$this->expectException(RequirementException::class);

		// mock away static methods
		$this->internalNative->expects($this->once())
			->method('isLoaded')
			->with(NextADInt_Adi_Requirements::MODULE_LDAP)
			->willReturn(false);

		WP_Mock::expectActionAdded(NextADInt_Adi_Ui_Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
			$sut, 'missingLdapModule',
		));

		$sut->requireLdap(true);
	}


	/**
	 * @test
	 */
	public function requireLdap_itSucceeds() {
		$sut = $this->sut();

		// mock away static methods
		$this->internalNative->expects($this->once())
			->method('isLoaded')
			->with(NextADInt_Adi_Requirements::MODULE_LDAP)
			->willReturn(true);

		$sut->requireLdap(true);
	}

	/**
	 * @test
	 */
	public function requireMbstring_itFails_ifExtensionIsNotLoaded()
	{
		$sut = $this->sut();
		$this->expectException(RequirementException::class);

		// mock away static methods
		$this->internalNative->expects($this->once())
			->method('isLoaded')
			->with(NextADInt_Adi_Requirements::MODULE_MBSTRING)
			->willReturn(false);

		WP_Mock::expectActionAdded(NextADInt_Adi_Ui_Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
			$sut, 'missingMbstring',
		));

		$sut->requireMbstring(true);
	}

	/**
	 * @test
	 */
	public function requireMbstring_itSucceeds() {
		$sut = $this->sut();

		// mock away static methods
		$this->internalNative->expects($this->once())
			->method('isLoaded')
			->with(NextADInt_Adi_Requirements::MODULE_MBSTRING)
			->willReturn(true);

		$sut->requireMbstring(true);
	}

    /**
     * @test
     */
    public function requireOpenSSL_itFails_ifExtensionIsNotLoaded()
    {
        $sut = $this->sut();
		$this->expectException(RequirementException::class);

        // mock away static methods
        $this->internalNative->expects($this->once())
            ->method('isLoaded')
            ->with(NextADInt_Adi_Requirements::MODULE_OPENSSL)
            ->willReturn(false);

        WP_Mock::expectActionAdded(NextADInt_Adi_Ui_Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
            $sut, 'missingOpenSSL',
        ));

        $sut->requireOpenSSL(true);
    }

    /**
     * @test
     */
    public function requireOpenSSL_itSucceeds() {
        $sut = $this->sut();

        // mock away static methods
        $this->internalNative->expects($this->once())
            ->method('isLoaded')
            ->with(NextADInt_Adi_Requirements::MODULE_OPENSSL)
            ->willReturn(true);

        $sut->requireOpenSSL(true);
    }

	/**
	 * @test
	 */
	public function preventTooManySites_itFails_ifIsLargeNetwork()
	{
		$sut = $this->sut();
		$this->expectException(RequirementException::class);

		WP_Mock::wpFunction('wp_is_large_network', array(
			'times'  => 1,
			'return' => true,
		));

		WP_Mock::expectActionAdded(NextADInt_Adi_Ui_Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
			$sut, 'tooManySites',
		));

		$sut->preventTooManySites(true);
	}

	/**
	 * @test
	 */
	public function preventTooManySites_itSucceeds() {
		$sut = $this->sut();

		WP_Mock::wpFunction('wp_is_large_network', array(
			'times'  => 1,
			'return' => false,
		));

		$sut->preventTooManySites(true);
	}

	/**
	 * @test
	 */
	public function preventSiteActivation_itFails_ifActivationIsInSite()
	{
		$sut = $this->sut();
		$this->expectException(RequirementException::class);

		WP_Mock::wpFunction('is_network_admin', array(
			'times'  => 1,
			'return' => false,
		));

		WP_Mock::expectActionAdded(NextADInt_Adi_Ui_Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
			$sut, 'networkSiteActivationNotAllowed',
		));

		$sut->preventSiteActivation(true);
	}

	/**
	 * @test
	 */
	public function preventSiteActivation_itSucceeds() {
		$sut = $this->sut();

		WP_Mock::wpFunction('is_network_admin', array(
			'times'  => 1,
			'return' => true,
		));

		$sut->preventSiteActivation(true);
	}

	/**
	 * @test
	 */
	public function registerPostActivation_showsDeprecationMessage() {
		$sut = $this->sut(array('isPluginInstalled'));

		$sut->expects($this->once())
			->method('isPluginInstalled')
			->with(NextADInt_Adi_Requirements::DEPRECATED_ADI_PLUGIN_NAME)
			->willReturn(true);

		WP_Mock::expectActionAdded(NextADInt_Adi_Ui_Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
			$sut, 'deactivatedDeprecatedAdiVersionMessage',
		));

		$sut->registerPostActivation();
	}

	/**
	 * @test
	 */
	public function deactivateDeprecatedVersion_itDeactivatesPreviousVersion_ifActive()
	{
		$sut = $this->sut();

		// mock away static methods

		$this->internalNative->expects($this->once())
			->method('includeOnce');

		wp_mock::wpfunction('is_plugin_active', array(
			'times'  => 1,
			'args' => NextADInt_Adi_Requirements::DEPRECATED_ADI_PLUGIN_NAME,
			'return' => true,
		));

		wp_mock::wpfunction('deactivate_plugins', array(
			'times'  => 1,
			'args' => NextADInt_Adi_Requirements::DEPRECATED_ADI_PLUGIN_NAME,
			'return' => true,
		));

		$actual = $sut->deactivateDeprecatedVersion();
		$this->assertTrue($actual);
	}

	/**
	 *
	 * @param null $methods
	 *
	 * @return NextADInt_Adi_Requirements|PHPUnit_Framework_MockObject_MockObject
	 */
	private function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Adi_Requirements')
			->setConstructorArgs(
				array()
			)
			->setMethods($methods)
			->getMock();
	}
}