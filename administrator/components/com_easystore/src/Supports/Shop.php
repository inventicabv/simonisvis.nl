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
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;

final class Shop
{
    public static function asNegative(string $price)
    {
        return '−&thinsp;' . $price;
    }

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

    public static function calculatePriceExcludingTax($price, $taxRate)
    {
        $taxRate = floatval($taxRate / 100);

        return $price / (1 + $taxRate);
    }

    public static function calculateTaxableAmountForProductsExcludingTax($price, $taxRate)
    {
        return $price - self::calculatePriceExcludingTax($price, $taxRate);
    }

    public static function addTaxablePrices($finalPrice, $taxRate)
    {
        if (empty($finalPrice) || !is_object($finalPrice)) {
            return $finalPrice;
        }

        $keys = ['unit_product_price', 'total_product_price', 'unit_discounted_price', 'total_discounted_price'];
        $suffix = '_with_tax';
        $price = clone $finalPrice;

        foreach ($keys as $key) {
            $newKey = $key . $suffix;
            $withCurrencyKey = $newKey . '_with_currency';
            $price->$newKey = $finalPrice->$key + self::calculateTaxableAmount($finalPrice->$key, $taxRate);
            $price->$withCurrencyKey = EasyStoreHelper::formatCurrency($price->$newKey);
        }

        return $price;
    }

    public static function recalculateCouponDiscount($finalPrice, $isCouponApplied)
    {
        if (empty($finalPrice) || !is_object($finalPrice)) {
            return $finalPrice;
        }

        $finalPrice->unit_discounted_price = $isCouponApplied ? $finalPrice->unit_product_price - $finalPrice->unit_discount_value : 0;
        $finalPrice->total_discounted_price = $isCouponApplied ? $finalPrice->total_product_price - $finalPrice->total_discount_value : 0;

        $finalPrice = self::formatWithCurrency($finalPrice, ['unit_discounted_price', 'total_discounted_price']);

        return $finalPrice;
    }

    public static function isTaxEnabled()
    {
        $settings = SettingsHelper::getSettings();

        // If the tax is included to the product price then we do not need to calculate the tax
        return (bool) $settings->get('tax.isTaxIncludedInPrice', 0) === false;
    }

    /**
     * Determines whether the price should be shown with tax.
     *
     * This function retrieves the tax settings from the SettingsHelper and
     * checks if the tax is not included in the price and if the price should
     * be displayed with tax. If the settings retrieval fails, it returns false.
     *
     * @return bool Returns true if the price should be displayed with tax and
     *              tax is not included in the price. Otherwise, returns false.
     *
     */
    public static function isPriceDisplayedWithTax()
    {
        $settings = SettingsHelper::getSettings();

        // Return true if tax is not included in price but should be shown
        return (bool) $settings->get('tax.showPriceWithTax', false);
    }

    public static function isShippingTaxEnabled()
    {
        $settings = SettingsHelper::getSettings();

        // If the tax is included to the product price then we do not need to calculate the tax
        return (bool) $settings->get('tax.chargeTaxOnShipping', 1);
    }

    public static function isCouponEnabled()
    {
        $settings = SettingsHelper::getSettings();

        return (bool) $settings->get('checkout.enable_coupon_code', true);
    }

    public static function getStoreAddress()
    {
        $settings = SettingsHelper::getSettings();

        return (object) [
            'address1' => $settings->get('general.addressLineOne', ''),
            'address2' => $settings->get('general.addressLineTwo', ''),
            'country'  => $settings->get('general.country', ''),
            'state'    => $settings->get('general.state', ''),
            'city'     => $settings->get('general.city', ''),
            'zip'      => $settings->get('general.postcode', ''),
        ];
    }

    public static function priceExcludingTax($price, $taxRate)
    {
        return $price - self::calculateTaxableAmount($price, $taxRate);
    }

    public static function updatePriceForIncludingTax($item)
    {
        if (self::isTaxEnabled()) {
            return $item;
        }

        $item->final_price->unit_product_price = self::calculatePriceExcludingTax($item->final_price->unit_product_price, $item->tax_rate);
        $item->final_price->total_product_price = self::calculatePriceExcludingTax($item->final_price->total_product_price, $item->tax_rate);
        $item->final_price->unit_discounted_price = self::calculatePriceExcludingTax($item->final_price->unit_discounted_price, $item->tax_rate);
        $item->final_price->total_discounted_price = self::calculatePriceExcludingTax($item->final_price->total_discounted_price, $item->tax_rate);

        $item->final_price = self::formatWithCurrency(
            $item->final_price,
            ['unit_product_price', 'total_product_price', 'unit_discounted_price', 'total_discounted_price']
        );

        return $item;
    }

    /**
     * Apply tax to the price if the tax is enabled and the price should be displayed with tax.
     * 
     * @param bool $isTaxable
     * 
     * @return bool
     * @since 1.5.0
     */
    public static function applyTaxToPrice($isTaxable = false)
    {
        return $isTaxable && self::isTaxEnabled() && self::isPriceDisplayedWithTax();
    }

    /**
     * Returns true if the tax percentage should be displayed.
     * 
     * @return bool
     * @since 1.5.0
     */
    public static function displayTaxPercentage()
    {
        return self::isTaxEnabled() && self::isPriceDisplayedWithTax();
    }
}
