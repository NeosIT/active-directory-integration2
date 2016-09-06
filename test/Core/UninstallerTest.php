<?php

/**
 * Created by PhpStorm.
 * User: the@neos-it.local
 * Date: 9/5/16
 * Time: 9:36 AM
 */
class Ut_NextADInt_Core_UninstallerTest extends Ut_BasicTest
{
    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        global $wp_version;
        unset($wp_version);
        global $wpdb;
        unset($wpdb);
        parent::tearDown();
    }

    /**
     * @return NextADInt_Core_Uninstaller| PHPUnit_Framework_MockObject_MockObject
     */
    public function sut($methods = null)
    {
        return $this->getMockBuilder('NextADInt_Core_Uninstaller')
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @test
     */
    public function getAllOptionTables_withSingleSite_returnDefaultTable() {
        global $wpdb;
        $wpdb = (object) array(
            'options' => 'wp_options'
        );

        $sut = $this->sut(null);

        \WP_Mock::wpFunction('is_multisite', array(
            'times'  => 1,
            'return' => false)
        );

        $expected = $sut->getAllOptionTables();
        $this->assertEquals($expected, array('wp_options'));
    }

    /**
     * @test
     */
    public function getAllOptionTables_withMultiSite_returnAllTables() {
        global $wpdb;
        $wpdb = (object)array(
            'base_prefix' => 'wp_'
        );

        $sut = $this->sut();

        \WP_Mock::wpFunction('is_multisite', array(
                'times' => 1,
                'return' => true)
        );

        // NextADInt_Core_Util_Internal_WordPress::getSites() will call wp_get_sites when wp_version == 4.5
        global $wp_version;
        $wp_version = '4.5';
        \WP_Mock::wpFunction('wp_get_sites', array(
                'times' => 1,
                'return' => array('obj1', 'obj2', 'obj3', 'obj4'))
        );

        $expected = $sut->getAllOptionTables();
        $this->assertEquals($expected, array('wp_options', 'wp_2_options', 'wp_3_options', 'wp_4_options'));
    }

    /**
     * @test
     */
    public function deleteAllEntriesFromTable_shouldExecuteSQLQuery() {
        global $wpdb;
        $wpdb = $this->createMockWithMethods('BlueprintClass', array('query'));

        $sut = $this->sut();

        $wpdb->expects($this->once())
            ->method('query')
            ->with("DELETE FROM wp_options WHERE option_name LIKE 'next_ad_int_%';");

        $sut->deleteAllEntriesFromTable('wp_options', 'option_name');
    }

    /**
     * @test
     */
    public function removePluginSettings_fromSingleSite_callMethodsInRightOrder() {
        global $wpdb;
        $wpdb = (object) array(
            'usermeta' => 'wp_usermeta'
        );

        $sut = $this->sut(array('getAllOptionTables', 'deleteAllEntriesFromTable'));

        $sut->expects($this->once())
            ->method('getAllOptionTables')
            ->willReturn(array('wp_options'));

        \WP_Mock::wpFunction('is_multisite', array(
                'times'  => 1,
                'return' => false)
        );

        $sut->expects($this->exactly(2))
            ->method('deleteAllEntriesFromTable')
            ->withConsecutive(
                array('wp_options', 'option_name'),
                array('wp_usermeta', 'meta_key'));

        $sut->removePluginSettings();
    }

    /**
     * @test
     */
    public function removePluginSettings_fromMultiSite_callMethodsInRightOrder() {
        global $wpdb;
        $wpdb = (object) array(
            'sitemeta' => 'wp_sitemeta',
            'usermeta' => 'wp_usermeta'
        );

        $sut = $this->sut(array('getAllOptionTables', 'deleteAllEntriesFromTable'));

        $sut->expects($this->once())
            ->method('getAllOptionTables')
            ->willReturn(array('wp_options', 'wp_2_options'));

        \WP_Mock::wpFunction('is_multisite', array(
                'times'  => 1,
                'return' => true)
        );

        $sut->expects($this->exactly(4))
            ->method('deleteAllEntriesFromTable')
            ->withConsecutive(
                array('wp_options', 'option_name'),
                array('wp_2_options', 'option_name'),
                array('wp_sitemeta', 'meta_key'),
                array('wp_usermeta', 'meta_key'));

        $sut->removePluginSettings();
    }
}
