<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_User_Ui_ExtendUserListTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_Configuration_Service | PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/* @var */
	private $internalNative;

	public function setUp() : void
	{
		parent::setUp();

		$this->configuration = $this->getMockBuilder('NextADInt_Multisite_Configuration_Service')
			->disableOriginalConstructor()
			->setMethods(array('getOptionValue'))
			->getMock();

		// mock native functions
		$this->internalNative = $this->createMockedNative();
		NextADInt_Core_Util::native($this->internalNative);
	}

	public function tearDown() : void
	{
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function register_showUserStatusFalse()
	{
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::SHOW_USER_STATUS)
			->willReturn(false);

		$returnedValue = $sut->register();
		$this->assertNull($returnedValue);
	}

	/**
	 *
	 * @return NextADInt_Adi_User_Ui_ExtendUserList| PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Adi_User_Ui_ExtendUserList')
			->setConstructorArgs(
				array(
					$this->configuration
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function register_itAddsFilters()
	{
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::SHOW_USER_STATUS)
			->willReturn(true);

		\WP_Mock::expectFilterAdded('manage_users_columns', array($sut, 'addColumns'));
		\WP_Mock::expectFilterAdded('manage_users_custom_column', array($sut, 'addContent'), 10, 3);

		$sut->register();
	}

	/**
	 * @test
	 */
	public function addColumns()
	{
		$sut = $this->sut(null);
		$this->mockFunction__();

		$columns = array();

		$filledColumns = array(
			$sut->__columnIsAdiUser() => 'NADI User',
			$sut->__columnUserDisabled() => 'Disabled',
		);

		$returnedValue = $sut->addColumns($columns);
		$this->assertEquals($filledColumns, $returnedValue);
	}

	/**
	 * @test
	 */
	public function addColumns_withCrmPeActivated()
	{
		$sut = $this->sut(array('__columnManagedByCrmPe'));
		$this->mockFunction__();


		$this->internalNative->expects($this->once())
			->method('isClassAvailable')
			->willReturn(true);

		$sut->expects($this->exactly(2))
			->method('__columnManagedByCrmPe')
			->willReturn('pe_crm_is_managed_by_pe_1');

		$columns = array();

		$filledColumns = array(
			$sut->__columnIsAdiUser() => 'NADI User',
			$sut->__columnUserDisabled() => 'Disabled',
			$sut->__columnManagedByCrmPe() => 'Managed by CRM'
		);

		$returnedValue = $sut->addColumns($columns);
		$this->assertEquals($filledColumns, $returnedValue);
	}

	/**
	 * @test
	 */
	public function addContent_rendersAdiUser() {
		$sut = $this->sut(array('renderIsAdiUserColumn'));

		$sut->expects($this->once())
			->method('renderIsAdiUserColumn')
			->with(666)
			->willReturn('EXP');

		$this->assertEquals('EXP', $sut->addContent('', $sut->__columnIsAdiUser(), 666));
	}

	/**
	 * @test
	 */
	public function addContent_rendersDisabledReason() {
		$sut = $this->sut(array('renderDisabledColumn'));

		$sut->expects($this->once())
			->method('renderDisabledColumn')
			->with(666)
			->willReturn('EXP');

		$this->assertEquals('EXP', $sut->addContent('', $sut->__columnUserDisabled(), 666));
	}

	/**
	 * @test
	 */
	public function renderUsernameColumn_itAddsPlaceholder()
	{
		$sut = $this->sut(null);

		$userId = 1;

		WP_Mock::wpFunction(
			'get_user_meta', array(
				'args' => array($userId, NEXT_AD_INT_PREFIX . 'samaccountname', true),
				'times' => '1',
				'return' => 'testUser'
			)
		);
		$expected = '<div class="adi_user dashicons dashicons-admin-users">&nbsp;</div>';
		$returnedValue = $sut->renderIsAdiUserColumn($userId);

		$this->assertEquals($expected, $returnedValue);
	}

	/**
	 * @test
	 */
	public function renderUsernameColumn_itAddsAnEmptyString()
	{
		$sut = $this->sut(null);

		$userId = 1;

		WP_Mock::wpFunction(
			'get_user_meta', array(
				'args' => array($userId, NEXT_AD_INT_PREFIX . 'samaccountname', true),
				'times' => '1',
				'return' => ''
			)
		);

		$expected = '';
		$returnedValue = $sut->renderIsAdiUserColumn($userId);

		$this->assertEquals($expected, $returnedValue);
	}

/**
 * @test
 */
	public function renderDisabledColumn_itShowsDisablingReason()
	{
		$sut = $this->sut(null);

		$userId = 1;

		WP_Mock::wpFunction(
			'get_user_meta', array(
				'args' => array($userId, $sut->__columnUserDisabled(), true),
				'times' => '1',
				'return' => 'true'
			)
		);

		WP_Mock::wpFunction(
			'get_user_meta', array(
				'args' => array($userId, NEXT_AD_INT_PREFIX . 'user_disabled_reason', true),
				'times' => '1',
				'return' => 'Spam'
			)
		);
		$expected = '<div class=\'adi_user_disabled\'>Spam</div>';
		$returnedValue = $sut->renderDisabledColumn($userId);

		$this->assertEquals($expected, $returnedValue);
	}

	/**
	 * @test
	 */
	public function renderManagedByCrmPeColumn_itShowsIfPremiumExtensionCRMIsEnabled()
	{
		$sut = $this->sut(array('__columnManagedByCrmPe'));

		$crmMetakey = 'next_ad_int_pe_crm_is_managed_by_pe_1';

		$userId = 1;

		$sut->expects($this->once())
			->method('__columnManagedByCrmPe')
			->willReturn($crmMetakey);

		WP_Mock::wpFunction(
			'get_user_meta', array(
				'args' => array($userId, $crmMetakey, true),
				'times' => '1',
				'return' => 'true'
			)
		);

		$expected = '<div class=\'adi_user_is_managed_by_crm_pe dashicons dashicons-yes\'>&nbsp;</div>';
		$returnedValue = $sut->renderManagedByCrmPe($userId);

		$this->assertEquals($expected, $returnedValue);
	}

	/**
	 * @test
	 */
	public function renderDisabledColumn_itShowEmptyString()
	{
		$sut = $this->sut(null);

		$userId = 1;

		WP_Mock::wpFunction(
			'get_user_meta', array(
				'args' => array($userId, $sut->__columnUserDisabled(), true),
				'times' => '1',
				'return' => false
			)
		);

		WP_Mock::wpFunction(
			'get_user_meta', array(
				'args' => array($userId, NEXT_AD_INT_PREFIX . 'user_disabled_reason', true),
				'times' => '1',
				'return' => 'Spam'
			)
		);
		$expected = '';
		$returnedValue = $sut->renderDisabledColumn($userId);

		$this->assertEquals($expected, $returnedValue);
	}

	/**
	 * @test
	 */
	public function addContent_noCase_doNotAlterValuesFromOtherColumns()
	{
		$sut = $this->sut(null);

		$userId = 1;

		$expected = 'something';
		$returnedValue = $sut->addContent('something', 'NoCase', $userId);

		$this->assertEquals($expected, $returnedValue);
	}
}