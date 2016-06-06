<?php

/**
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Ut_Core_Migration_Persistence_MigrationRepositoryTest extends Ut_BasicTest
{
	/**
	 * @param null $methods
	 *
	 * @return Core_Migration_Persistence_MigrationRepository|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('Core_Migration_Persistence_MigrationRepository')
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function getLastMigration_withMultsite_returnsExpectedResult()
	{
		$sut = $this->sut();

		WP_Mock::wpFunction('is_multisite', array(
			'times'  => 1,
			'return' => true,
		));

		WP_Mock::wpFunction('get_site_option', array(
			'times'  => 1,
			'return' => 1,
		));

		WP_Mock::wpFunction('get_option', array(
			'times'  => 0,
			'return' => 2,
		));

		$actual = $sut->getLastMigration();

		$this->assertEquals(1, $actual);
	}

	/**
	 * @test
	 */
	public function getLastMigration_withSinglesite_returnsExpectedResult()
	{
		$sut = $this->sut();

		WP_Mock::wpFunction('is_multisite', array(
			'times'  => 1,
			'return' => false,
		));

		WP_Mock::wpFunction('get_site_option', array(
			'times'  => 0,
			'return' => 1,
		));

		WP_Mock::wpFunction('get_option', array(
			'times'  => 1,
			'return' => 2,
		));

		$actual = $sut->getLastMigration();

		$this->assertEquals(2, $actual);
	}

	/**
	 * @test
	 */
	public function setLastMigration_withMultisite_triggersCorrectFunction()
	{
		$sut = $this->sut();

		WP_Mock::wpFunction('is_multisite', array(
			'times'  => 1,
			'return' => true,
		));

		WP_Mock::wpFunction('update_site_option', array(
			'times'  => 1,
			'with'   => 1,
			'return' => 1,
		));

		WP_Mock::wpFunction('update_option', array(
			'times'  => 0,
			'with'   => 1,
			'return' => 2,
		));

		$actual = $sut->setLastMigration(1);

		$this->assertEquals(1, $actual);
	}

	/**
	 * @test
	 */
	public function setLastMigration_withSinglesite_triggersCorrectFunction()
	{
		$sut = $this->sut();

		WP_Mock::wpFunction('is_multisite', array(
			'times'  => 1,
			'return' => false,
		));

		WP_Mock::wpFunction('update_site_option', array(
			'times'  => 0,
			'with'   => 1,
			'return' => 1,
		));

		WP_Mock::wpFunction('update_option', array(
			'times'  => 1,
			'with'   => 1,
			'return' => 2,
		));

		$actual = $sut->setLastMigration(1);

		$this->assertEquals(2, $actual);
	}
}