<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Helper;

use Closure;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

final class ArrayHelper
{
    public static function find(Closure $callback, $array)
    {
        if (empty($array)) {
            return null;
        }

        $value = array_filter($array, $callback);
        $value = array_values($value);

        return reset($value) ?? null;
    }

    public static function filter(Closure $callback, $array)
    {
        if (empty($array)) {
            return [];
        }

        $filtered = array_filter($array, $callback);

        return array_values($filtered);
    }


    public static function findIndex(Closure $callback, $array)
    {
        if (empty($array)) {
            return -1;
        }

        foreach ($array as $index => $value) {
            if ($callback($value, $index)) {
                return $index;
            }
        }

        return -1;
    }

    public static function findByArray(Closure $callback, $haystack, $needles)
    {
        if (empty($haystack) || empty($needles)) {
            return [];
        }

        $result = [];

        foreach ($needles as $item) {
            $value = $callback($haystack, $item);

            $result[] = $value;
        }

        $result = array_filter($result, function ($item) {
            return !is_null($item);
        });

        return array_values($result);
    }

    public static function toOptions(Closure $callback, $data)
    {
        if (empty($data)) {
            return [];
        }

        $options = [];

        foreach ($data as $value) {
            $options[] = $callback($value);
        }

        return $options;
    }

    public static function map(Closure $callback, $data)
    {
        $mapped = [];

        foreach ($data as $key => $value) {
            $mapped[] = $callback($value, $key);
        }

        return $mapped;
    }

    public static function some(Closure $callback, $data)
    {
        foreach ($data as $key => $item) {
            if ($callback($item, $key)) {
                return true;
            }
        }

        return false;
    }

    public static function every(Closure $callback, $data)
    {
        foreach ($data as $key => $item) {
            if (!$callback($item, $key)) {
                return false;
            }
        }

        return true;
    }
}
