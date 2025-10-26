<?php

/**
 * @package     PlgEasystoreshippingPostnl
 * @subpackage  Order Helper
 *
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace PlgEasystoreshippingPostnl;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Registry\Registry;

/**
 * PostNL Order Helper
 *
 * Handles order-related operations for PostNL shipments
 *
 * @since 1.0.0
 */
class OrderHelper
{
    /**
     * Build PostNL shipment payload from EasyStore order
     *
     * @param object $order  Order object from EasyStore
     * @param Registry $params Plugin parameters
     *
     * @return array PostNL shipment payload
     *
     * @since 1.0.0
     */
    public static function buildPostnlPayload(object $order, Registry $params): array
    {
        // Get shipping address
        $shippingAddress = json_decode($order->shipping_address ?? '{}');

        // Parse address into components
        $addressParts = self::parseAddress($shippingAddress);

        // Get customer/shipper info from config
        $customerCode   = $params->get('customer_code', '');
        $customerNumber = $params->get('customer_number', '');
        $productCode    = $params->get('product_code_delivery', '3085');

        // Calculate weight (in grams)
        $weight = self::calculateWeight($order, $params);

        // Build receiver data
        $receiver = [
            'Name'         => trim(($shippingAddress->first_name ?? '') . ' ' . ($shippingAddress->last_name ?? '')),
            'CompanyName'  => $shippingAddress->company ?? '',
            'Street'       => $addressParts['street'],
            'HouseNr'      => $addressParts['houseNumber'],
            'HouseNrExt'   => $addressParts['houseNumberSuffix'],
            'Zipcode'      => self::sanitizePostcode($shippingAddress->postcode ?? ''),
            'City'         => $shippingAddress->city ?? '',
            'Countrycode'  => strtoupper($shippingAddress->country_code ?? 'NL'),
            'Email'        => $shippingAddress->email ?? $order->email ?? '',
            'PhoneNumber'  => $shippingAddress->phone ?? '',
        ];

        // Build shipper data from config
        $shipper = [
            'CompanyName'  => $params->get('shipper_company_name', ''),
            'Street'       => $params->get('shipper_street', ''),
            'HouseNr'      => $params->get('shipper_house_number', ''),
            'HouseNrExt'   => $params->get('shipper_house_number_suffix', ''),
            'Zipcode'      => self::sanitizePostcode($params->get('shipper_zipcode', '')),
            'City'         => $params->get('shipper_city', ''),
            'Countrycode'  => strtoupper($params->get('shipper_country_code', 'NL')),
            'Email'        => $params->get('shipper_email', ''),
            'PhoneNumber'  => $params->get('shipper_phone', ''),
        ];

        // Build complete payload
        $payload = [
            'Customer' => [
                'CustomerCode'   => $customerCode,
                'CustomerNumber' => $customerNumber,
            ],
            'Shipments' => [
                [
                    'Addresses' => [
                        [
                            'AddressType' => '01', // Receiver
                            'FirstName'   => $shippingAddress->first_name ?? '',
                            'Name'        => $shippingAddress->last_name ?? '',
                            'CompanyName' => $receiver['CompanyName'],
                            'Street'      => $receiver['Street'],
                            'HouseNr'     => $receiver['HouseNr'],
                            'HouseNrExt'  => $receiver['HouseNrExt'],
                            'Zipcode'     => $receiver['Zipcode'],
                            'City'        => $receiver['City'],
                            'Countrycode' => $receiver['Countrycode'],
                            'Email'       => $receiver['Email'],
                            'PhoneNumber' => $receiver['PhoneNumber'],
                        ],
                        [
                            'AddressType' => '02', // Sender
                            'CompanyName' => $shipper['CompanyName'],
                            'Street'      => $shipper['Street'],
                            'HouseNr'     => $shipper['HouseNr'],
                            'HouseNrExt'  => $shipper['HouseNrExt'],
                            'Zipcode'     => $shipper['Zipcode'],
                            'City'        => $shipper['City'],
                            'Countrycode' => $shipper['Countrycode'],
                            'Email'       => $shipper['Email'],
                            'PhoneNumber' => $shipper['PhoneNumber'],
                        ],
                    ],
                    'Dimension' => [
                        'Weight' => $weight,
                    ],
                    'ProductCodeDelivery' => $productCode,
                    'Reference'           => $order->order_number ?? (string) $order->id,
                ],
            ],
            'LabelFormat' => $params->get('label_format', 'PDF'),
        ];

        return $payload;
    }

    /**
     * Parse address string into street, house number and suffix
     *
     * @param object $address Address object
     *
     * @return array Array with street, houseNumber, houseNumberSuffix
     *
     * @since 1.0.0
     */
    private static function parseAddress(object $address): array
    {
        $addressLine = trim($address->address_1 ?? '');

        // Try to parse Dutch address format: "Straatnaam 123 A"
        $pattern = '/^(.+?)\s+(\d+)\s*(.*)$/';

        if (preg_match($pattern, $addressLine, $matches)) {
            return [
                'street'             => trim($matches[1]),
                'houseNumber'        => trim($matches[2]),
                'houseNumberSuffix'  => trim($matches[3]),
            ];
        }

        // Fallback: use entire address as street
        return [
            'street'             => $addressLine,
            'houseNumber'        => '',
            'houseNumberSuffix'  => '',
        ];
    }

    /**
     * Calculate shipment weight from order
     *
     * @param object $order  Order object
     * @param Registry $params Plugin parameters
     *
     * @return int Weight in grams
     *
     * @since 1.0.0
     */
    private static function calculateWeight(object $order, Registry $params): int
    {
        // Try to get weight from order items
        $totalWeight = 0;

        if (isset($order->items) && is_array($order->items)) {
            foreach ($order->items as $item) {
                $weight = (float) ($item->weight ?? 0);
                $qty    = (int) ($item->quantity ?? 1);
                $totalWeight += ($weight * $qty);
            }
        }

        // If no weight found, use default
        if ($totalWeight <= 0) {
            $totalWeight = (int) $params->get('default_weight', 1000);
        } else {
            // Convert to grams if needed (assuming weight might be in kg)
            if ($totalWeight < 10) {
                $totalWeight = $totalWeight * 1000;
            }
        }

        return (int) $totalWeight;
    }

    /**
     * Sanitize postcode (remove spaces, uppercase)
     *
     * @param string $postcode Raw postcode
     *
     * @return string Sanitized postcode
     *
     * @since 1.0.0
     */
    public static function sanitizePostcode(string $postcode): string
    {
        return strtoupper(str_replace(' ', '', $postcode));
    }

    /**
     * Build Track & Trace URL
     *
     * @param string $barcode     PostNL barcode
     * @param string $postcode    Postcode (with spaces)
     * @param string $countryCode ISO2 country code
     * @param string $lang        Language code (NL/EN/DE/FR)
     *
     * @return string Track & Trace URL
     *
     * @since 1.0.0
     */
    public static function buildTtUrl(
        string $barcode,
        string $postcode,
        string $countryCode = 'NL',
        string $lang = 'NL'
    ): string {
        $cleanPostcode = self::sanitizePostcode($postcode);

        $params = http_build_query([
            'B' => $barcode,
            'P' => $cleanPostcode,
            'D' => strtoupper($countryCode),
            'L' => strtoupper($lang),
            'T' => 'C', // Consumer
        ]);

        return 'https://jouw.postnl.nl/track-and-trace/?' . $params;
    }

    /**
     * Save tracking information to order
     *
     * @param int    $orderId      Order ID
     * @param string $barcode      PostNL barcode
     * @param string $trackingUrl  Tracking URL
     *
     * @return bool Success status
     *
     * @since 1.0.0
     */
    public static function saveTracking(int $orderId, string $barcode, string $trackingUrl): bool
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            // Update order with tracking info
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__easystore_orders'))
                ->set($db->quoteName('tracking_number') . ' = ' . $db->quote($barcode))
                ->set($db->quoteName('tracking_url') . ' = ' . $db->quote($trackingUrl))
                ->where($db->quoteName('id') . ' = ' . (int) $orderId);

            $db->setQuery($query);
            $db->execute();

            Log::add(
                sprintf('Tracking saved for order #%d: %s', $orderId, $barcode),
                Log::INFO,
                'plg_easystoreshipping_postnl'
            );

            return true;

        } catch (\Exception $e) {
            Log::add(
                'Failed to save tracking: ' . $e->getMessage(),
                Log::ERROR,
                'plg_easystoreshipping_postnl'
            );
            return false;
        }
    }

    /**
     * Save label file to filesystem
     *
     * @param int    $orderId      Order ID
     * @param string $barcode      PostNL barcode
     * @param string $content      Label content (binary)
     * @param string $format       Label format (PDF/ZPL)
     *
     * @return string|false Path to saved file or false on failure
     *
     * @since 1.0.0
     */
    public static function saveLabel(int $orderId, string $barcode, string $content, string $format = 'PDF'): string|false
    {
        try {
            $basePath = JPATH_ROOT . '/media/com_easystore/postnl/' . $orderId;

            // Create directory if not exists
            if (!Folder::exists($basePath)) {
                Folder::create($basePath);
            }

            $filename = $barcode . '.' . strtolower($format);
            $filepath = $basePath . '/' . $filename;

            // Write label file
            if (!File::write($filepath, $content)) {
                throw new \RuntimeException('Failed to write label file');
            }

            Log::add(
                sprintf('Label saved for order #%d: %s', $orderId, $filepath),
                Log::INFO,
                'plg_easystoreshipping_postnl'
            );

            return $filepath;

        } catch (\Exception $e) {
            Log::add(
                'Failed to save label: ' . $e->getMessage(),
                Log::ERROR,
                'plg_easystoreshipping_postnl'
            );
            return false;
        }
    }

    /**
     * Send tracking email to customer
     *
     * @param object $order      Order object
     * @param string $barcode    PostNL barcode
     * @param string $trackingUrl Tracking URL
     * @param Registry $params   Plugin parameters
     *
     * @return bool Success status
     *
     * @since 1.0.0
     */
    public static function sendTrackingEmail(object $order, string $barcode, string $trackingUrl, Registry $params): bool
    {
        try {
            $app = Factory::getApplication();

            // Get customer email
            $customerEmail = $order->email ?? '';
            if (empty($customerEmail)) {
                Log::add(
                    'Cannot send tracking email: no customer email',
                    Log::WARNING,
                    'plg_easystoreshipping_postnl'
                );
                return false;
            }

            // Build email content
            $subject = Text::sprintf('PLG_EASYSTORESHIPPING_POSTNL_EMAIL_SUBJECT', $order->order_number ?? $order->id);

            $body = Text::sprintf(
                'PLG_EASYSTORESHIPPING_POSTNL_EMAIL_BODY',
                $order->order_number ?? $order->id,
                $barcode,
                $trackingUrl
            );

            // Get mailer
            $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();

            $mailer->setSubject($subject);
            $mailer->setBody($body);
            $mailer->addRecipient($customerEmail);

            // Send email
            $sent = $mailer->Send();

            if ($sent === true) {
                Log::add(
                    sprintf('Tracking email sent to %s for order #%d', $customerEmail, $order->id),
                    Log::INFO,
                    'plg_easystoreshipping_postnl'
                );
                return true;
            } else {
                throw new \RuntimeException('Mailer returned false');
            }

        } catch (\Exception $e) {
            Log::add(
                'Failed to send tracking email: ' . $e->getMessage(),
                Log::ERROR,
                'plg_easystoreshipping_postnl'
            );
            return false;
        }
    }
}
