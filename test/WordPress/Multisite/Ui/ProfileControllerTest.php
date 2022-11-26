<?php

namespace Dreitier\WordPress\Multisite\Ui;

use Dreitier\Test\BasicTest;
use Dreitier\WordPress\Multisite\Configuration\Persistence\BlogConfigurationRepository;
use Dreitier\WordPress\Multisite\Configuration\Persistence\DefaultProfileRepository;
use Dreitier\WordPress\Multisite\Configuration\Persistence\ProfileRepository;
use Mockery\Mock;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class ProfileControllerTest extends BasicTest
{
	/** @var ProfileRepository| MockObject */
	private $profileRepository;
	/** @var BlogConfigurationRepository|MockObject */
	private $blogConfigurationRepository;
	/** @var DefaultProfileRepository|MockObject */
	private $defaultProfileRepository;

	public function setUp(): void
	{
		parent::setUp();

		$this->profileRepository = $this->createMock(ProfileRepository::class);
		$this->blogConfigurationRepository = $this->createMock(BlogConfigurationRepository::class);
		$this->defaultProfileRepository = $this->createMock(DefaultProfileRepository::class);
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @param $methods
	 *
	 * @return ProfileController|MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder(ProfileController::class)
			->setConstructorArgs(array(
				$this->profileRepository,
				$this->blogConfigurationRepository,
				$this->defaultProfileRepository,
			))
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function findAll_delegatesCallToRepository()
	{
		$sut = $this->sut();

		$data = array('test');

		$this->profileRepository->expects($this->once())
			->method('findAll')
			->willReturn($data);

		$result = $sut->findAll();

		$this->assertEquals($data, $result);
	}

	public function findAllProfileAssociations_returnsExpectedResult()
	{
		$sut = $this->sut();

		$blogs = array(array('blog_id' => 1));

		$this->profileRepository->expects($this->once())
			->method('findAllIDs')
			->willReturn(array(1));

		$this->blogConfigurationRepository->expects($this->once())
			->method('findProfileAssociations')
			->with(1)
			->willReturn($blogs);

		$expected = array(1 => $blogs);

		$reuslt = $sut->findAllProfileAssociations();

		$this->assertEquals($expected, $reuslt);
	}

	/**
	 * @test
	 */
	public function validateType_withoutType_returnsFalse()
	{
		$sut = $this->sut();

		$result = $sut->validateType(array());

		$this->assertFalse($result);
	}

	/**
	 * @test
	 */
	public function validateType_withType_returnsTrue()
	{
		$sut = $this->sut();

		$result = $sut->validateType(array('type' => ''));

		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function saveProfile_withEmptyName_returnsFalse()
	{
		$sut = $this->sut(array('saveProfileInternal'));

		$sut->expects($this->never())
			->method('saveProfileInternal');

		$result = $sut->saveProfile(array(), 1);

		$this->assertFalse($result);
	}

	/**
	 * @test
	 */
	public function saveProfile_withNameAndNoErrors_returnsSuccessfulMessage()
	{
		$sut = $this->sut(array('saveProfileInternal'));

		$options = array('profile_name' => 'name');
		$data = array(
			'options' => $options,
		);

		$sut->expects($this->once())
			->method('saveProfileInternal')
			->with($options, 1)
			->willReturn(1);

		$result = $sut->saveProfile($data, 1);

		$this->assertEquals(1, $result);
	}

	/**
	 * @test
	 */
	public function saveProfileInternal_withNewProfile_delegatesCallToProfileRepositoryInsertProfileData()
	{
		$sut = $this->sut();

		$data = array('name' => 'name');

		$this->profileRepository->expects($this->once())
			->method('insertProfileData')
			->with($data)
			->willReturn(1);

		$this->profileRepository->expects($this->never())
			->method('updateProfileData')
			->with($data, 1)
			->willReturn(false);

		$result = $this->invokeMethod($sut, 'saveProfileInternal', array($data, null));
		$this->assertEquals(1, $result);
	}

	/**
	 * @test
	 */
	public function saveProfileInternal_withExistingProfile_delegatesCallToProfileRepositoryUpdateProfileData()
	{
		$sut = $this->sut();

		$data = array('name' => 'name');

		$this->profileRepository->expects($this->never())
			->method('insertProfileData')
			->with($data)
			->willReturn(true);

		$this->profileRepository->expects($this->once())
			->method('updateProfileData')
			->with($data, 1)
			->willReturn(false);

		$result = $this->invokeMethod($sut, 'saveProfileInternal', array($data, 1));
		$this->assertEquals(1, $result);
	}

	/**
	 * @test
	 */
	public function addProfile()
	{
		$sut = $this->sut(null);

		$data = array(
			'type' => 'add',
			'name' => 'TestName',
			'description' => 'TestDescription',
		);

		$this->profileRepository->expects($this->once())
			->method('insert')
			->with('TestName', 'TestDescription');

		$sut->addProfile($data);
	}

	/**
	 * @test
	 */
	public function deleteProfile()
	{
		$sut = $this->sut(null);

		$this->profileRepository->expects($this->once())
			->method('delete')
			->with(123);

		$sut->deleteProfile(123);
	}

	/**
	 * @test
	 */
	public function deleteProfile_withEmptyId_returnsFalse()
	{
		$sut = $this->sut(null);

		$this->profileRepository->expects($this->never())
			->method('delete')
			->with(null);

		$result = $sut->deleteProfile(null);

		$this->assertFalse($result);
	}

	/**
	 * @test
	 */
	public function deleteProfile_withErrorOnDelete_returnsErrorMessage()
	{
		$sut = $this->sut(null);
		$this->mockFunction__();

		$this->profileRepository->expects($this->once())
			->method('delete')
			->with(1)
			->willThrowException(new \Exception('test'));

		$expected = array(
			'message' => 'An error occurred while deleting the profile.',
			'type' => 'error',
			'isMessage' => true,
			'additionalInformation' => array(),
		);

		$result = $sut->deleteProfile(1);

		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 */
	public function deleteProfile_withSuccessOnDelete_returnsSuccessMessage()
	{
		$sut = $this->sut(null);
		$this->mockFunction__();

		$this->profileRepository->expects($this->once())
			->method('delete')
			->with(1);

		$expected = array(
			'message' => 'The profile was deleted successfully.',
			'type' => 'success',
			'isMessage' => true,
			'additionalInformation' => array(),
		);

		$result = $sut->deleteProfile(1);

		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 */
	public function changeProfile_withValidData_updateNameAndDescription()
	{
		$sut = $this->sut(array('validateId', 'validateName', 'validateDescription'));

		$data = array(
			'type' => 'change',
			'name' => 'TestName',
			'description' => 'TestDescription',
			'id' => '123',
		);

		$sut->expects($this->once())
			->method('validateId')
			->with($data)
			->willReturn(true);

		$sut->expects($this->once())
			->method('validateName')
			->with($data)
			->willReturn(true);

		$sut->expects($this->once())
			->method('validateDescription')
			->with($data)
			->willReturn(true);

		$this->profileRepository->expects($this->once())
			->method('updateName')
			->with('123', 'TestName');

		$this->profileRepository->expects($this->once())
			->method('updateDescription')
			->with('123', 'TestDescription');

		$sut->changeProfile($data);
	}

	/**
	 * @test
	 */
	public function validateName_withoutName_returnsFalse()
	{
		$sut = $this->sut();

		$result = $sut->validateName(array());

		$this->assertFalse($result);
	}

	/**
	 * @test
	 */
	public function validateName_withName_returnsTrue()
	{
		$sut = $this->sut();

		$result = $sut->validateName(array('name' => 'test'));

		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function validateDescription_withoutDescription_returnsFalse()
	{
		$sut = $this->sut();

		$result = $sut->validateDescription(array());

		$this->assertFalse($result);
	}

	/**
	 * @test
	 */
	public function validateDescription_withDescription_returnsTrue()
	{
		$sut = $this->sut();

		$result = $sut->validateDescription(array('description' => 'test'));

		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function validateId_withoutId_returnsFalse()
	{
		$sut = $this->sut();

		$result = $sut->validateId(array());

		$this->assertFalse($result);
	}

	/**
	 * @test
	 */
	public function validateId_withoutId_returnsTrue()
	{
		$sut = $this->sut();

		$result = $sut->validateId(array('id' => 1));

		$this->assertTrue($result);
	}
}