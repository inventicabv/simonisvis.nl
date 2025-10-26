<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Site\Helper;

use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Site\Helper\StringDecorator;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Class OrderHelper
 *
 * This class provides helper methods for order-related operations.
 * It includes methods to add and remove prefixes and suffixes from order IDs,
 * as well as methods to format and parse order numbers.
 *
 * @since 1.5.0
 */
final class OrderHelper
{
    /**
     * Creates a decorated order number with current settings
     * (Alternative more descriptive method name)
     *
     * @param int|string $orderId
     * @return string
     * @since 1.5.0
     */
    public static function formatOrderNumber($orderId): string
    {
        return StringDecorator::fromSettings()->decorate($orderId);
    }

    /**
     * Extracts the raw order ID from a decorated order number
     * (Alternative more descriptive method name)
     *
     * @param string $decoratedOrderId
     * @return string
     * @since 1.5.0
     */
    public static function parseOrderNumber(string $decoratedOrderId): string
    {
        return StringDecorator::fromSettings()->undecorated($decoratedOrderId);
    }

    /**
     * Get the seller's tax ID based on the country code.
     *
     * @param  string $countryCode
     * @return string
     * @since 1.7.0
     */
    public static function getSellerTaxID(string $countryCode = ''): string
    {
        $settings = SettingsHelper::getSettings();
        $sellerTaxIds = $settings->get('tax.sellerTaxIds', []);

        if (empty($sellerTaxIds)) {
            return '';
        }

        foreach ($sellerTaxIds as $item) {
            if (!self::isValidTaxIdConfig($item)) {
                continue;
            }

            $location = $item->taxIdLocation ?? '';

            switch ($location) {
                case 'all_countries':
                    return $item->taxIdNumber;

                case 'specific_countries':
                    $allowedCountries = $item->taxIdForCountries ?? [];
                    if (!empty($allowedCountries) && in_array($countryCode, $allowedCountries, true)) {
                        return $item->taxIdNumber;
                    }
                    break;

                case 'countries_except_for':
                    $excludedCountries = $item->taxIdForCountriesExcept ?? [];
                    if (empty($excludedCountries) || !in_array($countryCode, $excludedCountries, true)) {
                        return $item->taxIdNumber;
                    }
                    break;
            }
        }

        return '';
    }

    /**
     * Check if the tax ID configuration is valid.
     *
     * @param object $item
     * @return float
     * @since 1.7.0
     */
    private static function isValidTaxIdConfig(object $item): bool
    {
        return !empty($item->taxIdNumber)
            && !empty($item->taxIdLocation)
            && in_array($item->taxIdLocation, ['all_countries', 'specific_countries', 'countries_except_for'], true);
    }
}