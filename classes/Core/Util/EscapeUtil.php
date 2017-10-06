<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}

if (class_exists('NextADInt_Core_Util_EscapeUtil')) {
    return;
}

/**
 * Class NextADInt_Core_Util_EscapeUtil can escape strings.
 * For example the class can escape harmful html tags from a string.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 *
 * @access public
 */
class NextADInt_Core_Util_EscapeUtil
{
    const HARMLESS_HTML_TAGS = '<em><strong><code><samp><kbd><var>';

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * Escape harmful HTML tags from a string or an array with strings
     *
     * @param $element array|string
     * @return array|string|null
     */
    public static function escapeHarmfulHtml($element)
    {
        if (is_string($element)) {
            return strip_tags($element, self::HARMLESS_HTML_TAGS);
        }

        if (is_array($element)) {
            foreach ($element as $key => $value) {
                $element[$key] = self::escapeHarmfulHtml($value);
            }
            return $element;
        }

        return null;
    }
}