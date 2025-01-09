<?php

namespace Dreitier\WordPress\Multisite\Ui;

use Dreitier\Nadi\Configuration\Options;
use Dreitier\Test\BasicTestCase;
use Dreitier\WordPress\Multisite\Configuration\Persistence\ProfileConfigurationRepository;
use Dreitier\WordPress\Multisite\Option\Provider;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class ProfileConfigurationControllerTest extends BasicTestCase
{
	/** @var ProfileConfigurationRepository|MockObject */
	private $profileConfigurationRepository;

	/** @var  Provider */
	private $optionProvider;

	public function setUp(): void
	{
		parent::setUp();

		$this->optionProvider = new Options();
		$this->profileConfigurationRepository = $this->createMock(ProfileConfigurationRepository::class);
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @param $methods
	 *
	 * @return ProfileConfigurationController|MockObject
	 */
	public function sut(array $methods = [])
	{
		return $this->getMockBuilder(ProfileConfigurationController::class)
			->setConstructorArgs(array(
				$this->profileConfigurationRepository,
				$this->optionProvider
			))
			->onlyMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function saveProfileOptions_withErrorOnSave_returnsErrorMessage()
	{
		$sut = $this->sut(array('saveProfileOptionsInternal'));
		$this->mockFunction__();

		$sut->expects($this->once())
			->method('saveProfileOptionsInternal')
			->willThrowException(new \Exception('test'));

		$expected = array(
			'message' => 'An error occurred while saving the configuration.',
			'type' => 'error',
			'isMessage' => true,
			'additionalInformation' => [],
		);

		$result = $sut->saveProfileOptions([], 1);

		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 */
	public function saveProfileOptions_withSuccessOnSave_returnsSuccessMessage()
	{
		$sut = $this->sut(array('saveProfileOptionsInternal'));
		$this->mockFunction__();

		$sut->expects($this->once())
			->method('saveProfileOptionsInternal');

		$expected = array(
			'message' => 'The configuration was saved successfully.',
			'type' => 'success',
			'isMessage' => true,
			'additionalInformation' => [],
		);

		$result = $sut->saveProfileOptions([], 1);

		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 */
	public function saveProfileOptions_triggersSaveProfileOptionsInternalMethod()
	{
		$sut = $this->sut(array('saveProfileOptionsInternal'));
		$this->mockFunction__();

		$options = array(
			'port' => array(
				'option_value' => '389',
				'option_permission' => '1'
			)
		);

		$sut->expects($this->once())
			->method('saveProfileOptionsInternal')
			->with($options, 1);

		$expected = array(
			'message' => 'The configuration was saved successfully.',
			'type' => 'success',
			'isMessage' => true,
			'additionalInformation' => [],
		);

		$result = $sut->saveProfileOptions($options, 1);

		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 */
	public function saveProfileOptionsInternal_executeSaveOptions()
	{
		$sut = $this->sut(array('persistOption', 'validateOption'));

		$options = array(
			'port' => array(
				'option_value' => '389',
				'option_permission' => '1'
			)
		);

		$sut->expects($this->once())
			->method('validateOption')
			->with('port', $options['port'])
			->willReturn(true);

		$sut->expects($this->once())
			->method('persistOption')
			->with('port', $options['port'], 5)
			->willReturn(true);

		$this->invokeMethod($sut, 'saveProfileOptionsInternal', array($options, 5));
	}

	/**
	 * @test
	 */
	public function validateOption_metadataEmpty_returnFalse()
	{
		$sut = $this->sut();

		$option = array(
			'option_value' => '389',
			'option_permission' => '1'
		);

		$actual = $sut->validateOption('some_stuff', $option);
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function validateOption_optionPermissionNotSet()
	{
		$sut = $this->sut();

		$option = array(
			'option_value' => '389',
		);

		$actual = $sut->validateOption('port', $option);
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function validateOption_optionValueNotSet()
	{
		$sut = $this->sut();

		$option = array(
			'option_permission' => '1'
		);

		$actual = $sut->validateOption('port', $option);
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function validateOption_metadataNotEmpty_returnTrue()
	{
		$sut = $this->sut();

		$option = array(
			'option_value' => '389',
			'option_permission' => '1'
		);

		$actual = $sut->validateOption('port', $option);
		$this->assertEquals(true, $actual);
	}
}