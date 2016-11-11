<?php

/**
 * Ut_NextADInt_Adi_Authentication_Persistence_FailedLoginRepositoryTest
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_Authentication_Persistence_FailedLoginRepositoryTest extends Ut_BasicTest
{
	public function setUp()
	{
		parent::setUp();
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @return NextADInt_Adi_Authentication_Persistence_FailedLoginRepository|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Adi_Authentication_Persistence_FailedLoginRepository')
			->setConstructorArgs(array())
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function blockUser_unblockUserAfterCalculatedTime_persistCalculatedTime()
	{
		$sut = $this->sut(array('getCurrentTime', 'persistBlockUntil'));
		$currentTime = 1455193809;

		$sut->expects($this->once())
			->method('getCurrentTime')
			->willReturn($currentTime);

		$sut->expects($this->once())
			->method('persistBlockUntil')
			->with('hugo', $currentTime + 30)
			->willReturn(true);

		$actual = $sut->blockUser('hugo', 30);
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function isUserBlocked_userIsStillBlocked_returnTrue()
	{
		$sut = $this->sut(array('findBlockUntil', 'getCurrentTime'));
		$currentTime = 1455193809;

		$sut->expects($this->once())
			->method('findBlockUntil')
			->with('hugo')
			->willReturn($currentTime);

		$sut->expects($this->once())
			->method('getCurrentTime')
			->with()
			->willReturn($currentTime);

		$actual = $sut->isUserBlocked('hugo');
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function isUserBlocked_userIsUnblocked_returnFalse()
	{
		$sut = $this->sut(array('findBlockUntil', 'getCurrentTime'));

		$sut->expects($this->once())
			->method('findBlockUntil')
			->with('hugo')
			->willReturn(0);

		$sut->expects($this->once())
			->method('getCurrentTime')
			->with()
			->willReturn(1455193809);

		$actual = $sut->isUserBlocked('hugo');
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function increaseLoginAttempts_increaseCounterForFailedLoginAttempts_persistNewValue()
	{
		$sut = $this->sut(array('findLoginAttempts', 'persistLoginAttempts'));

		$sut->expects($this->once())
			->method('findLoginAttempts')
			->with('hugo')
			->willReturn(9);

		$sut->expects($this->once())
			->method('persistLoginAttempts')
			->with('hugo', 10)
			->willReturn(true);

		$actual = $sut->increaseLoginAttempts('hugo');
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function resetUser_resetLoginAttemptsAndUnblockTime_persistNewValues()
	{
		$sut = $this->sut(array('deleteBlockUntil', 'deleteLoginAttempts'));

		$sut->expects($this->once())
			->method('deleteBlockUntil')
			->with('hugo')
			->willReturn(true);

		$sut->expects($this->once())
			->method('deleteLoginAttempts')
			->with('hugo')
			->willReturn(true);

		$actual = $sut->resetUser('hugo');
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function resetUser_resetLoginAttemptsFailed_returnFalse()
	{
		$sut = $this->sut(array('deleteBlockUntil', 'deleteLoginAttempts'));

		$sut->expects($this->once())
			->method('deleteBlockUntil')
			->with('hugo')
			->willReturn(true);

		$sut->expects($this->once())
			->method('deleteLoginAttempts')
			->with('hugo')
			->willReturn(false);

		$actual = $sut->resetUser('hugo');
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function getSecondsUntilUnblock_userIsBlocked_returnSecondsUntilUnblock()
	{
		$sut = $this->sut(array('findBlockUntil', 'getCurrentTime'));

		$sut->expects($this->once())
			->method('findBlockUntil')
			->with('hugo')
			->willReturn(120);

		$sut->expects($this->once())
			->method('getCurrentTime')
			->with()
			->willReturn(120);

		$actual = $sut->getSecondsUntilUnblock('hugo');
		$this->assertEquals(1, $actual);
	}

	/**
	 * @test
	 */
	public function getSecondsUntilUnblock_userIsNotBlocked_returnZero()
	{
		$sut = $this->sut(array('findBlockUntil', 'getCurrentTime'));

		$sut->expects($this->once())
			->method('findBlockUntil')
			->with('hugo')
			->willReturn(110);

		$sut->expects($this->once())
			->method('getCurrentTime')
			->with()
			->willReturn(120);

		$actual = $sut->getSecondsUntilUnblock('hugo');
		$this->assertEquals(0, $actual);
	}

	/**
	 * @test
	 */
	public function findLoginAttempts_resetUnblockTime_deleteOption()
	{
		$sut = $this->sut(array('getOptionName'));

		$unixTime = 323767272;

		$sut->expects($this->once())
			->method('getOptionName')
			->with(true, 'hugo')
			->willReturn('next_ad_int_fl_la_hugo');

		\WP_Mock::wpFunction('get_site_option', array(
			'args' => array('next_ad_int_fl_la_hugo', 0),
			'times' => 1,
			'return' => $unixTime
		));

		$actual = $sut->findLoginAttempts('hugo');
		$this->assertEquals($unixTime, $actual);
	}

	/**
	 * @test
	 */
	public function persistLoginAttempts_resetUnblockTime_deleteOption()
	{
		$sut = $this->sut(array('getOptionName'));

		$unixTime = 123238723;

		$sut->expects($this->once())
			->method('getOptionName')
			->with(true, 'hugo')
			->willReturn('next_ad_int_fl_la_hugo');

		\WP_Mock::wpFunction('update_site_option', array(
			'args' => array('next_ad_int_fl_la_hugo', $unixTime),
			'times' => 1,
			'return' => true
		));

		$actual = $sut->persistLoginAttempts('hugo', $unixTime);
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function deleteLoginAttempts_resetUnblockTime_deleteOption()
	{
		$sut = $this->sut(array('getOptionName'));

		$sut->expects($this->once())
			->method('getOptionName')
			->with(true, 'hugo')
			->willReturn('next_ad_int_fl_la_hugo');

		\WP_Mock::wpFunction('delete_site_option', array(
			'args' => 'next_ad_int_fl_la_hugo',
			'times' => 1,
			'return' => true
		));

		$actual = $sut->deleteLoginAttempts('hugo');
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function findBlockUntil_resetUnblockTime_deleteOption()
	{
		$sut = $this->sut(array('getOptionName'));

		$unixTime = 323767272;

		$sut->expects($this->once())
			->method('getOptionName')
			->with(false, 'hugo')
			->willReturn('next_ad_int_fl_bt_hugo');

		\WP_Mock::wpFunction('get_site_option', array(
			'args' => array('next_ad_int_fl_bt_hugo', 0),
			'times' => 1,
			'return' => $unixTime
		));

		$actual = $sut->findBlockUntil('hugo');
		$this->assertEquals($unixTime, $actual);
	}

	/**
	 * @test
	 */
	public function persistBlockUntil_resetUnblockTime_deleteOption()
	{
		$sut = $this->sut(array('getOptionName'));

		$unixTime = 123238723;

		$sut->expects($this->once())
			->method('getOptionName')
			->with(false, 'hugo')
			->willReturn('next_ad_int_fl_bt_hugo');

		\WP_Mock::wpFunction('update_site_option', array(
			'args' => array('next_ad_int_fl_bt_hugo', $unixTime),
			'times' => 1,
			'return' => true
		));

		$actual = $sut->persistBlockUntil('hugo', $unixTime);
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function deleteBlockUntil_resetUnblockTime_deleteOption()
	{
		$sut = $this->sut(array('getOptionName'));

		$sut->expects($this->once())
			->method('getOptionName')
			->with(false, 'hugo')
			->willReturn('next_ad_int_fl_bt_hugo');

		\WP_Mock::wpFunction('delete_site_option', array(
			'args' => 'next_ad_int_fl_bt_hugo',
			'times' => 1,
			'return' => true
		));

		$actual = $sut->deleteBlockUntil('hugo');
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function getCurrentTime_getUnixTimestamp_returnTimestamp()
	{
		$sut = $this->sut(null);
		$this->assertEquals(time(), $sut->getCurrentTime());
	}

	/**
	 * @test
	 */
	public function encodeUsername_encodeUsernameToShaString_returnEncodedUsername()
	{
		$username = "klammer";

		$sut = $this->sut(null);

		$this->assertEquals(sha1($username), $sut->encodeUsername($username));
	}
}