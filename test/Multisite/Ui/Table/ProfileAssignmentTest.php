<?php

/**
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access private
 */
class Ut_NextADInt_Multisite_Ui_Table_ProfileAssignmentTest extends Ut_BasicTest
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
	 * @param null $methods
	 *
	 * @return NextADInt_Multisite_Ui_Table_ProfileAssignment|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Multisite_Ui_Table_ProfileAssignment')
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

		$expected = array(
			'cb'                                                       => '<input type="checkbox" />',
			NextADInt_Multisite_Ui_Table_ProfileAssignment::ADI_SITE_NAME_COLUMN => 'Site Name',
			'blogname'                                                 => 'URL',
		);

		WP_Mock::onFilter('wpmu_blogs_columns')
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

		WP_Mock::wpFunction('untrailingslashit', array(
			'args'   => 'domain.com/path',
			'times'  => 1,
			'return' => 'domain.com/path',
		));
		WP_Mock::wpFunction('esc_attr', array(
			'args'   => 1,
			'times'  => 1,
			'return' => 1,
		));

		$output = $this->captureOutput(function() use ($sut) {
			$sut->column_cb(array(
				'blog_id' => '1',
				'domain'  => 'domain.com',
				'path'    => '/path',
			));
		});

		$this->assertContains('<label class="screen-reader-text" for="blog_1">Select domain.com/path</label>', $output);
		$this->assertContains('<input type="checkbox" id="blog_1" name="allblogs[]" value="1" />', $output);
	}

	/**
	 * @test
	 * @outputBuffering disabled
	 */
	public function addContent_outputsSiteName()
	{
		$sut = $this->sut(null);

		WP_Mock::wpFunction('get_blog_details', array(
			'args'   => 666,
			'times'  => 1,
			'return' => (object)array('blogname' => 'BLOG'),
		));


		$this->expectOutputString('BLOG');

		$sut->addContent(NextADInt_Multisite_Ui_Table_ProfileAssignment::ADI_SITE_NAME_COLUMN, 666);
	}

}