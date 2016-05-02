<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_Adi_Ui_ConnectivityTestPageTest extends Ut_BasicTest
{
	/* @var Multisite_View_TwigContainer|PHPUnit_Framework_MockObject_MockObject */
	private $twigContainer;

	/* @var Multisite_Configuration_Service|PHPUnit_Framework_MockObject_MockObject $configuration */
	private $configuration;

	/** @var Ldap_Connection|PHPUnit_Framework_MockObject_MockObject $ldapConnection */
	private $ldapConnection;

	/* @var Ldap_Attribute_Service|PHPUnit_Framework_MockObject_MockObject $attributes */
	private $attributeService;

	/** @var Adi_User_Manager|PHPUnit_Framework_MockObject_MockObject */
	private $userManager;

	/** @var Adi_Role_Manager|PHPUnit_Framework_MockObject_MockObject $roleManager */
	private $roleManager;

	public function setUp()
	{
		parent::setUp();

		$this->twigContainer = $this->createMock('Multisite_View_TwigContainer');
		$this->configuration = $this->createMock('Multisite_Configuration_Service');
		$this->ldapConnection = $this->createMock('Ldap_Connection');
		$this->attributeService = $this->createMock('Ldap_Attribute_Service');
		$this->userManager = $this->createMock('Adi_User_Manager');
		$this->roleManager = $this->createMock('Adi_Role_Manager');
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

		$expectedTitle = 'Test authentication';
		$returnedTitle = $sut->getTitle();

		$this->assertEquals($expectedTitle, $returnedTitle);
	}

	/**
	 *
	 * @return Adi_Ui_ConnectivityTestPage| PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('Adi_Ui_ConnectivityTestPage')
			->setConstructorArgs(
				array(
					$this->twigContainer,
					$this->configuration,
					$this->ldapConnection,
					$this->attributeService,
					$this->userManager,
					$this->roleManager
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

		$expectedReturn = ADI_PREFIX . 'test_connection';
		$returnedValue = $sut->getSlug();

		$this->assertEquals($expectedReturn, $returnedValue);
	}

	/**
	 * @test
	 */
	public function renderAdmin()
	{
		$sut = $this->sut(array('render'));


		$params = array(
			'nonce'   => 'Active Directory Integration Test Authentication Nonce',
			'message' => null,
			'log'     => null,
		);

		WP_Mock::wpFunction(
			'current_user_can', array(
				'args'   => 'manage_options',
				'times'  => 1,
				'return' => false,
			)
		);

		WP_Mock::wpFunction(
			'wp_die', array(
				'args'  => array('You do not have sufficient permissions to access this page.'),
				'times' => 1,
			)
		);

		WP_Mock::wpFunction(
			'wp_create_nonce', array(
				'args'   => 'Active Directory Integration Test Authentication Nonce',
				'times'  => 1,
				'return' => 'Active Directory Integration Test Authentication Nonce',
			)
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
		$hook = ADI_PREFIX . 'test_connection';

		WP_Mock::wpFunction(
			'wp_enqueue_style', array(
				'args'  => array('adi2', ADI_URL . '/css/adi2.css', array(), Multisite_Ui::VERSION_CSS),
				'times' => 1,
			)
		);

		$sut->loadAdminScriptsAndStyle($hook);
	}

	/**
	 * @test
	 */
	public function processData()
	{
		$sut = $this->sut(array('printSystemEnvironment', 'connectToActiveDirectory', 'collectInformation'));
		$collectInformationResult = array('output' => 'Test', 'authentication_result' => 666);

		$_POST['username'] = 'testUser';
		$_POST['password'] = 1234;
		$_POST['security'] = 'someValue';

		WP_Mock::wpFunction(
			'wp_verify_nonce', array(
				'args'   => array($_POST['security'], 'Active Directory Integration Test Authentication Nonce'),
				'times'  => 1,
				'return' => true,
			)
		);

		$sut->expects($this->once())
			->method('collectInformation')
			->with($_POST['username'], $_POST['password'])
			->willReturn($collectInformationResult);


		$actual = $sut->processData();

		$this->assertTrue(is_array($actual));
		$output = $sut->getOutput();
		// first line has to be the output
		$this->assertTrue(strpos($output[0], $collectInformationResult['output']) !== false);
		$this->assertEquals($collectInformationResult['authentication_result'], $actual['status']);

	}

	/**
	 * @test
	 */
	public function collectInformation_itTriesToConnectToActiveDirectory()
	{
		$username = "username";
		$password = "password";

		$sut = $this->sut(array('connectToActiveDirectory', 'detectSystemEnvironment'));

		$sut->expects($this->once())
			->method('detectSystemEnvironment')
			->willReturn(array(array('PHP', '5.5')));

		$sut->expects($this->once())
			->method('connectToActiveDirectory')
			->with($username, $password)
			->willReturn(666);

		$actual = $sut->collectInformation($username, $password);

		$this->assertEquals('', $actual['output']);
		$this->assertEquals(666, $actual['authentication_result']);
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
}