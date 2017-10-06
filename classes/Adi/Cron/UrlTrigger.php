<?php
if ( ! defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Cron_UrlTrigger')) {
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
class NextADInt_Adi_Cron_UrlTrigger
{
	const TASK = 'next_ad_int-task';
	const AUTH_CODE = 'auth-code';
	const USER_ID = 'userid';

	const SYNC_TO_WORDPRESS = 'sync-to-wordpress';
	const SYNC_TO_AD = 'sync-to-ad';

	/* @var NextADInt_Multisite_Configuration_Service */
	private $configuration;

	/* @var NextADInt_Adi_Synchronization_ActiveDirectory */
	private $syncToActiveDirectory;

	/* @var NextADInt_Adi_Synchronization_WordPress */
	private $syncToWordPress;

	/**
	 * NextADInt_Adi_Cron_UrlTrigger constructor.
	 *
	 * @param NextADInt_Multisite_Configuration_Service $configuration
	 * @param NextADInt_Adi_Synchronization_ActiveDirectory  $syncToActiveDirectory
	 * @param NextADInt_Adi_Synchronization_WordPress  $syncToWordPress
	 */
	public function __construct(NextADInt_Multisite_Configuration_Service $configuration,
								NextADInt_Adi_Synchronization_ActiveDirectory $syncToActiveDirectory,
								NextADInt_Adi_Synchronization_WordPress $syncToWordPress)
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
	    // dont unescape $_POST because only numbers and base64 values will be accessed
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

		$authCode = NextADInt_Core_Util_ArrayUtil::get(self::AUTH_CODE, $post, false);
		if ( ! $this->validateAuthCode($authCode, $syncMode)) {
			return;
		}

		$userId = NextADInt_Core_Util_ArrayUtil::get(self::USER_ID, $post, false);
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
		$task = NextADInt_Core_Util_ArrayUtil::get(self::TASK, $post, false);

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
			$optionName = NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_AUTHCODE;
		} else {
			$optionName = NextADInt_Adi_Configuration_Options::SYNC_TO_AD_AUTHCODE;
		}

		$expectedCode = $this->configuration->getOptionValue($optionName);
		if ( ! next_ad_int_hash_equals($authCode, $expectedCode)) {
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