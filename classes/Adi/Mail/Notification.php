<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Mail_Notification')) {
	return;
}

/**
 * NextADInt_Adi_Mail_Notification sends notification emails.
 *
 * NextADInt_Adi_Mail_Notification send notification emails when an user account will be blocked.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @access  public
 */
class NextADInt_Adi_Mail_Notification
{
	/* @var NextADInt_Multisite_Configuration_Service $configuration */
	private $configuration;

	/* @var NextADInt_Ldap_Connection $ldapConnection */
	private $ldapConnection;

	/* @var Logger logger */
	private $logger;

	/**
	 * @param NextADInt_Multisite_Configuration_Service $configuration
	 * @param NextADInt_Ldap_Connection          $ldapConnection
	 */
	public function __construct(NextADInt_Multisite_Configuration_Service $configuration,
								NextADInt_Ldap_Connection $ldapConnection)
	{
		$this->configuration = $configuration;
		$this->ldapConnection = $ldapConnection;

		$this->logger = NextADInt_Core_Logger::getLogger();
	}

    /**
     * This method trigger the email dispatch for the user and/or for admins depending on the settings.
     * $useLocalWordPressUser will force user data to be looked up locally (ADI-383)
     *
     * @param WP_User $wpUser
     */
	public function sendNotifications(WP_User $wpUser)
	{
		$userNotification = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::USER_NOTIFICATION);

		if ($userNotification) {
			$mail = new NextADInt_Adi_Mail_Message();
			$mail->setUsername($wpUser->data->user_login);
			$mail->setTargetUser(true);
			$this->sendNotification($mail, $wpUser);
		}

		$adminNotification = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::ADMIN_NOTIFICATION);

		if ($adminNotification) {
			$mail = new NextADInt_Adi_Mail_Message();
			$mail->setUsername($wpUser->data->user_login);
			$mail->setTargetUser(false);
			$this->sendNotification($mail, $wpUser);
		}
	}

    /**
     * Do not call this method from the outside.
     * Prepares the NextADInt_Adi_Mail_Message and sends it.
     *
     * @param NextADInt_Adi_Mail_Message $mail
     * @param WP_User $wpUser
     * @return bool
     */
	public function sendNotification(NextADInt_Adi_Mail_Message $mail, WP_User $wpUser)
	{
		$url = get_bloginfo('url');

		// ADI-383 Github Issue #27 added check for http/https
		$fromEmail = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::FROM_EMAIL);

		$name = get_bloginfo('name');
		$blockTime = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::BLOCK_TIME);
		$remoteAddress = NextADInt_Core_Util_ArrayUtil::get('REMOTE_ADDR', $_SERVER, '');

		$metaData = $this->findWPUserAttributeValues($wpUser->data->user_login);

		if(!$metaData) {
		    return false;
        }

		$mail->setFirstName($metaData['firstName']);
		$mail->setSecondName($metaData['lastName']);
		$mail->setEmail($wpUser->data->user_email);
		$mail->setBlogUrl($url);
		$mail->setFromEmail($fromEmail);
		$mail->setBlogName($name);
		$mail->setBlockTime($blockTime);
		$mail->setRemoteAddress($remoteAddress);

		$success = $this->sendMails($mail);

		return $success;
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
	 * @param NextADInt_Adi_Mail_Message $mail
	 *
	 * @return bool
	 */
	function sendMails(NextADInt_Adi_Mail_Message $mail)
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
	 * @param NextADInt_Adi_Mail_Message $mail
	 *
	 * @return bool
	 */
	function sendMail($recipient, NextADInt_Adi_Mail_Message $mail)
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
	 * Get the recipients for the NextADInt_Adi_Mail_Message object
	 *
	 * @param NextADInt_Adi_Mail_Message $mail
	 *
	 * @return array
	 */
	public function getRecipients(NextADInt_Adi_Mail_Message $mail)
	{
		if ($mail->getTargetUser()) {
			return NextADInt_Core_Util_StringUtil::split($mail->getEmail(), ';');
		}

		$adminEmail = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::ADMIN_EMAIL);

		if ($adminEmail) {
			return NextADInt_Core_Util_StringUtil::split($adminEmail, ';');
		}

		return NextADInt_Core_Util_StringUtil::split(get_bloginfo('admin_email'), ';');
	}
}
