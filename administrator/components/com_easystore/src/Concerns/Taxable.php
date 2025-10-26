<?php

/**
 * @package     EasyStore.Administrator
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Concerns;

use JoomShaper\Component\EasyStore\Site\Helper\ArrayHelper;
use JoomShaper\Component\EasyStore\Administrator\Constants\CountryCodes;
use JoomShaper\Component\EasyStore\Administrator\Helper\EasyStoreHelper;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

trait Taxable
{
    public function getShippingTaxRate($country, $state = null)
    {
        $taxRate = $this->getTaxRate($country, $state);
        return $taxRate->shipping_tax_rate ?? 0;
    }

    public function getTaxRate($country, $state = null, $item = null)
    {
        $categoryId = !empty($item->catid) ? $item->catid : null;
        $isProductTaxable = isset($item->is_product_taxable) ? (bool) $item->is_product_taxable : true;
        $settings = SettingsHelper::getSettings();
        $taxRates = $settings->get('tax', []);

        $zeroTax = (object) [
            'product_tax_rate' => 0,
            'shipping_tax_rate' => 0,
            'applyOnShipping' => false,
        ];

        if (empty($country) || empty($taxRates->rates)) {
            return $zeroTax;
        }

        // Check if the country is EU
        $isEUCountry = $this->isEUCountry($country);
        $rateData    = $this->findCountryRate($taxRates, $country, $isEUCountry);

        if (empty($rateData)) {
            return $zeroTax;
        }

        $taxRate = $this->calculateTaxRate($rateData, $country, $state, $categoryId);

        if (!$isProductTaxable) {
            $taxRate->product_tax_rate = 0;
        }

        return $taxRate;
    }

    /**
     * Get the product tax rate.
     *
     * @param  object $item Single product item
     * @return mixed
     */
    public function getProductTaxRate($item)
    {
        list($country, $state) = EasyStoreHelper::getCountryAndState();

        $taxRate = $this->getTaxRate($country, $state, $item);

        return $taxRate->product_tax_rate;
    }

    /**
     * Apply the tax value on product price
     *
     * @param  mixed $price Product price
     * @param  mixed $taxRate Tax rate
     * @return mixed
     */
    public function applyTaxOnProductPrice($price, $taxRate)
    {
        return $price + (($price * $taxRate) / 100);
    }

    /**
     * Get taxable amount of a product
     *
     * @param  mixed $price Product price
     * @param  mixed $taxRate Tax rate
     * @return mixed
     */
    public function getTaxableAmount($price, $taxRate)
    {
        return ($price * $taxRate) / 100;
    }

    /**
     * Modify the product object with formatted tax value.
     *
     * @param  object $item Product entries
     *
     * @return object
     */
    public function modifyProductPriceWithTax($item)
    {
        $item->taxable_price = ($item->has_sale && $item->discount_value) ? $this->applyTaxOnProductPrice($item->discounted_price, $item->tax_rate) : $this->applyTaxOnProductPrice($item->regular_price, $item->tax_rate);
        $item->taxable_price_with_currency = EasyStoreHelper::formatCurrency($item->taxable_price);
        $item->taxable_price_with_segments = EasyStoreHelper::formatCurrency($item->taxable_price, true);

        return $item;
    }

        /**
     * Modify the product object with formatted tax value.
     *
     * @param  object $item Product entries
     *
     * @return object
     */
    public function modifyProductMinPriceWithTax($item)
    {
        $item->taxable_min_price = ($item->has_sale && $item->discount_value) ? $this->applyTaxOnProductPrice($item->discounted_min_price, $item->tax_rate) : $this->applyTaxOnProductPrice($item->min_price, $item->tax_rate);
        $item->taxable_min_price_with_currency = EasyStoreHelper::formatCurrency($item->taxable_min_price);
        $item->taxable_min_price_with_segments = EasyStoreHelper::formatCurrency($item->taxable_min_price, true);

        return $item;
    }

    /**
     * Checks if a given country code belongs to an EU country.
     *
     * This function checks if the provided country code (in numeric format)
     * corresponds to a country that is part of the European Union.
     *
     * @param string $countryCode The numeric ISO 3166-1 country code to check.
     *
     * @return bool Returns true if the country code belongs to an EU country, false otherwise.
     */
    public function isEUCountry($countryCode)
    {
        $euCountryCodes = [
            CountryCodes::AUSTRIA,
            CountryCodes::BELGIUM,
            CountryCodes::BULGARIA,
            CountryCodes::CROATIA,
            CountryCodes::CYPRUS,
            CountryCodes::CZECH_REPUBLIC,
            CountryCodes::DENMARK,
            CountryCodes::ESTONIA,
            CountryCodes::FINLAND,
            CountryCodes::FRANCE,
            CountryCodes::GERMANY,
            CountryCodes::GREECE,
            CountryCodes::HUNGARY,
            CountryCodes::IRELAND,
            CountryCodes::ITALY,
            CountryCodes::LATVIA,
            CountryCodes::LITHUANIA,
            CountryCodes::LUXEMBOURG,
            CountryCodes::MALTA,
            CountryCodes::NETHERLANDS,
            CountryCodes::POLAND,
            CountryCodes::PORTUGAL,
            CountryCodes::ROMANIA,
            CountryCodes::SLOVAKIA,
            CountryCodes::SLOVENIA,
            CountryCodes::SPAIN,
            CountryCodes::SWEDEN,
        ];

        return in_array($countryCode, $euCountryCodes, true);
    }


    /**
     * Finds the tax rate data for a given country, checking if it's an EU or non-EU country.
     *
     * @param object $taxRates  The collection of tax rate data.
     * @param string $country   The country code for which the tax rate needs to be found.
     * @param bool   $isEUCountry      Whether the country is part of the EU.
     *
     * @return object|null      The tax rate data for the country, or null if not found.
     */
    public function findCountryRate($taxRates, $country, $isEUCountry)
    {
        return ArrayHelper::find(function ($item) use ($country, $isEUCountry) {
            return $isEUCountry ? $item->country === CountryCodes::EUROPEAN_UNION : $item->country === $country;
        }, $taxRates->rates);
    }

    /**
     * Calculates the applicable tax rate and shipping tax based on country, state, and product category.
     *
     * @param object $rateData  The tax rate data for a country or state.
     * @param string $country   The country code for which the tax rate is being calculated.
     * @param string|null $state Optional state or region code (if applicable).
     * @param string|null $category Optional product category to check for tax overrides.
     *
     * @return object           An object containing the calculated tax rate, shipping tax, and applyOnShipping flag.
     */
    public function calculateTaxRate($rateData, $country, $state, $categoryId)
    {
        $taxRate        = 0;
        $shippingTaxRate = 0;

        $zeroTax = (object) [
            "product_tax_rate" => 0,
            "shipping_tax_rate" => 0,
            'applyOnShipping' => false,
        ];

        $location = !empty($state) ? $state : $country;

        if ($this->isEUCountry($country)) {
            $location = $country;
        }

        // Handle same rate case
        if ($rateData->isSameRate || empty($rateData->states)) {
            return $this->getRateData($rateData, $categoryId);
        }

        if (empty($location)) {
            return $zeroTax;
        }

        // Handle different state-specific rates
        if (!empty($rateData->states)) {
            if ($this->isEUCountry($location) && $this->isMicroBusinessVat($rateData)) {
                return $this->getRateData($rateData->states[0], $categoryId);
            }

            $stateRateData = ArrayHelper::find(function ($item) use ($location) {
                return (string) $item->id === (string) $location;
            }, $rateData->states);

            if (empty($stateRateData)) {
                return $zeroTax;
            }

            return $this->getRateData($stateRateData, $categoryId);
        }

        return (object) [
            "product_tax_rate" => (float) $taxRate,
            "shipping_tax_rate" => (float) $shippingTaxRate,
            'applyOnShipping' => false,
        ];
    }

    /**
     * Handle the case when the rate is the same.
     */
    public function getRateData($rateData, $categoryId)
    {
        $productTaxRate = (float) $rateData->rate;
        $shippingTaxRate = 0;

        if (!empty($rateData->overrideValues)) {
            $productOverride = ArrayHelper::find(function ($item) use ($categoryId) {
                return $item->overrideOn === 'products' && $item->category === $categoryId;
            }, $rateData->overrideValues);

            if (!empty($productOverride)) {
                $productTaxRate = (float) $productOverride->rate;
            }

            $shippingOverride = ArrayHelper::find(function ($item) {
                return $item->overrideOn === 'shipping';
            }, $rateData->overrideValues);

            if (!empty($shippingOverride)) {
                $shippingTaxRate = (float) $shippingOverride->rate;
            }
        }

        return (object) [
            "product_tax_rate"     => $productTaxRate,
            "shipping_tax_rate"    => $shippingTaxRate,
            'applyOnShipping' => !empty($shippingTaxRate),
        ];
    }

    /**
     * Check if VAT type is one-stop.
     *
     * @return bool
     */
    public function isOneStopVat($rateData)
    {
        return isset($rateData->vat_registration_type) && $rateData->vat_registration_type === "one-stop";
    }

    /**
     * Check if VAT type is micro-business.
     *
     * @return bool
     */
    public function isMicroBusinessVat($rateData)
    {
        return isset($rateData->vat_registration_type) && $rateData->vat_registration_type === "micro-business";
    }
}
