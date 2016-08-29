<?php

/**
 * @author Christopher Klein <ckl@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_Configuration_Import_Ui_ExtendPluginListTest extends Ut_BasicTest
{
	/**
	 * @var NextADInt_Adi_Configuration_ImportService
	 */
	private $importService;

	public function setUp()
	{
		parent::setUp();

		$this->importService = $this->createMock('NextADInt_Adi_Configuration_ImportService');
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 *
	 * @return NextADInt_Adi_Configuration_Import_Ui_ExtendPluginList| PHPUnit_Framework_MockObject_MockObject
	 */
	private function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Adi_Configuration_Import_Ui_ExtendPluginList')
			->setConstructorArgs(
				array(
					$this->importService,
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function register_itRegistersTheHooks()
	{
		$sut = $this->sut(null);

		\WP_Mock::expectFilterAdded('plugin_action_links_' . ADI_PLUGIN_FILE, array($sut, 'extendPluginActions'), 10, 5);
		\WP_Mock::expectFilterAdded('network_admin_plugin_action_links_' . ADI_PLUGIN_FILE, array($sut, 'extendPluginActions'), 10, 5);
		\WP_Mock::expectActionAdded('admin_post_' . NextADInt_Adi_Configuration_Import_Ui_ExtendPluginList::ACTION, array($sut, 'exportPreviousConfiguration'));

		$sut->register();
	}

	/**
	 * @test
	 */
	public function extendPluginAction_itAddsTheLink_whenAuthorized()
	{
		$sut = $this->sut(array('isNetworkExportAllowed'));

		WP_Mock::wpFunction('admin_url', array(
			'times' => 1,
			'return' => 'link',
		));

		WP_Mock::wpFunction('is_network_admin', array(
			'times' => 1,
			'return' => true,
		));

		$sut->expects($this->once())
			->method('isNetworkExportAllowed')
			->willReturn(true);

		$actual = $sut->extendPluginActions(array(), 'name');
		$this->assertTrue(isset($actual['adi_v1_configuration_export']));
		$this->assertRegExp('/Download ADI v1/', $actual['adi_v1_configuration_export']);
	}

	/**
	 * @test
	 */
	public function isNetworkExportAllowed_itReturnsTrue_whenConditionsMet()
	{
		$sut = $this->sut();

		WP_Mock::wpFunction('is_multisite', array(
			'times' => 1,
			'return' => true,
		));

		WP_Mock::wpFunction('is_super_admin', array(
			'times' => 1,
			'return' => true,
		));

		$this->assertTrue($sut->isNetworkExportAllowed());
	}

	/**
	 * @test
	 */
	public function isSingleSiteExportAllowed_itReturnsTrue_whenConditionsMet()
	{
		$sut = $this->sut();

		WP_Mock::wpFunction('is_multisite', array(
			'times' => 1,
			'return' => false,
		));

		WP_Mock::wpFunction('is_admin', array(
			'times' => 1,
			'return' => true,
		));

		$this->assertTrue($sut->isSingleSiteExportAllowed());
	}

	/**
	 * @test
	 */
	public function exportPreviousConfiguration_itFails_whenAuthorizationFails()
	{
		$sut = $this->sut(array('isNetworkExportAllowed'));

		$sut->expects($this->once())
			->method('isNetworkExportAllowed')
			->willReturn(false);

		WP_Mock::wpFunction('wp_die', array(
			'times' => 1,
			'return' => true,
		));

		$sut->exportPreviousConfiguration();
	}

	/**
	 * @test
	 */
	public function exportPreviousConfiguration_exportsNetwork()
	{
		$sut = $this->sut(array('isNetworkExportAllowed', 'dumpNetwork', 'sendDump'));

		$sut->expects($this->exactly(2))
			->method('isNetworkExportAllowed')
			->willReturn(true);

		$dump = array('r' => 'k');

		$sut->expects($this->once())
			->method('dumpNetwork')
			->willReturn($dump);

		$sut->expects($this->once())
			->method('sendDump')
			->with($dump);

		$sut->exportPreviousConfiguration();
	}

	/**
	 * @test
	 */
	public function exportPreviousConfiguration_exportsSingleSite()
	{
		$sut = $this->sut(array('isNetworkExportAllowed', 'isSingleSiteExportAllowed', 'dumpSingleSite', 'sendDump'));

		$sut->expects($this->exactly(2))
			->method('isNetworkExportAllowed')
			->willReturn(false);

		$sut->expects($this->once())
			->method('isSingleSiteExportAllowed')
			->willReturn(true);

		$dump = array('r' => 'k');

		$sut->expects($this->once())
			->method('dumpSingleSite')
			->willReturn($dump);

		$sut->expects($this->once())
			->method('sendDump')
			->with($dump);

		$sut->exportPreviousConfiguration();
	}

	/**
	 * @test
	 */
	public function dumpConfiguration_itConvertsTheInput()
	{
		$sut = $this->sut();

		$actual = $sut->dumpConfiguration(
			array(
				array(
					'option_old' => '444',
					'option_new' => '555',
					'value' => 666
				)
			)
		);

		$this->assertRegExp('/444/', $actual[0]);
		$this->assertRegExp('/555/', $actual[0]);
		$this->assertRegExp('/666/', $actual[0]);
	}

	/**
	 * @test
	 */
	public function dumpSingleSite_itDumpsTheConfiguration()
	{
		$sut = $this->sut(array('dumpConfiguration'));
		$config = array('config');

		$this->importService->expects($this->once())
			->method('getPreviousBlogVersion')
			->willReturn('1.1.7');

		$this->importService->expects($this->once())
			->method('getPreviousConfiguration')
			->with(null, '1.1.7')
			->willReturn($config);

		$sut->expects($this->once())
			->method('dumpConfiguration')
			->with($config)
			->willReturn($config);

		$actual = $sut->dumpSingleSite();

		$this->assertRegExp('/Single site/', $actual[0]);
		// separator
		$this->assertRegExp('/Version: 1\.1\.7/', $actual[2]);
		// separator
		$this->assertEquals('config', $actual[4]);
	}

	/**
	 * @test
	 */
	public function dumpNetwork_itDumpsTheConfiguration_whenNetworkActivated()
	{
		$sut = $this->sut(array('dumpConfiguration'));
		$config = array('config');

		$this->importService->expects($this->once())
			->method('getPreviousNetworkVersion')
			->willReturn('1.1.7');

		$this->importService->expects($this->once())
			->method('getPreviousConfiguration')
			->with(0, '1.1.7')
			->willReturn($config);

		$sut->expects($this->once())
			->method('dumpConfiguration')
			->with($config)
			->willReturn($config);

		$actual = $sut->dumpNetwork();

		$this->assertRegExp('/WordPress Multisite environment/', $actual[0]);
		// separator
		$this->assertRegExp('/Version: 1\.1\.7 \(global/', $actual[2]);
		// separator
		$this->assertEquals('config', $actual[4]);
	}

	/**
	 * @test
	 */
	public function dumpNetwork_itDumpsTheConfiguration_whenBlogActivated()
	{
		$sut = $this->sut(array('dumpConfiguration'));
		$config = array('config');

		$this->importService->expects($this->once())
			->method('getPreviousNetworkVersion')
			->willReturn(false);

		WP_Mock::wpFunction('wp_get_sites', array(
			'times' => 1,
			'return' => array(array('blog_id' => 555, 'domain' => 'domain', 'path' => 'path')),
		));

		$this->importService->expects($this->once())
			->method('getPreviousSiteVersion')
			->with(555)
			->willReturn('1.1.5');

		$sut->expects($this->once())
			->method('dumpConfiguration')
			->with(555, '1.1.5')
			->willReturn($config);

		$actual = $sut->dumpNetwork();

		$this->assertRegExp('/WordPress Multisite environment/', $actual[0]);
		// separator
		$this->assertRegExp('/Blog ID 555/', $actual[2]);
		$this->assertRegExp('/domain \- path/', $actual[2]);
		// separator
		$this->assertEquals('config', $actual[4]);
	}
}