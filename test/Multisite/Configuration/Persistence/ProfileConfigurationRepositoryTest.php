<?php
class Ut_Multisite_Configuration_Persistence_ProfileConfigurationRepositoryTest extends Ut_BasicTest
{
	/* @var Multisite_Option_Sanitizer|PHPUnit_Framework_MockObject_MockObject $sanitizer */
	private $sanitizer;

	/* @var Core_Encryption|PHPUnit_Framework_MockObject_MockObject $encryptionHandler */
	private $encryptionHandler;

	/** @var Multisite_Option_Provider */
	private $optionProvider;

	public function setUp()
	{
		parent::setUp();
		$this->sanitizer = parent::createMock('Multisite_Option_Sanitizer');
		$this->encryptionHandler = parent::createMock('Core_Encryption');
		$this->optionProvider = new Adi_Configuration_Options();
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @param $methods
	 *
	 * @return Multisite_Configuration_Persistence_ProfileConfigurationRepository|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods)
	{
		return $this->getMockBuilder('Multisite_Configuration_Persistence_ProfileConfigurationRepository')
			->setConstructorArgs(
				array(
					$this->sanitizer,
					$this->encryptionHandler,
					$this->optionProvider
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function findValueSanitized_optionMustBeDecrypted_returnDecryptedValue()
	{
		$sut = $this->sut(array('findValue'));

		$sut->expects($this->once())
			->method('findValue')
			->with(999, Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD)
			->willReturn('abba');

		$this->encryptionHandler->expects($this->once())
			->method('decrypt')
			->with('abba')
			->willReturn('encrypted');

		$this->sanitizer->expects($this->once())
			->method('sanitize')
			->with('encrypted')
			->willReturn('encrypted');

		$actual = $sut->findSanitizedValue(999, Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD);
		$this->assertEquals('encrypted', $actual);
	}

	/**
	 * @test
	 */
	public function findValueSanitized_optionMustBeSanitized_returnSanitizedValue()
	{
		$sut = $this->sut(array('findValue'));

		$meta = $this->optionProvider->get(Adi_Configuration_Options::DOMAIN_CONTROLLERS);

		$sut->expects($this->once())
			->method('findValue')
			->with(999, Adi_Configuration_Options::DOMAIN_CONTROLLERS)
			->willReturn('  a@b.de  ');

		$this->sanitizer->expects($this->once())
			->method('sanitize')
			->with('  a@b.de  ', $meta[Multisite_Option_Attribute::SANITIZER], $meta)
			->willReturn('a@b.de');

		$actual = $sut->findSanitizedValue(999, Adi_Configuration_Options::DOMAIN_CONTROLLERS);
		$this->assertEquals('a@b.de', $actual);
	}

	/**
	 * @test
	 */
	public function findValueSanitized_optionMustBeDecryptedAndSanitize_returnValue()
	{
		$sut = $this->sut(array('findValue'));
		$meta =  $this->optionProvider->get(Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD);

		$sut->expects($this->once())
			->method('findValue')
			->with(999, Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD)
			->willReturn('encrypted');

		$this->encryptionHandler->expects($this->once())
			->method('decrypt')
			->with('encrypted')
			->willReturn('  a@b.de  ');

		$this->sanitizer->expects($this->once())
			->method('sanitize')
			->with('  a@b.de  ',  $meta[Multisite_Option_Attribute::SANITIZER], $meta)
			->willReturn('a@b.de');

		$actual = $sut->findSanitizedValue(999, Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD);
		$this->assertEquals('a@b.de', $actual);
	}

	/**
	 * @test
	 */
	public function findValue_delegateToWordPressMethod_returnOptionValue() {
		$sut = $this->sut(array('createUniqueOptionName'));

		$sut->expects($this->once())
			->method('createUniqueOptionName')
			->with(true, 66, 'port')
			->willReturn('adi2_po_v_port');

		\WP_Mock::wpFunction('get_site_option',array(
			// the default value for option 'port' is always 389
			'args' => array('adi2_po_v_port', '389'),
			'times' => 1,
			'return' => '389'
		));

		$actual = $this->invokeMethod($sut,'findValue', array(66, 'port'));
		$this->assertEquals('389', $actual);
	}

	/**
	 * @test
	 */
	public function persistValueSanitized_optionMustBeSanitized_persistValue()
	{
		$sut = $this->sut(array('persistValue'));

		$meta = $this->optionProvider->get(Adi_Configuration_Options::DOMAIN_CONTROLLERS);

		$this->sanitizer->expects($this->once())
			->method('sanitize')
			->with('8078', $meta[Multisite_Option_Attribute::SANITIZER], $meta)
			->willReturn('sanitized');

		$sut->expects($this->once())
			->method('persistValue')
			->with(87, Adi_Configuration_Options::DOMAIN_CONTROLLERS, 'sanitized')
			->willReturn(true);

		$actual = $sut->persistSanitizedValue(87, Adi_Configuration_Options::DOMAIN_CONTROLLERS, '8078');
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function persistSanitized_optionMustBeEncrypted_persistValue()
	{
		$sut = $this->sut(array('persistValue'));

		$this->sanitizer->expects($this->once())
			->method('sanitize')
			->with('8078')
			->willReturn('8078');

		$this->encryptionHandler->expects($this->once())
			->method('encrypt')
			->with('8078')
			->willReturn('encrypted');

		$sut->expects($this->once())
			->method('persistValue')
			->with(87, Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD, 'encrypted')
			->willReturn(true);

		$actual = $sut->persistSanitizedValue(87, Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD, '8078');
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function persistSanitized_optionMustBeSanitizedAndEncrypted_persistValue()
	{
		$sut = $this->sut(array('persistValue'));

		$meta =  $this->optionProvider->get(Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD);

		$this->sanitizer->expects($this->once())
			->method('sanitize')
			->with('8078', $meta[Multisite_Option_Attribute::SANITIZER], $meta)
			->willReturn('sanitized');

		$this->encryptionHandler->expects($this->once())
			->method('encrypt')
			->with('sanitized')
			->willReturn('encrypted');

		$sut->expects($this->once())
			->method('persistValue')
			->with(87, Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD, 'encrypted')
			->willReturn(true);

		$actual = $sut->persistSanitizedValue(87, Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD, '8078');
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function persistValue_delegateToWordPressFunction_returnOptionValue() {
		$sut = $this->sut(array('createUniqueOptionName'));

		$sut->expects($this->once())
			->method('createUniqueOptionName')
			->with(true, 66, 'port')
			->willReturn('adi2_po_v_port');

		\WP_Mock::wpFunction('update_site_option',array(
			'args' => array('adi2_po_v_port', '389'),
			'times' => 1,
			'return' => true
		));

		$actual = $this->invokeMethod($sut,'persistValue', array(66, 'port', '389'));
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function deleteValue_delegateToWordPressFunction_deleteOptionValue() {
		$sut = $this->sut(array('createUniqueOptionName'));

		$sut->expects($this->once())
			->method('createUniqueOptionName')
			->with(true, 66, 'port')
			->willReturn('adi2_po_v_port');

		\WP_Mock::wpFunction('delete_site_option',array(
			'args' => array('adi2_po_v_port'),
			'times' => 1,
			'return' => '389'
		));

		$actual = $this->invokeMethod($sut,'deleteValue', array(66, 'port'));
		$this->assertEquals('389', $actual);
	}

	/**
	 * @test
	 */
	public function findPermissionSanitized_validPermission_returnOptionPermission() {
		$sut = $this->sut(array('findPermission'));

		$sut->expects($this->once())
			->method('findPermission')
			->with(66, 'port')
			->willReturn(2);

		$actual = $this->invokeMethod($sut,'findSanitizedPermission', array(66, 'port'));
		$this->assertEquals(2, $actual);
	}

	/**
	 * @test
	 */
	public function findPermissionSanitized_invalidPermission_returnDefaultPermission() {
		$sut = $this->sut(array('findPermission'));

		$sut->expects($this->once())
			->method('findPermission')
			->with(66, 'port')
			->willReturn('aaa');

		$actual = $this->invokeMethod($sut,'findSanitizedPermission', array(66, 'port'));
		$this->assertEquals(3, $actual);
	}

	/**
	 * @test
	 */
	public function findPermission_delegateToWordPressFunction_returnOptionPermission() {
		$sut = $this->sut(array('createUniqueOptionName'));

		$sut->expects($this->once())
			->method('createUniqueOptionName')
			->with(false, 66, 'port')
			->willReturn('adi2_po_p_port');

		\WP_Mock::wpFunction('get_site_option',array(
			'args' => array('adi2_po_p_port', 3),
			'times' => 1,
			'return' => '389'
		));

		$actual = $this->invokeMethod($sut,'findPermission', array(66, 'port'));
		$this->assertEquals('389', $actual);
	}

	/**
	 * @test
	 */
	public function persistPermissionSanitized_validPermission_persistOptionPermission() {
		$sut = $this->sut(array('persistPermission'));

		$sut->expects($this->once())
			->method('persistPermission')
			->with(66, 'port', 1)
			->willReturn(true);

		$actual = $sut->persistSanitizedPermission(66, 'port', 1);
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function persistPermissionSanitized_invalidPermission_doNothing() {
		$sut = $this->sut(array('persistPermission'));

		$sut->expects($this->never())
			->method('persistPermission');

		$actual = $sut->persistSanitizedPermission(66, 'port', 'a');
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function persistPermission_delegateToWordPressFunction_persistOptionPermission() {
		$sut = $this->sut(array('createUniqueOptionName'));

		$sut->expects($this->once())
			->method('createUniqueOptionName')
			->with(false, 66, 'port')
			->willReturn('adi2_po_p_port');

		\WP_Mock::wpFunction('update_site_option',array(
			'args' => array('adi2_po_p_port', 2),
			'times' => 1,
			'return' => true
		));

		$actual = $this->invokeMethod($sut,'persistPermission', array(66, 'port', 2));
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function deletePermission_delegateToWordPressFunction_deleteOptionPermission() {
		$sut = $this->sut(array('createUniqueOptionName'));

		$sut->expects($this->once())
			->method('createUniqueOptionName')
			->with(false, 66, 'port')
			->willReturn('adi2_po_p_port');

		\WP_Mock::wpFunction('delete_site_option',array(
			'args' => array('adi2_po_p_port'),
			'times' => 1,
			'return' => true
		));

		$actual = $this->invokeMethod($sut,'deletePermission', array(66, 'port'));
		$this->assertEquals(true, $actual);
	}
}