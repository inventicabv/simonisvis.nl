<?php

/**
 * @package     PlgEasystoreshippingPostnl
 * @subpackage  Extension
 *
 * @copyright   Copyright (C) 2025. All rights reserved.
 * @license     GNU General Public License version 3; see LICENSE
 */

namespace PlgEasystoreshippingPostnl\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Http\HttpFactory;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use PlgEasystoreshippingPostnl\PostnlClient;
use PlgEasystoreshippingPostnl\OrderHelper;

/**
 * PostNL Shipping Plugin for EasyStore
 *
 * @since 1.0.0
 */
class PostnlShipping extends CMSPlugin implements SubscriberInterface
{
    /**
     * Load the language file on instantiation
     *
     * @var    boolean
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Application object
     *
     * @var    \Joomla\CMS\Application\CMSApplication
     * @since  1.0.0
     */
    protected $app;

    /**
     * Database object
     *
     * @var    \Joomla\Database\DatabaseInterface
     * @since  1.0.0
     */
    protected $db;

    /**
     * Constructor
     *
     * @param   \Joomla\Event\DispatcherInterface  $dispatcher  The event dispatcher
     * @param   array                               $config      Configuration array
     *
     * @since   1.0.0
     */
    public function __construct($dispatcher, array $config = [])
    {
        parent::__construct($dispatcher, $config);

        // Get database instance
        $this->db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
    }

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onEasyStoreGetShippingMethods' => 'onEasyStoreGetShippingMethods',
            'onAfterRoute'                  => 'onAfterRoute',
        ];
    }

    /**
     * Get shipping methods for checkout (required by EasyStore)
     *
     * @param   Event  $event  The event object
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onEasyStoreGetShippingMethods(Event $event): void
    {
        // This method is for checkout shipping rate calculation
        // Note: Actual label creation happens via admin action, not here

        try {
            $arguments = $event->getArguments();
            $shippingAddress = $arguments['subject']->shipping_address ?? null;

            if (!$shippingAddress) {
                return;
            }

            // Get existing methods
            $shippingMethods = $event->getArgument('shippingMethods', []);

            // Get configured shipping rate or use default
            $defaultRate = (float) $this->params->get('default_shipping_rate', 6.95);

            // Get currency settings
            $settings = \JoomShaper\Component\EasyStore\Administrator\Helper\SettingsHelper::getSettings();
            $currencySettings = $settings->get('general.currency', 'EUR:€');
            $currencyFormat = $settings->get('general.currencyFormat', 'short');
            $parts = explode(':', $currencySettings);
            $currencyFull = $parts[0] ?? 'EUR';
            $currencyShort = $parts[1] ?? '€';

            $currency = ($currencyFormat === 'short') ? $currencyShort : $currencyFull . ' ';

            // Add PostNL as available shipping method
            $shippingMethods[] = [
                'uuid'               => $this->generateUuid(),
                'name'               => $this->formatShippingTitle(Text::_('PLG_EASYSTORESHIPPING_POSTNL')),
                'rate'               => $defaultRate,
                'rate_with_currency' => $currency . number_format($defaultRate, 2),
                'estimate'           => Text::_('PLG_EASYSTORESHIPPING_POSTNL_ESTIMATE'),
                'provider'           => 'postnl',
            ];

            $event->setArgument('shippingMethods', $shippingMethods);

        } catch (\Exception $e) {
            Log::add(
                'Error in onEasyStoreGetShippingMethods: ' . $e->getMessage(),
                Log::ERROR,
                'plg_easystoreshipping_postnl'
            );
        }
    }

    /**
     * Handle routing to check for PostNL action
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onAfterRoute(): void
    {
        // Only run in administrator
        if (!$this->app->isClient('administrator')) {
            return;
        }

        $input = $this->app->input;
        $task  = $input->get('task', '');

        // Check if this is our PostNL create label action
        if ($task === 'order.postnlCreate' || $task === 'postnlCreate') {
            $this->handleCreateShipment();
        }
    }

    /**
     * Handle shipment creation action
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function handleCreateShipment(): void
    {
        try {
            // Get order ID
            $orderId = $this->app->input->getInt('id', 0);

            if (!$orderId) {
                throw new \RuntimeException(Text::_('PLG_EASYSTORESHIPPING_POSTNL_ERROR_NO_ORDER'));
            }

            // Load order
            $order = $this->loadOrder($orderId);

            if (!$order) {
                throw new \RuntimeException(Text::_('PLG_EASYSTORESHIPPING_POSTNL_ERROR_ORDER_NOT_FOUND'));
            }

            // Check if already has tracking
            if (!empty($order->tracking_number)) {
                $this->app->enqueueMessage(
                    Text::sprintf('PLG_EASYSTORESHIPPING_POSTNL_WARNING_ALREADY_HAS_TRACKING', $order->tracking_number),
                    'warning'
                );
            }

            // Create shipment
            $result = $this->createShipment($order);

            // Save tracking info
            OrderHelper::saveTracking($orderId, $result['barcode'], $result['tracking_url']);

            // Save label
            OrderHelper::saveLabel($orderId, $result['barcode'], $result['label_content'], $result['label_format']);

            // Send tracking email if configured
            if ($this->params->get('auto_send_tracking', 1)) {
                OrderHelper::sendTrackingEmail($order, $result['barcode'], $result['tracking_url'], $this->params);
            }

            // Success message
            $this->app->enqueueMessage(
                Text::sprintf(
                    'PLG_EASYSTORESHIPPING_POSTNL_SUCCESS_CREATED',
                    $result['barcode'],
                    $result['tracking_url']
                ),
                'success'
            );

            // Redirect back to order
            $this->app->redirect('index.php?option=com_easystore&view=order&layout=edit&id=' . $orderId);

        } catch (\Exception $e) {
            Log::add(
                'PostNL shipment creation failed: ' . $e->getMessage(),
                Log::ERROR,
                'plg_easystoreshipping_postnl'
            );

            $this->app->enqueueMessage(
                Text::sprintf('PLG_EASYSTORESHIPPING_POSTNL_ERROR_GENERAL', $e->getMessage()),
                'error'
            );

            // Redirect back to order
            if ($orderId > 0) {
                $this->app->redirect('index.php?option=com_easystore&view=order&layout=edit&id=' . $orderId);
            }
        }
    }

    /**
     * Create PostNL shipment
     *
     * @param   object  $order  Order object
     *
     * @return  array   Result array with barcode, tracking_url, label_content, label_format
     *
     * @throws  \RuntimeException
     *
     * @since   1.0.0
     */
    protected function createShipment(object $order): array
    {
        $testMode = (bool) $this->params->get('test_mode', 1);

        // Build API client
        $http       = HttpFactory::getHttp();
        $baseUrl    = $this->params->get('api_base_url', 'https://api.postnl.nl');
        $apiKey     = $this->params->get('api_key', '');
        $authType   = $this->params->get('auth_type', 'apikey');
        $labelFormat = $this->params->get('label_format', 'PDF');

        $defaults = [
            'auth_type'    => $authType,
            'label_format' => $labelFormat,
        ];

        $client = new PostnlClient($http, $baseUrl, $apiKey, $defaults);

        // Build payload
        $payload = OrderHelper::buildPostnlPayload($order, $this->params);

        // Create shipment (or mock in test mode)
        if ($testMode) {
            $response = $client->generateMockResponse($payload);
        } else {
            $response = $client->createShipment($payload);

            // Confirm if configured
            if ($this->params->get('auto_confirm', 1)) {
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
        $trackingLang    = $this->params->get('tracking_lang', 'NL');

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
     * Load order from database
     *
     * @param   int  $orderId  Order ID
     *
     * @return  object|null  Order object or null
     *
     * @since   1.0.0
     */
    protected function loadOrder(int $orderId): ?object
    {
        $db    = $this->db;
        $query = $db->getQuery(true);

        $query->select('*')
            ->from($db->quoteName('#__easystore_orders'))
            ->where($db->quoteName('id') . ' = ' . (int) $orderId);

        $db->setQuery($query);

        return $db->loadObject();
    }

    /**
     * Generate UUID
     *
     * @return  string
     *
     * @since   1.0.0
     */
    protected function generateUuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr(ord($b[6]) & 0x0f | 0x40);
        $b[8] = chr(ord($b[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }

    /**
     * Format shipping method title
     *
     * @param   string  $title  Raw title
     *
     * @return  string  Formatted title
     *
     * @since   1.0.0
     */
    protected function formatShippingTitle(string $title): string
    {
        $title = str_replace(['-', '_'], ' ', $title);
        return ucwords(strtolower($title));
    }
}
