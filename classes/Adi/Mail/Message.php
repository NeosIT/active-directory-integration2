<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Mail_Message')) {
	return;
}

/**
 * NextADInt_Adi_Mail_Message represents a notification mail.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class NextADInt_Adi_Mail_Message
{
	private $fromEmail;
	private $blogUrl;
	private $blogName;
	private $firstName;
	private $secondName;
	private $username;
	private $email;
	private $remoteAddress;
	private $blockTime;
	private $targetUser;

	private static $bodyElements = array(
		'Someone tried to login into the WordPress site "%s" (%s) with %s username "%s" - but was stopped after too many wrong attempts.',
		'This account is associated with "%s %s" and the email address "%s".',
		'The login attempt was made from IP-Address: %s',
		'For security reasons this account is now blocked for %d seconds.',
		'PLEASE CONTACT AN ADMIN IF SOMEONE STILL TRIES TO LOGIN INTO YOUR ACCOUNT.',
		'THIS IS A SYSTEM GENERATED E-MAIL, PLEASE DO NOT RESPOND TO THE E-MAIL ADDRESS SPECIFIED ABOVE.',
	);

	/**
	 * NextADInt_Adi_Mail_Message constructor.
	 */
	public function __construct()
	{
	}

	public function getHeader()
	{
		if (empty($this->fromEmail)) {
			return '';
		}

        return 'From: ' . $this->fromEmail;
	}


	public function getSubject()
	{
		return '[' . $this->blogName . '] ' . __('Account blocked', NEXT_AD_INT_I18N);
	}

	public function getBody()
	{
		$body = sprintf($this->getBodyElement(0), $this->blogName, $this->blogUrl, $this->targetUser ? 'your' : 'the',
			$this->username);

		if (!$this->targetUser) {
			$body .= sprintf($this->getBodyElement(1), $this->firstName, $this->secondName, $this->email);
		}

		$body .= sprintf($this->getBodyElement(2), $this->remoteAddress);
		$body .= sprintf($this->getBodyElement(3), $this->blockTime);
		$body .= "\r\n";

		if ($this->targetUser) {
			$body .= $this->getBodyElement(4);
		}

		$body .= $this->getBodyElement(5);

		return $body;
	}

	/**
	 * Return the body element with the number $number.
	 *
	 * @param int $number
	 *
	 * @return string|void
	 */
	public function getBodyElement($number)
	{
		$element = self::$bodyElements[$number];
		$element = __($element, NEXT_AD_INT_I18N);
		$element .= "\r\n";

		return $element;
	}

	/**
	 * @return mixed
	 */
	public function getFromEmail()
	{
		return $this->fromEmail;
	}

	/**
	 * @param mixed $fromEmail
	 */
	public function setFromEmail($fromEmail)
	{
		$this->fromEmail = $fromEmail;
	}

	/**
	 * @return mixed
	 */
	public function getBlogUrl()
	{
		return $this->blogUrl;
	}

	/**
	 * @param mixed $blogUrl
	 */
	public function setBlogUrl($blogUrl)
	{
		$this->blogUrl = $blogUrl;
	}

	/**
	 * @return mixed
	 */
	public function getBlogName()
	{
		return $this->blogName;
	}

	/**
	 * @param mixed $blogName
	 */
	public function setBlogName($blogName)
	{
		$this->blogName = $blogName;
	}

	/**
	 * @return mixed
	 */
	public function getFirstName()
	{
		return $this->firstName;
	}

	/**
	 * @param mixed $firstName
	 */
	public function setFirstName($firstName)
	{
		$this->firstName = $firstName;
	}

	/**
	 * @return mixed
	 */
	public function getSecondName()
	{
		return $this->secondName;
	}

	/**
	 * @param mixed $secondName
	 */
	public function setSecondName($secondName)
	{
		$this->secondName = $secondName;
	}

	/**
	 * @return mixed
	 */
	public function getUsername()
	{
		return $this->username;
	}

	/**
	 * @param mixed $username
	 */
	public function setUsername($username)
	{
		$this->username = $username;
	}

	/**
	 * @return mixed
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * @param mixed $email
	 */
	public function setEmail($email)
	{
		$this->email = $email;
	}

	/**
	 * @return mixed
	 */
	public function getRemoteAddress()
	{
		return $this->remoteAddress;
	}

	/**
	 * @param mixed $remoteAddress
	 */
	public function setRemoteAddress($remoteAddress)
	{
		$this->remoteAddress = $remoteAddress;
	}

	/**
	 * @return mixed
	 */
	public function getBlockTime()
	{
		return $this->blockTime;
	}

	/**
	 * @param mixed $blockTime
	 */
	public function setBlockTime($blockTime)
	{
		$this->blockTime = $blockTime;
	}

	/**
	 * @return mixed
	 */
	public function getTargetUser()
	{
		return $this->targetUser;
	}

	/**
	 * @param mixed $targetUser
	 */
	public function setTargetUser($targetUser)
	{
		$this->targetUser = $targetUser;
	}
}