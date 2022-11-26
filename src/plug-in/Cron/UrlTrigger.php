<?php

namespace Dreitier\Nadi\Cron;

use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Synchronization\ActiveDirectorySynchronizationService;
use Dreitier\Nadi\Synchronization\WordPressSynchronizationService;
use Dreitier\Util\ArrayUtil;
use Dreitier\WordPress\Multisite\Configuration\Service;

/**
 * UrlTrigger processes HttpRequests and validates the auth codes for Sync to AD and Sync to WordPress
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class UrlTrigger
{
	const TASK = 'next_ad_int-task';
	const AUTH_CODE = 'auth-code';
	const USER_ID = 'userid';

	const SYNC_TO_WORDPRESS = 'sync-to-wordpress';
	const SYNC_TO_AD = 'sync-to-ad';

	/* @var Service */
	private $multisiteConfigurationService;

	/* @var ActiveDirectorySynchronizationService */
	private $syncToActiveDirectory;

	/* @var WordPressSynchronizationService */
	private $syncToWordPress;

	/**
	 * @param Service $configuration
	 * @param ActiveDirectorySynchronizationService $syncToActiveDirectory
	 * @param WordPressSynchronizationService $syncToWordPress
	 */
	public function __construct(Service                                       $configuration,
								ActiveDirectorySynchronizationService $syncToActiveDirectory,
								WordPressSynchronizationService       $syncToWordPress)
	{
		$this->multisiteConfigurationService = $configuration;
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
	 * @throws \Exception
	 */
	public function httpRequestEntryPoint()
	{
		// dont unescape $_POST because only numbers and base64 values will be accessed
		$success = $this->processHttpRequest($_POST);

		// NADI-636 return json to prevent user being redirected to wp-login.php
		if ($success) {
			wp_send_json(array('success' => true));
		} else {
			wp_send_json(array('success' => false, 'message' => 'Please refer to your NADI log file'), 500);
		}
	}

	/**
	 * Execute synchronize, syncToAd or nothing - depending on $_POST parameters.
	 *
	 * @param array $post array content of $_POST
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function processHttpRequest($post)
	{
		$syncMode = self::getSyncMode($post);
		if (false === $syncMode) {
			return false;
		}

		$authCode = ArrayUtil::get(self::AUTH_CODE, $post, false);
		if (!$this->validateAuthCode($authCode, $syncMode)) {
			return false;
		}

		$userId = ArrayUtil::get(self::USER_ID, $post, false);
		return $this->dispatchAction($userId, $syncMode);
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
		$task = ArrayUtil::get(self::TASK, $post, false);

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
			$optionName = Options::SYNC_TO_WORDPRESS_AUTHCODE;
		} else {
			$optionName = Options::SYNC_TO_AD_AUTHCODE;
		}

		$expectedCode = $this->multisiteConfigurationService->getOptionValue($optionName);
		if (!next_ad_int_hash_equals($authCode, $expectedCode)) {
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
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function dispatchAction($userId, $syncMode)
	{
		if ($syncMode === 1) {
			return $this->syncToWordPress->synchronize();
		} else {
			return $this->syncToActiveDirectory->synchronize($userId);
		}
	}
}