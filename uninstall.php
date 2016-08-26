<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (!defined('WP_UNINSTALL_PLUGIN')) {
	wp_die('Plugin uninstalling is not authorized.');
}

require_once 'init-autoloader.php';

global $wpdb;
$tables = array($wpdb->options);
if (is_multisite()){
	$tables = array_map($tables, $wpdb->blogs);
}

$prefixes = array(
	NEXT_AD_INT_PREFIX . Multisite_Configuration_Persistence_BlogConfigurationRepository::PREFIX,
	NEXT_AD_INT_PREFIX . Adi_Authentication_Persistence_FailedLoginRepository::PREFIX_LOGIN_ATTEMPTS,
	NEXT_AD_INT_PREFIX . Adi_Authentication_Persistence_FailedLoginRepository::PREFIX_BLOCKED_TIME,
	NEXT_AD_INT_PREFIX . Multisite_Configuration_Persistence_ProfileConfigurationRepository::PREFIX_VALUE,
	NEXT_AD_INT_PREFIX . Multisite_Configuration_Persistence_ProfileConfigurationRepository::PREFIX_PERMISSION,
	NEXT_AD_INT_PREFIX . Multisite_Configuration_Persistence_ProfileRepository::PREFIX_NAME,
	NEXT_AD_INT_PREFIX . Multisite_Configuration_Persistence_ProfileRepository::PREFIX_DESCRIPTION,
);

$backupTables = array();

foreach($tables as $table) {
	$backupTable = array();

	foreach($prefixes as $prefix) {
		$values = $wpdb->get_results( "SELECT option_name, option_value FROM $table WHERE option_name LIKE '$prefix%';", 'ARRAY_A' );
		$backupTable = array_merge($backupTable, $values);
		$wpdb->query("DELETE FROM $table WHERE option_name LIKE '$prefix%';");
	}

	$backupTables[$table] = $backupTable;
}

var_dump($backupTables);