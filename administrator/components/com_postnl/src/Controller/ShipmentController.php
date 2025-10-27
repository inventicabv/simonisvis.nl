<?php

/**
 * @package     COM_POSTNL
 * @subpackage  Controller
 *
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace Simonisvis\Component\PostNL\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Simonisvis\Component\PostNL\Administrator\Helper\PostnlClient;
use Simonisvis\Component\PostNL\Administrator\Helper\OrderHelper;

/**
 * Shipment controller class for PostNL component.
 *
 * @since  1.0.0
 */
class ShipmentController extends BaseController
{
    /**
     * Create a PostNL shipment for an order
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function create(): void
    {
        // Check token
        $this->checkToken();

        try {
            $app = Factory::getApplication();
            $orderId = $app->input->getInt('order_id', 0);

            if (!$orderId) {
                throw new \RuntimeException(Text::_('COM_POSTNL_ERROR_NO_ORDER'));
            }

            // Load order from EasyStore
            $order = $this->loadOrder($orderId);

            if (!$order) {
                throw new \RuntimeException(Text::_('COM_POSTNL_ERROR_ORDER_NOT_FOUND'));
            }

            // Check if already has tracking
            if (!empty($order->tracking_number)) {
                $app->enqueueMessage(
                    Text::sprintf('COM_POSTNL_WARNING_ALREADY_HAS_TRACKING', $order->tracking_number),
                    'warning'
                );
            }

            // Get plugin parameters for API configuration
            $plugin = \Joomla\CMS\Plugin\PluginHelper::getPlugin('easystoreshipping', 'postnl');
            $params = new \Joomla\Registry\Registry($plugin->params);

            // Create shipment via PostNL API
            $result = $this->createShipment($order, $params);

            // Save to our database
            $this->saveShipment($orderId, $result);

            // Update EasyStore order
            OrderHelper::saveTracking($orderId, $result['barcode'], $result['tracking_url']);

            // Send tracking email if configured
            if ($params->get('auto_send_tracking', 1)) {
                OrderHelper::sendTrackingEmail($order, $result['barcode'], $result['tracking_url'], $params);
            }

            // Success message
            $app->enqueueMessage(
                Text::sprintf(
                    'COM_POSTNL_SUCCESS_CREATED',
                    $result['barcode'],
                    $result['tracking_url']
                ),
                'success'
            );

        } catch (\Exception $e) {
            Log::add(
                'PostNL shipment creation failed: ' . $e->getMessage(),
                Log::ERROR,
                'com_postnl'
            );

            $app->enqueueMessage(
                Text::sprintf('COM_POSTNL_ERROR_GENERAL', $e->getMessage()),
                'error'
            );
        }

        // Redirect back to PostNL order detail page
        $this->setRedirect(
            Route::_('index.php?option=com_postnl&view=order&id=' . $orderId, false)
        );
    }

    /**
     * Print/Download PostNL label
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function print(): void
    {
        try {
            $app = Factory::getApplication();
            $orderId = $app->input->getInt('id', 0);

            if (!$orderId) {
                throw new \RuntimeException(Text::_('COM_POSTNL_ERROR_NO_ORDER'));
            }

            // Get the latest shipment for this order
            $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $query = $db->getQuery(true);

            $query->select('*')
                ->from($db->quoteName('#__postnl_shipments'))
                ->where($db->quoteName('order_id') . ' = ' . (int) $orderId)
                ->order($db->quoteName('created_date') . ' DESC');

            $db->setQuery($query, 0, 1);
            $shipment = $db->loadObject();

            if (!$shipment) {
                throw new \RuntimeException(Text::_('COM_POSTNL_ERROR_NO_SHIPMENT'));
            }

            // Decode label content
            $labelContent = base64_decode($shipment->label_content);

            if (!$labelContent) {
                throw new \RuntimeException(Text::_('COM_POSTNL_ERROR_NO_LABEL_CONTENT'));
            }

            // Determine content type
            $mimeType = $shipment->label_format === 'ZPL' ? 'application/zpl' : 'application/pdf';
            $extension = strtolower($shipment->label_format);
            $filename = $shipment->barcode . '.' . $extension;

            // Clean output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Set headers for download
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($labelContent));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: public');

            // Output label
            echo $labelContent;

            // Exit to prevent Joomla from adding extra output
            $app->close();

        } catch (\Exception $e) {
            Log::add(
                'PostNL label print failed: ' . $e->getMessage(),
                Log::ERROR,
                'com_postnl'
            );

            $app = Factory::getApplication();
            $app->enqueueMessage(
                Text::sprintf('COM_POSTNL_ERROR_GENERAL', $e->getMessage()),
                'error'
            );

            $orderId = $app->input->getInt('id', 0);
            $this->setRedirect(
                Route::_('index.php?option=com_postnl&view=order&id=' . $orderId, false)
            );
        }
    }

    /**
     * Load order from EasyStore database
     *
     * @param   int  $orderId  Order ID
     *
     * @return  object|null  Order object or null
     *
     * @since   1.0.0
     */
    protected function loadOrder(int $orderId): ?object
    {
        $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select('*')
            ->from($db->quoteName('#__easystore_orders'))
            ->where($db->quoteName('id') . ' = ' . (int) $orderId);

        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Create PostNL shipment via API
     *
     * @param   object             $order   Order object
     * @param   \Joomla\Registry\Registry  $params  Plugin parameters
     *
     * @return  array   Result array with barcode, tracking_url, label_content, label_format
     *
     * @throws  \RuntimeException
     *
     * @since   1.0.0
     */
    protected function createShipment(object $order, \Joomla\Registry\Registry $params): array
    {
        $testMode = (bool) $params->get('test_mode', 1);

        // Build API client
        $http = \Joomla\CMS\Http\HttpFactory::getHttp();
        $baseUrl = $params->get('api_base_url', 'https://api.postnl.nl');
        $apiKey = $params->get('api_key', '');
        $authType = $params->get('auth_type', 'apikey');
        $labelFormat = $params->get('label_format', 'PDF');

        $defaults = [
            'auth_type'    => $authType,
            'label_format' => $labelFormat,
        ];

        $client = new PostnlClient($http, $baseUrl, $apiKey, $defaults);

        // Build payload
        $payload = OrderHelper::buildPostnlPayload($order, $params);

        // Create shipment (or mock in test mode)
        if ($testMode) {
            $response = $client->generateMockResponse($payload);
        } else {
            $response = $client->createShipment($payload);

            // Confirm if configured
            if ($params->get('auto_confirm', 1)) {
                $barcode = $response['ResponseShipments'][0]['Barcode'] ?? '';
                if ($barcode) {
                    $client->confirmShipment($barcode);
                }
            }
        }

        // Extract label
        $labelInfo = $client->getLabelFromResponse($response);

        // Get shipping address for T&T URL
        $shippingAddress = json_decode($order->shipping_address ?? '{}');
        $trackingLang = $params->get('tracking_lang', 'NL');

        $trackingUrl = OrderHelper::buildTtUrl(
            $labelInfo['barcode'],
            $shippingAddress->postcode ?? '',
            $shippingAddress->country_code ?? 'NL',
            $trackingLang
        );

        return [
            'barcode'       => $labelInfo['barcode'],
            'tracking_url'  => $trackingUrl,
            'label_content' => $labelInfo['content'],
            'label_format'  => strtoupper($labelFormat),
        ];
    }

    /**
     * Save shipment to database
     *
     * @param   int    $orderId  Order ID
     * @param   array  $result   Shipment result
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function saveShipment(int $orderId, array $result): void
    {
        $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $now = Factory::getDate()->toSql();

        $data = (object) [
            'order_id'      => $orderId,
            'barcode'       => $result['barcode'],
            'tracking_url'  => $result['tracking_url'],
            'label_content' => $result['label_content'],
            'label_format'  => $result['label_format'],
            'status'        => 'created',
            'created_date'  => $now,
            'modified_date' => $now,
        ];

        $db->insertObject('#__postnl_shipments', $data);
    }
}
