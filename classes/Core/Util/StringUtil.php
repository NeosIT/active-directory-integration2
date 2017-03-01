<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}

if (class_exists('NextADInt_Core_Util_StringUtil')) {
    return;
}

/**
 * NextADInt_Core_Util_StringUtil provides helper functions for interacting with strings.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 *
 * @access public
 */
class NextADInt_Core_Util_StringUtil
{
    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * Split $string at $separator. If $string is an array then return this array.
     *
     * @param string $string
     * @param char $separator
     *
     * @return array
     */
    public static function split($string, $separator)
    {
        if (is_array($string)) {
            return $string;
        }

        if ("\n" === $separator) {
            $string = str_replace("\r", '', $string);
        }

        return explode($separator, $string);
    }

    /**
     * Split string at $separator and return only the items which are not empty and have at least one character.
     *
     * @param $string
     * @param $separator
     *
     * @return array
     */
    public static function splitNonEmpty($string, $separator)
    {
        $items = self::split($string, $separator);
        $r = array();

        foreach ($items as $item) {
            $trimmedItem = trim($item);

            if (!empty($trimmedItem)) {
                $r[] = $trimmedItem;
            }
        }

        return $r;
    }

    /**
     * Split a text with \r\n and only returns non-empty lines
     *
     * @param string $value
     *
     * @return array
     */
    public static function splitText($value)
    {
        $string = str_replace("\r", '', $value);
        $lines = explode("\n", $string);

        $array = array();
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line) {
                $array[] = $line;
            }
        }

        return $array;
    }

    /**
     * Concat array with the given separator
     *
     * @param array $array
     * @param char $separator
     *
     * @return string
     */
    public static function concat($array, $separator)
    {
        if (is_string($array)) {
            return $array;
        }

        return implode($separator, $array);
    }

    /**
     * Compare both strings case-insensitive
     *
     * @param string $string1
     * @param string $string2
     *
     * @return bool
     */
    public static function compareLowercase($string1, $string2)
    {
        $string1 = self::toLowerCase($string1);
        $string2 = self::toLowerCase($string2);

        return $string1 === $string2;
    }

    /**
     * Explode the given string and trim every element. If the line is empty it will not be added.
     *
     * @param string $trim
     * @param string $string
     *
     * @return array
     */
    public static function trimmedExplode($trim, $string)
    {
        $parts = explode($trim, $string);
        $r = array();

        foreach ($parts as $part) {
            $part = trim($part);

            if (strlen($part) > 0) {
                $r[] = $part;
            }
        }

        return $r;
    }

    /**
     * Convert the given binary string into a real string. Same as in adldap.php
     *
     * @param $binaryString
     *
     * @return mixed
     */
    public static function binaryToGuid($binaryString)
    {
        // Return null if binaryString is empty. (For example if user does not exist in Active Directory anymore.)
        if (empty($binaryString)) {
            return null;
        }

        $hexString = bin2hex($binaryString);
        $result = '';

        for ($k = 1; $k <= 4; ++$k) {
            $result .= substr($hexString, 8 - 2 * $k, 2);
        }

        $result .= '-';

        for ($k = 1; $k <= 2; ++$k) {
            $result .= substr($hexString, 12 - 2 * $k, 2);
        }

        $result .= '-';

        for ($k = 1; $k <= 2; ++$k) {
            $result .= substr($hexString, 16 - 2 * $k, 2);
        }

        $result .= '-' . substr($hexString, 16, 4);
        $result .= '-' . substr($hexString, 20);

        return self::toLowerCase($result);
    }

    /**
     * Convert object SID to domain SID
     *
     * @param string $objectSid
     *
     * @return string
     */
    public static function objectSidToDomainSid($objectSid)
    {
        $stringBuffer = "";

        if (is_string($objectSid) && !empty($objectSid)) {
            $position = 0;
            $reversedString = strrev($objectSid);

            for ($i = 0; $i < strlen($reversedString); $i++) {
                if ($reversedString[$i] === "-") {
                    $position = $i + 1;
                    break;
                }
            }

            $stringBuffer = substr($reversedString, $position);
            $stringBuffer = strrev($stringBuffer);
        }

        return $stringBuffer;
    }

    /**
     * Check if the given string is either empty or contains only whitespaces.
     *
     * @param $string
     *
     * @return bool
     */
    public static function isEmptyOrWhitespace($string)
    {
        if (null === $string) {
            return true;
        }

        $trimmedValue = trim($string);

        return ('' === $trimmedValue);
    }

    /**
     * Check if the given text starts with the given string.
     *
     * Thanks to:
     * http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php?answertab=votes#tab-top
     *
     * @param $needle
     * @param $haystack
     *
     * @return bool
     */
    public static function startsWith($needle, $haystack)
    {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    /**
     * Convert the given string to lowercase using {@link mb_strtolower}.
     *
     * @param $string
     *
     * @return mixed|string
     */
    public static function toLowerCase($string)
    {
        return mb_strtolower($string);
    }

    /**
     * Convert the $string into a new string with maximum of $maxChars length
     *
     * @issue ADI-420
     * @param mixed $string if the value is not of type string, the value is returned
     * @param int $maxChars by default the first 48 bytes are printed out (32 are not enough to display GUIDs)
     * @param boolean $appendByteInfo
     * @return string
     */
    public static function firstChars($string, $maxChars = 48, $appendByteInfo = true)
    {
        $r = $string;

        if (!is_string($string)) {
            return $r;
        }

        // only match the values and not meta data like "count"
        if (strlen($string) > $maxChars) {
            // trim output if $maxOutputChars is exceeded
            $r = substr($string, 0, $maxChars);

            if ($appendByteInfo) {
                $r .= " (... " . (strlen($string) - $maxChars) . " bytes more)";
            }
        }

        return $r;
    }

    /**
     * Parse bulk log into an array for use in frontend.
     *
     * @param $log
     * @return array
     */
    public static function transformLog($log)
    {
        if ($log[0] != '' && $log[0] != 'Test') {
            $logBuffer = array();

            foreach ($log as $key => $logline) {
                // check for stack traces inside the log
                if ($logline == "\nStack trace:" | substr($logline, 0, 2) == "\n#") {
                    $logBuffer[$key]['logLevel'] = '[ERROR]';
                    $logBuffer[$key]['logMessage'] = $logline;
                } elseif (!$logline == '') {
                    $tempArray = explode('|', $logline);
                    // str_replace used in order to remove unwanted spaces e.g. [INFO ] --> [INFO]
                    $logBuffer[$key]['logLevel'] = str_replace(' ', '', $tempArray[0]);
                    $logBuffer[$key]['logMessage'] = $tempArray[1];
                }
            }

            return $logBuffer;
        }
        return $log;
    }
}