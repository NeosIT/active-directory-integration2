<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_Mail_Notification')) {
	return;
}

/**
 * Adi_Mail_Notification sends notification emails.
 *
 * Adi_Mail_Notification send notification emails when an user account will be blocked.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @access  public
 */
class Adi_Mail_Notification
{
	/* @var Multisite_Configuration_Service $configuration */
	private $configuration;

	/* @var Ldap_Connection $ldapConnection */
	private $ldapConnection;

	/* @var Logger logger */
	private $logger;

	/**
	 * @param Multisite_Configuration_Service $configuration
	 * @param Ldap_Connection          $ldapConnection
	 */
	public function __construct(Multisite_Configuration_Service $configuration,
								Ldap_Connection $ldapConnection)
	{
		$this->configuration = $configuration;
		$this->ldapConnection = $ldapConnection;

		$this->logger = Logger::getLogger(__CLASS__);
	}

	/**
	 * This method trigger the email dispatch for the user and/or for admins depending on the settings.
	 *
	 * @param string $username
	 */
	public function sendNotifications($username)
	{
		$userNotification = $this->configuration->getOptionValue(Adi_Configuration_Options::USER_NOTIFICATION);

		if ($userNotification) {
			$mail = new Adi_Mail_Message();
			$mail->setUsername($username);
			$mail->setTargetUser(true);
			$this->sendNotification($mail);
		}

		$adminNotification = $this->configuration->getOptionValue(Adi_Configuration_Options::ADMIN_NOTIFICATION);

		if ($adminNotification) {
			$mail = new Adi_Mail_Message();
			$mail->setUsername($username);
			$mail->setTargetUser(false);
			$this->sendNotification($mail);
		}
	}

	/**
	 * Do not call this method from the outside.
	 * Prepares the Adi_Mail_Message and sends it.
	 *
	 * @param Adi_Mail_Message $mail
	 *
	 * @return bool
	 */
	public function sendNotification(Adi_Mail_Message $mail)
	{
		$recipient = $this->getUserMeta($mail->getUsername());

		if (!$recipient) {
			return false;
		}

		$url = get_bloginfo('url');
		$domain = preg_replace('/^(http:\/\/)(.+)\/.*$/i', '$2', $url);
		$name = get_bloginfo('name');
		$blockTime = $this->configuration->getOption(Adi_Configuration_Options::BLOCK_TIME);
		$remoteAddress = Core_Util_ArrayUtil::get('REMOTE_ADDR', $_SERVER, '');

		$mail->setFirstName(Core_Util_ArrayUtil::get('firstName', $recipient, ''));
		$mail->setSecondName(Core_Util_ArrayUtil::get('secondName', $recipient, ''));
		$mail->setEmail(Core_Util_ArrayUtil::get('email', $recipient, ''));
		$mail->setBlogUrl($url);
		$mail->setBlogDomain($domain);
		$mail->setBlogName($name);
		$mail->setBlockTime($blockTime);
		$mail->setRemoteAddress($remoteAddress);

		$success = $this->sendMails($mail);

		return $success;
	}

	/**
	 * Do not call this method from the outside.
	 * Get user attribute values either from WordPress (wp_usermeta) or from the Active Directory (depends on the settings).
	 *
	 * @param string $username
	 *
	 * @return array
	 */
	function getUserMeta($username)
	{
		$autoCreateUser = $this->configuration->getOptionValue(Adi_Configuration_Options::AUTO_UPDATE_USER);

		if ($autoCreateUser && $this->ldapConnection->isConnected()) {
			$values = $this->findADUserAttributeValues($username);
			$source = 'AD';
		} else {
			$values = $this->findWPUserAttributeValues($username);
			$source = 'WordPress';
		}

		if (!$values) {
			$this->logger->warn("Can not get user attributes for user '$username' from " . $source);

			return false;
		}

		return $values;
	}

	/**
	 * Do not call this method from the outside.
	 * Get the user attribute values from the active directory.
	 *
	 * @param string $username
	 *
	 * @return array|bool
	 */
	function findADUserAttributeValues($username)
	{
		$attributes = array('sn', 'givenname', 'mail');
		$userAttributeValues = $this->ldapConnection->findSanitizedAttributesOfUser($username, $attributes);

		return array(
			'email'     => Core_Util_ArrayUtil::get('mail', $userAttributeValues),
			'firstName' => Core_Util_ArrayUtil::get('givenname', $userAttributeValues),
			'lastName'  => Core_Util_ArrayUtil::get('sn', $userAttributeValues),
		);
	}

	/**
	 * Do not call this method from the outside.
	 * Get the user attribute values from WordPress.
	 *
	 * @param string $username
	 *
	 * @return array|bool
	 */
	function findWPUserAttributeValues($username)
	{
		$userId = username_exists($username);
		$userInfo = get_userdata($userId);

		if (!$userInfo) {
			return false;
		}

		return array(
			'email'     => $userInfo->user_email,
			'firstName' => $userInfo->user_firstname,
			'lastName'  => $userInfo->user_lastname,
		);
	}

	/**
	 * Do not call this method from the outside.
	 * Send the emails.
	 *
	 * @param Adi_Mail_Message $mail
	 *
	 * @return bool
	 */
	function sendMails(Adi_Mail_Message $mail)
	{
		$recipients = $this->getRecipients($mail);
		$success = false;

		foreach ($recipients as $recipient) {
			if (!is_email($recipient)) {
				continue;
			}

			if ($this->sendMail($recipient, $mail)) {
				$success = true;
			}
		}

		return $success;
	}

	/**
	 * Do not call this method from the outside.
	 * Send a single email to $recipient.
	 *
	 * @param string           $recipient
	 * @param Adi_Mail_Message $mail
	 *
	 * @return bool
	 */
	function sendMail($recipient, Adi_Mail_Message $mail)
	{
		// send mail to $recipient
		$status = wp_mail($recipient, $mail->getSubject(), $mail->getBody(), $mail->getHeader());

		$target = $mail->getTargetUser() ? 'User' : 'Admin';
		$username = $mail->getUsername();

		if ($status) {
			$this->logger->info("$target notification mail for blocked user account '$username' was send.");
		} else {
			$this->logger->warn("$target notification mail for blocked user account '$username' was not send.");
		}

		return $status;
	}

	/**
	 * Get the recipients for the Adi_Mail_Message object
	 *
	 * @param Adi_Mail_Message $mail
	 *
	 * @return array
	 */
	public function getRecipients(Adi_Mail_Message $mail)
	{
		if ($mail->getTargetUser()) {
			return Core_Util_StringUtil::split($mail->getEmail(), ';');
		}

		$adminEmail = $this->configuration->getOptionValue(Adi_Configuration_Options::ADMIN_EMAIL);

		if ($adminEmail) {
			return Core_Util_StringUtil::split($adminEmail, ';');
		}

		return Core_Util_StringUtil::split(get_bloginfo('admin_email'), ';');
	}
}
