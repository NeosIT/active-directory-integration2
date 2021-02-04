<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_ActiveDirectory_Sid')) {
	return;
}

/**
 * SID things
 *
 * @see https://devblogs.microsoft.com/oldnewthing/20040315-00/?p=40253
 * @see https://en.wikipedia.org/wiki/Security_Identifier
 * @author Christopher Klein <me[at]schakko[dot]de>
 * @access public
 */
class NextADInt_ActiveDirectory_Sid {
	/**
	 * Binary representation
	 * @var mixed
	 */
	private $binary;

	/**
	 * Formatted as "S-1-"...
	 * @var string
	 */
	private $formatted;

	/**
	 * Hex representation
	 * @var string
	 */
	private $hex;

	private function __construct($binary, $formatted, $hex) {
		$this->binary = $binary;
		$this->formatted = $formatted;
		$this->hex = $hex;
	}

	/**
	 * Get formatted SID as "S-..."
	 * @return string
	 */
	public function getFormatted() {
		return $this->formatted;
	}

	/**
	 * Get SID as binary string
	 *
	 * @return mixed
	 */
	public function getBinary() {
		return $this->binary;
	}

	/**
	 * Get SID as hex
	 * @return string
	 */
	public function getHex() {
		return $this->hex;
	}

	/**
	 * Based upon this SID, the domain part will be extracted
	 *
	 * @return ActiveDirectory_Sid
	 */
	public function getDomainPartAsSid()
	{
		// this pattern defined the domain part of a SID
		// @see https://en.wikipedia.org/wiki/Security_Identifier
		$pattern = '/^(S\-1\-5\-21\-\d+\-\d+\-\d+)+(\-.*)?/';

		if (preg_match($pattern, $this->formatted, $ret)) {
			return self::of(strtoupper($ret[1]));
		}

		return NULL;
	}

	/**
	 * Create a new SID instance
	 * @param $objectSid either binary representation, "S-..." format or hex format
	 * @return NextADInt_ActiveDirectory_Sid|null null if conversion failed
	 */
	public static function of($objectSid) {
		$binary = null;
		$formatted = null;
		$hex = null;

		// if the object SID does not start with an S- prefix, it is probably binary encoded
		if (NextADInt_Core_Util_StringUtil::startsWith('S-', $objectSid)) {
			$hex = adLDAP::sidStringToHex($objectSid);
			$binary = hex2bin($hex);
			$formatted = $objectSid;
		}
		// only allow hex parameter
		// @see https://stackoverflow.com/a/43600382/2545275
		else if (trim($objectSid, '0..9A..Fa..f') == '') {
			$hex = $objectSid;
			$binary = hex2bin($hex);
			$formatted = adLDAP::convertBinarySidToString($binary);
		}
		// check for binary type
		// @see https://stackoverflow.com/a/25344979/2545275
		else if (preg_match('~[^\x20-\x7E\t\r\n]~', $objectSid)) {
			$binary = $objectSid;
			$hex = bin2hex($binary);
			$formatted = adLDAP::convertBinarySidToString($objectSid);
		}

		if (!$binary || !$formatted || !$hex) {
			return null;
		}

		return new NextADInt_ActiveDirectory_Sid($binary, $formatted, $hex);
	}

	public function __toString() {
		return "SID={objectSID='" . $this->formatted . "'}";
	}
}