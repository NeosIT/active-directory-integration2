<?php

namespace Dreitier\Nadi\Authentication\SingleSignOn;

use Dreitier\Ldap\Connection;
use Dreitier\Nadi\Authentication\AuthenticationException;
use Dreitier\Nadi\Authentication\LogoutException;
use Dreitier\Nadi\Authentication\PrincipalResolver;
use Dreitier\Test\BasicTestCase;
use Dreitier\Util\Internal\Native;
use Dreitier\Util\Session\SessionHandler;
use Dreitier\Util\Util;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class ValidatorTest extends BasicTestCase
{
	/* @var SessionHandler|MockObject $sessionHandler */
	private $sessionHandler;

	/* @var Native|MockObject $sessionHandler */
	private $native;

	public function setUp(): void
	{
		parent::setUp();

		$this->sessionHandler = $this->createMock(SessionHandler::class);

		// mock away our internal php calls
		$this->native = $this->createMockedNative();
		Util::native($this->native);
	}

	public function tearDown(): void
	{
		parent::tearDown();
		Util::native(null);
	}

	/**
	 * @param null $methods
	 *
	 * @return Validator|MockObject
	 */
	public function sut(array $methods = [])
	{
		return $this->getMockBuilder(Validator::class)
			->setConstructorArgs([])
			->onlyMethods($methods)
			->getMock();
	}

	#[Test]
	public function validateLdapConnection_withOpenConnection_doesNotThrowsException()
	{
		$sut = $this->sut();

		/** @var Connection|MockObject $ldapConnection */
		$ldapConnection = $this->createMock(Connection::class);
		$this->behave($ldapConnection, 'isConnected', true);

		$sut->validateLdapConnection($ldapConnection);
	}

	#[Test]
	public function validateLdapConnection_withClosedConnection_throwsException()
	{
		$sut = $this->sut();

		$this->expectAuthenticationException('Cannot connect to ldap. Check the connection.');

		/** @var Connection|MockObject $ldapConnection */
		$ldapConnection = $this->createMock(Connection::class);
		$this->behave($ldapConnection, 'isConnected', false);

		$sut->validateLdapConnection($ldapConnection);
	}

	#[Test]
	public function validateUser_withValidUser_doesNotThrowException()
	{
		$user = $this->createWpUserMock();
		$sut = $this->sut();

		$sut->validateUser($user);
	}

	#[Test]
	public function validateUser_withInvalidUser_throwsException()
	{
		$sut = $this->sut();

		$this->expectAuthenticationException('The given user is invalid.');

		$sut->validateUser(false);
	}

	#[Test]
	public function validateProfile_withProfile_doesNotThrowException()
	{
		$sut = $this->sut(array('getSessionHandler'));
		$profile = [];

		$sut->validateProfile($profile);
	}

	#[Test]
	public function validateProfile_withoutProfile_throwsException()
	{
		$sut = $this->sut(array('getSessionHandler'));
		$profile = null;

		$this->expectAuthenticationException('No profile found for authentication.');

		$sut->validateProfile($profile);
	}

	#[Test]
	public function validateAuthenticationState_withoutFailedAuthentication_doesNotThrowException()
	{
		$credentials = PrincipalResolver::createCredentials('max@test.ad');
		$sut = $this->sut(array('getSessionHandler'));

		$this->sessionHandler->expects($this->once())
			->method('getValue')
			->with(Service::FAILED_SSO_PRINCIPAL)
			->willReturn(null);

		$this->behave($sut, 'getSessionHandler', $this->sessionHandler);

		$sut->validateAuthenticationState($credentials);
	}

	#[Test]
	public function validateAuthenticationState_withFailedAuthentication_throwsException()
	{
		$credentials = PrincipalResolver::createCredentials('max@test.ad');
		$sut = $this->sut(array('getSessionHandler'));

		$this->expectAuthenticationException('User has already failed to authenticate. Stop retrying.');

		$this->sessionHandler->expects($this->once())
			->method('getValue')
			->with(Service::FAILED_SSO_PRINCIPAL)
			->willReturn($credentials->getUserPrincipalName());

		$this->behave($sut, 'getSessionHandler', $this->sessionHandler);

		$sut->validateAuthenticationState($credentials);
	}

	#[Test]
	public function validateLogoutState_withUserLoggedIn_doesNotThrowException()
	{
		$sut = $this->sut(array('getSessionHandler'));

		$this->sessionHandler->expects($this->once())
			->method('getValue')
			->with(Service::USER_LOGGED_OUT, false)
			->willReturn(false);

		$this->behave($sut, 'getSessionHandler', $this->sessionHandler);

		$sut->validateLogoutState();
	}

	#[Test]
	public function validateLogoutState_withUserLoggedOut_throwsException()
	{
		$sut = $this->sut(array('getSessionHandler'));

		$this->expectLogoutAuthenticationException('User will not be logged in via SSO b/c he logged out manually.');

		$this->sessionHandler->expects($this->once())
			->method('getValue')
			->with(Service::USER_LOGGED_OUT, false)
			->willReturn(true);

		$this->behave($sut, 'getSessionHandler', $this->sessionHandler);

		$sut->validateLogoutState();
	}

	#[Test]
	public function validateUrl_notOnLogout_doesNotThrowException()
	{
		$sut = $this->sut();

		$sut->validateUrl();
	}

	#[Test]
	public function validateUrl_onLogout_throwsException()
	{
		$_GET['action'] = 'logout';
		$sut = $this->sut();

		$this->expectLogoutAuthenticationException('User cannot be logged in on logout action.');

		$sut->validateUrl();
	}

	#[Test]
	public function throwAuthenticationException_throwsCorrectException()
	{
		$message = 'This is an exception.';
		$sut = $this->sut();

		$this->expectAuthenticationException($message);

		$this->invokeMethod($sut, 'throwAuthenticationException', array($message));
	}

	/**
	 * Expect our {@link AuthenticationException} to be thrown.
	 *
	 * @param $expectedMessage
	 */
	private function expectAuthenticationException($expectedMessage)
	{
		$this->expectExceptionThrown(AuthenticationException::class, $expectedMessage);
	}

	/**
	 * Expect our {@link LogoutException} to be thrown.
	 *
	 * @param $expectedMessage
	 */
	private function expectLogoutAuthenticationException($expectedMessage)
	{
		$this->expectExceptionThrown(LogoutException::class, $expectedMessage);
	}
}