<?php
namespace Dreitier\Util;

use Dreitier\WordPress\WordPressSiteRepository;

/**
 * Removes all plugin settings
 */
class Uninstaller
{
    public function getAllOptionTables() {
        global $wpdb;

        if (!is_multisite()) {
            return array($wpdb->options);
        }

        $sites = WordPressSiteRepository::getSites();
        $firstOptionTable = $wpdb->base_prefix . 'options';
        $tables = array($firstOptionTable);

        for ($i = 2; $i <= sizeof($sites); $i++) {
            $optionTable = $wpdb->base_prefix . $i . '_options';
            array_push($tables, $optionTable);
        }

        return $tables;
    }

    public function deleteAllEntriesFromTable($table, $keyName) {
        global $wpdb;
        $prefix =NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX;

        $sql = "DELETE FROM $table WHERE $keyName LIKE '$prefix%';";
        $wpdb->query($sql);
    }

    public function removePluginSettings() {
        global $wpdb;

        $tables = $this->getAllOptionTables();
        foreach($tables as $table) {
            $this->deleteAllEntriesFromTable($table, 'option_name');
        }

        if (is_multisite()) {
            $this->deleteAllEntriesFromTable($wpdb->sitemeta, 'meta_key');
        }

        $this->deleteAllEntriesFromTable($wpdb->usermeta, 'meta_key');
    }
}