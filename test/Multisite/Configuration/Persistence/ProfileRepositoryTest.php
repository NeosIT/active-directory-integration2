<?php

/**
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Multisite_Configuration_Persistence_ProfileRepositoryTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository|PHPUnit_Framework_MockObject_MockObject $profileConfigurationRepository */
	private $profileConfigurationRepository;

	/* @var NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository|PHPUnit_Framework_MockObject_MockObject $blogConfigurationRepository */
	private $blogConfigurationRepository;

	/* @var NextADInt_Core_Persistence_WordPressRepository|PHPUnit_Framework_MockObject_MockObject $wordPressRepository */
	private $wordPressRepository;

	/* @var NextADInt_Multisite_Option_Provider */
	private $optionProvider;

	public function setUp()
	{
		parent::setUp();

		$this->profileConfigurationRepository = parent::createMock('NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository');
		$this->blogConfigurationRepository = parent::createMock('NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository');
		$this->wordPressRepository = parent::createMock('NextADInt_Core_Persistence_WordPressRepository');
		$this->optionProvider = new NextADInt_Adi_Configuration_Options();
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @param $methods
	 *
	 * @return NextADInt_Multisite_Configuration_Persistence_ProfileRepository|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Multisite_Configuration_Persistence_ProfileRepository')
			->setConstructorArgs(
				array(
					$this->profileConfigurationRepository,
					$this->blogConfigurationRepository,
					$this->wordPressRepository,
					$this->optionProvider,
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function getProfileOption_returnsExpectedResult()
	{
		$sut = $this->sut();

		$result = $this->invokeMethod($sut, 'getProfileOption', array(1, NextADInt_Multisite_Configuration_Persistence_ProfileRepository::PREFIX_NAME));

		$this->assertEquals('next_ad_int_p_n_1', $result);
	}

	/**
	 * @test
	 */
	public function getOptionNameByMapping_withMapping_returnsMappedName()
	{
		$sut = $this->sut();

		$result = $this->invokeMethod($sut, 'getOptionNameByMapping', array(
			NextADInt_Adi_Configuration_Options::PROFILE_NAME,
			1,
		));

		$this->assertEquals('next_ad_int_p_n_1', $result);
	}

	/**
	 * @test
	 */
	public function findAll_returnsExpectedResult()
	{
		$sut = $this->sut(array('findAllIDs', 'findName'));

		$sut->expects($this->once())
			->method('findAllIDs')
			->willReturn(array(1));

		$sut->expects($this->once())
			->method('findName')
			->with(1)
			->willReturn('name');

		$expected = array(
			array(
				'profileId'   => 1,
				'profileName' => 'name',
			),
		);

		$result = $sut->findAll();

		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 */
	public function findAllIDs_singleSite_returnEmptyArray()
	{
		$sut = $this->sut(null);

		WP_Mock::wpFunction('is_multisite', array(
			'times'  => 1,
			'return' => false,
		));

		$actual = $sut->findAllIds();
		$this->assertEquals(array(), $actual);
	}

	/**
	 * @test
	 */
	public function findAllIDs_multiSite_returnAllProfileIds()
	{
		$sut = $this->sut(null);

		WP_Mock::wpFunction('is_multisite', array(
			'times'  => 1,
			'return' => true,
		));

		$this->wordPressRepository->expects($this->once())
			->method('getTableSiteMeta')
			->willReturn('wp_sitemeta');

		$this->wordPressRepository->expects($this->once())
			->method('wpdb_get_col')
			->with("SELECT meta_key FROM wp_sitemeta WHERE meta_key LIKE 'next_ad_int_p_n_%';")
			->willReturn(array('next_ad_int_p_n_1', 'next_ad_int_p_n_2'));

		$actual = $sut->findAllIds();
		$this->assertEquals(array('1', '2'), $actual);
	}

	/**
	 * @test
	 */
	public function findName_triggersCorrectMethods()
	{
		$sut = $this->sut(array('getProfileOption'));
		$this->mockFunction__();

		$sut->expects($this->once())
			->method('getProfileOption')
			->with(1, NextADInt_Multisite_Configuration_Persistence_ProfileRepository::PREFIX_NAME)
			->willReturn('name');

		WP_Mock::wpFunction('get_site_option', array(
            'args'   => array('name', 'New Profile'),
            'times'  => 1,
            'return' => 'name')
		);

		$result = $sut->findName(1);

		$this->assertEquals('name', $result);
	}

	/**
	 * @test
	 */
	public function findDescription_triggersCorrectMethods()
	{
		$sut = $this->sut(array('getProfileOption'));

		$sut->expects($this->once())
			->method('getProfileOption')
			->with(1, NextADInt_Multisite_Configuration_Persistence_ProfileRepository::PREFIX_DESCRIPTION)
			->willReturn('desc');

		WP_Mock::wpFunction(
			'get_site_option', array(
				'args'   => array('desc', ''),
				'times'  => 1,
				'return' => 'desc',
			)
		);

		$result = $sut->findDescription(1);

		$this->assertEquals('desc', $result);
	}

	/**
	 * @test
	 */
	public function insert_searchUnusedProfileId_createNewProfile()
	{
		$sut = $this->sut(null);

		\WP_Mock::wpFunction('get_site_option', array(
			'args'   => array('next_ad_int_p_n_1', false),
			'times'  => 1,
			'return' => 'some value',
		));

		\WP_Mock::wpFunction('get_site_option', array(
			'args'   => array('next_ad_int_p_n_2', false),
			'times'  => 1,
			'return' => false,
		));

		\WP_Mock::wpFunction('update_site_option', array(
			'args'  => array('next_ad_int_p_n_2', 'p-name'),
			'times' => 1,
		));

		\WP_Mock::wpFunction('update_site_option', array(
			'args'  => array('next_ad_int_p_d_2', 'p-description'),
			'times' => 1,
		));

		$sut->insert('p-name', 'p-description');
	}

	/**
	 * @test
	 */
	public function insertProfileData_triggersCorrectMethods()
	{
		$sut = $this->sut(array('getProfileOption', 'getOptionNameByMapping'));

		$data = array(
			NextADInt_Adi_Configuration_Options::PROFILE_NAME => array('option_value' => 'test'),
			'show'                                  => array('option_value' => 'show'),
		);

		$sut->expects($this->once())
			->method('getProfileOption')
			->with(1, NextADInt_Multisite_Configuration_Persistence_ProfileRepository::PREFIX_NAME)
			->willReturn('name');

		WP_Mock::wpFunction(
			'get_site_option', array(
				'args'   => array('name', false),
				'times'  => 1,
				'return' => false,
			)
		);

		$sut->expects($this->exactly(2))
			->method('getOptionNameByMapping')
			->withConsecutive(
				array(NextADInt_Adi_Configuration_Options::PROFILE_NAME, 1),
				array('show', 1)
			)
			->willReturnOnConsecutiveCalls(
				'name',
				false
			);

		WP_Mock::wpFunction(
			'update_site_option', array(
				'args'  => array('name', 'test'),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'update_site_option', array(
				'args'  => array('show', 'show'),
				'times' => 0,
			)
		);

		$result = $sut->insertProfileData($data);

		$this->assertEquals(1, $result);
	}

	/**
	 * @test
	 */
	public function updateProfileData_triggersCorrectMethods()
	{
		$sut = $this->sut(array('getOptionNameByMapping'));

		$data = array(
			NextADInt_Adi_Configuration_Options::PROFILE_NAME => array('option_value' => 'test'),
			'show'                                  => array('option_value' => 'show'),
		);

		$sut->expects($this->exactly(2))
			->method('getOptionNameByMapping')
			->withConsecutive(
				array(NextADInt_Adi_Configuration_Options::PROFILE_NAME, 1),
				array('show', 1)
			)
			->willReturnOnConsecutiveCalls(
				'name',
				false
			);

		WP_Mock::wpFunction(
			'update_site_option', array(
				'args'  => array('name', 'test'),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'update_site_option', array(
				'args'  => array('show', 'show'),
				'times' => 0,
			)
		);

		$sut->updateProfileData($data, 1);
	}

	/**
	 * @test
	 */
	public function insertDefaultProfile_noProfilesExist_createDefaultProfile()
	{
		$sut = $this->sut(array('findAll', 'insert'));
		$this->mockFunction__();

		\WP_Mock::wpFunction('get_site_option', array(
			'args'   => array('next_ad_int_p_n_1', false),
			'times'  => 1,
			'return' => false,
		));

		$sut->expects($this->once())
			->method('insert')
			->with('My NADI profile',
				'This profile has been created by the plugin installation automatically. It can safely be deleted.')
			->willReturn(true);

		$actual = $sut->insertDefaultProfile();
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function insertDefaultProfile_profilesExist_doNothing()
	{
		$sut = $this->sut(array('findAll'));

		\WP_Mock::wpFunction('get_site_option', array(
			'args'   => array('next_ad_int_p_n_1', false),
			'times'  => 1,
			'return' => 'some value',
		));

		$actual = $sut->insertDefaultProfile();
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function updateName_delegateToMethod_updateProfileName()
	{
		$sut = $this->sut(null);

		\WP_Mock::wpFunction('update_site_option', array(
			'args'   => array('next_ad_int_p_n_5', 'new name'),
			'times'  => 1,
			'return' => true,
		));

		$actual = $sut->updateName(5, 'new name');
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function updateDescription_delegateToMethod_updateProfileDescription()
	{
		$sut = $this->sut(null);

		\WP_Mock::wpFunction('update_site_option', array(
			'args'   => array('next_ad_int_p_d_5', 'new description'),
			'times'  => 1,
			'return' => true,
		));

		$actual = $sut->updateDescription(5, 'new description');
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function delete_delegateToMethod_deleteProfileAndDependencies()
	{
		$sut = $this->sut(null);

		\WP_Mock::wpFunction('delete_site_option', array(
			'args'   => array('next_ad_int_p_n_5'),
			'times'  => 1,
			'return' => true,
		));

		\WP_Mock::wpFunction('delete_site_option', array(
			'args'   => array('next_ad_int_p_d_5'),
			'times'  => 1,
			'return' => true,
		));

		$this->profileConfigurationRepository->expects($this->at(0))
			->method('deleteValue')
			->with(5, NextADInt_Adi_Configuration_Options::SUPPORT_LICENSE_KEY);

		$this->profileConfigurationRepository->expects($this->at(1))
			->method('deletePermission')
			->with(5, NextADInt_Adi_Configuration_Options::SUPPORT_LICENSE_KEY);

		$this->blogConfigurationRepository->expects($this->once())
			->method('deleteProfileAssociations')
			->with(5);

		$sut->delete(5);
	}
}