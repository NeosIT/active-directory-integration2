<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_Ui_ConnectivityTestPageTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_View_TwigContainer|PHPUnit_Framework_MockObject_MockObject */
	private $twigContainer;

	/* @var NextADInt_Multisite_Configuration_Service|PHPUnit_Framework_MockObject_MockObject $configuration */
	private $configuration;

	/** @var NextADInt_Ldap_Connection|PHPUnit_Framework_MockObject_MockObject $ldapConnection */
	private $ldapConnection;

	/* @var NextADInt_Ldap_Attribute_Service|PHPUnit_Framework_MockObject_MockObject $attributes */
	private $attributeService;

	/** @var NextADInt_Adi_User_Manager|PHPUnit_Framework_MockObject_MockObject */
	private $userManager;

	/** @var NextADInt_Adi_Role_Manager|PHPUnit_Framework_MockObject_MockObject $roleManager */
	private $roleManager;

	public function setUp()
	{
		parent::setUp();

		$this->twigContainer = $this->createMock('NextADInt_Multisite_View_TwigContainer');
		$this->configuration = $this->createMock('NextADInt_Multisite_Configuration_Service');
		$this->ldapConnection = $this->createMock('NextADInt_Ldap_Connection');
		$this->attributeService = $this->createMock('NextADInt_Ldap_Attribute_Service');
		$this->userManager = $this->createMock('NextADInt_Adi_User_Manager');
		$this->roleManager = $this->createMock('NextADInt_Adi_Role_Manager');
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
	 * @return NextADInt_Adi_Ui_ConnectivityTestPage| PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Adi_Ui_ConnectivityTestPage')
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

		$expectedReturn = NEXT_AD_INT_PREFIX . 'test_connection';
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
		$hook = NEXT_AD_INT_PREFIX . 'test_connection';

		WP_Mock::wpFunction(
			'wp_enqueue_style', array(
				'args'  => array('next_ad_int', NEXT_AD_INT_URL . '/css/next_ad_int.css', array(), NextADInt_Multisite_Ui::VERSION_CSS),
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
        $collectInformationResult = array('output' => 'Test', 'authentication_result' => 666);

        $_POST['username'] = 'test\\\\User'; // should be addslashes('test\User');
        $_POST['password'] = "secret's";
        $_POST['security'] = 'base64';

        $expectedUsername = 'test\User';
        $expectedPassword = "secret's";

        WP_Mock::wpFunction(
            'wp_verify_nonce', array(
                'args'   => array($_POST['security'], 'Active Directory Integration Test Authentication Nonce'),
                'times'  => 1,
                'return' => true,
            )
        );

        $sut->expects($this->once())
            ->method('collectInformation')
            ->with($expectedUsername, $expectedPassword)
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
		$supportString = 'Support for: ###123###http://example.com###ExampleBlogName###';
		
		$supportData = array(
			$supportString,
			'Support Hash: ' . hash('sha256', $supportString)
		);

		$sut = $this->sut(array('connectToActiveDirectory', 'detectSystemEnvironment', 'detectSupportData'));

		$sut->expects($this->once())
			->method('detectSupportData')
			->willReturn($supportData);
		
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
			->with(NextADInt_Adi_Configuration_Options::SUPPORT_LICENSE_KEY, 1)
			->willReturn('123');

		WP_Mock::wpFunction('get_current_blog_id', array(
			'times' => 1,
			'return' => 1
		));
		
		WP_Mock::wpFunction('get_site_url', array(
			'times' => 1,
			'return' => 'http://example.com'
		));

		WP_Mock::wpFunction('get_bloginfo', array(
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
			->with(NextADInt_Adi_Configuration_Options::SUPPORT_LICENSE_KEY, 1)
			->willReturn('');

		WP_Mock::wpFunction('get_current_blog_id', array(
			'times' => 1,
			'return' => 1
		));

		WP_Mock::wpFunction('get_site_url', array(
			'times' => 1,
			'return' => 'http://example.com'
		));

		WP_Mock::wpFunction('get_bloginfo', array(
			'args' => 'name',
			'times' => 1,
			'return' => 'ExampleBlogName'
		));

		$actual = $sut->detectSupportData();

		$this->assertEquals($expected, $actual);
	}
}