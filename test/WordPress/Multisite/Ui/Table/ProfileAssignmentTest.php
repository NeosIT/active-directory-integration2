<?php

namespace Dreitier\WordPress\Multisite\Ui\Table;

use Dreitier\Test\BasicTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access private
 */
class ProfileAssignmentTest extends BasicTest
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
	 * @param null $methods
	 *
	 * @return ProfileAssignment|MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder(ProfileAssignment::class)
			->disableOriginalConstructor()
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function register_itAddsFilter()
	{
		$sut = $this->sut(null);

		\WP_Mock::expectActionAdded('manage_sites_custom_column', array($sut, 'addContent'), 1, 2);

		$sut->register();
	}

	/**
	 * @test
	 */
	public function getColumns_returnsExpectedColumns()
	{
		$sut = $this->sut();
		$this->mockFunction__();

		$expected = array(
			'cb' => '<input type="checkbox" />',
			ProfileAssignment::NEXT_AD_INT_SITE_NAME_COLUMN => 'Site Name',
			'blogname' => 'URL',
		);

		\WP_Mock::onFilter('wpmu_blogs_columns')
			->with($expected)
			->reply($expected);

		$actual = $sut->get_columns();

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getBulkActions_returnsEmptyArray()
	{
		$sut = $this->sut();

		$result = $this->invokeMethod($sut, 'get_bulk_actions');

		$this->assertEquals(0, count($result));
	}

	/**
	 * @test
	 */
	public function columnCb_containsCorrectLabelAndInputElements()
	{
		$sut = $this->sut();
		$this->mockFunction__();

		\WP_Mock::wpFunction('untrailingslashit', array(
			'args' => 'domain.com/path',
			'times' => 1,
			'return' => 'domain.com/path',
		));

		\WP_Mock::wpFunction('esc_attr', array(
			'args' => 1,
			'times' => 1,
			'return' => 1,
		));

		$output = $this->captureOutput(function () use ($sut) {
			$sut->column_cb(array(
				'blog_id' => '1',
				'domain' => 'domain.com',
				'path' => '/path',
			));
		});

		$this->assertStringContainsString('<label class="screen-reader-text" for="blog_1">Select domain.com/path</label>', $output);
		$this->assertStringContainsString('<input type="checkbox" id="blog_1" name="allblogs[]" value="1" />', $output);
	}

	/**
	 * @test
	 * @outputBuffering disabled
	 */
	public function addContent_outputsSiteName()
	{
		global $wp_version;

		$wp_version = '4.6';

		$sut = $this->sut(null);

		\WP_Mock::wpFunction('get_blog_details', array(
			'args' => 666,
			'times' => 1,
			'return' => (object)array('blogname' => 'BLOG'),
		));


		$this->expectOutputString('BLOG');

		$sut->addContent(ProfileAssignment::NEXT_AD_INT_SITE_NAME_COLUMN, 666);
	}

	/**
	 * @issue ADI-419
	 * @test
	 * @outputBuffering disabled
	 */
	public function ADI_419_addContent_itUses_get_site_whenRunningWordPress4_7OrLater()
	{
		global $wp_version;

		$wp_version = '4.7';

		$sut = $this->sut(null);

		\WP_Mock::wpFunction('get_site', array(
			'args' => 666,
			'times' => 1,
			'return' => (object)array('blogname' => 'BLOG'),
		));

		$this->expectOutputString('BLOG');

		$sut->addContent(ProfileAssignment::NEXT_AD_INT_SITE_NAME_COLUMN, 666);
	}
}