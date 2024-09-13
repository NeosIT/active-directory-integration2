<?php

namespace Dreitier\WordPress\Multisite\Ui;

use Dreitier\Test\BasicTest;
use Dreitier\WordPress\Multisite\Ui;
use Dreitier\WordPress\Multisite\View\TwigContainer;
use Mockery\Mock;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class BlogProfileRelationshipPageTest extends BasicTest
{
	/* @var TwigContainer|MockObject */
	private $twigContainer;

	/* @var BlogProfileRelationshipController|MockObject */
	private $blogProfileRelationshipController;

	public function setUp(): void
	{
		parent::setUp();

		$this->twigContainer = $this->createMock(TwigContainer::class);
		$this->blogProfileRelationshipController = $this->createMock(BlogProfileRelationshipController::class);
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function getTitle()
	{
		$sut = $this->sut(null);
		$this->mockFunctionEsc_html__();

		$expectedTitle = 'Profile assignment';

		$returnedTitle = $sut->getTitle();
		$this->assertEquals($expectedTitle, $returnedTitle);
	}

	/**
	 *
	 * @return BlogProfileRelationshipPage| MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder(BlogProfileRelationshipPage::class)
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

		$expectedReturn =NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'blog_profile_relationship';
		$returnedValue = $sut->getSlug();

		$this->assertEquals($expectedReturn, $returnedValue);
	}

	/**
	 * @test
	 */
	public function wpAjaxSlug()
	{
		$sut = $this->sut(null);

		$expectedReturn =NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'blog_profile_relationship';
		$returnedValue = $sut->wpAjaxSlug();

		$this->assertEquals($expectedReturn, $returnedValue);
	}

	/**
	 * @test
	 */
	public function renderNetwork()
	{
		$sut = $this->sut(array('display'));
		$this->mockFunction__();

		$nonce = 'some_nonce';
		$this->createMock('WP_MS_Sites_List_Table');
		$table = $this->createMock(Ui\Table\ProfileAssignment::class);
		$i18n = array(
			'search' => 'Search',
			'title' => 'Profile assignment',
			'defaultProfile' => 'Default profile',
			'noneAssigned' => '--- None assigned',
			'apply' => 'Apply',
			'changeBlogs' => 'Change selected blogs to profile',
			'useDefaultProfile' => '--- Use default profile'
		);

		\WP_Mock::userFunction('wp_create_nonce', array(
			'args' => BlogProfileRelationshipPage::NONCE,
			'times' => 1,
			'return' => $nonce,
		));

		$this->blogProfileRelationshipController->expects($this->once())
			->method('buildSiteTable')
			->willReturn($table);

		$sut->expects($this->once())
			->method('display')
			->with(BlogProfileRelationshipPage::TEMPLATE, array('nonce' => $nonce, 'table' => $table, 'i18n' => $i18n));

		$sut->renderNetwork();
	}

	/**
	 * @test
	 */
	public function loadJavaScriptAdmin()
	{
		$sut = $this->sut(null);
		$hook =NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'blog_profile_relationship';

		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array(
					'next_ad_int_blog_profile_association',
					NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/blog-profile-relationship.js',
					array('jquery'),
					BlogProfileRelationshipPage::VERSION_BLOG_PROFILE_RELATIONSHIP_JS,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_style', array(
				'args' => array('next_ad_int',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/css/next_ad_int.css', array(), Ui::VERSION_CSS),
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
			'profile' => 1,
			'allblogs' => array(1, 2),
		);

		\WP_Mock::userFunction(
			'check_ajax_referer', array(
				'args' => array('Active Directory Integration Profile Assignment Nonce', 'security', true),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'current_user_can', array(
				'args' => 'manage_network',
				'times' => 1,
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

		\WP_Mock::userFunction(
			'check_ajax_referer', array(
				'args' => array('Active Directory Integration Profile Assignment Nonce', 'security', true),
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

		\WP_Mock::userFunction(
			'check_ajax_referer', array(
				'args' => array('Active Directory Integration Profile Assignment Nonce', 'security', true),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'current_user_can', array(
				'args' => 'manage_network',
				'times' => 1,
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
			'profile' => 1,
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
			'default-profile' => 1,
		);

		$this->blogProfileRelationshipController->expects($this->once())
			->method('saveDefaultProfile')
			->with($data['default-profile']);

		$this->invokeMethod($sut, 'saveDefaultProfile', array($data));
	}
}