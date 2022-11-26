<?php

namespace Dreitier\WordPress\Multisite\Ui;

use Dreitier\Test\BasicTest;
use Dreitier\WordPress\Multisite\Configuration\Persistence\BlogConfigurationRepository;
use Dreitier\WordPress\Multisite\Option\Provider;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class BlogConfigurationControllerTest extends BasicTest
{
	/**  @var BlogConfigurationRepository| MockObject */
	private $blogConfigurationRepository;

	/**  @var Provider|MockObject */
	private $optionProvider;

	public function setUp(): void
	{
		parent::setUp();

		$this->blogConfigurationRepository = $this->getMockBuilder(BlogConfigurationRepository::class)
			->disableOriginalConstructor()
			->setMethods(array('persistSanitizedValue'))
			->getMock();

		$this->optionProvider = $this->createMock(Provider::class);
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @param $methods
	 *
	 * @return BlogConfigurationController|MockObject
	 */
	public function sut($methods)
	{
		return $this->getMockBuilder(BlogConfigurationController::class)
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
		$this->mockFunction__();

		$data = array();
		$expected = array("status_success" => true);

		$sut->expects($this->once())
			->method('saveBlogOptionsInternal')
			->with($data);

		$actual = $sut->saveBlogOptions($data);

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function saveBlogOptions_withErrors_returnsSuccessMessage()
	{
		$sut = $this->sut(array('saveBlogOptionsInternal'));
		$this->mockFunction__();

		$data = array();
		$expected = array("status_success" => false);

		$sut->expects($this->once())
			->method('saveBlogOptionsInternal')
			->willThrowException(new \Exception(''));

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
			'port' => array(
				'option_value' => 'stuff',
			),
			'tls' => array(
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
			'port' => array(
				'option_value' => 'stuff',
			),
			'tls' => array(
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