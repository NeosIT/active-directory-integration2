<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Configuration_Import_Ui_ExtendPluginList')) {
	return;
}

/**
 * NextADInt_Adi_Configuration_Import_Ui_ExtendPluginList extends the network or blog plug-in list with an additional option for
 * exporting the previous ADI configuration.
 * As multisites has not been officially supported by ADI v1 we do <strong>not</strong> support the export of a configurations
 * ADI 1.x. network installation with blog activation.
 *
 * @author Christopher Klein <ckl@neos-it.de>
 * @access public
 */
class NextADInt_Adi_Configuration_Import_Ui_ExtendPluginList
{
	const ACTION = 'adiv1_configuration_export.txt';

	const LINE_BREAK = "\r\n";
	const LINE_SEPARATOR = "-----\r\n";

	/**
	 * @var NextADInt_Adi_Configuration_ImportService
	 */
	private $importService;

	/**
	 * NextADInt_Adi_Configuration_Import_Ui_ExtendPluginList constructor.
	 * @param NextADInt_Adi_Configuration_ImportService $importService
	 */
	public function __construct(NextADInt_Adi_Configuration_ImportService $importService)
	{
		$this->importService = $importService;
	}

	public function register()
	{
		// register the actions only for ADI v2 and no other plug-in entry
		add_filter('plugin_action_links_' . NEXT_AD_INT_PLUGIN_FILE, array($this, 'extendPluginActions'), 10, 5);
		add_filter('network_admin_plugin_action_links_' . NEXT_AD_INT_PLUGIN_FILE, array($this, 'extendPluginActions'), 10, 5);

		// make the export available
		add_action('admin_post_' . self::ACTION, array($this, 'exportPreviousConfiguration'));

	}

	/**
	 * Add the "Download ADI v1 settings" link to the plug-in list of WordPress if the current user is blog admin or network admin
	 *
	 * @param $actions
	 * @param $pluginFile
	 * @return mixed
	 */
	public function extendPluginActions($actions, $pluginFile)
	{
		// network export can only be done inside the network dashboard
		$networkExportAllowed = ($this->isNetworkExportAllowed() && is_network_admin());

		if ($networkExportAllowed || $this->isSingleSiteExportAllowed()) {
			$actions['adi_v1_configuration_export'] = '<a href="' . admin_url('admin-post.php?action=' . self::ACTION) . '">' . __('Download ADI v1 configuration', 'next-active-directory-integration') . '</a>';
		}

		return $actions;
	}

	/**
	 * Network export is allowed if it is a network installation and the current user is super admin.
	 *
	 * @return bool
	 */
	public function isNetworkExportAllowed()
	{
		return (is_multisite() && is_super_admin());
	}

	/**
	 * Single site export is allowed if it is <strong>not</strong> a network installation and the current user is admin.
	 * @return bool
	 */
	public function isSingleSiteExportAllowed()
	{
		return (!is_multisite() && is_admin());
	}

	/**
	 * Export the previous configuration based upon the current screen.
	 * <ul>
	 * <li>If the user is super admin and visits the network plugin settings, the multisite configuration can be exported</li>
	 * <li>If the user is blog admin and visits the (single site) plugin settings, the single site configuration can be exported</li>
	 * </ul>
	 */
	public function exportPreviousConfiguration()
	{
		// in a multisite environment the "action_post" hook does not belong to the network dashboard
		// so we can't use is_network_admin for further checking
		if (!($this->isNetworkExportAllowed() || $this->isSingleSiteExportAllowed())) {
			wp_die('Authorization requizred', "", array('response' => 403));
			return;
		}

		$dump = array();

		if ($this->isNetworkExportAllowed()) {
			$dump = $this->dumpNetwork();
		} else {
			$dump = $this->dumpSingleSite();
		}

		$this->sendDump($dump);
	}

	/**
	 * Implode the given dump and send its content to the client. This method exists gracefully.
	 *
	 * @untestable
	 * @access package
	 * @param array $dump
	 */
	function sendDump($dump)
	{
		header('Content-Type: text/plain');
		header('Content-Disposition: attachment; filename=' . self::ACTION);
		header('Pragma: no-cache');

		echo implode(self::LINE_BREAK, $dump);

		exit;

	}

	/**
	 * Convert the provded configuration into a viewable string
	 *
	 * @param array $configuration
	 * @return array
	 */
	public function dumpConfiguration($configuration)
	{
		$r = array();

		foreach ($configuration as $index => $optionDefinition) {
			$r[] = $optionDefinition['option_old'] . " (old) -> " . $optionDefinition['option_new'] . " (new): " . $optionDefinition['value'];
		}

		return $r;
	}

	/**
	 * Create a dump of the current single site configuration
	 * @return array
	 */
	public function dumpSingleSite()
	{
		$r = array();

		$version = $this->importService->getPreviousBlogVersion();
		$r[] = "Single site installation";
		$r[] = self::LINE_SEPARATOR;

		if (!NextADInt_Adi_Configuration_ImportService::isPreviousVersion($version)) {
			$r[] = "No previous version of NADI found.";
			return $r;
		}

		$r[] = "Version: $version";
		$r[] = self::LINE_SEPARATOR;

		return array_merge($r, $this->dumpConfiguration($this->importService->getPreviousConfiguration(null, $version)));
	}

	/**
	 * Dump the multisite configuration. For > 1.1.5 the network configuration is exported, <= 1.1.5 the site configuration is used.
	 * @return array
	 */
	public function dumpNetwork()
	{
		$r = array();

		$r[] = "WordPress Multisite environment";
		$r[] = self::LINE_SEPARATOR;

		$version = $this->importService->getPreviousNetworkVersion();

		if (NextADInt_Adi_Configuration_ImportService::isPreviousVersion($version)) {
			$r[] = "Version: $version (global network installation)\r\n";
			$r[] = self::LINE_SEPARATOR;

			return array_merge($r, $this->dumpConfiguration($this->importService->getPreviousConfiguration(0, $version)));
		}

		// iterate over each of the installed sites of this network
		$sites = NextADInt_Core_Util_Internal_WordPress::getSites();

		foreach ($sites as $site) {
			$blogId = $site['blog_id'];
			$r[] = "Blog ID " . $blogId . " (" . $site['domain'] . " - " . $site['path'] . ")\r\n";
			$r[] = self::LINE_SEPARATOR;

			$version = $this->importService->getPreviousSiteVersion($blogId);

			if (!$version) {
				$r[] = "  No NADI installed.";
				continue;
			}

			$r = array_merge($r, $this->dumpConfiguration($blogId));

		}

		return $r;
	}
}