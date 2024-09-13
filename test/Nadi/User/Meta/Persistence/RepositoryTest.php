<?php

namespace Dreitier\Nadi\User\Meta\Persistence;

use Dreitier\Test\BasicTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @access private
 */
class RepositoryTest extends BasicTest
{
	public function setUp(): void
	{
		parent::setUp();
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function find_delegatesCallToWordPressFunction()
	{
		$sut = $this->sut(null);

		\WP_Mock::userFunction('get_user_meta', array(
			'args' => array(1, 'metaKey', false),
			'times' => 1,
			'return' => 'meta_value',
		));

		$actual = $sut->find(1, 'metaKey', false);
		$this->assertEquals('meta_value', $actual);
	}

	/**
	 * @test
	 */
	public function create_delegatesCallToWordPressFunction()
	{
		$sut = $this->sut(null);

		\WP_Mock::userFunction('add_user_meta', array(
			'args' => array(1, 'metaKey', 'metaValue'),
			'times' => 1,
			'return' => 1,
		));

		$actual = $sut->create(1, 'metaKey', 'metaValue');
		$this->assertEquals(1, $actual);
	}

	/**
	 * @test
	 */
	public function update_delegatesCallToWordPressFunction()
	{
		$sut = $this->sut(null);

		\WP_Mock::userFunction('update_user_meta', array(
			'args' => array(1, 'metaKey', 'metaValue'),
			'times' => 1,
			'return' => true,
		));

		$actual = $sut->update(true, 'metaKey', 'metaValue');
		$this->assertEquals(1, $actual);
	}

	/**
	 * @test
	 */
	public function delete_delegatesCallToWordPressFunction()
	{
		$sut = $this->sut(null);

		\WP_Mock::userFunction('delete_user_meta', array(
			'args' => array(1, 'metaKey'),
			'times' => 1,
			'return' => true,
		));

		$actual = $sut->delete(1, 'metaKey');
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function disableUser_triggersCorrectMethods()
	{
		$sut = $this->sut(array('update'));

		$wpUser = new \WP_User();
		$wpUser->user_login = 'user_login';
		$wpUser->user_email = 'user_email';
		$wpUser->ID = 1;

		$sut->expects($this->exactly(3))
			->method('update')
			->withConsecutive(
				array(1, 'next_ad_int_user_disabled', true),
				array(1, 'next_ad_int_user_disabled_reason', 'reason'),
				array(1, 'next_ad_int_user_disabled_email', 'user_email')
			);

		$sut->disableUser($wpUser, 'reason');
	}

	/**
	 * @test
	 */
	public function enableUser_triggersCorrectMethods()
	{
		$sut = $this->sut(array('update', 'delete'));

		$wpUser = new \WP_User();
		$wpUser->ID = 1;

		$sut->expects($this->exactly(2))
			->method('update')
			->withConsecutive(
				array(1, 'next_ad_int_user_disabled', false),
				array(1, 'next_ad_int_user_disabled_reason', '')
			);

		$sut->expects($this->once())
			->method('delete')
			->with(1, 'next_ad_int_user_disabled_email');

		$sut->enableUser($wpUser);
	}

	/**
	 * @test
	 */
	public function isUserDisabled_returnsExpectedResult()
	{
		$sut = $this->sut(array('find'));
		$sut->expects($this->exactly(2))
			->method('find')
			->with(1, 'next_ad_int_user_disabled', true)
			->willReturnOnConsecutiveCalls(false, true);

		foreach (array(false, true) as $expected) {
			$actual = $sut->isUserDisabled(1);
			$this->assertEquals($expected, $actual);
		}
	}

	/**
	 * Create a partial mock for our {@see Repository}.
	 *
	 * @param $methods
	 *
	 * @return Repository|MockObject
	 */
	private function sut($methods)
	{
		return $this->getMockBuilder(Repository::class)
			->setConstructorArgs(array())
			->setMethods($methods)
			->getMock();
	}
}