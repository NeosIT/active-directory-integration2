<?php

namespace Dreitier\Nadi\Ui;

use Dreitier\Ldap\Connection;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\User\LoginSucceededService;
use Dreitier\Nadi\User\Manager;
use Dreitier\Test\BasicTest;
use Dreitier\WordPress\Multisite\Configuration\Service;
use Dreitier\WordPress\Multisite\Ui;
use Dreitier\WordPress\Multisite\View\TwigContainer;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class ConnectivityTestPageTest extends BasicTest
{
	/* @var TwigContainer|MockObject */
	private $twigContainer;

	/* @var Service|MockObject $configuration */
	private $configuration;

	/** @var Connection|MockObject $ldapConnection */
	private $ldapConnection;

	/* @var \Dreitier\Ldap\Attribute\Service|MockObject $attributes */
	private $attributeService;

	/** @var Manager|MockObject */
	private $userManager;

	/** @var \Dreitier\Nadi\Role\Manager|MockObject $roleManager */
	private $roleManager;

	/** @var LoginSucceededService */
	private $loginSucceededService;

	public function setUp(): void
	{
		parent::setUp();

		$this->twigContainer = $this->createMock(TwigContainer::class);
		$this->configuration = $this->createMock(Service::class);
		$this->ldapConnection = $this->createMock(Connection::class);
		$this->attributeService = $this->createMock(\Dreitier\Ldap\Attribute\Service::class);
		$this->userManager = $this->createMock(Manager::class);
		$this->roleManager = $this->createMock(\Dreitier\Nadi\Role\Manager::class);
		$this->loginSucceededService = $this->createMock(LoginSucceededService::class);

		$this->mockFunctionEsc_html__();

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

		$expectedTitle = 'Test authentication';

		$returnedTitle = $sut->getTitle();
		$this->assertEquals($expectedTitle, $returnedTitle);
	}

	/**
	 *
	 * @return ConnectivityTestPage| MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder(ConnectivityTestPage::class)
			->setConstructorArgs(
				array(
					$this->twigContainer,
					$this->configuration,
					$this->ldapConnection,
					$this->attributeService,
					$this->userManager,
					$this->roleManager,
					$this->loginSucceededService
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

		$expectedReturn =NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'test_connection';
		$returnedValue = $sut->getSlug();

		$this->assertEquals($expectedReturn, $returnedValue);
	}

	/**
	 * @test
	 */
	public function renderAdmin()
	{
		$sut = $this->sut(array('render'));
		$this->mockFunction__();
		$this->mockFunctionEsc_html__();

		$params = array(
			'nonce' => 'Active Directory Integration Test Authentication Nonce',
			'message' => null,
			'log' => null,
			'i18n' => array(
				'title' => 'Test Active Directory authentication',
				'descriptionLine1' => 'Please enter the username and password for the account you want to authenticate with. After submitting the request you will get the debug output.',
				'descriptionLine2' => 'For this page feature of blocking user accounts with failed login attempts is disabled. You do not have to worry about locking an account.',
				'descriptionLine3' => 'Please note that the entered password <strong>is not masked</strong>.',
				'username' => 'Username:',
				'password' => 'Password (will be shown):',
				'tryAgain' => 'Try to authenticate again',
				'tryAuthenticate' => 'Try to authenticate',
				'showLogOutput' => 'Show log output',
			)
		);

		\WP_Mock::userFunction('current_user_can', array(
				'args' => 'manage_options',
				'times' => 1,
				'return' => false)
		);

		\WP_Mock::userFunction('wp_die', array(
				'args' => array('You do not have sufficient permissions to access this page.'),
				'times' => 1)
		);

		\WP_Mock::userFunction('wp_create_nonce', array(
				'args' => 'Active Directory Integration Test Authentication Nonce',
				'times' => 1,
				'return' => 'Active Directory Integration Test Authentication Nonce',)
		);

		$sut->expects($this->once())
			->method('render')
			->with('test-connection.twig', $params);

		$sut->renderAdmin();
	}

	/**
	 * @test
	 */
	public function loadJavaScriptAdmin()
	{
		$sut = $this->sut(null);
		$hook =NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'test_connection';

		\WP_Mock::userFunction(
			'wp_enqueue_style', array(
				'args' => array('next_ad_int',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/css/next_ad_int.css', array(), Ui::VERSION_CSS),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_style', array(
				'args' => array('next_ad_int_bootstrap_min_css',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/css/bootstrap.min.css', array(), Ui::VERSION_CSS),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script', array(
				'args' => array('next_ad_int_bootstrap_min_js',NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL . '/js/libraries/bootstrap.min.js', array(), Ui::VERSION_PAGE_JS),
				'times' => 1,
			)
		);

		$sut->loadAdminScriptsAndStyle($hook);
	}

	/**
	 * @test
	 */
	public function processData_withEscapedCharacter_unescapeThem()
	{
		$sut = $this->sut(array('printSystemEnvironment', 'connectToActiveDirectory', 'collectInformation'));
		$collectInformationResult = array('output' => 'Test', 'authentication_result' => new \WP_User());

		$_POST['username'] = 'test\User'; // should be addslashes('test\User');
		$_POST['password'] = "secret's";
		$_POST['security'] = 'base64';

		$expectedUsername = 'test\User';
		$expectedPassword = "secret's";

		\WP_Mock::userFunction(
			'wp_verify_nonce', array(
				'args' => array($_POST['security'], 'Active Directory Integration Test Authentication Nonce'),
				'times' => 1,
				'return' => true,
			)
		);

		$sut->expects($this->once())
			->method('collectInformation')
			->with($expectedUsername, $expectedPassword)
			->willReturn($collectInformationResult);


		$actual = $sut->processData();

		$this->assertTrue(is_array($actual));
		$this->assertTrue($actual['status']);
	}

	/**
	 * @test
	 */
	public function processData_withValidCredentials_returnsTrue()
	{
		$sut = $this->sut(array('printSystemEnvironment', 'connectToActiveDirectory', 'collectInformation'));
		$collectInformationResult = array('output' => 'Test', 'authentication_result' => true);

		$_POST['username'] = 'john.doe';
		$_POST['password'] = 'secret';
		$_POST['security'] = 'someValue';

		\WP_Mock::userFunction(
			'wp_verify_nonce', array(
				'args' => array($_POST['security'], 'Active Directory Integration Test Authentication Nonce'),
				'times' => 1,
				'return' => true,
			)
		);

		$sut->expects($this->once())
			->method('collectInformation')
			->with($_POST['username'], $_POST['password'])
			->willReturn($collectInformationResult);


		$actual = $sut->processData();

		$this->assertTrue(is_array($actual));
		$this->assertFalse($actual['status']);
	}

	/**
	 * @test
	 */
	public function processData_withMissingOrInvalidNonce_dies()
	{
		$sut = $this->sut(array('printSystemEnvironment', 'connectToActiveDirectory', 'collectInformation'));

		$_POST['username'] = 'john.doe';
		$_POST['password'] = 'secret';
		$_POST['security'] = 'someIncorrectValue';

		\WP_Mock::userFunction(
			'wp_verify_nonce', array(
				'args' => array($_POST['security'], 'Active Directory Integration Test Authentication Nonce'),
				'times' => 1,
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'wp_die', array(
				'args' => 'You do not have sufficient permissions.',
				'times' => 1
			)
		);

		$sut->processData();
	}

	/**
	 * @test
	 */
	public function collectInformation_performsSystemDetection_returnsExpectedArray()
	{
		$username = 'john.doe';
		$password = 'secret';
		$expected = new \WP_User();
		$supportString = 'Support for: ###123###http://example.com###ExampleBlogName###';
		$environment = array(array('PHP', '5.5'));

		$supportData = array(
			$supportString,
			'Support Hash: ' . hash('sha256', $supportString)
		);

		$sut = $this->sut(array('detectSupportData', 'detectSystemEnvironment', 'authenticateAndAuthorize'));

		$sut->expects($this->once())->method('detectSupportData')->willReturn($supportData);
		$sut->expects($this->once())->method('detectSystemEnvironment')->willReturn($environment);
		$sut->expects($this->once())->method('authenticateAndAuthorize')->willReturn($expected);

		$actual = $sut->collectInformation($username, $password);

		$this->assertNotEmpty($actual);
		$this->assertEquals($actual['authentication_result'], $expected);
	}

	/**
	 * @test
	 */
	public function detectSystemEnvironment_hasRelevantInformation()
	{
		$sut = $this->sut();

		$actual = $sut->detectSystemEnvironment();

		$this->assertEquals('PHP', $actual[0][0]);
		$this->assertEquals('WordPress', $actual[1][0]);
		$this->assertEquals('Active Directory Integration', $actual[2][0]);
		$this->assertEquals('Operating System', $actual[3][0]);
		$this->assertEquals('Web Server', $actual[4][0]);
		$this->assertEquals('adLDAP', $actual[5][0]);
	}

	/**
	 * @test
	 */
	public function processData_returnEmptyArray()
	{
		$sut = $this->sut(null);

		$returnedCache = $sut->processData();

		$this->assertTrue(is_array($returnedCache));
		$this->assertTrue(empty($returnedCache));

	}

	/**
	 * @test
	 */
	public function detectSupportData_withLicense_returnArray()
	{
		$sut = $this->sut();

		$supportString = 'Support for: ###123###http://example.com###ExampleBlogName###';

		$expected = array(
			$supportString,
			'Support Hash: ' . hash('sha256', $supportString)
		);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::SUPPORT_LICENSE_KEY, 1)
			->willReturn('123');

		\WP_Mock::userFunction('get_current_blog_id', array(
			'times' => 1,
			'return' => 1
		));

		\WP_Mock::userFunction('get_site_url', array(
			'times' => 1,
			'return' => 'http://example.com'
		));

		\WP_Mock::userFunction('get_bloginfo', array(
			'args' => 'name',
			'times' => 1,
			'return' => 'ExampleBlogName'
		));

		$actual = $sut->detectSupportData();

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function detectSupportData_withoutLicense_returnArray()
	{
		$sut = $this->sut();

		$supportString = 'Support for: ###unlicensed###http://example.com###ExampleBlogName###';

		$expected = array(
			$supportString,
			'Support Hash: ' . hash('sha256', $supportString)
		);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::SUPPORT_LICENSE_KEY, 1)
			->willReturn('');

		\WP_Mock::userFunction('get_current_blog_id', array(
			'times' => 1,
			'return' => 1
		));

		\WP_Mock::userFunction('get_site_url', array(
			'times' => 1,
			'return' => 'http://example.com'
		));

		\WP_Mock::userFunction('get_bloginfo', array(
			'args' => 'name',
			'times' => 1,
			'return' => 'ExampleBlogName'
		));

		$actual = $sut->detectSupportData();

		$this->assertEquals($expected, $actual);
	}
}