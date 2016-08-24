<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage')) {
	return;
}

/**
 * Controller for manual synchronizing of Active Directory users into the current WordPress instance
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 *
 * @access public
 */
class NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage extends NextADInt_Multisite_View_Page_Abstract
{
	const TITLE = 'Sync to WordPress';
	const SLUG = 'sync_to_wordpress';
	const AJAX_SLUG = null;
	const CAPABILITY = 'manage_options';
	const TEMPLATE = 'sync-to-wordpress.twig';
	const NONCE = 'Active Directory Integration Sync to WordPress Nonce';


	/* @var NextADInt_Adi_Synchronization_WordPress $syncToWordPress*/
	private $syncToWordPress;
	/** @var NextADInt_Multisite_Configuration_Service $configuration */
	private $configuration;

	private $result;
	private $log;

	/**
	 * @param NextADInt_Multisite_View_TwigContainer            $twigContainer
	 * @param NextADInt_Adi_Synchronization_WordPress        $syncToWordPress
	 * @param NextADInt_Multisite_Configuration_Service $configuration
	 */
	public function __construct(NextADInt_Multisite_View_TwigContainer $twigContainer,
								NextADInt_Adi_Synchronization_WordPress $syncToWordPress,
								NextADInt_Multisite_Configuration_Service $configuration)
	{
		parent::__construct($twigContainer);

		$this->syncToWordPress = $syncToWordPress;
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

		$params = $this->processData($_POST);
		// add nonce for security
		$params['nonce'] = wp_create_nonce(self::NONCE);
		$params['authCode'] = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_AUTHCODE);
		$params['blogUrl'] = get_site_url(get_current_blog_id());
		$params['message'] = $this->result;
		$params['log'] = $this->log;

		$this->display(self::TEMPLATE, $params);
	}

	/**
	 * This method reads the $_POST array and triggers Sync to Wordpress (if the authentication code from $_POST is correct)
	 *
	 * @param array $post
	 * @return array
	 */
	public function processData($post)
	{
		if (!isset($post['syncToWordpress'])) {	// TODO bulkImport darf nicht in POST stehen
			return array();
		}

		$security = NextADInt_Core_Util_ArrayUtil::get('security', $post, '');
		if (!wp_verify_nonce($security, self::NONCE)) {
			$message = esc_html__('You do not have sufficient permissions to access this page.', NEXT_AD_INT_I18N);
			wp_die($message);
		}

		ob_start();
		NextADInt_Core_Logger::displayAndLogMessages();
		$status = $this->syncToWordPress->synchronize();
		NextADInt_Core_Logger::logMessages();
		$this->log = ob_get_contents();
		ob_end_clean();

		//Split the String and put the single log messages into an array
		$this->log = explode("<br />",$this->log);


		if ($status) {
			$this->result = esc_html__('Sync to WordPress succeeded.', NEXT_AD_INT_I18N);
		} else {
			$this->result = esc_html__('Sync to WordPress failed.', NEXT_AD_INT_I18N);
		}

		return array(
			'status' => $status
		);
	}

	/**
	 * Include JavaScript und CSS Files into WordPress.
	 *
	 * @param string $hook
	 */
	public function loadAdminScriptsAndStyle($hook)
	{
		if (strpos($hook, self::getSlug()) === false) {
			return;
		}

		wp_enqueue_style('next_ad_int', NEXT_AD_INT_URL . '/css/next_ad_int.css', array(), NextADInt_Multisite_Ui::VERSION_CSS);
	}

	/**
	 * Get the menu slug of the page.
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
	 * @return mixed
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
