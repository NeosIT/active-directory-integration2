<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Multisite_Ui_BlogProfileRelationshipControllerTest extends Ut_BasicTest
{
	/** @var NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository| PHPUnit_Framework_MockObject_MockObject */
	private $blogConfigurationRepository;

	/** @var NextADInt_Multisite_Configuration_Persistence_ProfileRepository| PHPUnit_Framework_MockObject_MockObject */
	private $profileRepository;

	/** @var  NextADInt_Core_Persistence_WordPressRepository | PHPUnit_Framework_MockObject_MockObject */
	private $wordPressRepository;

	/** @var NextADInt_Multisite_Configuration_Persistence_DefaultProfileRepository|PHPUnit_Framework_MockObject_MockObject */
	private $defaultProfileRepository;

	public function setUp()
	{
		parent::setUp();

		$this->blogConfigurationRepository = $this->createMock('NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository');
		$this->profileRepository = $this->createMock('NextADInt_Multisite_Configuration_Persistence_ProfileRepository');
		$this->wordPressRepository = $this->createMock('NextADInt_Core_Persistence_WordPressRepository');
		$this->defaultProfileRepository = $this->createMock
		('NextADInt_Multisite_Configuration_Persistence_DefaultProfileRepository');
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @param $methods
	 *
	 * @return NextADInt_Multisite_Ui_BlogProfileRelationshipController|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Multisite_Ui_BlogProfileRelationshipController')
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
			->method('saveDefaultProfile')
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