<?php

namespace Dreitier\Nadi\User\Persistence;

use Dreitier\Nadi\Authentication\PrincipalResolver;
use Dreitier\Nadi\User\User;
use Dreitier\Test\BasicTestCase;
use Dreitier\WordPress\WordPressErrorException;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @access private
 */
class RepositoryTest extends BasicTestCase
{
	/** @var WordPressErrorException|\Mockery\MockInterface */
	private $exceptionUtil;

	public function setUp(): void
	{
		parent::setUp();

		$this->exceptionUtil = $this->createUtilClassMock(WordPressErrorException::class);
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function findById_delegatesCallToInternalMethod()
	{
		$sut = $this->sut(array('findByKey'));

		$expected = $this->createMock(\WP_User::class);

		$sut->expects($this->once())
			->method('findByKey')
			->with('id', 1)
			->willReturn($expected);

		$actual = $sut->findById(1);

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findByUsername_delegatesCallToInternalMethod()
	{
		$sut = $this->sut(array('findByKey'));

		$expected = $this->createMock(\WP_User::class);

		$sut->expects($this->once())
			->method('findByKey')
			->with('login', 'test')
			->willReturn($expected);

		$actual = $sut->findByUsername('test');

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findByEmail_delegatesCallToInternalMethod()
	{
		$sut = $this->sut(array('findByKey'));

		$expected = $this->createMock(\WP_User::class);

		$sut->expects($this->once())
			->method('findByKey')
			->with('email', 'test@test.com')
			->willReturn($expected);

		$actual = $sut->findByEmail('test@test.com');

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findByKey_delegatesCallToWordPressFunction()
	{
		$sut = $this->sut([]);

		$expected = $this->createMock(\WP_User::class);

		\WP_Mock::userFunction('get_user_by', array(
			'args' => array('login', 'test'),
			'times' => 1,
			'return' => $expected,
		));

		$actual = $this->invokeMethod($sut, 'findByKey', array('login', 'test'));

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findByMetaKey_delegatesCallToWordPressFunction()
	{
		$sut = $this->sut();

		$expected = array($expected = $this->createMock(\WP_User::class));

		\WP_Mock::userFunction('get_users', array(
			'args' => array(array('meta_key' => 'key', 'meta_value' => 'value', 'fields' => 'all')),
			'times' => 1,
			'return' => $expected,
		));

		$actual = $this->invokeMethod($sut, 'findByMetaKey', array('key', 'value'));
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findByMetaKey_itIgnoresMetaValue_ifValueIsNull()
	{
		$sut = $this->sut();

		$expected = array($expected = $this->createMock(\WP_User::class));

		\WP_Mock::userFunction('get_users', array(
			'args' => array(array('meta_key' => 'key', 'fields' => 'all')),
			'times' => 1,
			'return' => $expected,
		));

		$actual = $this->invokeMethod($sut, 'findByMetaKey', array('key'));
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findUserMeta_delegatesCallToWordPressFunction()
	{
		$sut = $this->sut();

		$expected = array('first_name' => array('My first name'));

		\WP_Mock::userFunction('get_user_meta', array(
			'args' => array(666),
			'times' => 1,
			'return' => $expected,
		));

		$actual = $sut->findUserMeta(666);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function updateMetaKey_delegatesCallToWordPressFunction()
	{
		$sut = $this->sut();

		\WP_Mock::userFunction('update_user_meta', array(
			'args' => array(1, 'key', 'value'),
		));

		$sut->updateMetaKey(1, 'key', 'value');
	}

	/**
	 * @test
	 */
	public function findBySAMAccountName_withoutUser_returnsFalse()
	{
		$sut = $this->sut(array('findByMetaKey'));

		$actual = $sut->findBySAMAccountName('sam');

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function findBySAMAccountName_withUser_returnsUser()
	{
		$sut = $this->sut(array('findByMetaKey'));

		$wpUser = $this->createMock(\WP_User::class);

		$sut->expects($this->once())
			->method('findByMetaKey')
			->with('next_ad_int_samaccountname', 'sam')
			->willReturn(array($wpUser));

		$actual = $sut->findBySAMAccountName('sam');

		$this->assertEquals($wpUser, $actual);
	}

	/**
	 * @test
	 */
	public function updateSAMAccountName_delegatesCallToInternalMethod()
	{
		$sut = $this->sut(array('updateMetaKey'));

		$sut->expects($this->once())
			->method('updateMetaKey')
			->with(1, 'next_ad_int_samaccountname', 'sam');

		$sut->updateSAMAccountName(1, 'sam');
	}

	/**
	 * @test
	 */
	public function findByObjectGuid_withoutUser_returnsFalse()
	{
		$sut = $this->sut(array('findByMetaKey'));

		$actual = $sut->findByObjectGuid('guid1');

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function findByObjectGuid_withUser_returnsUser()
	{
		$sut = $this->sut(array('findByMetaKey'));

		$wpUser = $this->createMock(\WP_User::class);

		$sut->expects($this->once())
			->method('findByMetaKey')
			->with('next_ad_int_objectguid', 'guid')
			->willReturn(array($wpUser));

		$actual = $sut->findByObjectGuid('guid');

		$this->assertEquals($wpUser, $actual);
	}

	/**
	 * @test
	 * @issue ADI-702
	 */
	public function findByObjectGuid_withEmptyGuid_itReturnsFalse()
	{
		$sut = $this->sut(array('findByMetaKey'));

		$sut->expects($this->never())
			->method('findByMetaKey');

		$actual = $sut->findByObjectGuid('');

		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function isEmailExisting_delegatesCallToFindByEmailMethod()
	{
		$sut = $this->sut(array('findByEmail'));

		$sut->expects($this->once())
			->method('findByEmail')
			->with('test@test.com');

		$sut->isEmailExisting('test@test.com');
	}

	/**
	 * @test
	 */
	public function isEmailExisting_returnsTrueIfUserIsExisting()
	{
		$sut = $this->sut(array('findByEmail'));

		$wpUser = $this->createMock(\WP_User::class);

		$sut->expects($this->once())
			->method('findByEmail')
			->with('test@test.com')
			->willReturn($wpUser);

		$actual = $sut->isEmailExisting('test@test.com');

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function isEmailExisting_returnsFalseIfUserIsNotExisting()
	{
		$sut = $this->sut(array('findByEmail'));

		$sut->expects($this->once())
			->method('findByEmail')
			->with('test@test.com')
			->willReturn(false);

		$actual = $sut->isEmailExisting('test@test.com');

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function updateEmail_delegatesToInternalUpdatePropertyMethod()
	{
		$sut = $this->sut(array('updateProperty'));

		$sut->expects($this->once())
			->method('updateProperty')
			->with(1, 'user_email', 'test@test.com');

		$sut->updateEmail(1, 'test@test.com');
	}

	/**
	 * @test
	 */
	public function updatePassword_delegatesToInternalUpdatePropertyMethod()
	{
		$sut = $this->sut(array('updateProperty'));

		$sut->expects($this->once())
			->method('updateProperty')
			->with(1, 'user_pass', 'password');

		$sut->updatePassword(1, 'password');
	}

	/**
	 * @test
	 */
	public function updateProperty_doesNotTriggerWordPressErrorPart()
	{
		$sut = $this->sut(array('findById'));

		\WP_Mock::userFunction('wp_update_user', array(
			'args' => array(
				array('ID' => 1, 'user_email' => 'test@test.com'),
			),
			'times' => 1,
			'return' => 1,
		));

		\WP_Mock::userFunction('is_wp_error', array(
			'args' => 1,
			'times' => 1,
			'return' => false,
		));

		$sut->expects($this->never())
			->method('findById')
			->with(1);

		$this->invokeMethod($sut, 'updateProperty', array(1, 'user_email', 'test@test.com'));
	}

	/**
	 * @test
	 */
	public function updateProperty_doesTriggerWordPressErrorPart()
	{
		$sut = $this->sut(array('findById'));

		$wpUser = $this->createMock(\WP_User::class);
		$wpUser->display_name = 'display_name';

		$wpErrorMock = $this->createMockedObject(\WP_Error::class, [], array('get_error_messages'));
		$wpErrorMock->expects($this->once())
			->method('get_error_messages')
			->willReturn([]);

		\WP_Mock::userFunction('wp_update_user', array(
			'args' => array(
				array('ID' => 1, 'user_email' => 'test@test.com'),
			),
			'times' => 1,
			'return' => $wpErrorMock,
		));

		\WP_Mock::userFunction('is_wp_error', array(
			'args' => array($wpErrorMock),
			'times' => 1,
			'return' => true,
		));

		$sut->expects($this->once())
			->method('findById')
			->with(1)
			->willReturn($wpUser);

		$this->invokeMethod($sut, 'updateProperty', array(1, 'user_email', 'test@test.com'));
	}

	/**
	 * @test
	 */
	public function create_withErrorOnCreation_throwsException()
	{
		$email = 'john.doe@test.ad';

		$sut = $this->sut();

		$wpError = $this->createMockedObject(\WP_Error::class, [], array('get_error_messages'));

		$adiUser = $this->createMock(User::class);

		$this->behave($adiUser, 'getUserLogin', 'username');
		$this->behave($adiUser, 'getCredentials', PrincipalResolver::createCredentials('username', 'password'));

		\WP_Mock::userFunction('is_wp_error', array(
			'args' => array($wpError),
			'times' => 1,
			'return' => true,
		));

		\WP_Mock::userFunction('wp_create_user', array(
			'args' => array('username', 'password', $email),
			'times' => 1,
			'return' => $wpError,
		));

		$this->exceptionUtil->shouldReceive('processWordPressError')
			->once();

		$sut->create($adiUser, $email);
	}

	/**
	 * @test
	 */
	public function create_itReturnsResult()
	{
		$email = 'john.doe@test.ad';

		$sut = $this->sut();

		$adiUser = $this->createMock(User::class);

		$this->behave($adiUser, 'getUserLogin', 'username');
		$this->behave($adiUser, 'getCredentials', PrincipalResolver::createCredentials('username', 'password'));

		\WP_Mock::userFunction('wp_create_user', array(
			'args' => array('username', 'password', $email),
			'times' => 1,
			'return' => 1,
		));

		\WP_Mock::userFunction('is_wp_error', array(
			'args' => 1,
			'times' => 1,
			'return' => false,
		));

		$this->exceptionUtil->shouldReceive('handleWordPressErrorAsException')
			->never();

		$actual = $sut->create($adiUser, $email);
		$this->assertEquals(1, $actual);
	}

	/**
	 * @test
	 */
	public function update_itReturnswithErrorOnUpdate_returnsErrorObject()
	{
		$sut = $this->sut();

		$wpError = $this->createMockedObject(\WP_Error::class, [], array('get_error_messages'));
		$wpError->expects($this->once())
			->method('get_error_messages')
			->willReturn([]);

		$adiUser = $this->createMock(User::class);

		$adiUser->expects($this->once())
			->method('getUserLogin')
			->willReturn('username');

		$adiUser->expects($this->once())
			->method('getId')
			->willReturn(1);

		\WP_Mock::userFunction('is_wp_error', array(
			'args' => array($wpError),
			'times' => 1,
			'return' => true,
		));

		\WP_Mock::userFunction('wp_update_user', array(
			'args' => array([]),
			'times' => 1,
			'return' => $wpError,
		));

		$actual = $sut->update($adiUser, []);
		$this->assertEquals($wpError, $actual);
	}

	/**
	 * Create a partial mock for our {@see Repository}.
	 *
	 * @param $methods
	 *
	 * @return Repository|MockObject
	 */
	private function sut(array $methods = [])
	{
		return $this->getMockBuilder(Repository::class)
			->setConstructorArgs([])
			->onlyMethods($methods)
			->getMock();
	}
}