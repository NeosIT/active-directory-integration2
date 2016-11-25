<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Multisite_Ui_BlogConfigurationControllerTest extends Ut_BasicTest
{
	/**  @var NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository| PHPUnit_Framework_MockObject_MockObject */
	private $blogConfigurationRepository;

	/**  @var NextADInt_Multisite_Option_Provider| PHPUnit_Framework_MockObject_MockObject */
	private $optionProvider;

	public function setUp()
	{
		parent::setUp();

		$this->blogConfigurationRepository = $this->getMockBuilder('NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository')
			->disableOriginalConstructor()
			->setMethods(array('persistSanitizedValue'))
			->getMock();

		$this->optionProvider = $this->createMock('NextADInt_Multisite_Option_Provider');
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @param $methods
	 *
	 * @return NextADInt_Multisite_Ui_BlogConfigurationController|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods)
	{
		return $this->getMockBuilder('NextADInt_Multisite_Ui_BlogConfigurationController')
			->setConstructorArgs(array(
				$this->blogConfigurationRepository,
				$this->optionProvider))
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function saveBlogOptions_withoutErrors_returnsSuccessMessage()
	{
		$sut = $this->sut(array('saveBlogOptionsInternal'));

		$data = array();
		$expected = array(
			'isMessage'             => true,
			'type'                  => 'success',
			'message'               => 'The configuration has been saved successfully.',
			'additionalInformation' => array(),
		);

		$sut->expects($this->once())
			->method('saveBlogOptionsInternal')
			->with($data);

        WP_Mock::wpFunction('__', array(
            'args'       => array(WP_Mock\Functions::type('string'), 'next-active-directory-integration'),
            'times'      => '0+',
            'return_arg' => 0
        ));

		$actual = $sut->saveBlogOptions($data);

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function saveBlogOptions_withErrors_returnsSuccessMessage()
	{
		$sut = $this->sut(array('saveBlogOptionsInternal'));

		$data = array();
		$expected = array(
			'isMessage'             => true,
			'type'                  => 'error',
			'message'               => 'An error occurred while saving the configuration.',
			'additionalInformation' => array(),
		);

		$sut->expects($this->once())
			->method('saveBlogOptionsInternal')
			->willThrowException(new Exception(''));

        WP_Mock::wpFunction('__', array(
            'args'       => array(WP_Mock\Functions::type('string'), 'next-active-directory-integration'),
            'times'      => '0+',
            'return_arg' => 0
        ));

		$actual = $sut->saveBlogOptions($data);

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function saveBlogOptions_delegatesCallTosaveBlogOptionsInternal()
	{
		$sut = $this->sut(array('saveBlogOptionsInternal'));

		$options = array(
			'port'    => array(
				'option_value' => 'stuff',
			),
			'tls'     => array(
				'option_value' => 'true',
			),
			'base_dn' => array(
				'option_value' => '127.0.0.1',
			),
		);

		$sut->expects($this->once())
			->method('saveBlogOptionsInternal')
			->with($options);

		$sut->saveBlogOptions($options);
	}

	/**
	 * @test
	 */
	public function saveBlogOptionsInternal_delegateCorrectly()
	{
		$sut = $this->sut(array('validateOption', 'persistOption'));

		$options = array(
			'port'    => array(
				'option_value' => 'stuff',
			),
			'tls'     => array(
				'option_value' => 'true',
			),
			'base_dn' => array(
				'option_value' => '127.0.0.1',
			),
		);

		$sut->expects($this->exactly(3))
			->method('validateOption')
			->withConsecutive(
				array('port', array('option_value' => 'stuff')),
				array('tls', array('option_value' => 'true')),
				array('base_dn', array('option_value' => '127.0.0.1'))
			)->will($this->onConsecutiveCalls(
				true,
				false,
				true
			));

		$sut->expects($this->exactly(2))
			->method('persistOption')
			->withConsecutive(
				array('port', array('option_value' => 'stuff')),
				array('base_dn', array('option_value' => '127.0.0.1'))
			);

		$this->invokeMethod($sut, 'saveBlogOptionsInternal', array($options));
	}

	/**
	 * @test
	 */
	public function validateOption_optionIsNull_returnFalse()
	{
		$sut = $this->sut(null);

		$actual = $sut->validateOption("port", null);
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function validateOption_noMetadata_returnFalse()
	{
		$sut = $this->sut(null);

		$this->optionProvider->expects($this->never())
			->method('existOption');

		$actual = $sut->validateOption("some_invalid_stuff", null);
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */

	public function persistOption_withMetadata_persist()
	{
		$sut = $this->sut(null);

		\WP_Mock::wpFunction('get_current_blog_id', array(
				'return' => 1)
		);

		$this->blogConfigurationRepository->expects($this->once())
			->method('persistSanitizedValue')
			->with('1', 'port', '389');

		$sut->persistOption("port", '389');
	}
}