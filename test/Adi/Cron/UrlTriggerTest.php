<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_Cron_UrlTriggerTest extends Ut_BasicTest
{
	/* @var Multisite_Configuration_Service|PHPUnit_Framework_MockObject_MockObject $configuration */
	private $configuration;

	/* @var Adi_Synchronization_ActiveDirectory|PHPUnit_Framework_MockObject_MockObject $syncToActiveDirectory */
	private $syncToActiveDirectory;

	/* @var Adi_Synchronization_WordPress|PHPUnit_Framework_MockObject_MockObject $syncToWordPress */
	private $syncToWordPress;

	public function setUp()
	{
		parent::setUp();
		$this->configuration = $this->createMock('Multisite_Configuration_Service');
		$this->syncToActiveDirectory = $this->createMock('Adi_Synchronization_ActiveDirectory');
		$this->syncToWordPress = $this->createMock('Adi_Synchronization_WordPress');
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @param $methods
	 *
	 * @return Adi_Cron_UrlTrigger|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods)
	{
		$class = 'Adi_Cron_UrlTrigger';
		$constructor = array(
			$this->configuration,
			$this->syncToActiveDirectory,
			$this->syncToWordPress
		);

		return parent::createMockedObject($class, $constructor, $methods);
	}

	/**
	 * @test
	 */
	public function register_registerMethod_checkMethodIsRegistered()
	{
		$sut = $this->sut(null);

		\WP_Mock::expectActionAdded('init', array($sut, 'httpRequestEntryPoint'));

		$sut->register();
	}

	/**
	 * @test
	 */
	public function httpRequestEntryPoint_storeValuesInPost_delegateWithPostValues()
	{
		$sut = $this->sut(array('processHttpRequest'));

		$_POST = array('key' => 'value');

		$sut->expects($this->once())
			->method('processHttpRequest')
			->with($_POST);

		$sut->httpRequestEntryPoint();
	}

	/**
	 * @test
	 */
	public function processHttpRequest_withWrongBulkMode_skipMethod()
	{
		$sut = $this->sut(array('getSyncMode', 'validateAuthCode'));

		$post = array('key' => 'value');

		$sut->expects($this->never())
			->method('validateAuthCode');

		$sut->processHttpRequest($post);
	}

	/**
	 * @test
	 */
	public function processHttpRequest_withWrongAuthCode_skipMethod()
	{
		$sut = $this->sut(array('getSyncMode', 'validateAuthCode', 'dispatchAction'));

		$post = array('authCode' => 'stuff');

		$sut->expects($this->never())
			->method('validateAuthCode')
			->with($post['authCode'])
			->willReturn(false);

		$sut->expects($this->never())
			->method('dispatchAction');

		$sut->processHttpRequest($post);
	}

	/**
	 * @test
	 */
	public function processHttpRequest_withCorrectAuthCode_dispatchMethod()
	{
		$sut = $this->sut(array('getSyncMode', 'validateAuthCode', 'dispatchAction'));

		$post = array('authCode' => 'stuff', 'userid' => 666);

		$sut->expects($this->never())
			->method('validateAuthCode')
			->with($post['authCode'])
			->willReturn(1);

		$sut->expects($this->never())
			->method('dispatchAction')
			->with(666, 1);

		$sut->processHttpRequest($post);
	}

	/**
	 * @test
	 */
	public function getSyncMode_wrongTask_returnFalse()
	{
		$sut = $this->sut(null);

		$post = array('adi-taska' => '');

		$actual = $sut->getSyncMode($post);
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function getSyncMode_syncToWordpressTask_return1()
	{
		$sut = $this->sut(null);

		$post = array('adi2-task' => 'sync-to-wordpress');

		$actual = $sut->getSyncMode($post);
		$this->assertEquals(1, $actual);
	}

	/**
	 * @test
	 */
	public function getSyncMode_syncToAd_return2()
	{
		$sut = $this->sut(null);

		$post = array('adi2-task' => 'sync-to-ad');

		$actual = $sut->getSyncMode($post);
		$this->assertEquals(2, $actual);
	}

	/**
	 * @test
	 */
	public function validateAuthCode_withWrongAuthCode_returnFalse()
	{
		$sut = $this->sut(array('output'));

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::SYNC_TO_WORDPRESS_AUTHCODE)
			->willReturn('einAnderesPassword');

		$actual = $sut->validateAuthCode('ad84fd2', 1);
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function validateAuthCode_withCorrectAuthCode_returnTrue()
	{
		$sut = $this->sut(array('output'));

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::SYNC_TO_AD_AUTHCODE)
			->willReturn('ad84fd2');

		$actual = $sut->validateAuthCode('ad84fd2', 2);
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function dispatchAction_withBulkMode1_dispatchTosyncToWordpress()
	{
		$sut = $this->sut(null);

		$this->syncToWordPress->expects($this->once())
			->method('synchronize')
			->with();

		$this->syncToActiveDirectory->expects($this->never())
			->method('synchronize');

		$sut->dispatchAction(22222, 1);
	}

	/**
	 * @test
	 */
	public function dispatchAction_withBulkMode2_dispatchToSyncBack()
	{
		$sut = $this->sut(null);

		$this->syncToWordPress->expects($this->never())
			->method('synchronize');

		$this->syncToActiveDirectory->expects($this->once())
			->method('synchronize')
			->with(22222);

		$sut->dispatchAction(22222, 2);
	}
}