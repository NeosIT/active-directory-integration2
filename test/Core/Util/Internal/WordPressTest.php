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

        \WP_Mock::wpFunction('get_sites', array(
                'times'  => 1)
        );

        // call function get_sites();
        NextADInt_Core_Util_Internal_WordPress::getSites();
    }

    /**
     * @test
     */
    public function getSites_withWordPress4_5_shouldCallWpGetSites() {
        global $wp_version;
        $wp_version = '4.5.3';

        \WP_Mock::wpFunction('wp_get_sites', array(
                'times'  => 1)
        );

        // call function get_sites();
        NextADInt_Core_Util_Internal_WordPress::getSites();
    }
}