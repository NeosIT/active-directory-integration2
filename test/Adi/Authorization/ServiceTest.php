<?php

/**
 * @author Christopher Klein <ckl@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_Authorization_ServiceTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_Configuration_Service| PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/* @var NextADInt_Adi_User_Manager| PHPUnit_Framework_MockObject_MockObject */
	private $userManager;

    /* @var NextADInt_Adi_Role_Manager| PHPUnit_Framework_MockObject_MockObject */
    private $roleManager;

    /** @var NextADInt_Adi_LoginState */
    private $loginState;

	public function setUp()
	{
		parent::setUp();

		$this->configuration = $this->createMock('NextADInt_Multisite_Configuration_Service');
		$this->userManager = $this->createMock('NextADInt_Adi_User_Manager');
        $this->roleManager = $this->createMock('NextADInt_Adi_Role_Manager');
        $this->loginState = new NextADInt_Adi_LoginState();
	}

    /**
     * @param null $methods
     * @param bool $simulated
     * @return PHPUnit_Framework_MockObject_MockObject
     */
	public function sut($methods = null, $simulated = false) {
        return $this->getMockBuilder('NextADInt_Adi_Authorization_Service')
            ->setConstructorArgs(
                array(
                    $this->configuration,
                    $this->userManager,
                    $this->roleManager,
                    $this->loginState)
            )->setMethods($methods)
            ->getMock();

    }

	public function tearDown()
	{
		parent::tearDown();
	}

    /**
     * @test
     */
    public function authorizationIsNever_ifUserHasNotBeenPreviouslyAuthenticated() {
        $r = $this->sut()->checkAuthorizationRequired(null);

        $this->assertFalse($r);
    }

    /**
     * @test
     */
    public function authorizationIsNeverRequiredForAdmins() {
        $wpAdmin = new WP_User();
        $wpAdmin->setId(1);

        $r = $this->sut()->checkAuthorizationRequired($wpAdmin);

        $this->assertFalse($r);
    }

    /**
     * @test
     */
    public function isUserEnabled_returnsError_ifUserIsDisabled()
    {
        $sut = $this->sut(array('checkAuthorizationRequired'));
        $wpUser = new WP_User();
        $wpUser->setId(2);

        $sut->expects($this->once())
            ->method('checkAuthorizationRequired')
            ->with($wpUser)
            ->willReturn($wpUser->ID);

        $this->userManager->expects($this->once())
            ->method('isDisabled')
            ->with($wpUser->ID)
            ->willReturn(true);

        wp_mock::userFunction('get_user_meta', array(
            'times' => 1,
            'args' => array($wpUser->ID, 'next_ad_int_user_disabled_reason', true),
            'return' => "msg",
        ));

        $r = $sut->isUserEnabled($wpUser, 'username', '');

        $this->assertTrue($r instanceof WP_Error);
        $this->assertEquals('user_disabled', $r->getErrorKey());
    }

    /**
     * @test
     */
    public function isUserEnabled_returnsAuthenticatedUser_ifUserIsEnabled() {
        $sut = $this->sut(array('checkAuthorizationRequired'));
        $wpUser = new WP_User();
        $wpUser->setId(2);

        $sut->expects($this->once())
            ->method('checkAuthorizationRequired')
            ->with($wpUser)
            ->willReturn($wpUser->ID);

        $this->userManager->expects($this->once())
            ->method('isDisabled')
            ->with($wpUser->ID)
            ->willReturn(false);

        wp_mock::userFunction('get_user_meta', array(
            'times' => 0,
        ));

        $r = $sut->isUserEnabled($wpUser, 'username', '');

        $this->assertEquals($wpUser, $r);
    }

    /**
     * @test
     */
    public function isUserInAuthorizationGroup_ifAuthorizeByGroupIsDisabled_itSucceeds() {
        $sut = $this->sut(array('checkAuthorizationRequired'));
        $wpUser = new WP_User();
        $wpUser->setId(2);

        $sut->expects($this->once())
            ->method('checkAuthorizationRequired')
            ->with($wpUser)
            ->willReturn($wpUser->ID);

        $this->configuration->expects($this->once())
            ->method('getOptionValue')
            ->with(NextADInt_Adi_Configuration_Options::AUTHORIZE_BY_GROUP)
            ->willReturn(false);

        $this->assertEquals($wpUser, $sut->isUserInAuthorizationGroup($wpUser, 'username'));
    }

    /**
     * @test
     */
    public function isUserInAuthorizationGroup_ifUserIsOnlyLocalAvailable_itSucceeds() {
        $sut = $this->sut(array('checkAuthorizationRequired'));
        $wpUser = new WP_User();
        $wpUser->setId(2);

        $sut->expects($this->once())
            ->method('checkAuthorizationRequired')
            ->with($wpUser)
            ->willReturn($wpUser->ID);

        $this->configuration->expects($this->once())
            ->method('getOptionValue')
            ->with(NextADInt_Adi_Configuration_Options::AUTHORIZE_BY_GROUP)
            ->willReturn(true);

        wp_mock::userFunction('get_user_meta', array(
            'times' => 1,
            'args' => array($wpUser->ID, NEXT_AD_INT_PREFIX . 'objectguid', true),
            'return' => null
        ));

        $this->assertEquals($wpUser, $sut->isUserInAuthorizationGroup($wpUser, 'username'));
    }

    /**
     * @test
     */
    public function isUserInAuthorizationGroup_ifUserIsFromAd_andHeDoesNotBelongAuthorizationGroup_itFails() {
        $sut = $this->sut(array('checkAuthorizationRequired'));
        $wpUser = new WP_User();
        $wpUser->setId(2);

        $sut->expects($this->once())
            ->method('checkAuthorizationRequired')
            ->with($wpUser)
            ->willReturn($wpUser->ID);

        $this->configuration->expects($this->once())
            ->method('getOptionValue')
            ->with(NextADInt_Adi_Configuration_Options::AUTHORIZE_BY_GROUP)
            ->willReturn(true);

        wp_mock::userFunction('get_user_meta', array(
            'times' => 1,
            'args' => array($wpUser->ID, NEXT_AD_INT_PREFIX . 'objectguid', true),
            'return' => 'guid'
        ));

        $this->loginState->setAuthenticationSucceeded();

        $roleMapping = new NextADInt_Adi_Role_Mapping('guid');

        $this->roleManager->expects($this->once())
            ->method('createRoleMapping')
            ->with('guid')
            ->willReturn($roleMapping);

        $this->roleManager->expects($this->once())
            ->method('isInAuthorizationGroup')
            ->with($roleMapping)
            ->willReturn(false);

        $r = $sut->isUserInAuthorizationGroup($wpUser, 'username');

        $this->assertTrue($r instanceof WP_Error);
        $this->assertEquals('user_not_authorized', $r->getErrorKey());
    }
}