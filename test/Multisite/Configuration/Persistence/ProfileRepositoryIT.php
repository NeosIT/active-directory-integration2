<?php

/**
 * Ut_Database_ProfilesIT
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Multisite_Configuration_Persistence_ProfileRepositoryIT extends Ut_DatabaseTest
{
	/* @var Core_Persistence_WordPressRepository|PHPUnit_Framework_MockObject_MockObject basicCommands */
	private $wordPressRepository;

	public function setUp()
	{
		parent::setUp();

		$this->wordPressRepository = $this->createWordPressRepository();
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	public function createWordPressRepository()
	{
		global $pdo;
		$pdo = $this->getConnection()->getConnection();

		$mock = $this->createMock('Core_Persistence_WordPressRepository');

		$mock->expects($this->any())
			->method('getTableOptions')
			->willReturn('wp_options');

		$mock->expects($this->any())
			->method('getTableSiteMeta')
			->willReturn('wp_sitemeta');

		$mock->expects($this->any())
			->method('wpdb_get_col')
			->willReturnCallback(
				function ($sql, $params) {
					// sprintf sql
					array_unshift($params, $sql);
					if (sizeof($params) > 1) {
						$sql = call_user_func_array('sprintf', $params);
					}

					// execute sql
					/* @var PDO $pdo */
					global $pdo;

					$values = $pdo->query($sql)->fetchAll();

					// manipulate data because pdo can not emulate get_col
					$result = array();
					foreach ($values as $value) {
						$result[] = $value['meta_key'];
					}

					return $result;
				}
			);

		return $mock;
	}

	/**
	 * @param $methods
	 *
	 * @return Multisite_Configuration_Persistence_ProfileRepository|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods)
	{
		return $this->getMockBuilder('Multisite_Configuration_Persistence_ProfileRepository')
			->setConstructorArgs(
				array(
					null,
					null,
					$this->wordPressRepository
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function findAllIDs_singleSite_returnAllIds()
	{
		$sut = $this->sut(null);
		$connection = $this->getConnection();
		$pdo = $connection->getConnection();

		WP_Mock::wpFunction('is_multisite', array(
			'return' => true
		));

		$pdo->exec("
CREATE TABLE `wp_sitemeta` (
  `meta_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `site_id` bigint(20) NOT NULL DEFAULT '0',
  `meta_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_value` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`meta_id`),
  KEY `meta_key` (`meta_key`(191)),
  KEY `site_id` (`site_id`)
) ENGINE=InnoDB AUTO_INCREMENT=350 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

		$pdo->exec("
INSERT INTO `wp_sitemeta` (`meta_id`, `site_id`, `meta_key`, `meta_value`) VALUES (1, 1, 'adi2_p_n_1', 'aaa');
INSERT INTO `wp_sitemeta` (`meta_id`, `site_id`, `meta_key`, `meta_value`) VALUES (2, 1, 'adi2_p_d_1', 'afda');
INSERT INTO `wp_sitemeta` (`meta_id`, `site_id`, `meta_key`, `meta_value`) VALUES (3, 1, 'adi2_p_n_2', 'afv');
INSERT INTO `wp_sitemeta` (`meta_id`, `site_id`, `meta_key`, `meta_value`) VALUES (4, 1, 'adi2_p_d_2', 'dfdf');
INSERT INTO `wp_sitemeta` (`meta_id`, `site_id`, `meta_key`, `meta_value`) VALUES (5, 1, 'adi2_p_n_3', 'dddd');
INSERT INTO `wp_sitemeta` (`meta_id`, `site_id`, `meta_key`, `meta_value`) VALUES (6, 1, 'adi2_p_d_3', 'aaa');
");

		$actual = $sut->findAllIds();
		$expected = array('1', '2', '3');
		$this->assertEquals($expected, $actual);
	}
}