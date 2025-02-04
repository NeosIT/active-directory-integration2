<?php

namespace Dreitier\WordPress\Multisite\Configuration\Persistence;

use Dreitier\Test\BasicTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @access private
 */
class DefaultProfileRepositoryTest extends BasicTestCase
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
	 * @param $methods
	 *
	 * @return DefaultProfileRepository|MockObject
	 */
	public function sut(array $methods = [])
	{
		return $this->getMockBuilder(DefaultProfileRepository::class)
			->setConstructorArgs([])
			->onlyMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function findProfileId_noProfileExists_returnsMinusOne()
	{
		$sut = $this->sut(array('getProfileOptionName'));

		$sut->expects($this->once())
			->method('getProfileOptionName')
			->willReturn('next_ad_int_p_default');

		\WP_Mock::userFunction('get_site_option', array(
			'args' => array('next_ad_int_p_default', false),
			'times' => 1,
			'return' => false,
		));

		$actual = $sut->findProfileId();
		$this->assertEquals(-1, $actual);
	}

	/**
	 * @test
	 */
	public function findProfileId_profileExists_returnsProfileId()
	{
		$sut = $this->sut(array('getProfileOptionName'));

		$sut->expects($this->once())
			->method('getProfileOptionName')
			->willReturn('next_ad_int_p_default');

		\WP_Mock::userFunction('get_site_option', array(
			'args' => array('next_ad_int_p_default', false),
			'times' => 1,
			'return' => 5,
		));

		$actual = $sut->findProfileId();
		$this->assertEquals(5, $actual);
	}

	/**
	 * @test
	 */
	public function saveProfileId_triggerWordPressFunction()
	{
		$sut = $this->sut(array('getProfileOptionName'));

		$sut->expects($this->once())
			->method('getProfileOptionName')
			->willReturn('next_ad_int_p_default');

		\WP_Mock::userFunction('update_site_option', array(
			'args' => array('next_ad_int_p_default', 5),
			'times' => 1,
		));

		$sut->saveProfileId(5);
	}

	/**
	 * @test
	 */
	public function getProfileOptionName_returnsCorrectOptionName()
	{
		$sut = $this->sut();

		$expected = 'next_ad_int_p_default';
		$actual = $this->invokeMethod($sut, 'getProfileOptionName');

		$this->assertEquals($expected, $actual);
	}
}