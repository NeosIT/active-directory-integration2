<?php
namespace Dreitier\WordPress;

use Dreitier\Test\BasicTest;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class WordPressSiteRepositoryTest extends BasicTest
{
    public function setUp() : void
    {
        parent::setUp();
    }

    public function tearDown() : void
    {
        global $wp_version;
        unset($wp_version);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getSites_withWordPress4_6_shouldCallGetSites() {
        global $wp_version;
        $wp_version = '4.6';

        $expected = array('blog_id' => 1);

        $site = $this->createMockWithMethods(\BlueprintClass::class, array('to_array'));
        $site->expects($this->once())
            ->method('to_array')
            ->willReturn($expected);

        \WP_Mock::userFunction('get_sites', array(
                'times'  => 1,
                'return' => array($site))
        );

        // call function get_sites();
        $actual = WordPressSiteRepository::getSites();
        $this->assertEquals(array($expected), $actual);
    }

    /**
     * @test
     */
    public function getSites_withWordPress4_5_shouldCallWpGetSites() {
        global $wp_version;
        $wp_version = '4.5.3';

        $site = array(
            'blog_id' => '1'
        );

        \WP_Mock::userFunction('wp_get_sites', array(
                'times'  => 1,
                'return' => array($site))
        );

        // call function get_sites();
        $actual = WordPressSiteRepository::getSites();
        $this->assertEquals(array($site), $actual);
    }

    /**
     * @issue ADI-419
     * @test
     */
    public function ADI_419_getSite_withWordPress4_6_itShouldCallGetBlogDetails() {
        global $wp_version;
        $wp_version = '4.6';

        $site = array(
            'blog_id' => '1'
        );

        \WP_Mock::userFunction('get_blog_details', array(
                'times'  => 1,
                'return' => array($site))
        );

        // call function get_sites();
        $actual = WordPressSiteRepository::getSite();
        $this->assertEquals(array($site), $actual);
    }

    /**
     * @issue ADI-419
     * @test
     */
    public function ADI_419_getSite_withWordPress4_7_itShouldCallGetSite() {
        global $wp_version;
        $wp_version = '4.7';

        $site = array(
            'blog_id' => '1'
        );

        \WP_Mock::userFunction('get_site', array(
                'times'  => 1,
                'return' => array($site))
        );

        // call function get_sites();
        $actual = WordPressSiteRepository::getSite();
        $this->assertEquals(array($site), $actual);
    }
}