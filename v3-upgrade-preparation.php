<?php

function nadi_v3_upgrade_message()
{
	$summary = 'https://active-directory-wp.com/2022/12/02/important-breaking-changes-with-nadi-3-0-0/';
	$milestone = 'https://github.com/NeosIT/active-directory-integration2/milestone/11';

	$r = sprintf(__('<strong>Warning!</strong> The upcoming major version 3.0.0 of Next Active Directory Integration requires PHP 8.0 to work. Version 3.0.0 will be released around March 1st, 2023.<br />Please read the <a href="%s">next major version\'s summary</a> and the full <a href="%s">milestone description</a> carefully.'), $summary, $milestone);
	$affectedPremiumExtensions = array();

	foreach (get_plugins() as $path => $plugin) {
		if (strpos($path, 'nadiext') !== FALSE || (isset($plugin['Name']) && (strpos($plugin['Name'], 'Next Active Directory Integration:') !== FALSE))) {
			$affectedPremiumExtensions[] = $plugin['Name'];
		}
	}

	if (sizeof($affectedPremiumExtensions) > 0) {
		$r .= sprintf(__('<br /><br />We did our best to make all NADI Premium Extensions downward compatible. But nevertheless you might have to upgrade the following NADI Premium Extensions might require an upgrade to be usable with NADI 3.0.0 and later: <ul><li>%s</li></ul> '), implode("</li><li>", $affectedPremiumExtensions));
	}

	return $r;
}

function nadi_v3_must_prepare($plugin_data)
{
	// always show notification in development environment
	if (defined('NEXT_ACTIVE_DIRECTORY_INTEGRATION_DEV_ENVIRONMENT')) {
		return true;
	}

	$newVersion = $plugin_data['new_version'];
	$newVersionIsMajorUpgrade = version_compare('3.0.0', $newVersion, '>=');

	$installedVersion = $plugin_data['Version'];
	$installedVersionIsBeforeMajorUpgrade = version_compare($installedVersion, '3.0.0',  '<');

	if ($newVersionIsMajorUpgrade && $installedVersionIsBeforeMajorUpgrade) {
		return true;
	}

	return false;
}

function nadi_v3_single_site_notification($plugin_data, $response)
{
	if (!nadi_v3_must_prepare($plugin_data)) {
		return;
	}

	$update_notice = '</p><div class="wc_plugin_upgrade_notice">';
	$update_notice .= nadi_v3_upgrade_message();
	$update_notice .= '</div>';

	echo wp_kses_post($update_notice);
}

function nadi_v3_multisite_notification($file, $plugin)
{
	if (!nadi_v3_must_prepare($plugin)) {
		return;
	}

	if (!is_multisite()) {
		return;
	}

	$wp_list_table = _get_list_table('WP_Plugins_List_Table');
	printf(
		'<tr class="plugin-update-tr"><td colspan="%s" class="plugin-update update-message notice inline notice-warning notice-alt"><div class="update-message"><h4 style="margin: 0; font-size: 14px;">%s</h4>%s</div></td></tr>',
		$wp_list_table->get_column_count(),
		$plugin['Name'],
		nadi_v3_upgrade_message()
	);
}

add_action('in_plugin_update_message-next-active-directory-integration/index.php', 'nadi_v3_single_site_notification', 10, 2);
add_action('after_plugin_row_wp-next-active-directory-integration/index.php', 'nadi_v3_multisite_notification', 10, 2);
