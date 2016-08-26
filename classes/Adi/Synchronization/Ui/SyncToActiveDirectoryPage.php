<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_Synchronization_Ui_SyncToActiveDirectoryPage')) {
	return;
}

/**
 * Controller for manual synchronization of WordPress profiles back to the connected Active Directory
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 *
 * @access public
 */
class Adi_Synchronization_Ui_SyncToActiveDirectoryPage extends Multisite_View_Page_Abstract
{
	const TITLE = 'Sync to AD';
	const SLUG = 'sync_to_ad';
	const AJAX_SLUG = null;
	const CAPABILITY = 'manage_options';
	const TEMPLATE = 'sync-to-ad.twig';
	const NONCE = 'Active Directory Integration Sync to AD Nonce';

	/* @var Adi_Synchronization_ActiveDirectory $syncToActiveDirectory */
	private $syncToActiveDirectory;

	/** @var Multisite_Configuration_Service */
	private $configuration;

	private $result;
	private $log;


	/**
	 * @param Multisite_View_TwigContainer $twigContainer
	 * @param Adi_Synchronization_ActiveDirectory $syncToActiveDirectory
	 * @param Multisite_Configuration_Service $configuration
	 */
	public function __construct(Multisite_View_TwigContainer $twigContainer,
								Adi_Synchronization_ActiveDirectory $syncToActiveDirectory,
								Multisite_Configuration_Service $configuration)
	{
		parent::__construct($twigContainer);

		$this->syncToActiveDirectory = $syncToActiveDirectory;
		$this->configuration = $configuration;
	}

	/**
	 * Get the page title.
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return esc_html__(self::TITLE, NEXT_AD_INT_I18N);
	}

	/**
	 * Render the page for an admin.
	 */
	public function renderAdmin()
	{
		$this->checkCapability();

		// get data from $_POST
		$params = $this->processData($_POST);
		$params['nonce'] = wp_create_nonce(self::NONCE); // add nonce for security
		$params['authCode'] = $this->configuration->getOptionValue(Adi_Configuration_Options::SYNC_TO_AD_AUTHCODE);
		$params['blogUrl'] = get_site_url(get_current_blog_id());
		$params['message'] = $this->result;
		$params['log'] = $this->log;

		// render
		$this->display(self::TEMPLATE, $params);
	}

	/**
	 * This method reads the $_POST array and triggers Sync to AD (if the authentication code from $_POST is correct)
	 *
	 * @return array
	 */
	public function processData($post)
	{
		if (!isset($post['syncToAd'])) {	//TODO bulkSyncBack ist nicht mehr erlaubt in $_POST
			return array();
		}

		$security =  Core_Util_ArrayUtil::get('security', $post, '');
		if (!wp_verify_nonce($security, self::NONCE)) {
			$message = __('You do not have sufficient permissions to access this page.', NEXT_AD_INT_I18N);
			wp_die($message);
		}

		$userId = Core_Util_ArrayUtil::get('userid', $post, '');

		ob_start();
		Core_Logger::displayAndLogMessages();
		$result = $this->syncToActiveDirectory->synchronize($userId);

		Core_Logger::logMessages();
		$this->log = ob_get_contents();
		ob_end_clean();

		// split the string and put the single log messages into an array
		$this->log = explode("<br />",$this->log);

		if ($result) {
			$this->result = esc_html__('Sync to AD succeeded.', NEXT_AD_INT_I18N);
		} else {
			$this->result = esc_html__('Sync to AD failed.', NEXT_AD_INT_I18N);
		}

		return array(
			'status' => $result
		);
	}

	/**
	 * Include JavaScript und CSS Files into WordPress.
	 *
	 * @param $hook
	 */
	public function loadAdminScriptsAndStyle($hook)
	{
		if (strpos($hook, self::getSlug()) === false) {
			return;
		}

		wp_enqueue_style('adi2', ADI_URL . '/css/adi2.css', array(), Multisite_Ui::VERSION_CSS);
	}

	/**
	 * Get the menu slug for the page.
	 *
	 * @return string
	 */
	public function getSlug()
	{
		return ADI_PREFIX . self::SLUG;
	}

	/**
	 * Get the slug for post requests.
	 *
	 * @return null
	 */
	public function wpAjaxSlug()
	{
		return self::AJAX_SLUG;
	}

	/**
	 * Get the current capability to check if the user has permission to view this page.
	 *
	 * @return string
	 */
	protected function getCapability()
	{
		return self::CAPABILITY;
	}

	/**
	 * Get the current nonce value.
	 *
	 * @return mixed
	 */
	protected function getNonce()
	{
		return self::NONCE;
	}
}
