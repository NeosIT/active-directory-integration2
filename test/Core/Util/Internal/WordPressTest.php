<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Core_Util_Internal_WordPress extends Ut_BasicTest
{
    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
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

        $site = $this->createMockWithMethods('BlueprintClass', array('to_array'));
        $site->expects($this->once())
            ->method('to_array')
            ->willReturn($expected);

        \WP_Mock::wpFunction('get_sites', array(
                'times'  => 1,
                'return' => array($site))
        );

        // call function get_sites();
        $actual = NextADInt_Core_Util_Internal_WordPress::getSites();
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

        \WP_Mock::wpFunction('wp_get_sites', array(
                'times'  => 1,
                'return' => array($site))
        );

        // call function get_sites();
        $actual = NextADInt_Core_Util_Internal_WordPress::getSites();
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

        \WP_Mock::wpFunction('get_blog_details', array(
                'times'  => 1,
                'return' => array($site))
        );

        // call function get_sites();
        $actual = NextADInt_Core_Util_Internal_WordPress::getSite();
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

        \WP_Mock::wpFunction('get_site', array(
                'times'  => 1,
                'return' => array($site))
        );

        // call function get_sites();
        $actual = NextADInt_Core_Util_Internal_WordPress::getSite();
        $this->assertEquals(array($site), $actual);
    }
}