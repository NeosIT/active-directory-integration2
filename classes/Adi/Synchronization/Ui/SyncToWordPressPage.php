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
		return esc_html__('Sync to WordPress', 'next-active-directory-integration');
	}

	/**
	 * Render the page for an admin.
	 */
	public function renderAdmin()
	{
		$this->checkCapability();

        // dont unescape $_POST because only base64 values will be accessed
		$params = $this->processData($_POST);
		// add nonce for security
		$params['nonce'] = wp_create_nonce(self::NONCE);
		$params['authCode'] = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_AUTHCODE);
		$params['blogUrl'] = get_site_url(get_current_blog_id());
		$params['message'] = $this->result;
		$params['log'] = $this->log;
        $params['i18n'] = array(
            'title' => __('Sync To WordPress', 'next-active-directory-integration'),
            'descriptionLine1' => __('If you want to trigger Sync to WordPress, you must know the URL to the index.php of your blog:', 'next-active-directory-integration'),
            'descriptionLine2' => __('Settings like auth-code etc. depends on the current blog. So be careful which blog you are using. Here are some examples:', 'next-active-directory-integration'),
            'repeatAction' => __('Repeat AD to WordPress synchronization', 'next-active-directory-integration'),
            'startAction' => __('Start AD to WordPress synchronization', 'next-active-directory-integration')
        );

		$i18n = array(
            'title' => __('Sync To WordPress', 'next-active-directory-integration'),
            'descriptionLine1' => __('If you want to trigger Sync to WordPress, you must know the URL to the index.php of your blog:', 'next-active-directory-integration'),
            'descriptionLine2' => __('Settings like auth-code etc. depends on the current blog. So be careful which blog you are using. Here are some examples:', 'next-active-directory-integration'),
            'repeatAction' => __('Repeat AD to WordPress synchronization', 'next-active-directory-integration'),
            'startAction' => __('Start AD to WordPress synchronization', 'next-active-directory-integration')
        );
		$params['i18n'] = NextADInt_Core_Util_EscapeUtil::escapeHarmfulHtml($i18n);

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
			$message = esc_html__('You do not have sufficient permissions to access this page.', 'next-active-directory-integration');
			wp_die($message);
		}

		ob_start();
		NextADInt_Core_Logger::logMessages();
		$status = $this->syncToWordPress->synchronize();
		$this->log = ob_get_contents();
		ob_end_clean();

		//Split the String and put the single log messages into an array
		$this->log = explode("<br />",$this->log);
		$this->log = NextADInt_Core_Util_StringUtil::transformLog($this->log);


		if ($status) {
			$this->result = esc_html__('Sync to WordPress succeeded.', 'next-active-directory-integration');
		} else {
			$this->result = esc_html__('Sync to WordPress failed.', 'next-active-directory-integration');
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

		wp_enqueue_style('next_ad_int_bootstrap_min_css', NEXT_AD_INT_URL . '/css/bootstrap.min.css', array(), NextADInt_Multisite_Ui::VERSION_CSS);
		wp_enqueue_style('next_ad_int_bootstrap_theme_min_css', NEXT_AD_INT_URL . '/css/bootstrap-theme.min.css', array(), NextADInt_Multisite_Ui::VERSION_CSS);
		wp_enqueue_script('next_ad_int_bootstrap_min_js', NEXT_AD_INT_URL . '/js/bootstrap.min.js', array(), NextADInt_Multisite_Ui::VERSION_PAGE_JS);
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
