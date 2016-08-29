<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (!defined('WP_UNINSTALL_PLUGIN')) {
	wp_die('Plugin uninstalling is not authorized.');
}

require_once 'constants.php';
require_once NEXT_AD_INT_PATH . '/Autoloader.php';
$autoLoader = new NextADInt_Adi_Autoloader();
$autoLoader->register();

global $wpdb;
$prefix = ADI_PREFIX;

// delete entries from all options tables
if (is_multisite()){
    // add options table for the first blog
    $tables = array($wpdb->base_prefix . 'options');

    // get all sites
    global $wp_version;
    if ( version_compare( $wp_version, '4.6.0', '>=')) {
        $sites = get_sites();
    } else {
        $sites = wp_get_sites();
    }

    // add all other options tables
    for ($i = 2; $i <= sizeof($sites); $i++) {
        array_push($tables, $wpdb->base_prefix . $i . '_options');
    }
} else {
    $tables = array($wpdb->options);
}

foreach($tables as $table) {
    $sql = "DELETE FROM $table WHERE option_name LIKE '$prefix%';";
    $wpdb->query($sql);
}

// delete entries from sitemeta
if (is_multisite()) {
    $sql = "DELETE FROM $wpdb->sitemeta WHERE meta_key LIKE '$prefix%';";
    $wpdb->query($sql);
}

// delete all usermeta entries
$sql = "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE '$prefix%';";
$wpdb->query($sql);