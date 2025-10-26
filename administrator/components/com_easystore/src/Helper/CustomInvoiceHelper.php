<?php

/**
 * @package     EasyStore.Site
 * @subpackage  com_easystore
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Helper;

use DateTime;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper;
use JoomShaper\Component\EasyStore\Administrator\Service\InvoiceNumberService;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Class CustomInvoiceHelper
 *
 * This class provides helper methods for custom invoice ID generation with yearly reset functionality.
 * It handles generating custom invoice IDs that reset annually on January 1st.
 *
 * @since 1.7.0
 */
final class CustomInvoiceHelper
{
    /**
     * Generate a custom invoice ID based on settings
     *
     * @return string The generated custom invoice ID
     * @since 1.7.0
     */
    public static function generateCustomInvoiceId(): string
    {
        $settings = SettingsHelper::getSettings();
        $baseId = $settings->get('general.customInvoiceIdBase', '000000');

        $invoiceNumberService = new InvoiceNumberService();
        $invoiceId = $invoiceNumberService->generateInvoiceNumber((int) $baseId);

        SettingsHelper::setSettings('general.customInvoiceIdBase', $invoiceId);
        
        return $invoiceId;
    }

    public static function getGeneratedCustomInvoiceId($invoiceId): string
    {
        $settings = SettingsHelper::getSettings();
        $prefix = $settings->get('general.customInvoiceIdPrefix', '');
        $suffix = $settings->get('general.customInvoiceIdSuffix', '');

       return $prefix . $invoiceId . $suffix;
    }

    /**
     * Check if a custom invoice ID already exists
     *
     * @param string $customInvoiceId The custom invoice ID to check
     * @return bool True if exists, false otherwise
     * @since 1.7.0
     */
    public static function customInvoiceIdExists(string $customInvoiceId): bool
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        
        $query = $db->getQuery(true);
        $query->select('COUNT(*)')
              ->from($db->quoteName('#__easystore_orders'))
              ->where($db->quoteName('custom_invoice_id') . ' = ' . $db->quote($customInvoiceId));
        
        $db->setQuery($query);
        $count = (int) $db->loadResult();
        
        return $count > 0;
    }

    // get last order created date to check yearly reset
    public static function getLastOrderCreatedDate(): ?string
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('created')
              ->from($db->quoteName('#__easystore_orders'))
              ->order('created DESC')
              ->setLimit(1);
        $db->setQuery($query);
        $result = $db->loadResult();
        if ($result) {
            return (new DateTime($result))->format('Y-m-d');
        }
        return null;
    }
}