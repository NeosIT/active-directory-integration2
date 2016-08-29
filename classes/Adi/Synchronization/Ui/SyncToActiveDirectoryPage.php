<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Synchronization_Ui_SyncToActiveDirectoryPage')) {
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
class NextADInt_Adi_Synchronization_Ui_SyncToActiveDirectoryPage extends NextADInt_Multisite_View_Page_Abstract
{
	const TITLE = 'Sync to AD';
	const SLUG = 'sync_to_ad';
	const AJAX_SLUG = null;
	const CAPABILITY = 'manage_options';
	const TEMPLATE = 'sync-to-ad.twig';
	const NONCE = 'Active Directory Integration Sync to AD Nonce';

	/* @var NextADInt_Adi_Synchronization_ActiveDirectory $syncToActiveDirectory */
	private $syncToActiveDirectory;

	/** @var NextADInt_Multisite_Configuration_Service */
	private $configuration;

	private $result;
	private $log;


	/**
	 * @param NextADInt_Multisite_View_TwigContainer $twigContainer
	 * @param NextADInt_Adi_Synchronization_ActiveDirectory $syncToActiveDirectory
	 * @param NextADInt_Multisite_Configuration_Service $configuration
	 */
	public function __construct(NextADInt_Multisite_View_TwigContainer $twigContainer,
								NextADInt_Adi_Synchronization_ActiveDirectory $syncToActiveDirectory,
								NextADInt_Multisite_Configuration_Service $configuration)
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
		$params['authCode'] = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SYNC_TO_AD_AUTHCODE);
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

		$security =  NextADInt_Core_Util_ArrayUtil::get('security', $post, '');
		if (!wp_verify_nonce($security, self::NONCE)) {
			$message = __('You do not have sufficient permissions to access this page.', NEXT_AD_INT_I18N);
			wp_die($message);
		}

		$userId = NextADInt_Core_Util_ArrayUtil::get('userid', $post, '');

		ob_start();
		NextADInt_Core_Logger::displayAndLogMessages();
		$result = $this->syncToActiveDirectory->synchronize($userId);

		NextADInt_Core_Logger::logMessages();
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

		wp_enqueue_style('next_ad_int', NEXT_AD_INT_URL . '/css/next_ad_int.css', array(), NextADInt_Multisite_Ui::VERSION_CSS);
	}

	/**
	 * Get the menu slug for the page.
	 *
	 * @return string
	 */
	public function getSlug()
	{
		return NEXT_AD_INT_PREFIX . self::SLUG;
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
