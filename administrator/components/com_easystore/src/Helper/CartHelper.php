<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;

final class CartHelper
{
    public static function defaultPrice($item)
    {
        return (object) [
            'unit_product_price' => (float) bcdiv($item->item_price * 100, '100', 2),
            'total_product_price' => (float) bcdiv(($item->item_price * 100) * (int) $item->quantity, '100', 2),
            'unit_discount_value' => 0,
            'total_discount_value' => 0,
            'unit_discounted_price' => 0,
            'total_discounted_price' => 0,
            'unit_product_price_with_tax' => 0,
            'total_product_price_with_tax' => 0,
            'unit_discounted_price_with_tax' => 0,
            'total_discounted_price_with_tax' => 0,
        ];
    }

    public static function withCurrency(object $price)
    {
        $newPrice = clone $price;

        foreach ($price as $key => $value) {
            if (!is_numeric($value)) {
                continue;
            }

            $withCurrencyKey = $key . '_with_currency';
            $newPrice->$withCurrencyKey = EasyStoreHelper::formatCurrency($value);
        }

        return $newPrice;
    }
}
