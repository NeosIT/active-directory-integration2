<?php

namespace Dreitier\Nadi\Multisite\Site\Ui;

use Dreitier\Test\BasicTest;
use Dreitier\WordPress\Multisite\Configuration\Persistence\BlogConfigurationRepository;
use Dreitier\WordPress\Multisite\Configuration\Persistence\ProfileRepository;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access private
 */
class ExtendSiteListTest extends BasicTest
{
	/* @var BlogConfigurationRepository | MockObject */
	private $blogConfigurationRepository;

	/* @var ProfileRepository | MockObject */
	private $profileRepository;

	public function setUp(): void
	{
		parent::setUp();

		$this->blogConfigurationRepository = $this->createMock(BlogConfigurationRepository::class);
		$this->profileRepository = $this->createMock(ProfileRepository::class);
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 *
	 * @return ExtendSiteList | MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder(ExtendSiteList::class)
			->setConstructorArgs(
				array(
					$this->blogConfigurationRepository,
					$this->profileRepository,
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function register_itAddsFilter()
	{
		$sut = $this->sut(null);

		\WP_Mock::expectFilterAdded('wpmu_blogs_columns', array($sut, 'addColumns'), 10, 1);
		\WP_Mock::expectActionAdded('manage_sites_custom_column', array($sut, 'addContent'), 1, 2);

		$sut->register();
	}

	/**
	 * @test
	 */
	public function addColumn_itAddsTheAdiProfileColumn()
	{
		$sut = $this->sut(null);
		$this->mockFunction__();

		$actual = $sut->addColumns(array());

		$this->assertTrue(isset($actual[ExtendSiteList::ADI_PROFILE_COLUMN]));
	}

	/**
	 * @test
	 * @outputBuffering disabled
	 */
	public function addContent_outputsProfileName()
	{
		$sut = $this->sut(null);

		$this->blogConfigurationRepository->expects($this->once())
			->method('isDefaultProfileUsed')
			->with(666)
			->willReturn(false);

		$this->blogConfigurationRepository->expects($this->once())
			->method('findProfileId')
			->with(666)
			->willReturn(444);

		$this->profileRepository->expects($this->once())
			->method('findName')
			->with(444)
			->willReturn('name');

		$this->expectOutputString('name');

		$sut->addContent(ExtendSiteList::ADI_PROFILE_COLUMN, 666);
	}

	/**
	 * @test
	 * @outputBuffering disabled
	 */
	public function addContent_withDefaultProfileUsage_outputsDefaultProfileMessage()
	{
		$sut = $this->sut(null);
		$this->mockFunction__();

		$this->blogConfigurationRepository->expects($this->once())
			->method('isDefaultProfileUsed')
			->with(666)
			->willReturn(true);

		$this->blogConfigurationRepository->expects($this->once())
			->method('findProfileId')
			->with(666)
			->willReturn(444);

		$this->profileRepository->expects($this->once())
			->method('findName')
			->with(444)
			->willReturn('name');

		$this->expectOutputString('name (default profile)');

		$sut->addContent(ExtendSiteList::ADI_PROFILE_COLUMN, 666);
	}

	/**
	 * @test
	 * @outputBuffering disabled
	 */
	public function addContent_withNoProfile_outputsNone()
	{
		$sut = $this->sut(null);
		$this->mockFunction__();

		$this->blogConfigurationRepository->expects($this->once())
			->method('isDefaultProfileUsed')
			->with(666)
			->willReturn(false);

		$this->blogConfigurationRepository->expects($this->once())
			->method('findProfileId')
			->with(666)
			->willReturn(false);

		$this->profileRepository->expects($this->never())
			->method('findName')
			->with(444)
			->willReturn('name');

		$this->expectOutputString('<em>None assigned</em>');

		$sut->addContent(ExtendSiteList::ADI_PROFILE_COLUMN, 666);
	}
}