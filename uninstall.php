<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (!defined('WP_UNINSTALL_PLUGIN')) {
	wp_die('Plugin uninstalling is not authorized.');
}

require_once 'constants.php';
require_once ADI_PATH . '/Autoloader.php';
$autoLoader = new Adi_Autoloader();

global $wpdb;
$tables = array($wpdb->options);
if (is_multisite()){
	$tables = array_map($tables, $wpdb->blogs);
}

$prefixes = array(
	ADI_PREFIX . Multisite_Configuration_Persistence_BlogConfigurationRepository::PREFIX,
	ADI_PREFIX . Adi_Authentication_Persistence_FailedLoginRepository::PREFIX_LOGIN_ATTEMPTS,
	ADI_PREFIX . Adi_Authentication_Persistence_FailedLoginRepository::PREFIX_BLOCKED_TIME,
	ADI_PREFIX . Multisite_Configuration_Persistence_ProfileConfigurationRepository::PREFIX_VALUE,
	ADI_PREFIX . Multisite_Configuration_Persistence_ProfileConfigurationRepository::PREFIX_PERMISSION,
	ADI_PREFIX . Multisite_Configuration_Persistence_ProfileRepository::PREFIX_NAME,
	ADI_PREFIX . Multisite_Configuration_Persistence_ProfileRepository::PREFIX_DESCRIPTION,
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