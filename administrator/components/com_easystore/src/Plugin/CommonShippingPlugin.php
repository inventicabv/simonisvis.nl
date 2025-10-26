<?php

/**
 * @package     EasyStore.Administrator
 * @subpackage  EasyStore.Plugin
 *
 * @copyright   (C) 2023 - 2025 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Component\EasyStore\Administrator\Plugin;

use Joomla\Event\Event;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Application\CMSApplication;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

abstract class CommonShippingPlugin extends CMSPlugin implements SubscriberInterface
{
    /**
     * The application object
     *
     * @var CMSApplication
     *
     * @since 1.0.0
     */
    protected $app;

    /**
     * Affects constructor behavior. If true, language files will be loaded automatically.
     *
     * @var    boolean
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Constructor
     *
     * @param   DispatcherInterface  $dispatcher  The event dispatcher
     * @param   array                $config      An optional associative array of configuration settings.
     *                                            Recognized key values include 'name', 'group', 'params', 'language'
     *                                            (this list is not meant to be comprehensive).
     *
     * @since   1.0.0
     */
    public function __construct(DispatcherInterface $dispatcher, array $config = [])
    {
        parent::__construct($dispatcher, $config);
    }

    /**
     * Method to get the subscribed events
     *
     * @return  array  The array of events to subscribe to
     *
     * @since   1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onEasyStoreGetShippingMethods' => 'onEasyStoreGetShippingMethods',
        ];
    }

    /**
     * Method to get the shipping methods
     *
     * @param Event $event The event object
     *
     * @return void
     *
     * @since 1.0.0
     */
    abstract public function onEasyStoreGetShippingMethods(Event $event); // phpcs:ignore PSR1.Methods.CamelCapsMethodName


    /**
     * Method to format the shipping method
     *
     * @param object $rate          The rate object
     * @param string $serviceName   The service name
     * @param float  $handlingFee   The handling fee
     *
     * @return array The formatted shipping method
     *
     * @since 1.0.0
     */
    protected function formatShippingMethod($rate, $serviceName, $handlingFee = 0)
    {
        $totalFee = $rate->total_charge + $handlingFee;

        return [
            'uuid' => $this->generate_uuid(),
            'id' => strtolower($this->_name . '_' . $rate->service_type),
            'name' => ucwords(strtolower($serviceName)),
            'rate' => $totalFee,
            'rate_with_currency' => $rate->currency . $totalFee,
            'estimate' => $rate->delivery_date,
            'provider' => $this->_name
        ];
    }

    public function generate_uuid()
    {
        $b = random_bytes(16);
        $b[6] = chr(ord($b[6]) & 0x0f | 0x40);
        $b[8] = chr(ord($b[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }

    public function formatShippingTitle($title)
    {
        $title = str_replace(['-', '_'], ' ', $title);
        return ucwords(strtolower($title));
    }

    /**
     * Method to remove duplicate items from an array based on multiple keys how to use
     * $uniqueItems = $this->uniqueByKeys($items, ['service', 'price']);
     *
     * @param array $items The array of items
     * @param array $keys  The array of keys to use for uniqueness
     *
     * @return array The unique array of items
     *
     * @since 1.0.0
     */
    public function uniqueByKeys(array $items, array $keys)
    {
        $seen = [];
        $unique = [];

        foreach ($items as $item) {
            $key = '';
            foreach ($keys as $k) {
                $key .= $item[$k] . '|';
            }

            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $item;
            }
        }

        return $unique;
    }
}
