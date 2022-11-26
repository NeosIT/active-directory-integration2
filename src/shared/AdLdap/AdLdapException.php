<?php

namespace Dreitier\AdLdap;

class AdLdapException extends \Exception
{
	private $_ldapError = null;
	private $_ldapErrno = null;
	private $_message = null;
	private $_detailedMessage = null;

	public function __construct($message, $detailedMessage = null, $ldapError = null, $ldapErrno = null)
	{
		parent::__construct($message);

		$this->_detailedMessage = $detailedMessage != null ? $detailedMessage : $message;
		$this->_ldapError = $ldapError;
		$this->_ldapErrno = $ldapErrno;
	}

	public function getLdapError()
	{
		return $this->_ldapError;
	}

	public function getLdapErrno()
	{
		return $this->_ldapErrno;
	}
}
