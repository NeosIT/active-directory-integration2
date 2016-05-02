<?php
if ( ! defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_Cron_UrlTrigger')) {
	return;
}

/**
* ADI_Cron_UrlTrigger
*
* ADI_Cron_UrlTrigger processes HttpRequests and validates the auth codes for Sync to AD and Sync to WordPress
*
* @author Tobias Hellmann <the@neos-it.de>
* @access public
*/
class Adi_Cron_UrlTrigger
{
	const TASK = 'adi2-task';
	const AUTH_CODE = 'auth-code';
	const USER_ID = 'userid';

	const SYNC_TO_WORDPRESS = 'sync-to-wordpress';
	const SYNC_TO_AD = 'sync-to-ad';

	/* @var Multisite_Configuration_Service */
	private $configuration;

	/* @var Adi_Synchronization_ActiveDirectory */
	private $syncToActiveDirectory;

	/* @var Adi_Synchronization_WordPress */
	private $syncToWordPress;

	/**
	 * Adi_Cron_UrlTrigger constructor.
	 *
	 * @param Multisite_Configuration_Service $configuration
	 * @param Adi_Synchronization_ActiveDirectory  $syncToActiveDirectory
	 * @param Adi_Synchronization_WordPress  $syncToWordPress
	 */
	public function __construct(Multisite_Configuration_Service $configuration,
								Adi_Synchronization_ActiveDirectory $syncToActiveDirectory,
								Adi_Synchronization_WordPress $syncToWordPress)
	{
		$this->configuration = $configuration;
		$this->syncToActiveDirectory = $syncToActiveDirectory;
		$this->syncToWordPress = $syncToWordPress;
	}

	/**
	 * Register the url listener.
	 */
	public function register()
	{
		add_action('init', array($this, 'httpRequestEntryPoint'));
	}

	/**
	 * Get POST-values and delegate them to the processHttpRequest method.
	 */
	public function httpRequestEntryPoint()
	{
		$this->processHttpRequest($_POST);
	}

	/**
	 * Execute synchronize, syncToAd or nothing - depending on $_POST parameters.
	 *
	 * @param array $post array content of $_POST
	 */
	public function processHttpRequest($post)
	{
		$syncMode = self::getSyncMode($post);
		if (false === $syncMode) {
			return;
		}

		$authCode = Core_Util_ArrayUtil::get(self::AUTH_CODE, $post, false);
		if ( ! $this->validateAuthCode($authCode, $syncMode)) {
			return;
		}

		$userId = Core_Util_ArrayUtil::get(self::USER_ID, $post, false);
		$this->dispatchAction($userId, $syncMode);
	}

	/**
	 * Get the sync mode (import or sync) from $post
	 *
	 * @param array $post
	 *
	 * @return bool|int
	 */
	public static function getSyncMode($post)
	{
		$task = Core_Util_ArrayUtil::get(self::TASK, $post, false);

		switch ($task) {
			case self::SYNC_TO_WORDPRESS:
				return 1;
			case self::SYNC_TO_AD:
				return 2;
			default:
				return false;
		}
	}

	/**
	 * Validate the auth code for the given sync mode.
	 *
	 * @param string $authCode
	 * @param string $syncMode
	 *
	 * @return bool
	 */
	public function validateAuthCode($authCode, $syncMode)
	{
		if ($syncMode === 1) {
			$optionName = Adi_Configuration_Options::SYNC_TO_WORDPRESS_AUTHCODE;
		} else {
			$optionName = Adi_Configuration_Options::SYNC_TO_AD_AUTHCODE;
		}

		$expectedCode = $this->configuration->getOptionValue($optionName);
		if ( ! hash_equals($authCode, $expectedCode)) {
			$this->output('AuthCode is not correct.');

			return false;
		}

		return true;
	}

	/**
	 * Delegate param to echo. This method is necessary for unit testing.
	 *
	 * @param string $value
	 */
	public function output($value)
	{
		echo $value;
	}

	/**
	 * Call the syncToAd or synchronize method - depending on the sync mode.
	 *
	 * @param int $userId
	 * @param string $syncMode
	 */
	public function dispatchAction($userId, $syncMode)
	{
		if ($syncMode === 1) {
			$this->syncToWordPress->synchronize();
		} else {
			$this->syncToActiveDirectory->synchronize($userId);
		}
	}
}