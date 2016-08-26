<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_Adi_Synchronization_Ui_SyncToWordPressTest extends Ut_BasicTest
{
	/* @var Multisite_View_TwigContainer|PHPUnit_Framework_MockObject_MockObject */
	private $twigContainer;

	/* @var Adi_Synchronization_WordPress |PHPUnit_Framework_MockObject_MockObject */
	private $syncToWordPress;

	/* @var Multisite_Configuration_Service | PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	public function setUp()
	{
		parent::setUp();

		$this->twigContainer = $this->createMock('Multisite_View_TwigContainer');
		$this->configuration = $this->createMock('Multisite_Configuration_Service');
		$this->syncToWordPress = $this->createMock('Adi_Synchronization_WordPress');
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 *
	 * @return Adi_Synchronization_Ui_SyncToWordPressPage| PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('Adi_Synchronization_Ui_SyncToWordPressPage')
			->setConstructorArgs(
				array(
					$this->twigContainer,
					$this->syncToWordPress,
					$this->configuration
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function getTitle()
	{
		$sut = $this->sut(null);

		$returnedTitle = $sut->getTitle();
		$this->assertEquals(Adi_Synchronization_Ui_SyncToWordPressPage::TITLE, $returnedTitle);
	}

	/**
	 * @test
	 */
	public function getSlug()
	{
		$sut = $this->sut(null);

		$returnedValue = $sut->getSlug();
		$this->assertEquals(NEXT_AD_INT_PREFIX . Adi_Synchronization_Ui_SyncToWordPressPage::SLUG, $returnedValue);
	}


	/**
	 * @test
	 */
	public function renderAdmin_validCapability_delegateToMethod()
	{
		$sut = $this->sut(array('checkCapability', 'processData', 'display'));

		$paramsFilled = array(
			'nonce'    => Adi_Synchronization_Ui_SyncToWordPressPage::NONCE, //add nonce for security
			'authCode' => 'someAuthCode',
			'blogUrl'  => 'www.testsite.it',
			'message' => null,
			'log' => null,
		);

		$sut->expects($this->once())
			->method('processData')
			->willReturn(array());

		WP_Mock::wpFunction('wp_create_nonce', array(
			'args'   => Adi_Synchronization_Ui_SyncToWordPressPage::NONCE,
			'times'  => 1,
			'return' => Adi_Synchronization_Ui_SyncToWordPressPage::NONCE)
		);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::SYNC_TO_WORDPRESS_AUTHCODE)
			->willReturn('someAuthCode');

		WP_Mock::wpFunction('get_current_blog_id', array(
			'times'  => 1,
			'return' => 1,)
		);

		WP_Mock::wpFunction('get_site_url', array(
			'args'   => 1,
			'times'  => 1,
			'return' => 'www.testsite.it',)
		);

		$sut->expects($this->once())
			->method('display')
			->with(Adi_Synchronization_Ui_SyncToWordPressPage::TEMPLATE, $paramsFilled);

		$sut->renderAdmin();
	}

	/**
	 * @test
	 */
	public function loadJavaScriptAdmin_validHook_enqueueScript()
	{
		$sut = $this->sut(null);
		$hook = NEXT_AD_INT_PREFIX . Adi_Synchronization_Ui_SyncToWordPressPage::SLUG;

		WP_Mock::wpFunction(
			'wp_enqueue_style', array(
				'args'  => array('adi2', NEXT_AD_INT_URL . '/css/adi2.css', array(), Multisite_Ui::VERSION_CSS),
				'times' => 1,
			)
		);

		$sut->loadAdminScriptsAndStyle($hook);
	}

	/**
	 * @test
	 */
	public function loadJavaScriptAdmin_invalidHook_doNothing()
	{
		$sut = $this->sut(null);
		$hook = NEXT_AD_INT_PREFIX . 'some_stuff';

		WP_Mock::wpFunction('wp_enqueue_style', array(
			'times' => 0)
		);

		$sut->loadAdminScriptsAndStyle($hook);
	}

	/**
	 * @test
	 */
	public function processData_invalidPost_returnEmptyArray()
	{
		$sut = $this->sut(null);

		$post = array();

		$actual = $sut->processData($post);
		$this->assertEquals(array(), $actual);
	}

	/**
	 * @test
	 */
	public function processData_invalidNonce_callWpDie()
	{
		$sut = $this->sut(null);

		$post = array(
			'syncToWordpress' => '',
			'security' => 'invalid'
		);

		WP_Mock::wpFunction('wp_verify_nonce', array(
			'args'   => array($post['security'], Adi_Synchronization_Ui_SyncToWordPressPage::NONCE),
			'times'  => 1,
			'return' => false)
		);

		WP_Mock::wpFunction('wp_die', array(
			'times'  => 1)
		);

		$sut->processData($post);
	}

	/**
	 * @test
	 */
	public function processData_validNonce_returnResult()
	{
		$sut = $this->sut(null);

		$post = array(
			'syncToWordpress' => '',
			'security' => 'valid'
		);

		WP_Mock::wpFunction('wp_verify_nonce', array(
			'args'   => array($post['security'], Adi_Synchronization_Ui_SyncToWordPressPage::NONCE),
			'times'  => 1,
			'return' => true)
		);

		WP_Mock::wpFunction('wp_die', array(
			'times' => 0,)
		);

		$this->syncToWordPress->expects($this->once())
			->method('synchronize')
			->willReturn('bulkData');

		$expected = array(
			'status' => 'bulkData'
		);

		$actual = $sut->processData($post);
		$this->assertTrue(is_array($actual));
		$this->assertEquals($expected, $actual);

	}

	/**
	 * @test
	 */
	public function wpAjaxSlug_getAjaxSlug_returnAjaxSlug() {
		$sut = $this->sut(null);

		$returnedTitle = $sut->wpAjaxSlug();
		$this->assertEquals(Adi_Synchronization_Ui_SyncToWordPressPage::AJAX_SLUG, $returnedTitle);
	}
}