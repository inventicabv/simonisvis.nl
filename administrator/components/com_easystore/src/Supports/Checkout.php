<?php

/**
 * @package     EasyStore.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Supports;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;

final class Checkout
{
    /**
     * Add the object properties with currency format.
     * Will search a key by the provided keys array and if exists and the value is numeric then
     * create a new key with the _with_currency suffix and format the numeric value with currency symbol.
     *
     * @param object $data
     * @param array $keys
     *
     * @return object
     */
    public static function formatWithCurrency(object &$data, array $keys = [])
    {
        if (empty($keys)) {
            $keys = array_keys((array) $data);
        }

        foreach ($keys as $key) {
            if (!isset($data->$key) || !is_numeric($data->$key)) {
                continue;
            }

            $newKey = $key . '_with_currency';
            $data->$newKey = EasyStoreHelper::formatCurrency($data->$key);
        }

        return $data;
    }

    public static function calculateTaxableAmount($price, $taxRate)
    {
        if (empty($price) || empty($taxRate)) {
            return 0;
        }

        return ($price * $taxRate) / 100;
    }
}
