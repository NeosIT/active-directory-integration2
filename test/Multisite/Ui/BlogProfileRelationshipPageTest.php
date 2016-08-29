<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Multisite_Ui_BlogProfileRelationshipPageTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_View_TwigContainer|PHPUnit_Framework_MockObject_MockObject */
	private $twigContainer;

	/* @var NextADInt_Multisite_Ui_BlogProfileRelationshipController| PHPUnit_Framework_MockObject_MockObject */
	private $blogProfileRelationshipController;

	public function setUp()
	{
		parent::setUp();

		$this->twigContainer = $this->createMock('NextADInt_Multisite_View_TwigContainer');
		$this->blogProfileRelationshipController = $this->createMock('NextADInt_Multisite_Ui_BlogProfileRelationshipController');
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function getTitle()
	{
		$sut = $this->sut(null);

		$expectedTitle = 'Profile assignment';
		$returnedTitle = $sut->getTitle();

		$this->assertEquals($expectedTitle, $returnedTitle);
	}

	/**
	 *
	 * @return NextADInt_Multisite_Ui_BlogProfileRelationshipPage| PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Multisite_Ui_BlogProfileRelationshipPage')
			->setConstructorArgs(
				array(
					$this->twigContainer,
					$this->blogProfileRelationshipController,
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function getSlug()
	{
		$sut = $this->sut(null);

		$expectedReturn = NEXT_AD_INT_PREFIX . 'blog_profile_relationship';
		$returnedValue = $sut->getSlug();

		$this->assertEquals($expectedReturn, $returnedValue);
	}

	/**
	 * @test
	 */
	public function wpAjaxSlug()
	{
		$sut = $this->sut(null);

		$expectedReturn = NEXT_AD_INT_PREFIX . 'blog_profile_relationship';
		$returnedValue = $sut->wpAjaxSlug();

		$this->assertEquals($expectedReturn, $returnedValue);
	}

	/**
	 * @test
	 */
	public function renderNetwork()
	{
		$sut = $this->sut(array('display'));

		$nonce = 'some_nonce';
		$this->createMock('WP_MS_Sites_List_Table');
		$table = $this->createMock('NextADInt_Multisite_Ui_Table_ProfileAssignment');

		WP_Mock::wpFunction('wp_create_nonce', array(
			'args'   => NextADInt_Multisite_Ui_BlogProfileRelationshipPage::NONCE,
			'times'  => 1,
			'return' => $nonce,
		));

		$this->blogProfileRelationshipController->expects($this->once())
			->method('buildSiteTable')
			->willReturn($table);

		$sut->expects($this->once())
			->method('display')
			->with(NextADInt_Multisite_Ui_BlogProfileRelationshipPage::TEMPLATE, array('nonce' => $nonce, 'table' => $table));

		$sut->renderNetwork();
	}

	/**
	 * @test
	 */
	public function loadJavaScriptAdmin()
	{
		$sut = $this->sut(null);
		$hook = NEXT_AD_INT_PREFIX . 'blog_profile_relationship';

		WP_Mock::wpFunction(
			'wp_enqueue_script', array(
				'args'  => array(
					'adi2_blog_profile_association',
					NEXT_AD_INT_URL . '/js/blog-profile-relationship.js',
					array('jquery'),
					NextADInt_Multisite_Ui_BlogProfileRelationshipPage::VERSION_BLOG_PROFILE_RELATIONSHIP_JS,
				),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_enqueue_style', array(
				'args'  => array('adi2', NEXT_AD_INT_URL . '/css/adi2.css', array(), NextADInt_Multisite_Ui::VERSION_CSS),
				'times' => 1,
			)
		);

		$sut->loadNetworkScriptsAndStyle($hook);
	}

	/**
	 * @test
	 */
	public function wpAjaxListener()
	{
		$sut = $this->sut(array('saveBlogProfileAssociations', 'saveDefaultProfile'));

		$_POST['data'] = array(
			'profile'  => 1,
			'allblogs' => array(1, 2),
		);

		WP_Mock::wpFunction(
			'check_ajax_referer', array(
				'args'  => array('Active Directory Integration Profile Assignment Nonce', 'security', true),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'current_user_can', array(
				'args'   => 'manage_network',
				'times'  => 1,
				'return' => true,
			)
		);

		$sut->expects($this->once())
			->method('saveBlogProfileAssociations')
			->with($_POST['data']);

		$sut->expects($this->once())
			->method('saveDefaultProfile')
			->with($_POST['data']);

		$sut->wpAjaxListener();
	}

	/**
	 * @test
	 */
	public function wpAjaxListener_noData()
	{
		$sut = $this->sut(null);

		$_POST['data'] = '';

		WP_Mock::wpFunction(
			'check_ajax_referer', array(
				'args'  => array('Active Directory Integration Profile Assignment Nonce', 'security', true),
				'times' => 1,
			)
		);

		$sut->wpAjaxListener();
	}

	/**
	 * @test
	 */
	public function wpAjaxListener_noPermission()
	{
		$sut = $this->sut(null);

		$_POST['data'] = 'someData';

		WP_Mock::wpFunction(
			'check_ajax_referer', array(
				'args'  => array('Active Directory Integration Profile Assignment Nonce', 'security', true),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'current_user_can', array(
				'args'   => 'manage_network',
				'times'  => 1,
				'return' => false,
			)
		);

		$sut->wpAjaxListener();
	}

	/**
	 * @test
	 */
	public function saveBlogProfileAssociations_noData_doesNotTriggerController()
	{
		$sut = $this->sut();

		$data = array();

		$this->blogProfileRelationshipController->expects($this->never())
			->method('saveBlogProfileAssociations');

		$this->invokeMethod($sut, 'saveBlogProfileAssociations', array($data));
	}

	/**
	 * @test
	 */
	public function saveBlogProfileAssociations_withData_doesTriggerController()
	{
		$sut = $this->sut();

		$data = array(
			'profile'  => 1,
			'allblogs' => array(1),
		);

		$this->blogProfileRelationshipController->expects($this->once())
			->method('saveBlogProfileAssociations')
			->with($data['profile'], $data['allblogs']);

		$this->invokeMethod($sut, 'saveBlogProfileAssociations', array($data));
	}

	/**
	 * @test
	 */
	public function saveDefaultProfile_noData_doesNotTriggerController()
	{
		$sut = $this->sut();

		$data = array();

		$this->blogProfileRelationshipController->expects($this->never())
			->method('saveDefaultProfile');

		$this->invokeMethod($sut, 'saveDefaultProfile', array($data));
	}

	/**
	 * @test
	 */
	public function saveDefaultProfile_withData_doesTriggerController()
	{
		$sut = $this->sut();

		$data = array(
			'default-profile'  => 1,
		);

		$this->blogProfileRelationshipController->expects($this->once())
			->method('saveDefaultProfile')
			->with($data['default-profile']);

		$this->invokeMethod($sut, 'saveDefaultProfile', array($data));
	}
}