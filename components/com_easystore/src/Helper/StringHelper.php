<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

final class StringHelper
{
    public static function toRegexSafeString($search)
    {
        if (empty($search)) {
            return '';
        }

        $search   = preg_replace('/\s+/', ' ', $search);
        $keywords = explode(' ', $search);

        $value = array_map(function ($item) {
            return preg_quote($item, '/');
        }, $keywords);

        return implode('|', $value);
    }

    public static function toAlphabeticString($string)
    {
        if (empty($string)) {
            return '';
        }

        $cleanedString = preg_replace('/[^a-zA-Z\s]/', '', $string);
        $cleanedString = preg_replace('/\s+/', ' ', $cleanedString);
        $cleanedString = trim($cleanedString);

        return $cleanedString;
    }

    public static function stringToArray($string, $separator = '.')
    {
        if (empty($string)) {
            return [];
        }

        $array = explode($separator, $string);
        $array = array_filter($array, function ($item) {
            return !empty($item);
        });

        return array_values($array);
    }
}
