<?php

namespace Dreitier\WordPress\Multisite\Ui;

use Dreitier\Test\BasicTest;
use Dreitier\WordPress\Multisite\Configuration\Persistence\BlogConfigurationRepository;
use Dreitier\WordPress\Multisite\Configuration\Persistence\DefaultProfileRepository;
use Dreitier\WordPress\Multisite\Configuration\Persistence\ProfileRepository;
use Dreitier\WordPress\WordPressRepository;
use Mockery\Mock;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class BlogProfileRelationshipControllerTest extends BasicTest
{
	/** @var BlogConfigurationRepository|MockObject */
	private $blogConfigurationRepository;

	/** @var ProfileRepository|MockObject */
	private $profileRepository;

	/** @var  WordPressRepository | MockObject */
	private $wordPressRepository;

	/** @var DefaultProfileRepository|MockObject */
	private $defaultProfileRepository;

	public function setUp(): void
	{
		parent::setUp();

		$this->blogConfigurationRepository = $this->createMock(BlogConfigurationRepository::class);
		$this->profileRepository = $this->createMock(ProfileRepository::class);
		$this->wordPressRepository = $this->createMock(WordPressRepository::class);
		$this->defaultProfileRepository = $this->createMock(DefaultProfileRepository::class);
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @param $methods
	 *
	 * @return BlogProfileRelationshipController|MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder(BlogProfileRelationshipController::class)
			->setConstructorArgs(
				array(
					$this->blogConfigurationRepository,
					$this->profileRepository,
					$this->defaultProfileRepository,
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function saveDefaultProfile_withExistingProfile_delegatesCallToRepository()
	{
		$sut = $this->sut(array('validateProfile'));

		$this->profileRepository->expects($this->once())
			->method('findAllIds')
			->willReturn(array(1, 2));

		$sut->expects($this->once())
			->method('validateProfile')
			->with(1, array(1, 2))
			->willReturn(true);

		$this->defaultProfileRepository->expects($this->once())
			->method('saveProfileId')
			->with(1);

		$sut->saveDefaultProfile(1);
	}

	/**
	 * @test
	 */
	public function saveDefaultProfile_withoutExistingProfile_doesNotDelegateCallToRepository()
	{
		$sut = $this->sut(array('validateProfile'));

		$this->profileRepository->expects($this->once())
			->method('findAllIds')
			->willReturn(array(1, 2));

		$sut->expects($this->once())
			->method('validateProfile')
			->with(1, array(1, 2))
			->willReturn(false);

		$this->defaultProfileRepository->expects($this->never())
			->method('saveProfileId')
			->with(1);

		$sut->saveDefaultProfile(1);
	}

	/**
	 * @test
	 */
	public function saveBlogProfileAssociations_withExistingBlogAndProfileId()
	{
		$sut = $this->sut(array('validateBlog', 'validateProfile', 'getSites'));

		$profiles = array(
			'3',
			'99',
		);

		$sites = array(
			array('blog_id' => '2'),
			array('blog_id' => '9'),
		);

		$this->profileRepository->expects($this->once())
			->method('findAllIds')
			->willReturn($profiles);

		$sut->expects($this->once())
			->method('getSites')
			->willReturn($sites);

		$sut->expects($this->once())
			->method('validateBlog')
			->with('2', $sites)
			->willReturn(true);

		$sut->expects($this->once())
			->method('validateProfile')
			->with('3', $profiles)
			->willReturn(true);

		$this->blogConfigurationRepository->expects($this->once())
			->method('updateProfileId')
			->with('2', '3');

		$sut->saveBlogProfileAssociations('3', array('2'));
	}

	/**
	 * @test
	 */
	public function validateBlog_withNotExistingBlogId_returnFalse()
	{
		$sut = $this->sut(null);

		$sites = array(
			array('blog_id' => '2'),
			array('blog_id' => '9'),
		);

		$this->blogConfigurationRepository->expects($this->never())
			->method('updateProfileId');

		$actual = $sut->validateBlog('5', $sites);
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function validateProfile_withNotExistingProfileId_returnFalse()
	{
		$sut = $this->sut(null);

		$profiles = array(
			'3',
			'99',
		);

		$this->blogConfigurationRepository->expects($this->never())
			->method('updateProfileId');

		$actual = $sut->validateProfile('66', $profiles);
		$this->assertEquals(false, $actual);
	}
}