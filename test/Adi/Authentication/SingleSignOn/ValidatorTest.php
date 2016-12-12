<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Authentication_SingleSignOn_ValidatorTest')) {
	return;
}

/**
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Ut_NextADInt_Adi_Authentication_SingleSignOn_ValidatorTest extends Ut_BasicTest
{
	/* @var NextADInt_Core_Session_Handler|PHPUnit_Framework_MockObject_MockObject $sessionHandler */
	private $sessionHandler;

	/* @var NextADInt_Core_Util_Internal_Native|PHPUnit_Framework_MockObject_MockObject $sessionHandler */
	private $native;

	public function setUp()
	{
		parent::setUp();

		$this->sessionHandler = $this->createMock('NextADInt_Core_Session_Handler');

		// mock away our internal php calls
		$this->native = $this->createMockedNative();
		NextADInt_Core_Util::native($this->native);
	}

	public function tearDown()
	{
		parent::tearDown();
		NextADInt_Core_Util::native(null);
	}

	/**
	 * @param null $methods
	 *
	 * @return NextADInt_Adi_Authentication_SingleSignOn_Validator|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Adi_Authentication_SingleSignOn_Validator')
			->setConstructorArgs(array())
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function validateLdapConnection_withOpenConnection_doesNotThrowsException()
	{
		$sut = $this->sut();

		/** @var NextADInt_Ldap_Connection|PHPUnit_Framework_MockObject_MockObject $ldapConnection */
		$ldapConnection = $this->createMock('NextADInt_Ldap_Connection');
		$this->behave($ldapConnection, 'isConnected', true);

		$sut->validateLdapConnection($ldapConnection);
	}

	/**
	 * @test
	 */
	public function validateLdapConnection_withClosedConnection_throwsException()
	{
		$sut = $this->sut();

		$this->expectAuthenticationException('Cannot connect to ldap. Check the connection.');

		/** @var NextADInt_Ldap_Connection|PHPUnit_Framework_MockObject_MockObject $ldapConnection */
		$ldapConnection = $this->createMock('NextADInt_Ldap_Connection');
		$this->behave($ldapConnection, 'isConnected', false);

		$sut->validateLdapConnection($ldapConnection);
	}

	/**
	 * @test
	 */
	public function validateUser_withValidUser_doesNotThrowException()
	{
		$user = $this->createWpUserMock();
		$sut = $this->sut();

		$sut->validateUser($user);
	}

	/**
	 * @test
	 */
	public function validateUser_withInvalidUser_throwsException()
	{
		$sut = $this->sut();

		$this->expectAuthenticationException('The given user is invalid.');

		$sut->validateUser(false);
	}

	/**
	 * @test
	 */
	public function validateProfile_withProfile_doesNotThrowException()
	{
		$sut = $this->sut(array('getSessionHandler'));
		$profile = array();

		$sut->validateProfile($profile);
	}

	/**
	 * @test
	 */
	public function validateProfile_withoutProfile_throwsException()
	{
		$sut = $this->sut(array('getSessionHandler'));
		$profile = null;

		$this->expectAuthenticationException('No profile found for authentication.');

		$sut->validateProfile($profile);
	}

	/**
	 * @test
	 */
	public function validateAuthenticationState_withoutFailedAuthentication_doesNotThrowException()
	{
		$credentials = new NextADInt_Adi_Authentication_Credentials('max@test.ad');
		$sut = $this->sut(array('getSessionHandler'));

		$this->sessionHandler->expects($this->once())
			->method('getValue')
			->with(NextADInt_Adi_Authentication_SingleSignOn_Service::FAILED_SSO_UPN)
			->willReturn(null);

		$this->behave($sut, 'getSessionHandler', $this->sessionHandler);

		$sut->validateAuthenticationState($credentials);
	}

	/**
	 * @test
	 */
	public function validateAuthenticationState_withFailedAuthentication_throwsException()
	{
		$credentials = new NextADInt_Adi_Authentication_Credentials('max@test.ad');
		$sut = $this->sut(array('getSessionHandler'));

		$this->expectAuthenticationException('User has already failed to authenticate. Stop retrying.');

		$this->sessionHandler->expects($this->once())
			->method('getValue')
			->with(NextADInt_Adi_Authentication_SingleSignOn_Service::FAILED_SSO_UPN)
			->willReturn($credentials->getUserPrincipalName());

		$this->behave($sut, 'getSessionHandler', $this->sessionHandler);

		$sut->validateAuthenticationState($credentials);
	}

	/**
	 * @test
	 */
	public function validateLogoutState_withUserLoggedIn_doesNotThrowException()
	{
		$sut = $this->sut(array('getSessionHandler'));

		$this->sessionHandler->expects($this->once())
			->method('getValue')
			->with(NextADInt_Adi_Authentication_SingleSignOn_Service::USER_LOGGED_OUT, false)
			->willReturn(false);

		$this->behave($sut, 'getSessionHandler', $this->sessionHandler);

		$sut->validateLogoutState();
	}

	/**
	 * @test
	 */
	public function validateLogoutState_withUserLoggedOut_throwsException()
	{
		$sut = $this->sut(array('getSessionHandler'));

		$this->expectLogoutAuthenticationException('User will not be logged in via SSO b/c he logged out manually.');

		$this->sessionHandler->expects($this->once())
			->method('getValue')
			->with(NextADInt_Adi_Authentication_SingleSignOn_Service::USER_LOGGED_OUT, false)
			->willReturn(true);

		$this->behave($sut, 'getSessionHandler', $this->sessionHandler);

		$sut->validateLogoutState();
	}

	/**
	 * @test
	 */
	public function validateUrl_notOnLogout_doesNotThrowException()
	{
		$sut = $this->sut();

		$sut->validateUrl();
	}

	/**
	 * @test
	 */
	public function validateUrl_onLogout_throwsException()
	{
		$_GET['action'] = 'logout';
		$sut = $this->sut();

		$this->expectLogoutAuthenticationException('User cannot be logged in on logout action.');

		$sut->validateUrl();
	}

	/**
	 * @test
	 */
	public function throwAuthenticationException_throwsCorrectException()
	{
		$message = 'This is an exception.';
		$sut = $this->sut();

		$this->expectAuthenticationException($message);

		$this->invokeMethod($sut, 'throwAuthenticationException', array($message));
	}

	/**
	 * Expect our {@link NextADInt_Adi_Authentication_Exception} to be thrown.
	 *
	 * @param $expectedMessage
	 */
	private function expectAuthenticationException($expectedMessage)
	{
		$this->expectExceptionThrown('NextADInt_Adi_Authentication_Exception', $expectedMessage);
	}

	/**
	 * Expect our {@link NextADInt_Adi_Authentication_LogoutException} to be thrown.
	 *
	 * @param $expectedMessage
	 */
	private function expectLogoutAuthenticationException($expectedMessage)
	{
		$this->expectExceptionThrown('NextADInt_Adi_Authentication_LogoutException', $expectedMessage);
	}
}