<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_Configuration_ImportServiceTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository |PHPUnit_Framework_MockObject_MockObject */
	private $blogConfigurationRepository;

	/* @var NextADInt_Multisite_Configuration_Service|PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/* @var NextADInt_Core_Util_Internal_Native|\Mockery\MockInterface */
	private $internalNative;

	/* @var NextADInt_Multisite_Option_Provider|PHPUnit_Framework_MockObject_MockObject */
	private $optionProvider;

	public function setUp() : void
	{
		parent::setUp();

		$this->blogConfigurationRepository = $this->createMock('NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository');
		$this->configuration = $this->createMock('NextADInt_Multisite_Configuration_Service');
		$this->optionProvider = new NextADInt_Adi_Configuration_Options();

		// mock native functions
		$this->internalNative = $this->createMockedNative();
		NextADInt_Core_Util::native($this->internalNative);
	}

	public function tearDown() : void
	{
        global $wp_version;
        unset($wp_version);
		parent::tearDown();
		// release mocked native functions
		NextADInt_Core_Util::native(null);
	}

	/**
	 *
	 * @return NextADInt_Adi_Configuration_ImportService| PHPUnit_Framework_MockObject_MockObject
	 */
	private function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Adi_Configuration_ImportService')
			->setConstructorArgs(
				array(
					$this->blogConfigurationRepository,
					$this->configuration,
					$this->optionProvider
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function registerPostActivation_withTransient_itRegistersTheOutputOfMigrationNotices()
	{
		$sut = $this->sut(null);

        \WP_Mock::wpFunction( 'get_transient', array(
            'times' => 1,
            'args' => array( NextADInt_Adi_Init::NEXT_AD_INT_PLUGIN_HAS_BEEN_ENABLED),
            'return' => true,
        ));

        \WP_Mock::expectActionAdded('all_admin_notices', array($sut, 'createMigrationNotices'));

        \WP_Mock::wpFunction( 'delete_transient', array(
            'times' => 1,
            'args' => array( NextADInt_Adi_Init::NEXT_AD_INT_PLUGIN_HAS_BEEN_ENABLED)
        ));

		$sut->registerPostActivation();
	}

    /**
     * @test
     */
    public function registerPostActivation_withoutTransient_doNothing()
    {
        $sut = $this->sut(null);

        \WP_Mock::wpFunction( 'get_transient', array(
            'times' => 1,
            'args' => array( NextADInt_Adi_Init::NEXT_AD_INT_PLUGIN_HAS_BEEN_ENABLED),
            'return' => false,
        ));

        \WP_Mock::wpFunction( 'delete_transient', array(
            'times' => 0,
            'args' => array( NextADInt_Adi_Init::NEXT_AD_INT_PLUGIN_HAS_BEEN_ENABLED)
        ));

        $sut->registerPostActivation();
    }

	/**
	 * public function
	 * /**
	 * @test
	 */
	public function autoImport_itDoesNothing_whenRunningInMultisiteEnvironment()
	{
		$sut = $this->sut(array('updateSite'));

		WP_Mock::wpFunction('is_multisite', array(
			'times' => 1,
			'return' => true,
		));

		$sut->expects($this->never())
			->method('updateSite');

		$result = $sut->autoImport();
		$this->assertFalse($result);
	}

	/**
	 * @test
	 */
	public function autoImport_returnsResultFromUpdateSite_inNonMultiSiteEnvironment()
	{
		$sut = $this->sut(array('updateSite'));

		WP_Mock::wpFunction('is_multisite', array(
			'times' => 1,
			'return' => false,
		));

		$sut->expects($this->once())
			->method('updateSite')
			->willReturn(true);

		$result = $sut->autoImport();
		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function createMigrationNotices_echosAmountOfBlogsInMultisite()
	{
		$this->mockFunction__();

		WP_Mock::wpFunction('is_multisite', array(
			'times' => 1,
			'return' => true,
		));

		WP_Mock::wpFunction('is_network_admin', array(
			'times' => 1,
			'return' => true,
		));

        // NextADInt_Core_Util_Internal_WordPress::getSites() will call wp_get_sites when wp_version == 4.5
        global $wp_version;
        $wp_version = '4.5';
		WP_Mock::wpFunction('wp_get_sites', array(
			'times' => 1,
			'return' => array(array('blog_id' => 1), array('blog_id' => 2)),
		));

		$sut = $this->sut(array('getBlogVersion'));

		$sut->expects($this->exactly(2))
			->method('getBlogVersion')
			->withConsecutive(
				array(1),
				array(2)
			)
			->willReturn(true);

		$this->expectOutputRegex('/are 2 sites/');

		$sut->createMigrationNotices();
	}

	/**
	 * @test
	 */
	public function createMigrationNotices_echosMigrationHintInSingleSite()
	{
		WP_Mock::wpFunction('is_multisite', array(
			'times' => 1,
			'return' => false,
		));

		$sut = $this->sut(array('getBlogVersion'));
		$this->mockFunction__();

		$sut->expects($this->once())
			->method('getBlogVersion')
			->willReturn(true);

		$this->expectOutputRegex('/have been migrated/');

		$sut->createMigrationNotices();
	}


	/**
	 * @test
	 */
	public function updateSite_returnsTrue_ifUpdateOldVersionWasSuccessful()
	{
		$version = '1.1.5';

		$sut = $this->sut(array('getBlogVersion', 'migratePreviousVersion'));

		$sut->expects($this->once())
			->method('getBlogVersion')
			->with(null)
			->willReturn($version);

		$sut->expects($this->once())
			->method('migratePreviousVersion')
			->with($version, null)
			->willReturn(true);


		$result = $this->invokeMethod($sut, 'updateSite');
		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function updateSite_returnsFalse_ifUpdateOldVersionWasNotSuccessful()
	{
		$version = '1.1.5';

		$sut = $this->sut(array('getBlogVersion', 'migratePreviousVersion'));

		$sut->expects($this->once())
			->method('getBlogVersion')
			->with(null)
			->willReturn($version);

		$sut->expects($this->once())
			->method('migratePreviousVersion')
			->with($version, null)
			->willReturn(false);


		$result = $this->invokeMethod($sut, 'updateSite');
		$this->assertFalse($result);
	}

	/**
	 * @test
	 */
	public function migratePreviousVersion_returnsFalse_ifVersionIsUpToDate()
	{
		$version = '2.0';

		$sut = $this->sut();

		$this->internalNative->expects($this->once())
			->method('compare')
			->with($version, NEXT_AD_INT_PLUGIN_VERSION, '<')
			->willReturn(false);

		$result = $this->invokeMethod($sut, 'migratePreviousVersion', array($version));
		$this->assertFalse($result);
	}

	/**
	 * @test
	 */
	public function migratePreviousVersion_returnsTrue_ifVersionNeedToBeUpdated()
	{
		$version = '1.1.5';

		$sut = $this->sut(array('importOptions'));

		$this->internalNative->expects($this->once())
			->method('compare')
			->with($version, NEXT_AD_INT_PLUGIN_VERSION, '<')
			->willReturn(true);

		$sut->expects($this->once())
			->method('importOptions')
			->with(null, $version);

		$result = $this->invokeMethod($sut, 'migratePreviousVersion', array($version));
		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function getPreviousNetworkVersion_itDelegatesTo_get_site_option()
	{
		$sut = $this->sut();

		WP_Mock::wpFunction('get_site_option', array(
			'args' => array(
				NextADInt_Adi_Configuration_ImportService::OLD_VERSION_KEY,
				false
			),
			'return' => 666,
		));

		$this->assertEquals(666, $sut->getPreviousNetworkVersion());
	}

	/**
	 * @test
	 */
	public function getPreviousSiteVersion_itDelegatesTo_get_blog_option()
	{
		$sut = $this->sut();

		WP_Mock::wpFunction('is_multisite', array(
			'return' => true));

		WP_Mock::wpFunction('get_blog_option', array(
			'args' => array(
				555,
				NextADInt_Adi_Configuration_ImportService::OLD_VERSION_KEY,
				false
			),
			'return' => 666,
		));

		$this->assertEquals(666, $sut->getPreviousSiteVersion(555));
	}

	/**
	 * @test
	 */
	public function getPreviousBlogVersion_itDelegatesTo_get_option()
	{
		$sut = $this->sut();

		WP_Mock::wpFunction('get_option', array(
			'args' => array(
				NextADInt_Adi_Configuration_ImportService::OLD_VERSION_KEY,
				false
			),
			'return' => 666,
		));

		$this->assertEquals(666, $sut->getPreviousBlogVersion());
	}

	/**
	 * @test
	 */
	public function getBlogVersion_returnsGetSiteOptionValue_ifSiteOptionIsFound()
	{
		$siteId = 2;
		$expected = '1.1.5';

		$sut = $this->sut();

		WP_Mock::wpFunction('is_multisite', array(
			'times' => 1,
			'return' => true,
		));

		WP_Mock::wpFunction('get_site_option', array(
			'args'   => array(
				NextADInt_Adi_Configuration_ImportService::OLD_VERSION_KEY, false,
			),
			'return' => $expected,
		));

		$actual = $this->invokeMethod($sut, 'getBlogVersion', array($siteId));
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getBlogVersion_itDelegatesTo_getPreviousSiteVersion_whenInMultisiteEnvironment()
	{
		$siteId = 2;
		$expected = '1.1.5';

		$sut = $this->sut(array('getPreviousNetworkVersion', 'getPreviousSiteVersion'));

		WP_Mock::wpFunction('is_multisite', array(
			'times' => 1,
			'return' => true,
		));

		$sut->expects($this->once())
			->method('getPreviousNetworkVersion')
			->willReturn(false);

		$sut->expects($this->once())
			->method('getPreviousSiteVersion')
			->with($siteId)
			->willReturn($expected);

		$actual = $sut->getBlogVersion($siteId);
		$this->assertEquals($expected, $actual);
	}

	/**
 * @test
 */
	public function getBlogVersion_itDelegatesTo_getPreviousBlogVersion_whenIsNotMultisite()
	{
		$siteId = 2;
		$expected = '1.1.5';

		$sut = $this->sut(array('getPreviousBlogVersion'));

		WP_Mock::wpFunction('is_multisite', array(
			'times' => 1,
			'return' => false,
		));

		$sut->expects($this->once())
			->method('getPreviousBlogVersion')
			->willReturn($expected);

		$actual = $sut->getBlogVersion($siteId);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getBlogVersion_itDelegatesTo_getPreviousBlogVersion_whenSiteIdIsNull()
	{
		$siteId = null;
		$expected = '1.1.5';

		$sut = $this->sut(array('getPreviousBlogVersion'));

		$sut->expects($this->once())
			->method('getPreviousBlogVersion')
			->willReturn($expected);

		$actual = $sut->getBlogVersion($siteId);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function isPreviousVersion_itReturnsTrueFor_1_1_7()
	{
		$this->internalNative->expects($this->once())
			->method('compare')
			->with('1.1.7', NEXT_AD_INT_PLUGIN_VERSION, '<')
			->willReturn(true);

		$this->assertTrue(NextADInt_Adi_Configuration_ImportService::isPreviousVersion('1.1.7'));
	}

	/**
	 * @test
	 */
	public function isPreviousVersion_itReturnsFalseFor_2_x()
	{
		$this->internalNative->expects($this->once())
			->method('compare')
			->with('2.1', NEXT_AD_INT_PLUGIN_VERSION, '<')
			->willReturn(false);

		$this->assertFalse(NextADInt_Adi_Configuration_ImportService::isPreviousVersion('2.1'));
	}

	/**
	 * @test
	 */
	public function importOptions_triggersCorrectMethodsWithCorrectParameters()
	{
		$sut = $this->sut(array('getPreviousConfiguration', 'persistConvertedAttributeMapping'));

		$sut->expects($this->once())
			->method('getPreviousConfiguration')
			->with(1, 'previous_version')
			->willReturn(array(array('option_new' => 'option_new', 'value' => 'value')));

		$this->blogConfigurationRepository->expects($this->at(0))
			->method('persistSanitizedValue')
			->with(1, 'option_new', 'value');

		$sut->expects($this->once())
			->method('persistConvertedAttributeMapping')
			->with(1, 'previous_version');

		$this->invokeMethod($sut, 'importOptions', array(1, 'previous_version'));
	}

	/**
	 * @test
	 */
	public function getPreviousConfiguration_itMergesOldAndNewOptions()
	{
		$sut = $this->sut(array('getMergedOptions', 'getOption'));

		$sut->expects($this->once())
			->method('getMergedOptions')
			->willReturn(array('option'));

		$sut->expects($this->once())
			->method('getOption')
			->with(1, 'option', false)
			->willReturn('value');

		$actual = $sut->getPreviousConfiguration(1, false);

		$this->assertEquals(array(array('option_old' => 'option', 'option_new' => 'option', 'value' => 'value')), $actual);
	}

	/**
	 * @test
	 */
	public function getOption_returnsResultFromGetOption_ifMultisiteIsFalse()
	{
		$blogId = 1;
		$optionsName = 'option';
		$previousVersion = '1.1.5';
		$expected = 'optionValue';

		$sut = $this->sut();

		WP_Mock::wpFunction('is_multisite', array(
			'return' => false,
			'times'  => 1,
		));

		WP_Mock::wpFunction('get_option', array(
			'args'   => array(
				'AD_Integration_option',
			),
			'times'  => 1,
			'return' => $expected,
		));

		$actual = $this->invokeMethod($sut, 'getOption', array($blogId, $optionsName, $previousVersion));
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getOption_returnsResultFromGetOption_ifMultisiteIsTrueButBlogIdNull()
	{
		$blogId = null;
		$optionsName = 'option';
		$previousVersion = '1.1.5';
		$expected = 'optionValue';

		$sut = $this->sut();

		WP_Mock::wpFunction('is_multisite', array(
			'return' => true,
			'times'  => 1,
		));

		WP_Mock::wpFunction('get_option', array(
			'args'   => array(
				'AD_Integration_option',
			),
			'times'  => 1,
			'return' => $expected,
		));

		$actual = $this->invokeMethod($sut, 'getOption', array($blogId, $optionsName, $previousVersion));
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getOption_returnsResultFromGetBlogOption_ifVersionIsOlderThanOrEqualToOnePointFive()
	{
		$blogId = 1;
		$optionsName = 'option';
		$previousVersion = '1.1.5';
		$expected = 'optionValue';

		$sut = $this->sut();

		WP_Mock::wpFunction('is_multisite', array(
			'return' => true,
			'times'  => 1,
		));

		WP_Mock::wpFunction('get_blog_option', array(
			'args'   => array(
				$blogId, 'AD_Integration_option',
			),
			'times'  => 1,
			'return' => $expected,
		));

		$this->internalNative->expects($this->once())
			->method('compare')
			->with('1.1.5', '1.1.5', '<=')
			->willReturn(true);

		$actual = $this->invokeMethod($sut, 'getOption', array($blogId, $optionsName, $previousVersion));
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getOption_returnsResultFromGetSiteOption_ifVersionIsNewerThanOnePointFive()
	{
		$blogId = 1;
		$optionsName = 'option';
		$previousVersion = '1.1.6';
		$expected = 'optionValue';

		$sut = $this->sut();

		WP_Mock::wpFunction('is_multisite', array(
			'return' => true,
			'times'  => 1,
		));

		WP_Mock::wpFunction('get_site_option', array(
			'args'   => array(
				'AD_Integration_option',
			),
			'times'  => 1,
			'return' => $expected,
		));

		$this->internalNative->expects($this->once())
			->method('compare')
			->with('1.1.6', '1.1.5', '<=')
			->willReturn(false);

		$actual = $this->invokeMethod($sut, 'getOption', array($blogId, $optionsName, $previousVersion));
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function convertOptionName_itConvertsAllOldOptionNames()
	{
		$this->assertEquals('name_pattern', NextADInt_Adi_Configuration_ImportService::convertOptionName('display_name'));
		$this->assertEquals(NextADInt_Adi_Configuration_Options::SYNC_TO_AD_ENABLED, NextADInt_Adi_Configuration_ImportService::convertOptionName('syncback'));
		$this->assertEquals(NextADInt_Adi_Configuration_Options::SYNC_TO_AD_USE_GLOBAL_USER, NextADInt_Adi_Configuration_ImportService::convertOptionName('syncback_use_global_user'));
		$this->assertEquals(NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_USER, NextADInt_Adi_Configuration_ImportService::convertOptionName('syncback_global_user'));
		$this->assertEquals(NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD, NextADInt_Adi_Configuration_ImportService::convertOptionName('syncback_global_pwd'));
		$this->assertEquals(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_ENABLED, NextADInt_Adi_Configuration_ImportService::convertOptionName('bulkimport_enabled'));
		$this->assertEquals(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_AUTHCODE, NextADInt_Adi_Configuration_ImportService::convertOptionName('bulkimport_authcode'));
		$this->assertEquals(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_SECURITY_GROUPS, NextADInt_Adi_Configuration_ImportService::convertOptionName('bulkimport_security_groups'));
		$this->assertEquals(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_USER, NextADInt_Adi_Configuration_ImportService::convertOptionName('bulkimport_user'));
		$this->assertEquals(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_PASSWORD, NextADInt_Adi_Configuration_ImportService::convertOptionName('bulkimport_pwd'));
	}

	/**
	 * @test
	 */
	public function convertOptionName_itReturnsTheSameName_whenNotExistent()
	{
		$this->assertEquals('new_option_name', NextADInt_Adi_Configuration_ImportService::convertOptionName('new_option_name'));
	}

	/**
	 * @test
	 */
	public function persistConvertedAttributeMapping_convertsArrayToString() {
		$sut = $this->sut(array('getOption', 'convertAttributeMapping'));
		$siteId = 6;
		$previousVersion = 'version';

		$sut->expects($this->at(0))
			->method('getOption')
			->with($siteId, 'usermeta_empty_overwrite', $previousVersion)
			->willReturn('1');

		$sut->expects($this->at(1))
			->method('getOption')
			->with($siteId, 'attributes_to_show', $previousVersion)
			->willReturn('attribute_to_show');

		$sut->expects($this->at(2))
			->method('getOption')
			->with($siteId, 'additional_user_attributes', $previousVersion)
			->willReturn('additional_user_attributes');

		$sut->expects($this->once())
			->method('convertAttributeMapping')
			->with('additional_user_attributes', 'attribute_to_show', '1')
			->willReturn(array(
				'ad_attribute' => array(
					'type' => 'type',
					'wordpress_attribute' => 'wa',
					'description' => 'd',
					'view_in_userprofile' => false,
					'sync_to_ad' => true,
					'overwrite' => true
				)
			));


		$this->blogConfigurationRepository->expects($this->once())
			->method('persistSanitizedValue')
			->with($siteId,
				'additional_user_attributes',
				'ad_attribute:type:wa:d:0:1:1;'
				);

		$sut->persistConvertedAttributeMapping($siteId, $previousVersion);
	}

	/**
	 * @test
	 */
	public function convertAttributeMapping_mapsValues()
	{
		$sut = $this->sut(array('getOption'));

		$customAttributes = "lastlogon:timestamp:last_logon_time
whencreated:time:user_created_on
homephone:string
otherhomephone:list
manager:cn";

		$attributesToShow = "lastlogon
whencreated:User Created on
<h4>A headline</h4>
homephone:Phone (home):*
otherhomephone::*
manager:Manager";

		$actual = $sut->convertAttributeMapping($customAttributes, $attributesToShow, true);
		$this->assertEquals(5, sizeof($actual));
		$this->assertEquals('timestamp', $actual['lastlogon']['type']);
		$this->assertEquals('last_logon_time', $actual['lastlogon']['wordpress_attribute']);
		$this->assertEquals(true, $actual['lastlogon']['overwrite']);
		$this->assertEquals(true, $actual['lastlogon']['view_in_userprofile']);
		$this->assertEquals(false, $actual['lastlogon']['sync_to_ad']);
		$this->assertEquals('', $actual['lastlogon']['description']);
		// default mapping
		$this->assertEquals('homephone', $actual['homephone']['wordpress_attribute']);
		// sync to ad
		$this->assertEquals(true, $actual['homephone']['sync_to_ad']);
		// description
		$this->assertEquals('Manager', $actual['manager']['description']);
	}
}
