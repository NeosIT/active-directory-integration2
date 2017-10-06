<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepositoryTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_Option_Sanitizer|PHPUnit_Framework_MockObject_MockObject $attributes */
	private $sanitizer;

	/* @var NextADInt_Core_Encryption|PHPUnit_Framework_MockObject_MockObject $attributes */
	private $encryptionHandler;

	/** @var NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository| PHPUnit_Framework_MockObject_MockObject */
	private $profileConfigurationRepository;

	/* @var NextADInt_Multisite_Option_Provider| $optionProvider */
	private $optionProvider;

	/** @var NextADInt_Multisite_Configuration_Persistence_DefaultProfileRepository|PHPUnit_Framework_MockObject_MockObject $defaultProfileRepository */
	private $defaultProfileRepository;

	public function setUp()
	{
		parent::setUp();

		$this->sanitizer = parent::createMock('NextADInt_Multisite_Option_Sanitizer');
		$this->encryptionHandler = parent::createMock('NextADInt_Core_Encryption');
		$this->profileConfigurationRepository = parent::createMock
		('NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository');
		$this->defaultProfileRepository = parent::createMock
		('NextADInt_Multisite_Configuration_Persistence_DefaultProfileRepository');
		$this->optionProvider = new NextADInt_Adi_Configuration_Options();
	}

	public function tearDown()
	{
        global $wp_version;
        unset($wp_version);
		parent::tearDown();
	}

	/**
	 * @param $methods
	 *
	 * @return NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods)
	{
		return $this->getMockBuilder('NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository')
			->setConstructorArgs(
				array(
					$this->sanitizer,
					$this->encryptionHandler,
					$this->optionProvider,
					$this->profileConfigurationRepository,
					$this->defaultProfileRepository,
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function findAllSanitized_getValuesForAllOptions_delegateToMethod()
	{
		$sut = $this->sut(array('findSanitizedValue'));
		$this->mockFunction__();

		$sut->expects($this->at(0))
			->method('findSanitizedValue')
			->with(5, NextADInt_Adi_Configuration_Options::SUPPORT_LICENSE_KEY)
			->willReturn('support_license_key');

		$actual = $sut->findAllSanitized(5);
		$this->assertEquals('support_license_key', $actual[NextADInt_Adi_Configuration_Options::SUPPORT_LICENSE_KEY]);
	}

	/**
	 * @test
	 */
	public function findAllSanitized_invalidOptionName_returnNull()
	{
		$sut = $this->sut(null);
		$actual = $sut->findSanitizedValue(5, NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository::PROFILE_ID);
		$this->assertEquals(null, $actual);
	}

	/**
	 * @test
	 */
	public function findSanitized_withProfileHandledOption_returnValue()
	{
		$sut = $this->sut(array('isOptionHandledByProfile', 'findProfileId'));


		$sut->expects($this->once())
			->method('isOptionHandledByProfile')
			->with(5, NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD)
			->willReturn(true);

		$sut->expects($this->once())
			->method('findProfileId')
			->with(5)
			->willReturn(1);

		$this->profileConfigurationRepository->expects($this->once())
			->method('findSanitizedValue')
			->with(1, NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD)
			->willReturn('profile-password!');

		$actual = $sut->findSanitizedValue(5, NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD);
		$this->assertEquals('profile-password!', $actual);
	}

	/**
	 * @test
	 */
	public function findSanitized_optionIsPassword_returnValue()
	{
		$sut = $this->sut(array('findRawValue', 'isOptionHandledByProfile'));
		$this->mockFunction__();

		$sut->expects($this->once())
			->method('isOptionHandledByProfile')
			->with(5, NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD)
			->willReturn(false);

		$sut->expects($this->once())
			->method('findRawValue')
			->with(5, NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD)
			->willReturn('--encrypted--');

		$this->encryptionHandler->expects($this->once())
			->method('decrypt')
			->with('--encrypted--')
			->willReturn('password!');

		$this->sanitizer->expects($this->once())
			->method('sanitize')
			->with('password!')
			->willReturn('password!');

		$actual = $sut->findSanitizedValue(5, NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD);
		$this->assertEquals('password!', $actual);
	}

	/**
	 * @test
	 */
	public function findSanitized_optionMustBeSanitized_returnValue()
	{
		$sut = $this->sut(array('findRawValue', 'isOptionHandledByProfile'));
		$this->mockFunction__();

		$sut->expects($this->once())
			->method('isOptionHandledByProfile')
			->with(5, NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS)
			->willReturn(false);

		$sut->expects($this->once())
			->method('findRawValue')
			->with(5, NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS)
			->willReturn(' hi ');

		$meta = $this->optionProvider->get(NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS);

		$this->sanitizer->expects($this->once())
			->method('sanitize')
			->with(' hi ', $meta[NextADInt_Multisite_Option_Attribute::SANITIZER], $meta)
			->willReturn('hi');

		$actual = $sut->findSanitizedValue(5, NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS);
		$this->assertEquals('hi', $actual);
	}

	/**
	 * @test
	 */
	public function findSanitized_passwordMustBeSanitized_returnValue()
	{
		$sut = $this->sut(array('findRawValue', 'isOptionHandledByProfile'));

		$meta = $this->optionProvider->get(NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD);

		$sut->expects($this->once())
			->method('isOptionHandledByProfile')
			->with(5, NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD)
			->willReturn(false);

		$sut->expects($this->once())
			->method('findRawValue')
			->with(5, NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD)
			->willReturn('--encrypted--');

		$this->encryptionHandler->expects($this->once())
			->method('decrypt')
			->with('--encrypted--')
			->willReturn('  password!  ');

		$this->sanitizer->expects($this->once())
			->method('sanitize')
			->with('  password!  ', $meta[NextADInt_Multisite_Option_Attribute::SANITIZER], $meta)
			->willReturn('password!');

		$actual = $sut->findSanitizedValue(5, NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD);
		$this->assertEquals('password!', $actual);
	}

	/**
	 * @test
	 */
	public function isOptionHandledByProfile_withEditableOption_returnsFalse()
	{
		$sut = $this->sut(array('findProfileId'));

		$sut->expects($this->once())
			->method('findProfileId')
			->with(5)
			->willReturn(1);

		$this->profileConfigurationRepository->expects($this->once())
			->method('findSanitizedPermission')
			->with(1, NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD)
			->willReturn(NextADInt_Multisite_Configuration_Service::EDITABLE);

		$actual = $this->invokeMethod($sut, 'isOptionHandledByProfile', array(
			5, NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD,
		));

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function isOptionHandledByProfile_withNonEditableOption_returnsTrue()
	{
		$sut = $this->sut(array('findProfileId'));

		$sut->expects($this->once())
			->method('findProfileId')
			->with(5)
			->willReturn(1);

		$this->profileConfigurationRepository->expects($this->once())
			->method('findSanitizedPermission')
			->with(1, NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD)
			->willReturn(NextADInt_Multisite_Configuration_Service::DISABLED_FOR_BLOG_ADMIN);

		$actual = $this->invokeMethod($sut, 'isOptionHandledByProfile', array(
			5, NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD,
		));

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function find_singleSite_returnOptionValue()
	{
		$sut = $this->sut(array('getOptionName'));

		\WP_Mock::wpFunction(
			'is_multisite', array(
				'times'  => 1,
				'return' => false,
			)
		);

		$sut->expects($this->once())
			->method('getOptionName')
			->with('port')
			->willReturn('next_ad_int_bov_port');

		\WP_Mock::wpFunction(
			'get_option', array(
				'args'   => array('next_ad_int_bov_port', false),
				'times'  => 1,
				'return' => true,
			)
		);

		$actual = $this->invokeMethod($sut, 'findRawValue', array(6, 'port'));
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function find_multiSite_returnBlogOptionValue()
	{
		$sut = $this->sut(array('getOptionName'));

		\WP_Mock::wpFunction(
			'is_multisite', array(
				'times'  => 1,
				'return' => true,
			)
		);

		$sut->expects($this->once())
			->method('getOptionName')
			->with('port')
			->willReturn('next_ad_int_bov_port');

		\WP_Mock::wpFunction(
			'get_blog_option', array(
				'args'   => array(6, 'next_ad_int_bov_port', false),
				'times'  => 1,
				'return' => true,
			)
		);

		$actual = $this->invokeMethod($sut, 'findRawValue', array(6, 'port'));
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function persistSanitized_invalidOptionName_returnNull()
	{
		$sut = $this->sut(null);
		$value = $sut->persistSanitizedValue(5, NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository::PROFILE_ID,
			'');
		$this->assertEquals(null, $value);
	}

	/**
	 * @test
	 */
	public function persistSanitized_optionMustBeSanitized_persistValue()
	{
		$sut = $this->sut(array('persist'));

		$meta = $this->optionProvider->get(NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS);

		$this->sanitizer->expects($this->once())
			->method('sanitize')
			->with('123456', $meta[NextADInt_Multisite_Option_Attribute::SANITIZER], $meta)
			->willReturn('sanitized');

		$sut->expects($this->once())
			->method('persist')
			->with(6, NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS, 'sanitized')
			->willReturn('sanitized');

		$value = $sut->persistSanitizedValue(6, NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS, '123456');
		$this->assertEquals('sanitized', $value);
	}

	/**
	 * @test
	 */
	public function persistSanitized_optionMustBeEncrypted_persistValue()
	{
		$sut = $this->sut(array('persist'));

		$this->sanitizer->expects($this->once())
			->method('sanitize')
			->with('123456')
			->willReturn('123456');

		$this->encryptionHandler->expects($this->once())
			->method('encrypt')
			->with('123456')
			->willReturn('--encrypted--');

		$sut->expects($this->once())
			->method('persist')
			->with(6, NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD, '--encrypted--')
			->willReturn('--encrypted--');

		$value = $sut->persistSanitizedValue(6, NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD, '123456');
		$this->assertEquals('--encrypted--', $value);
	}

	/**
	 * @test
	 */
	public function persistSanitized_optionMustBeSanitizedAndEncrypted_persistValue()
	{
		$sut = $this->sut(array('persist'));

		$meta = $this->optionProvider->get(NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD);

		$this->sanitizer->expects($this->once())
			->method('sanitize')
			->with('123456', $meta[NextADInt_Multisite_Option_Attribute::SANITIZER], $meta)
			->willReturn('sanitized');

		$this->encryptionHandler->expects($this->once())
			->method('encrypt')
			->with('sanitized')
			->willReturn('--encrypted--');

		$sut->expects($this->once())
			->method('persist')
			->with(6, NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD, '--encrypted--')
			->willReturn('--encrypted--');

		$value = $sut->persistSanitizedValue(6, NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD, '123456');
		$this->assertEquals('--encrypted--', $value);
	}

	/**
	 * @test
	 */
	public function persist_singleSite_persistOptionValue()
	{
		$sut = $this->sut(array('getOptionName'));

		\WP_Mock::wpFunction(
			'is_multisite', array(
				'times'  => 1,
				'return' => false,
			)
		);

		$sut->expects($this->once())
			->method('getOptionName')
			->with('port')
			->willReturn('next_ad_int_bov_port');

		\WP_Mock::wpFunction(
			'update_option', array(
				'args'   => array('next_ad_int_bov_port', 'value', false),
				'times'  => 1,
				'return' => true,
			)
		);

		$actual = $this->invokeMethod($sut, 'persist', array(6, 'port', 'value'));
		$this->assertEquals('value', $actual);
	}

	/**
	 * @test
	 */
	public function persist_multiSite_persistBlogOptionValue()
	{
		$sut = $this->sut(array('getOptionName'));

		\WP_Mock::wpFunction(
			'is_multisite', array(
				'times'  => 1,
				'return' => true,
			)
		);

		$sut->expects($this->once())
			->method('getOptionName')
			->with('port')
			->willReturn('next_ad_int_bov_port');

		\WP_Mock::wpFunction(
			'update_blog_option', array(
				'args'   => array(6, 'next_ad_int_bov_port', 'value'),
				'times'  => 1,
				'return' => true,
			)
		);

		$actual = $this->invokeMethod($sut, 'persist', array(6, 'port', 'value'));
		$this->assertEquals('value', $actual);
	}

	/**
	 * @test
	 */
	public function isDefaultProfileUsed_withProfile_returnFalse()
	{
		$sut = $this->sut(array('findRawValue'));

		$sut->expects($this->once())
			->method('findRawValue')
			->with(10, NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository::PROFILE_ID)
			->willReturn(1);

		$this->defaultProfileRepository->expects($this->once())
			->method('findProfileId')
			->willReturn(false);

		$actual = $sut->isDefaultProfileUsed(10);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function isDefaultProfileUsed_withDefaultProfileFalse_returnFalse()
	{
		$sut = $this->sut(array('findRawValue'));

		$sut->expects($this->once())
			->method('findRawValue')
			->with(10, NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository::PROFILE_ID)
			->willReturn(false);

		$this->defaultProfileRepository->expects($this->once())
			->method('findProfileId')
			->willReturn(false);

		$actual = $sut->isDefaultProfileUsed(10);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function isDefaultProfileUsed_withDefaultProfileNone_returnFalse()
	{
		$sut = $this->sut(array('findRawValue'));

		$sut->expects($this->once())
			->method('findRawValue')
			->with(10, NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository::PROFILE_ID)
			->willReturn(false);

		$this->defaultProfileRepository->expects($this->once())
			->method('findProfileId')
			->willReturn(-1);

		$actual = $sut->isDefaultProfileUsed(10);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function isDefaultProfileUsed_withDefaultProfile_returnTrue()
	{
		$sut = $this->sut(array('findRawValue'));

		$sut->expects($this->once())
			->method('findRawValue')
			->with(10, NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository::PROFILE_ID)
			->willReturn(false);

		$this->defaultProfileRepository->expects($this->once())
			->method('findProfileId')
			->willReturn(1);

		$actual = $sut->isDefaultProfileUsed(10);

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function findProfileId_delegateToMethod_returnProfileId()
	{
		$sut = $this->sut(array('findRawValue'));

		$this->defaultProfileRepository->expects($this->never())
			->method('findProfileId');

		$sut->expects($this->once())
			->method('findRawValue')
			->with(10, NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository::PROFILE_ID)
			->willReturn(10);

		$value = $sut->findProfileId(10);
		$this->assertEquals($value, 10);
	}

	/**
	 * @test
	 */
	public function findProfileId_withDefaultProfile_returnDefaultProfileId()
	{
		$sut = $this->sut(array('findRawValue'));

		$sut->expects($this->once())
			->method('findRawValue')
			->with(10, NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository::PROFILE_ID)
			->willReturn(false);

		$this->defaultProfileRepository->expects($this->once())
			->method('findProfileId')
			->willReturn(5);

		$value = $sut->findProfileId(10);
		$this->assertEquals($value, 5);
	}

	/**
	 * @test
	 */
	public function updateProfileId_delegateToMethod_returnProfileId()
	{
		$sut = $this->sut(array('persist'));

		$sut->expects($this->once())
			->method('persist')
			->with(10, NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository::PROFILE_ID, 666)
			->willReturn(666);

		$value = $sut->updateProfileId(10, 666);
		$this->assertEquals(666, $value);
	}

	/**
	 * @test
	 */
	public function delete_singleSite_deleteOptionValue()
	{
		$sut = $this->sut(array('getOptionName'));

		\WP_Mock::wpFunction(
			'is_multisite', array(
				'times'  => 1,
				'return' => false,
			)
		);

		$sut->expects($this->once())
			->method('getOptionName')
			->with('port')
			->willReturn('next_ad_int_bov_port');

		\WP_Mock::wpFunction(
			'delete_option', array(
				'args'   => array('next_ad_int_bov_port'),
				'times'  => 1,
				'return' => true,
			)
		);

		$actual = $this->invokeMethod($sut, 'delete', array(6, 'port'));
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function delete_multiSite_deleteBlogOptionValue()
	{
		$sut = $this->sut(array('getOptionName'));

		\WP_Mock::wpFunction(
			'is_multisite', array(
				'times'  => 1,
				'return' => true,
			)
		);

		$sut->expects($this->once())
			->method('getOptionName')
			->with('port')
			->willReturn('next_ad_int_bov_port');

		\WP_Mock::wpFunction('delete_blog_option', array(
				'args'   => array(6, 'next_ad_int_bov_port'),
				'times'  => 1,
				'return' => true)
		);

		$actual = $this->invokeMethod($sut, 'delete', array(6, 'port'));
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function deleteProfileAssociations_delegateToMethod_returnProfileId()
	{
		$sut = $this->sut(array('findRawValue', 'delete', 'getSites'));

		$sites = array(
			array(
				'blog_id' => 1,
			),
			array(
				'blog_id' => 3,
			),
		);

		$sut->expects($this->once())
			->method('getSites')
			->willReturn($sites);

		$sut->expects($this->exactly(2))
			->method('findRawValue')
			->withConsecutive(
				array(1, NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository::PROFILE_ID),
				array(3, NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository::PROFILE_ID)
			)
			->will($this->onConsecutiveCalls(7, 9));

		$sut->expects($this->once())
			->method('delete')
			->with(3, NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository::PROFILE_ID);

		$this->invokeMethod($sut, 'deleteProfileAssociations', array(9));
	}

	/**
	 * @test
	 */
	public function getSites_multiSite_returnDummy()
	{
		$sut = $this->sut(null);

		$sites = array(
			array(
				'blog_id' => 1,
			),
			array(
				'blog_id' => 3,
			),
		);

		\WP_Mock::wpFunction('is_multisite', array(
				'times'  => 1,
				'return' => true)
		);

        // NextADInt_Core_Util_Internal_WordPress::getSites() will call wp_get_sites when wp_version == 4.5
        global $wp_version;
        $wp_version = '4.5';
		\WP_Mock::wpFunction('wp_get_sites', array(
				'times'  => 1,
				'return' => $sites)
		);

		$this->assertEquals($sites, $sut->getSites());
	}

	/**
	 * @test
	 */
	public function getSites_singleSite_returnSites()
	{
		$sut = $this->sut(null);

		$sites = array(
			array(
				'blog_id' => 0,
			),
		);

		\WP_Mock::wpFunction('is_multisite', array(
				'times'  => 1,
				'return' => false)
		);

		$this->assertEquals($sites, $sut->getSites());
	}


	/**
	 * @test
	 */
	public function persist_updateOption_withOptionExistsMultisite()
	{
		$this->mockFunction__();

		$sut = $this->sut(array('doesOptionExist', 'updateOption'));

		$siteId = 1;
		$optionName = NextADInt_Adi_Configuration_Options::PORT;
		$optionValue = 389;

		$databaseOptionName = 'next_ad_int_bo_v_port';

		$this->sanitizer->expects($this->once())
			->method('sanitize')
			->willReturn(389);

		$sut->expects($this->once())
			->method('doesOptionExist')
			->with($databaseOptionName, $siteId)
			->willReturn(true);

		$sut->expects($this->once())
			->method('updateOption')
			->with($databaseOptionName, $optionValue, $siteId)
			->willReturn(true);

		\WP_Mock::wpFunction('is_multisite', array(
				'times'  => 1,
				'return' => true)
		);

		$actual = $sut->persistSanitizedValue(1, $optionName, $optionValue);
		$expected = 389;

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function persist_createOption_withOptionDoesNotExistsMultisite()
	{
		$this->mockFunction__();

		$sut = $this->sut(array('doesOptionExist', 'createOption'));

		$siteId = 1;
		$optionName = NextADInt_Adi_Configuration_Options::PORT;
		$optionValue = 389;

		$databaseOptionName = 'next_ad_int_bo_v_port';

		$this->sanitizer->expects($this->once())
			->method('sanitize')
			->willReturn(389);

		$sut->expects($this->once())
			->method('doesOptionExist')
			->with($databaseOptionName, $siteId)
			->willReturn(false);

		$sut->expects($this->once())
			->method('createOption')
			->with($databaseOptionName, $optionValue, $siteId)
			->willReturn(true);

		\WP_Mock::wpFunction('is_multisite', array(
				'times'  => 1,
				'return' => true)
		);

		$actual = $sut->persistSanitizedValue(1, $optionName, $optionValue);
		$expected = 389;

		$this->assertEquals($expected, $actual);
	}
}
