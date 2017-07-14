<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Authentication_Persistence_FailedLoginRepository')) {
	return;
}

/**
 * NextADInt_Adi_Authentication_Persistence_FailedLoginRepository stores the failed login attempts and the block time of an user.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @access  public
 */
class NextADInt_Adi_Authentication_Persistence_FailedLoginRepository
{
	const PREFIX = 'fl_';
	const PREFIX_LOGIN_ATTEMPTS = 'la_';
	const PREFIX_BLOCKED_TIME = 'bt_';

	/* @var Logger */
	private $logger;

	/**
	 */
	public function __construct()
	{
		$this->logger = NextADInt_Core_Logger::getLogger();
	}

	/**
	 * Generate the WordPress option name from the type and the username.
	 *
	 * @param int $loginAttempts
	 * @param string $username
	 *
	 * @return string
	 */
	protected function getOptionName($loginAttempts, $username)
	{
		$prefix = $loginAttempts ? self::PREFIX_LOGIN_ATTEMPTS : self::PREFIX_BLOCKED_TIME;
		return NEXT_AD_INT_PREFIX . self::PREFIX . $prefix . '_' . $this->encodeUsername($username);
	}

	/**
	 * Block the user for a number of seconds.
	 *
	 * @param string $username
	 * @param int $seconds
	 *
	 * @return bool
	 */
	public function blockUser($username, $seconds)
	{
		$blockTime = $this->getCurrentTime() + $seconds;

		return $this->persistBlockUntil($username, $blockTime);
	}

	/**
	 * Is the user blocked?
	 *
	 * @param string $username
	 *
	 * @return bool
	 */
	public function isUserBlocked($username)
	{
		$blockTime = $this->findBlockUntil($username);
		$currentTime = $this->getCurrentTime();

		return $blockTime >= $currentTime;
	}

	/**
	 * Increase the number of failed login attempts for the user.
	 *
	 * @param string $username
	 *
	 * @return bool
	 */
	public function increaseLoginAttempts($username)
	{
		$loginAttempts = $this->findLoginAttempts($username);

		return $this->persistLoginAttempts($username, $loginAttempts + 1);
	}

	/**
	 * Unblock the user and reset his login attempts counter.
	 *
	 * @param string $username
	 *
	 * @return bool
	 */
	public function resetUser($username)
	{
		$user = $this->deleteBlockUntil($username);
		$loginAttempts = $this->deleteLoginAttempts($username);

		return $user && $loginAttempts;
	}

	/**
	 * Get the number of seconds until the user is unblocked
	 *
	 * @param string $username
	 *
	 * @return int|null|string
	 */
	public function getSecondsUntilUnblock($username)
	{
		$seconds = $this->findBlockUntil($username) - $this->getCurrentTime() + 1;

		if (0 > $seconds) {
			return 0;
		}

		return $seconds;
	}

	/**
	 * This method should not be called by the outside.
	 * Get the login attempts for the user.
	 *
	 * @param string $username
	 *
	 * @return int|null|string
	 */
	function findLoginAttempts($username)
	{
		$optionName = $this->getOptionName(true, $username);

		/* This function is almost identical to get_option(), except that in multisite,
		it returns the network-wide option. For non-multisite installs, it uses get_option. */
		return get_site_option($optionName, 0);
	}

	/**
	 * This method should not be called by the outside.
	 * Set the number of login attempts for the user.
	 *
	 * @param string $username
	 * @param int $loginAttempts
	 *
	 * @return bool
	 */
	function persistLoginAttempts($username, $loginAttempts)
	{
		$optionName = $this->getOptionName(true, $username);

		/* This function is essentially the same as update_option() but works network wide when using WP Multisite. */
		return update_site_option($optionName, $loginAttempts);
	}

	/**
	 * Delete the login attempts option for $username.
	 *
	 * @param string $username
	 *
	 * @return bool
	 */
	function deleteLoginAttempts($username)
	{
		$optionName = $this->getOptionName(true, $username);

		/* Essentially the same as delete_option() but works network wide when using WP Multisite. */
		return delete_site_option($optionName);
	}

	/**
	 * This method should not be called by the outside.
	 * Get the unix time 'block_until' for the user.
	 *
	 * @param string $username
	 *
	 * @return int|null|string
	 */
	function findBlockUntil($username)
	{
		$optionName = $this->getOptionName(false, $username);

		/* This function is almost identical to get_option(), except that in multisite,
		it returns the network-wide option. For non-multisite installs, it uses get_option. */
		return get_site_option($optionName, 0);
	}

	/**
	 * This method should not be called by the outside.
	 * Set the unix time 'block_until' for the user.
	 *
	 * @param string $username
	 * @param int $blockUntil
	 *
	 * @return bool
	 */
	public function persistBlockUntil($username, $blockUntil)
	{
		$optionName = $this->getOptionName(false, $username);

		/* This function is essentially the same as update_option() but works network wide when using WP Multisite. */
		return update_site_option($optionName, $blockUntil);
	}

	/**
	 * Delete the 'block time until' value option for $username.
	 *
	 * @param string $username
	 *
	 * @return bool
	 */
	function deleteBlockUntil($username)
	{
		$optionName = $this->getOptionName(false, $username);

		/* Essentially the same as delete_option() but works network wide when using WP Multisite. */
		return delete_site_option($optionName);
	}

	/**
	 * This method should not be called by the outside.
	 * Get the current time. This method is necessary for unit-testing.
	 *
	 * Get current time
	 *
	 * @return int
	 */
	public function getCurrentTime()
	{
		return time();
	}

	/**
	 * Encodes given Username to SHA1
	 *
	 * @param $username
	 * @return string
	 */
	function encodeUsername($username)
	{
		return sha1($username);
	}
}