<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Multisite_Ui_ProfileConfigurationControllerTest extends Ut_BasicTest
{
	/** @var NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository| PHPUnit_Framework_MockObject_MockObject */
	private $profileConfigurationRepository;

	/** @var  NextADInt_Multisite_Option_Provider */
	private $optionProvider;

	public function setUp() : void
	{
		parent::setUp();

		$this->optionProvider = new NextADInt_Adi_Configuration_Options();
		$this->profileConfigurationRepository = $this->createMock('NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository');
	}

	public function tearDown() : void
	{
		parent::tearDown();
	}

	/**
	 * @param $methods
	 *
	 * @return NextADInt_Multisite_Ui_ProfileConfigurationController|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Multisite_Ui_ProfileConfigurationController')
			->setConstructorArgs(array(
				$this->profileConfigurationRepository,
				$this->optionProvider
			))
			->setMethods($methods)
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
			->willThrowException(new Exception('test'));

		$expected = array(
			'message'   => 'An error occurred while saving the configuration.',
			'type'      => 'error',
			'isMessage' => true,
			'additionalInformation' => array(),
		);

		$result = $sut->saveProfileOptions(array(), 1);

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
			'message'   => 'The configuration was saved successfully.',
			'type'      => 'success',
			'isMessage' => true,
			'additionalInformation' => array(),
		);

		$result = $sut->saveProfileOptions(array(), 1);

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
				'option_value'      => '389',
				'option_permission' => '1'
			)
		);

		$sut->expects($this->once())
			->method('saveProfileOptionsInternal')
			->with($options, 1);

		$expected = array(
			'message'   => 'The configuration was saved successfully.',
			'type'      => 'success',
			'isMessage' => true,
			'additionalInformation' => array(),
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
				'option_value'      => '389',
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
		$sut = $this->sut(null);

		$option = array(
			'option_value'      => '389',
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
		$sut = $this->sut(null);

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
		$sut = $this->sut(null);

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
        $sut = $this->sut(null);

        $option = array(
            'option_value' => '389',
            'option_permission' => '1'
        );

        $actual = $sut->validateOption('port', $option);
        $this->assertEquals(true, $actual);
    }
}