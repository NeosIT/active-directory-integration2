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
		return '[' . $this->blogName . '] ' . __('Account blocked', 'next-active-directory-integration');
	}

	public function getBody()
	{
        $text = $this->getTranslatedEMailText();

        if ($this->targetUser) {
            $body = sprintf($text[0], $this->blogName, $this->blogUrl, $this->username);
        } else {
            $body = sprintf($text[1], $this->blogName, $this->blogUrl, $this->username);
        }


		if (!$this->targetUser) {
			$body .= sprintf($text[2], $this->firstName, $this->secondName, $this->email);
		}

		$body .= sprintf($text[3], $this->remoteAddress);
		$body .= sprintf($text[4], $this->blockTime);
		$body .= "\r\n";

		if ($this->targetUser) {
			$body .= $text[5];
		}

		$body .= $text[6];

		return $body;
	}

    /**
     * Return the translated text for the e-mail body
     */
	public function getTranslatedEmailText() {
        return array(
            __('Someone tried to login into the WordPress site "%s" (%s) with your username "%s" - but was stopped after too many wrong attempts.', 'next-active-directory-integration'),
            __('Someone tried to login into the WordPress site "%s" (%s) with the username "%s" - but was stopped after too many wrong attempts.', 'next-active-directory-integration'),
            __('This account is associated with "%s %s" and the email address "%s".', 'next-active-directory-integration'),
            __('The login attempt was made from IP-Address: %s', 'next-active-directory-integration'),
            __('For security reasons this account is now blocked for %d seconds.', 'next-active-directory-integration'),
            __('PLEASE CONTACT AN ADMIN IF SOMEONE STILL TRIES TO LOGIN INTO YOUR ACCOUNT.', 'next-active-directory-integration'),
            __('THIS IS A SYSTEM GENERATED E-MAIL, PLEASE DO NOT RESPOND TO THE E-MAIL ADDRESS SPECIFIED ABOVE.', 'next-active-directory-integration'),
        );
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